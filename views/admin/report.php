<?php
include_once '../../config/config.php';
include_once '../../sessions/session.php';
include_once '../../controllers/FormFieldController.php';

if (!isLoggedIn()) {
    header('Location: ' . $base_url . '/views/admin/log-masuk.php');
    exit();
}

function safeDateParam($value, $fallback) {
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value) ? $value : $fallback;
}

function addReportCount(&$bucket, $key) {
    $key = trim((string) $key);
    if ($key === '') {
        $key = 'Not Set';
    }
    if (!isset($bucket[$key])) {
        $bucket[$key] = 0;
    }
    $bucket[$key]++;
}

function rowsFromBucket($bucket) {
    arsort($bucket);
    $rows = [];
    foreach ($bucket as $label => $total) {
        $rows[] = ['label' => $label, 'total' => $total];
    }
    return $rows;
}

$today = new DateTime('now', new DateTimeZone('Asia/Kuala_Lumpur'));
$defaultStart = (clone $today)->modify('first day of this month')->format('Y-m-d');
$defaultEnd = $today->format('Y-m-d');
$startDate = safeDateParam($_GET['start_date'] ?? '', $defaultStart);
$endDate = safeDateParam($_GET['end_date'] ?? '', $defaultEnd);

if ($startDate > $endDate) {
    [$startDate, $endDate] = [$endDate, $startDate];
}

$start = $startDate . ' 00:00:00';
$end = $endDate . ' 23:59:59';
$fields = getFormFields(true);
$fieldLabels = [];
foreach ($fields as $field) {
    $fieldLabels[$field['field_key']] = $field['label'];
}

