
<?php

session_start();
date_default_timezone_set("Asia/Kolkata");

// ========================================
// 1. FACULTY LOGIN CHECK
// ========================================

if (
    !isset($_SESSION["faculty_logged_in"]) ||
    $_SESSION["faculty_logged_in"] !== true
) {
    header("Location: login.php");
    exit;
}

require_once "../config/db.php";

$faculty_id = (int)($_SESSION["faculty_db_id"] ?? 0);

if ($faculty_id <= 0) {
    header("Location: login.php");
    exit;
}

// ========================================
// 2. HELPER FUNCTION
// ========================================

function e($value)
{
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

// ========================================
// 3. FETCH FACULTY LECTURES
// ========================================

$sql = "
    SELECT
        id,
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

        CASE
            WHEN CONCAT(lecture_date, ' ', end_time) <= NOW()
                THEN 'completed'

            WHEN CONCAT(lecture_date, ' ', start_time) > NOW()
                THEN 'scheduled'

            ELSE 'active'
        END AS current_status

    FROM lectures
    WHERE faculty_id = ?
    ORDER BY lecture_date DESC, start_time DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error while loading lectures.");
}

$stmt->bind_param("i", $faculty_id);
$stmt->execute();

$result = $stmt->get_result();

$lectures = [];

while ($row = $result->fetch_assoc()) {
    $lectures[] = $row;
}

$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>View Lectures | Attendify</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
    >

    <link
        rel="stylesheet"
        href="../dashboard.css"
    >

    <style>

        body {
            background: #f5f7fb;
        }

        .lecture-page {
            max-width: 1500px;
            margin: auto;
            padding: 30px;
        }

        .lecture-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 28px;
        }

        .lecture-header h2 {
            font-weight: 750;
            margin-bottom: 7px;
            color: #202336;
        }

        .lecture-header p {
            color: #777f91;
        }

        .lecture-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 23px;
            height: 100%;
            box-shadow: 0 5px 20px rgba(20, 30, 70, .045);
            transition: transform .2s, box-shadow .2s;
        }

        .lecture-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 28px rgba(20, 30, 70, .08);
        }

        .lecture-card h5 {
            font-weight: 750;
            margin-bottom: 18px;
            color: #252940;
            overflow-wrap: anywhere;
        }

        .lecture-info {
            color: #555d70;
            font-size: 13px;
            margin-bottom: 12px;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .lecture-info i {
            width: 22px;
            color: #5146e5;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 750;
            text-transform: uppercase;
        }

        .status-active {
            color: #166534;
            background: #dcfce7;
        }

        .status-scheduled {
            color: #1d4ed8;
            background: #dbeafe;
        }

        .status-completed {
            color: #4b5563;
            background: #e5e7eb;
        }

        .semester-badge {
            display: inline-block;
            margin-bottom: 15px;
            padding: 6px 10px;
            border-radius: 8px;
            background: #eef2ff;
            color: #4338ca;
            font-size: 12px;
            font-weight: 700;
        }

        .lecture-actions {
            display: flex;
            gap: 9px;
            flex-wrap: wrap;
            margin-top: 20px;
            padding-top: 17px;
            border-top: 1px solid #edf0f5;
        }

        .lecture-actions .btn {
            border-radius: 8px;
            font-size: 12px;
        }

        .empty-state {
            text-align: center;
            background: white;
            padding: 55px 20px;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
        }

        .empty-state > i {
            font-size: 45px;
            color: #a1a7b5;
            margin-bottom: 18px;
        }

        .empty-state h4 {
            font-weight: 750;
            color: #202336;
        }

        .location-panel {
            background: #f8faff;
            border: 1px solid #e7eaf5;
            padding: 12px;
            border-radius: 10px;
            margin-top: 14px;
        }

        .location-panel .lecture-info {
            margin-bottom: 6px;
        }

        #qrcode {
            min-height: 220px;
        }

        @media (max-width: 768px) {

            .lecture-page {
                padding: 17px;
            }

            .lecture-header h2 {
                font-size: 24px;
            }

            .lecture-card {
                padding: 18px;
            }

        }

    </style>

</head>

<body>

