<?php

session_start();

if (!isset($_SESSION["admin_logged_in"]) || $_SESSION["admin_logged_in"] !== true) {
    header("Location: login.php");
    exit();
}

require_once "../config/db.php";

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($id <= 0) {
    header("Location: view_faculty.php");
    exit();
}


$stmt = $conn->prepare(
    "SELECT faculty_id, name, email, phone, department
     FROM faculty
     WHERE id = ?"
);

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: view_faculty.php");
    exit();
}

$faculty = $result->fetch_assoc();

$message = "";
$message_type = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $faculty_id = trim($_POST["faculty_id"]);
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $department = trim($_POST["department"]);

    if (
        empty($faculty_id) ||
        empty($name) ||
        empty($email) ||
        empty($department)
    ) {

        $message = "Please fill all required fields.";
        $message_type = "danger";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $message_type = "danger";

    } else {

        $check = $conn->prepare(
            "SELECT id FROM faculty
             WHERE (faculty_id = ? OR email = ?)
             AND id != ?"
        );

        $check->bind_param("ssi", $faculty_id, $email, $id);
        $check->execute();

        $check_result = $check->get_result();

        if ($check_result->num_rows > 0) {

            $message = "Faculty ID or email already exists.";
            $message_type = "danger";

        } else {

            $update = $conn->prepare(
                "UPDATE faculty
                 SET faculty_id = ?,
                     name = ?,
                     email = ?,
                     phone = ?,
                     department = ?
                 WHERE id = ?"
            );

            $update->bind_param(
                "sssssi",
                $faculty_id,
                $name,
                $email,
                $phone,
                $department,
                $id
            );

            if ($update->execute()) {

                $message = "Faculty details updated successfully.";
                $message_type = "success";

                $faculty["faculty_id"] = $faculty_id;
                $faculty["name"] = $name;
                $faculty["email"] = $email;
                $faculty["phone"] = $phone;
                $faculty["department"] = $department;

            } else {

                $message = "Unable to update faculty details.";
                $message_type = "danger";
            }

            $update->close();
        }

        $check->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Faculty - Attendify</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

    <link rel="stylesheet" href="../dashboard.css">

</head>

<body>

<div class="auth-page">

    <div class="auth-card">

        <div class="auth-logo">

            <div class="logo-icon">
                <i class="fa-solid fa-user-pen"></i>
            </div>

            <h1>Edit Faculty</h1>

            <p>Update faculty account details</p>

        </div>


        <?php if (!empty($message)): ?>

            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php endif; ?>


        <form method="POST">

            <div class="form-group">

                <label class="form-label">
                    Faculty ID *
                </label>

                <input
                    type="text"
                    name="faculty_id"
                    class="form-control"
                    value="<?php echo htmlspecialchars($faculty["faculty_id"]); ?>"
                    required>

            </div>


            <div class="form-group">

                <label class="form-label">
                    Faculty Name *
                </label>

                <input
                    type="text"
                    name="name"
                    class="form-control"
                    value="<?php echo htmlspecialchars($faculty["name"]); ?>"
                    required>

            </div>


            <div class="form-group">

                <label class="form-label">
                    Email *
                </label>

                <input
                    type="email"
                    name="email"
                    class="form-control"
                    value="<?php echo htmlspecialchars($faculty["email"]); ?>"
                    required>

            </div>


            <div class="form-group">

                <label class="form-label">
                    Phone
                </label>

                <input
                    type="text"
                    name="phone"
                    class="form-control"
                    value="<?php echo htmlspecialchars($faculty["phone"]); ?>">

            </div>


            <div class="form-group">

                <label class="form-label">
                    Department *
                </label>

                <input
                    type="text"
                    name="department"
                    class="form-control"
                    value="<?php echo htmlspecialchars($faculty["department"]); ?>"
                    required>

            </div>


            <button type="submit" class="btn-primary">

                <i class="fa-solid fa-floppy-disk"></i>
                Update Faculty

            </button>

        </form>


        <a href="view_faculty.php" class="back-link">

            <i class="fa-solid fa-arrow-left"></i>
            Back to Faculty List

        </a>

    </div>

</div>

</body>
</html>