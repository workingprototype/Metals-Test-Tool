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
$sql = "SELECT name, mobile, alt_mobile, sample, metal_type, weight FROM receipts WHERE sr_no = ?";
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
        'weight' => $row['weight']
    ]);
} else {
    echo json_encode(['success' => false]);
}

$stmt->close();
$conn->close();
?>