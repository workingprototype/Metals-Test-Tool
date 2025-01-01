<?php
// Path to the config file
$configFile = 'config.json';

require 'vendor/autoload.php';  // If you installed using Composer, otherwise adjust the path accordingly


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

// Calculate the current month and determine the Sr. No. prefix
$current_month = date('n');  // 1 = January, 12 = December
$current_letter = chr(64 + $current_month);  // Convert month number to letter (A = 1, B = 2, ..., L = 12)

// Get the last used Sr. No. for the previous month
$prev_month = $current_month == 1 ? 12 : $current_month - 1;  // Previous month logic
$sql = "SELECT sr_no FROM receipts WHERE MONTH(report_date) = $prev_month ORDER BY sr_no DESC LIMIT 1";
$result = $conn->query($sql);

// Initialize the last_letter variable to a default value of 'A'
$last_letter = 'A';

if ($result->num_rows > 0) {
    $last_sr_no = $result->fetch_assoc()['sr_no'];
    $last_letter = substr($last_sr_no, 0, 1);  // Extract the letter from the Sr. No.
} 

// If the last letter was 'Z', reset the letter to 'A' for the next month
if ($last_letter == 'Z') {
    $current_letter = 'A';
} else {
    // Otherwise, continue to the next letter
    $current_letter = chr(ord($last_letter) + 1);
}

// Get the total number of receipts for the current month to determine the count for this month
$sql = "SELECT COUNT(*) AS total FROM receipts WHERE MONTH(report_date) = $current_month";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$customer_count = $row['total'] + 1; // Increment the count for the new customer

