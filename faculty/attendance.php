
<?php
session_start();

if (
    !isset($_SESSION["faculty_logged_in"]) ||
    $_SESSION["faculty_logged_in"] !== true
) {
    header("Location: login.php");
    exit();
}

require_once "../config/db.php";

$faculty_db_id = (int)$_SESSION["faculty_db_id"];

$lecture_id = filter_input(INPUT_GET, "lecture_id", FILTER_VALIDATE_INT);
$lecture_id = ($lecture_id && $lecture_id > 0) ? $lecture_id : 0;

$selectedLecture = null;
$lectures = [];
$records = [];
$totalPresent = 0;
$totalLate = 0;
$totalStudents = 0;
$totalLectures = 0;

// Fetch only this faculty's lectures
$stmt = $conn->prepare(
    "SELECT id, subject, lecture_date, room
     FROM lectures
     WHERE faculty_id = ?
     ORDER BY lecture_date DESC, id DESC"
);

$stmt->bind_param("i", $faculty_db_id);
$stmt->execute();

$lectureResult = $stmt->get_result();

while ($lecture = $lectureResult->fetch_assoc()) {
    $lectures[] = $lecture;
}

$stmt->close();

$totalLectures = count($lectures);

// Verify that the requested lecture belongs to this faculty
if ($lecture_id > 0) {

    $stmt = $conn->prepare(
        "SELECT id, subject, lecture_date, room
         FROM lectures
         WHERE id = ? AND faculty_id = ?
         LIMIT 1"
    );

    $stmt->bind_param("ii", $lecture_id, $faculty_db_id);
    $stmt->execute();

    $selectedLecture = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if (!$selectedLecture) {
        http_response_code(404);
        $lecture_id = 0;
    }
}

// Fetch attendance
if ($lecture_id > 0 && $selectedLecture) {

    $stmt = $conn->prepare(
        "SELECT
            a.id,
            a.attendance_time,
            a.status,
            a.latitude,
            a.longitude,
            s.student_id,
            s.name,
            s.email,
            s.course,
            s.semester,
            l.subject,
            l.lecture_date,
            l.room
         FROM attendance a
         INNER JOIN students s
            ON a.student_id = s.id
         INNER JOIN lectures l
            ON a.lecture_id = l.id
         WHERE l.id = ?
           AND l.faculty_id = ?
         ORDER BY a.attendance_time DESC"
    );

    $stmt->bind_param("ii", $lecture_id, $faculty_db_id);

} else {

    $stmt = $conn->prepare(
        "SELECT
            a.id,
            a.attendance_time,
            a.status,
            a.latitude,
            a.longitude,
            s.student_id,
            s.name,
            s.email,
            s.course,
            s.semester,
            l.subject,
            l.lecture_date,
            l.room
         FROM attendance a
         INNER JOIN students s
            ON a.student_id = s.id
         INNER JOIN lectures l
            ON a.lecture_id = l.id
         WHERE l.faculty_id = ?
         ORDER BY a.attendance_time DESC"
    );

    $stmt->bind_param("i", $faculty_db_id);
}

$stmt->execute();

$attendanceResult = $stmt->get_result();

while ($row = $attendanceResult->fetch_assoc()) {

    $records[] = $row;

    if ($row["status"] === "present") {
        $totalPresent++;
    } elseif ($row["status"] === "late") {
        $totalLate++;
    }
}

$stmt->close();

