<?php

// Get the visitor ID from cookies and validate it with the users table
function getUserInfo() {
    global $pdo;

    // Check if visitor_id exists in the cookies
    if (!isset($_COOKIE['visitor_id'])) {
        return false; // Return false if the cookie is not set
    }

    $visitor_id = $_COOKIE['visitor_id'];

    try {
        // Prepare query to get user info from the users table
        $stmtUser = $pdo->prepare("SELECT name FROM users WHERE visitor_id = :visitor_id LIMIT 1");
        $stmtUser->execute(['visitor_id' => $visitor_id]);

        // Check if user info is found
        if ($stmtUser->rowCount() > 0) {
            $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
            return ['userName' => $user['name'], 'visitor_id' => $visitor_id];
        } else {
            return ['userName' => 'User not found', 'visitor_id' => null];
        }
    } catch (PDOException $e) {
        error_log('Error fetching user info: ' . $e->getMessage());
        return false;
    }
}

function getUserProfile($visitor_id) {
    global $pdo;

    if (!$visitor_id) return [];

    try {
        $stmtUser = $pdo->prepare("SELECT name, faculty, semester, custom_fields FROM users WHERE visitor_id = :visitor_id LIMIT 1");
        $stmtUser->execute(['visitor_id' => $visitor_id]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return [];
        }

        $custom = json_decode($user['custom_fields'] ?? '{}', true);
        if (!is_array($custom)) {
            $custom = [];
        }

        return array_merge([
            'name' => $user['name'] ?? '',
            'faculty' => $user['faculty'] ?? '',
            'semester' => $user['semester'] ?? '',
        ], $custom);
    } catch (PDOException $e) {
        error_log('Error fetching user profile: ' . $e->getMessage());
        return [];
    }
}

// Get visit data based on the visitor ID
function getVisits($visitor_id) {
    global $pdo;

    if (!$visitor_id) return [];

    try {
        // Prepare query to get the latest visit of today from the visits table
        $stmtVisits = $pdo->prepare("
            SELECT location_id, faculty, semester, custom_fields, location_name, visit_count, check_in 
            FROM visits 
            WHERE visitor_id = :visitor_id 
              AND DATE(check_in) = CURDATE() 
            ORDER BY check_in DESC 
            LIMIT 1
        ");
        $stmtVisits->execute(['visitor_id' => $visitor_id]);

        // Fetch the latest visit data for today
        return $stmtVisits->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        error_log('Error fetching visits: ' . $e->getMessage());
        return [];
    }
}


function getUserDetail($visitor_id) {
    global $pdo;

    if (!$visitor_id) return [];

    try {
        // Prepare query to get the latest visit regardless of the date
        $stmtVisits = $pdo->prepare("
            SELECT location_id, faculty, semester, custom_fields, location_name, visit_count, check_in 
            FROM visits 
            WHERE visitor_id = :visitor_id 
            ORDER BY check_in DESC 
            LIMIT 1
        ");
        $stmtVisits->execute(['visitor_id' => $visitor_id]);

        // Fetch the latest visit data
        return $stmtVisits->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        error_log('Error fetching user details: ' . $e->getMessage());
        return [];
    }
}


function checkLocation($visitor_id, $location_id) {
    global $pdo;

    try {

        // Correct timezone for GMT+8
        $timezone = new DateTimeZone('Asia/Kuala_Lumpur'); // or 'Asia/Singapore'

        $today = new DateTime('now', $timezone);
        $todayDate = $today->format('Y-m-d');

        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM visits 
            WHERE visitor_id = :visitor_id 
              AND location_id = :location_id
              AND DATE(check_in) = :today
        ");
        $stmt->execute([
            ':visitor_id' => $visitor_id,
            ':location_id' => $location_id,
            ':today' => $todayDate
        ]);

        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log('Error checking location: ' . $e->getMessage());
        return false;
    }
}


function isCheckedInToday($visitor_id) {
    global $pdo;

    // Get the current date in GMT+8 (Malaysia time)
    $currentDate = (new DateTime('now', new DateTimeZone('Asia/Kuala_Lumpur')))->format('Y-m-d');

    // Check if there's any check-in record for today
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM visits 
        WHERE visitor_id = :visitor_id 
          AND DATE(check_in) = :today
    ");
    $stmt->execute([':visitor_id' => $visitor_id, ':today' => $currentDate]);

    return $stmt->fetchColumn() > 0;
}



?>
