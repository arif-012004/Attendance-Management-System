<?php

require_once "../config/db.php";

$message = "";
$message_type = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $admin_id = trim($_POST["admin_id"]);
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];


    if (
        empty($admin_id) ||
        empty($name) ||
        empty($email) ||
        empty($password)
    ) {

        $message = "Please fill all fields.";
        $message_type = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $message_type = "error";

    } elseif (strlen($password) < 6) {

        $message = "Password must contain at least 6 characters.";
        $message_type = "error";

    } else {


        /* CHECK EXISTING ADMIN */

        $check = $conn->prepare(
            "SELECT id
             FROM admins
             WHERE admin_id = ? OR email = ?"
        );

        $check->bind_param(
            "ss",
            $admin_id,
            $email
        );

        $check->execute();

        $result = $check->get_result();


        if ($result->num_rows > 0) {

            $message = "Admin ID or email already exists.";
            $message_type = "error";

        } else {


            /* HASH PASSWORD */

            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );


            /* INSERT ADMIN */

            $stmt = $conn->prepare(
                "INSERT INTO admins
                (admin_id, name, email, password)
                VALUES (?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "ssss",
                $admin_id,
                $name,
                $email,
                $hashed_password
            );


            if ($stmt->execute()) {

                $message = "Admin account created successfully.";
                $message_type = "success";

            } else {

                $message = "Unable to create admin account.";
                $message_type = "error";

            }

            $stmt->close();
        }

        $check->close();
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

    <title>Create Admin - Attendify</title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">


    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">


    <link
        rel="stylesheet"
        href="../css/dashboard.css">

</head>


<body>


<div class="auth-page">

    <div class="auth-card">


        <div class="auth-icon">

            <i class="fa-solid fa-user-shield"></i>

        </div>


        <div class="brand-logo">

            <i class="fa-solid fa-qrcode"></i>

            Attendify

        </div>


        <h2>Create Admin</h2>


        <p class="auth-subtitle">

            Create the administrator account
            for your attendance system.

        </p>


        <?php if (!empty($message)): ?>

            <div class="alert
                <?php
                echo $message_type === "success"
                    ? "alert-success"
                    : "alert-danger";
                ?>">

                <?php echo htmlspecialchars($message); ?>

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
                    required>

            </div>


            <div class="form-group">

                <label>

                    <i class="fa-solid fa-user"></i>

                    Admin Name

                </label>


                <input
                    type="text"
                    name="name"
                    placeholder="Enter Admin Name"
                    required>

            </div>


            <div class="form-group">

                <label>

                    <i class="fa-solid fa-envelope"></i>

                    Email

                </label>


                <input
                    type="email"
                    name="email"
                    placeholder="admin@example.com"
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
                    placeholder="Create Password"
                    minlength="6"
                    required>

            </div>


            <button
                type="submit"
                class="auth-btn">

                <i class="fa-solid fa-user-plus"></i>

                Create Admin

            </button>


        </form>


        <div class="auth-footer">

            <a href="login.php">

                Go to Admin Login

            </a>

        </div>


    </div>

</div>


</body>

</html>