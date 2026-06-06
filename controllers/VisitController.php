<?php
// VisitController.php
function getUserIdByVisitorId($visitor_id) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT user_id FROM visits WHERE visitor_id = :visitor_id LIMIT 1");
    $stmt->execute([':visitor_id' => $visitor_id]);

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user['user_id'];
    }

    return null;  // Return null if no matching user found
}

function generateCardToken() {
    return bin2hex(random_bytes(32));
}

function getCardTokenByVisitorId($visitor_id) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT user_id, card_token FROM users WHERE visitor_id = :visitor_id LIMIT 1");
    $stmt->execute([':visitor_id' => $visitor_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        return null;
    }

    if (!empty($user['card_token'])) {
        return $user['card_token'];
    }

    $cardToken = generateCardToken();
    $stmt = $pdo->prepare("UPDATE users SET card_token = :card_token WHERE user_id = :user_id");
    $stmt->execute([
        ':card_token' => $cardToken,
        ':user_id' => $user['user_id'],
    ]);

    return $cardToken;
}

function getVisitorIdByCardToken($card_token) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT visitor_id FROM users WHERE card_token = :card_token LIMIT 1");
    $stmt->execute([':card_token' => $card_token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    return $user['visitor_id'] ?? null;
}

function getVisitorIdByUserId($user_id) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT visitor_id FROM visits WHERE user_id = :user_id LIMIT 1");
    $stmt->execute([':user_id' => $user_id]);

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user['visitor_id'];
    }

    return null;  // Return null if no matching user found
}

