
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

$faculty_db_id = (int)($_SESSION["faculty_db_id"] ?? 0);

if ($faculty_db_id <= 0) {
    die("Faculty session not found. Please login again.");
}

$error = "";
$success = "";

// Fetch faculty details
$stmt = $conn->prepare(
    "SELECT id, faculty_id, name, email, department
     FROM faculty
     WHERE id = ?"
);

$stmt->bind_param("i", $faculty_db_id);
$stmt->execute();

$result = $stmt->get_result();
$faculty = $result->fetch_assoc();
$stmt->close();

if (!$faculty) {
    die("Faculty account not found.");
}

// Update profile
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $department = trim($_POST["department"] ?? "");

    if ($name === "" || $email === "" || $department === "") {
        $error = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {

        // Check whether email is already used by another faculty
        $check = $conn->prepare(
            "SELECT id FROM faculty
             WHERE email = ? AND id != ?"
        );

        $check->bind_param("si", $email, $faculty_db_id);
        $check->execute();
        $email_result = $check->get_result();

        if ($email_result->num_rows > 0) {
            $error = "This email is already registered with another faculty.";
        }

        $check->close();

        if ($error === "") {

            $update = $conn->prepare(
                "UPDATE faculty
                 SET name = ?, email = ?, department = ?
                 WHERE id = ?"
            );

            $update->bind_param(
                "sssi",
                $name,
                $email,
                $department,
                $faculty_db_id
            );

            if ($update->execute()) {

                $_SESSION["faculty_name"] = $name;
                $_SESSION["faculty_email"] = $email;
                $_SESSION["faculty_department"] = $department;

                $success = "Profile updated successfully!";

                // Refresh faculty details
                $faculty["name"] = $name;
                $faculty["email"] = $email;
                $faculty["department"] = $department;

            } else {
                $error = "Unable to update profile. Please try again.";
            }

            $update->close();
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

    <title>Faculty Profile - Attendify</title>

    <link rel="stylesheet" href="../dashboard.css">
    <link rel="stylesheet" href="profile.css">

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

<main class="profile-page">

    <div class="profile-heading">
        <div>
            <span class="profile-label">FACULTY ACCOUNT</span>
            <h1>My Profile</h1>
            <p>View and manage your Attendify faculty information.</p>
        </div>
    </div>

    <?php if ($error !== ""): ?>
        <div class="profile-alert error">
            <i class="fa-solid fa-circle-exclamation"></i>
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success !== ""): ?>
        <div class="profile-alert success">
            <i class="fa-solid fa-circle-check"></i>
            <?= e($success) ?>
        </div>
    <?php endif; ?>

    <section class="profile-card">

        <div class="profile-banner">
            <div class="profile-avatar">
                <i class="fa-solid fa-user-tie"></i>
            </div>

            <div class="profile-banner-info">
                <h2><?= e($faculty["name"]) ?></h2>
                <p><?= e($faculty["department"]) ?></p>
                <span class="account-badge">
                    <i class="fa-solid fa-circle-check"></i>
                    Faculty Account
                </span>
            </div>
        </div>

        <form method="POST" action="profile.php" class="profile-form">

            <div class="profile-section-title">
                <i class="fa-solid fa-id-card"></i>
                <div>
                    <h3>Personal Information</h3>
                    <p>Keep your faculty details up to date.</p>
                </div>
            </div>

            <div class="profile-fields">

                <div class="profile-field">
                    <label for="faculty_id">Faculty ID</label>
                    <div class="profile-input-wrap">
                        <i class="fa-solid fa-fingerprint"></i>
                        <input
                            type="text"
                            id="faculty_id"
                            value="<?= e($faculty["faculty_id"]) ?>"
                            readonly
                        >
                    </div>
                    <small>Faculty ID cannot be changed here.</small>
                </div>

                <div class="profile-field">
                    <label for="name">Full Name</label>
                    <div class="profile-input-wrap">
                        <i class="fa-solid fa-user"></i>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="<?= e($faculty["name"]) ?>"
                            maxlength="100"
                            required
                        >
                    </div>
                </div>

                <div class="profile-field">
                    <label for="email">Email Address</label>
                    <div class="profile-input-wrap">
                        <i class="fa-solid fa-envelope"></i>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?= e($faculty["email"]) ?>"
                            maxlength="150"
                            required
                        >
                    </div>
                </div>

                <div class="profile-field">
                    <label for="department">Department</label>
                    <div class="profile-input-wrap">
                        <i class="fa-solid fa-building"></i>
                        <input
                            type="text"
                            id="department"
                            name="department"
                            value="<?= e($faculty["department"]) ?>"
                            maxlength="100"
                            required
                        >
                    </div>
                </div>

            </div>

            <div class="profile-actions">
                <a href="dashboard.php" class="profile-cancel-btn">
                    Cancel
                </a>

                <button type="submit" class="profile-save-btn">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Save Changes
                </button>
            </div>

        </form>
    </section>

    <div class="profile-note">
        <i class="fa-solid fa-shield-halved"></i>
        <span>
            Your profile information is linked to your Attendify faculty account.
        </span>
    </div>

</main>

</body>
</html>