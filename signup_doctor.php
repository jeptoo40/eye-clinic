<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Database credentials
$host = "localhost";
$dbname = "eyecare";
$username = "root";
$password = "1234";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST["name"] ?? '';
    $email = $_POST["email"] ?? '';
    $specialization = $_POST["specialization"] ?? '';
    $license = $_POST["license"] ?? '';
    $password = $_POST["password"] ?? '';

    // Validate inputs
    if (!$name || !$email || !$specialization || !$license || !$password) {
        die("All fields are required.");
    }

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM accounts WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        die("Email already registered.");
    }

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert into accounts table
    $stmt = $pdo->prepare("INSERT INTO accounts (role, full_name, email, password, specialization, license_number) VALUES (?, ?, ?, ?, ?, ?)");
    $success = $stmt->execute(['doctor', $name, $email, $hashedPassword, $specialization, $license]);

    if ($success) {
        header("Location: doctor_dashboard.php");
        exit();
    } else {
        die("Failed to register doctor.");
    }
}
?>
