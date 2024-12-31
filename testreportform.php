<?php
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

// Twilio API setup
require_once 'vendor/autoload.php'; // Adjust to the location of Twilio SDK

use Twilio\Rest\Client;

// Path to the config file
$configFile = 'config.json';

// Load configuration from the JSON file
$configs = json_decode(file_get_contents($configFile), true);

// Toggle to enable or disable Twilio usage
$use_twilio = true; // Set to false to disable Twilio functionality

// Load Twilio credentials from the config file
$twilio_sid = $configs['Twilio']['twilio_sid'];
$twilio_token = $configs['Twilio']['twilio_token'];
$twilio_phone_number = $configs['Twilio']['twilio_phone_number']; // Your Twilio phone number (for SMS & WhatsApp)

// Check if Twilio credentials are available and usage is enabled
$twilio_available = $use_twilio && !empty($twilio_sid) && !empty($twilio_token) && !empty($twilio_phone_number);

// Twilio Client initialization (only if credentials are available and toggle is enabled)
if ($twilio_available) {
    $client = new Client($twilio_sid, $twilio_token);
    // echo "Twilio functionality is enabled.<br>";
} elseif ($use_twilio) {
    echo "Twilio credentials are missing. SMS/WhatsApp features will not work.<br>";
} else {
   // echo "Twilio functionality is disabled.<br>";
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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['fetch_report'])) {
        $sr_no = $_POST['sr_no'];
        
        // Fetch receipt data based on sr_no
        $sql = "SELECT name, mobile, alt_mobile, sample, metal_type, weight FROM receipts WHERE sr_no = '$sr_no'";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $name = $row['name'];
            $mobile = $row['mobile'];
            $alt_mobile = isset($row['alt_mobile']) ? $row['alt_mobile'] : '';
            $sample = $row['sample'];
            $metal_type = $row['metal_type'];
            $weight = $row['weight'];
        } else {
            echo "No receipt found with this Sr. No.";
        }
    }

    if (isset($_POST['submit_report'])) {
        // Retrieve form data safely with default values for missing fields
    $sr_no = mysqli_real_escape_string($conn, $_POST['sr_no']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $sample = mysqli_real_escape_string($conn, $_POST['sample']);
    $metal_type = mysqli_real_escape_string($conn, $_POST['metal_type']);
    $count = isset($_POST['count']) ? mysqli_real_escape_string($conn, $_POST['count']) : 0;
    $mobile = mysqli_real_escape_string($conn, $_POST['mobile']);
    $alt_mobile = mysqli_real_escape_string($conn, $_POST['alt_mobile']);
    $weight = mysqli_real_escape_string($conn, $_POST['weight']);
    $gold_percent = isset($_POST['gold_percent']) ? mysqli_real_escape_string($conn, $_POST['gold_percent']) : 0.00;
        
        // Handle optional metals with default values
    $silver = isset($_POST['silver']) && $_POST['silver'] !== '' ? mysqli_real_escape_string($conn, $_POST['silver']) : 0.00;
    $platinum = isset($_POST['platinum']) && $_POST['platinum'] !== '' ? mysqli_real_escape_string($conn, $_POST['platinum']) : 0.00;
    $zinc = isset($_POST['zinc']) && $_POST['zinc'] !== '' ? mysqli_real_escape_string($conn, $_POST['zinc']) : 0.00;
    $copper = isset($_POST['copper']) && $_POST['copper'] !== '' ? mysqli_real_escape_string($conn, $_POST['copper']) : 0.00;
    $others = isset($_POST['others']) && $_POST['others'] !== '' ? mysqli_real_escape_string($conn, $_POST['others']) : 0.00;
    $rhodium = isset($_POST['rhodium']) && $_POST['rhodium'] !== '' ? mysqli_real_escape_string($conn, $_POST['rhodium']) : 0.00;
    $iridium = isset($_POST['iridium']) && $_POST['iridium'] !== '' ? mysqli_real_escape_string($conn, $_POST['iridium']) : 0.00;
    $ruthenium = isset($_POST['ruthenium']) && $_POST['ruthenium'] !== '' ? mysqli_real_escape_string($conn, $_POST['ruthenium']) : 0.00;
    $palladium = isset($_POST['palladium']) && $_POST['palladium'] !== '' ? mysqli_real_escape_string($conn, $_POST['palladium']) : 0.00;
    $lead = isset($_POST['lead']) && $_POST['lead'] !== '' ? mysqli_real_escape_string($conn, $_POST['lead']) : 0.00;
    
    $total_karat = isset($_POST['total_karat']) && $_POST['total_karat'] !== '' ? mysqli_real_escape_string($conn, $_POST['total_karat']) : 0.00;

        // SQL query to insert into the database
    $sql = "INSERT INTO test_reports (`sr_no`, `report_date`, `name`, `sample`, `metal_type`, `count`, `mobile`, `alt_mobile`, `weight`, `gold_percent`, `silver`, `platinum`, `zinc`, `copper`, `others`, `rhodium`, `iridium`, `ruthenium`, `palladium`, `lead`, `total_karat`) 
    VALUES ('$sr_no', CURDATE(), '$name', '$sample', '$metal_type', '$count', '$mobile', '$alt_mobile', '$weight', '$gold_percent', '$silver', '$platinum', '$zinc', '$copper', '$others', '$rhodium', '$iridium', '$ruthenium', '$palladium', '$lead', '$total_karat')";
        
        // Output the SQL query for debugging purposes
       // var_dump($sql);

    if (mysqli_query($conn, $sql)) {
        echo "Test report saved successfully!";

            $karat_value = (strtolower($metal_type) == 'gold') ? $total_karat : 'N/A'; 

            // Get the current date

            $current_date = date('d-m-Y');

            // Send SMS and WhatsApp

            // Combine both mobile numbers into an array
            $phone_numbers = [$mobile, $alt_mobile];

            if ($twilio_available) {
            $message = "Your Test Results:\n\nName: $name\nToken. No: $sr_no\nType: $metal_type\nPurity: $gold_percent%\nCarat: $karat_value\n\nThank You.\n\n- National Gold Testing, Thrissur. \nFor any doubt/Clarification please call our office 8921243476,6282479875";

           // Send SMS
           foreach ($phone_numbers as $phone_number) {
            if (!empty($phone_number)) {
                // Send SMS
                try {
                    $client->messages->create(
                        $phone_number,
                        [
                            'from' => $twilio_phone_number,
                            'body' => $message
                        ]
                    );
                    echo "SMS sent successfully to $phone_number!<br>";
                } catch (Exception $e) {
                    echo "Error sending SMS to $phone_number: " . $e->getMessage() . "<br>";
                }
            }
        }
         // Determine the karat value based on metal type
    $karat_value = (strtolower($metal_type) == 'gold') ? $total_karat : 'N/A';       
        // Send WhatsApp message via the WhatsApp Business API
        $whatsapp_api_url = $configs['WhatsApp']['whatsapp_api_url'];
        $whatsapp_number = $configs['WhatsApp']['whatsapp_number'];
        $access_token = $configs['WhatsApp']['access_token']; // Facebook access token for WhatsApp API
        $whatsapp_url = $whatsapp_api_url . $whatsapp_number . '/messages';
        // Format the phone number into E.164 format
    $formatted_mobile = preg_replace('/\D/', '', $mobile);  // Remove non-digits
    if (strlen($formatted_mobile) == 10) {
        $formatted_mobile = '+91' . $formatted_mobile; // Adjust according to your country code
    }

    // Prepare the message data for the WhatsApp template
    $access_token = $configs['WhatsApp']['access_token']; // Facebook access token for WhatsApp API
    $whatsapp_data = [
        'messaging_product' => 'whatsapp',
        'to' => $formatted_mobile,
        'type' => 'template',
        'template' => [
            'name' => 'testreportssoftware',  // Your template name
            'language' => ['code' => 'en_US'],  // Language code for the template
            'components' => [
                [
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => $name],           // {{1}} - Name
                        ['type' => 'text', 'text' => $sr_no],          // {{2}} - Token Number
                        ['type' => 'text', 'text' => $current_date],    // {{3}} - Date
                        ['type' => 'text', 'text' => $sample],          // {{4}} - Sample
                        ['type' => 'text', 'text' => $metal_type],      // {{5}} - Metal Type
                        ['type' => 'text', 'text' => $gold_percent],    // {{6}} - Result (Gold %)
                        ['type' => 'text', 'text' => $karat_value]      // {{7}} - Total Karat
                    ]
                ]
            ]
        ]
    ];

    // Make the HTTP POST request to WhatsApp API using cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $whatsapp_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($whatsapp_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);  // Get the HTTP response code
    curl_close($ch);

    // Check the response from WhatsApp API
    if ($http_code == 200) {
        echo "WhatsApp message sent successfully!";
        } else {
            echo "Error sending WhatsApp message. Response: " . $response;
        }
    } else {
        echo "SMS and WhatsApp messages were not sent because Twilio is disabled.<br>";
    }
} else {
    echo "Error: " . $sql . "<br>" . mysqli_error($conn);
}
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
        }
        .form-container {
            max-width: 100%;
            margin: 50px auto;
            background-color: #f4f4f4;
            padding: 20px;
            border: 1px solid #ccc;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        .form-header {
            background-color: #0078d7;
            color: white;
            padding: 10px;
            border-radius: 5px 5px 0 0;
            text-align: center;
            margin-bottom: 20px;
        }
        .form-group label {
            font-weight: bold;
            color: #333;
        }
        .btn-primary, .btn-success {
            background-color: #0078d7;
            border-color: #0078d7;
        }
        .btn-primary:hover, .btn-success:hover {
            background-color: #005fa3;
            border-color: #005fa3;
        }
        .btn-secondary {
            background-color: #f0f0f0;
            color: #333;
            border-color: #ccc;
        }
        .btn-secondary:hover {
            background-color: #e0e0e0;
        }
        /* Style for print layout */
        .receipt-layout {
            display: none; /* Hidden initially */
        }
        .form-row {
            margin-bottom: 15px;
        }
        .form-control {
            height: 35px;
        }
        .btn-block {
            margin-top: 10px;
        }
    </style>


    <!-- JavaScript for calculating Karat Purity -->
