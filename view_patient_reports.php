<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: login_page.php');
    exit();
}

$patient_id = isset($_GET['patient_id']) && ctype_digit($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
if ($patient_id <= 0) {
    die("Invalid patient ID.");
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=eyecare", "root", "1234");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed.");
}

// Get patient info
$patientStmt = $pdo->prepare("SELECT full_name, email FROM accounts WHERE id = ? AND role = 'patient'");
$patientStmt->execute([$patient_id]);
$patient = $patientStmt->fetch(PDO::FETCH_ASSOC);
if (!$patient) {
    die("Patient not found.");
}

// Fetch reports submitted by this patient
$stmt = $pdo->prepare("
    SELECT report_text, file_path, submitted_at
    FROM patient_reports
    WHERE patient_id = ?
    ORDER BY submitted_at DESC
");
$stmt->execute([$patient_id]);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($patient['full_name']) ?> - Reports</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #eef2f5;
            padding: 40px;
        }
        .container {
            max-width: 900px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        h2 {
            margin-top: 0;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 8px;
        }
        .report {
            margin-top: 20px;
            background: #f8f9fa;
            border-left: 4px solid #2980b9;
            padding: 15px;
            border-radius: 6px;
        }
        .meta {
            font-size: 0.9em;
            color: #555;
            margin-bottom: 10px;
        }
        pre {
            background: #f4f4f4;
            padding: 10px;
            white-space: pre-wrap;
            border-radius: 4px;
        }
        .download {
            display: inline-block;
            margin-top: 10px;
            color: #3498db;
            text-decoration: none;
        }
        .download:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Reports from <?= htmlspecialchars($patient['full_name']) ?> (<?= htmlspecialchars($patient['email']) ?>)</h2>

    <?php if (empty($reports)): ?>
        <p>No reports submitted by this patient yet.</p>
    <?php else: ?>
        <?php foreach ($reports as $report): ?>
            <div class="report">
                <div class="meta">
                    Submitted on: <?= date("D, M j, Y - H:i", strtotime($report['submitted_at'])) ?>
                </div>
                <pre><?= htmlspecialchars($report['report_text']) ?></pre>
                <?php if (!empty($report['file_path']) && file_exists($report['file_path'])): ?>
                    <a class="download" href="<?= htmlspecialchars($report['file_path']) ?>" target="_blank">📄 View/Download Attachment</a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>
