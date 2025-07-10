<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start(); // Start the session to store the last execution time

// Set the default timezone (e.g., 'Asia/Kolkata' for Indian Standard Time)
date_default_timezone_set('Asia/Kolkata'); // Replace with your desired timezone

// Get the current date in DD-MM-YYYY format
$currentDate = (new DateTime())->format('d-m-Y');

// Generate the current time in HH:MM:SS AM/PM format
$currentTime = (new DateTime())->format('h:i:s A');

// Twilio API setup
require_once 'vendor/autoload.php'; // Adjust to the location of Twilio SDK

// Path to the config file
$configFile = 'config.json';

// Load configuration from the JSON file
$configs = json_decode(file_get_contents($configFile), true);
// Get the zoom level from the config
$zoomLevel = isset($configs['zoomLevel']) ? $configs['zoomLevel'] : '100'; // Default to 100% if not set

// Function to log messages to the database
function logMessage($conn, $sr_no, $message_type, $recipient, $message, $status) {
    $stmt = $conn->prepare("INSERT INTO message_logs (sr_no, message_type, recipient, message, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $sr_no, $message_type, $recipient, $message, $status);
    $stmt->execute();
    $stmt->close();
}

// Function to send SMS using Fast2SMS
function sendMessages($configs, $phone_numbers, $name, $sr_no, $metal_type, $gold_percent, $total_karat, $current_date, $sample, $conn, $silver, $platinum) {

    // Rate-limiting: Check if the function was called within the last 5 seconds
    if (isset($_SESSION['last_send_time']) && (time() - $_SESSION['last_send_time']) < 5) {
        echo "Please wait 5 seconds before sending messages again.<br>";
        return; // Exit the function if the cooldown period hasn't passed
    }

    // Update the last execution time
    $_SESSION['last_send_time'] = time();

    // Determine the value to send based on metal_type
    $value_to_send = $gold_percent; // Default to gold_percent
    if ($metal_type == 'Silver') {
        $value_to_send = $silver; // Use silver value if metal_type is silver
    } elseif ($metal_type == 'Platinum') {
        $value_to_send = $platinum; // Use platinum value if metal_type is platinum
    }

    // Variables for the template (pipe-separated values with newlines)
    $variables_values = "$name|$sr_no|$current_date|$metal_type|$value_to_send";

    // Approved template format
    $template_id = "178649"; // Replace with your approved template ID
    // Fast2SMS API URL
    $fast2sms_url = "https://www.fast2sms.com/dev/bulkV2";

    // Fast2SMS API Key
    $api_key = $configs['Fast2SMS']['api_key']; // Ensure you have added 'Fast2SMS' section in your config.json

    // Send SMS via Fast2SMS
    foreach ($phone_numbers as $phone_number) {
        if (!empty($phone_number)) {
            try {
                // Sanitize the phone number
                $formatted_number = preg_replace('/\D/', '', $phone_number); // Remove all non-numeric characters

                // Remove the '91' prefix if it exists
                if (strlen($formatted_number) == 12 && substr($formatted_number, 0, 2) == '91') {
                    $formatted_number = substr($formatted_number, 2); // Remove the first 2 characters (91)
                }

                // Ensure the number is exactly 10 digits long
                if (strlen($formatted_number) != 10) {
                    echo "Invalid phone number: $phone_number. It must be exactly 10 digits long. Skipping.<br>";
                    continue;
                }

                // Prepare the Fast2SMS API request
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $fast2sms_url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'authorization: ' . $api_key,
                    'Content-Type: application/x-www-form-urlencoded'
                ]);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                    'sender_id' => 'NTGLD',
                    'message' => $template_id, // Use the template ID
                    'variables_values' => $variables_values, // Variables for the template
                    'route' => 'dlt',
                    'numbers' => $formatted_number, // Send the sanitized 10-digit number
                    'flash' => 0
                ]));

                // Execute the request
                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($http_code == 200) {
                    echo "SMS sent successfully to $phone_number!<br>";
                    logMessage($conn, $sr_no, 'SMS', $phone_number, $variables_values, 'Success');
                } else {
                    echo "Error sending SMS to $phone_number. Response: $response<br>";
                    logMessage($conn, $sr_no, 'SMS', $phone_number, $variables_values, 'Failed');
                }
            } catch (Exception $e) {
                echo "Error sending SMS to $phone_number: " . $e->getMessage() . "<br>";
                logMessage($conn, $sr_no, 'SMS', $phone_number, $variables_values, 'Failed');
            }
        }
    }

    // Send WhatsApp message to both numbers
    $whatsapp_api_url = $configs['WhatsApp']['whatsapp_api_url'];
    $whatsapp_number = $configs['WhatsApp']['whatsapp_number'];
    $access_token = $configs['WhatsApp']['access_token'];

    foreach ($phone_numbers as $phone_number) {
        if (!empty($phone_number)) {
            // Format the WhatsApp number
            $formatted_mobile = preg_replace('/\D/', '', $phone_number);
            if (strlen($formatted_mobile) == 10) {
                $formatted_mobile = '+91' . $formatted_mobile;
            }

            // Prepare WhatsApp API request
            $whatsapp_data = [
                'messaging_product' => 'whatsapp',
                'to' => $formatted_mobile,
                'type' => 'template',
                'template' => [
                    'name' => 'testreportssoftware', // Ensure this template is approved
                    'language' => ['code' => 'en_US'],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => [
                                ['type' => 'text', 'text' => $name],
                                ['type' => 'text', 'text' => $sr_no],
                                ['type' => 'text', 'text' => $current_date],
                                ['type' => 'text', 'text' => $sample],
                                ['type' => 'text', 'text' => $metal_type],
                                ['type' => 'text', 'text' => $value_to_send], // Use the determined value
                                ['type' => 'text', 'text' => $total_karat]
                            ]
                        ]
                    ]
                ]
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $whatsapp_api_url . $whatsapp_number . '/messages');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($whatsapp_data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $access_token,
                'Content-Type: application/json'
            ]);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // Decode the API response
            $response_data = json_decode($response, true);

            // Check if the message was sent successfully
            if ($http_code == 200 && isset($response_data['messages'][0]['id'])) {
                echo "WhatsApp message sent successfully to $phone_number!<br>";
                logMessage($conn, $sr_no, 'WhatsApp', $formatted_mobile, json_encode($whatsapp_data), 'Success');
            } else {
                // Handle API errors
                $error_message = "Error sending WhatsApp message to $phone_number. ";
                if (isset($response_data['error']['message'])) {
                    $error_message .= "API Error: " . $response_data['error']['message'];
                } else {
                    $error_message .= "Response: " . $response;
                }
                echo $error_message . "<br>";
                logMessage($conn, $sr_no, 'WhatsApp', $formatted_mobile, json_encode($whatsapp_data), 'Failed');
            }
        }
    }
}

