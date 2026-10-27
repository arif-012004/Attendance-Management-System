<?php

session_start();

if (!isset($_SESSION["admin_logged_in"]) || $_SESSION["admin_logged_in"] !== true) {
    header("Location: login.php");
    exit();
}

require_once "../config/db.php";

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($id > 0) {

    $stmt = $conn->prepare(
        "DELETE FROM faculty WHERE id = ?"
    );

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $stmt->close();
}

header("Location: view_faculty.php");
exit();

?>