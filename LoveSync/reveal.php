<?php
include "assets/connection.php";

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get match ID
$match_id = isset($_GET['match_id']) ? (int)$_GET['match_id'] : 0;
if (!$match_id) {
    die("Invalid match.");
}

// Fetch match and verify user is part of it
$match = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT * FROM blind_matches
    WHERE id = '$match_id'
      AND (user1_id = '$user_id' OR user2_id = '$user_id')
"));
if (!$match) {
    die("Match not found or you don't have access.");
}

// Determine which user (1 or 2) the current user is
$user_number = ($match['user1_id'] == $user_id) ? 1 : 2;
$other_user_id = ($user_number == 1) ? $match['user2_id'] : $match['user1_id'];

// Fetch other user's basic info
$other_user = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT full_name, email FROM users WHERE id = '$other_user_id'
"));

// Define columns based on user number
$revealed_col = "user{$user_number}_revealed";
$image_col = "user{$user_number}_reveal_image";
$decision_col = "user{$user_number}_decision";

$other_revealed_col = "user" . ($user_number == 1 ? 2 : 1) . "_revealed";
$other_image_col = "user" . ($user_number == 1 ? 2 : 1) . "_reveal_image";
$other_decision_col = "user" . ($user_number == 1 ? 2 : 1) . "_decision";