<main class="lecture-page">

    <!-- PAGE HEADER -->

    <div class="lecture-header">

        <div>

            <h2>
                <i class="fa-solid fa-book-open text-primary"></i>
                My Lectures
            </h2>

            <p class="mb-0">
                Manage lectures, check their status and view attendance.
            </p>

        </div>

        <div class="d-flex gap-2 flex-wrap">

            <a
                href="dashboard.php"
                class="btn btn-outline-secondary"
            >
                <i class="fa-solid fa-arrow-left"></i>
                Dashboard
            </a>

            <a
                href="start_lecture.php"
                class="btn btn-primary"
            >
                <i class="fa-solid fa-plus"></i>
                Start New Lecture
            </a>

        </div>

    </div>

    <!-- EMPTY STATE -->

    <?php if (count($lectures) === 0): ?>

        <div class="empty-state">

            <i class="fa-solid fa-calendar-xmark"></i>

            <h4>No Lectures Found</h4>

            <p class="text-muted">
                You have not created any lectures yet.
            </p>

            <a
                href="start_lecture.php"
                class="btn btn-primary"
            >
                <i class="fa-solid fa-plus"></i>
                Create Your First Lecture
            </a>

        </div>

    <?php else: ?>

        <div class="row g-4">

            <?php foreach ($lectures as $lecture): ?>

                <?php

                $status = $lecture["current_status"];

                if ($status === "active") {
                    $badgeClass = "status-active";
                } elseif ($status === "scheduled") {
                    $badgeClass = "status-scheduled";
                } else {
                    $badgeClass = "status-completed";
                }

                $lectureDate = $lecture["lecture_date"];

                $startTimestamp = strtotime(
                    $lectureDate . " " . $lecture["start_time"]
                );

                $endTimestamp = strtotime(
                    $lectureDate . " " . $lecture["end_time"]
                );

                // attendance_deadline stores a time.
                // Combine it with the lecture date.

                $deadlineTimestamp = strtotime(
                    $lectureDate . " " . $lecture["attendance_deadline"]
                );

                $now = time();

                $attendanceOpen = (
                    $status === "active" &&
                    $now >= $startTimestamp &&
                    $now < $deadlineTimestamp &&
                    $now < $endTimestamp
                );

                $qrAvailable = (
                    $attendanceOpen &&
                    !empty($lecture["qr_token"])
                );

                ?>

                <div class="col-12 col-md-6 col-xl-4">

                    <article class="lecture-card">

                        <!-- STATUS AND ID -->

                        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">

                            <span class="status-badge <?= e($badgeClass) ?>">
                                <?= e(ucfirst($status)) ?>
                            </span>

                            <small class="text-muted">
                                ID: <?= (int)$lecture["id"] ?>
                            </small>

                        </div>

                        <!-- SUBJECT -->

                        <h5>
                            <?= e($lecture["subject"]) ?>
                        </h5>

                        <!-- SEMESTER -->

                        <div class="semester-badge">

                            <i class="fa-solid fa-graduation-cap"></i>

                            Semester:
                            <?= e($lecture["semester"] ?: "Not assigned") ?>

                        </div>

                        <!-- DATE -->

                        <div class="lecture-info">

                            <i class="fa-regular fa-calendar"></i>

                            <?= e(date("d M Y", strtotime($lectureDate))) ?>

                        </div>

                        <!-- TIME -->

                        <div class="lecture-info">

                            <i class="fa-regular fa-clock"></i>

                            <?= e(date("h:i A", strtotime($lecture["start_time"]))) ?>

                            -

                            <?= e(date("h:i A", strtotime($lecture["end_time"]))) ?>

                        </div>

                        <!-- ROOM -->

                        <div class="lecture-info">

                            <i class="fa-solid fa-location-dot"></i>

                            <?= e($lecture["room"] ?: "Room not specified") ?>

                        </div>

                        <!-- ATTENDANCE WINDOW -->

                        <div class="lecture-info">

                            <i class="fa-solid fa-stopwatch"></i>

                            Attendance window:

                            <?= (int)$lecture["attendance_open_minutes"] ?>
                            minutes

                        </div>

                        <!-- ATTENDANCE DEADLINE -->

                        <div class="lecture-info">

                            <i class="fa-solid fa-hourglass-end"></i>

                            Attendance cutoff:

                            <?= e(date("h:i A", $deadlineTimestamp)) ?>

                        </div>

                        <!-- GPS RADIUS -->

                        <div class="lecture-info">

                            <i class="fa-solid fa-location-crosshairs"></i>

                            GPS radius:

                            <?= (int)$lecture["radius"] ?> metres

                        </div>

                        <!-- LOCATION -->

                        <div class="location-panel">

                            <div class="lecture-info">

                                <i class="fa-solid fa-map-pin"></i>

                                Latitude:

                                <?= e($lecture["latitude"] ?? "Not set") ?>

                            </div>

                            <div class="lecture-info mb-0">

                                <i class="fa-solid fa-map-pin"></i>

                                Longitude:

                                <?= e($lecture["longitude"] ?? "Not set") ?>

                            </div>

                        </div>

                        <!-- ACTIONS -->

                        <div class="lecture-actions">

                            <?php if ($qrAvailable): ?>

                                <button
                                    type="button"
                                    class="btn btn-success btn-sm"
                                    onclick="showQR(
                                        <?= e(json_encode($lecture["qr_token"])) ?>,
                                        <?= (int)$lecture["id"] ?>
                                    )"
                                >
                                    <i class="fa-solid fa-qrcode"></i>
                                    Show QR
                                </button>

                            <?php elseif ($status === "scheduled"): ?>

                                <button
                                    type="button"
                                    class="btn btn-secondary btn-sm"
                                    disabled
                                >
                                    <i class="fa-regular fa-clock"></i>
                                    Not Started
                                </button>

                            <?php elseif ($status === "completed"): ?>

                                <button
                                    type="button"
                                    class="btn btn-secondary btn-sm"
                                    disabled
                                >
                                    <i class="fa-solid fa-lock"></i>
                                    Lecture Completed
                                </button>

                            <?php else: ?>

                                <button
                                    type="button"
                                    class="btn btn-warning btn-sm"
                                    disabled
                                >
                                    <i class="fa-solid fa-lock"></i>
                                    Attendance Closed
                                </button>

                            <?php endif; ?>

                            <a
                                href="attendance.php?lecture_id=<?= (int)$lecture["id"] ?>"
                                class="btn btn-outline-primary btn-sm"
                            >
                                <i class="fa-solid fa-users"></i>
                                View Attendance
                            </a>

                        </div>

                    </article>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</main>