<script>
    function calculateKarat() {
        var weight = parseFloat(document.getElementById("weight").value);
        var gold_percent = parseFloat(document.getElementById("gold_percent").value);
        
        // Ensure the inputs are valid numbers
        if (!isNaN(weight) && !isNaN(gold_percent) && weight > 0 && gold_percent > 0) {
            var total_karat = gold_percent * (24 / 100);  // Calculate total karat based on gold percent
            document.getElementById("total_karat").value = total_karat.toFixed(2); // Display result with 2 decimals
        } else {
            document.getElementById("total_karat").value = "0.00"; // Default value if inputs are not valid
        }
    }
</script>


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
        <form method="post">
            <!-- Row for Count and Sr. No -->
            <div class="form-row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="count">(Total Reports Today)</label>
                        <input type="number" class="form-control" id="count" name="count" value="<?php echo $count; ?>" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="sr_no">Sr. No</label>
                        <input type="text" class="form-control" id="sr_no" name="sr_no" value="<?php echo $sr_no; ?>" required>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-success btn-block" name="fetch_report">Fetch Report</button>

            <!-- Row for Pre-filled fields (Metal Type, Name, Mobile, Sample) -->
            <div class="form-row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="metal_type">Metal Type</label>
                        <input type="text" class="form-control" id="metal_type" name="metal_type" value="<?php echo $metal_type; ?>" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo $name; ?>" readonly>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="mobile">Mobile</label>
                        <input type="text" class="form-control" id="mobile" name="mobile" value="<?php echo $mobile; ?>" readonly>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="mobile">Alternative Mobile Number</label>
                        <input type="text" class="form-control" id="alt_mobile" name="alt_mobile" value="<?php echo $alt_mobile; ?>" readonly>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="sample">Sample</label>
                        <input type="text" class="form-control" id="sample" name="sample" value="<?php echo $sample; ?>" readonly>
                    </div>
                </div>
            </div>

            <!-- Row for Weight, Gold %, and Total Karat -->
            <div class="form-row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="weight">Weight</label>
                        <input type="number" step="0.01" class="form-control" id="weight" name="weight" value="<?php echo $weight; ?>" oninput="calculateKarat()">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="gold_percent">Gold % or Purity</label>
                        <input type="number" step="0.01" class="form-control" id="gold_percent" name="gold_percent" oninput="calculateKarat()">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="total_karat">Total Karat</label>
                        <input type="text" class="form-control" id="total_karat" name="total_karat" value="<?php echo number_format($total_karat, 2); ?>" readonly>
                    </div>
                </div>
            </div>

            <!-- Row for Optional Metal Fields -->
            <div class="form-row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="silver">Silver</label>
                        <input type="number" step="0.01" class="form-control" id="silver" name="silver">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="platinum">Platinum</label>
                        <input type="number" step="0.01" class="form-control" id="platinum" name="platinum">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="zinc">Zinc</label>
                        <input type="number" step="0.01" class="form-control" id="zinc" name="zinc">
                    </div>
                </div>
            </div>

            <!-- Row for More Optional Metals -->
            <div class="form-row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="copper">Copper</label>
                        <input type="number" step="0.01" class="form-control" id="copper" name="copper">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="lead">Lead</label>
                        <input type="number" step="0.01" class="form-control" id="lead" name="lead">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="rhodium">Rhodium</label>
                        <input type="number" step="0.01" class="form-control" id="rhodium" name="rhodium">
                    </div>
                </div>
            </div>

            <!-- Row for Final Metals -->
            <div class="form-row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="iridium">Iridium</label>
                        <input type="number" step="0.01" class="form-control" id="iridium" name="iridium">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="ruthenium">Ruthenium</label>
                        <input type="number" step="0.01" class="form-control" id="ruthenium" name="ruthenium">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="palladium">Palladium</label>
                        <input type="number" step="0.01" class="form-control" id="palladium" name="palladium">
                    </div>
                </div>
            </div>

        <div class="form-group">
            <label for="others">Others</label>
            <input type="number" step="0.01" class="form-control" id="others" name="others">
        </div>


        

        <button type="button" class="btn btn-success btn-block" id="savePrintBtn">Print Receipt Only</button>
        <button type="submit" class="btn btn-primary btn-block" name="submit_report">Save Report & Send SMS & WhatsApp</button>
    </form>