// Extract database settings from the config file
$servername = $configs['Database']['db_host'];
$username = $configs['Database']['db_user'];
$password = $configs['Database']['db_password'];
$dbname = $configs['Database']['db_name'];

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$name = $sample = $metal_type = $sr_no = $mobile = $alt_mobile = $weight = "";
$total_karat = 0;
$count = 0; // Initialize count variable

// Fetch count of reports for today
$sql_count = "SELECT COUNT(*) AS today_count FROM test_reports WHERE report_date = CURDATE()";
$result_count = $conn->query($sql_count);
if ($result_count->num_rows > 0) {
    $row_count = $result_count->fetch_assoc();
    $count = $row_count['today_count']; // Get the total reports made today
}

// Calculate the current month and determine the Sr. No. prefix
// 1. Get the most recent receipt from the database to ensure consistency
$sql = "SELECT sr_no, report_date FROM receipts ORDER BY id DESC LIMIT 1";
$result = $conn->query($sql);

$current_letter = 'A'; // Default starting letter if database is empty
$customer_count = 1;   // Default starting number

if ($result && $result->num_rows > 0) {
    $last_receipt = $result->fetch_assoc();
    $last_sr_no = $last_receipt['sr_no'];
    $last_report_date = new DateTime($last_receipt['report_date']);
    $current_date = new DateTime();

    // Extract letter and number from the last sr_no
    list($last_letter, $last_number) = explode(' ', $last_sr_no);

    // 2. Check if the last receipt was in the same month and year
    if ($last_report_date->format('Y-m') == $current_date->format('Y-m')) {
        // Same month: Increment the number, keep the letter
        $current_letter = $last_letter;
        $customer_count = intval($last_number) + 1;
    } else {
        // New month: Increment the letter, reset number to 1
        if ($last_letter == 'Z') {
            $current_letter = 'A'; // Wrap around from Z to A
        } else {
            $current_letter = chr(ord($last_letter) + 1);
        }
        $customer_count = 1;
    }
} else {
    // This is the very first record in the database
    // The defaults 'A' and 1 are already set
}

