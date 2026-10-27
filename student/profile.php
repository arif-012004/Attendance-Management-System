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
    "SELECT student_id, name, email, phone, course, semester
     FROM students
     WHERE id = ?
     LIMIT 1"
);

$stmt->bind_param("i", $id);
$stmt->execute();

$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    header("Location: logout.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile | Attendify</title>
    <link rel="stylesheet" href="student.css">
</head>

<body class="portal-body">

<nav class="portal-nav">
    <a class="portal-logo" href="dashboard.php">Attendify</a>

    <div class="nav-actions">
        <a href="dashboard.php">Dashboard</a>
        <a href="history.php">History</a>
        <a class="nav-logout" href="logout.php">Logout</a>
    </div>
</nav>

<main class="portal-main profile-page">

    <section class="content-card profile-card">

        <div class="profile-avatar">👤</div>

        <h1><?= e($student["name"]) ?></h1>

        <p class="muted">Student Profile</p>

        <div class="profile-details">

            <div class="profile-detail">
                <span>Student ID</span>
                <strong><?= e($student["student_id"]) ?></strong>
            </div>

            <div class="profile-detail">
                <span>Email</span>
                <strong><?= e($student["email"] ?: "-") ?></strong>
            </div>

            <div class="profile-detail">
                <span>Phone</span>
                <strong><?= e($student["phone"] ?: "-") ?></strong>
            </div>

            <div class="profile-detail">
                <span>Course</span>
                <strong><?= e($student["course"]) ?></strong>
            </div>

            <div class="profile-detail">
                <span>Semester</span>
                <strong><?= e($student["semester"]) ?></strong>
            </div>

        </div>

        <a class="btn-primary" href="dashboard.php">
            Back to Dashboard
        </a>

    </section>

</main>

</body>
</html>