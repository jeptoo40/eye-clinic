<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);


if ($_SERVER['SERVER_NAME'] === 'localhost') {
    $host = "localhost";
    $dbname = "eyecare";
    $username = "root";
    $password = "1234";
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Check if form is submitted via POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST["name"] ?? '';
    $email = $_POST["email"] ?? '';
    $age = $_POST["age"] ?? '';
    $gender = $_POST["gender"] ?? '';
    $password = $_POST["password"] ?? '';

    // Validate required fields
    if (!$name || !$email || !$password || !$age || !$gender) {
        die("Missing required fields.");
    }

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM accounts WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        die("Email already registered.");
    }

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert into patients table
    $stmt = $pdo->prepare("
    INSERT INTO accounts (role, full_name, email, age, gender, password) 
    VALUES (?, ?, ?, ?, ?, ?)
  ");
  $role = 'patient';  // since this is patient signup form
  $success = $stmt->execute([$role, $name, $email, $age, $gender, $hashedPassword]);
  

    if ($success) {
        header("Location: patient_dashboard.html");
        exit;
    } else {
        die("Failed to register patient.");
    }
}
?>