// Generate the suggested Sr. No. for the form
$sr_no = $current_letter . " " . $customer_count;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['fetch_report'])) {
        $sr_no_letter = $_POST['sr_no_letter'];
        $sr_no_count = $_POST['sr_no_count'];
        $sr_no = $sr_no_letter . " " . $sr_no_count;

        // Fetch receipt data based on sr_no
        $sql = "SELECT name, mobile, alt_mobile, sample, metal_type, weight FROM receipts WHERE sr_no = '$sr_no'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $name = $row['name'];
            $mobile = isset($row['mobile']) ? $row['mobile'] : '';
            $alt_mobile = isset($row['alt_mobile']) ? $row['alt_mobile'] : '';
            $sample = $row['sample'];
            $metal_type = $row['metal_type'];
            $weight = $row['weight'];
        } else {
            $name = $sample = $metal_type = $weight = $mobile = $alt_mobile = "";
            echo "No receipt found with this Sr. No.";
        }
    }

    if (isset($_POST['submit_report'])) {
        // Retrieve form data
        $current_letter = mysqli_real_escape_string($conn, $_POST['sr_no_letter']);
        $customer_count = mysqli_real_escape_string($conn, $_POST['sr_no_count']);

        $sr_no = $current_letter . " " . $customer_count;
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $sample = mysqli_real_escape_string($conn, $_POST['sample']);
        $metal_type = mysqli_real_escape_string($conn, $_POST['metal_type']);
        $count = isset($_POST['count']) ? mysqli_real_escape_string($conn, $_POST['count']) : 0;
        $mobile = mysqli_real_escape_string($conn, $_POST['mobile']);
        $alt_mobile = mysqli_real_escape_string($conn, $_POST['alt_mobile']);
        $weight = mysqli_real_escape_string($conn, $_POST['weight']);
        $gold_percent = isset($_POST['gold_percent']) && is_numeric($_POST['gold_percent']) ? mysqli_real_escape_string($conn, $_POST['gold_percent']) : 0.00;
        $silver = !empty($_POST['silver']) ? mysqli_real_escape_string($conn, $_POST['silver']) : 0.00;
        $platinum = !empty($_POST['platinum']) ? mysqli_real_escape_string($conn, $_POST['platinum']) : 0.00;
        $zinc = !empty($_POST['zinc']) ? mysqli_real_escape_string($conn, $_POST['zinc']) : 0.00;
        $copper = !empty($_POST['copper']) ? mysqli_real_escape_string($conn, $_POST['copper']) : 0.00;
        $others = !empty($_POST['others']) ? mysqli_real_escape_string($conn, $_POST['others']) : 0.00;
        $rhodium = !empty($_POST['rhodium']) ? mysqli_real_escape_string($conn, $_POST['rhodium']) : 0.00;
        $iridium = !empty($_POST['iridium']) ? mysqli_real_escape_string($conn, $_POST['iridium']) : 0.00;
        $ruthenium = !empty($_POST['ruthenium']) ? mysqli_real_escape_string($conn, $_POST['ruthenium']) : 0.00;
        $palladium = !empty($_POST['palladium']) ? mysqli_real_escape_string($conn, $_POST['palladium']) : 0.00;
        $lead = !empty($_POST['lead']) ? mysqli_real_escape_string($conn, $_POST['lead']) : 0.00;
        $tin = !empty($_POST['tin']) ? mysqli_real_escape_string($conn, $_POST['tin']) : 0.00;
        $cadmium = !empty($_POST['cadmium']) ? mysqli_real_escape_string($conn, $_POST['cadmium']) : 0.00;
        $nickel = !empty($_POST['nickel']) ? mysqli_real_escape_string($conn, $_POST['nickel']) : 0.00;
        $total_karat = isset($_POST['total_karat']) ? mysqli_real_escape_string($conn, $_POST['total_karat']) : 0.00;

        // Check if the record already exists
        $check_sql = "SELECT * FROM test_reports WHERE sr_no = '$sr_no'";
        $check_result = $conn->query($check_sql);

        if ($check_result->num_rows > 0) {
            // Update existing record
            $update_sql = "UPDATE test_reports SET 
                `name` = '$name', 
                `sample` = '$sample', 
                `metal_type` = '$metal_type', 
                `count` = '$count', 
                `mobile` = '$mobile', 
                `alt_mobile` = '$alt_mobile', 
                `weight` = '$weight', 
                `gold_percent` = '$gold_percent', 
                `silver` = '$silver', 
                `platinum` = '$platinum', 
                `zinc` = '$zinc', 
                `copper` = '$copper', 
                `others` = '$others', 
                `rhodium` = '$rhodium', 
                `iridium` = '$iridium', 
                `ruthenium` = '$ruthenium', 
                `palladium` = '$palladium', 
                `lead` = '$lead', 
                `tin` = '$tin', 
                `cadmium` = '$cadmium', 
                `nickel` = '$nickel', 
                `total_karat` = '$total_karat' 
                WHERE `sr_no` = '$sr_no'";

            if (mysqli_query($conn, $update_sql)) {
                echo "Test report updated successfully!";
                $phone_numbers = [$mobile, $alt_mobile];
                sendMessages($configs, $phone_numbers, $name, $sr_no, $metal_type, $gold_percent, $total_karat, date('d-m-Y'), $sample, $conn, $silver, $platinum);
            } else {
                echo "Error updating record: " . mysqli_error($conn);
            }
        } else {
            // Insert new record
            $insert_sql = "INSERT INTO test_reports (
                `sr_no`, `report_date`, `name`, `sample`, `metal_type`, `count`, `mobile`, `alt_mobile`, `weight`, 
                `gold_percent`, `silver`, `platinum`, `zinc`, `copper`, `others`, `rhodium`, `iridium`, `ruthenium`, 
                `palladium`, `lead`, `tin`, `cadmium`, `nickel`, `total_karat`
            ) VALUES (
                '$sr_no', CURDATE(), '$name', '$sample', '$metal_type', '$count', '$mobile', '$alt_mobile', '$weight', 
                '$gold_percent', '$silver', '$platinum', '$zinc', '$copper', '$others', '$rhodium', '$iridium', 
                '$ruthenium', '$palladium', '$lead', '$tin', '$cadmium', '$nickel', '$total_karat'
            )";

            if (mysqli_query($conn, $insert_sql)) {
                echo "Test report saved successfully!";
                $phone_numbers = [$mobile, $alt_mobile];
                sendMessages($configs, $phone_numbers, $name, $sr_no, $metal_type, $gold_percent, $total_karat, date('d-m-Y'), $sample, $conn, $silver, $platinum);
            } else {
                echo "Error: " . $insert_sql . "<br>" . mysqli_error($conn);
            }
        }
    }
    if (isset($_POST['save_send_print'])) {
        // Retrieve form data
        $current_letter = mysqli_real_escape_string($conn, $_POST['sr_no_letter']);
        $customer_count = mysqli_real_escape_string($conn, $_POST['sr_no_count']);
    
        $sr_no = $current_letter . " " . $customer_count;
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $sample = mysqli_real_escape_string($conn, $_POST['sample']);
        $metal_type = mysqli_real_escape_string($conn, $_POST['metal_type']);
        $count = isset($_POST['count']) ? mysqli_real_escape_string($conn, $_POST['count']) : 0;
        $mobile = mysqli_real_escape_string($conn, $_POST['mobile']);
        $alt_mobile = mysqli_real_escape_string($conn, $_POST['alt_mobile']);
        $weight = mysqli_real_escape_string($conn, $_POST['weight']);
        $gold_percent = isset($_POST['gold_percent']) && is_numeric($_POST['gold_percent']) ? mysqli_real_escape_string($conn, $_POST['gold_percent']) : 0.00;        
        $silver = !empty($_POST['silver']) ? mysqli_real_escape_string($conn, $_POST['silver']) : 0.00;
        $platinum = !empty($_POST['platinum']) ? mysqli_real_escape_string($conn, $_POST['platinum']) : 0.00;
        $zinc = !empty($_POST['zinc']) ? mysqli_real_escape_string($conn, $_POST['zinc']) : 0.00;
        $copper = !empty($_POST['copper']) ? mysqli_real_escape_string($conn, $_POST['copper']) : 0.00;
        $others = !empty($_POST['others']) ? mysqli_real_escape_string($conn, $_POST['others']) : 0.00;
        $rhodium = !empty($_POST['rhodium']) ? mysqli_real_escape_string($conn, $_POST['rhodium']) : 0.00;
        $iridium = !empty($_POST['iridium']) ? mysqli_real_escape_string($conn, $_POST['iridium']) : 0.00;
        $ruthenium = !empty($_POST['ruthenium']) ? mysqli_real_escape_string($conn, $_POST['ruthenium']) : 0.00;
        $palladium = !empty($_POST['palladium']) ? mysqli_real_escape_string($conn, $_POST['palladium']) : 0.00;
        $lead = !empty($_POST['lead']) ? mysqli_real_escape_string($conn, $_POST['lead']) : 0.00;
        $tin = !empty($_POST['tin']) ? mysqli_real_escape_string($conn, $_POST['tin']) : 0.00;
        $cadmium = !empty($_POST['cadmium']) ? mysqli_real_escape_string($conn, $_POST['cadmium']) : 0.00;
        $nickel = !empty($_POST['nickel']) ? mysqli_real_escape_string($conn, $_POST['nickel']) : 0.00;
        $total_karat = isset($_POST['total_karat']) && is_numeric($_POST['total_karat']) ? mysqli_real_escape_string($conn, $_POST['total_karat']) : 0.00;        
    
        // Check if the record already exists
        $check_sql = "SELECT * FROM test_reports WHERE sr_no = '$sr_no'";
        $check_result = $conn->query($check_sql);
    
        if ($check_result->num_rows > 0) {
            // Update existing record
            $update_sql = "UPDATE test_reports SET 
                `name` = '$name', 
                `sample` = '$sample', 
                `metal_type` = '$metal_type', 
                `count` = '$count', 
                `mobile` = '$mobile', 
                `alt_mobile` = '$alt_mobile', 
                `weight` = '$weight', 
                `gold_percent` = '$gold_percent', 
                `silver` = '$silver', 
                `platinum` = '$platinum', 
                `zinc` = '$zinc', 
                `copper` = '$copper', 
                `others` = '$others', 
                `rhodium` = '$rhodium', 
                `iridium` = '$iridium', 
                `ruthenium` = '$ruthenium', 
                `palladium` = '$palladium', 
                `lead` = '$lead', 
                `tin` = '$tin', 
                `cadmium` = '$cadmium', 
                `nickel` = '$nickel', 
                `total_karat` = '$total_karat' 
                WHERE `sr_no` = '$sr_no'";
    
            if (mysqli_query($conn, $update_sql)) {
                echo "Test report updated successfully!";
                $phone_numbers = [$mobile, $alt_mobile];
                sendMessages($configs, $phone_numbers, $name, $sr_no, $metal_type, $gold_percent, $total_karat, date('d-m-Y'), $sample, $conn, $silver, $platinum);
            } else {
                echo "Error updating record: " . mysqli_error($conn);
            }
        } else {
            // Insert new record
            $insert_sql = "INSERT INTO test_reports (
                `sr_no`, `report_date`, `name`, `sample`, `metal_type`, `count`, `mobile`, `alt_mobile`, `weight`, 
                `gold_percent`, `silver`, `platinum`, `zinc`, `copper`, `others`, `rhodium`, `iridium`, `ruthenium`, 
                `palladium`, `lead`, `tin`, `cadmium`, `nickel`, `total_karat`
            ) VALUES (
                '$sr_no', CURDATE(), '$name', '$sample', '$metal_type', '$count', '$mobile', '$alt_mobile', '$weight', 
                '$gold_percent', '$silver', '$platinum', '$zinc', '$copper', '$others', '$rhodium', '$iridium', 
                '$ruthenium', '$palladium', '$lead', '$tin', '$cadmium', '$nickel', '$total_karat'
            )";
    
            if (mysqli_query($conn, $insert_sql)) {
                echo "Test report saved successfully!";
                $phone_numbers = [$mobile, $alt_mobile];
                sendMessages($configs, $phone_numbers, $name, $sr_no, $metal_type, $gold_percent, $total_karat, date('d-m-Y'), $sample, $conn, $silver, $platinum);
            } else {
                echo "Error: " . $insert_sql . "<br>" . mysqli_error($conn);
            }
        }
    
        // Redirect to print the receipt
        echo "
