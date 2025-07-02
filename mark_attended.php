<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: login_page.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id'])) {
    $appointmentId = (int) $_POST['appointment_id'];
    $doctorNotes = trim($_POST['doctor_notes'] ?? '');

    try {
        $pdo = new PDO("mysql:host=localhost;dbname=eyecare", "root", "1234");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $update = $pdo->prepare("
            UPDATE appointments 
            SET status = 'attended', doctor_notes = :notes 
            WHERE id = :id
        ");
        $update->execute([
            ':notes' => $doctorNotes,
            ':id' => $appointmentId,
        ]);
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}

header('Location: doctor_dashboard.php');
exit();
