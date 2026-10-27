
<?php
session_start();

require_once "../config/db.php";

/* Faculty login check */
if (empty($_SESSION["faculty_logged_in"])) {
    header("Location: ../index.html");
    exit;
}

$error = "";
$success = "";

/* Form submit hone par */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $student_id = trim($_POST["student_id"] ?? "");
    $name       = trim($_POST["name"] ?? "");
    $email      = trim($_POST["email"] ?? "");
    $phone      = trim($_POST["phone"] ?? "");
    $course     = trim($_POST["course"] ?? "");
    $semester   = trim($_POST["semester"] ?? "");
    $student_password = $_POST["student_password"] ?? "";

    /* 1. Required fields validation */
    if (
        $student_id === "" ||
        $name === "" ||
        $course === "" ||
        $semester === "" ||
        $student_password === ""
    ) {
        $error = "Please fill all required fields.";

    /* 2. Email validation */
    } elseif (
        $email !== "" &&
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {
        $error = "Please enter a valid email address.";

    /* 3. Password validation */
    } elseif (strlen($student_password) < 8) {
        $error = "Password must contain at least 8 characters.";

    /* 4. Semester validation */
    } elseif (
        !in_array(
            $semester,
            ["1", "2", "3", "4", "5", "6", "7", "8"],
            true
        )
    ) {
        $error = "Please select a valid semester.";

    } else {

        /* 5. Check duplicate Student ID */
        $check = $conn->prepare(
            "SELECT id FROM students WHERE student_id = ? LIMIT 1"
        );

        $check->bind_param("s", $student_id);
        $check->execute();

        $result = $check->get_result();

        if ($result->num_rows > 0) {

            $error = "Student ID already exists.";

        } else {

            /* 6. Securely hash the password */
            $password_hash = password_hash(
                $student_password,
                PASSWORD_DEFAULT
            );

            /* 7. Insert student into database */
            $stmt = $conn->prepare(
                "INSERT INTO students
                (student_id, name, email, phone, course, semester, password_hash)
                VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "sssssss",
                $student_id,
                $name,
                $email,
                $phone,
                $course,
                $semester,
                $password_hash
            );

            if ($stmt->execute()) {
                $success = "Student added successfully! Login credentials are ready.";
            } else {
                $error = "Unable to add student. Please check the database.";
            }

            $stmt->close();
        }

        $check->close();
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

    <title>Add Student | Attendify</title>

    <link rel="stylesheet" href="../dashboard.css">

    <style>
        .student-form {
            max-width: 720px;
            margin: 30px auto;
            padding: 28px;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 5px 20px rgba(0,0,0,.07);
        }

        .student-form h2 {
            margin-top: 0;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            font-weight: 600;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #d8dce5;
            border-radius: 8px;
            box-sizing: border-box;
        }

        .form-group small {
            display: block;
            margin-top: 6px;
            color: #777;
        }

        .form-message {
            padding: 12px;
            margin-bottom: 18px;
            border-radius: 8px;
        }

        .form-error {
            color: #a32132;
            background: #ffecef;
        }

        .form-success {
            color: #12653e;
            background: #e8fff2;
        }

        .form-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .form-buttons button,
        .form-buttons a {
            padding: 12px 18px;
            border: 0;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }

        .submit-btn {
            color: white;
            background: #5145d7;
        }

        .back-btn {
            color: #333;
            background: #eceef4;
        }
    </style>
</head>

<body>

<main class="student-form">

    <h2>Add New Student</h2>

    <p>Create a student account for the Attendify portal.</p>

    <?php if ($error !== ""): ?>
        <div class="form-message form-error">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success !== ""): ?>
        <div class="form-message form-success">
            <?= e($success) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="add_student.php">

        <div class="form-group">
            <label for="student_id">Student ID *</label>
            <input
                type="text"
                id="student_id"
                name="student_id"
                value="<?= e($_POST["student_id"] ?? "") ?>"
                required
            >
        </div>

        <div class="form-group">
            <label for="name">Student Name *</label>
            <input
                type="text"
                id="name"
                name="name"
                value="<?= e($_POST["name"] ?? "") ?>"
                required
            >
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input
                type="email"
                id="email"
                name="email"
                value="<?= e($_POST["email"] ?? "") ?>"
            >
        </div>

        <div class="form-group">
            <label for="phone">Phone</label>
            <input
                type="text"
                id="phone"
                name="phone"
                value="<?= e($_POST["phone"] ?? "") ?>"
            >
        </div>

        <div class="form-group">
            <label for="course">Course *</label>
            <input
                type="text"
                id="course"
                name="course"
                value="<?= e($_POST["course"] ?? "") ?>"
                required
            >
        </div>

        <div class="form-group">
            <label for="semester">Semester *</label>

            <select id="semester" name="semester" required>
                <option value="">Select Semester</option>

                <?php for ($i = 1; $i <= 8; $i++): ?>
                    <option
                        value="<?= $i ?>"
                        <?= (($_POST["semester"] ?? "") == (string)$i)
                            ? "selected"
                            : "" ?>
                    >
                        Semester <?= $i ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="student_password">Student Login Password *</label>

            <input
                type="password"
                id="student_password"
                name="student_password"
                minlength="8"
                autocomplete="new-password"
                placeholder="Minimum 8 characters"
                required
            >

            <small>
                Share this password securely with the student.
            </small>
        </div>

        <div class="form-buttons">
            <button type="submit" class="submit-btn">
                Add Student
            </button>

            <a href="dashboard.php" class="back-btn">
                Back to Dashboard
            </a>
        </div>

    </form>

</main>

</body>
</html>