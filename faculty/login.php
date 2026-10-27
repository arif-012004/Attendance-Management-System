<?php

session_start();

if (isset($_SESSION["faculty_logged_in"]) && $_SESSION["faculty_logged_in"] === true) {
    header("Location: dashboard.php");
    exit();
}

require_once "../config/db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $faculty_id = trim($_POST["faculty_id"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($faculty_id === "" || $password === "") {
        $error = "Please enter Faculty ID and Password.";
    } else {

        $stmt = $conn->prepare(
            "SELECT id, faculty_id, name, email, department, password
             FROM faculty
             WHERE faculty_id = ?"
        );

        $stmt->bind_param("s", $faculty_id);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $faculty = $result->fetch_assoc();

            if (password_verify($password, $faculty["password"])) {

                session_regenerate_id(true);

                $_SESSION["faculty_logged_in"] = true;
                $_SESSION["faculty_db_id"] = $faculty["id"];
                $_SESSION["faculty_id"] = $faculty["faculty_id"];
                $_SESSION["faculty_name"] = $faculty["name"];
                $_SESSION["faculty_email"] = $faculty["email"];
                $_SESSION["faculty_department"] = $faculty["department"];

                header("Location: dashboard.php");
                exit();

            } else {
                $error = "Invalid Faculty ID or Password.";
            }

        } else {
            $error = "Invalid Faculty ID or Password.";
        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Faculty Login - Attendify</title>

    <link rel="stylesheet" href="../dashboard.css">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
    >

</head>

<body>

<div class="auth-page">

    <div class="auth-card">

        <div class="auth-icon">
            <i class="fa-solid fa-user-tie"></i>
        </div>

        <h1>Faculty Login</h1>

        <p class="auth-subtitle">
            Login to access your Attendify faculty dashboard
        </p>

        <?php if ($error !== ""): ?>

            <div class="alert alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <div class="form-group">

                <label for="faculty_id">
                    Faculty ID
                </label>

                <input
                    type="text"
                    id="faculty_id"
                    name="faculty_id"
                    placeholder="Enter your Faculty ID"
                    required
                >

            </div>

            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                >

            </div>

            <button type="submit" class="primary-btn">
                <i class="fa-solid fa-right-to-bracket"></i>
                Login
            </button>

        </form>

        <a
            href="http://localhost/Attendance%20project%202/"
            class="back-link"
        >
            <i class="fa-solid fa-arrow-left"></i>
            Back to Homepage
        </a>

    </div>

</div>

</body>
</html>