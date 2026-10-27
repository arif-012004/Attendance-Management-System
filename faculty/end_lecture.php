
<?php
session_start();

if (
    !isset($_SESSION["faculty_logged_in"]) ||
    $_SESSION["faculty_logged_in"] !== true
) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: view_lectures.php");
    exit();
}

require_once "../config/db.php";

$faculty_db_id = (int)$_SESSION["faculty_db_id"];
$lecture_id = filter_input(INPUT_POST, "lecture_id", FILTER_VALIDATE_INT);

if (!$lecture_id || $lecture_id <= 0) {
    header("Location: view_lectures.php");
    exit();
}

$stmt = $conn->prepare(
    "UPDATE lectures
     SET status = 'completed',
         end_time = CASE
             WHEN end_time IS NULL OR end_time = '00:00:00'
             THEN CURTIME()
             ELSE end_time
         END
     WHERE id = ?
       AND faculty_id = ?
       AND status = 'active'"
);

$stmt->bind_param("ii", $lecture_id, $faculty_db_id);
$stmt->execute();
$stmt->close();

header("Location: view_lectures.php");
exit();
?>