<?php
session_start();

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

$id = (int)$_SESSION["student_db_id"];

$stmt = $conn->prepare(
    "SELECT l.subject, l.lecture_date, l.start_time, l.room,
            a.attendance_time, a.status
     FROM attendance a
     JOIN lectures l ON l.id = a.lecture_id
     WHERE a.student_id = ?
     ORDER BY a.attendance_time DESC"
);

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance History | Attendify</title>
    <link rel="stylesheet" href="student.css">
</head>

<body class="portal-body">

<nav class="portal-nav">
    <a class="portal-logo" href="dashboard.php">Attendify</a>

    <div class="nav-actions">
        <a href="dashboard.php">Dashboard</a>
        <a href="profile.php">Profile</a>
        <a class="nav-logout" href="logout.php">Logout</a>
    </div>
</nav>

<main class="portal-main">

    <section class="content-card">

        <h1>Attendance History</h1>

        <p class="muted">Your saved attendance records.</p>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Room</th>
                        <th>Marked At</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($result->num_rows === 0): ?>

                    <tr>
                        <td colspan="6">No attendance records found.</td>
                    </tr>

                <?php else: ?>

                    <?php while ($row = $result->fetch_assoc()): ?>

                        <tr>
                            <td><?= e($row["subject"]) ?></td>

                            <td>
                                <?= e(date(
                                    "d M Y",
                                    strtotime($row["lecture_date"])
                                )) ?>
                            </td>

                            <td>
                                <?= e(date(
                                    "h:i A",
                                    strtotime($row["start_time"])
                                )) ?>
                            </td>

                            <td><?= e($row["room"] ?: "-") ?></td>

                            <td>
                                <?= e(date(
                                    "h:i A",
                                    strtotime($row["attendance_time"])
                                )) ?>
                            </td>

                            <td><?= e(ucfirst($row["status"])) ?></td>
                        </tr>

                    <?php endwhile; ?>

                <?php endif; ?>

                </tbody>
            </table>
        </div>

    </section>

</main>

</body>
</html>