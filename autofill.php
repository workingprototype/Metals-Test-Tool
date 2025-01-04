<?php
// fetch_data.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Load configuration from the JSON file
$configFile = 'config.json';
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

// Get the input value from the AJAX request
$input = $_GET['input'];

// Query to fetch suggestions based on name, mobile, or alt_mobile
$sql = "SELECT name, mobile, alt_mobile FROM receipts WHERE name LIKE ? OR mobile LIKE ? OR alt_mobile LIKE ? LIMIT 10";
$stmt = $conn->prepare($sql);
$searchTerm = '%' . $input . '%';
$stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

$suggestions = [];
while ($row = $result->fetch_assoc()) {
    $suggestions[] = $row;
}

echo json_encode($suggestions); // Return suggestions as JSON

$stmt->close();
$conn->close();
?>