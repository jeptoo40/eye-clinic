<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: login_patient.php');
    exit();
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=eyecare", "root", "1234");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get patient info
    $patientStmt = $pdo->prepare("SELECT full_name, age FROM accounts WHERE id = ?");
    $patientStmt->execute([$_SESSION['user_id']]);
    $patient = $patientStmt->fetch(PDO::FETCH_ASSOC);

    // Get prescriptions with doctor name
    $stmt = $pdo->prepare("
        SELECT p.id, p.prescription_text, p.created_at, a.datetime, a.doctor_id, ac.full_name AS doctor_name
        FROM prescriptions p
        JOIN appointments a ON p.appointment_id = a.id
        JOIN accounts ac ON a.doctor_id = ac.id
        WHERE p.patient_id = ?
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Prescriptions</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:400,700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Roboto', sans-serif;
      background: #fefefe;
      padding: 40px 20px;
      color: #222;
      max-width: 800px;
      margin: auto;
    }

    .clinic-header {
      text-align: center;
      border-bottom: 2px solid #ccc;
      padding-bottom: 10px;
      margin-bottom: 30px;
    }

    .clinic-header h1 {
      font-size: 28px;
      margin: 0;
      color: #2c3e50;
    }

    .clinic-header p {
      margin: 4px 0;
      font-size: 14px;
      color: #666;
    }

    .download-btn {
      display: inline-block;
      margin: 20px auto 40px;
      background: #3498db;
      color: #fff;
      padding: 10px 20px;
      border-radius: 30px;
      text-decoration: none;
      font-weight: bold;
      text-align: center;
      transition: background 0.3s;
    }

    .download-btn:hover {
      background: #2980b9;
    }

    .prescription {
      border: 1px solid #aaa;
      padding: 25px;
      margin-bottom: 30px;
      border-radius: 10px;
      background: #fff;
      box-shadow: 0 0 10px rgba(0,0,0,0.05);
      page-break-inside: avoid;
    }

    .prescription-header {
      display: flex;
      justify-content: space-between;
      margin-bottom: 15px;
      font-size: 14px;
    }

    .patient-info, .doctor-name {
      font-size: 15px;
      margin-bottom: 10px;
    }

    .rx-label {
      font-size: 22px;
      font-weight: bold;
      color: #2c3e50;
      margin-bottom: 10px;
    }

    .prescription-content {
      font-size: 16px;
      line-height: 1.6;
      white-space: pre-wrap;
      padding-left: 10px;
    }

    .no-data {
      text-align: center;
      font-style: italic;
      color: #999;
    }

    @import url('https://fonts.googleapis.com/css2?family=Great+Vibes&display=swap');
    .signature {
  text-align: right;
  margin-top: 30px;
}

.signed-name {
  font-family: 'Great Vibes', cursive;
  font-size: 24px;
  color: #2c3e50;
  margin-bottom: 2px;
}

.signature-label {
  font-size: 12px;
  color: #666;
}


    @media print {
      .download-btn {
        display: none;
      }

      .prescription {
        page-break-after: always;
      }
    }
  </style>
</head>
<body>

  <div class="clinic-header">
    <h1>EyeCare Clinic</h1>
    <p>123 Health Street, Vision City</p>
    <p>Phone: (123) 456-7890 | Email: contact@eyecare.com</p>
  </div>

  <a href="#" onclick="window.print();" class="download-btn">🖨️ Download as PDF</a>

  <h2 style="text-align:center;">Prescription Records</h2>

  <?php if (count($prescriptions) === 0): ?>
    <p class="no-data">No prescriptions available.</p>
  <?php else: ?>
    <?php foreach ($prescriptions as $rx): ?>
      <div class="prescription">
        <div class="prescription-header">
          <div>Date: <strong><?= date("D, M j, Y", strtotime($rx['datetime'])) ?></strong></div>
          <div>Issued: <strong><?= date("H:i", strtotime($rx['created_at'])) ?></strong></div>
        </div>
        <div class="patient-info">Patient: <strong><?= htmlspecialchars($patient['full_name']) ?></strong>, Age: <strong><?= $patient['age'] ?></strong></div>
        <div class="doctor-name">Prescribed by: Dr. <?= htmlspecialchars($rx['doctor_name']) ?></div>
        <div class="rx-label">Rx</div>
        <div class="prescription-content"><?= htmlspecialchars($rx['prescription_text']) ?></div>
        <div class="signature">
  <div class="signed-name">Dr. <?= htmlspecialchars($rx['doctor_name']) ?></div>
  <div class="signature-label">Doctor's Signature</div>
</div>

      </div>
    <?php endforeach; ?>
  <?php endif; ?>

</body>
</html>