<script>
    console.log('Print script executed');
    window.addEventListener('load', function() {
        console.log('Page fully loaded');
        // Populate the receipt layout with the form data
        document.getElementById('printSrNo').textContent = '$sr_no';
        document.getElementById('printDate').textContent = '$currentDate $currentTime'; // Use PHP-generated date and time
        document.getElementById('printName').textContent = '$name';
        document.getElementById('printSample').textContent = '$sample';
        document.getElementById('printWeight').textContent = '$weight';
        document.getElementById('printGoldPercent').textContent = '$gold_percent';
        document.getElementById('printSilver').textContent = '$silver';
        document.getElementById('printPlatinum').textContent = '$platinum';
        document.getElementById('printZinc').textContent = '$zinc';
        document.getElementById('printCopper').textContent = '$copper';
        document.getElementById('printOthers').textContent = '$others';
        document.getElementById('printRhodium').textContent = '$rhodium';
        document.getElementById('printIridium').textContent = '$iridium';
        document.getElementById('printRuthenium').textContent = '$ruthenium';
        document.getElementById('printPalladium').textContent = '$palladium';
        document.getElementById('printLead').textContent = '$lead';
        document.getElementById('printTin').textContent = '$tin';
        document.getElementById('printCadmium').textContent = '$cadmium';
        document.getElementById('printNickel').textContent = '$nickel';
        document.getElementById('printTotalKarat').textContent = '$total_karat';

        console.log('Receipt div populated');
        // Show the receipt layout for printing
        var receiptContent = document.getElementById('receipt').innerHTML;

        // Check if Electron IPC Renderer is available
        if (window.electron && window.electron.ipcRenderer) {
            // Send the receipt content to the main process for printing
            window.electron.ipcRenderer.send('print-receipt', receiptContent);
        } else {
            // Fallback for non-Electron environments (e.g., browser)
            var printWindow = window.open('', '_blank', 'width=600,height=400');
            printWindow.document.write('<html><head><title>Receipt</title>');
            printWindow.document.write('<style>body { font-family: Arial, sans-serif; }</style>');
            printWindow.document.write('</head><body>');
            printWindow.document.write(receiptContent);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.focus();
            console.log('Printing receipt');
            printWindow.print();
            printWindow.close();
        }
    });
</script>
";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Bootstrap CSS -->
    <link href="vendor/assets/bootstrap.min.css" rel="stylesheet">
    <style>
    body {
        background-color: #e0e0e0;
        font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        font-size: 14px; /* Increased font size */
        margin: 0;
        padding: 0;
        height: 100vh; /* Full viewport height */
        overflow: hidden; /* Prevent body scrolling */
    }

    .form-container {
        width: 100%; /* Full width */
        height: 100vh; /* Full viewport height */
        background-color: #f4f4f4;
        padding: 10px; /* Adjusted padding */
        border: 1px solid #ccc;
        box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
        display: flex;
        flex-direction: column;
    }

    .form-header {
        background-color: #0078d7;
        color: white;
        padding: 10px; /* Adjusted padding */
        border-radius: 5px 5px 0 0;
        text-align: center;
        margin-bottom: 10px; /* Adjusted margin */
    }

    .form-content {
        flex: 1; /* Take up remaining space */
        overflow-y: auto; /* Enable scrolling within the form content */
        padding: 10px; /* Adjusted padding */
        margin-bottom: 10px; /* Add margin to prevent overlap with footer */
    }

    .form-group label {
        font-weight: bold;
        color: #333;
        font-size: 19px; /* Increased font size */
    }

    .form-control {
        height: 30px; /* Increased height */
        font-size: 19px; /* Increased font size */
        padding: 5px; /* Adjusted padding */
    }

    .btn-block {
        margin-top: 10px; /* Adjusted margin */
        padding: 8px; /* Adjusted padding */
        font-size: 14px; /* Increased font size */
    }

    .compact-input {
        width: 100px; /* Adjusted width */
        height: 30px; /* Increased height */
        font-size: 19px; /* Increased font size */
        padding: 5px; /* Adjusted padding */
    }

    .metal-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px; /* Adjusted gap */
        margin-bottom: 10px; /* Adjusted margin */
    }

    .metal-grid .form-group {
        margin-bottom: 0;
    }

    .info-bar-container {
        width: 100%;
        position: relative;
    }

    #infoBar {
        width: 100%;
        text-align: center;
        padding: 10px; /* Adjusted padding */
        border-radius: 5px;
        box-shadow: 0px 0px 5px rgba(0, 0, 0, 0.1);
        background-color: white;
        position: absolute;
        top: 0;
        left: 0;
        z-index: 1000;
        font-size: 14px; /* Increased font size */
    }

    .navbar {
        padding: 10px; /* Adjusted padding */
    }

    .navbar-brand {
        font-size: 18px; /* Increased font size */
    }

    .nav-link {
        font-size: 14px; /* Increased font size */
        padding: 10px; /* Adjusted padding */
    }

    .receipt-layout {
        margin: 0;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px; /* Increased font size */
    }

    .receipt-layout div {
        margin-bottom: 10px; /* Adjusted margin */
    }

    .receipt-layout strong {
        font-size: 16px; /* Increased font size */
    }

    /* Custom class for larger input */
    .larger-input {
        width: 150px; /* Adjusted width */
        height: 40px; /* Increased height */
        font-size: 16px; /* Increased font size */
        padding: 8px; /* Adjusted padding */
    }

    /* Ensure the form takes up the full height */
    html, body {
        height: 100%;
    }

    /* Make the form container stretch to full height */
    .form-container {
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    /* Adjust the footer buttons to stay at the bottom */
    .form-footer {
        margin-top: auto; /* Push to the bottom */
        padding: 10px;
        background-color: #f4f4f4;
        border-top: 1px solid #ccc;
        flex-shrink: 0; /* Prevent footer from shrinking */
    }
</style>
    <!-- JavaScript for calculating Karat Purity -->
    <script>
        function calculateKarat() {
            var weight = parseFloat(document.getElementById("weight").value);
            var gold_percent = parseFloat(document.getElementById("gold_percent").value);
            
            if (!isNaN(weight) && !isNaN(gold_percent) && weight > 0 && gold_percent > 0) {
                var total_karat = gold_percent * (24 / 100);
                document.getElementById("total_karat").value = total_karat.toFixed(2);
            } else {
                document.getElementById("total_karat").value = "0.00";
            }
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
    // Select all input elements
    const inputs = document.querySelectorAll('input');

    // Add focus event listener to each input
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.select(); // Select the text inside the input
        });
    });
});
</script>
</head>
<body class="testreportform-page">
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Check if the body has the class 'testreportform-page'
        if (document.body.classList.contains('testreportform-page')) {
            // Get the zoom level from PHP
            var zoomLevel = "<?php echo $zoomLevel; ?>";
            // Create a style element
            var style = document.createElement('style');
            style.type = 'text/css';

            
            // Define the custom zoom-out CSS
            var css = `
                body {
                    zoom: ${zoomLevel}%; /* Use the zoom level from config.json */
                }
            `;

            // Append the CSS to the style element
            if (style.styleSheet) {
                // This is required for IE8 and below
                style.styleSheet.cssText = css;
            } else {
                style.appendChild(document.createTextNode(css));
            }

            // Append the style element to the head
            document.head.appendChild(style);
        }
    });
    </script>
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
                <li class="nav-item active">
                    <a class="nav-link" href="testreportform.php">Test Report Page</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="reports.php">Reports Page</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="receipts.php">Receipts Page</a>
                </li>
                <li class="nav-item">
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

    <div class="form-container">
    <div class="form-header">
        <h4>Test Report Form</h4>
    </div>
    <div class="form-content">
        <form method="post">
            <input type="hidden" id="fetched_date" value="">
            <!-- Row for Count and Sr. No -->
            <div class="form-row">
                <div class="col-sm-6">
                    <div class="form-group ">
                        <label for="count">(Total Reports Today)</label>
                        <input type="number" style="width: 120px;" class="form-control" id="count" name="count" value="<?php echo $count; ?>" readonly>
                    </div>
                </div>
                <div class="metal-grid-container" style="border: 1px solid #000; padding: 1px; background: linear-gradient(135deg, #f0f0f0, #c0c0c0);">  
                <div class="col-sm-6">
    <div class="form-group">
        <label for="sr_no">Sr.no</label>
        <div class="d-flex align-items-center">
            <input type="text" class="form-control mr-2" id="sr_no_letter" name="sr_no_letter" style="width: 50px;" value="<?php echo $current_letter; ?>">
            <input type="number" class="form-control" id="sr_no_count" name="sr_no_count" placeholder="" style="width: 100px;">
        </div>
        <div class="info-bar-container">
            <div id="infoBar" class="alert alert-warning" style="display: none; margin-bottom: 15px;">
                Token number doesn't exist.
            </div>
        </div>
    </div>