$stmt = $pdo->prepare("
    SELECT v.visit_id, v.visitor_id, u.name, v.check_in, v.location_name, v.faculty, v.semester, v.custom_fields
    FROM visits v
    LEFT JOIN users u ON u.user_id = v.user_id
    WHERE v.status = 'checked-in'
      AND v.check_in BETWEEN :start_date AND :end_date
    ORDER BY v.check_in ASC
");
$stmt->execute([
    ':start_date' => $start,
    ':end_date' => $end,
]);
$visits = $stmt->fetchAll(PDO::FETCH_ASSOC);

$byDate = [];
$byLocation = [];
$byFaculty = [];
$bySemester = [];
$byFacultySemester = [];
$byCustom = [];

foreach ($visits as $visit) {
    addReportCount($byDate, substr($visit['check_in'], 0, 10));
    addReportCount($byLocation, $visit['location_name']);
    addReportCount($byFaculty, $visit['faculty']);
    addReportCount($bySemester, $visit['semester']);
    addReportCount($byFacultySemester, ($visit['faculty'] ?: 'Not Set') . ' / ' . ($visit['semester'] ?: 'Not Set'));

    $custom = json_decode($visit['custom_fields'] ?? '{}', true);
    if (!is_array($custom)) {
        continue;
    }

    foreach ($custom as $key => $value) {
        $label = $fieldLabels[$key] ?? ucwords(str_replace('_', ' ', $key));
        if (!isset($byCustom[$key])) {
            $byCustom[$key] = ['label' => $label, 'values' => []];
        }
        addReportCount($byCustom[$key]['values'], $value);
    }
}

function renderSummaryTable($title, $rows, $labelHeading) {
    ?>
    <section class="report-card">
        <h2><?php echo htmlspecialchars($title); ?></h2>
        <table>
            <thead>
                <tr>
                    <th><?php echo htmlspecialchars($labelHeading); ?></th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="2">No data</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['label']); ?></td>
                            <td><?php echo (int) $row['total']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
    <?php
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Visitor Report</title>
    <style>
        body {
            margin: 0;
            padding: 28px;
            background: #f4f6f8;
            color: #1f2933;
            font-family: Arial, sans-serif;
        }
        .report-page {
            max-width: 1120px;
            margin: 0 auto;
        }
        .report-header {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        h1 {
            margin: 0;
            font-size: 28px;
        }
        .muted {
            color: #687385;
            margin-top: 6px;
        }
        .toolbar {
            display: flex;
            gap: 8px;
        }
        .btn {
            border: 0;
            border-radius: 5px;
            padding: 9px 14px;
            background: #3273dc;
            color: #fff;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
        }
        .summary-strip {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            margin-bottom: 18px;
        }
        .metric,
        .report-card {
            background: #fff;
            border: 1px solid #dde3ea;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(16, 24, 40, 0.06);
        }
        .metric {
            padding: 16px;
        }
        .metric strong {
            display: block;
            font-size: 26px;
            margin-top: 4px;
        }
        .report-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 14px;
            margin-bottom: 18px;
        }
        .report-card {
            padding: 14px;
            overflow-x: auto;
        }
        .report-card h2 {
            margin: 0 0 10px;
            font-size: 16px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        th,
        td {
            border: 1px solid #dde3ea;
            padding: 8px 10px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #eaf3ff;
        }
        .details table {
            min-width: 880px;
        }
        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .toolbar {
                display: none;
            }
            .report-card,
            .metric {
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
<main class="report-page">
    <header class="report-header">
        <div>
            <h1>Visitor Detail Report</h1>
            <div class="muted"><?php echo htmlspecialchars($startDate); ?> to <?php echo htmlspecialchars($endDate); ?></div>
        </div>
        <div class="toolbar">
            <button class="btn" onclick="window.print()">Print</button>
            <a class="btn" href="main.php">Back</a>
        </div>
    </header>

    <section class="summary-strip">
        <div class="metric">Total Visits<strong><?php echo count($visits); ?></strong></div>
        <div class="metric">Faculties<strong><?php echo count(array_filter($byFaculty, fn($count, $key) => $key !== 'Not Set', ARRAY_FILTER_USE_BOTH)); ?></strong></div>
        <div class="metric">Semesters<strong><?php echo count(array_filter($bySemester, fn($count, $key) => $key !== 'Not Set', ARRAY_FILTER_USE_BOTH)); ?></strong></div>
    </section>

    <div class="report-grid">
        <?php renderSummaryTable('Date Count', rowsFromBucket($byDate), 'Date'); ?>
        <?php renderSummaryTable('Location Count', rowsFromBucket($byLocation), 'Location'); ?>
        <?php renderSummaryTable('Faculty Count', rowsFromBucket($byFaculty), 'Faculty'); ?>
        <?php renderSummaryTable('Semester Count', rowsFromBucket($bySemester), 'Semester'); ?>
        <?php renderSummaryTable('Faculty + Semester Count', rowsFromBucket($byFacultySemester), 'Faculty / Semester'); ?>
        <?php foreach ($byCustom as $customField): ?>
            <?php renderSummaryTable($customField['label'] . ' Count', rowsFromBucket($customField['values']), $customField['label']); ?>
        <?php endforeach; ?>
    </div>

    <section class="report-card details">
        <h2>Visit Details</h2>
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Date / Time</th>
                    <th>Name</th>
                    <th>Faculty</th>
                    <th>Semester</th>
                    <th>Location</th>
                    <th>Additional Fields</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($visits)): ?>
                    <tr><td colspan="7">No visits found for this date range.</td></tr>
                <?php else: ?>
                    <?php foreach ($visits as $index => $visit): ?>
                        <?php
                        $custom = json_decode($visit['custom_fields'] ?? '{}', true);
                        if (!is_array($custom)) {
                            $custom = [];
                        }
                        $customParts = [];
                        foreach ($custom as $key => $value) {
                            $label = $fieldLabels[$key] ?? ucwords(str_replace('_', ' ', $key));
                            $customParts[] = $label . ': ' . $value;
                        }
                        ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($visit['check_in']); ?></td>
                            <td><?php echo htmlspecialchars($visit['name'] ?: 'Not Set'); ?></td>
                            <td><?php echo htmlspecialchars($visit['faculty'] ?: 'Not Set'); ?></td>
                            <td><?php echo htmlspecialchars($visit['semester'] ?: 'Not Set'); ?></td>
                            <td><?php echo htmlspecialchars($visit['location_name'] ?: 'Not Set'); ?></td>
                            <td><?php echo htmlspecialchars(implode(', ', $customParts) ?: '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</main>
</body>
</html>