$totalStudents = count($records);

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function formatTime($time) {
    if (!$time) {
        return "—";
    }

    return date("d M Y, h:i A", strtotime($time));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Attendance Records - Attendify</title>

    <link rel="stylesheet" href="../dashboard.css">
    <link rel="stylesheet" href="lecture_pages.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
</head>

<body>

<nav class="dashboard-navbar">

    <div class="dashboard-logo">
        <i class="fa-solid fa-calendar-check"></i>
        Attendify
    </div>

    <div class="dashboard-nav-right">
        <a href="dashboard.php" class="logout-btn">
            <i class="fa-solid fa-arrow-left"></i>
            Dashboard
        </a>

        <a href="logout.php" class="logout-btn">
            <i class="fa-solid fa-right-from-bracket"></i>
            Logout
        </a>
    </div>

</nav>

<main class="lecture-list-page">

    <div class="lecture-list-header">

        <div>
            <span class="lecture-eyebrow">
                <i class="fa-solid fa-clipboard-check"></i>
                ATTENDANCE MANAGEMENT
            </span>

            <h1>Attendance Records</h1>

            <p>
                Review student attendance for your lectures.
            </p>
        </div>

        <a href="view_lectures.php" class="lecture-add-btn">
            <i class="fa-solid fa-book-open"></i>
            View Lectures
        </a>

    </div>

    <section class="attendance-filter-card">

        <form method="GET" action="attendance.php">

            <div class="attendance-filter-field">
                <label for="lecture_id">Filter by Lecture</label>

                <select name="lecture_id" id="lecture_id">

                    <option value="">All My Lectures</option>

                    <?php foreach ($lectures as $lecture): ?>

                        <option
                            value="<?= (int)$lecture["id"] ?>"
                            <?= $lecture_id === (int)$lecture["id"] ? "selected" : "" ?>
                        >
                            <?= e($lecture["subject"]) ?>
                            — <?= e(date("d M Y", strtotime($lecture["lecture_date"]))) ?>
                        </option>

                    <?php endforeach; ?>

                </select>
            </div>

            <button type="submit" class="lecture-add-btn">
                <i class="fa-solid fa-filter"></i>
                Apply Filter
            </button>

            <a href="attendance.php" class="lecture-secondary-btn">
                Reset
            </a>

        </form>

    </section>

    <?php if ($selectedLecture): ?>

        <div class="attendance-selected-lecture">

            <div class="selected-lecture-icon">
                <i class="fa-solid fa-book-open"></i>
            </div>

            <div>
                <span>Selected Lecture</span>
                <h2><?= e($selectedLecture["subject"]) ?></h2>

                <p>
                    <?= e(date("d M Y", strtotime($selectedLecture["lecture_date"]))) ?>
                    &nbsp;·&nbsp;
                    <?= e($selectedLecture["room"]) ?>
                </p>
            </div>

        </div>

    <?php endif; ?>

    <div class="attendance-stats-grid">

        <div class="attendance-stat-card">
            <div class="attendance-stat-icon purple">
                <i class="fa-solid fa-users"></i>
            </div>

            <div>
                <span>Attendance Entries</span>
                <strong><?= $totalStudents ?></strong>
            </div>
        </div>

        <div class="attendance-stat-card">
            <div class="attendance-stat-icon green">
                <i class="fa-solid fa-circle-check"></i>
            </div>

            <div>
                <span>Present</span>
                <strong><?= $totalPresent ?></strong>
            </div>
        </div>

        <div class="attendance-stat-card">
            <div class="attendance-stat-icon orange">
                <i class="fa-solid fa-clock"></i>
            </div>

            <div>
                <span>Late</span>
                <strong><?= $totalLate ?></strong>
            </div>
        </div>

        <div class="attendance-stat-card">
            <div class="attendance-stat-icon blue">
                <i class="fa-solid fa-book"></i>
            </div>

            <div>
                <span>My Lectures</span>
                <strong><?= $totalLectures ?></strong>
            </div>
        </div>

    </div>

    <section class="lecture-table-card">

        <div class="lecture-table-top">
            <div>
                <h2>Student Attendance</h2>

                <p>
                    <?php if ($lecture_id > 0): ?>
                        Attendance entries for the selected lecture.
                    <?php else: ?>
                        Attendance entries across your lectures.
                    <?php endif; ?>
                </p>
            </div>

            <span class="record-count">
                <?= $totalStudents ?> Records
            </span>
        </div>

        <?php if ($totalStudents > 0): ?>

            <div class="lecture-table-wrapper">

                <table class="lecture-table attendance-table">

                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Student ID</th>
                            <th>Subject</th>
                            <th>Date & Time</th>
                            <th>Room</th>
                            <th>Status</th>
                            <th>Location</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($records as $row): ?>

                        <tr>

                            <td>
                                <div class="lecture-subject-cell">

                                    <div class="lecture-subject-icon student-icon">
                                        <i class="fa-solid fa-user"></i>
                                    </div>

                                    <div>
                                        <strong><?= e($row["name"]) ?></strong>
                                        <small><?= e($row["course"] ?: "Student") ?></small>
                                    </div>

                                </div>
                            </td>

                            <td>
                                <span class="lecture-radius">
                                    <?= e($row["student_id"]) ?>
                                </span>
                            </td>

                            <td><?= e($row["subject"]) ?></td>

                            <td>
                                <div class="lecture-timing">
                                    <strong>
                                        <?= e(formatTime($row["attendance_time"])) ?>
                                    </strong>
                                </div>
                            </td>

                            <td><?= e($row["room"]) ?></td>

                            <td>
                                <span class="lecture-status status-<?= e($row["status"]) ?>">
                                    <span class="status-dot"></span>
                                    <?= e(ucfirst($row["status"])) ?>
                                </span>
                            </td>

                            <td>
                                <?php if (
                                    $row["latitude"] !== null &&
                                    $row["longitude"] !== null
                                ): ?>

                                    <span class="location-verified">
                                        <i class="fa-solid fa-location-dot"></i>
                                        Captured
                                    </span>

                                <?php else: ?>

                                    <span class="location-not-verified">
                                        Not recorded
                                    </span>

                                <?php endif; ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="lecture-empty-state">
                <div class="lecture-empty-icon">
                    <i class="fa-solid fa-clipboard-list"></i>
                </div>

                <h3>No Attendance Records</h3>

                <p>
                    Attendance will appear here after students
                    successfully scan the lecture QR and pass verification.
                </p>

                <a href="view_lectures.php" class="lecture-add-btn">
                    <i class="fa-solid fa-book-open"></i>
                    View My Lectures
                </a>
            </div>

        <?php endif; ?>

    </section>

</main>

</body>
</html>

<?php
$conn->close();
?>