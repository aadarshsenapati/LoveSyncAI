<?php
include "assets/connection.php";

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Accept match ID from either 'match' or 'match_id' parameter
$match_id = 0;
if (isset($_GET['match_id']) && is_numeric($_GET['match_id'])) {
    $match_id = (int)$_GET['match_id'];
} elseif (isset($_GET['match']) && is_numeric($_GET['match'])) {
    $match_id = (int)$_GET['match'];
}

if ($match_id <= 0) {
    die("Invalid match.");
}

/* ==========================================
   AJAX POLLING: RETURN NEW MESSAGES AS JSON
========================================== */
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
    
    // Fetch new messages (ID > last_id)
    $new_query = mysqli_query($conn, "
        SELECT * FROM messages
        WHERE match_id = '$match_id'
          AND id > '$last_id'
        ORDER BY created_at ASC
    ");
    
    $messages = [];
    while ($msg = mysqli_fetch_assoc($new_query)) {
        // Determine sender name based on reveal status
        $is_mine = ($msg['sender_id'] == $user_id);
        // We need match data to know if hidden – fetch match again (or cache in session? simple approach: query match)
        $match_info = mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT reveal_status FROM blind_matches WHERE id = '$match_id'
        "));
        $is_hidden = ($match_info['reveal_status'] == 'Hidden');
        
        // Get other user's name if revealed
        $other_id = 0;
        if (!$is_hidden && !$is_mine) {
            $match_row = mysqli_fetch_assoc(mysqli_query($conn, "
                SELECT user1_id, user2_id FROM blind_matches WHERE id = '$match_id'
            "));
            $other_id = ($match_row['user1_id'] == $user_id) ? $match_row['user2_id'] : $match_row['user1_id'];
            $other_user = mysqli_fetch_assoc(mysqli_query($conn, "
                SELECT full_name FROM users WHERE id = '$other_id'
            "));
        }
        
        $sender_name = $is_mine ? 'You' : ($is_hidden ? 'Match' : ($other_user['full_name'] ?? 'Match'));
        
        $messages[] = [
            'id' => $msg['id'],
            'message' => htmlspecialchars($msg['message']),
            'time' => date('H:i', strtotime($msg['created_at'])),
            'sender_name' => $sender_name,
            'is_mine' => $is_mine
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode(['messages' => $messages]);
    exit();
}

/* ==========================================
   VERIFY MATCH BELONGS TO USER (NON-AJAX)
========================================== */
$match_query = mysqli_query($conn, "
    SELECT * FROM blind_matches
    WHERE id = '$match_id'
      AND (user1_id = '$user_id' OR user2_id = '$user_id')
");
$match = mysqli_fetch_assoc($match_query);

if (!$match) {
    die("Match not found or you don't have access.");
}

// Determine the other user's ID
$other_id = ($match['user1_id'] == $user_id) ? $match['user2_id'] : $match['user1_id'];

// Fetch other user's basic info (name, profile pic, etc.)
$other_user_query = mysqli_query($conn, "
    SELECT id, full_name, gender FROM users WHERE id = '$other_id'
");
$other_user = mysqli_fetch_assoc($other_user_query);

$is_hidden = ($match['reveal_status'] == 'Hidden');

/* ==========================================
   HANDLE SENDING A NEW MESSAGE
========================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    if (!empty($message)) {
        $message = mysqli_real_escape_string($conn, $message);
        mysqli_query($conn, "
            INSERT INTO messages (match_id, sender_id, message, created_at)
            VALUES ('$match_id', '$user_id', '$message', NOW())
        ");
    }
    // Redirect to avoid form resubmission
    header("Location: chat.php?match_id=$match_id");
    exit();
}

/* ==========================================
   FETCH ALL MESSAGES FOR INITIAL DISPLAY
========================================== */
$messages_query = mysqli_query($conn, "
    SELECT * FROM messages
    WHERE match_id = '$match_id'
    ORDER BY created_at ASC
");

// Get the last message ID for polling
$last_msg_id = 0;
$last_msg_row = mysqli_query($conn, "
    SELECT id FROM messages
    WHERE match_id = '$match_id'
    ORDER BY id DESC LIMIT 1
");
if ($last_msg_row && mysqli_num_rows($last_msg_row) > 0) {
    $last_msg = mysqli_fetch_assoc($last_msg_row);
    $last_msg_id = $last_msg['id'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LoveSync · Blind Chat</title>
    <!-- Google Font & Font Awesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(145deg, #fff3f8 0%, #f1e4ff 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1.5rem;
            position: relative;
        }

        /* background bubbles */
        .bg-bubble {
            position: fixed;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle at 30% 30%, rgba(255, 175, 200, 0.4), rgba(205, 150, 255, 0.2));
            border-radius: 50%;
            top: -50px;
            left: -50px;
            z-index: 0;
            filter: blur(50px);
        }

        .bg-bubble2 {
            bottom: -50px;
            right: -30px;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle at 70% 70%, #ffe0f0, #e8caff);
            position: fixed;
            border-radius: 50%;
            filter: blur(70px);
            z-index: 0;
        }

        /* main container */
        .wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 800px;
            margin: 1rem auto;
        }

        /* navbar */
        .navbar {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255,255,255,0.8);
            border-radius: 3rem;
            padding: 0.8rem 2rem;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px -10px rgba(140, 80, 130, 0.2);
        }

        .nav-logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 700;
            font-size: 1.6rem;
            background: linear-gradient(135deg, #e3648c, #a07bda);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-links {
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem 1.5rem;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: #4f3a5a;
            font-weight: 500;
            transition: 0.2s;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .nav-links a:hover {
            color: #d4588a;
        }

        .nav-links .btn-link {
            background: rgba(255, 255, 255, 0.8);
            border-radius: 2rem;
            padding: 0.4rem 1.2rem;
            border: 1px solid #e2bcdb;
        }

        .nav-links .btn-link:hover {
            background: white;
        }

        /* glass card for chat container */
        .chat-glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: 2.5rem;
            box-shadow: 0 30px 60px -20px rgba(70, 20, 60, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.5) inset;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 70vh;
            max-height: 700px;
        }

        .chat-header {
            padding: 1.5rem 2rem;
            background: rgba(230, 120, 160, 0.15);
            border-bottom: 1px solid rgba(255, 255, 255, 0.6);
            text-align: center;
        }

        .chat-header h2 {
            font-weight: 600;
            font-size: 1.6rem;
            color: #32243d;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .chat-header h2 i {
            color: #e27c9f;
        }

        .chat-header small {
            display: block;
            color: #6f4f7a;
            margin-top: 0.3rem;
            font-size: 0.9rem;
        }

        .reveal-banner {
            background: #fae1ed;
            color: #a04868;
            padding: 0.8rem;
            text-align: center;
            font-size: 0.95rem;
            border-bottom: 1px solid #f3b6d0;
        }

        .reveal-banner a {
            color: #a04868;
            font-weight: 700;
            text-decoration: underline;
        }

        .messages-area {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            background: rgba(255, 245, 250, 0.3);
        }

        .message {
            display: flex;
            flex-direction: column;
            max-width: 75%;
        }

        .message.sent {
            align-self: flex-end;
            align-items: flex-end;
        }

        .message.received {
            align-self: flex-start;
            align-items: flex-start;
        }

        .message .bubble {
            padding: 0.9rem 1.4rem;
            border-radius: 2rem;
            line-height: 1.5;
            word-wrap: break-word;
            font-size: 0.95rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.03);
        }

        .sent .bubble {
            background: linear-gradient(145deg, #ff8aad, #c472e0);
            color: white;
            border-bottom-right-radius: 0.5rem;
        }

        .received .bubble {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(4px);
            color: #2d1f38;
            border-bottom-left-radius: 0.5rem;
            border: 1px solid rgba(255, 255, 255, 0.6);
        }

        .message .meta {
            font-size: 0.7rem;
            color: #8e6a93;
            margin-top: 0.3rem;
            padding: 0 0.8rem;
        }

        .chat-input-form {
            display: flex;
            padding: 1.2rem 1.5rem;
            background: rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(8px);
            border-top: 1px solid rgba(255, 255, 255, 0.7);
            gap: 0.8rem;
        }

        .chat-input-form input {
            flex: 1;
            padding: 1rem 1.5rem;
            border: 1.5px solid rgba(220, 180, 210, 0.4);
            border-radius: 3rem;
            font-size: 1rem;
            background: rgba(255, 250, 250, 0.8);
            transition: all 0.2s;
            font-family: 'Inter', sans-serif;
        }

        .chat-input-form input:focus {
            outline: none;
            border-color: #e27c9f;
            background: white;
            box-shadow: 0 0 0 4px rgba(230, 120, 160, 0.15);
        }

        .chat-input-form button {
            padding: 1rem 2rem;
            border: none;
            border-radius: 3rem;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            background: linear-gradient(145deg, #ff8aad, #c472e0);
            color: white;
            box-shadow: 0 10px 20px -8px rgba(220, 100, 150, 0.5);
        }

        .chat-input-form button:hover {
            transform: scale(1.02);
            box-shadow: 0 16px 24px -8px #cb7bb0;
        }

        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }

        .back-link a {
            color: #6f4f7a;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.5);
            padding: 0.6rem 1.8rem;
            border-radius: 3rem;
            backdrop-filter: blur(4px);
            transition: 0.2s;
        }

        .back-link a:hover {
            background: rgba(255, 255, 255, 0.8);
            color: #d4588a;
        }

        @media (max-width: 600px) {
            .chat-glass {
                height: 80vh;
            }
            .chat-header h2 {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
    <div class="bg-bubble"></div>
    <div class="bg-bubble2"></div>

    <div class="wrapper">
        <!-- navbar -->
        <nav class="navbar">
            <div class="nav-logo">
                <i class="fas fa-heart-circle-check"></i>
                <span>LoveSync</span>
            </div>
            <div class="nav-links">
                <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="profile.php"><i class="far fa-user"></i> Profile</a>
                <a href="wish.php"><i class="fas fa-envelope"></i> Wish</a>
                <a href="astro.php"><i class="fas fa-sparkles"></i> Astro</a>
                <a href="whatsapp.php"><i class="fab fa-whatsapp"></i> WhatsApp</a>
                <a href="vault.php"><i class="fas fa-cloud"></i> Vault</a>
                <a href="logout.php" class="btn-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </nav>

        <!-- chat container -->
        <div class="chat-glass">
            <div class="chat-header">
                <h2>
                    <i class="fas fa-comments"></i>
                    <?php if ($is_hidden): ?>
                        🔒 Anonymous Chat
                    <?php else: ?>
                        Chat with <?php echo htmlspecialchars($other_user['full_name']); ?>
                    <?php endif; ?>
                </h2>
                <small><?php echo $is_hidden ? 'You are chatting with a secret match' : 'Match revealed'; ?></small>
            </div>

            <?php if ($is_hidden): ?>
                <div class="reveal-banner">
                    <i class="fas fa-eye-slash"></i> Identities are hidden. 
                    <a href="reveal.php?match_id=<?php echo $match_id; ?>">Click here to reveal yourself</a>
                </div>
            <?php endif; ?>

            <div class="messages-area" id="messageArea">
                <?php 
                while ($msg = mysqli_fetch_assoc($messages_query)): 
                    $is_mine = ($msg['sender_id'] == $user_id);
                    $sender_name = '';
                    if ($is_mine) {
                        $sender_name = 'You';
                    } else {
                        $sender_name = $is_hidden ? 'Match' : htmlspecialchars($other_user['full_name']);
                    }
                    $time = date('H:i', strtotime($msg['created_at']));
                ?>
                    <div class="message <?php echo $is_mine ? 'sent' : 'received'; ?>" data-message-id="<?php echo $msg['id']; ?>">
                        <div class="bubble">
                            <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                        </div>
                        <div class="meta">
                            <?php echo $time; ?> · <?php echo $sender_name; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <form method="POST" class="chat-input-form" autocomplete="off" id="chatForm">
                <input type="text" name="message" placeholder="Type your message..." required id="messageInput">
                <button type="submit"><i class="fas fa-paper-plane"></i> Send</button>
            </form>
        </div>

        <div class="back-link">
            <a href="dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </div>

    <!-- Hidden data for polling -->
    <input type="hidden" id="matchId" value="<?php echo $match_id; ?>">
    <input type="hidden" id="lastMessageId" value="<?php echo $last_msg_id; ?>">

    <script>
        const messageArea = document.getElementById('messageArea');
        const matchId = document.getElementById('matchId').value;
        let lastMessageId = parseInt(document.getElementById('lastMessageId').value) || 0;

        // Auto-scroll to bottom on page load
        messageArea.scrollTop = messageArea.scrollHeight;

        // Function to append a new message to the chat
        function appendMessage(msg) {
            const div = document.createElement('div');
            div.className = `message ${msg.is_mine ? 'sent' : 'received'}`;
            div.setAttribute('data-message-id', msg.id);
            div.innerHTML = `
                <div class="bubble">${msg.message.replace(/\n/g, '<br>')}</div>
                <div class="meta">${msg.time} · ${msg.sender_name}</div>
            `;
            messageArea.appendChild(div);
        }

        // Poll for new messages every 3 seconds
        function pollMessages() {
            fetch(`chat.php?ajax=1&match_id=${matchId}&last_id=${lastMessageId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.messages && data.messages.length > 0) {
                        data.messages.forEach(msg => {
                            appendMessage(msg);
                            if (msg.id > lastMessageId) {
                                lastMessageId = msg.id;
                            }
                        });
                        // Scroll to bottom if user is near bottom
                        const isNearBottom = messageArea.scrollHeight - messageArea.scrollTop - messageArea.clientHeight < 100;
                        if (isNearBottom) {
                            messageArea.scrollTop = messageArea.scrollHeight;
                        }
                    }
                })
                .catch(err => console.error('Polling error:', err));
        }

        // Start polling every 3 seconds
        setInterval(pollMessages, 3000);

        // Optional: stop polling when form is submitted (will be redirected)
        document.getElementById('chatForm').addEventListener('submit', function() {
            // No need to stop, page will reload after POST
        });
    </script>
</body>
</html>