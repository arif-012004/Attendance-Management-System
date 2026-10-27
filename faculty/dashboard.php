<?php

session_start();

if (
    !isset($_SESSION["faculty_logged_in"]) ||
    $_SESSION["faculty_logged_in"] !== true
) {
    header("Location: login.php");
    exit();
}

$faculty_name = $_SESSION["faculty_name"];
$faculty_id = $_SESSION["faculty_id"];
$department = $_SESSION["faculty_department"];

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Faculty Dashboard - Attendify</title>

    <link rel="stylesheet" href="../dashboard.css">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
    >

</head>

<body>

<nav class="dashboard-navbar">

    <div class="dashboard-logo">
        <i class="fa-solid fa-calendar-check"></i>
        Attendify
    </div>

    <div class="dashboard-nav-right">

        <span class="welcome-text">
            <?php echo htmlspecialchars($faculty_name); ?>
        </span>

        <a href="logout.php" class="logout-btn">
            <i class="fa-solid fa-right-from-bracket"></i>
            Logout
        </a>

    </div>

</nav>


<div class="dashboard-container">

    <div class="dashboard-header">

        <div>
            <h1>Faculty Dashboard</h1>

            <p>
                Welcome back,
                <strong><?php echo htmlspecialchars($faculty_name); ?></strong>
            </p>
        </div>

    </div>


    <div class="dashboard-grid">

        <div class="dashboard-card">

            <div class="card-icon">
                <i class="fa-solid fa-user-plus"></i>
            </div>

            <h3>Add Student</h3>

            <p>
                Register a new student in the attendance system.
            </p>

            <a href="add_student.php" class="card-btn">
                Add Student
            </a>

        </div>


        <div class="dashboard-card">

            <div class="card-icon">
                <i class="fa-solid fa-users"></i>
            </div>

            <h3>View Students</h3>

            <p>
                View and manage registered students.
            </p>

            <a href="view_student.php" class="card-btn">
                View Students
            </a>

        </div>


        <div class="dashboard-card">

            <div class="card-icon">
                <i class="fa-solid fa-qrcode"></i>
            </div>

            <h3>Start Lecture</h3>

            <p>
                Start a lecture and generate an attendance QR code.
            </p>

            <a href="start_lecture.php" class="card-btn">
                Start Lecture
            </a>

        </div>


        <div class="dashboard-card">

            <div class="card-icon">
                <i class="fa-solid fa-chalkboard"></i>
            </div>

            <h3>View Lectures</h3>

            <p>
                View your active and previous lectures.
            </p>

            <a href="view_lectures.php" class="card-btn">
                View Lectures
            </a>

        </div>


        <div class="dashboard-card">

            <div class="card-icon">
                <i class="fa-solid fa-clipboard-check"></i>
            </div>

            <h3>View Attendance</h3>

            <p>
                Check attendance records for your lectures.
            </p>

            <a href="attendance.php" class="card-btn">
                View Attendance
            </a>

        </div>


        <div class="dashboard-card">

            <div class="card-icon">
                <i class="fa-solid fa-user-tie"></i>
            </div>

            <h3>Faculty Profile</h3>

            <p>
                View your faculty account information.
            </p>

            <a href="profile.php" class="card-btn">
                View Profile
            </a>

        </div>

    </div>

</div>

</body>
</html>