</div></div></div>

            <!-- <button type="submit" class="btn btn-success btn-block" name="fetch_report">Fetch Report</button> -->

            <!-- Row for Pre-filled fields (Metal Type, Name, Mobile, Sample) -->
            <div class="metal-grid-container" style="border: 2px solid #000; padding: 10px; margin-top: 20px;">

            <div class="form-row">
                <div class="col-sm-6">
                    <div class="form-group">
                        <label for="metal_type">Metal Type</label>
                        <input type="text" class="form-control" id="metal_type" name="metal_type" value="<?php echo $metal_type; ?>" readonly>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo $name; ?>" readonly>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="col-sm-4">
                    <div class="form-group">
                        <label for="mobile">Mobile</label>
                        <input type="text" class="form-control" id="mobile" name="mobile" value="<?php echo $mobile; ?>" readonly>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        <label for="mobile">Alt. Mobile</label>
                        <input type="text" class="form-control" id="alt_mobile" name="alt_mobile" value="<?php echo $alt_mobile; ?>" readonly>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        <label for="sample">Sample</label>
                        <input type="text" class="form-control" id="sample" name="sample" value="<?php echo $sample; ?>" readonly>
                    </div>
                </div>
            </div>

            <!-- Row for Weight, Gold %, and Total Karat -->
            <div class="form-row">
    <div class="col-sm-4">
        <div class="form-group">
            <label for="weight">Weight</label>
            <input type="number" step="0.01" class="form-control compact-input" id="weight" name="weight" value="<?php echo $weight; ?>" oninput="calculateKarat()">
        </div>
    </div></br></br></br></br>
    <div class="col-sm-4">
    <div class="form-group">
        <label for="gold_percent">Gold % or Purity</label>
        <input type="number" step="0.01" class="form-control larger-input" id="gold_percent" name="gold_percent" oninput="calculateKarat()">
    </div>