<!-- QR MODAL -->

<div
    class="modal fade"
    id="qrModal"
    tabindex="-1"
    aria-labelledby="qrModalTitle"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title" id="qrModalTitle">
                    <i class="fa-solid fa-qrcode"></i>
                    Lecture QR Code
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close"
                ></button>

            </div>

            <div class="modal-body text-center">

                <p class="text-muted small">
                    Students must scan this QR during the attendance
                    window and be within the permitted GPS radius.
                </p>

                <div
                    id="qrcode"
                    class="d-flex justify-content-center align-items-center my-3"
                ></div>

                <p
                    id="qrLectureId"
                    class="fw-bold"
                ></p>

                <p class="text-danger small">
                    Do not share the QR code outside the classroom.
                </p>

                <button
                    type="button"
                    class="btn btn-outline-primary btn-sm"
                    onclick="downloadQR()"
                >
                    <i class="fa-solid fa-download"></i>
                    Download QR
                </button>

            </div>

        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>

// ========================================
// SHOW LECTURE QR
// ========================================

function showQR(token, lectureId) {

    const qrBox = document.getElementById("qrcode");

    qrBox.innerHTML = "";

    document.getElementById("qrLectureId").textContent =
        "Lecture ID: " + lectureId;

    new QRCode(qrBox, {
        text: token,
        width: 220,
        height: 220,
        colorDark: "#17192b",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });

    const modalElement = document.getElementById("qrModal");

    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);

    modal.show();
}

// ========================================
// DOWNLOAD QR
// ========================================

function downloadQR() {

    const qrBox = document.getElementById("qrcode");

    const canvas = qrBox.querySelector("canvas");

    const image = qrBox.querySelector("img");

    let imageUrl = "";

    if (canvas) {
        imageUrl = canvas.toDataURL("image/png");
    } else if (image && image.src) {
        imageUrl = image.src;
    } else {
        alert("QR code is not ready yet.");
        return;
    }

    const link = document.createElement("a");

    link.href = imageUrl;
    link.download = "Attendify-Lecture-QR.png";

    document.body.appendChild(link);
    link.click();
    link.remove();
}

</script>

</body>
</html>

