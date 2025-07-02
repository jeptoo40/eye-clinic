<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: login_page.php');
    exit();
}

$pdo = new PDO("mysql:host=localhost;dbname=eyecare", "root", "1234");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$appointmentId = $_GET['appointment_id'] ?? null;

if (!$appointmentId) {
    die("Appointment ID missing.");
}

// Fetch appointment and patient info
$stmt = $pdo->prepare("
  SELECT a.id, a.patient_id, a.doctor_id, a.datetime, p.full_name AS patient_name
  FROM appointments a
  JOIN accounts p ON a.patient_id = p.id
  WHERE a.id = ?
");
$stmt->execute([$appointmentId]);
$appt = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$appt) die("Invalid appointment ID.");

$prescriptionText = '';

// Check if prescription already exists
$stmt = $pdo->prepare("SELECT * FROM prescriptions WHERE appointment_id = ?");
$stmt->execute([$appointmentId]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);
if ($existing) {
    $prescriptionText = $existing['prescription_text'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $text = trim($_POST['prescription_text']);

    if ($existing) {
        // Update
        $stmt = $pdo->prepare("UPDATE prescriptions SET prescription_text = ? WHERE appointment_id = ?");
        $stmt->execute([$text, $appointmentId]);
    } else {
        // Insert
        $stmt = $pdo->prepare("INSERT INTO prescriptions (appointment_id, patient_id, doctor_id, prescription_text)
                               VALUES (?, ?, ?, ?)");
        $stmt->execute([$appointmentId, $appt['patient_id'], $appt['doctor_id'], $text]);
    }

    header("Location: doctor_dashboard.php"); // redirect back
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Prescription for <?php echo htmlspecialchars($appt['patient_name']); ?></title>
  <style>
    body { font-family: Arial; padding: 20px; max-width: 700px; margin: auto; }
    textarea { width: 100%; height: 200px; padding: 10px; }
    button { margin-top: 10px; padding: 10px 20px; background: #3498db; color: #fff; border: none; }
  </style>
</head>
<body>
  <h2>Prescription for <?php echo htmlspecialchars($appt['patient_name']); ?></h2>
  <p>Appointment on: <?php echo date("D, M j, Y - H:i", strtotime($appt['datetime'])); ?></p>

  <form method="POST">
    <textarea name="prescription_text" required><?php echo htmlspecialchars($prescriptionText); ?></textarea><br>
    <button type="submit">Save Prescription</button>
  </form>
</body>
</html>
