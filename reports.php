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
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

// Pagination variables
$limit = 10; // Number of records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page > 1) ? ($page * $limit) - $limit : 0;

// Search parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_date = isset($_GET['search_date']) ? $_GET['search_date'] : '';

// If no search date is provided, set it to today's date by default
if (empty($search_date)) {
    $search_date = date('Y-m-d'); // Today's date in Y-m-d format
}

$search_query = "";

// Build search query if search is active
if ($search || $search_date) {
    $search_query = "WHERE (sr_no LIKE '%$search%' OR name LIKE '%$search%' OR mobile LIKE '%$search%')";
    if ($search_date) {
        $search_query .= " AND report_date = '$search_date'";
    }
}

// Fetch total number of records for pagination
$total_result = $conn->query("SELECT COUNT(*) AS total FROM test_reports $search_query");
$total_rows = $total_result->fetch_assoc()['total'];

// Fetch reports with pagination
$query = "SELECT * FROM test_reports $search_query ORDER BY report_date DESC LIMIT $start, $limit";
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
    $sheet->setCellValue('E1', 'Mobile');
    $sheet->setCellValue('F1', 'Alt Mobile');
    $sheet->setCellValue('G1', 'Metal Type');
    $sheet->setCellValue('H1', 'Weight');
    $sheet->setCellValue('I1', 'Purity / Gold %');
    $sheet->setCellValue('J1', 'Silver');
    $sheet->setCellValue('K1', 'Zinc');
    $sheet->setCellValue('L1', 'Copper');
    $sheet->setCellValue('M1', 'Others');
    $sheet->setCellValue('N1', 'Platinum');
    $sheet->setCellValue('O1', 'Rhodium');
    $sheet->setCellValue('P1', 'Iridium');
    $sheet->setCellValue('Q1', 'Ruthenium');
    $sheet->setCellValue('R1', 'Palladium');
    $sheet->setCellValue('S1', 'Lead');
    $sheet->setCellValue('T1', 'Total Karat');
 
    // Fetch and populate data
    $export_query = "SELECT * FROM test_reports $search_query";
    $export_result = $conn->query($export_query);

    $rowCount = 2; // Start from row 2
    while ($row = $export_result->fetch_assoc()) {
        $sheet->setCellValue('A'.$rowCount, $row['sr_no']);
        $sheet->setCellValue('B'.$rowCount, $row['report_date']);
        $sheet->setCellValue('C'.$rowCount, $row['name']);
        $sheet->setCellValue('D'.$rowCount, $row['sample']);
        $sheet->setCellValueExplicit('E'.$rowCount, $row['mobile'], DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('F'.$rowCount, $row['alt_mobile'], DataType::TYPE_STRING);
        $sheet->setCellValue('G'.$rowCount, $row['metal_type']);
        $sheet->setCellValue('H'.$rowCount, $row['weight']);
        $sheet->setCellValue('I'.$rowCount, $row['gold_percent']);
        $sheet->setCellValue('J'.$rowCount, $row['silver']);
        $sheet->setCellValue('K'.$rowCount, $row['zinc']);
        $sheet->setCellValue('L'.$rowCount, $row['copper']);
        $sheet->setCellValue('M'.$rowCount, $row['others']);
        $sheet->setCellValue('N'.$rowCount, $row['platinum']);
        $sheet->setCellValue('O'.$rowCount, $row['rhodium']);
        $sheet->setCellValue('P'.$rowCount, $row['iridium']);
        $sheet->setCellValue('Q'.$rowCount, $row['ruthenium']);
        $sheet->setCellValue('R'.$rowCount, $row['palladium']);
        $sheet->setCellValue('S'.$rowCount, $row['lead']);
        $sheet->setCellValue('T'.$rowCount, $row['total_karat']);
        $rowCount++;
    }

    // Export to Excel file
    $writer = new Xlsx($spreadsheet);
    $writer->save('test_report.xlsx');
    header('Location: test_report.xlsx');
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
    $pdf->Cell(40, 10, 'Alt Mobile', 1);
    $pdf->Cell(40, 10, 'Metal Type', 1);
    $pdf->Cell(40, 10, 'Weight (gm)', 1);
    $pdf->Cell(40, 10, 'Purity/Gold %', 1);
    $pdf->Cell(20, 10, 'Silver', 1);
    $pdf->Cell(20, 10, 'Zinc', 1);
    $pdf->Cell(20, 10, 'Copper', 1);
    $pdf->Cell(20, 10, 'Others', 1);
    $pdf->Cell(20, 10, 'Platinum', 1);
    $pdf->Cell(20, 10, 'Rhodium', 1);
    $pdf->Cell(20, 10, 'Iridium', 1);
    $pdf->Cell(20, 10, 'Ruthenium', 1);
    $pdf->Cell(20, 10, 'Palladium', 1);
    $pdf->Cell(20, 10, 'Lead', 1);
    $pdf->Cell(20, 10, 'Total Karat', 1);
    $pdf->Ln();

    // Fetch and populate data
    $export_query = "SELECT * FROM test_reports $search_query";
    $export_result = $conn->query($export_query);

    while ($row = $export_result->fetch_assoc()) {
        $pdf->Cell(20, 10, $row['sr_no'], 1);
        $pdf->Cell(30, 10, $row['report_date'], 1);
        $pdf->Cell(50, 10, $row['name'], 1);
        $pdf->Cell(40, 10, $row['sample'], 1);
        $pdf->Cell(40, 10, $row['mobile'], 1);
        $pdf->Cell(40, 10, $row['alt_mobile'], 1);
        $pdf->Cell(40, 10, $row['metal_type'], 1);
        $pdf->Cell(40, 10, $row['weight'], 1);
        $pdf->Cell(40, 10, $row['gold_percent'], 1);
        $pdf->Cell(20, 10, $row['silver'], 1);
        $pdf->Cell(20, 10, $row['zinc'], 1);
        $pdf->Cell(20, 10, $row['copper'], 1);
        $pdf->Cell(20, 10, $row['others'], 1);
        $pdf->Cell(20, 10, $row['platinum'], 1);
        $pdf->Cell(20, 10, $row['rhodium'], 1);
        $pdf->Cell(20, 10, $row['iridium'], 1);
        $pdf->Cell(20, 10, $row['ruthenium'], 1);
        $pdf->Cell(20, 10, $row['palladium'], 1);
        $pdf->Cell(20, 10, $row['lead'], 1);
        $pdf->Cell(20, 10, $row['total_karat'], 1);

        $pdf->Ln();
    }

    // Output to PDF file
    $pdf->Output('D', 'test_report.pdf');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Reports</title>
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
                <li class="nav-item active">
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
                    <a class="nav-link" href="" onclick="window.close(); return false;">Exit</a>
                </li>
            </ul>
        </div>
    </nav>
<div class="container mt-5">
    <h2 class="mb-4">Search Reports</h2>
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

     <div class="table-responsive mt-4">
        <table class="table table-striped table-bordered">
            <thead class="table-dark">
            <tr>
                <th>Sr No</th>
                <th>Report Date</th>
                <th>Name</th>
                <th>Sample</th>
                <th>Mobile</th>
                <th>Alt Mobile</th>
                <th>Metal Type</th>
                <th>Weight</th>
                <th>Purity/Gold %</th>
                <th>Silver</th>
                <th>Zinc</th>
                <th>Copper</th>
                <th>Others</th>
                <th>Platinum</th>
                <th>Rhodium</th>
                <th>Iridium</th>
                <th>Ruthenium</th>
                <th>Palladium</th>
                <th>Lead</th>
                <th>Total Karat</th>
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
                        <td><?php echo $row['mobile']; ?></td>
                        <td><?php echo $row['alt_mobile']; ?></td>
                        <td><?php echo $row['metal_type']; ?></td>
                        <td><?php echo $row['weight']; ?></td>
                        <td><?php echo $row['gold_percent']; ?></td>
                        <td><?php echo $row['silver']; ?></td>
                        <td><?php echo $row['zinc']; ?></td>
                        <td><?php echo $row['copper']; ?></td>
                        <td><?php echo $row['others']; ?></td>
                        <td><?php echo $row['platinum']; ?></td>
                        <td><?php echo $row['rhodium']; ?></td>
                        <td><?php echo $row['iridium']; ?></td>
                        <td><?php echo $row['ruthenium']; ?></td>
                        <td><?php echo $row['palladium']; ?></td>
                        <td><?php echo $row['lead']; ?></td>
                        <td><?php echo $row['total_karat']; ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="20">No records found</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

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