</div>
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
                <div style="font-size:12px;text-align:right;width:100%;">0</div>
                <div style="font-size:12px;text-align:right;width:100%;"><span id="printCopper"></span></div>
                <div style="font-size:12px;text-align:right;width:100%;"><span id="printZinc"></span></div>
                <div style="font-size:12px;text-align:right;width:100%;">0</div>
            </div>
            <div style="float:right;font-size:12px;width:33%;">
                <div style="font-size:12px;text-align:right;width:100%;">0</div>
                <div style="font-size:12px;text-align:right;width:100%;"><span id="printIridium"></span></div>
                <div style="font-size:12px;text-align:right;width:100%;">0</div>
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
document.getElementById('savePrintBtn').addEventListener('click', function() {
    // Collect form data
    var srNo = document.getElementById('sr_no').value.toUpperCase();
     // Get today's date
     var today = new Date();
       // Format date and time
       var dateString = today.toLocaleDateString();  // This gets the date in a localized format
    var timeString = today.toLocaleTimeString();  // This gets the time in a localized format
    
    // Combine date and time into a single string
    var dateTimeString = dateString + ' ' + timeString;
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
    // document.getElementById('printSilver').textContent = silver;
    document.getElementById('printPlatinum').textContent = platinum;
    document.getElementById('printZinc').textContent = zinc;
    document.getElementById('printCopper').textContent = copper;
    document.getElementById('printOthers').textContent = others;
    document.getElementById('printRhodium').textContent = rhodium;
    document.getElementById('printIridium').textContent = iridium;
    document.getElementById('printRuthenium').textContent = ruthenium;
    document.getElementById('printPalladium').textContent = palladium;
    document.getElementById('printLead').textContent = lead;
    document.getElementById('printTotalKarat').textContent = totalKarat;

    // Show the receipt layout for printing
    var receiptContent = document.getElementById('receipt').innerHTML;

    // Open the print window
    var printWindow = window.open('', '_blank', 'width=600,height=400');
    printWindow.document.write('<html><head><title>Receipt</title>');
    printWindow.document.write('</head><body>');
    printWindow.document.write(receiptContent);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
    printWindow.close();
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

        // Return the next input element if available, otherwise null
        return formElements[currentIndex + 1] || null;
    }
</script>

<!-- jQuery and Bootstrap Bundle (includes Popper) --> 
<script src="vendor/assets/jquery-3.5.1.slim.min.js"></script>
<script src="vendor/assets/bootstrap.bundle.min.js"></script>

</body>
</html>