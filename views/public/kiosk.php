<?php
session_start();
include_once '../../config/config.php';
include_once '../../controllers/CookieSet.php';
include_once '../../controllers/FormFieldController.php';

$uuid = generateUUID();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Registration</title>
    <!-- Bulma CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
    <link rel="stylesheet" href="../../assets/css/main-kiosk.css">
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
                
                <form action="process-check-in-kiosk.php" method="POST">
                    <input type="hidden" name="visitorId" value="<?php echo htmlspecialchars($uuid); ?>" />

                    <?php renderVisitorFields(); ?>
                    
                    <div class="field">
                        <label class="label has-text-white">Lokasi Dituju</label>
                        <div class="control">
                            <div class="select is-fullwidth is-rounded">
                                <select name="location" required>
                                    <option value="" disabled selected>Sila pilih lokasi</option>
                                    <?php
                                    // Fetch locations from the database
                                    $stmt = $pdo->query("SELECT location_id, name FROM locations ORDER BY name ASC");
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        echo '<option value="' . htmlspecialchars($row['location_id']) . '">' . htmlspecialchars($row['name']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    
                    
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
