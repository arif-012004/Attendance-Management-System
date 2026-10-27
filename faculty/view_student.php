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

$result = $conn->query(
    "SELECT id, student_id, name, email, phone, course, semester
     FROM students
     ORDER BY id DESC"
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Students - Attendify</title>

    <link rel="stylesheet" href="student.css">

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


<div class="faculty-list-page">

    <div class="faculty-list-header">

        <div>
            <h1>Students</h1>

            <p>
                Manage all students registered in Attendify.
            </p>
        </div>

        <a href="add_student.php" class="primary-btn">
            <i class="fa-solid fa-user-plus"></i>
            Add Student
        </a>

    </div>


    <div class="faculty-table-card">

        <?php if ($result && $result->num_rows > 0): ?>

            <div class="table-wrapper">

                <table class="faculty-table">

                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Student ID</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Course</th>
                            <th>Semester</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php while ($student = $result->fetch_assoc()): ?>

                        <tr>

                            <td>

                                <div class="faculty-person">

                                    <div class="faculty-avatar">
                                        <?php
                                        echo strtoupper(
                                            substr($student["name"], 0, 1)
                                        );
                                        ?>
                                    </div>

                                    <div>
                                        <strong>
                                            <?php
                                            echo htmlspecialchars(
                                                $student["name"]
                                            );
                                            ?>
                                        </strong>

                                        <small>
                                            Registered Student
                                        </small>
                                    </div>

                                </div>

                            </td>


                            <td>

                                <span class="faculty-id-badge">
                                    <?php
                                    echo htmlspecialchars(
                                        $student["student_id"]
                                    );
                                    ?>
                                </span>

                            </td>


                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $student["email"] ?: "—"
                                );
                                ?>
                            </td>


                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $student["phone"] ?: "—"
                                );
                                ?>
                            </td>


                            <td>

                                <span class="department-badge">
                                    <?php
                                    echo htmlspecialchars(
                                        $student["course"]
                                    );
                                    ?>
                                </span>

                            </td>


                            <td>
                                Semester
                                <?php
                                echo htmlspecialchars(
                                    $student["semester"]
                                );
                                ?>
                            </td>


                            <td>

                                <div class="faculty-actions">

                                    <a
                                        href="edit_student.php?id=<?php echo $student["id"]; ?>"
                                        class="action-btn edit-btn"
                                    >
                                        <i class="fa-solid fa-pen"></i>
                                        Edit
                                    </a>

                                    <a
                                        href="delete_student.php?id=<?php echo $student["id"]; ?>"
                                        class="action-btn delete-btn"
                                        onclick="return confirm('Are you sure you want to delete this student?');"
                                    >
                                        <i class="fa-solid fa-trash"></i>
                                        Delete
                                    </a>

                                </div>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="empty-state">

                <div class="empty-icon">
                    <i class="fa-solid fa-users"></i>
                </div>

                <h3>No Students Found</h3>

                <p>
                    No students have been registered yet.
                </p>

                <a href="add_student.php" class="primary-btn">
                    <i class="fa-solid fa-user-plus"></i>
                    Add Student
                </a>

            </div>

        <?php endif; ?>

    </div>

</div>

</body>
</html>