<?php

include_once '../../config/config.php';
include_once '../../controllers/VisitController.php';
include_once '../../controllers/FormFieldController.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $payload = collectVisitPayload($_POST);
    $visitor_id = $_POST['visitorId'];
    $location_id = $_POST['location'];

    if (checkInVisitor($visitor_id, $payload['name'], $location_id, $payload['faculty'], $payload['semester'], $payload['custom_fields'])) {
        header("Location: visitor-card.php");
        exit();
    } else {
        echo "Check-in failed. Please try again.";
    }
}



?>
