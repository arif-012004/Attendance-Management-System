<?php

session_start();

if (!isset($_SESSION["admin_logged_in"]) || $_SESSION["admin_logged_in"] !== true) {
    header("Location: login.php");
    exit();
}

require_once "../config/db.php";

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $faculty_id = trim($_POST["faculty_id"]);
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $department = trim($_POST["department"]);
    $password = $_POST["password"];

    if (
        empty($faculty_id) ||
        empty($name) ||
        empty($email) ||
        empty($department) ||
        empty($password)
    ) {
        $message = "Please fill all required fields.";
        $message_type = "danger";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $message_type = "danger";
    } elseif (strlen($password) < 6) {
        $message = "Password must contain at least 6 characters.";
        $message_type = "danger";
    } else {

        $check = $conn->prepare(
            "SELECT id FROM faculty WHERE faculty_id = ? OR email = ?"
        );

        $check->bind_param("ss", $faculty_id, $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {

            $message = "Faculty ID or email already exists.";
            $message_type = "danger";

        } else {

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                "INSERT INTO faculty
                (faculty_id, name, email, phone, department, password)
                VALUES (?, ?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "ssssss",
                $faculty_id,
                $name,
                $email,
                $phone,
                $department,
                $hashed_password
            );

            if ($stmt->execute()) {

                $message = "Faculty account created successfully.";
                $message_type = "success";

                $_POST = array();

            } else {

                $message = "Unable to create faculty account.";
                $message_type = "danger";
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Faculty - Attendify</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

    <link rel="stylesheet" href="../dashboard.css">

</head>

<body>

<div class="auth-page">

    <div class="auth-card">

        <div class="auth-logo">

            <div class="logo-icon">
                <i class="fa-solid fa-user-plus"></i>
            </div>

            <h1>Add Faculty</h1>

            <p>Create a new faculty account</p>

        </div>


        <?php if (!empty($message)): ?>

            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php endif; ?>


        <form method="POST" action="">

            <div class="form-group">

                <label class="form-label">
                    Faculty ID *
                </label>

                <input
                    type="text"
                    name="faculty_id"
                    class="form-control"
                    placeholder="Example: FAC001"
                    value="<?php echo htmlspecialchars($_POST["faculty_id"] ?? ""); ?>"
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
                    placeholder="Enter faculty name"
                    value="<?php echo htmlspecialchars($_POST["name"] ?? ""); ?>"
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
                    placeholder="faculty@example.com"
                    value="<?php echo htmlspecialchars($_POST["email"] ?? ""); ?>"
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
                    placeholder="Enter phone number"
                    value="<?php echo htmlspecialchars($_POST["phone"] ?? ""); ?>">

            </div>


            <div class="form-group">

                <label class="form-label">
                    Department *
                </label>

                <input
                    type="text"
                    name="department"
                    class="form-control"
                    placeholder="Example: Computer Engineering"
                    value="<?php echo htmlspecialchars($_POST["department"] ?? ""); ?>"
                    required>

            </div>


            <div class="form-group">

                <label class="form-label">
                    Password *
                </label>

                <input
                    type="password"
                    name="password"
                    class="form-control"
                    placeholder="Minimum 6 characters"
                    required>

            </div>


            <button type="submit" class="primary-btn">

                <i class="fa-solid fa-user-plus"></i>
                Create Faculty

            </button>

        </form>


        <a href="dashboard.php" class="back-btn">

            <i class="fa-solid fa-arrow-left"></i>
            Back to Dashboard

        </a>

    </div>

</div>

</body>
</html>