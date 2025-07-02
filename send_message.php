<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: login_page.php");
    exit();
}

$doctor_id = $_SESSION['user_id'];
$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;

if ($patient_id <= 0) {
    die("Invalid patient ID.");
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=eyecare", "root", "1234");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB connection failed.");
}

// Check patient exists
$stmt = $pdo->prepare("SELECT full_name FROM accounts WHERE id = ? AND role = 'patient'");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$patient) {
    die("Patient not found.");
}

// Handle message
$success = '';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message'] ?? '');
    if (empty($message)) {
        $errors[] = "Message cannot be empty.";
    } else {
        $insert = $pdo->prepare("INSERT INTO doctor_messages (doctor_id, patient_id, message_text) VALUES (?, ?, ?)");
        $insert->execute([$doctor_id, $patient_id, $message]);
        $success = "Message sent successfully.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Send Message to <?= htmlspecialchars($patient['full_name']) ?></title>
</head>
<body>
    <h2>Send Message to <?= htmlspecialchars($patient['full_name']) ?></h2>

    <?php if ($success): ?>
        <p style="color: green;"><?= $success ?></p>
    <?php endif; ?>

    <?php foreach ($errors as $err): ?>
        <p style="color: red;"><?= htmlspecialchars($err) ?></p>
    <?php endforeach; ?>

    <form method="POST">
        <textarea name="message" rows="6" cols="50" placeholder="Write your message..."><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea><br>
        <button type="submit">Send</button>
    </form>
    <br>
    <a href="doctor_dashboard.php">← Back to Dashboard</a>
</body>
</html>
