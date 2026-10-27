
<?php
session_start();
date_default_timezone_set("Asia/Kolkata");

if (
    !isset($_SESSION["faculty_logged_in"]) ||
    $_SESSION["faculty_logged_in"] !== true
) {
    header("Location: login.php");
    exit();
}

require_once "../config/db.php";

$faculty_db_id = (int)($_SESSION["faculty_db_id"] ?? 0);

$success = false;
$error = "";
$lecture = null;

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $subject = trim($_POST["subject"] ?? "");
    $lecture_date = trim($_POST["lecture_date"] ?? "");
    $start_time = trim($_POST["start_time"] ?? "");
    $end_time = trim($_POST["end_time"] ?? "");
    $room = trim($_POST["room"] ?? "");
    $semester = trim($_POST["semester"] ?? "");

    $radius = filter_input(INPUT_POST, "radius", FILTER_VALIDATE_INT);

    $attendance_open_minutes = filter_input(
        INPUT_POST,
        "attendance_open_minutes",
        FILTER_VALIDATE_INT
    );

    $latitude = filter_input(INPUT_POST, "latitude", FILTER_VALIDATE_FLOAT);
    $longitude = filter_input(INPUT_POST, "longitude", FILTER_VALIDATE_FLOAT);

    /* Validate required fields */

    if (
        $subject === "" ||
        $lecture_date === "" ||
        $start_time === "" ||
        $end_time === "" ||
        $room === "" ||
        $semester === ""
    ) {
        $error = "Please fill in all required fields.";
    }

    elseif (!in_array($semester, ["1", "2", "3", "4", "5", "6", "7", "8"], true)) {
        $error = "Please select a valid semester.";
    }

    elseif ($radius === false || $radius === null || $radius < 5 || $radius > 1000) {
        $error = "Radius must be between 5 and 1000 metres.";
    }

    elseif (
        $attendance_open_minutes === false ||
        !in_array($attendance_open_minutes, [10, 15, 20, 30], true)
    ) {
        $error = "Please select a valid attendance window.";
    }

    elseif (
        $latitude === false ||
        $latitude === null ||
        $longitude === false ||
        $longitude === null ||
        $latitude < -90 ||
        $latitude > 90 ||
        $longitude < -180 ||
        $longitude > 180
    ) {
        $error = "Please capture a valid classroom location.";
    }

    else {

        /* Validate date and time */

        $startDateTime = DateTime::createFromFormat(
            "!Y-m-d H:i",
            $lecture_date . " " . $start_time
        );

        $endDateTime = DateTime::createFromFormat(
            "!Y-m-d H:i",
            $lecture_date . " " . $end_time
        );

        $now = new DateTime();

        if (!$startDateTime || !$endDateTime) {
            $error = "Invalid lecture date or time.";
        }

        elseif ($endDateTime <= $startDateTime) {
            $error = "Lecture end time must be after start time.";
        }

        elseif ($endDateTime <= $now) {
            $error = "Lecture end time must be in the future.";
        }

        else {

            /* Calculate attendance deadline */

            $attendanceDeadline = clone $startDateTime;

            $attendanceDeadline->modify(
                "+" . $attendance_open_minutes . " minutes"
            );

            if ($attendanceDeadline > $endDateTime) {
                $attendanceDeadline = clone $endDateTime;
            }

            $attendance_deadline = $attendanceDeadline->format("H:i:s");

            /* Determine lecture status */

            if ($now < $startDateTime) {
                $lecture_status = "scheduled";
            } elseif ($now < $endDateTime) {
                $lecture_status = "active";
            } else {
                $lecture_status = "completed";
            }

            /* Generate secure QR token */

            try {
                $qr_token = bin2hex(random_bytes(32));
            } catch (Throwable $ex) {
                $qr_token = "";
                $error = "Unable to generate a secure QR token.";
            }

            /* Save lecture */

            if ($error === "") {

                $stmt = $conn->prepare(
                    "INSERT INTO lectures
                    (
                        faculty_id,
                        subject,
                        lecture_date,
                        start_time,
                        end_time,
                        room,
                        semester,
                        qr_token,
                        latitude,
                        longitude,
                        radius,
                        attendance_open_minutes,
                        attendance_deadline,
                        status
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );

                if (!$stmt) {
                    $error = "Database error: " . $conn->error;
                } else {

                    $stmt->bind_param(
                        "isssssssddiiss",
                        $faculty_db_id,
                        $subject,
                        $lecture_date,
                        $start_time,
                        $end_time,
                        $room,
                        $semester,
                        $qr_token,
                        $latitude,
                        $longitude,
                        $radius,
                        $attendance_open_minutes,
                        $attendance_deadline,
                        $lecture_status
                    );

                    if ($stmt->execute()) {

                        $new_lecture_id = $conn->insert_id;

                        $lecture = [
                            "id" => $new_lecture_id,
                            "subject" => $subject,
                            "lecture_date" => $lecture_date,
                            "start_time" => $start_time,
                            "end_time" => $end_time,
                            "room" => $room,
                            "semester" => $semester,
                            "latitude" => $latitude,
                            "longitude" => $longitude,
                            "radius" => $radius,
                            "attendance_open_minutes" => $attendance_open_minutes,
                            "attendance_deadline" => $attendance_deadline,
                            "qr_token" => $qr_token,
                            "status" => $lecture_status
                        ];

                        $success = true;

                    } else {
                        $error = "Failed to create lecture: " . $stmt->error;
                    }

                    $stmt->close();
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Start Lecture - Attendify</title>

<link rel="stylesheet" href="../dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

<style>
.lecture-form-page{max-width:950px;margin:0 auto;padding:40px 20px 60px}
.lecture-form-card{background:#fff;padding:32px;border-radius:18px;border:1px solid #e5e7eb;box-shadow:0 12px 35px rgba(30,35,70,.06)}
.lecture-form-card h1{margin:0 0 10px;color:#202336;font-size:28px}
.lecture-form-card>p{color:#85899b;margin-bottom:28px;line-height:1.6}
.lecture-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.lecture-field{margin-bottom:20px}
.lecture-field label{display:block;margin-bottom:9px;color:#303347;font-size:13px;font-weight:700}
.lecture-field input,.lecture-field select{width:100%;height:48px;padding:0 14px;border:1px solid #dfe1eb;border-radius:10px;background:#fafaff;color:#303347;font-size:14px;outline:none;box-sizing:border-box}
.lecture-field input:focus,.lecture-field select:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.1)}
.lecture-field small{display:block;margin-top:7px;color:#85899b;font-size:12px;line-height:1.5}
.location-box{margin:5px 0 25px;padding:22px;background:#f8f8ff;border:1px solid #e3e4ff;border-radius:14px}
.location-box h3{margin:0 0 8px;color:#303347;font-size:16px}
.location-box p{color:#85899b;font-size:13px;line-height:1.6}
.location-btn{padding:12px 18px;border:none;border-radius:9px;background:#4f46e5;color:white;font-weight:700;cursor:pointer}
.location-status{margin-top:12px;font-size:13px;font-weight:600}
.coordinates-box{display:none;margin-top:15px;padding:14px;background:white;border:1px solid #e3e4ff;border-radius:10px;font-size:13px;line-height:1.8}
.create-lecture-btn{width:100%;padding:15px;border:none;border-radius:11px;background:linear-gradient(135deg,#4f46e5,#6366f1);color:white;font-size:15px;font-weight:700;cursor:pointer}
.lecture-alert{margin-bottom:22px;padding:15px 18px;border-radius:10px;font-size:14px;line-height:1.6}
.error-alert{background:#fff1f2;color:#be123c;border:1px solid #fecdd3}
.success-alert{background:#ecfdf5;color:#047857;border:1px solid #a7f3d0}
.generated-qr{margin-top:28px;padding:25px;text-align:center;background:#f8f8ff;border:1px solid #e3e4ff;border-radius:16px}
#lectureQR{display:flex;justify-content:center;align-items:center;margin:20px auto;padding:15px;background:white;border-radius:12px;width:fit-content;min-height:250px}
.generated-qr p{color:#85899b;font-size:13px;line-height:1.7}
.generated-location{margin:18px auto;max-width:450px;padding:16px;background:white;border:1px solid #e3e4ff;border-radius:12px;text-align:left}
.qr-download-btn{display:inline-block;padding:12px 20px;margin:8px;border-radius:9px;background:#4f46e5;color:white;text-decoration:none;font-weight:700;border:none;cursor:pointer}
@media(max-width:650px){.lecture-form-grid{grid-template-columns:1fr;gap:0}.lecture-form-card{padding:22px 17px}.lecture-form-card h1{font-size:24px}}
</style>
</head>

<body>

<nav class="dashboard-navbar">
    <div class="dashboard-logo">
        <i class="fa-solid fa-calendar-check"></i>
        Attendify
    </div>

    <div class="dashboard-nav-right">
        <a href="dashboard.php" class="logout-btn">
            <i class="fa-solid fa-arrow-left"></i> Dashboard
        </a>
        <a href="logout.php" class="logout-btn">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
    </div>
</nav>

<main class="lecture-form-page">
<section class="lecture-form-card">

<h1><i class="fa-solid fa-calendar-plus" style="color:#5146e5"></i> Start a Lecture</h1>
<p>Create a lecture, select its semester, set the attendance window and generate a QR code.</p>

<?php if ($error !== ""): ?>
    <div class="lecture-alert error-alert">
        <i class="fa-solid fa-circle-exclamation"></i>
        <?= e($error) ?>
    </div>
<?php endif; ?>

<?php if ($success && $lecture): ?>

    <div class="lecture-alert success-alert">
        <i class="fa-solid fa-circle-check"></i>
        Lecture created successfully!
    </div>

    <div class="generated-qr">
        <h2><?= e($lecture["subject"]) ?></h2>

        <p>
            Room: <?= e($lecture["room"]) ?><br>
            Semester: <?= e($lecture["semester"]) ?><br>

            <?= e(date("d M Y", strtotime($lecture["lecture_date"]))) ?><br>

            <?= e(date("h:i A", strtotime($lecture["start_time"]))) ?>
            -
            <?= e(date("h:i A", strtotime($lecture["end_time"]))) ?>
        </p>

        <div id="lectureQR"></div>

        <p>
            Attendance window:
            <strong><?= e($lecture["attendance_open_minutes"]) ?> minutes</strong><br>

            Attendance cutoff:
            <strong><?= e(date("h:i A", strtotime($lecture["attendance_deadline"]))) ?></strong><br>

            Location radius:
            <strong><?= e($lecture["radius"]) ?> metres</strong>
        </p>

        <div class="generated-location">
            <strong><i class="fa-solid fa-location-dot"></i> Classroom Location</strong><br><br>

            Latitude: <strong><?= e($lecture["latitude"]) ?></strong><br>
            Longitude: <strong><?= e($lecture["longitude"]) ?></strong>
        </div>

        <button type="button" class="qr-download-btn" onclick="downloadQR()">
            <i class="fa-solid fa-download"></i> Download QR
        </button>

        <a href="view_lectures.php" class="qr-download-btn">
            <i class="fa-solid fa-book-open"></i> View Lectures
        </a>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
    const lectureToken = <?= json_encode($lecture["qr_token"]) ?>;

    new QRCode(document.getElementById("lectureQR"), {
        text: lectureToken,
        width: 250,
        height: 250,
        colorDark: "#17192b",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });

    function downloadQR() {
        const box = document.getElementById("lectureQR");
        const canvas = box.querySelector("canvas");

        if (!canvas) {
            alert("QR code is not ready.");
            return;
        }

        const link = document.createElement("a");
        link.href = canvas.toDataURL("image/png");
        link.download = "Attendify-Lecture-QR.png";
        link.click();
    }
    </script>

<?php else: ?>

<form method="POST" action="start_lecture.php" id="lectureForm">

<div class="lecture-form-grid">

    <div class="lecture-field">
        <label for="subject">Subject Name *</label>
        <input type="text" id="subject" name="subject" maxlength="150"
               placeholder="e.g. Computer Networks" required>
    </div>

    <div class="lecture-field">
        <label for="room">Classroom / Room *</label>
        <input type="text" id="room" name="room" maxlength="100"
               placeholder="e.g. Room 204" required>
    </div>

    <div class="lecture-field">
        <label for="lecture_date">Lecture Date *</label>
        <input type="date" id="lecture_date" name="lecture_date"
               min="<?= date("Y-m-d") ?>" value="<?= date("Y-m-d") ?>" required>
    </div>

    <div class="lecture-field">
        <label for="semester">Semester *</label>
        <select name="semester" id="semester" required>
            <option value="">Select Semester</option>
            <?php for ($i = 1; $i <= 8; $i++): ?>
                <option value="<?= $i ?>">Semester <?= $i ?></option>
            <?php endfor; ?>
        </select>
    </div>

    <div class="lecture-field">
        <label for="start_time">Start Time *</label>
        <input type="time" id="start_time" name="start_time" required>
    </div>

    <div class="lecture-field">
        <label for="end_time">End Time *</label>
        <input type="time" id="end_time" name="end_time" required>
    </div>

    <div class="lecture-field">
        <label for="attendance_open_minutes">Attendance Window *</label>
        <select name="attendance_open_minutes" id="attendance_open_minutes" required>
            <option value="10">First 10 Minutes</option>
            <option value="15">First 15 Minutes</option>
            <option value="20" selected>First 20 Minutes</option>
            <option value="30">First 30 Minutes</option>
        </select>
        <small>Attendance closes after the selected time or when the lecture ends, whichever comes first.</small>
    </div>

    <div class="lecture-field">
        <label for="radius">Location Radius (Metres) *</label>
        <input type="number" id="radius" name="radius" min="5" max="1000" value="5" required>
        <small>Students must be within the selected distance of the classroom.</small>
    </div>

</div>

<div class="location-box">
    <h3><i class="fa-solid fa-location-dot" style="color:#5146e5"></i> Classroom Location</h3>

    <p>Stand at the classroom location and capture the faculty device's GPS coordinates.</p>

    <input type="hidden" name="latitude" id="latitude" required>
    <input type="hidden" name="longitude" id="longitude" required>

    <button type="button" class="location-btn" id="captureLocationBtn">
        <i class="fa-solid fa-crosshairs"></i> Capture Current Location
    </button>

    <div class="location-status" id="locationStatus">Location not captured yet.</div>

    <div class="coordinates-box" id="coordinatesBox">
        <strong>Latitude:</strong> <span id="displayLatitude">-</span><br>
        <strong>Longitude:</strong> <span id="displayLongitude">-</span><br>
        <strong>Accuracy:</strong> <span id="displayAccuracy">-</span> metres
    </div>
</div>

<button type="submit" class="create-lecture-btn">
    <i class="fa-solid fa-qrcode"></i> Create Lecture & Generate QR
</button>

</form>

<?php endif; ?>

</section>
</main>

<script>
const captureBtn = document.getElementById("captureLocationBtn");

if (captureBtn) {
    captureBtn.addEventListener("click", function () {
        const status = document.getElementById("locationStatus");
        const coordinatesBox = document.getElementById("coordinatesBox");

        if (!navigator.geolocation) {
            status.textContent = "Your browser does not support location services.";
            status.style.color = "#dc2626";
            return;
        }

        status.textContent = "Getting your current location...";
        status.style.color = "#5146e5";

        captureBtn.disabled = true;
        captureBtn.textContent = "Getting Location...";

        navigator.geolocation.getCurrentPosition(
            function (position) {
                const latitude = position.coords.latitude;
                const longitude = position.coords.longitude;
                const accuracy = position.coords.accuracy;

                document.getElementById("latitude").value = latitude;
                document.getElementById("longitude").value = longitude;

                document.getElementById("displayLatitude").textContent = latitude.toFixed(8);
                document.getElementById("displayLongitude").textContent = longitude.toFixed(8);
                document.getElementById("displayAccuracy").textContent = Math.round(accuracy);

                coordinatesBox.style.display = "block";
                status.textContent = "Location captured successfully.";
                status.style.color = "#16834a";

                captureBtn.disabled = false;
                captureBtn.textContent = "Location Captured";
            },
            function (error) {
                let message = "Unable to get location.";

                if (error.code === 1) {
                    message = "Location permission denied. Please allow location access.";
                } else if (error.code === 2) {
                    message = "Location unavailable. Turn on GPS and try again.";
                } else if (error.code === 3) {
                    message = "Location request timed out. Please try again.";
                }

                status.textContent = message;
                status.style.color = "#dc2626";

                captureBtn.disabled = false;
                captureBtn.textContent = "Capture Current Location";
            },
            {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 0
            }
        );
    });
}

const lectureForm = document.getElementById("lectureForm");

if (lectureForm) {
    lectureForm.addEventListener("submit", function (event) {
        const latitude = document.getElementById("latitude").value;
        const longitude = document.getElementById("longitude").value;

        if (latitude === "" || longitude === "") {
            event.preventDefault();
            alert("Please capture the classroom location before creating the lecture.");
        }
    });
}
</script>

</body>
</html>
```
