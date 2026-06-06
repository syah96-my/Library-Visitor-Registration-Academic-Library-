<?php
session_start();
include_once '../../config/config.php';
include_once '../../controllers/CookieRetrieve.php';
include_once '../../controllers/VisitController.php';

// First, check if 'loc' parameter exists in GET
if (isset($_GET['loc']) && !empty($_GET['loc'])) {
    $location_id = base64_decode($_GET['loc'], true);
    if ($location_id === false || !ctype_digit($location_id)) {
        header("Location: invalid-id.php");
        exit;
    }
} else {
    // Location not provided, redirect to no-location page
    header("Location: no-location.php");
    exit;
}

// Check if visitor_id cookie exists and not empty
if (isset($_COOKIE['visitor_id']) && !empty($_COOKIE['visitor_id'])) {
    $visitor_id = $_COOKIE['visitor_id']; // get visitor_id from cookie
    $userInfo = getUserInfo();

    if ($userInfo === false || $userInfo['visitor_id'] === null) {
        setcookie('visitor_id', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        unset($_COOKIE['visitor_id']);
        $_SESSION['location_id'] = $location_id;
        header("Location: check-in.php?loc=" . rawurlencode($_GET['loc']));
        exit;
    }

        // Use your checkLocation function to see if the user already checked in today at this location
        if (checkLocation($visitor_id, $location_id)) {
            // Case 1: Checked in today at the same location
            header("Location: visitor-card.php");
            exit;
        } else {
            // Case 2 & 3: Either checked in today at a different location or not checked in today at all
            if (isCheckedInToday($visitor_id)) {
                    if (updateNewLocation($visitor_id, $location_id)) {
                        header("Location: visitor-card.php");
                        exit;
                    } else {
                        echo "Failed to update location.";
                    }

            } else {
                if (registerNewDay($visitor_id, $location_id)) {
                    header("Location: visitor-card.php");
                    exit;
                } else {
                    echo "Failed to register visit.";
                }
            }
        }

} else {
    $_SESSION['location_id'] = $location_id;
    header("Location: check-in.php?loc=" . rawurlencode($_GET['loc']));
    exit;
}
?>