/* ==========================================
   HANDLE IMAGE UPLOAD (REVEAL)
========================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['reveal_image']) && $match[$revealed_col] == 0) {
    $target_dir = "uploads/reveals/";
    // Create directory if it doesn't exist
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_extension = strtolower(pathinfo($_FILES['reveal_image']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];

    if (!in_array($file_extension, $allowed)) {
        $error = "Only JPG, PNG, and GIF images are allowed.";
    } elseif ($_FILES['reveal_image']['size'] > 5 * 1024 * 1024) {
        $error = "Image must be less than 5MB.";
    } else {
        $new_filename = "reveal_{$match_id}_user{$user_number}_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_filename;

        if (move_uploaded_file($_FILES['reveal_image']['tmp_name'], $target_file)) {
            // Update database
            mysqli_query($conn, "
                UPDATE blind_matches
                SET $revealed_col = 1,
                    $image_col = '$target_file'
                WHERE id = '$match_id'
            ");

            // Refresh match data
            $match = mysqli_fetch_assoc(mysqli_query($conn, "
                SELECT * FROM blind_matches WHERE id = '$match_id'
            "));

            // If both have now revealed, update reveal_status to 'Revealed'
            if ($match['user1_revealed'] == 1 && $match['user2_revealed'] == 1) {
                mysqli_query($conn, "
                    UPDATE blind_matches
                    SET reveal_status = 'Revealed'
                    WHERE id = '$match_id'
                ");
                $match['reveal_status'] = 'Revealed';
            }
        } else {
            $error = "Sorry, there was an error uploading your file.";
        }
    }
}

/* ==========================================
   HANDLE ACCEPT / REJECT
========================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['decision'])) {
    $decision = mysqli_real_escape_string($conn, $_POST['decision']); // 'accepted' or 'rejected'

    // Update the current user's decision
    mysqli_query($conn, "
        UPDATE blind_matches
        SET $decision_col = '$decision'
        WHERE id = '$match_id'
    ");

    // End the match (both users will be redirected to chat with 'Ended' status)
    mysqli_query($conn, "
        UPDATE blind_matches
        SET reveal_status = 'Ended'
        WHERE id = '$match_id'
    ");

    // Redirect to chat (which will show appropriate message)
    header("Location: chat.php?match_id=$match_id");
    exit();
}

// Refresh match data after potential updates
$match = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT * FROM blind_matches WHERE id = '$match_id'
"));

// Determine if both have revealed
$both_revealed = ($match['user1_revealed'] == 1 && $match['user2_revealed'] == 1);
$current_revealed = $match[$revealed_col];
$other_revealed = $match[$other_revealed_col];
$current_decision = $match[$decision_col];
$other_decision = $match[$other_decision_col];
$status = $match['reveal_status'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LoveSync · Reveal Match</title>
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
            max-width: 600px;
            margin: 1rem auto;
        }

        /* navbar (same as other pages) */
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

        /* glass card */
        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: 2.5rem;
            box-shadow: 0 30px 60px -20px rgba(70, 20, 60, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.5) inset;
            padding: 2rem 2.2rem;
            width: 100%;
        }

        h1 {
            font-weight: 700;
            font-size: 2rem;
            color: #32243d;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin-bottom: 1.5rem;
        }

        h2, h3 {
            font-weight: 600;
            color: #32243d;
            margin-bottom: 1rem;
        }

        .status-badge {
            display: inline-block;
            padding: 0.4rem 1.2rem;
            border-radius: 3rem;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }

        .status-hidden { background: #ffe0b0; color: #8a5f3a; }
        .status-revealed { background: #c8e6c9; color: #256f3a; }
        .status-ended { background: #e0e0e0; color: #4a4a4a; }

        .info-box {
            background: rgba(255, 255, 255, 0.5);
            border-radius: 2rem;
            padding: 1.5rem;
            margin: 1.5rem 0;
        }

        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #eac0db;
            margin: 1rem auto;
            display: block;
        }

        .email {
            font-size: 1rem;
            color: #5a3f63;
            margin: 1rem 0;
            word-break: break-all;
        }

        .btn {
            padding: 0.9rem 2rem;
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
            text-decoration: none;
            margin: 0.3rem;
        }

        .btn:hover {
            transform: scale(1.02);
            box-shadow: 0 16px 24px -8px #cb7bb0;
        }

        .btn-success {
            background: linear-gradient(145deg, #6fcf97, #27ae60);
        }

        .btn-danger {
            background: linear-gradient(145deg, #f28b82, #d32f2f);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.8);
            color: #5a3f63;
            border: 1px solid #e5c1d4;
            box-shadow: none;
        }

        .btn-secondary:hover {
            background: white;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .upload-form input[type="file"] {
            width: 100%;
            padding: 0.8rem 1.2rem;
            border: 1.5px solid rgba(220, 180, 210, 0.4);
            border-radius: 3rem;
            background: rgba(255, 255, 255, 0.8);
            margin: 1rem 0;
            font-family: 'Inter', sans-serif;
        }

        .alert {
            background: #ffebee;
            color: #b33441;
            padding: 0.8rem 1.5rem;
            border-radius: 3rem;
            margin: 1rem 0;
            font-size: 0.9rem;
            border: 1px solid #f5c2c2;
        }

        .decision-buttons {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        hr {
            border: none;
            height: 1px;
            background: linear-gradient(to right, transparent, #deb0d0, transparent);
            margin: 2rem 0;
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
                <a href="chat.php?match_id=<?php echo $match_id; ?>" style="color:#d4588a;"><i class="fas fa-comments"></i> Chat</a>
                <a href="logout.php" class="btn-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </nav>

        <!-- main card -->
        <div class="glass-card">
            <h1><i class="fas fa-eye"></i> Reveal Identity</h1>

            <div class="status-badge status-<?php echo strtolower($status); ?>">
                <i class="fas fa-info-circle"></i> Status: <?php echo $status; ?>
            </div>

            <?php if ($status == 'Ended'): ?>
                <div class="info-box">
                    <p style="margin-bottom: 1.5rem;">This match has ended. You cannot reveal or change anything.</p>
                    <a href="chat.php?match_id=<?php echo $match_id; ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Chat</a>
                </div>

            <?php elseif ($both_revealed): ?>
                <!-- Both revealed: show details and accept/reject -->
                <div class="info-box">
                    <h3>Your Match: <?php echo htmlspecialchars($other_user['full_name']); ?></h3>
                    <?php if ($match[$other_image_col]): ?>
                        <img src="<?php echo htmlspecialchars($match[$other_image_col]); ?>" alt="Profile" class="profile-image">
                    <?php endif; ?>
                    <div class="email"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($other_user['email']); ?></div>

                    <?php if ($current_decision == 'pending'): ?>
                        <p style="margin-top: 1rem;">Do you want to accept this match?</p>
                        <div class="decision-buttons">
                            <form method="POST" style="display: inline;">
                                <button type="submit" name="decision" value="accepted" class="btn btn-success"><i class="fas fa-check"></i> Accept</button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <button type="submit" name="decision" value="rejected" class="btn btn-danger"><i class="fas fa-times"></i> Reject</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <p>You have <?php echo $current_decision; ?> this match.</p>
                        <?php if ($other_decision != 'pending'): ?>
                            <p>The other user has <?php echo $other_decision; ?> the match.</p>
                        <?php endif; ?>
                        <div style="text-align: center; margin-top: 1.5rem;">
                            <a href="chat.php?match_id=<?php echo $match_id; ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Chat</a>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif ($current_revealed): ?>
                <!-- User has revealed, waiting for other -->
                <div class="info-box">
                    <p><i class="fas fa-check-circle" style="color:#27ae60;"></i> You have revealed your identity. Waiting for the other person to reveal...</p>
                    <?php if ($match[$image_col]): ?>
                        <p style="margin-top: 1rem;">Your uploaded image:</p>
                        <img src="<?php echo htmlspecialchars($match[$image_col]); ?>" alt="Your reveal" class="profile-image" style="width:120px; height:120px;">
                    <?php endif; ?>
                    <div style="text-align: center; margin-top: 1.5rem;">
                        <a href="chat.php?match_id=<?php echo $match_id; ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Chat</a>
                    </div>
                </div>

            <?php else: ?>
                <!-- User has not revealed yet -->
                <div class="info-box">
                    <p>To reveal yourself, please upload a photo (this will be shown to your match after they also reveal).</p>
                    <?php if (isset($error)): ?>
                        <div class="alert"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div>
                    <?php endif; ?>
                    <form method="POST" enctype="multipart/form-data" class="upload-form">
                        <input type="file" name="reveal_image" accept="image/*" required>
                        <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 1.5rem;">
                            <button type="submit" class="btn"><i class="fas fa-upload"></i> Upload & Reveal</button>
                            <a href="chat.php?match_id=<?php echo $match_id; ?>" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <hr>
            <div style="text-align: center;">
                <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-home"></i> Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>