function checkInVisitor($visitor_id, $name, $location_id, $faculty, $semester, $custom_fields = []) {
    global $pdo;
    
    // Check if the visitor has already checked in today at the same location
    if (checkDuplicateCheckIn($visitor_id, $location_id)) {
        return true; // Return true if already checked in to avoid duplicate entries
    }

    try {
        // Start a transaction to ensure both inserts succeed or fail together
        $pdo->beginTransaction();

        // Check if the visitor_id already exists in the users table
        $stmtCheckUser = $pdo->prepare("SELECT user_id FROM users WHERE visitor_id = :visitor_id LIMIT 1");
        $stmtCheckUser->execute(['visitor_id' => $visitor_id]);

        $customJson = json_encode($custom_fields, JSON_UNESCAPED_UNICODE);

        // Check if the user exists
        if ($stmtCheckUser->rowCount() === 0) {
            // Insert into users table if visitor_id does not exist
            $stmt1 = $pdo->prepare("INSERT INTO users (visitor_id, name, faculty, semester, custom_fields, card_token, created_at)  
                                    VALUES (:visitor_id, :name, :faculty, :semester, :custom_fields, :card_token, :created_at)");
            $stmt1->execute([
                'visitor_id' => $visitor_id,
                'name' => $name,
                'faculty' => $faculty,
                'semester' => $semester,
                'custom_fields' => $customJson,
                'card_token' => generateCardToken(),
                'created_at' => (new DateTime('now', new DateTimeZone('Asia/Kuala_Lumpur')))->format('Y-m-d H:i:s'),
            ]);

            // Get the last inserted user ID
            $user_id = $pdo->lastInsertId();
        } else {
            // Get the existing user_id if visitor_id already exists
            $user = $stmtCheckUser->fetch(PDO::FETCH_ASSOC);
            $user_id = $user['user_id'];

            $stmtUpdateUser = $pdo->prepare("UPDATE users SET name = :name, faculty = :faculty, semester = :semester, custom_fields = :custom_fields WHERE user_id = :user_id");
            $stmtUpdateUser->execute([
                'name' => $name,
                'faculty' => $faculty,
                'semester' => $semester,
                'custom_fields' => $customJson,
                'user_id' => $user_id,
            ]);
        }

        // Fetch the location name from the locations table using location_id
        $stmtLocation = $pdo->prepare("SELECT name FROM locations WHERE location_id = :location_id");
        $stmtLocation->execute(['location_id' => $location_id]);

        // Check if the location was found
        if ($stmtLocation->rowCount() > 0) {
            $location = $stmtLocation->fetch(PDO::FETCH_ASSOC);
            $location_name = $location['name']; // Get location name
        } else {
            throw new Exception("Location not found.");
        }

        // Insert into visits table
        $stmt2 = $pdo->prepare("INSERT INTO visits (visitor_id, user_id, check_in, location_id, faculty, semester, custom_fields, status, location_name, visit_count)  
                                VALUES (:visitor_id, :user_id, :check_in, :location_id, :faculty, :semester, :custom_fields, :status, :location_name, 1)");
        $stmt2->execute([
            'visitor_id' => $visitor_id,
            'user_id' => $user_id,
            'check_in' => (new DateTime('now', new DateTimeZone('Asia/Kuala_Lumpur')))->format('Y-m-d H:i:s'), // GMT+8
            'location_id' => $location_id,
            'faculty' => $faculty,
            'semester' => $semester,
            'custom_fields' => $customJson,
            'status' => 'checked-in',
            'location_name' => $location_name,
            
        ]);

        // Commit the transaction
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        // Roll back the transaction if something goes wrong
        $pdo->rollBack();
        // Log the error message if needed (optional)
        error_log("Error during check-in: " . $e->getMessage());
    }
    return false; // Return false if an exception occurred
}
function updateNewLocation($visitor_id, $location_id) {
    global $pdo;

    try {
        // Start a transaction to ensure the update is atomic
        $pdo->beginTransaction();

        // Fetch the location name based on the location_id
        $stmtLocation = $pdo->prepare("SELECT name FROM locations WHERE location_id = :location_id");
        $stmtLocation->execute(['location_id' => $location_id]);

        // Check if the location was found
        if ($stmtLocation->rowCount() > 0) {
            $location = $stmtLocation->fetch(PDO::FETCH_ASSOC);
            $location_name = $location['name']; // Get location name
        } else {
            throw new Exception("Location not found.");
        }

        // Check if the visitor has checked in today at any location
        $stmtCheck = $pdo->prepare("
            SELECT * FROM visits 
            WHERE visitor_id = :visitor_id 
              AND DATE(check_in) = CURDATE()
            ORDER BY check_in DESC 
            LIMIT 1
        ");
        $stmtCheck->execute(['visitor_id' => $visitor_id]);

        // If no check-in record found for today, return false
        if ($stmtCheck->rowCount() === 0) {
            $pdo->rollBack();
            return false;
        }

        // Get the latest check-in data
        $latestVisit = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        // Prepare the new insert query with updated location details
        $stmtInsert = $pdo->prepare("
            INSERT INTO visits (visitor_id, user_id, check_in, location_id, faculty, semester, custom_fields, status, location_name, visit_count) 
            VALUES (:visitor_id, :user_id, :check_in, :location_id, :faculty, :semester, :custom_fields, :status, :location_name, 1)
        ");

        // Execute the insert with the copied data, updating only location_id and location_name
        $stmtInsert->execute([
            'visitor_id' => $latestVisit['visitor_id'],
            'user_id' => $latestVisit['user_id'],
            'check_in' => (new DateTime('now', new DateTimeZone('Asia/Kuala_Lumpur')))->format('Y-m-d H:i:s'), // GMT+8
            'location_id' => $location_id,
            'faculty' => $latestVisit['faculty'],
            'semester' => $latestVisit['semester'],
            'custom_fields' => $latestVisit['custom_fields'],
            'status' => 'checked-in',
            'location_name' => $location_name,
        ]);

        // Commit the transaction
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        // Roll back if any error occurs
        $pdo->rollBack();
        error_log("Error updating new location: " . $e->getMessage());
        return false;
    }
}

function registerNewDay($visitor_id, $location_id) {
    global $pdo;

    try {
        // Start a transaction to ensure the update is atomic
        $pdo->beginTransaction();

        // Fetch the location name based on the location_id
        $stmtLocation = $pdo->prepare("SELECT name FROM locations WHERE location_id = :location_id");
        $stmtLocation->execute(['location_id' => $location_id]);

        // Check if the location was found
        if ($stmtLocation->rowCount() > 0) {
            $location = $stmtLocation->fetch(PDO::FETCH_ASSOC);
            $location_name = $location['name']; // Get location name
        } else {
            throw new Exception("Location not found.");
        }

        // Fetch the latest visit data regardless of the date
        $stmtCheck = $pdo->prepare("SELECT * FROM visits WHERE visitor_id = :visitor_id ORDER BY check_in DESC LIMIT 1");
        $stmtCheck->execute(['visitor_id' => $visitor_id]);

        // Get the latest check-in data
        $latestVisit = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$latestVisit) {
            $pdo->rollBack();
            return false;
        }

        // Prepare the new insert query with updated location details
        $stmtInsert = $pdo->prepare("INSERT INTO visits (visitor_id, user_id, check_in, location_id, faculty, semester, custom_fields, status, location_name, visit_count) VALUES (:visitor_id, :user_id, :check_in, :location_id, :faculty, :semester, :custom_fields, :status, :location_name, 1)");

        // Execute the insert with the copied data, updating only location_id and location_name
        $stmtInsert->execute([
            'visitor_id' => $latestVisit['visitor_id'],
            'user_id' => $latestVisit['user_id'],
            'check_in' => (new DateTime('now', new DateTimeZone('Asia/Kuala_Lumpur')))->format('Y-m-d H:i:s'), // GMT+8
            'location_id' => $location_id,
            'faculty' => $latestVisit['faculty'],
            'semester' => $latestVisit['semester'],
            'custom_fields' => $latestVisit['custom_fields'],
            'status' => 'checked-in',
            'location_name' => $location_name,
        ]);

        // Commit the transaction
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        // Roll back if any error occurs
        $pdo->rollBack();
        error_log("Error updating new location: " . $e->getMessage());
        return false;
    }
}


function checkDuplicateCheckIn($visitor_id, $location_id) {
    global $pdo;

    // Get the current date in 'Y-m-d' format (ignoring time)
    $currentDate = (new DateTime('now', new DateTimeZone('Asia/Kuala_Lumpur')))->format('Y-m-d');

    // Query to check if the visitor_id has checked in at the same location on the same day
    $stmt = $pdo->prepare("SELECT 1 FROM visits WHERE visitor_id = :visitor_id 
                           AND location_id = :location_id 
                           AND DATE(check_in) = :currentDate LIMIT 1");
    $stmt->execute([
        'visitor_id' => $visitor_id,
        'location_id' => $location_id,
        'currentDate' => $currentDate,
    ]);

    // If a record is found, return true (duplicate check-in)
    if ($stmt->rowCount() > 0) {
        return true; // Duplicate check-in found
    }

    return false; // No duplicate check-in
}

function checkOutVisitor($visit_id) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE visits SET check_out = NOW(), status = 'checked-out' WHERE visit_id = :visit_id");
    $stmt->execute(['visit_id' => $visit_id]);
}
?>
