<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Path to the config file
$configFile = 'config.json';

// Load configuration from the JSON file
$configs = json_decode(file_get_contents($configFile), true);

// Database connection
$servername = $configs['Database']['db_host'];
$username = $configs['Database']['db_user'];
$password = $configs['Database']['db_password'];
$dbname = $configs['Database']['db_name'];

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Determine the message type to display (SMS or WhatsApp)
$message_type = isset($_GET['type']) ? $_GET['type'] : 'SMS';

// Pagination logic
$limit = 10; // Number of records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page
$offset = ($page - 1) * $limit; // Offset for SQL query

// Fetch total number of records
$sql_count = "SELECT COUNT(*) as total FROM message_logs WHERE message_type = '$message_type'";
$result_count = $conn->query($sql_count);
$total_records = $result_count->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit); // Total pages

// Fetch logs from the database with pagination
$sql = "SELECT * FROM message_logs WHERE message_type = '$message_type' ORDER BY sent_at DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

$logs = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message Logs</title>
    <link href="vendor/assets/bootstrap.min.css" rel="stylesheet">
    <style>
        .table th, .table td {
            vertical-align: middle;
        }
        .pagination {
            justify-content: center;
        }
    </style>
</head>
<body>
    <!-- Top Nav Menu -->
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
                    <a class="nav-link" href="" onclick="window.close(); return false;">Exit</a>
                </li>
            </ul>
        </div>
    </nav>
    <div class="container mt-4">
        <h2 class="mb-4">Message Logs</h2>
        
        <!-- Toggle between SMS and WhatsApp logs -->
        <div class="btn-group mb-4" role="group">
            <a href="?type=SMS" class="btn btn-<?php echo $message_type === 'SMS' ? 'primary' : 'secondary'; ?>">SMS Logs</a>
            <a href="?type=WhatsApp" class="btn btn-<?php echo $message_type === 'WhatsApp' ? 'primary' : 'secondary'; ?>">WhatsApp Logs</a>
        </div>

        <!-- Logs Table -->
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Sr. No.</th>
                        <th>Recipient</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Sent At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo $log['sr_no']; ?></td>
                            <td><?php echo $log['recipient']; ?></td>
                            <td><?php echo $log['message']; ?></td>
                            <td><?php echo $log['status']; ?></td>
                            <td><?php echo $log['sent_at']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <nav aria-label="Page navigation">
            <ul class="pagination">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?type=<?php echo $message_type; ?>&page=<?php echo $page - 1; ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?type=<?php echo $message_type; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?type=<?php echo $message_type; ?>&page=<?php echo $page + 1; ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>

    <!-- Bootstrap JS and jQuery -->
    <script src="vendor/assets/jquery-3.5.1.slim.min.js"></script>
    <script src="vendor/assets/popper.min.js"></script>
    <script src="vendor/assets/bootstrap.bundle.min.js"></script>
</body>
</html>