// Generate the Sr. No.
$sr_no = $current_letter . " " . $customer_count;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['submit_receipt'])) {
        $metal_type = $_POST['metal_type'];
        $sr_no = $_POST['sr_no'];
        $report_date = $_POST['report_date'];
        $name = $_POST['name'];
        $mobile = $_POST['mobile'];
        $alt_mobile = isset($_POST['alt_mobile']) ? $_POST['alt_mobile'] : NULL; // Capture alt_mobile (optional)
        $sample = $_POST['sample'];
        $weight = $_POST['weight'];

        // Always prepend +91 to the mobile number
        if (!empty($mobile)) {
            $mobile = "+91" . $mobile;
        }
        
        if (!empty($alt_mobile)) {
            $alt_mobile = "+91" . $alt_mobile;
        }
        

        $sql = "INSERT INTO receipts (metal_type, sr_no, report_date, name, mobile, alt_mobile, sample, weight) 
        VALUES ('$metal_type', '$sr_no', '$report_date', '$name', '$mobile', '$alt_mobile', '$sample', '$weight')";

        if ($conn->query($sql) === TRUE) {
            // Receipt saved successfully, now show the receipt and print option
            echo "<script>
                    alert('Receipt saved successfully.');
                    window.location.href = '" . $_SERVER['PHP_SELF'] . "?print_receipt=true&sr_no=" . urlencode($sr_no) . "';
                </script>";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }
}

if (isset($_GET['print_receipt']) && $_GET['print_receipt'] == 'true') {
    $sr_no = $_GET['sr_no'];
    $sql = "SELECT * FROM receipts WHERE sr_no = '$sr_no'";
    $result = $conn->query($sql);
    $receipt = $result->fetch_assoc();
    if ($receipt) {
        ?>
        <html>
        <head>
            <style>
 
                /* Hide form content when printing */
                .form-container {
                    display: none;
                }
            </style>
            
        </head>
        <body>
        <div id="receipt">         
            <div style="display:flex;width:80%;">
                <div style="text-align:center;width:33.33%;">
                    <div style="margin-top:75px;margin-left:25px;">
                        <div style="align-items:center;display:flex;gap:100px;margin-bottom:15px;font-size:15px;font-weight: bold;text-transform: uppercase;font-size:small;">
                            <div>&nbsp;</div>
                            <div><?php echo $receipt['sr_no']; ?></div>
                        </div>
                        <div style="align-items:center;display:flex;gap:100px;margin-bottom:15px;font-size:15px;font-weight: bold;text-transform: uppercase;font-size:small;">
                            <div>&nbsp;</div>
                            <div> <?php echo $receipt['name']; ?></div>
                        </div>
                        <div style="align-items:center;display:flex;gap:100px;margin-bottom:15px;font-size:x-small;">
                            <div>&nbsp;</div>
                            <div> <?php echo $receipt['report_date']; ?></div>
                        </div>
                        <div style="align-items:center;display:flex;gap:100px;margin-bottom:15px;font-size:x-small;">
                            <div>&nbsp;</div>
                            <div> <?php echo $receipt['weight']; ?> grams</div>
                        </div>
                        <div style="align-items:center;display:flex;gap:100px;margin-bottom:15px;margin-left: -13px;font-size:x-small;">
                            <div>&nbsp;</div>
                           <div> <?php echo $receipt['mobile']; ?> <br> <?php echo $receipt['alt_mobile'] ? $receipt['alt_mobile'] : ''; ?> </div>
                        </div>
                    </div>
                </div>
                <div style="text-align:center;width:66.67%;margin-right:170px;">
                    <div style="margin-top:72px;margin-right:190px;">
                        <div style="margin-top:20px;margin-bottom: -31px;">
                            <div style="display:flex;justify-content:space-between;">
                                <div>&nbsp;</div>
                                <div style="">
                                    <div style="align-items:center;display:flex;gap:10px;justify-content:center;font-size:x-small;">
                                        <div>&nbsp;</div>
                                        <div><?php echo $receipt['report_date']; ?></div>
                                    </div>
                                </div>
                                <div>&nbsp;</div>
                                <div style="">
                                    <div style="align-items:center;display:flex;gap:20px;justify-content:center;font-size:15px;font-weight: bold;text-transform: uppercase;font-size:small;">
                                        <div>&nbsp;</div>
                                        <div><?php echo $receipt['sr_no']; ?></div>
                                    </div>
                                </div>
                                <div>&nbsp;</div>
                            </div>
                        </div>
                        <div style="margin-top:20px;">
                            <div style="display:flex;justify-content:space-between;">
                                <div>&nbsp;</div>
                                <div style="">
                                    <div style="align-items:center;display:flex;gap:10px;justify-content:center;font-size:15px;font-weight: bold;text-transform: uppercase;font-size:small;">
                                        <div>&nbsp;</div>
                                        <div><?php echo $receipt['name']; ?></div>
                                    </div>
                                </div>
                                <div>&nbsp;</div>
                                <div style="">
                                    <div style="align-items:center;display:flex;gap:50px;justify-content:center;font-size:x-small;">
                                        <div>&nbsp;</div>
                                        <div>&nbsp;</div>
                                    </div>
                                </div>
                                <div>&nbsp;</div>
                            </div>
                        </div>
                        <div style="margin-top:14px;margin-bottom:4px;">
                            <div style="display:flex;justify-content:space-between;">
                                <div>&nbsp;</div>
                                <div style="">
                                    <div style="align-items:center;display:flex;gap:50px;justify-content:center;margin-bottom:25px;font-size:x-small;">
                                        <div>&nbsp;</div>
                                        <div><?php echo $receipt['weight']; ?></div>
                                    </div>
                                </div>
                                <div>&nbsp;</div>
                                <div style="">
                                    <div style="align-items:center;display:flex;gap:50px;justify-content:center;font-size:x-small;">
                                        <div>&nbsp;</div>
                                        <div><?php echo $receipt['sample']; ?></div>
                                    </div>
                                </div>
                                <div>&nbsp;</div>
                            </div>
                        </div>
                        <div style="margin-top:40px;">
                            <div style="display:flex;justify-content:space-between; margin-top: -12px;margin-left: 10px;">
                                <div>&nbsp;</div>
                                <div style="">
                                    <div style="align-items:center;display:flex;gap:50px;justify-content:center;font-size:x-small;">
                                        <div>&nbsp;</div>
                                        <div><?php echo $receipt['mobile']; ?></div>
                                    </div>
                                </div>
                                <div  style="font-size:x-small;">&nbsp;</div>
                                <div style="">
                                    <div style="align-items:center;display:flex;gap:50px;font-size:x-small;justify-content:center;">
                                        <div>&nbsp;</div>
                                        <div>&nbsp;</div>
                                    </div>
                                </div>
                                <div>&nbsp;</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
            window.onload = function() {
                window.print();  // Automatically trigger the print dialog when page loads
                window.onafterprint = function() {
                    window.close();  // Close the window after printing
                }
            }
        </script>
        </body>
        </html>
        <?php
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
            max-width: 500px;
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
    </style>
</head>
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

<body>
    <!-- Top Nav Menu -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <a class="navbar-brand" href="index.php">National Gold Testing</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item active">
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
        <h4>Receipt Form</h4>
    </div>
    <form method="post">
        <div class="form-group">
            <label>Metal Type</label>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="metal_type" value="Gold" id="gold" required checked>
                <label class="form-check-label" for="gold">Gold</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="metal_type" value="Silver" id="silver" required>
                <label class="form-check-label" for="silver">Silver</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="metal_type" value="Platinum" id="platinum" required>
                <label class="form-check-label" for="platinum">Platinum</label>
            </div>
        </div>

        <div class="form-group">
            <label for="sr_no">Sr. No</label>
            <input type="text" class="form-control" id="sr_no" name="sr_no" value="<?php echo $sr_no; ?>" readonly required>
        </div>

        <div class="form-group">
            <label for="date">Date</label>
            <input type="date" class="form-control" name="report_date" value="<?php echo date('Y-m-d'); ?>" required>
        </div>


        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>

        <div class="form-group">
            <label for="mobile">Mobile</label>
            <div class="input-group">
                <span class="input-group-text">+91</span>
                <input type="text" class="form-control" id="mobile" name="mobile" placeholder="Enter mobile number">
            </div>
        </div>
            <div class="form-group">
            <label for="alt_mobile">Alt-Mobile (Optional)</label>
            <div class="input-group">
            <span class="input-group-text">+91</span>
            <input type="text" class="form-control" id="alt_mobile" name="alt_mobile" placeholder="Enter alternate mobile number">
            </div>
            </div>

        <div class="form-group">
            <label for="sample">Sample</label>
            <input type="text" class="form-control" id="sample" name="sample" required>
        </div>

        <div class="form-group">
            <label for="weight">Weight</label>
            <input type="number" step="0.001" class="form-control" id="weight" name="weight" required>
        </div>

        <button type="submit" class="btn btn-primary btn-block" name="submit_receipt">Save Receipt</button>
    </form>
</div>
<!-- jQuery and Bootstrap Bundle (includes Popper) -->
<script src="vendor/assets/jquery-3.5.1.slim.min.js"></script>
<script src="vendor/assets/bootstrap.bundle.min.js"></script>

</body>
</html>