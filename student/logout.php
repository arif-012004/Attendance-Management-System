<?php
session_start();

unset(
    $_SESSION["student_logged_in"],
    $_SESSION["student_db_id"],
    $_SESSION["student_id"],
    $_SESSION["student_name"],
    $_SESSION["student_semester"],
    $_SESSION["student_csrf"]
);

header("Location: login.php");
exit;