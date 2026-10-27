<?php
session_start();
date_default_timezone_set("Asia/Kolkata");

header("Content-Type: application/json; charset=utf-8");

function respond($status, $message, $extra = []) {
    http_response_code($status);

    echo json_encode(array_merge([
        "success" => $status >= 200 && $status < 300,
        "message" => $message
    ], $extra));

    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    respond(405, "POST request required.");
}

if (
    empty($_SESSION["student_logged_in"]) ||
    empty($_SESSION["student_db_id"])
) {
    respond(401, "Please sign in again.");
}

require_once "../config/db.php";

$payload = json_decode(file_get_contents("php://input"), true);

if (!is_array($payload)) {
    respond(400, "Invalid request data.");
}

if (
    empty($_SESSION["student_csrf"]) ||
    !hash_equals(
        $_SESSION["student_csrf"],
        (string)($payload["csrf"] ?? "")
    )
) {
    respond(403, "Security check failed. Refresh the page.");
}

$token = trim((string)($payload["qr_token"] ?? ""));

$lat = filter_var(
    $payload["latitude"] ?? null,
    FILTER_VALIDATE_FLOAT
);

$lon = filter_var(
    $payload["longitude"] ?? null,
    FILTER_VALIDATE_FLOAT
);

$accuracy = filter_var(
    $payload["accuracy"] ?? 9999,
    FILTER_VALIDATE_FLOAT
);

$studentDbId = (int)$_SESSION["student_db_id"];

if (!preg_match('/^[a-f0-9]{32,128}$/i', $token)) {
    respond(422, "Invalid QR token.");
}

if (
    $lat === false ||
    $lon === false ||
    $lat < -90 ||
    $lat > 90 ||
    $lon < -180 ||
    $lon > 180
) {
    respond(422, "Valid location coordinates are required.");
}

if ($accuracy === false || $accuracy > 100) {
    respond(422, "GPS accuracy is too low. Try again.");
}

/* Get student semester */

$stmt = $conn->prepare(
    "SELECT id, semester
     FROM students
     WHERE id = ?
     LIMIT 1"
);

$stmt->bind_param("i", $studentDbId);
$stmt->execute();

$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    respond(401, "Student account was not found.");
}

/* Get lecture linked to QR token */

$stmt = $conn->prepare(
    "SELECT id, subject, lecture_date, start_time, end_time,
            semester, latitude, longitude, radius,
            attendance_open_minutes, attendance_deadline, status
     FROM lectures
     WHERE qr_token = ?
     LIMIT 1"
);

$stmt->bind_param("s", $token);
$stmt->execute();

$lecture = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$lecture) {
    respond(404, "QR token is invalid.");
}

if ((string)$lecture["semester"] !== (string)$student["semester"]) {
    respond(403, "This lecture is not for your semester.");
}

if (
    $lecture["status"] === "cancelled" ||
    $lecture["status"] === "completed"
) {
    respond(403, "This lecture has ended or was cancelled.");
}

/* Check lecture time */

$start = strtotime(
    $lecture["lecture_date"] . " " . $lecture["start_time"]
);

$end = strtotime(
    $lecture["lecture_date"] . " " .
    ($lecture["end_time"] ?: "23:59:59")
);

if (!empty($lecture["attendance_deadline"])) {

    $deadline = strtotime(
        $lecture["lecture_date"] . " " .
        $lecture["attendance_deadline"]
    );

} else {

    $deadline = $start +
        ((int)$lecture["attendance_open_minutes"] * 60);
}

$cutoff = min($end, $deadline);

if (time() < $start) {
    respond(403, "Attendance is not open yet.");
}

if (time() > $cutoff) {
    respond(403, "Attendance window is closed.");
}

/* Check lecture coordinates */

if (
    $lecture["latitude"] === null ||
    $lecture["longitude"] === null
) {
    respond(422, "Faculty has not saved the lecture location.");
}

/* Calculate distance using Haversine formula */

$earthRadius = 6371000;

$lat1 = deg2rad((float)$lat);
$lat2 = deg2rad((float)$lecture["latitude"]);

$dLat = deg2rad(
    (float)$lecture["latitude"] - (float)$lat
);

$dLon = deg2rad(
    (float)$lecture["longitude"] - (float)$lon
);

$a = sin($dLat / 2) ** 2
    + cos($lat1) * cos($lat2) * sin($dLon / 2) ** 2;

$distance = $earthRadius * 2 * atan2(
    sqrt($a),
    sqrt(max(0, 1 - $a))
);

$radius = max(5, (int)$lecture["radius"]);

if ($distance > $radius) {
    respond(
        403,
        "You are outside the allowed classroom radius. Distance: " .
        round($distance) . " metres."
    );
}

/* Save attendance in existing attendance table */

$stmt = $conn->prepare(
    "INSERT INTO attendance
        (lecture_id, student_id, latitude, longitude, status)
     VALUES (?, ?, ?, ?, 'present')"
);

$lectureId = (int)$lecture["id"];

$stmt->bind_param(
    "iidd",
    $lectureId,
    $studentDbId,
    $lat,
    $lon
);

if ($stmt->execute()) {

    $stmt->close();

    respond(
        200,
        "Attendance marked successfully for " . $lecture["subject"] . ".",
        ["distance_m" => round($distance, 1)]
    );
}

$errorNumber = $stmt->errno;
$stmt->close();

if ($errorNumber === 1062) {
    respond(409, "Your attendance is already marked for this lecture.");
}

respond(500, "Unable to save attendance. Please contact your faculty.");