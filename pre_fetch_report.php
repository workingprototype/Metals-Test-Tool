<?php
// Path to the config file
$configFile = 'config.json';

// Load configuration from the JSON file
$configs = json_decode(file_get_contents($configFile), true);

// Extract database settings from the config file
$servername = $configs['Database']['db_host'];
$username = $configs['Database']['db_user'];
$password = $configs['Database']['db_password'];
$dbname = $configs['Database']['db_name'];

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sr_no = $_POST['sr_no'];

// Fetch receipt data based on sr_no
$sql = "SELECT * FROM test_reports WHERE sr_no = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $sr_no);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'name' => $row['name'],
        'mobile' => $row['mobile'],
        'alt_mobile' => $row['alt_mobile'],
        'sample' => $row['sample'],
        'metal_type' => $row['metal_type'],
        'weight' => $row['weight'],
        'gold_percent' => $row['gold_percent'],
        'total_karat' => $row['total_karat'],
        'silver' => $row['silver'],
        'platinum' => $row['platinum'],
        'zinc' => $row['zinc'],
        'copper' => $row['copper'],
        'others' => $row['others'],
        'rhodium' => $row['rhodium'],
        'iridium' => $row['iridium'],
        'ruthenium' => $row['ruthenium'],
        'palladium' => $row['palladium'],
        'lead' => $row['lead'],
        'tin' => $row['tin'],
        'cadmium' => $row['cadmium'],
        'nickel' => $row['nickel']
    ]);
} else {
    echo json_encode(['success' => false]);
}

$stmt->close();
$conn->close();
?>