
<?php
session_start();
require_once "../config/db.php";

if (empty($_SESSION["faculty_logged_in"])) {
    header("Location: ../index.html");
    exit;
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$error = "";
$success = "";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
}

if (!$id || $id < 1) {
    exit("Invalid student ID.");
}

/* Load student details */
$stmt = $conn->prepare(
    "SELECT id, student_id, name, email, phone, course, semester
     FROM students WHERE id = ? LIMIT 1"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    exit("Student not found.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $student_id = trim($_POST["student_id"] ?? "");
    $name       = trim($_POST["name"] ?? "");
    $email      = trim($_POST["email"] ?? "");
    $phone      = trim($_POST["phone"] ?? "");
    $course     = trim($_POST["course"] ?? "");
    $semester   = trim($_POST["semester"] ?? "");
    $new_password = $_POST["student_password"] ?? "";

    if (
        $student_id === "" ||
        $name === "" ||
        $course === "" ||
        $semester === ""
    ) {
        $error = "Please fill all required fields.";
    } elseif (
        $email !== "" &&
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {
        $error = "Please enter a valid email address.";
    } elseif (!in_array($semester, ["1","2","3","4","5","6","7","8"], true)) {
        $error = "Please select a valid semester.";
    } elseif ($new_password !== "" && strlen($new_password) < 8) {
        $error = "New password must contain at least 8 characters.";
    } else {
        /* Check whether another student uses this ID */
        $check = $conn->prepare(
            "SELECT id FROM students
             WHERE student_id = ? AND id <> ? LIMIT 1"
        );
        $check->bind_param("si", $student_id, $id);
        $check->execute();
        $duplicate = $check->get_result()->num_rows > 0;
        $check->close();

        if ($duplicate) {
            $error = "That Student ID is already in use.";
        } else {
            $conn->begin_transaction();

            try {
                if ($new_password !== "") {
                    $password_hash = password_hash(
                        $new_password,
                        PASSWORD_DEFAULT
                    );

                    $update = $conn->prepare(
                        "UPDATE students
                         SET student_id=?, name=?, email=?, phone=?,
                             course=?, semester=?, password_hash=?
                         WHERE id=?"
                    );
                    $update->bind_param(
                        "sssssssi",
                        $student_id,
                        $name,
                        $email,
                        $phone,
                        $course,
                        $semester,
                        $password_hash,
                        $id
                    );
                } else {
                    $update = $conn->prepare(
                        "UPDATE students
                         SET student_id=?, name=?, email=?, phone=?,
                             course=?, semester=?
                         WHERE id=?"
                    );
                    $update->bind_param(
                        "ssssssi",
                        $student_id,
                        $name,
                        $email,
                        $phone,
                        $course,
                        $semester,
                        $id
                    );
                }

                if (!$update->execute()) {
                    throw new Exception("Student details could not be updated.");
                }

                $update->close();
                $conn->commit();

                $success = "Student details updated successfully.";

                /* Refresh displayed values */
                $student = [
                    "id" => $id,
                    "student_id" => $student_id,
                    "name" => $name,
                    "email" => $email,
                    "phone" => $phone,
                    "course" => $course,
                    "semester" => $semester
                ];

            } catch (Throwable $ex) {
                $conn->rollback();
                $error = "Update failed. Check your database columns and try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Edit Student | Attendify</title>
<link rel="stylesheet" href="../dashboard.css">
<style>
body{font-family:Arial,sans-serif;background:#f5f6fc;color:#252744}
.edit-card{max-width:720px;margin:35px auto;padding:28px;background:white;border-radius:14px;box-shadow:0 5px 22px #0000000d}
.form-group{margin-bottom:16px}
label{display:block;margin-bottom:7px;font-weight:600;font-size:14px}
input,select{width:100%;box-sizing:border-box;padding:12px;border:1px solid #d8dce5;border-radius:8px}
button,.back{display:inline-block;padding:12px 17px;border:0;border-radius:8px;background:#5145d7;color:white;text-decoration:none;cursor:pointer}
.back{background:#eceef4;color:#333}
.message{padding:12px;border-radius:8px;margin:15px 0}
.error{background:#ffecef;color:#a32132}
.success{background:#e8fff2;color:#12653e}
.actions{display:flex;gap:10px;flex-wrap:wrap}
small{color:#777}
</style>
</head>
<body>
<main class="edit-card">
    <h2>Edit Student</h2>

    <?php if ($error): ?>
        <div class="message error"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="message success"><?= e($success) ?></div>
    <?php endif; ?>

    <form method="POST" action="edit_student.php?id=<?= (int)$id ?>">
        <input type="hidden" name="id" value="<?= (int)$id ?>">

        <div class="form-group">
            <label>Student ID *</label>
            <input name="student_id" required value="<?= e($student["student_id"]) ?>">
        </div>

        <div class="form-group">
            <label>Student Name *</label>
            <input name="name" required value="<?= e($student["name"]) ?>">
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="<?= e($student["email"]) ?>">
        </div>

        <div class="form-group">
            <label>Phone</label>
            <input name="phone" value="<?= e($student["phone"]) ?>">
        </div>

        <div class="form-group">
            <label>Course *</label>
            <input name="course" required value="<?= e($student["course"]) ?>">
        </div>

        <div class="form-group">
            <label>Semester *</label>
            <select name="semester" required>
                <?php for ($i=1; $i<=8; $i++): ?>
                    <option value="<?= $i ?>"
                        <?= ((string)$student["semester"] === (string)$i) ? "selected" : "" ?>>
                        Semester <?= $i ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Set / Reset Student Password</label>
            <input type="password" name="student_password"
                   minlength="8" autocomplete="new-password"
                   placeholder="Leave blank to keep current password">
            <small>At least 8 characters. Leave blank to keep the existing password.</small>
        </div>

        <div class="actions">
            <button type="submit">Save Changes</button>
            <a class="back" href="dashboard.php">Back to Dashboard</a>
        </div>
    </form>
</main>
</body>
</html>