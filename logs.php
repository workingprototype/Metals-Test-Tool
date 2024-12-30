<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'vendor/autoload.php'; // Adjust to the location of Twilio SDK

use Twilio\Rest\Client;

// Path to the config file
$configFile = 'config.json';

// Load configuration from the JSON file
$configs = json_decode(file_get_contents($configFile), true);

// Check if Twilio is enabled
$twilioEnabled = isset($configs['Twilio']['use_twilio']) && $configs['Twilio']['use_twilio'] === true;

if ($twilioEnabled) {
    // Load Twilio credentials from the config file
    $twilio_sid = $configs['Twilio']['twilio_sid'];
    $twilio_token = $configs['Twilio']['twilio_token'];
    $twilio_phone_number = $configs['Twilio']['twilio_phone_number']; // Your Twilio phone number (for SMS & WhatsApp)

    // Initialize Twilio client
    $client = new Client($twilio_sid, $twilio_token);

    // Fetch account balance
    try {
        $balance = $client->balance->fetch();
        $availableBalance = $balance->balance;

        // Pagination setup
        $messagesPerPage = 10; // Number of messages per page
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page
        $offset = ($page - 1) * $messagesPerPage; // Offset for message retrieval

        // Fetch the message logs (filtering by 'from' and sorting by dateSent desc)
        $messages = $client->messages->read(
            ['from' => $twilio_phone_number], // Filter for messages sent from your number
            $messagesPerPage // Number of messages to fetch per page
        );

        // Get total message count for pagination
        $totalMessages = count($client->messages->read(['from' => $twilio_phone_number])); // Get all messages to count
        $totalPages = ceil($totalMessages / $messagesPerPage);

        // Initialize counters for statuses
        $deliveredCount = 0;
        $undeliveredCount = 0;
        $failedCount = 0;
        $queuedCount = 0;

        // Loop through the messages to count status types
        foreach ($client->messages->read(['from' => $twilio_phone_number]) as $message) {
            switch ($message->status) {
                case 'delivered':
                    $deliveredCount++;
                    break;
                case 'undelivered':
                    $undeliveredCount++;
                    break;
                case 'failed':
                    $failedCount++;
                    break;
                case 'queued':
                case 'sending':
                case 'sent':
                    $queuedCount++;
                    break;
            }
        }
    } catch (Exception $e) {
        echo 'Error fetching Twilio data: ' . $e->getMessage();
        $twilioEnabled = false; // Set to false in case of any error
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Delivery Logs and Balance</title>
    <link href="vendor/assets/bootstrap.min.css" rel="stylesheet">
    <style>
        .table th, .table td {
            vertical-align: middle;
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
        <h2 class="mb-4">Twilio SMS Delivery Logs and Account Balance</h2>
        
        <?php if ($twilioEnabled): ?>
            <h4>Number Used: <?php echo  $twilio_phone_number ?></h4>
            <p>Available Balance: $<?php echo $availableBalance; ?></p>
            <h4>SMS Status Counts</h4>
            <ul>
                <li>Delivered: <?php echo $deliveredCount; ?></li>
                <li>Undelivered: <?php echo $undeliveredCount; ?></li>
                <li>Failed: <?php echo $failedCount; ?></li>
                <li>In Queue/Sent: <?php echo $queuedCount; ?></li>
            </ul>

            <!-- Table Wrapper for Responsiveness -->
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Message SID</th>
                            <th>To</th>
                            <th>Status</th>
                            <th>Sent At</th>
                            <th>Body</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Loop through the messages and display them in the table
                        foreach ($messages as $message) {
                            echo '<tr>';
                            echo '<td>' . $message->sid . '</td>';
                            echo '<td>' . $message->to . '</td>';
                            echo '<td>' . ucfirst($message->status) . '</td>';
                            echo '<td>' . $message->dateSent->format('Y-m-d H:i:s') . '</td>';  // Fixed line
                            echo '<td>' . htmlspecialchars($message->body) . '</td>';
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Links -->
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php else: ?>
            <p>Twilio is currently disabled. Please enable it in the configuration settings.</p>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS and jQuery -->
    <script src="vendor/assets/jquery-3.5.1.slim.min.js"></script>
    <script src="vendor/assets/popper.min.js"></script>
    <script src="vendor/assets/bootstrap.bundle.min.js"></script>
</body>
</html>
