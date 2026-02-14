<?php
include "assets/connection.php";

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* ==========================================
   CHECK SINGLE STATUS
========================================== */
$user = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT * FROM users WHERE id='$user_id'
"));
if($user['relationship_status'] != "Single"){
    die("Blind dating only for singles 💙");
}

/* ==========================================
   CHECK FOR EXISTING HIDDEN MATCH
========================================== */
$active_match = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT id FROM blind_matches
    WHERE (user1_id='$user_id' OR user2_id='$user_id')
      AND reveal_status='Hidden'
    LIMIT 1
"));

if ($active_match) {
    // User already has an active blind match – go to that chat
    header("Location: chat.php?match_id=" . $active_match['id']);
    exit();
}

/* ==========================================
   AGE FUNCTION
========================================== */
function getAge($dob){
    return date_diff(date_create($dob), date_create('today'))->y;
}

$myAge = getAge($user['dob']);

/* ==========================================
   MY ASTRO DETAILS
========================================== */
$myAstro = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT * FROM astro_details WHERE user_id='$user_id'
"));
if(!$myAstro){
    die("Your astrology data missing ❌");
}

/* ==========================================
   FIND CANDIDATES (with flexible age gap)
========================================== */
$genderWanted = ($user['gender']=="Male") ? "Female" : "Male";

function findCandidates($conn, $user_id, $genderWanted, $myAge, $minGap, $maxGap){
    $candidates = mysqli_query($conn,"
        SELECT * FROM users
        WHERE id!='$user_id'
        AND relationship_status='Single'
        AND gender='$genderWanted'
    ");

    $profiles = [];
    while($row = mysqli_fetch_assoc($candidates)){
        $age = getAge($row['dob']);
        $gap = abs($age - $myAge);
        if($gap < $minGap || $gap > $maxGap){
            continue;
        }

        // Skip if a previous match ended
        $blocked = mysqli_fetch_assoc(mysqli_query($conn,"
            SELECT * FROM blind_matches
            WHERE (
                (user1_id='$user_id' AND user2_id='{$row['id']}')
                OR (user1_id='{$row['id']}' AND user2_id='$user_id')
            ) AND reveal_status='Ended'
        "));
        if($blocked) continue;

        $astro = mysqli_fetch_assoc(mysqli_query($conn,"
            SELECT * FROM astro_details WHERE user_id='{$row['id']}'
        "));
        if(!$astro) continue;

        $profiles[] = [
            "user"  => $row,
            "astro" => $astro,
            "age"   => $age
        ];
    }
    return $profiles;
}

// First try strict age gap (5–7)
$matchProfiles = findCandidates($conn, $user_id, $genderWanted, $myAge, 5, 7);

// If none found, relax the gap (3–10)
if(empty($matchProfiles)){
    $matchProfiles = findCandidates($conn, $user_id, $genderWanted, $myAge, 3, 10);
}

// Still no candidates? Show "No Match Found"
if(empty($matchProfiles)){
    $noMatch = true;
} else {
    /* ==========================================
       BULK MATCH API CALL
    ========================================== */
    $bulkArray = [];
    foreach($matchProfiles as $p){
        $bulkArray[] = [
            "user_id"   => (int)$p['user']['id'],          // unique identifier
            "asc_deg"   => (float)$p['astro']['asc_degree'],
            "sun_deg"   => (float)$p['astro']['sun_degree'],
            "moon_deg"  => (float)$p['astro']['moon_degree'],
            "star_no"   => (int)$p['astro']['moon_nakshatra_no'],
            "gender"    => strtolower($p['user']['gender']),
            "rasi_no"   => (int)$p['astro']['moon_rasi_no']
        ];
    }

    $payload = [
        "native_asc_degrees"   => (float)$myAstro['asc_degree'],
        "native_sun_degrees"   => (float)$myAstro['sun_degree'],
        "native_moon_degrees"  => (float)$myAstro['moon_degree'],
        "native_star_no"       => (int)$myAstro['moon_nakshatra_no'],
        "native_gender"        => strtolower($user['gender']),
        "native_rasi_no"       => (int)$myAstro['moon_rasi_no'],
        "match_details"        => $bulkArray
    ];

    // cURL request
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL            => "http://127.0.0.1:5004/astrobulk", // verify port!
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => "POST",
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => ["Content-Type: application/json"],
        CURLOPT_TIMEOUT        => 10
    ]);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if($response && $httpCode == 200){
        $result = json_decode($response, true);

        if(isset($result['highest_guna_milan_score'], $result['best_match_profile'])){
            // Extract numeric score
            preg_match('/\d+/', $result['highest_guna_milan_score'], $m);
            $bestScore = $m[0] ?? 0;

            // Find the profile that matches the returned best_match_profile (by user_id)
            $bestProfile = null;
            if(isset($result['best_match_profile']['user_id'])){
                $bestUserId = $result['best_match_profile']['user_id'];
                foreach($matchProfiles as $p){
                    if($p['user']['id'] == $bestUserId){
                        $bestProfile = $p;
                        break;
                    }
                }
            }

            // Fallback if we couldn't identify the profile
            if(!$bestProfile){
                $bestProfile = $matchProfiles[array_rand($matchProfiles)];
                $bestScore = rand(18,30); // fallback score
            }
        } else {
            // API response missing expected fields
            $bestProfile = $matchProfiles[array_rand($matchProfiles)];
            $bestScore = rand(18,30);
        }
    } else {
        // API call failed
        $bestProfile = $matchProfiles[array_rand($matchProfiles)];
        $bestScore = rand(18,30);
    }

    /* ==========================================
       SAVE MATCH
    ========================================== */
    $uid2 = $bestProfile['user']['id'];
    mysqli_query($conn,"
        INSERT INTO blind_matches (user1_id, user2_id, astro_score, reveal_status)
        VALUES ('$user_id', '$uid2', '$bestScore', 'Hidden')
    ");
    $match_id = mysqli_insert_id($conn);

    // Redirect to chat with the new match (using match_id for consistency)
    header("Location: chat.php?match_id=$match_id");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Blind Match 💙</title>
    <style>
        body{ font-family:Segoe UI; background:linear-gradient(135deg,#89f7fe,#66a6ff); text-align:center; padding:60px; }
        .card{ background:white; padding:30px; border-radius:12px; display:inline-block; box-shadow:0 5px 20px rgba(0,0,0,0.2); }
        button{ padding:10px 20px; border:none; background:#007bff; color:white; border-radius:8px; cursor:pointer; }
    </style>
</head>
<body>
    <div class="card">
        <h2>No Match Found 😔</h2>
        <p>We couldn’t find a compatible partner right now.</p>
        <a href="blind.php"><button>Try Again 🔄</button></a>
    </div>
</body>
</html>