<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: login_page.php');
    exit();
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=eyecare", "root", "1234");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed.");
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_text = trim($_POST['report_text']);
    $patient_id = $_SESSION['user_id'];
    $file_path = null;

    if (empty($report_text)) {
        $errors[] = "Report text is required.";
    }

    // Check for duplicate report
    if (empty($errors)) {
        $dupCheck = $pdo->prepare("SELECT COUNT(*) FROM patient_reports WHERE patient_id = ? AND report_text = ?");
        $dupCheck->execute([$patient_id, $report_text]);
        if ($dupCheck->fetchColumn() > 0) {
            $errors[] = "You have already submitted this report.";
        }
    }

    // Handle file upload
    if (isset($_FILES['report_file']) && $_FILES['report_file']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($_FILES['report_file']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $errors[] = "Invalid file type. Allowed types: PDF, JPG, JPEG, PNG.";
        } else {
            $targetDir = 'uploads/reports/';
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            $filename = uniqid('report_') . '.' . $ext;
            $targetFile = $targetDir . $filename;

            if (move_uploaded_file($_FILES['report_file']['tmp_name'], $targetFile)) {
                $file_path = $targetFile;
            } else {
                $errors[] = "Failed to upload the file.";
            }
        }
    }

    // Insert if no errors
    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO patient_reports (patient_id, report_text, file_path) VALUES (?, ?, ?)");
        $stmt->execute([$patient_id, $report_text, $file_path]);
        $success = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f7fa;
            padding: 40px;
        }
        .form-container {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        textarea {
            width: 100%;
            height: 150px;
            padding: 10px;
            margin-top: 10px;
            font-size: 14px;
            resize: vertical;
        }
        input[type="file"] {
            margin-top: 15px;
        }
        button {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 20px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px;
        }
        .success {
            background: #d4edda;
            padding: 10px;
            margin-top: 15px;
            border-left: 5px solid #28a745;
        }
        .error {
            background: #f8d7da;
            padding: 10px;
            margin-top: 15px;
            border-left: 5px solid #dc3545;
        }
    </style>
</head>
<body>
<div class="form-container">
    <h2>Submit a Report to Your Doctor</h2>

    <?php if ($success): ?>
        <div class="success">Your report has been submitted successfully!</div>
        <p style="margin-top:20px; font-style: italic; color:#555;">
            Redirecting to the patient dashboard in 10 seconds... 
            <a href="patient_dashboard.php">Click here</a> if not redirected.
        </p>
        <script>
            setTimeout(function() {
                window.location.href = "patient_dashboard.php";
            }, 10000);
        </script>
    <?php endif; ?>

    <?php foreach ($errors as $error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endforeach; ?>

    <?php if (!$success): ?>
        <form method="POST" enctype="multipart/form-data">
            <label for="report_text">Report Details:</label><br>
            <textarea name="report_text" id="report_text" required><?= htmlspecialchars($_POST['report_text'] ?? '') ?></textarea><br>

            <label for="report_file">Optional File Upload:</label><br>
            <input type="file" name="report_file" id="report_file" accept=".pdf,.jpg,.jpeg,.png"><br>

            <button type="submit">Submit Report</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
