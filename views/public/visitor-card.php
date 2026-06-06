<?php
session_start();
include_once '../../config/config.php';
include_once '../../controllers/CookieRetrieve.php';
include_once '../../controllers/ColorController.php';
include_once '../../controllers/FormFieldController.php';

// Main logic to use the functions
$userData = getUserInfo();

if ($userData === false || $userData['visitor_id'] === null) {
    header("Location: no-location.php");
    exit;
} else {
    // Extract user name
    $userName = htmlspecialchars(strtoupper($userData['userName']));
$visit = getVisits($userData['visitor_id']);

if (!empty($visit)) {
    // Extract visit details from the associative array
    $location_id = $visit['location_id'];
    $location_name = htmlspecialchars($visit['location_name']);
    $faculty = htmlspecialchars(strtoupper($visit['faculty'] ?? ''));
    $semester = htmlspecialchars(strtoupper($visit['semester'] ?? ''));
    $customFields = json_decode($visit['custom_fields'] ?? '{}', true);
    if (!is_array($customFields)) {
        $customFields = [];
    }
    $check_in = $visit['check_in'];

    // Split the date and time
    list($date, $time) = explode(' ', $check_in);

            // Split the date and time
            list($date, $time) = explode(' ', $check_in);
       
        //unset($_SESSION['location_id']);
    } else {
        unset($_SESSION['location_id']);
        header("Location: no-location.php");
        exit;
    }
}

$location_color=getColor($location_id);
$avatar = 'read';
$fieldLabels = [];
foreach (getFormFields(true) as $field) {
    $fieldLabels[$field['field_key']] = $field['label'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://code.iconify.design/2/2.1.2/iconify.min.js"></script>
    <link rel="stylesheet" href="../../assets/css/visitor-pass.css">
    <title>Kad Digital</title>
    
</head>
<body>

<div class="business2">
    <div class="header" style="background-color:<?php echo $location_color; ?>">
        <img class="logo" src="../../assets/images/dummy-logo.svg" alt="logo">
        <h2>KAD DIGITAL</h2>
    </div>
    <div class="avatar" style="background: linear-gradient(to bottom, <?php echo $location_color; ?> 50%, white 50%);">
        <img src="../../assets/images/<?php echo $avatar; ?>.png" alt="Avatar">
        <h3>VISITOR</h3>
    </div>
    <div class="infos">
        <p>Nama: <?php echo htmlspecialchars(strtoupper($userName)) ;?></p>
        <?php echo $faculty !== '' ? "<p>Faculty: $faculty</p>" : ''; ?>
        <?php echo $semester !== '' ? "<p>Semester: $semester</p>" : ''; ?>
        <p>Lokasi: <?php echo htmlspecialchars(strtoupper($location_name)) ;?></p>
        <p>Tarikh: <?php echo (new DateTime($date))->format('d/m/Y'); ?></p>
        <p>Masa: <?php echo htmlspecialchars($time) ;?></p>
        <?php
        foreach ($customFields as $key => $value) {
            $label = htmlspecialchars($fieldLabels[$key] ?? ucwords(str_replace('_', ' ', $key)));
            $safeValue = htmlspecialchars(strtoupper((string) $value));
            echo $safeValue !== '' ? "<p>$label: $safeValue</p>" : '';
        }
        ?>
    </div>
    <div class="credit" style="background-color:<?php echo $location_color; ?>">
        <p>Selamat Datang</p>
        <span class="iconify" data-icon="mdi:card-account-details" style="font-size: 2rem; color: #ffffff;"></span>
    </div>
</div>

</body>
</html>
