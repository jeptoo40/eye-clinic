<?php
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['doctor', 'patient'])) {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

$doctor_id = $role === 'doctor' ? $user_id : ($_GET['doctor_id'] ?? null);
$patient_id = $role === 'patient' ? $user_id : ($_GET['patient_id'] ?? null);

if (!$doctor_id || !$patient_id) {
    http_response_code(400);
    echo json_encode(["error" => "Missing doctor or patient ID"]);
    exit;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=eyecare", "root", "1234");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("
        SELECT message_text, sender_role, sent_at
        FROM doctor_messages
        WHERE doctor_id = ? AND patient_id = ?
        ORDER BY sent_at ASC
    ");
    $stmt->execute([$doctor_id, $patient_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($messages);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error"]);
}
