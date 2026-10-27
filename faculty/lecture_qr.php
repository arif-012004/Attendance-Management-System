
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

$faculty_db_id = (int)$_SESSION["faculty_db_id"];
$lecture_id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$lecture_id || $lecture_id <= 0) {
    header("Location: view_lectures.php");
    exit();
}

$stmt = $conn->prepare(
    "SELECT id, subject, room, lecture_date, start_time,
            radius, qr_token, status
     FROM lectures
     WHERE id = ? AND faculty_id = ?
     LIMIT 1"
);

$stmt->bind_param("ii", $lecture_id, $faculty_db_id);
$stmt->execute();

$result = $stmt->get_result();
$lecture = $result->fetch_assoc();

$stmt->close();

if (!$lecture) {
    header("Location: view_lectures.php");
    exit();
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

    <title>Lecture QR - Attendify</title>

    <link rel="stylesheet" href="../dashboard.css">
    <link rel="stylesheet" href="lecture_pages.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
</head>

<body>

<nav class="dashboard-navbar">
    <div class="dashboard-logo">
        <i class="fa-solid fa-calendar-check"></i>
        Attendify
    </div>

    <div class="dashboard-nav-right">
        <a href="view_lectures.php" class="logout-btn">
            <i class="fa-solid fa-arrow-left"></i>
            My Lectures
        </a>

        <a href="logout.php" class="logout-btn">
            <i class="fa-solid fa-right-from-bracket"></i>
            Logout
        </a>
    </div>
</nav>

<main class="lecture-list-page">

    <section class="lecture-qr-card">

        <span class="lecture-eyebrow">
            <i class="fa-solid fa-qrcode"></i>
            LECTURE ATTENDANCE
        </span>

        <h1><?= e($lecture["subject"]) ?></h1>

        <p class="qr-page-subtitle">
            <?= e($lecture["room"]) ?> ·
            <?= e(date("d M Y", strtotime($lecture["lecture_date"]))) ?>
        </p>

        <?php if ($lecture["status"] === "active"): ?>

            <span class="lecture-status status-active">
                <span class="status-dot"></span>
                Lecture Active
            </span>

            <div id="lectureQR" class="lecture-qr-display"></div>

            <p class="qr-page-note">
                Students must scan this QR and pass the server's
                location verification within <?= e($lecture["radius"]) ?> metres.
            </p>

            <button
                class="lecture-add-btn"
                type="button"
                onclick="downloadQR()"
            >
                <i class="fa-solid fa-download"></i>
                Download QR
            </button>

        <?php else: ?>

            <div class="lecture-alert error-alert">
                <i class="fa-solid fa-lock"></i>
                This lecture has ended. Attendance QR is no longer available.
            </div>

        <?php endif; ?>

        <div class="qr-page-footer">
            <a href="attendance.php?lecture_id=<?= (int)$lecture["id"] ?>"
               class="lecture-action-btn view-action">
                <i class="fa-solid fa-clipboard-check"></i>
                View Attendance
            </a>

            <a href="view_lectures.php" class="lecture-secondary-btn">
                Back to Lectures
            </a>
        </div>

    </section>

</main>

<?php if ($lecture["status"] === "active"): ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
const lectureToken = <?= json_encode($lecture["qr_token"]) ?>;

new QRCode(document.getElementById("lectureQR"), {
    text: lectureToken,
    width: 250,
    height: 250,
    colorDark: "#17192b",
    colorLight: "#ffffff",
    correctLevel: QRCode.CorrectLevel.H
});

function downloadQR() {
    const box = document.getElementById("lectureQR");
    const canvas = box.querySelector("canvas");
    const img = box.querySelector("img");

    let url = "";

    if (canvas) {
        url = canvas.toDataURL("image/png");
    } else if (img) {
        url = img.src;
    }

    if (!url) {
        alert("QR code is not ready.");
        return;
    }

    const a = document.createElement("a");
    a.href = url;
    a.download = "Attendify-Lecture-QR.png";
    a.click();
}
</script>

<?php endif; ?>

</body>
</html>