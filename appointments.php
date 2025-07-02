<?php
$host = 'localhost';
$db   = 'eyecare';
$user = 'root';
$pass = ''; // or your MySQL password

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Example: show appointments for doctor_id = 1
$doctor_id = 1;

$sql = "SELECT * FROM appointments WHERE doctor_id = ? ORDER BY datetime ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
  <title>Doctor Dashboard - Appointments</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-5">
  <h1 class="mb-4">Upcoming Appointments</h1>

  <?php if ($result->num_rows > 0): ?>
    <table class="table table-bordered table-striped">
      <thead class="table-dark">
        <tr>
          <th>#</th>
          <th>Date & Time</th>
          <th>Status</th>
          <th>Patient ID</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= $row['id'] ?></td>
            <td><?= date("D, M j, Y - g:i A", strtotime($row['datetime'])) ?></td>
            <td><?= ucfirst($row['status']) ?></td>
            <td><?= $row['patient_id'] ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  <?php else: ?>
    <div class="alert alert-info">No appointments found for this doctor.</div>
  <?php endif; ?>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
