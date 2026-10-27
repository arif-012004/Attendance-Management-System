<?php

session_start();

require_once "../config/db.php";


$error = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $admin_id = trim($_POST["admin_id"]);
    $password = $_POST["password"];


    if (
        empty($admin_id) ||
        empty($password)
    ) {

        $error = "Please enter Admin ID and Password.";

    } else {


        $stmt = $conn->prepare(
            "SELECT
                id,
                admin_id,
                name,
                email,
                password
             FROM admins
             WHERE admin_id = ?"
        );


        $stmt->bind_param(
            "s",
            $admin_id
        );


        $stmt->execute();


        $result = $stmt->get_result();


        if ($result->num_rows === 1) {

            $admin = $result->fetch_assoc();


            if (
                password_verify(
                    $password,
                    $admin["password"]
                )
            ) {


                session_regenerate_id(true);


                $_SESSION["admin_logged_in"] = true;

                $_SESSION["admin_id"] =
                    $admin["id"];

                $_SESSION["admin_username"] =
                    $admin["admin_id"];

                $_SESSION["admin_name"] =
                    $admin["name"];

                $_SESSION["admin_email"] =
                    $admin["email"];


                header(
                    "Location: dashboard.php"
                );

                exit();


            } else {

                $error =
                    "Invalid Admin ID or Password.";

            }


        } else {

            $error =
                "Invalid Admin ID or Password.";

        }


        $stmt->close();

    }

}

?>


<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Admin Login - Attendify</title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">


    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">


    <link rel="stylesheet" href="../dashboard.css">

</head>


<body>


<div class="auth-page">


    <div class="auth-card">


        <div class="auth-icon">

            <i class="fa-solid fa-shield-halved"></i>

        </div>


        <div class="brand-logo">

            <i class="fa-solid fa-qrcode"></i>

            Attendify

        </div>


        <h2>Admin Login</h2>


        <p class="auth-subtitle">

            Sign in to manage your attendance system.

        </p>


        <?php if (!empty($error)): ?>

            <div class="alert alert-danger">

                <i class="fa-solid fa-circle-exclamation"></i>

                <?php echo htmlspecialchars($error); ?>

            </div>

        <?php endif; ?>


        <form method="POST">


            <div class="form-group">

                <label>

                    <i class="fa-solid fa-id-card"></i>

                    Admin ID

                </label>


                <input
                    type="text"
                    name="admin_id"
                    placeholder="Enter Admin ID"
                    autocomplete="username"
                    required>

            </div>


            <div class="form-group">

                <label>

                    <i class="fa-solid fa-lock"></i>

                    Password

                </label>


                <input
                    type="password"
                    name="password"
                    placeholder="Enter Password"
                    autocomplete="current-password"
                    required>

            </div>


            <button
                type="submit"
                class="auth-btn">

                <i class="fa-solid fa-right-to-bracket"></i>

                Login

            </button>


        </form>


        <div class="auth-footer">

            <a href="../index.html">

                <i class="fa-solid fa-arrow-left"></i>

                Back to Homepage

            </a>

        </div>


    </div>

</div>


</body>

</html>