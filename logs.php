<?php
// Turn on error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// =================================================================
// 1. CONFIGURATION & SETUP
// =================================================================

// Path to the config file
$configFile = 'config.json';
$configs = json_decode(file_get_contents($configFile), true);

// Database settings
$servername = $configs['Database']['db_host'];
$username = $configs['Database']['db_user'];
$password = $configs['Database']['db_password'];
$dbname = $configs['Database']['db_name'];

// Establish a secure database connection
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// =================================================================
// 2. HELPER FUNCTIONS
// =================================================================

/**
 * Generates intelligent, responsive pagination HTML.
 */
function generatePagination($currentPage, $totalPages, $queryParams = []) {
    if ($totalPages <= 1) {
        return '';
    }
    unset($queryParams['page']);
    $queryString = http_build_query($queryParams);
    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center flex-wrap">';
    $disabledPrev = ($currentPage <= 1) ? 'disabled' : '';
    $html .= "<li class='page-item {$disabledPrev}'><a class='page-link' href='?page=1&{$queryString}'>« First</a></li>";
    $html .= "<li class='page-item {$disabledPrev}'><a class='page-link' href='?page=" . ($currentPage - 1) . "&{$queryString}'>Previous</a></li>";
    $window = 2;
    for ($i = 1; $i <= $totalPages; $i++) {
        if ($i == 1 || $i == $totalPages || ($i >= $currentPage - $window && $i <= $currentPage + $window)) {
            $active = ($i == $currentPage) ? 'active' : '';
            $html .= "<li class='page-item {$active}'><a class='page-link' href='?page={$i}&{$queryString}'>{$i}</a></li>";
        } elseif ($i == $currentPage - $window - 1 || $i == $currentPage + $window + 1) {
            $html .= "<li class='page-item disabled'><span class='page-link'>...</span></li>";
        }
    }
    $disabledNext = ($currentPage >= $totalPages) ? 'disabled' : '';
    $html .= "<li class='page-item {$disabledNext}'><a class='page-link' href='?page=" . ($currentPage + 1) . "&{$queryString}'>Next</a></li>";
    $html .= "<li class='page-item {$disabledNext}'><a class='page-link' href='?page={$totalPages}&{$queryString}'>Last »</a></li>";
    $html .= '</ul></nav>';
    return $html;
}

// =================================================================
// 3. INPUT HANDLING & DEFAULTS
// =================================================================

// Pagination variables
$limit = 15;
$page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Filter parameters from GET request
$message_type = $_GET['type'] ?? 'SMS';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';

// =================================================================
// 4. DATA FETCHING FOR DISPLAY (SECURE & PAGINATED)
// =================================================================

$where_clauses = [];
$params = [];
$types = '';

// Build WHERE clause dynamically and securely
$where_clauses[] = "message_type = ?";
$params[] = $message_type;
$types .= 's';

if (!empty($from_date) && !empty($to_date)) {
    $where_clauses[] = "DATE(sent_at) BETWEEN ? AND ?";
    array_push($params, $from_date, $to_date);
    $types .= 'ss';
} elseif (!empty($from_date)) {
    $where_clauses[] = "DATE(sent_at) >= ?";
    $params[] = $from_date;
    $types .= 's';
} elseif (!empty($to_date)) {
    $where_clauses[] = "DATE(sent_at) <= ?";
    $params[] = $to_date;
    $types .= 's';
}

$sql_where = " WHERE " . implode(" AND ", $where_clauses);

// Get total number of records for pagination
$count_sql = "SELECT COUNT(*) AS total FROM message_logs" . $sql_where;
$stmt = $conn->prepare($count_sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$total_records = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);
$stmt->close();

// Get records for the current page
$data_sql = "SELECT * FROM message_logs" . $sql_where . " ORDER BY sent_at DESC LIMIT ?, ?";
$stmt = $conn->prepare($data_sql);

// Add limit and offset to parameters for the data query
$data_params = $params;
$data_types = $types . 'ii';
array_push($data_params, $start, $limit);

$stmt->bind_param($data_types, ...$data_params);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

// Preserve query parameters for links
$queryParams = $_GET;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message Logs</title>
    <link href="vendor/assets/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .table th, .table td { vertical-align: middle; }
        .table td:nth-child(3) { /* Message column */
            word-break: break-word;
            min-width: 300px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <a class="navbar-brand" href="index.php">National Gold Testing</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Home Page</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="testreportform.php">Test Report Page</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="reports.php">Reports Page</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="receipts.php">Receipts Page</a>
                </li>
                <li class="nav-item active">
                    <a class="nav-link" href="logs.php">Logs Page</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="config.php">Config Page</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="exit.php" onclick="window.close(); return false;">Exit</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Message Logs</h4>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <input type="hidden" name="type" value="<?php echo htmlspecialchars($message_type); ?>">
                    <div class="col-md-auto">
                        <label class="form-label">Log Type:</label>
                        <div class="btn-group" role="group">
                            <a href="?type=SMS" class="btn btn-<?php echo $message_type === 'SMS' ? 'primary' : 'outline-primary'; ?>">SMS Logs</a>
                            <a href="?type=WhatsApp" class="btn btn-<?php echo $message_type === 'WhatsApp' ? 'primary' : 'outline-primary'; ?>">WhatsApp Logs</a>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label for="from_date" class="form-label">From Date:</label>
                        <input type="date" id="from_date" name="from_date" class="form-control" value="<?php echo htmlspecialchars($from_date); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="to_date" class="form-label">To Date:</label>
                        <input type="date" id="to_date" name="to_date" class="form-control" value="<?php echo htmlspecialchars($to_date); ?>">
                    </div>
                    <div class="col-md-auto">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="?type=<?php echo htmlspecialchars($message_type); ?>" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
            <div class="card-footer">
                <span class="badge bg-info">Total Logs: <?php echo $total_records; ?></span>
                <span class="badge bg-light text-dark">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
            </div>
        </div>

        <div class="table-responsive mt-4">
            <table class="table table-striped table-bordered table-hover">
                <thead class="table-dark sticky-top">
                    <tr>
                        <th>Sr. No.</th>
                        <th>Recipient</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Sent At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($log = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($log['sr_no'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($log['recipient'] ?? 'N/A'); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($log['message'] ?? 'N/A')); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo (stripos($log['status'] ?? '', 'success') !== false || stripos($log['status'] ?? '', 'sent') !== false) ? 'success' : 'danger'; ?>">
                                        <?php echo htmlspecialchars($log['status'] ?? 'N/A'); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars(date('d-m-Y H:i:s', strtotime($log['sent_at'] ?? ''))); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">No logs found for the selected criteria.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Intelligent Pagination -->
        <div class="mt-4">
            <?php echo generatePagination($page, $total_pages, $queryParams); ?>
        </div>
    </div>

    <script src="vendor/assets/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$conn->close();
?>