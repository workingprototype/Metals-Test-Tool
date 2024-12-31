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
// Include libraries for PDF and Excel export
require 'vendor/autoload.php'; // For PhpSpreadsheet
require('vendor/setasign/fpdf/fpdf.php'); // For FPDF

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
// Pagination variables
$limit = 10; // Number of records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page > 1) ? ($page * $limit) - $limit : 0;

// Search parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_date = isset($_GET['search_date']) ? $_GET['search_date'] : '';
$search_query = "";

// Build search query if search is active
if ($search || $search_date) {
    $search_query = "WHERE (sr_no LIKE '%$search%' OR name LIKE '%$search%' OR mobile LIKE '%$search%')";
    if ($search_date) {
        $search_query .= " AND report_date = '$search_date'";
    }
}

// Fetch total number of records for pagination
$total_result = $conn->query("SELECT COUNT(*) AS total FROM receipts $search_query");
$total_rows = $total_result->fetch_assoc()['total'];

// Default to today's date if no search date is provided
if (!$search_date) {
    $search_date = date('Y-m-d');
}

// Fetch today's receipts with pagination
$query = "SELECT * FROM receipts WHERE report_date = '$search_date' $search_query ORDER BY report_date DESC LIMIT $start, $limit";
$result = $conn->query($query);

// Handle Export to Excel
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set headers
    $sheet->setCellValue('A1', 'Sr No');
    $sheet->setCellValue('B1', 'Report Date');
    $sheet->setCellValue('C1', 'Name');
    $sheet->setCellValue('D1', 'Sample');
    $sheet->setCellValue('E1', 'Weight');
    // Add other headers...

    // Fetch and populate data
    $export_query = "SELECT * FROM receipts WHERE report_date = '$search_date' $search_query";
    $export_result = $conn->query($export_query);

    $rowCount = 2; // Start from row 2
    while ($row = $export_result->fetch_assoc()) {
        $sheet->setCellValue('A'.$rowCount, $row['sr_no']);
        $sheet->setCellValue('B'.$rowCount, $row['report_date']);
        $sheet->setCellValue('C'.$rowCount, $row['name']);
        $sheet->setCellValue('D'.$rowCount, $row['sample']);
        $sheet->setCellValue('E'.$rowCount, $row['weight']);
        // Populate other fields...
        $rowCount++;
    }

    // Export to Excel file
    $writer = new Xlsx($spreadsheet);
    $writer->save('receipts.xlsx');
    header('Location: receipts.xlsx');
    exit;
}

// Handle Export to PDF
if (isset($_GET['export']) && $_GET['export'] == 'pdf') {
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 12);

    // Set table headers
    $pdf->Cell(20, 10, 'Sr No', 1);
    $pdf->Cell(30, 10, 'Report Date', 1);
    $pdf->Cell(50, 10, 'Name', 1);
    $pdf->Cell(40, 10, 'Sample', 1);
    $pdf->Cell(30, 10, 'Weight', 1);
    // Add other headers...
    $pdf->Ln();

    // Fetch and populate data
    $export_query = "SELECT * FROM receipts WHERE report_date = '$search_date' $search_query";
    $export_result = $conn->query($export_query);

    while ($row = $export_result->fetch_assoc()) {
        $pdf->Cell(20, 10, $row['sr_no'], 1);
        $pdf->Cell(30, 10, $row['report_date'], 1);
        $pdf->Cell(50, 10, $row['name'], 1);
        $pdf->Cell(40, 10, $row['sample'], 1);
        $pdf->Cell(30, 10, $row['weight'], 1);
        // Populate other fields...
        $pdf->Ln();
    }

    // Output to PDF file
    $pdf->Output('D', 'receipts.pdf');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipts</title>
    <!-- Bootstrap CSS -->
    <link href="vendor/assets/bootstrap.min.css" rel="stylesheet">
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
                <li class="nav-item">
                    <a class="nav-link" href="testreportform.php">Test Report Page</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="reports.php">Reports Page</a>
                </li>
                <li class="nav-item active">
                    <a class="nav-link" href="receipts.php">Receipts Page</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logs.php">Logs Page</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="config.php">Config Page</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="" onclick="window.close(); return false;">Exit</a>
                </li>
            </ul>
        </div>
    </nav>
<div class="container mt-5">
    <h2 class="mb-4">Search Receipts</h2>
    <form method="GET" action="" class="row g-3">
        <div class="col-md-4">
            <input type="text" class="form-control" name="search" placeholder="Search by Sr No, Name, Mobile" value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="col-md-4">
            <input type="date" class="form-control" name="search_date" placeholder="Search by Date" value="<?php echo htmlspecialchars($search_date); ?>">
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="?export=excel&search=<?php echo urlencode($search); ?>&search_date=<?php echo urlencode($search_date); ?>" class="btn btn-success">Export to Excel</a>
            <a href="?export=pdf&search=<?php echo urlencode($search); ?>&search_date=<?php echo urlencode($search_date); ?>" class="btn btn-danger">Export to PDF</a>
        </div>
    </form>

    <table class="table table-striped table-bordered mt-4">
        <thead class="table-dark">
            <tr>
                <th>Sr No</th>
                <th>Report Date</th>
                <th>Name</th>
                <th>Sample</th>
                <th>Weight</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['sr_no']; ?></td>
                        <td><?php echo date('d-m-Y', strtotime($row['report_date'])); ?></td>
                        <td><?php echo $row['name']; ?></td>
                        <td><?php echo $row['sample']; ?></td>
                        <td><?php echo $row['weight']; ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5">No records found</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <?php
            $total_pages = ceil($total_rows / $limit);
            if ($total_pages > 1) {
                for ($i = 1; $i <= $total_pages; $i++) {
                    $active = $i == $page ? 'active' : '';
                    echo "<li class='page-item $active'><a class='page-link' href='?page=$i&search=$search&search_date=$search_date'>$i</a></li>";
                }
            }
            ?>
        </ul>
    </nav>
</div>

<!-- Bootstrap JS -->
<script src="vendor/assets/bootstrap.bundle.min.js"></script>
</body>
</html>
