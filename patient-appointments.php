<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
  header('Location: login_patient.php');
  exit();
}

try {
  $pdo = new PDO("mysql:host=localhost;dbname=eyecare", "root", "1234");
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  die("Database connection failed.");
}



$patientId = $_SESSION['user_id'];

$stmt = $pdo->prepare("
  SELECT datetime, doctor_notes
  FROM appointments
  WHERE patient_id = ? 
    AND status IN ('confirmed', 'attended') 
    AND datetime >= NOW()
  ORDER BY datetime ASC
  LIMIT 1
");
$stmt->execute([$patientId]);
$appt = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Patient Appointments</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
  <style>
   body {
  font-family: 'Poppins', sans-serif;
  padding: 40px 20px;
  max-width: 700px;
  margin: 0 auto;
  background: linear-gradient(135deg, #e3f2fd 0%, #ffffff 100%);
  color: #333;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
}

h1 {
  color: #0d47a1;
  font-weight: 700;
  font-size: 2.5rem;
  margin-bottom: 30px;
  text-align: center;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  text-shadow: 1px 1px 4px rgba(13,71,161,0.2);
}

.appointment-card {
  background: #ffffff;
  border-radius: 16px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
  padding: 30px 25px;
  margin-bottom: 30px;
  transition: box-shadow 0.3s ease;
}

.appointment-card:hover {
  box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
}

.datetime {
  font-weight: 700;
  font-size: 1.3rem;
  margin-bottom: 12px;
  color: #1565c0;
  border-left: 5px solid #1565c0;
  padding-left: 15px;
}

.doctor-notes {
  font-style: italic;
  color: #555;
  white-space: pre-wrap;
  line-height: 1.6;
  font-size: 1rem;
}

.no-appointments {
  font-style: italic;
  color: #999;
  font-size: 1.2rem;
  text-align: center;
  margin-top: 60px;
}

  </style>
</head>
<body>

<h1>Your Next Appointment</h1>

<?php if ($appt): ?>
  <div class="appointment-card">
    <div class="datetime"><?= date("D, M j, Y - H:i", strtotime($appt['datetime'])) ?></div>
    <div class="doctor-notes">
      <?= !empty($appt['doctor_notes']) ? htmlspecialchars($appt['doctor_notes']) : 'No doctor notes available.' ?>
    </div>
  </div>
<?php else: ?>
  <p class="no-appointments">You have no upcoming appointments.</p>
<?php endif; ?>

</body>
</html>
