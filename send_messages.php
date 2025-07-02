<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_POST['receiver_id']) || !isset($_POST['message'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing fields']);
    exit();
}

$sender_id = $_SESSION['user_id'];
$receiver_id = (int) $_POST['receiver_id'];
$message = trim($_POST['message']);

if ($message === '') {
    echo json_encode(['status' => 'error', 'message' => 'Empty message']);
    exit();
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=eyecare", "root", "1234");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("INSERT INTO chat_messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$sender_id, $receiver_id, $message]);

    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'DB error']);
}