</div>
    
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const copperInput = document.getElementById('copper');
    const metalInputs = document.querySelectorAll('.metal-input');
    const srNoCountInput = document.getElementById('sr_no_count');
    const infoBar = document.getElementById('infoBar');
    const goldPercentInput = document.getElementById('gold_percent');

    // Function to calculate copper value
    function calculateCopper() {
        let totalOtherMetals = 0;

        // Sum the values of all other metal inputs
        metalInputs.forEach(input => {
            if (input.value && !isNaN(input.value) && input.id !== 'copper') {
                totalOtherMetals += parseFloat(input.value);
            }
        });

          // Subtract the gold percentage from 100
          const goldPercent = goldPercentInput.value ? parseFloat(goldPercentInput.value) : 0;
        const totalMetals = totalOtherMetals + goldPercent;

        // Calculate copper value
        const copperValue = 100 - totalMetals;
        copperInput.value = copperValue.toFixed(2); // Set copper value with 2 decimal places
    }

    goldPercentInput.addEventListener('input', calculateCopper);

    // Add event listeners to all metal inputs
    metalInputs.forEach(input => {
        input.addEventListener('input', calculateCopper);
    });

    // Trigger calculation when sr_no_count changes
    srNoCountInput.addEventListener('input', function() {
        const srNoLetter = document.getElementById('sr_no_letter').value;
        const srNoCount = srNoCountInput.value;

        if (srNoCount) {
            fetchReportData(srNoLetter, srNoCount);
        } else {
            resetFormFields();
            infoBar.style.display = 'none'; // Hide the info bar
        }
    });

    // Function to fetch report data
    function fetchReportData(srNoLetter, srNoCount) {
        const srNo = srNoLetter + " " + srNoCount;

        fetch('fetch_report.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `sr_no=${encodeURIComponent(srNo)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update the form fields with the fetched data
                document.getElementById('metal_type').value = data.metal_type || '';
                document.getElementById('name').value = data.name || '';
                document.getElementById('mobile').value = data.mobile || '';
                document.getElementById('alt_mobile').value = data.alt_mobile || '';
                document.getElementById('sample').value = data.sample || '';
                document.getElementById('weight').value = data.weight || '';
                document.getElementById('gold_percent').value = data.gold_percent || '';
                document.getElementById('total_karat').value = data.total_karat || '';
                document.getElementById('silver').value = data.silver || '';
                document.getElementById('platinum').value = data.platinum || '';
                document.getElementById('zinc').value = data.zinc || '';
                document.getElementById('copper').value = data.copper || '';
                document.getElementById('others').value = data.others || '';
                document.getElementById('rhodium').value = data.rhodium || '';
                document.getElementById('iridium').value = data.iridium || '';
                document.getElementById('ruthenium').value = data.ruthenium || '';
                document.getElementById('palladium').value = data.palladium || '';
                document.getElementById('lead').value = data.lead || '';
                document.getElementById('tin').value = data.tin || '';
                document.getElementById('cadmium').value = data.cadmium || '';
                document.getElementById('nickel').value = data.nickel || '';

                // Calculate copper value after fields are populated
                calculateCopper();
                infoBar.style.display = 'none'; // Hide the info bar if record is found
            } else {
                resetFormFields();
                infoBar.style.display = 'block'; // Show the info bar if no record is found
            }
        })
        .catch(error => {
            console.error('Error fetching report data:', error);
            infoBar.style.display = 'block'; // Show the info bar if there's an error
        });
    }

    // Function to reset form fields
    function resetFormFields() {
        document.getElementById('metal_type').value = '';
        document.getElementById('name').value = '';
        document.getElementById('mobile').value = '';
        document.getElementById('alt_mobile').value = '';
        document.getElementById('sample').value = '';
        document.getElementById('weight').value = '';
        document.getElementById('gold_percent').value = '';
        document.getElementById('total_karat').value = '';
        document.getElementById('silver').value = '';
        document.getElementById('platinum').value = '';
        document.getElementById('zinc').value = '';
        document.getElementById('copper').value = '';
        document.getElementById('others').value = '';
        document.getElementById('rhodium').value = '';
        document.getElementById('iridium').value = '';
        document.getElementById('ruthenium').value = '';
        document.getElementById('palladium').value = '';
        document.getElementById('lead').value = '';
        document.getElementById('tin').value = '';
        document.getElementById('cadmium').value = '';
        document.getElementById('nickel').value = '';

        // Calculate copper value after resetting fields
        calculateCopper();
    }

    // Trigger calculation on page load
    calculateCopper();
});

document.addEventListener('DOMContentLoaded', function() {
    const srNoCountInput = document.getElementById('sr_no_count');
    const srNoLetterInput = document.getElementById('sr_no_letter');
    const editReportButton = document.getElementById('editReport');

    // Fetch data on keyup for sr_no_count
    srNoCountInput.addEventListener('keyup', function() {
        const srNoLetter = srNoLetterInput.value;
        const srNoCount = srNoCountInput.value;

        if (srNoCount) {
            fetchReportData(srNoLetter, srNoCount);
        }
    });

    // Fetch data for editing when Edit button is clicked
    editReportButton.addEventListener('click', function() {
        const srNoLetter = srNoLetterInput.value;
        const srNoCount = srNoCountInput.value;

        if (srNoCount) {
            fetchReportData(srNoLetter, srNoCount, true); // Pass true for editing
        } else {
            alert('Please enter a valid Sr. No. count.');
        }
    });

    function fetchReportData(srNoLetter, srNoCount, isEdit = false) {
        const srNo = srNoLetter + " " + srNoCount;

        fetch('pre_fetch_report.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `sr_no=${encodeURIComponent(srNo)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update the form fields with the fetched data
                document.getElementById('metal_type').value = data.metal_type || '';
                document.getElementById('name').value = data.name || '';
                document.getElementById('mobile').value = data.mobile || '';
                document.getElementById('alt_mobile').value = data.alt_mobile || '';
                document.getElementById('sample').value = data.sample || '';
                document.getElementById('weight').value = data.weight || '';
                document.getElementById('gold_percent').value = data.gold_percent || '';
                document.getElementById('total_karat').value = data.total_karat || '';
                document.getElementById('silver').value = data.silver || '';
                document.getElementById('platinum').value = data.platinum || '';
                document.getElementById('zinc').value = data.zinc || '';
                document.getElementById('copper').value = data.copper || '';
                document.getElementById('others').value = data.others || '';
                document.getElementById('rhodium').value = data.rhodium || '';
                document.getElementById('iridium').value = data.iridium || '';
                document.getElementById('ruthenium').value = data.ruthenium || '';
                document.getElementById('palladium').value = data.palladium || '';
                document.getElementById('lead').value = data.lead || '';
                document.getElementById('tin').value = data.tin || '';
                document.getElementById('cadmium').value = data.cadmium || '';
                document.getElementById('nickel').value = data.nickel || '';
                document.getElementById('fetched_date').value = data.report_date || ''; 
                if (isEdit) {
                    // Enable all fields for editing
                    document.querySelectorAll('input').forEach(input => {
                        input.readOnly = false;
                    });
                }
            } else {
                console.error('No receipt found with this Sr. No.', error);
                // Clear the hidden date field if the report is not found
                document.getElementById('fetched_date').value = '';
            }
        })
        .catch(error => {
            console.error('Error fetching report data:', error);
        });
    }
});
</script>
<script>
    // Function to format metal values to two decimal places
    function formatMetalValue(input) {
            let value = input.value.trim();
            if (value === "") {
                input.value = "0.00";
            } else {
                // Ensure the value is a number and format it to two decimal places
                let num = parseFloat(value);
                if (!isNaN(num)) {
                    input.value = num.toFixed(2);
                } else {
                    input.value = "0.00";
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Select all input fields that need formatting
            const metalInputs = document.querySelectorAll('.metal-input');

            // Add blur event listener to each input field
            metalInputs.forEach(input => {
                input.addEventListener('blur', function() {
                    formatMetalValue(this);
                });
            });
            // Focus on the sr_no_count input box on page load
            const srNoCountInput = document.getElementById('sr_no_count');
            if (srNoCountInput) {
                srNoCountInput.focus();
            }

            // Select all input elements
            const inputs = document.querySelectorAll('input');

            // Add focus event listener to each input
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.select(); // Select the text inside the input
                });
            });
        });
    </script>
