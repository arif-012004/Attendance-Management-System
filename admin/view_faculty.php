<?php

session_start();

if (
    !isset($_SESSION["admin_logged_in"]) ||
    $_SESSION["admin_logged_in"] !== true
) {
    header("Location: login.php");
    exit();
}

require_once "../config/db.php";

/* Fetch all faculty */
$sql = "SELECT id, faculty_id, name, email, phone, department, created_at
        FROM faculty
        ORDER BY id DESC";

$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Faculty Management - Attendify</title>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
    >

    <link rel="stylesheet" href="../dashboard.css">

    <style>

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 25px;
        }

        .page-title h1 {
            margin-bottom: 6px;
        }

        .page-title p {
            margin: 0;
        }

        .add-faculty-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 18px;
            border-radius: 9px;
            background: linear-gradient(135deg, #5b5cf0, #7048e8);
            color: #ffffff;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 6px 16px rgba(91, 92, 240, 0.18);
            transition: 0.2s ease;
        }

        .add-faculty-btn:hover {
            color: #ffffff;
            transform: translateY(-1px);
        }

        .faculty-card {
            background: #ffffff;
            border: 1px solid #e7e9f2;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(30, 35, 70, 0.06);
            overflow: hidden;
        }

        .faculty-card-header {
            padding: 22px 25px;
            border-bottom: 1px solid #edf0f5;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .faculty-card-header h3 {
            margin: 0;
            font-size: 17px;
            color: #252a45;
        }

        .faculty-count {
            font-size: 13px;
            color: #73788f;
            background: #f3f4ff;
            padding: 6px 10px;
            border-radius: 20px;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .faculty-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }

        .faculty-table th {
            background: #fafbff;
            color: #62687d;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 15px 18px;
            text-align: left;
            border-bottom: 1px solid #e9ebf2;
        }

        .faculty-table td {
            padding: 17px 18px;
            color: #42475c;
            font-size: 14px;
            border-bottom: 1px solid #f0f1f5;
            vertical-align: middle;
        }

        .faculty-table tbody tr {
            transition: 0.15s ease;
        }

        .faculty-table tbody tr:hover {
            background: #fafaff;
        }

        .faculty-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .faculty-avatar {
            width: 40px;
            height: 40px;
            flex-shrink: 0;
            border-radius: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eef0ff;
            color: #5b5cf0;
            font-size: 16px;
            font-weight: 700;
        }

        .faculty-name {
            font-weight: 600;
            color: #292e46;
        }

        .faculty-id {
            color: #777d91;
            font-size: 12px;
            margin-top: 3px;
        }

        .department-badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 7px;
            background: #f1f2ff;
            color: #5758d9;
            font-size: 12px;
            font-weight: 600;
        }

        .action-buttons {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .action-btn {
            width: 35px;
            height: 35px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            transition: 0.2s ease;
        }

        .edit-btn {
            background: #eef0ff;
            color: #5758d9;
        }

        .edit-btn:hover {
            background: #5b5cf0;
            color: #ffffff;
        }

        .delete-btn {
            background: #fff0f0;
            color: #e14d4d;
        }

        .delete-btn:hover {
            background: #e14d4d;
            color: #ffffff;
        }

        .empty-state {
            text-align: center;
            padding: 65px 20px;
        }

        .empty-icon {
            width: 65px;
            height: 65px;
            margin: 0 auto 18px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f0f1ff;
            color: #5b5cf0;
            font-size: 25px;
        }

        .empty-state h3 {
            margin-bottom: 8px;
            color: #292e46;
        }

        .empty-state p {
            color: #777d91;
            margin-bottom: 20px;
            font-size: 14px;
        }

        @media (max-width: 768px) {

            .page-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .add-faculty-btn {
                width: 100%;
                justify-content: center;
            }

            .faculty-card-header {
                padding: 18px;
            }

        }

    </style>

</head>

<body>

<!-- NAVBAR -->

<nav class="dashboard-navbar">

    <a href="dashboard.php" class="dashboard-logo">
        <i class="fa-solid fa-qrcode"></i>
        Attendify
    </a>

    <div class="dashboard-user">

        <span>
            <?php echo htmlspecialchars($_SESSION["admin_name"]); ?>
        </span>

        <a href="logout.php" class="logout-btn">
            Logout
        </a>

    </div>

</nav>


<!-- MAIN CONTENT -->

<div class="dashboard-container">


    <!-- PAGE HEADER -->

    <div class="page-header">

        <div class="page-title">

            <h1>Faculty Management</h1>

            <p>
                View and manage all registered faculty members.
            </p>

        </div>


        <a href="add_faculty.php" class="add-faculty-btn">

            <i class="fa-solid fa-plus"></i>

            Add Faculty

        </a>

    </div>


    <!-- FACULTY TABLE -->

    <div class="faculty-card">


        <div class="faculty-card-header">

            <h3>

                <i class="fa-solid fa-users"></i>

                Faculty List

            </h3>


            <span class="faculty-count">

                <?php echo $result->num_rows; ?>

                Faculty

            </span>

        </div>


        <?php if ($result->num_rows > 0): ?>


            <div class="table-wrapper">

                <table class="faculty-table">

                    <thead>

                        <tr>

                            <th>Faculty</th>

                            <th>Email</th>

                            <th>Phone</th>

                            <th>Department</th>

                            <th>Actions</th>

                        </tr>

                    </thead>


                    <tbody>


                        <?php while ($faculty = $result->fetch_assoc()): ?>


                            <tr>


                                <!-- FACULTY -->

                                <td>

                                    <div class="faculty-info">

                                        <div class="faculty-avatar">

                                            <?php
                                            echo strtoupper(
                                                substr($faculty["name"], 0, 1)
                                            );
                                            ?>

                                        </div>


                                        <div>

                                            <div class="faculty-name">

                                                <?php
                                                echo htmlspecialchars(
                                                    $faculty["name"]
                                                );
                                                ?>

                                            </div>


                                            <div class="faculty-id">

                                                <?php
                                                echo htmlspecialchars(
                                                    $faculty["faculty_id"]
                                                );
                                                ?>

                                            </div>

                                        </div>

                                    </div>

                                </td>


                                <!-- EMAIL -->

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $faculty["email"]
                                    );
                                    ?>

                                </td>


                                <!-- PHONE -->

                                <td>

                                    <?php

                                    echo !empty($faculty["phone"])
                                        ? htmlspecialchars($faculty["phone"])
                                        : "—";

                                    ?>

                                </td>


                                <!-- DEPARTMENT -->

                                <td>

                                    <span class="department-badge">

                                        <?php
                                        echo htmlspecialchars(
                                            $faculty["department"]
                                        );
                                        ?>

                                    </span>

                                </td>


                                <!-- ACTIONS -->

                                <td>

                                    <div class="action-buttons">


                                        <!-- EDIT -->

                                        <a
                                            href="edit_faculty.php?id=<?php echo (int)$faculty["id"]; ?>"
                                            class="action-btn edit-btn"
                                            title="Edit Faculty"
                                        >

                                            <i class="fa-solid fa-pen"></i>

                                        </a>


                                        <!-- DELETE -->

                                        <a
                                            href="delete_faculty.php?id=<?php echo (int)$faculty["id"]; ?>"
                                            class="action-btn delete-btn"
                                            title="Delete Faculty"
                                            onclick="return confirm('Are you sure you want to delete this faculty member?');"
                                        >

                                            <i class="fa-solid fa-trash"></i>

                                        </a>


                                    </div>

                                </td>


                            </tr>


                        <?php endwhile; ?>


                    </tbody>

                </table>

            </div>


        <?php else: ?>


            <!-- EMPTY STATE -->

            <div class="empty-state">

                <div class="empty-icon">

                    <i class="fa-solid fa-user-group"></i>

                </div>


                <h3>No Faculty Added Yet</h3>


                <p>
                    Start by adding your first faculty member.
                </p>


                <a href="add_faculty.php" class="add-faculty-btn">

                    <i class="fa-solid fa-plus"></i>

                    Add Faculty

                </a>

            </div>


        <?php endif; ?>


    </div>


    <!-- BACK -->

    <a href="dashboard.php" class="back-link">

        <i class="fa-solid fa-arrow-left"></i>

        Back to Dashboard

    </a>


</div>

</body>

</html>