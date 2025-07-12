<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

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

// Calculate the current month and determine the Sr. No. prefix
// 1. Get the most recent receipt from the database
$sql = "SELECT sr_no, report_date FROM receipts ORDER BY id DESC LIMIT 1";
$result = $conn->query($sql);

$current_letter = 'A'; // Default starting letter
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

// Generate the new Sr. No.
$sr_no = $current_letter . " " . $customer_count;
// --- END OF NEW LOGIC ---

// Ensure the Sr. No. is unique
while (!isSrNoUnique($conn, $sr_no)) {
    $customer_count++;
    $sr_no = $current_letter . " " . $customer_count;
}

// Function to check if the Sr. No. is unique
function isSrNoUnique($conn, $sr_no) {
    $sql = "SELECT sr_no FROM receipts WHERE sr_no = '$sr_no'";
    $result = $conn->query($sql);
    return $result->num_rows === 0;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['submit_receipt'])) {
        $metal_type = $_POST['metal_type'];
        $sr_no_letter = $_POST['sr_no_letter'];
        $sr_no_count = $_POST['sr_no_count'];
        $sr_no = $sr_no_letter . " " . $sr_no_count;
        $report_date = $_POST['report_date'];
        $name = $_POST['name'];
        $mobile = $_POST['mobile'];
        $alt_mobile = isset($_POST['alt_mobile']) && !empty($_POST['alt_mobile']) ? $_POST['alt_mobile'] : NULL;
        $sample = $_POST['sample'];
        $weight = $_POST['weight'];

        // Always prepend +91 to the mobile number
        if (!empty($mobile)) {
            $mobile = "+91" . $mobile;
        }
        
        if (!empty($alt_mobile)) {
            $alt_mobile = "+91" . $alt_mobile;
        }

        // Check if the Sr. No. already exists
        $sql_check = "SELECT * FROM receipts WHERE sr_no = '$sr_no'";
        $result_check = $conn->query($sql_check);

        if ($result_check->num_rows > 0) {
            // Update the existing record
            $sql = "UPDATE receipts SET metal_type='$metal_type', report_date='$report_date', name='$name', mobile='$mobile', alt_mobile='$alt_mobile', sample='$sample', weight='$weight' WHERE sr_no='$sr_no'";
        } else {
            // Insert a new record
            $sql = "INSERT INTO receipts (metal_type, sr_no, report_date, name, mobile, alt_mobile, sample, weight) 
            VALUES ('$metal_type', '$sr_no', '$report_date', '$name', '$mobile', '$alt_mobile', '$sample', '$weight')";
        }

        
        if ($conn->query($sql) === TRUE) {
            // Receipt saved successfully, redirect to the print page
            header("Location: " . $_SERVER['PHP_SELF'] . "?print_receipt=true&sr_no=" . urlencode($sr_no));
            exit(); // Ensure no further code is executed
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
                            <div> </div>
                            <div><?php echo $receipt['sr_no']; ?></div>
                        </div>
                        <div style="align-items:center;display:flex;gap:100px;justify-content:center;margin-bottom:15px;font-size:15px;font-weight: bold;text-transform: uppercase;font-size:small;">
                            <div> </div>
                            <div> <?php echo $receipt['name']; ?></div>
                        </div>
                        <div style="align-items:center;display:flex;gap:100px;margin-bottom:17px;font-size:x-small;">
                            <div> </div>
                           <div> <?php echo (new DateTime($receipt['report_date']))->format('d-m-Y'); ?></div>
                        </div>
                        <div style="align-items:center;display:flex;gap:100px;margin-bottom:17px;font-size:x-small;">
                            <div> </div>
                            <div> <?php echo $receipt['weight']; ?> grams</div>
                        </div>
                        <div style="align-items:center;display:flex;gap:100px;margin-bottom:15px;margin-left: -13px;font-size:x-small;">
                            <div> </div>
                           <div> <?php echo $receipt['mobile']; ?> <br> <?php echo $receipt['alt_mobile'] ? $receipt['alt_mobile'] : ''; ?> </div>
                        </div>
                    </div>
                </div>
                <div style="text-align:center;width:66.67%;margin-right:170px;margin-left: 50px;">
                    <div style="margin-top:68px;margin-right:190px;">
                        <div style="margin-top:20px;margin-bottom: -29px;">
    <div style="display:flex;justify-content:space-between;">
        <div> </div>
        <div style="">
            <div style="margin-left: 10px;white-space: nowrap;align-items:center;display:flex;gap:10px;justify-content:center;font-size:x-small;">
                <div> </div>
                <div><?php echo (new DateTime($receipt['report_date']))->format('d-m-Y'); ?></div>
            </div>
        </div>
        <div> </div>
        <div style="">
            <div style="margin-left: 50px; white-space: nowrap;align-items:center;display:flex;gap:20px;justify-content:center;font-size:15px;font-weight: bold;text-transform: uppercase;font-size:small;">
                <div> </div>
                <div style="margin-left: 30px;"><?php echo $receipt['sr_no']; ?></div>
            </div>
        </div>
        <div> </div>
    </div>
</div>
                        <div style="margin-top:20px;">
                            <div style="display:flex;justify-content:space-between;">
                                <div> </div>
                                <div style="">
                                    <div style="align-items:center;display:flex;gap:10px;justify-content:center;font-size:15px;font-weight: bold;text-transform: uppercase;font-size:small;">
                                        <div> </div>
                                        <div><?php echo $receipt['name']; ?></div>
                                    </div>
                                </div>
                                <div> </div>
                                <div style="">
                                    <div style="align-items:center;display:flex;gap:50px;justify-content:center;font-size:x-small;">
                                        <div> </div>
                                        <div> </div>
                                    </div>
                                </div>
                                <div> </div>
                            </div>
                        </div>
                        <div style="margin-left: 30px; margin-top:14px;margin-bottom:4px;">
                            <div style="display:flex;justify-content:space-between;">
                                <div> </div>
                                <div style="">
                                    <div style="align-items:center;display:flex;gap:50px;justify-content:center;margin-bottom:25px;font-size:x-small;">
                                        <div> </div>
                                        <div><?php echo $receipt['weight']; ?></div>
                                    </div>
                                </div>
                                <div> </div>
                                <div style="">
                                    <div style="margin-left: 30px;align-items:center;display:flex;gap:50px;justify-content:center;font-size:x-small;">
                                        <div> </div>
                                        <div><?php echo $receipt['sample']; ?></div>
                                    </div>
                                </div>
                                <div> </div>
                            </div>
                        </div>
                        <div style="margin-top:40px;">
                            <div style="display:flex;justify-content:space-between; margin-top: -5px;margin-left: 10px;">
                                <div> </div>
                                <div style="">
                                    <div style="align-items:center;display:flex;gap:50px;justify-content:center;font-size:x-small;">
                                        <div> </div>
                                        <div><?php echo $receipt['mobile'] . "</br>" . $receipt['alt_mobile']; ?></div>
                                    </div>
                                </div>
                                <div  style="font-size:x-small;"> </div>
                                <div style="">
                                    <div style="align-items:center;display:flex;gap:50px;font-size:x-small;justify-content:center;">
                                        <div> </div>
                                        <div> </div>
                                    </div>
                                </div>
                                <div> </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
    // Get the receipt content as HTML
    var receiptContent = document.getElementById('receipt').innerHTML;

    // Check if Electron IPC Renderer is available
    if (window.electron && window.electron.ipcRenderer) {
        window.electron.ipcRenderer.send('print-receipt', receiptContent);
    } else {
        // Fallback: Open a new window and print using browser's print dialog
        var printWindow = window.open('', '_blank', 'width=600,height=400');
        printWindow.document.write('<html><head><title>Receipt</title></head><body>');
        printWindow.document.write(receiptContent);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    }

    // Redirect to index.php after printing
    setTimeout(() => {
        window.location.href = 'index.php';
    }, 20);
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
    max-width: 850px;
    margin: 30px auto;
    background-color: #f4f4f4;
    padding: 15px;
    border: 1px solid #ccc;
    box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
}

.form-header {
    background-color: #0078d7;
    color: white;
    padding: 8px;
    border-radius: 5px 5px 0 0;
    text-align: center;
    margin-bottom: 15px;
    font-size: 14px;
}

.form-group label {
    font-weight: bold;
    color: #333;
    font-size: 13px;
}

.btn-primary, .btn-success {
    background-color: #0078d7;
    border-color: #0078d7;
    font-size: 13px;
    padding: 6px 12px;
}

.btn-primary:hover, .btn-success:hover {
    background-color: #005fa3;
    border-color: #005fa3;
}

.btn-secondary {
    background-color: #f0f0f0;
    color: #333;
    border-color: #ccc;
    font-size: 13px;
    padding: 6px 12px;
}

.btn-secondary:hover {
    background-color: #e0e0e0;
}

.warning-message {
    font-size: 0.875em;
    margin-top: 5px;
}
.form-control {
    font-size: 18px;
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
    <form method="post" id="receiptForm">
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

        <div style="display: flex; gap: 20px;">
            <div class="form-group" style="width: 150px;">
                <label for="sr_no_letter">Sr. No Letter</label>
                <div style="display: flex;">
                    <input type="text" class="form-control" style="width: 50px; margin-right: 5px;" id="sr_no_letter" name="sr_no_letter" value="<?php echo $current_letter; ?>" required>
                    <input type="number" class="form-control" style="width: 100px;" id="sr_no_count" name="sr_no_count" value="<?php echo $customer_count; ?>" required>
                </div>
            </div>

            <div class="form-group" style="width: 150px;">
                <label for="date">Date</label>
                <input type="date" class="form-control" name="report_date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label for="name">Name</label>
            <div style="position: relative;">
                <input type="text" class="form-control" id="name-ghost" style="position: absolute; top: 0; left: 0; z-index: 1; background-color: transparent; color: #adb5bd; border-color: transparent; user-select: none;" disabled>
                <input type="text" class="form-control" id="name" name="name" required autocomplete="off" style="position: relative; z-index: 2; background-color: transparent;">
            </div>
        </div>

        <div class="form-group">
            <label for="mobile">Mobile</label>
            <div class="input-group">
                <span class="input-group-text">+91</span>
                <div style="position: relative; flex-grow: 1;">
                    <input type="text" class="form-control" id="mobile-ghost" style="position: absolute; top: 0; left: 0; z-index: 1; background-color: transparent; color: #adb5bd; border-color: transparent; user-select: none;" disabled>
                    <input type="text" class="form-control" id="mobile" name="mobile" placeholder="Enter mobile number" autocomplete="off" style="position: relative; z-index: 2; background-color: transparent;">
                </div>
            </div>
            <div id="mobile-warning" class="warning-message" style="color: red; display: none;">Mobile numbers should not exceed 10 digits.</div>
        </div>

        <div class="form-group">
            <label for="alt_mobile">Alt-Mobile (Optional)</label>
            <div class="input-group">
                <span class="input-group-text">+91</span>
                <div style="position: relative; flex-grow: 1;">
                    <input type="text" class="form-control" id="alt_mobile-ghost" style="position: absolute; top: 0; left: 0; z-index: 1; background-color: transparent; color: #adb5bd; border-color: transparent; user-select: none;" disabled>
                    <input type="text" class="form-control" id="alt_mobile" name="alt_mobile" placeholder="Enter alternate mobile number" autocomplete="off" style="position: relative; z-index: 2; background-color: transparent;">
                </div>
            </div>
            <div id="alt-mobile-warning" class="warning-message" style="color: red; display: none;">Mobile numbers should not exceed 10 digits.</div>
        </div>

        <div style="display: flex; gap: 20px;">
            <div class="form-group">
                <label for="sample">Sample</label>
                <input type="text" style="width: 150px;" class="form-control" id="sample" name="sample" required>
            </div>

            <div class="form-group">
                <label for="weight">Weight</label>
                <input type="number" style="width: 100px;" step="0.001" class="form-control" id="weight" name="weight" required>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block" name="submit_receipt">Save Receipt</button>
    </form>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- UNIFIED GHOST-TEXT & NAVIGATION LOGIC ---

        // --- Configuration & DOM References ---
        const navOrder = ['sr_no_count', 'name', 'mobile', 'alt_mobile', 'sample', 'weight'];
        const ghostFields = ['name', 'mobile', 'alt_mobile'];
        
        const receiptForm = document.getElementById('receiptForm');
        let suggestionData = null; // Holds the full data object for the current top suggestion

        // --- Core Functions ---

        // Debounce function to limit API calls
        function debounce(func, delay) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), delay);
            };
        }

        // Fills all relevant form fields from a suggestion
        function fillFormFromSuggestion(data) {
            if (!data) return;
            document.getElementById('name').value = data.name || '';
            document.getElementById('mobile').value = data.mobile ? data.mobile.replace('+91', '') : '';
            document.getElementById('alt_mobile').value = data.alt_mobile ? data.alt_mobile.replace('+91', '') : '';
        }

        // Clears all ghost inputs and the stored suggestion data
        function clearGhostsAndSuggestion() {
            ghostFields.forEach(id => {
                const ghostInput = document.getElementById(`${id}-ghost`);
                if(ghostInput) ghostInput.value = '';
            });
            suggestionData = null;
        }

        // Fetches suggestions from the server
        async function fetchAndShowSuggestion(inputValue, fieldType) {
            clearGhostsAndSuggestion();
            if (inputValue.length < 1) return;

            const searchTerm = (fieldType === 'mobile' || fieldType === 'alt_mobile') 
                ? `+91${inputValue}` 
                : inputValue;

            try {
                const response = await fetch(`autofill.php?input=${encodeURIComponent(searchTerm)}&_=${new Date().getTime()}`);
                const suggestions = await response.json();

                if (suggestions.length > 0) {
                    const topSuggestion = suggestions[0];
                    const ghostInput = document.getElementById(`${fieldType}-ghost`);
                    let suggestionText;

                    if (fieldType === 'name') suggestionText = topSuggestion.name;
                    if (fieldType === 'mobile') suggestionText = topSuggestion.mobile ? topSuggestion.mobile.replace('+91', '') : '';
                    if (fieldType === 'alt_mobile') suggestionText = topSuggestion.alt_mobile ? topSuggestion.alt_mobile.replace('+91', '') : '';
                    
                    if (ghostInput && suggestionText && suggestionText.toLowerCase().startsWith(inputValue.toLowerCase())) {
                        ghostInput.value = suggestionText;
                        suggestionData = topSuggestion;
                    }
                }
            } catch (error) {
                console.error('Error fetching suggestions:', error);
            }
        }

        // --- Main Enter Key Navigation Handler ---
        receiptForm.addEventListener('keydown', function(e) {
            if (e.key !== 'Enter') return;

            const activeEl = document.activeElement;
            if (!activeEl || !activeEl.id) return;

            const currentIndex = navOrder.indexOf(activeEl.id);
            if (currentIndex === -1) return;

            e.preventDefault(); // Stop default form submission

            // If there's a ghost suggestion, accept it
            if (suggestionData && ghostFields.includes(activeEl.id)) {
                fillFormFromSuggestion(suggestionData);
                clearGhostsAndSuggestion();
            }

            // Navigate to the next field or submit
            const isLastField = currentIndex === navOrder.length - 1;
            if (isLastField) {
                document.querySelector('button[name="submit_receipt"]').click();
            } else {
                const nextId = navOrder[currentIndex + 1];
                document.getElementById(nextId)?.focus();
            }
        });

        // --- Ghost Text Event Listener Setup ---
        ghostFields.forEach(id => {
            const inputElement = document.getElementById(id);
            const ghostElement = document.getElementById(`${id}-ghost`);

            if (inputElement && ghostElement) {
                const debouncedFetch = debounce(fetchAndShowSuggestion, 200);
                
                inputElement.addEventListener('input', () => {
                    debouncedFetch(inputElement.value, id);
                });
                
                // Handle Tab/ArrowRight for accepting suggestions without moving field
                inputElement.addEventListener('keydown', (e) => {
                    if ((e.key === 'Tab' || e.key === 'ArrowRight') && ghostElement.value && inputElement.selectionStart === inputElement.value.length) {
                        e.preventDefault();
                        if (suggestionData) {
                            fillFormFromSuggestion(suggestionData);
                            clearGhostsAndSuggestion();
                        }
                    }
                });

                inputElement.addEventListener('blur', () => {
                    setTimeout(clearGhostsAndSuggestion, 150);
                });
            }
        });

        // --- Other Page Logic ---

        // Focus on the first field on page load
        document.getElementById('name').focus();
        
        // Select text on focus for all inputs
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('focus', function() { this.select(); });
        });
        
        // Logic for fetching data when Sr. No. is changed manually
        const debouncedSrNoChange = debounce(function() {
            const srNoLetter = document.getElementById('sr_no_letter').value.trim();
            const srNoCount = document.getElementById('sr_no_count').value.trim();
            if (srNoLetter && srNoCount) {
                fetch(`fetch_receipt_edit.php?sr_no=${encodeURIComponent(srNoLetter + ' ' + srNoCount)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data) {
                            document.querySelector('input[name="metal_type"][value="' + data.metal_type + '"]').checked = true;
                            document.querySelector('input[name="report_date"]').value = data.report_date;
                            document.getElementById('sample').value = data.sample;
                            document.getElementById('weight').value = data.weight;
                            fillFormFromSuggestion(data);
                        }
                    })
                    .catch(error => console.error('Error fetching receipt data:', error));
            }
        }, 100);
        document.getElementById('sr_no_letter').addEventListener('input', debouncedSrNoChange);
        document.getElementById('sr_no_count').addEventListener('input', debouncedSrNoChange);

        // Phone number length validation
        function validatePhoneLength(input, warningElement) {
            const value = input.value.replace(/\D/g, '');
            warningElement.style.display = (value.length > 10) ? 'block' : 'none';
        }
        document.getElementById('mobile').addEventListener('input', (e) => validatePhoneLength(e.target, document.getElementById('mobile-warning')));
        document.getElementById('alt_mobile').addEventListener('input', (e) => validatePhoneLength(e.target, document.getElementById('alt-mobile-warning')));
    });
    </script>
</div>

<!-- jQuery and Bootstrap Bundle (includes Popper) -->
<script src="vendor/assets/jquery-3.5.1.slim.min.js"></script>
<script src="vendor/assets/bootstrap.bundle.min.js"></script>

</body>
</html>