<!-- Grid for Optional Metal Fields -->
<div class="metal-grid-container" style="border: 2px solid #000; padding: 10px; margin-top: 20px; font-size: 20px;">
    <div class="metal-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px;">
        <!-- Silver -->
        <div class="form-group">
            <label for="silver">Silver</label>
            <input type="number" step="0.01" class="form-control metal-input compact-input" id="silver" name="silver">
        </div>
          <!-- Zinc -->
          <div class="form-group">
            <label for="zinc">Zinc</label>
            <input type="number" step="0.01" class="form-control metal-input compact-input" id="zinc" name="zinc">
        </div>
        <!-- Cadmium -->
        <div class="form-group">
            <label for="cadmium">Cadmium</label>
            <input type="number" step="0.01" class="form-control metal-input compact-input" id="cadmium" name="cadmium">
        </div>  
        <!-- Nickel -->
        <div class="form-group">
            <label for="nickel">Nickel</label>
            <input type="number" step="0.01" class="form-control metal-input compact-input" id="nickel" name="nickel">
        </div>
        <!-- Iridium -->
        <div class="form-group">
            <label for="iridium">Iridium</label>
            <input type="number" step="0.01" class="form-control metal-input compact-input" id="iridium" name="iridium">
        </div>
         <!-- Ruthenium -->
         <div class="form-group">
            <label for="ruthenium">Ruthenium</label>
            <input type="number" step="0.01" class="form-control metal-input compact-input" id="ruthenium" name="ruthenium">
        </div>
        <!-- Platinum -->
        <div class="form-group">
            <label for="platinum">Platinum</label>
            <input type="number" step="0.01" class="form-control metal-input compact-input" id="platinum" name="platinum">
        </div>
      
        <!-- Tin -->
        <div class="form-group">
            <label for="tin">Tin</label>
            <input type="number" step="0.01" class="form-control metal-input compact-input" id="tin" name="tin">
        </div>
        <!-- Rhodium -->
        <div class="form-group">
            <label for="rhodium">Rhodium</label>
            <input type="number" step="0.01" class="form-control metal-input compact-input" id="rhodium" name="rhodium">
        </div>
      
        <!-- Palladium -->
        <div class="form-group">
            <label for="palladium">Palladium</label>
            <input type="number" step="0.01" class="form-control metal-input compact-input" id="palladium" name="palladium">
        </div>
       
        <!-- Lead -->
        <div class="form-group">
            <label for="lead">Lead</label>
            <input type="number" step="0.01" class="form-control metal-input compact-input" id="lead" name="lead">
        </div>
        <!-- Copper -->
        <div class="form-group">
            <label for="copper">Copper</label>
            <input type="number" step="0.01" class="form-control compact-input" id="copper" name="copper">
        </div>
        <!-- Others -->
        <div class="form-group">
            <label for="others">Others</label>
            <input type="number" step="0.01" class="form-control metal-input compact-input" id="others" name="others">
        </div>
        <!-- Total Karat -->
        <div class="form-group" style="grid-column: span 3; display: flex; flex-direction: column; align-items: center;">
            <label for="total_karat">Total Karat</label>
            <input type="text" class="form-control compact-input" id="total_karat" name="total_karat" value="<?php echo number_format($total_karat, 2); ?>" readonly style="width: 200px; font-size: 20px; text-align: center;">
        </div>
    </div>
</div>
            
           <!-- Prominent Save, Send & Print Button -->
<button type="submit" class="btn btn-danger btn-block" name="save_send_print" id="save_send_print" style="margin-bottom: 10px;">Save, Send & Print</button>
<!-- Smaller Buttons Below -->
<div class="row">
    <div class="col-6">
        <button type="button" class="btn btn-info btn-block" id="savePrintBtn">Print Receipt Only</button>
    </div>
    <div class="col-6">
        <button type="submit" class="btn btn-info btn-block" name="submit_report">Save & Send only</button>
    </div>
</div><br><br>
        </form></div>
    </div>
</body>
</html>
<!-- Hidden receipt layout for printing -->
<div id="receipt" class="receipt-layout"  style="margin: 0; padding: 0; display: flex; align-items: center; justify-content: center;">
<div style="margin-top:12.5%;width:88%;transform:skewY(-1deg);font-family: Arial, sans-serif;">
        <div style="display:flex;justify-content:space-between;margin-bottom:10px;">
            <div style="font-size:18px;text-align:center;width:28%;text-transform: uppercase;"><strong><span id="printSrNo"></span></strong></div>
            <div style="font-size:12px;text-align:center;width:33%;"><span id="printWeight"></span></div>
            <div style="font-size:12px;text-align:right;width:33%;"><span id="printDate"></span></div>
        </div>
        <div style="display:flex;justify-content:space-between;margin-bottom:8px;margin-top: 2.2%;">
            <div style="font-size:15px;text-align:center;width:38%;text-transform: uppercase;"><strong><span id="printName"></span></strong></div>
            <div style="font-size:12px;text-align:right;width:30%;text-transform: uppercase;"><span id="printSample"></span></div>
            <div style="font-size:12px;text-align:center;width:31%;">&nbsp;</div>
        </div>
        <div style="display:flex;justify-content:space-between;margin-bottom:13px;margin-top: 15px;">
            <div style="font-size:12px;text-align:right;width:33%;">&nbsp;</div>
            <div style="font-size:12px;text-align:right;width:33%;">&nbsp;</div>
            <div style="font-size:12px;text-align:right;width:33%;margin-right: 20px;"><span id="printPlatinum"></span></div>
        </div>
        <div style="display:flex;justify-content:space-between;margin-bottom:10px;">
            <div style="font-size:18px;text-align:right;width:37%;"><strong><span id="printGoldPercent"></span></strong></div>
            <div style="font-size:12px;text-align:right;width:33%;">&nbsp;</div>
            <div style="font-size:18px;text-align:left;width:23%;"><strong><span id="printTotalKarat"></span></strong></div>
        </div>
        <div style="display:flex;justify-content:space-between;margin-top: 16px;">
            <div style="float:right;font-size:12px;width:33%;">
                <div style="font-size:12px;text-align:right;width:100%;"><span id="printSilver"></div>
                <div style="font-size:12px;text-align:right;width:100%;"><span id="printCopper"></span></div>
                <div style="font-size:12px;text-align:right;width:100%;"><span id="printZinc"></span></div>
                <div style="font-size:12px;text-align:right;width:100%;"><span id="printCadmium"></div>
            </div>
            <div style="float:right;font-size:12px;width:33%;">
                <div style="font-size:12px;text-align:right;width:100%;"><span id="printNickel"></div>
                <div style="font-size:12px;text-align:right;width:100%;"><span id="printIridium"></span></div>
                <div style="font-size:12px;text-align:right;width:100%;"><span id="printTin"></div>
                <div style="font-size:12px;text-align:right;width:100%;"><span id="printPalladium"></span></div>
            </div>
            <div style="float:right;font-size:12px;width:33%;margin-right: 20px;">
                <div style="font-size:12px;text-align:right;width:100%;"><span id="printRuthenium"></span></div>
                <div style="font-size:12px;text-align:right;width:100%;"><span id="printRhodium"></span></div>
                <div style="font-size:12px;text-align:right;width:100%;"><span id="printLead"></span></div>
                <div style="font-size:12px;text-align:right;width:100%;"><span id="printOthers"></span></div>
            </div>
        </div>
    </div>   
