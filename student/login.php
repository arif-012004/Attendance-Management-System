<?php
session_start();
date_default_timezone_set("Asia/Kolkata");

if (!empty($_SESSION["student_logged_in"])) {
    header("Location: dashboard.php");
    exit;
}

require_once "../config/db.php";

$error = "";
$studentId = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $studentId = trim($_POST["student_id"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($studentId === "" || $password === "") {
        $error = "Enter your Student ID and password.";
    } else {

        $stmt = $conn->prepare(
            "SELECT id, student_id, name, email, course, semester, password_hash
             FROM students
             WHERE student_id = ?
             LIMIT 1"
        );

        if (!$stmt) {
            $error = "Database error. Please contact the administrator.";
        } else {

            $stmt->bind_param("s", $studentId);
            $stmt->execute();

            $student = $stmt->get_result()->fetch_assoc();

            $stmt->close();

            if (
                $student &&
                !empty($student["password_hash"]) &&
                password_verify($password, $student["password_hash"])
            ) {

                session_regenerate_id(true);

                $_SESSION["student_logged_in"] = true;
                $_SESSION["student_db_id"] = (int)$student["id"];
                $_SESSION["student_id"] = $student["student_id"];
                $_SESSION["student_name"] = $student["name"];
                $_SESSION["student_semester"] = (string)$student["semester"];

                header("Location: dashboard.php");
                exit;

            } else {
                $error = "Invalid Student ID or password. Contact faculty if your account has no password yet.";
            }
        }
    }
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login | Attendify</title>

    <link rel="stylesheet" href="student.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
</head>

<body class="auth-body">

<a class="back-home" href="../index.html">
    <i class="fa-solid fa-arrow-left"></i> Back to Home
</a>

<main class="login-shell">

    <section class="login-brand">
        <div class="brand-mark">
            <i class="fa-solid fa-qrcode"></i>
        </div>

        <span class="eyebrow">ATTENDIFY STUDENT PORTAL</span>

        <h1>Your classes.<br><span>Your attendance.</span></h1>

        <p>
            Sign in to scan lecture QR codes, verify your classroom
            location and review your attendance records.
        </p>

        <div class="feature-line">
            <i class="fa-solid fa-location-dot"></i>
            Location-verified attendance
        </div>

        <div class="feature-line">
            <i class="fa-solid fa-clock"></i>
            Lecture and attendance status
        </div>

        <div class="feature-line">
            <i class="fa-solid fa-chart-simple"></i>
            Personal attendance history
        </div>
    </section>

    <section class="login-panel">

        <div class="panel-icon">
            <i class="fa-solid fa-user-graduate"></i>
        </div>

        <h2>Student Login</h2>

        <p class="muted">
            Use the credentials provided by your faculty.
        </p>

        <?php if ($error !== ""): ?>
            <div class="notice error">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <label for="student_id">Student ID</label>

            <div class="input-wrap">
                <i class="fa-solid fa-id-card"></i>

                <input
                    type="text"
                    id="student_id"
                    name="student_id"
                    value="<?= e($studentId) ?>"
                    placeholder="Enter Student ID"
                    required
                >
            </div>

            <label for="password">Password</label>

            <div class="input-wrap">
                <i class="fa-solid fa-lock"></i>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter password"
                    required
                >
            </div>

            <button class="btn-primary" type="submit">
                Sign In <i class="fa-solid fa-arrow-right"></i>
            </button>

        </form>

        <div class="login-foot">
            Need access? Contact your faculty to create your student account.
        </div>

    </section>

</main>

</body>
</html>