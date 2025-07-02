<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: accounts.html');
    exit();
}

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

// Fetch all appointments with patient info
$stmt = $pdo->prepare("
    SELECT a.id, a.datetime, a.status, a.message, p.id AS patient_id, p.full_name AS patient_name 
    FROM appointments a
    JOIN accounts p ON a.patient_id = p.id
    ORDER BY a.datetime DESC
");
$stmt->execute();
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
$appointment_count = count($appointments);
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Doctor Dashboard</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background-color: #f5f6fa;
            margin: 0;
            padding: 0;
        }

        .header {
            background-color: #2c3e50;
            padding: 20px;
            color: #fff;
            text-align: center;
        }

        .container {
            max-width: 1000px;
            margin: 30px auto;
            background: #ffffff;
            padding: 25px 30px;
            box-shadow: 0px 0px 10px rgba(0,0,0,0.05);
            border-radius: 8px;
        }

        h1, h2 {
            margin-top: 0;
            color: #34495e;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e1e1e1;
        }

        th {
            background-color: #ecf0f1;
            font-weight: bold;
        }

        tr:hover {
            background-color: #f9f9f9;
        }

        .status-pending {
            color: #e67e22;
            font-weight: bold;
        }

        .status-confirmed {
            color: #27ae60;
            font-weight: bold;
        }

        .status-cancelled {
            color: #c0392b;
            font-weight: bold;
        }

        a {
            text-decoration: none;
            color: #2980b9;
        }

        a:hover {
            text-decoration: underline;
        }

        .no-data {
            padding: 10px;
            background: #fef8e7;
            border-left: 5px solid #f1c40f;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<div class="header">
    <h1>Welcome Dr. <?php echo htmlspecialchars($_SESSION['name'] ?? ''); ?></h1>
</div>

<div class="container">
    <h2>All Appointments</h2>
    <p>Total appointments: <?php echo $appointment_count; ?></p>

    <?php if ($appointment_count > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Patient Name</th>
                    <th>Date & Time</th>
                    <th>Status</th>
                    <th>Message</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
           <?php foreach ($appointments as $appt): ?>
    <tr>
        <td><?php echo htmlspecialchars($appt['patient_name']); ?></td>
        <td><?php echo date("D, M j, Y - H:i", strtotime($appt['datetime'])); ?></td>
        <td class="status-<?php echo strtolower($appt['status']); ?>">
            <?php echo htmlspecialchars($appt['status']); ?>
        </td>
        <td><?php echo htmlspecialchars($appt['message'] ?? ''); ?></td>
        <td>
            <a href="prescription.php?appointment_id=<?php echo $appt['id']; ?>">Prescription</a> |
            <a href="patient_profile.php?id=<?php echo $appt['patient_id']; ?>">View Profile</a> |
            
            <a href="view_patient_reports.php?patient_id=<?= $appt['patient_id'] ?>">View Reports</a> |

            <a href="send_message.php?patient_id=<?= $appt['patient_id']; ?>">Send Message</a> |

            <a href="chats.php?user_id=<?= $appt['patient_id'] ?>">Chat with Patient</a>






           
            <?php if ($appt['status'] !== 'attended'): ?>
                |
                <form method="POST" action="mark_attended.php" style="display:inline-block; margin-top: 5px;">
        <input type="hidden" name="appointment_id" value="<?php echo $appt['id']; ?>">
        <textarea name="doctor_notes" rows="2" cols="30" placeholder="Add notes for patient..." required></textarea><br>
        <button type="submit" onclick="return confirm('Mark this appointment as attended?');">Mark as Attended</button>
    </form>
            <?php endif; ?>
        </td>
    </tr>
<?php endforeach; ?>




            </tbody>
        </table>
    <?php else: ?>
        <div class="no-data">No appointments found.</div>
    <?php endif; ?>
</div>


<a class="back" href="home.html">&larr; Back to Home</a>

</body>
</html>
