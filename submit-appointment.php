<?php
$host = "localhost";
$dbname = "eyecare";
$username = "root";
$password = "1234";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("DB connection failed: " . $e->getMessage());
}

$name = $_POST['name'];
$email = $_POST['email'];
$phone = $_POST['phone'];
$date = $_POST['date'];
$time = $_POST['time'];
$message = $_POST['message'] ?? '';

// Combine date and time to match DATETIME format
$datetime = date("Y-m-d H:i:s", strtotime("$date $time"));

// Get or create patient ID (assuming you store patients in an `accounts` table)
$stmt = $pdo->prepare("SELECT id FROM accounts WHERE email = ?");
$stmt->execute([$email]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    // Insert new patient
    $stmt = $pdo->prepare("INSERT INTO accounts (full_name, email, phone, role) VALUES (?, ?, ?, 'patient')");
    $stmt->execute([$name, $email, $phone]);
    $patient_id = $pdo->lastInsertId();
} else {
    $patient_id = $patient['id'];
}

// For now, assign doctor_id = 1 (you can later improve with doctor selection)
$doctor_id = 1;

// Insert appointment
$stmt = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, datetime, status, message, created_at) 
                       VALUES (?, ?, ?, 'pending', ?, NOW())");
$stmt->execute([$patient_id, $doctor_id, $datetime, $message]);

header("Location: booking-success.html"); // Or redirect to confirmation
exit;
