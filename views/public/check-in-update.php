<?php
session_start();
include_once '../../config/config.php';
include_once '../../controllers/CookieRetrieve.php';
include_once '../../controllers/FormFieldController.php';

if (isset($_GET['loc']) && !empty($_GET['loc'])) {
    $decodedLocation = base64_decode($_GET['loc'], true);
    if ($decodedLocation === false || !ctype_digit($decodedLocation)) {
        header("Location: invalid-id.php");
        exit;
    }
    $_SESSION['location_id'] = $decodedLocation;
}

if (isset($_SESSION['location_id'])) {
    $location_id = $_SESSION['location_id'];
} else {
    header("Location: no-location.php");
    exit;
}

// Main logic to use the functions
$userInfo = getUserInfo();

if ($userInfo === false || $userInfo['visitor_id'] === null) {
    header("Location: check-in.php");
    exit;
} else {
    $visitorId = $userInfo['visitor_id'];
    $userName = htmlspecialchars(strtoupper($userInfo['userName']));
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Registration</title>
    <!-- Bulma CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
    <link rel="stylesheet" href="../../assets/css/main-visitor.css">
    <link rel="stylesheet" href="../../assets/css/icon.css">
    <!-- Iconify Icons -->
    <script src="https://code.iconify.design/2/2.1.2/iconify.min.js"></script>
   
</head>
<body>
    <section class="section">
        <div class="container">
            <div class="box">
                <!-- Logo above the title -->
                <img src="../../assets/images/dummy-logo.svg" alt="Logo" class="logo">
                
                <h1 class="title has-text-centered">Pendaftaran Pelawat</h1>
                
                <form action="process-check-in.php" method="POST">
                    <!-- Nama -->
                    <input type="hidden" name="location" value="<?php echo htmlspecialchars($location_id); ?>" />
                    <input type="hidden" name="visitorId" value="<?php echo htmlspecialchars($visitorId); ?>" />
                    

                    <div class="field">
                        <label class="label has-text-white">
                            <h3 style="text-align: center; font-weight: bold;">SELAMAT KEMBALI</h3>
                            <h4 style="text-align: center; font-weight: bold;"><?php echo htmlspecialchars($userName); ?></h4>
                        </label>
                    </div>

                    <?php renderVisitorFields(getUserProfile($visitorId)); ?>
                    

                    <!-- Submit Button -->
                    <div class="field">
                        <div class="control">
                            <button type="submit" class="button is-fullwidth is-rounded">Daftar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
</body>
</html>
