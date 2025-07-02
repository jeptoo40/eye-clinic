<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: login_page.php');
    exit();
}

$patient_id = $_SESSION['user_id'];

try {
    $pdo = new PDO("mysql:host=localhost;dbname=eyecare", "root", "1234");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// For demo purposes: pick any doctor (replace with your actual logic for assigned doctor)
$stmt = $pdo->prepare("SELECT id FROM accounts WHERE role = 'doctor' LIMIT 1");
$stmt->execute();
$doctor_id = $stmt->fetchColumn();

// Handle sending a new message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message_text'])) {
    $stmt = $pdo->prepare("
        INSERT INTO doctor_messages (doctor_id, patient_id, message_text, sender_role, sent_at)
        VALUES (?, ?, ?, 'patient', NOW())
    ");
    $stmt->execute([$doctor_id, $patient_id, $_POST['message_text']]);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch chat messages
$stmt = $pdo->prepare("
    SELECT message_text, sender_role, sent_at
    FROM doctor_messages
    WHERE doctor_id = ? AND patient_id = ?
    ORDER BY sent_at ASC
");
$stmt->execute([$doctor_id, $patient_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Chat with Your Doctor</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f0f2f5;
      margin: 0;
      padding: 0;
    }
    .container {
      max-width: 700px;
      margin: 40px auto;
      background: #fff;
      padding: 25px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    h1 {
      text-align: center;
      color: #333;
    }
    .chat-box {
      max-height: 60vh;
      overflow-y: auto;
      margin-bottom: 20px;
    }
    .message {
      margin: 10px 0;
      padding: 10px 15px;
      border-radius: 15px;
      max-width: 70%;
      position: relative;
      clear: both;
    }
    .message.doctor {
      background: #cce5ff;
      float: right;
      text-align: right;
    }
    .message.patient {
      background: #d4edda;
      float: left;
      text-align: left;
    }
    .meta {
      font-size: 0.8em;
      color: #555;
      margin-top: 5px;
    }
    form textarea {
      width: 100%;
      height: 60px;
      resize: none;
      padding: 10px;
      font-size: 1em;
      border-radius: 5px;
      border: 1px solid #ccc;
    }
    form button {
      margin-top: 10px;
      padding: 10px 20px;
      background: #3498db;
      color: #fff;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }
    form button:hover {
      background: #2980b9;
    }
    .back {
      display: inline-block;
      margin-top: 20px;
      color: #3498db;
      text-decoration: none;
    }
  </style>
</head>
<body>

<div class="container">
  <h1>Chat with Your Doctor</h1>

  <div class="chat-box" id="chatBox">
    <?php if (empty($messages)): ?>
      <p>No messages yet.</p>
    <?php else: ?>
      <?php foreach ($messages as $msg): ?>
        <div class="message <?= $msg['sender_role'] === 'doctor' ? 'doctor' : 'patient' ?>">
          <?= nl2br(htmlspecialchars($msg['message_text'])) ?>
          <div class="meta">
            <strong><?= $msg['sender_role'] === 'doctor' ? 'Doctor' : 'You' ?></strong> –
            <?= date("D, M j, Y - H:i", strtotime($msg['sent_at'])) ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <form method="POST">
    <textarea name="message_text" placeholder="Type your reply..." required></textarea>
    <button type="submit">Send</button>
  </form>

  <a class="back" href="patient_dashboard.html">&larr; Back to Dashboard</a>
</div>

<<script>
    const chatBox = document.getElementById("chatBox");
    const scrollKey = "chatScroll_doctor_<?= $patient_id ?>";
    const doctorId = <?= json_encode($doctor_id) ?>;
    const patientId = <?= json_encode($patient_id) ?>;

    function fetchMessages() {
        fetch(`fetch_messages.php?doctor_id=${doctorId}&patient_id=${patientId}`)
            .then(response => response.json())
            .then(data => {
                chatBox.innerHTML = '';
                data.forEach(msg => {
                    const div = document.createElement('div');
                    div.className = 'message ' + (msg.sender_role === 'doctor' ? 'doctor' : 'patient');
                    div.innerHTML = `
                        <div class="sender-label">${msg.sender_role === 'doctor' ? 'Doctor' : 'You'}</div>
                        ${msg.message_text.replace(/\n/g, '<br>')}
                        <div class="timestamp">${new Date(msg.sent_at).toLocaleString()}</div>
                    `;
                    chatBox.appendChild(div);
                });
                chatBox.scrollTop = chatBox.scrollHeight;
            });
    }

    // Fetch messages initially and then every 5 seconds
    fetchMessages();
    setInterval(fetchMessages, 5000);

    // Save scroll position before unload
    window.addEventListener("beforeunload", () => {
        sessionStorage.setItem(scrollKey, chatBox.scrollTop);
    });
</script>

</body>
</html>