</div>

<script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('testReportForm');
            const saveSendPrintButton = document.getElementById('save_send_print');

            form.addEventListener('submit', function(event) {
                // Disable the submit button to prevent multiple submissions
                saveSendPrintButton.disabled = true;
                saveSendPrintButton.textContent = 'Processing...';

                // Optionally, you can add a delay to re-enable the button after a certain time
                setTimeout(function() {
                    saveSendPrintButton.disabled = false;
                    saveSendPrintButton.textContent = 'Save, Send & Print';
                }, 5000); // Re-enable after 5 seconds
            });
        });
    </script>
<script>
document.getElementById('savePrintBtn').addEventListener('click', function() {
    // Collect form data
    var current_letter = document.getElementById('sr_no_letter').value.toUpperCase();
    var customer_count = document.getElementById('sr_no_count').value.toUpperCase();
    var srNo = current_letter + " " + customer_count;

    // --- FIX: USE FETCHED DATE FOR REPRINTING ---
    // This logic now checks for a date from a fetched report.
    // If no report was fetched, it defaults to the current date and time.
    var fetchedDateValue = document.getElementById('fetched_date').value;
    var dateTimeString;

    if (fetchedDateValue) {
        // A report was fetched. Use its date.
        const dateParts = fetchedDateValue.split('-'); // Fetched date is YYYY-MM-DD
        const formattedDate = `${dateParts[2]}-${dateParts[1]}-${dateParts[0]}`; // Convert to DD-MM-YYYY
        
        // Use a placeholder time for reprints, as time isn't stored.
        dateTimeString = formattedDate + ' 12:00:00 PM'; 
    } else {
        // No report fetched, so it's a new one. Use current date and time.
        dateTimeString = "<?php echo $currentDate . ' ' . $currentTime; ?>";
    }
    // --- END OF FIX ---
    
    var name = document.getElementById('name').value.toUpperCase() || '';
    var mobile = document.getElementById('mobile').value  || '';
    var sample = document.getElementById('sample').value || '';
    var weight = document.getElementById('weight').value || '0';
    var metalType = document.getElementById('metal_type').value;
    var goldPercent = document.getElementById('gold_percent').value || '0';
    var silver = document.getElementById('silver').value || '0';
    var platinum = document.getElementById('platinum').value || '0';
    var zinc = document.getElementById('zinc').value || '0';
    var copper = document.getElementById('copper').value || '0';
    var others = document.getElementById('others').value || '0';
    var rhodium = document.getElementById('rhodium').value || '0';;
    var iridium = document.getElementById('iridium').value || '0';
    var ruthenium = document.getElementById('ruthenium').value || '0';
    var palladium = document.getElementById('palladium').value || '0';
    var lead = document.getElementById('lead').value || '0';
    var tin = document.getElementById('tin').value || '0';
    var cadmium = document.getElementById('cadmium').value || '0';
    var nickel = document.getElementById('nickel').value || '0';
    var totalKarat = document.getElementById('total_karat').value || '0';

    // Populate the receipt layout
    document.getElementById('printSrNo').textContent = srNo;
    document.getElementById('printDate').textContent = dateTimeString;
    document.getElementById('printName').textContent = name;
    // document.getElementById('printMobile').textContent = mobile;
    document.getElementById('printSample').textContent = sample;
    document.getElementById('printWeight').textContent = weight;
    // document.getElementById('printMetalType').textContent = metalType;
    document.getElementById('printGoldPercent').textContent = goldPercent;
     document.getElementById('printSilver').textContent = silver;
    document.getElementById('printPlatinum').textContent = platinum;
    document.getElementById('printZinc').textContent = zinc;
    document.getElementById('printCopper').textContent = copper;
    document.getElementById('printOthers').textContent = others;
    document.getElementById('printRhodium').textContent = rhodium;
    document.getElementById('printIridium').textContent = iridium;
    document.getElementById('printRuthenium').textContent = ruthenium;
    document.getElementById('printPalladium').textContent = palladium;
    document.getElementById('printLead').textContent = lead;
    document.getElementById('printTin').textContent = tin;
    document.getElementById('printCadmium').textContent = cadmium;
    document.getElementById('printNickel').textContent = nickel;
    document.getElementById('printTotalKarat').textContent = totalKarat;

    // Show the receipt layout for printing
    var receiptContent = document.getElementById('receipt').innerHTML;

// Check if Electron IPC Renderer is available
if (window.electron && window.electron.ipcRenderer) {
        // Send the receipt content to the main process for printing
        window.electron.ipcRenderer.send('print-receipt', receiptContent);
    } else {
        // Fallback for non-Electron environments (e.g., browser)
        var printWindow = window.open('', '_blank', 'width=600,height=400');
        printWindow.document.write('<html><head><title>Receipt</title>');
        printWindow.document.write('<style>body { font-family: Arial, sans-serif; }</style>');
        printWindow.document.write('</head><body>');
        printWindow.document.write(receiptContent);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    }
});

</script>
<script>
    // Function to move focus to the next input element when "Enter" is pressed
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            // Find the currently focused input element
            let currentElement = document.activeElement;

            // Check if the current element is an input or textarea
            if (currentElement.tagName === 'INPUT' || currentElement.tagName === 'TEXTAREA') {
                // Find the next input element
                let nextElement = getNextInput(currentElement);

                // If there is a next input element, focus on it
                if (nextElement) {
                    nextElement.focus();
                    e.preventDefault(); // Prevent form submission on Enter
                }
            }
        }
    });

    // Function to get the next input element in the form
    function getNextInput(currentElement) {
        let formElements = Array.from(currentElement.form.elements);
        let currentIndex = formElements.indexOf(currentElement);

        // Loop through the form elements to find the next editable input
        for (let i = currentIndex + 1; i < formElements.length; i++) {
            let nextElement = formElements[i];

            // Skip read-only, disabled, and the weight input field
            if ((nextElement.tagName === 'INPUT' || nextElement.tagName === 'TEXTAREA') && 
                !nextElement.readOnly && !nextElement.disabled && nextElement.id !== 'weight') {
                return nextElement;
            }
        }

        // Return null if no editable input is found
        return null;
    }

    // Focus on the sr_no_count input box on page load
    document.addEventListener('DOMContentLoaded', function() {
        const srNoCountInput = document.getElementById('sr_no_count');
        if (srNoCountInput) {
            srNoCountInput.focus();
        }

        // Select all input elements
        const inputs = document.querySelectorAll('input');

        // Add focus event listener to each input
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.select(); // Select the text inside the input
            });
        });
    });

    
</script>

<!-- jQuery and Bootstrap Bundle (includes Popper) --> 
<script src="vendor/assets/jquery-3.5.1.slim.min.js"></script>
<script src="vendor/assets/bootstrap.bundle.min.js"></script>

</body>
</html>