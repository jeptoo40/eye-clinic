<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: login_patient.php");
    exit();
}

$pdo = new PDO("mysql:host=localhost;dbname=eyecare", "root", "1234");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Fetch patient info
$patientStmt = $pdo->prepare("SELECT full_name FROM accounts WHERE id = ?");
$patientStmt->execute([$_SESSION['user_id']]);
$patient = $patientStmt->fetch(PDO::FETCH_ASSOC);

// Fetch medical history
$historyStmt = $pdo->prepare("
    SELECT condition_name, diagnosis_date, notes 
    FROM medical_history 
    WHERE patient_id = ? 
    ORDER BY diagnosis_date DESC
");
$historyStmt->execute([$_SESSION['user_id']]);
$history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
  <title>Medical History - <?= htmlspecialchars($patient['full_name']) ?></title>
  <style>
    body { font-family: Arial; max-width: 800px; margin: auto; padding: 40px; background: #f9f9f9; }
    h1 { text-align: center; color: #2c3e50; }
    .record {
      background: #fff;
      padding: 20px;
      margin: 20px 0;
      border-left: 5px solid #3498db;
      box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    }
    .record h3 {
      margin: 0 0 5px;
      color: #2980b9;
    }
    .record small {
      color: #888;
    }
    .record p {
      margin-top: 10px;
    }
    .download-btn {
      display: block;
      text-align: center;
      margin: 30px auto;
      padding: 10px 20px;
      background: #3498db;
      color: #fff;
      text-decoration: none;
      border-radius: 25px;
      font-weight: bold;
    }

    @media print {
      .download-btn {
        display: none;
      }
    }
  </style>
</head>
<body>
  <h1>Medical History</h1>
  <p style="text-align:center;"><strong>Patient:</strong> <?= htmlspecialchars($patient['full_name']) ?></p>

  <a href="#" onclick="window.print()" class="download-btn">🖨️ Download as PDF</a>

  <?php if (count($history) === 0): ?>
    <p style="text-align:center; font-style: italic;">No medical history records found.</p>
  <?php else: ?>
    <?php foreach ($history as $entry): ?>
      <div class="record">
        <h3><?= htmlspecialchars($entry['condition_name']) ?></h3>
        <small>Date Diagnosed: <?= date("F j, Y", strtotime($entry['diagnosis_date'])) ?></small>
        <p><?= nl2br(htmlspecialchars($entry['notes'])) ?></p>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</body>
</html>
