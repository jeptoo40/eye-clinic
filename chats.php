<?php
session_start();

// Check if doctor is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: login_page.php');
    exit();
}

$doctor_id = $_SESSION['user_id'];
$patient_id = $_GET['user_id'] ?? null;

if (!$patient_id) {
    die("No patient selected.");
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=eyecare", "root", "1234");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle sending message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty(trim($_POST['message_text']))) {
    $stmt = $pdo->prepare("
        INSERT INTO doctor_messages (doctor_id, patient_id, message_text, sender_role, sent_at)
        VALUES (?, ?, ?, 'doctor', NOW())
    ");
    $stmt->execute([$doctor_id, $patient_id, $_POST['message_text']]);
    header("Location: chats.php?user_id=" . $patient_id);
    exit();
}

// Fetch conversation between this doctor and patient
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
    <title>Chat with Patient</title>
    <style>
        body { font-family: Arial, sans-serif; background: #eef2f5; margin: 0; padding: 0; }
        .chat-container {
            max-width: 700px;
            margin: 40px auto;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            height: 80vh;
        }
        .chat-header {
            background: #3498db;
            color: #fff;
            padding: 15px;
            font-size: 1.2em;
        }
        .chat-box {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #f9fbfd;
        }
        .message {
            margin-bottom: 15px;
            padding: 10px 15px;
            border-radius: 12px;
            max-width: 70%;
            position: relative;
            clear: both;
        }
        .doctor {
            background-color: #dff0ff;
            float: left;
        }
        .patient {
            background-color: #d1f5d3;
            float: right;
            text-align: right;
        }
        .timestamp {
            font-size: 0.8em;
            color: #888;
            margin-top: 5px;
        }
        .sender-label {
            font-size: 0.8em;
            font-weight: bold;
            margin-bottom: 3px;
        }
        .chat-form {
            border-top: 1px solid #ccc;
            padding: 15px;
            background: #fff;
        }
        .chat-form textarea {
            width: 100%;
            height: 60px;
            padding: 10px;
            resize: none;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-family: inherit;
        }
        .chat-form button {
            margin-top: 10px;
            padding: 10px 20px;
            background: #3498db;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .chat-form button:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>

<div class="chat-container">
    <div class="chat-header">Chat with Patient #<?= htmlspecialchars($patient_id) ?></div>
    <div class="chat-box" id="chatBox">
        <?php foreach ($messages as $msg): ?>
            <div class="message <?= $msg['sender_role'] === 'doctor' ? 'doctor' : 'patient' ?>">
                <div class="sender-label">
                    <?= $msg['sender_role'] === 'doctor' ? 'Doctor' : 'Patient' ?>
                </div>
                <?= nl2br(htmlspecialchars($msg['message_text'])) ?>
                <div class="timestamp">
                    <?= date("M j, Y H:i", strtotime($msg['sent_at'])) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <form class="chat-form" method="POST">
        <textarea name="message_text" placeholder="Type your message here..." required></textarea>
        <button type="submit">Send</button>
    </form>
    <a class="back" href="doctor_dashboard.php">&larr; Back to Dashboard</a>  |

<a class="back" href="home.html">&larr; Back to Home</a>

</div>



    <script>
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
