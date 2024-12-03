<?php
$servername = "localhost";
$username = "user";  // Use appropriate MySQL credentials
$password = "password";
$dbname = "metal_store";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['submit_receipt'])) {
        $metal_type = $_POST['metal_type'];
        $sr_no = $_POST['sr_no'];
        $report_date = $_POST['report_date'];
        $name = $_POST['name'];
        $mobile = $_POST['mobile'];
        $sample = $_POST['sample'];
        $weight = $_POST['weight'];

        // Always prepend +91 to the mobile number
        $mobile = "+91" . $mobile;

        $sql = "INSERT INTO receipts (metal_type, sr_no, report_date, name, mobile, sample, weight) 
                VALUES ('$metal_type', '$sr_no', '$report_date', '$name', '$mobile', '$sample', '$weight')";

        if ($conn->query($sql) === TRUE) {
            echo "Receipt saved successfully.";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }
}

$conn->close();
?>

<!-- HTML original Form for Receipt -->
<!-- <form method="post">
    <h2>Receipt Form</h2>
    <label>Metal Type:</label>
    <select name="metal_type" required>
        <option value="Gold">Gold</option>
        <option value="Silver">Silver</option>
        <option value="Platinum">Platinum</option>
    </select><br>

    <label>Sr. No:</label>
    <input type="text" name="sr_no" required><br>

    <label>Date:</label>
    <input type="date" name="report_date" value="<?php echo date('Y-m-d'); ?>" required><br>

    <label>Name:</label>
    <input type="text" name="name" required><br>

    <label>Mobile:</label>
    <input type="text" name="mobile" required><br>

    <label>Sample:</label>
    <input type="text" name="sample" required><br>

    <label>Weight (gm):</label>
    <input type="number" step="0.001" name="weight" required><br>

    <button type="submit" name="submit_receipt">Save Receipt</button>
</form> -->



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
    </style>
</head>

<body>
<div class="form-container">
    <div class="form-header">
        <h4>Receipt Form</h4>
    </div>
    <form method="post">
        <div class="form-group">
            <label>Metal Type</label>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="metal_type" value="Gold" id="gold" required>
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
            <input type="text" class="form-control" id="sr_no" name="sr_no" required>
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
                <input type="text" class="form-control" id="mobile" name="mobile" placeholder="Enter mobile number" required>
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
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>