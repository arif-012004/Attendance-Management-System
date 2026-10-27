<?php
session_start();
date_default_timezone_set("Asia/Kolkata");

if (
    empty($_SESSION["student_logged_in"]) ||
    empty($_SESSION["student_db_id"])
) {
    header("Location: login.php");
    exit;
}

require_once "../config/db.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$studentDbId = (int)$_SESSION["student_db_id"];

$csrf = $_SESSION["student_csrf"] ?? bin2hex(random_bytes(32));
$_SESSION["student_csrf"] = $csrf;

$stmt = $conn->prepare(
    "SELECT id, student_id, name, email, phone, course, semester
     FROM students
     WHERE id = ?
     LIMIT 1"
);

$stmt->bind_param("i", $studentDbId);
$stmt->execute();

$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$_SESSION["student_name"] = $student["name"];
$_SESSION["student_semester"] = (string)$student["semester"];

$semester = (string)$student["semester"];
$today = date("Y-m-d");

/* Today's lectures for this student's semester */

$stmt = $conn->prepare(
    "SELECT id, subject, lecture_date, start_time, end_time,
            room, semester, radius, attendance_deadline,
            attendance_open_minutes, status
     FROM lectures
     WHERE semester = ?
       AND lecture_date = ?
       AND status <> 'cancelled'
     ORDER BY start_time ASC"
);

$stmt->bind_param("ss", $semester, $today);
$stmt->execute();

$result = $stmt->get_result();
$lectures = [];

while ($row = $result->fetch_assoc()) {

    $start = strtotime(
        $row["lecture_date"] . " " . $row["start_time"]
    );

    $end = strtotime(
        $row["lecture_date"] . " " .
        ($row["end_time"] ?: "23:59:59")
    );

    if (!empty($row["attendance_deadline"])) {
        $deadline = strtotime(
            $row["lecture_date"] . " " .
            $row["attendance_deadline"]
        );
    } else {
        $deadline = $start +
            ((int)$row["attendance_open_minutes"] * 60);
    }

    $cutoff = min($end, $deadline);

    $row["is_live"] = (
        time() >= $start &&
        time() <= $cutoff &&
        $row["status"] !== "completed"
    );

    $row["attendance_closed"] = (
        time() > $cutoff ||
        $row["status"] === "completed"
    );

    $lectures[] = $row;
}

$stmt->close();

/* Attendance statistics */

$stmt = $conn->prepare(
    "SELECT
        COUNT(*) AS total,
        COALESCE(SUM(status = 'present'), 0) AS present_count,
        COALESCE(SUM(status = 'late'), 0) AS late_count
     FROM attendance
     WHERE student_id = ?"
);

$stmt->bind_param("i", $studentDbId);
$stmt->execute();

$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

$total = (int)$stats["total"];
$present = (int)$stats["present_count"];
$late = (int)$stats["late_count"];

$percentage = $total > 0
    ? round((($present + $late) / $total) * 100)
    : 0;

/* Recent attendance */

$stmt = $conn->prepare(
    "SELECT l.subject, l.lecture_date, l.room,
            a.attendance_time, a.status
     FROM attendance a
     JOIN lectures l ON l.id = a.lecture_id
     WHERE a.student_id = ?
     ORDER BY a.attendance_time DESC
     LIMIT 8"
);

$stmt->bind_param("i", $studentDbId);
$stmt->execute();

$historyResult = $stmt->get_result();
$history = [];

