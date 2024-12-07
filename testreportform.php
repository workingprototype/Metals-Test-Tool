<?php
// Twilio API setup
require_once 'vendor/autoload.php'; // Adjust to the location of Twilio SDK

use Twilio\Rest\Client;

// Toggle to enable or disable Twilio usage
$use_twilio = false; // Set to false to disable Twilio functionality

// Twilio credentials

$twilio_sid = '';
$twilio_token = '';
$twilio_phone_number = ''; // Your Twilio phone number (for SMS & Whatsapp)

// Check if Twilio credentials are available and usage is enabled
$twilio_available = $use_twilio && !empty($twilio_sid) && !empty($twilio_token) && !empty($twilio_phone_number);

// Twilio Client initialization (only if credentials are available and toggle is enabled)
if ($twilio_available) {
    $client = new Client($twilio_sid, $twilio_token);
    echo "Twilio functionality is enabled.<br>";
} elseif ($use_twilio) {
    echo "Twilio credentials are missing. SMS/WhatsApp features will not work.<br>";
} else {
    echo "Twilio functionality is disabled.<br>";
}

$servername = "localhost";
$username = "user";  // Use appropriate MySQL credentials
$password = "password";
$dbname = "metal_store";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$name = $sample = $metal_type = $sr_no = $mobile = $weight = "";
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
        $sql = "SELECT name, mobile, sample, metal_type, weight FROM receipts WHERE sr_no = '$sr_no'";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $name = $row['name'];
            $mobile = $row['mobile'];
            $sample = $row['sample'];
            $metal_type = $row['metal_type'];
            $weight = $row['weight'];
        } else {
            echo "No receipt found with this Sr. No.";
        }
    }

    if (isset($_POST['submit_report'])) {
        // Retrieve form data safely with default values for missing fields
        $sr_no = $_POST['sr_no'];
        $name = $_POST['name'];
        $sample = $_POST['sample'];
        $metal_type = $_POST['metal_type'];
        $count = isset($_POST['count']) ? $_POST['count'] : 0;
        $mobile = $_POST['mobile'];
        $weight = $_POST['weight'];
        $gold_percent = isset($_POST['gold_percent']) ? $_POST['gold_percent'] : 0.00;
        
        // Handle optional metals with default values
        $silver = isset($_POST['silver']) ? $_POST['silver'] : 0.00;
        $platinum = isset($_POST['platinum']) ? $_POST['platinum'] : 0.00;

        $zinc = isset($_POST['zinc']) ? $_POST['zinc'] : 0.00;
        $copper = isset($_POST['copper']) ? $_POST['copper'] : 0.00;
        $others = isset($_POST['others']) ? $_POST['others'] : 0.00;
        $rhodium = isset($_POST['rhodium']) ? $_POST['rhodium'] : 0.00;
        $iridium = isset($_POST['iridium']) ? $_POST['iridium'] : 0.00;
        $ruthenium = isset($_POST['ruthenium']) ? $_POST['ruthenium'] : 0.00;
        $palladium = isset($_POST['palladium']) ? $_POST['palladium'] : 0.00;
        $lead = isset($_POST['lead']) ? $_POST['lead'] : 0.00;

        $total_karat = isset($_POST['total_karat']) ? $_POST['total_karat'] : 0.00;

        // SQL query to insert into the database
        $sql = "INSERT INTO test_reports (sr_no, report_date, name, sample, metal_type, count, mobile, weight, gold_percent, silver, platinum, zinc, copper, others, rhodium, iridium, ruthenium, palladium, lead, total_karat)
                VALUES ('$sr_no', CURDATE(), '$name', '$sample', '$metal_type', '$count', '$mobile', '$weight', '$gold_percent', '$silver', '$platinum', '$zinc', '$copper', '$others', '$rhodium', '$iridium', '$ruthenium', '$palladium', '$lead', '$total_karat')";

        if (mysqli_query($conn, $sql)) {
            echo "Test report saved successfully!";

            // Send SMS and WhatsApp
            if ($twilio_available) {
            $message = "Hi $name, 
                Here's the test report result for your $metal_type. 
                Weight: $weight, Gold Percentage: $gold_percent%, Silver: $silver, Platinum: $platinum, 
                Zinc: $zinc, 
                Copper: $copper, 
                Others: $others, 
                Rhodium: $rhodium, 
                Iridium: $iridium, 
                Ruthenium: $ruthenium, 
                Palladium: $palladium, 
                Lead: $lead, 
                Total Carat: $total_karat.";

            // Send SMS
            try {
                $client->messages->create(
                    $mobile,
                    [
                        'from' => $twilio_phone_number,
                        'body' => $message
                    ]
                );
                echo "SMS sent successfully!";
            } catch (Exception $e) {
                echo "Error sending SMS: " . $e->getMessage();
            }

        // Send WhatsApp message via the WhatsApp Business API
        $whatsapp_url = 'https://graph.facebook.com/v14.0/' . $twilio_phone_number . '/messages'; // WhatsApp Business API endpoint
        $access_token = 'your_facebook_access_token'; // Facebook access token for WhatsApp API

        $whatsapp_data = [
            'messaging_product' => 'whatsapp',
            'to' => $mobile,
            'type' => 'text',
            'text' => ['body' => $message]
        ];

        // Make the HTTP POST request to WhatsApp API
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
        curl_close($ch);

        if ($response) {
            echo "WhatsApp message sent successfully!";
        } else {
            echo "Error sending WhatsApp message.";
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
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #e0e0e0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }
        .form-container {
            max-width: 600px;
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
<div class="form-container">
    <div class="form-header">
        <h4>Test Report Form</h4>
    </div>
    <form method="post">
          <!-- Count field (readonly) -->
          <div class="form-group">
            <label for="count">(Total Reports Today)</label>
            <input type="number" class="form-control" id="count" name="count" value="<?php echo $count; ?>" readonly>
        </div>

        <div class="form-group">
            <label for="sr_no">Sr. No</label>
            <input type="text" class="form-control" id="sr_no" name="sr_no" value="<?php echo $sr_no; ?>" required>
        </div>
        
        <button type="submit" class="btn btn-success btn-block" name="fetch_report">Fetch Report</button>

        <!-- Pre-filled fields -->
        <div class="form-group mt-4">
            <label for="metal_type">Metal Type</label>
            <input type="text" class="form-control" id="metal_type" name="metal_type" value="<?php echo $metal_type; ?>" readonly>
        </div>

        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" class="form-control" id="name" name="name" value="<?php echo $name; ?>" readonly>
        </div>

        <div class="form-group">
            <label for="mobile">Mobile</label>
            <input type="text" class="form-control" id="mobile" name="mobile" value="<?php echo $mobile; ?>" readonly>
        </div>

        <div class="form-group">
            <label for="sample">Sample</label>
            <input type="text" class="form-control" id="sample" name="sample" value="<?php echo $sample; ?>" readonly>
        </div>

        <div class="form-group">
            <label for="weight">Weight</label>
            <input type="number" step="0.01" class="form-control" id="weight" name="weight" value="<?php echo $weight; ?>" oninput="calculateKarat()">
        </div>

        <div class="form-group">
            <label for="gold_percent">Gold %</label>
            <input type="number" step="0.01" class="form-control" id="gold_percent" name="gold_percent" oninput="calculateKarat()">
        </div>

        <div class="form-group">
            <label for="silver">Silver</label>
            <input type="number" step="0.01" class="form-control" id="silver" name="silver">
        </div>

        <div class="form-group">
            <label for="platinum">Platinum</label>
            <input type="number" step="0.01" class="form-control" id="platinum" name="platinum">
        </div>

        <div class="form-group">
            <label for="zinc">Zinc</label>
            <input type="number" step="0.01" class="form-control" id="zinc" name="zinc">
        </div>

        <div class="form-group">
            <label for="copper">Copper</label>
            <input type="number" step="0.01" class="form-control" id="copper" name="copper">
        </div>

        <div class="form-group">
            <label for="others">Others</label>
            <input type="number" step="0.01" class="form-control" id="others" name="others">
        </div>

        <div class="form-group">
            <label for="rhodium">Rhodium</label>
            <input type="number" step="0.01" class="form-control" id="rhodium" name="rhodium">
        </div>

        <div class="form-group">
            <label for="iridium">Iridium</label>
            <input type="number" step="0.01" class="form-control" id="iridium" name="iridium">
        </div>

        <div class="form-group">
            <label for="ruthenium">Ruthenium</label>
            <input type="number" step="0.01" class="form-control" id="ruthenium" name="ruthenium">
        </div>

        <div class="form-group">
            <label for="palladium">Palladium</label>
            <input type="number" step="0.01" class="form-control" id="palladium" name="palladium">
        </div>

        <div class="form-group">
            <label for="lead">Lead</label>
            <input type="number" step="0.01" class="form-control" id="lead" name="lead">
        </div>


        <div class="form-group">
            <label for="total_karat">Total Karat</label>
            <input type="text" class="form-control" id="total_karat" name="total_karat" value="<?php echo number_format($total_karat, 2); ?>" readonly>
        </div>

        <button type="submit" class="btn btn-primary btn-block" name="submit_report">Save & Send Report</button>
        <button type="button" class="btn btn-success btn-block" id="savePrintBtn">Print Receipt</button>
    </form>
</div>
<!-- Hidden receipt layout for printing -->
<div id="receipt" class="receipt-layout">
    <h2>Receipt</h2>
    <p><strong>Sr. No:</strong> <span id="printSrNo"></span></p>
    <p><strong>Date:</strong> <span id="printDate"></span></p>
    <p><strong>Name:</strong> <span id="printName"></span></p>
    <p><strong>Mobile:</strong> <span id="printMobile"></span></p>
    <p><strong>Sample:</strong> <span id="printSample"></span></p>
    <p><strong>Weight:</strong> <span id="printWeight"></span></p>
    <p><strong>Metal Type:</strong> <span id="printMetalType"></span></p>
    <p><strong>Gold %:</strong> <span id="printGoldPercent"></span></p>
    <p><strong>Silver:</strong> <span id="printSilver"></span></p>
    <p><strong>Platinum:</strong> <span id="printPlatinum"></span></p>
    <p><strong>Zinc:</strong> <span id="printZinc"></span></p>
    <p><strong>Copper:</strong> <span id="printCopper"></span></p>
    <p><strong>Others:</strong> <span id="printOthers"></span></p>
    <p><strong>Rhodium:</strong> <span id="printRhodium"></span></p>
    <p><strong>Iridium:</strong> <span id="printIridium"></span></p>
    <p><strong>Ruthenium:</strong> <span id="printRuthenium"></span></p>
    <p><strong>Palladium:</strong> <span id="printPalladium"></span></p>
    <p><strong>Lead:</strong> <span id="printLead"></span></p>
    <p><strong>Total Karat:</strong> <span id="printTotalKarat"></span></p>
</div>


<script>
document.getElementById('savePrintBtn').addEventListener('click', function() {
    // Collect form data
    var srNo = document.getElementById('sr_no').value;
     // Get today's date
     var today = new Date();
    var dateString = today.toLocaleDateString();  
    var name = document.getElementById('name').value;
    var mobile = document.getElementById('mobile').value;
    var sample = document.getElementById('sample').value;
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
    document.getElementById('printDate').textContent = dateString;
    document.getElementById('printName').textContent = name;
    document.getElementById('printMobile').textContent = mobile;
    document.getElementById('printSample').textContent = sample;
    document.getElementById('printWeight').textContent = weight;
    document.getElementById('printMetalType').textContent = metalType;
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

<!-- jQuery and Bootstrap Bundle (includes Popper) Todo: Download it locally and make it run offline. --> 
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>