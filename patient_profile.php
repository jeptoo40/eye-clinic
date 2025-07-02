<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: login_page.php');
    exit();
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=eyecare", "root", "1234");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed.");
}

$patient_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($patient_id <= 0) {
    die("Invalid patient ID.");
}

// Fetch patient information
$stmt = $pdo->prepare("SELECT full_name, age, gender, email FROM accounts WHERE id = ? AND role = 'patient'");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$patient) {
    die("Patient not found.");
}

// Fetch medical history
$historyStmt = $pdo->prepare("SELECT condition_name, diagnosis_date, notes FROM medical_history WHERE patient_id = ?");
$historyStmt->execute([$patient_id]);
$medicalHistory = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch prescriptions
$rxStmt = $pdo->prepare("
    SELECT p.prescription_text, p.created_at, a.datetime
    FROM prescriptions p
    JOIN appointments a ON p.appointment_id = a.id
    WHERE p.patient_id = ?
    ORDER BY p.created_at DESC
");
$rxStmt->execute([$patient_id]);
$prescriptions = $rxStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Profile - <?= htmlspecialchars($patient['full_name']) ?></title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #eef2f7;
            margin: 0;
            padding: 40px;
            color: #333;
        }
        .container {
            max-width: 960px;
            margin: auto;
            background: #fff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }
        h1 {
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            color: #2c3e50;
        }
        .section {
            margin-bottom: 40px;
        }
        .section h2 {
            color: #3498db;
            border-left: 4px solid #3498db;
            padding-left: 10px;
        }
        .record {
            border-left: 4px solid #95a5a6;
            padding-left: 15px;
            margin-top: 15px;
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
        }
        .record strong {
            font-size: 1.05em;
        }
        pre {
            background: #f4f4f4;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
        small {
            color: #666;
        }
        a.back {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            color: #3498db;
            font-weight: bold;
        }
        .info p {
            margin: 5px 0;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Patient Profile</h1>

    <div class="section info">
        <h2>Personal Information</h2>
        <p><strong>Name:</strong> <?= htmlspecialchars($patient['full_name']) ?></p>
        <p><strong>Age:</strong> <?= htmlspecialchars($patient['age']) ?></p>
        <p><strong>Gender:</strong> <?= htmlspecialchars($patient['gender']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($patient['email']) ?></p>
    </div>

    <div class="section">
        <h2>Medical History</h2>
        <?php if (empty($medicalHistory)): ?>
            <p>No medical history available.</p>
        <?php else: ?>
            <?php foreach ($medicalHistory as $entry): ?>
                <div class="record">
                    <strong><?= htmlspecialchars($entry['condition_name']) ?></strong><br>
                    <small>Diagnosed on: <?= htmlspecialchars($entry['diagnosis_date']) ?></small>
                    <p><?= nl2br(htmlspecialchars($entry['notes'])) ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2>Prescriptions</h2>
        <?php if (empty($prescriptions)): ?>
            <p>No prescriptions available.</p>
        <?php else: ?>
            <?php foreach ($prescriptions as $rx): ?>
                <div class="record">
                    <strong>Date:</strong> <?= date("D, M j, Y - H:i", strtotime($rx['datetime'])) ?><br>
                    <pre><?= htmlspecialchars($rx['prescription_text']) ?></pre>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <a class="back" href="doctor_dashboard.php">&larr; Back to Dashboard</a>
</div>

</body>
</html>