while ($row = $historyResult->fetch_assoc()) {
    $history[] = $row;
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Student Dashboard | Attendify</title>

    <link rel="stylesheet" href="student.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

    <script src="https://unpkg.com/html5-qrcode"></script>
</head>

<body class="portal-body">

<nav class="portal-nav">
    <a class="portal-logo" href="dashboard.php">
        <i class="fa-solid fa-qrcode"></i> Attendify
    </a>

    <div class="nav-actions">
        <a href="history.php">Attendance History</a>
        <a href="profile.php">My Profile</a>
        <a class="nav-logout" href="logout.php">Logout</a>
    </div>
</nav>

<main class="portal-main">

    <section class="welcome-banner">
        <div>
            <span class="eyebrow">STUDENT PORTAL</span>

            <h1>Welcome, <?= e($student["name"]) ?>!</h1>

            <p>
                Scan the active lecture QR code from your classroom
                to mark attendance.
            </p>

            <div class="student-tags">
                <span>ID: <?= e($student["student_id"]) ?></span>
                <span><?= e($student["course"]) ?></span>
                <span>Semester <?= e($student["semester"]) ?></span>
            </div>
        </div>

        <div class="welcome-art">
            <i class="fa-solid fa-qrcode"></i>
        </div>
    </section>

    <section class="stats-grid">

        <div class="stat-card">
            <small>Attendance Records</small>
            <strong><?= $total ?></strong>
        </div>

        <div class="stat-card">
            <small>Present</small>
            <strong><?= $present ?></strong>
        </div>

        <div class="stat-card">
            <small>Late</small>
            <strong><?= $late ?></strong>
        </div>

        <div class="stat-card">
            <small>Attendance Rate</small>
            <strong><?= $percentage ?>%</strong>
        </div>

    </section>

    <section class="content-grid">

        <div class="content-card">

            <h2>Scan Lecture QR</h2>

            <p class="muted">
                Allow camera and location access when prompted.
            </p>

            <div id="reader" class="qr-reader"></div>

            <div class="scan-controls">
                <button class="btn-primary" id="startScan" type="button">
                    Start Camera
                </button>

                <button class="btn-secondary" id="stopScan"
                        type="button" disabled>
                    Stop Camera
                </button>
            </div>

            <div class="manual-token">
                <label for="manualToken">QR token for testing</label>

                <div class="manual-row">
                    <input id="manualToken"
                           placeholder="Paste QR token">

                    <button class="btn-secondary"
                            id="submitToken" type="button">
                        Submit
                    </button>
                </div>
            </div>

            <div id="scanMessage" class="notice hidden"></div>

        </div>

        <div class="content-card">

            <h2>Today's Lectures</h2>

            <p class="muted">
                Lectures for Semester <?= e($semester) ?>
            </p>

            <?php if (!$lectures): ?>

                <div class="empty-state">
                    No lectures scheduled for your semester today.
                </div>

            <?php else: ?>

                <div class="lecture-list">

                    <?php foreach ($lectures as $lecture): ?>

                        <article class="lecture-item">

                            <div class="lecture-info">
                                <strong>
                                    <?= e($lecture["subject"]) ?>
                                </strong>

                                <span>
                                    <?= e($lecture["room"] ?: "Room not set") ?>
                                </span>

                                <span>
                                    <?= e(date(
                                        "h:i A",
                                        strtotime($lecture["start_time"])
                                    )) ?>
                                    -
                                    <?= e(date(
                                        "h:i A",
                                        strtotime($lecture["end_time"])
                                    )) ?>
                                </span>
                            </div>

                            <?php if ($lecture["is_live"]): ?>
                                <span class="badge badge-live">Scan Open</span>
                            <?php elseif ($lecture["attendance_closed"]): ?>
                                <span class="badge badge-closed">Closed</span>
                            <?php else: ?>
                                <span class="badge badge-upcoming">Upcoming</span>
                            <?php endif; ?>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>

    </section>

    <section class="content-card history-card">

        <div class="section-heading">
            <h2>Recent Attendance</h2>
            <a class="text-link" href="history.php">View All</a>
        </div>

        <?php if (!$history): ?>

            <div class="empty-state">
                No attendance records available yet.
            </div>

        <?php else: ?>

            <div class="table-wrap">

                <table>
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Date</th>
                            <th>Room</th>
                            <th>Marked At</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($history as $item): ?>

                            <tr>
                                <td><?= e($item["subject"]) ?></td>

                                <td>
                                    <?= e(date(
                                        "d M Y",
                                        strtotime($item["lecture_date"])
                                    )) ?>
                                </td>

                                <td><?= e($item["room"] ?: "-") ?></td>

                                <td>
                                    <?= e(date(
                                        "h:i A",
                                        strtotime($item["attendance_time"])
                                    )) ?>
                                </td>

                                <td><?= e(ucfirst($item["status"])) ?></td>
                            </tr>

                        <?php endforeach; ?>

                    </tbody>
                </table>

            </div>

        <?php endif; ?>

    </section>

</main>

<script>
const csrfToken = <?= json_encode($csrf) ?>;

let scanner = null;
let scannerRunning = false;
let submitting = false;

const messageBox = document.getElementById("scanMessage");

function showMessage(message, type = "info") {
    messageBox.className = "notice " + type;
    messageBox.textContent = message;
}

async function submitToken(token) {

    token = (token || "").trim();

    if (!token || submitting) {
        return;
    }

    submitting = true;

    showMessage("Checking your location...", "info");

    try {

        if (!navigator.geolocation) {
            throw new Error("Location is not supported by this browser.");
        }

        const position = await new Promise((resolve, reject) => {
            navigator.geolocation.getCurrentPosition(
                resolve,
                reject,
                {
                    enableHighAccuracy: true,
                    timeout: 20000,
                    maximumAge: 0
                }
            );
        });

        const response = await fetch("mark_attendance.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                csrf: csrfToken,
                qr_token: token,
                latitude: position.coords.latitude,
                longitude: position.coords.longitude,
                accuracy: position.coords.accuracy
            })
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || "Attendance could not be marked.");
        }

        showMessage(data.message, "success");

        if (scanner && scannerRunning) {
            await scanner.stop();
            scannerRunning = false;
        }

        setTimeout(() => location.reload(), 1800);

    } catch (error) {

        let message = error.message || "Unable to verify attendance.";

        if (error.code === 1) {
            message = "Location permission denied. Allow location access.";
        } else if (error.code === 2) {
            message = "Location unavailable. Turn on device location.";
        } else if (error.code === 3) {
            message = "Location request timed out. Try again.";
        }

        showMessage(message, "error");

    } finally {
        submitting = false;
    }
}

document.getElementById("startScan").addEventListener("click", async () => {

    if (scannerRunning) return;

    if (typeof Html5Qrcode === "undefined") {
        showMessage("QR scanner library could not load. Check your internet connection.", "error");
        return;
    }

    try {

        scanner = new Html5Qrcode("reader");

        await scanner.start(
            { facingMode: "environment" },
            {
                fps: 10,
                qrbox: { width: 240, height: 240 }
            },
            async decodedText => {

                if (scannerRunning && !submitting) {
                    await submitToken(decodedText);
                }

            },
            () => {}
        );

        scannerRunning = true;

        document.getElementById("startScan").disabled = true;
        document.getElementById("stopScan").disabled = false;

        showMessage("Camera started. Point it at the lecture QR code.", "info");

    } catch (error) {
        showMessage("Camera could not start. Allow camera permission and use localhost or HTTPS.", "error");
    }
});

document.getElementById("stopScan").addEventListener("click", async () => {

    if (scanner && scannerRunning) {

        await scanner.stop();

        scannerRunning = false;

        document.getElementById("startScan").disabled = false;
        document.getElementById("stopScan").disabled = true;

        showMessage("Camera stopped.", "info");
    }
});

document.getElementById("submitToken").addEventListener("click", () => {
    submitToken(document.getElementById("manualToken").value);
});
</script>

</body>
</html>