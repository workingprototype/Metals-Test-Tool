<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database connection
$configFile = 'config.json';
$configs = json_decode(file_get_contents($configFile), true);
$servername = $configs['Database']['db_host'];
$username = $configs['Database']['db_user'];
$password = $configs['Database']['db_password'];
$dbname = $configs['Database']['db_name'];

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the Sr. No. from the query parameter
$sr_no = $_GET['sr_no'];

// Fetch receipt data based on Sr. No. using a prepared statement
$sql = "SELECT * FROM receipts WHERE sr_no = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $sr_no);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode($row); // Return the data as JSON
} else {
    echo json_encode(null); // Return null if no data is found
}

$stmt->close();
$conn->close();
?>