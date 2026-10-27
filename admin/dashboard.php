<?php

session_start();


if (
    !isset($_SESSION["admin_logged_in"]) ||
    $_SESSION["admin_logged_in"] !== true
) {

    header("Location: login.php");

    exit();

}


$admin_name =
    $_SESSION["admin_name"];

?>


<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard - Attendify</title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">


    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">


    <link
        rel="stylesheet"
        href="../dashboard.css">

</head>


<body>


<!-- NAVBAR -->

<nav class="dashboard-navbar">

    <div class="container-fluid">


        <a
            href="dashboard.php"
            class="dashboard-brand">

            <i class="fa-solid fa-qrcode"></i>

            Attendify

        </a>


        <div class="dashboard-nav-links">


            <span class="welcome-text">

                Welcome,
                <?php
                echo htmlspecialchars($admin_name);
                ?>

            </span>


            <a href="dashboard.php">

                Dashboard

            </a>


            <a href="logout.php">

                <i class="fa-solid fa-right-from-bracket"></i>

                Logout

            </a>


        </div>

    </div>

</nav>



<!-- MAIN -->

<main class="dashboard-main">


    <div class="container">


        <div class="dashboard-heading">


            <span class="small-heading">

                ADMIN PANEL

            </span>


            <h1>

                Admin Dashboard

            </h1>


            <p>

                Manage faculty and your
                attendance system from one place.

            </p>


        </div>



        <!-- CARDS -->

        <div class="dashboard-grid">


            <!-- ADD FACULTY -->

            <div class="dashboard-card">


                <div class="card-icon">

                    <i class="fa-solid fa-user-plus"></i>

                </div>


                <h3>

                    Add Faculty

                </h3>


                <p>

                    Create a faculty account
                    and give them access
                    to the attendance system.

                </p>


                <a
                    href="add_faculty.php"
                    class="dashboard-btn">

                    Add Faculty

                    <i class="fa-solid fa-arrow-right"></i>

                </a>


            </div>



            <!-- VIEW FACULTY -->

            <div class="dashboard-card">


                <div class="card-icon">

                    <i class="fa-solid fa-users"></i>

                </div>


                <h3>

                    View Faculty

                </h3>


                <p>

                    View and manage all
                    faculty members registered
                    in the system.

                </p>


                <a
                    href="view_faculty.php"
                    class="dashboard-btn">

                    View Faculty

                    <i class="fa-solid fa-arrow-right"></i>

                </a>


            </div>



            <!-- SYSTEM OVERVIEW -->

            <div class="dashboard-card">


                <div class="card-icon">

                    <i class="fa-solid fa-chart-line"></i>

                </div>


                <h3>

                    System Overview

                </h3>


                <p>

                    Attendance statistics,
                    lectures and system
                    activity will appear here.

                </p>


                <a
                    href="#"
                    class="dashboard-btn secondary">

                    Coming Soon

                </a>


            </div>


        </div>


    </div>

</main>



<footer class="dashboard-footer">

    <p>

        © 2026 Attendify.
        All rights reserved.

    </p>

</footer>


</body>

</html>