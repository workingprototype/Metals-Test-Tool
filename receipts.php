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

// Function to add space between letters and numbers in the search input
function formatSrNo($input) {
    return preg_replace('/([A-Za-z])(\d)/', '$1 $2', $input);
}
// Build search query if search is active
$search_query = "";

// Search parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';
$include_date = isset($_GET['include_date']) ? $_GET['include_date'] : false;
$exact_search = isset($_GET['exact_search']) ? $_GET['exact_search'] : false; // New parameter for exact search


// Format the search input for sr_no
$formatted_search = formatSrNo($search);

// Get today's date
$today = date('Y-m-d');

// Set default date range to today if no date range is provided
if (empty($from_date) && empty($to_date)) {
    $from_date = $today;
    $to_date = $today;
    $include_date = false; // Ensure the date range is included in the search

}

if (isset($_GET['todays_reports']) || (!isset($_GET['search']) && !isset($_GET['from_date']) && !isset($_GET['to_date']))) {
    $from_date = $today;
    $to_date = $today;
    $include_date = true; // Ensure the date range is included in the search
}

// If no search parameters are provided, show today's reports by default
if (empty($formatted_search) && empty($from_date) && empty($to_date)) {
    $search_query = "WHERE report_date = '$today'";
} else {
    // If search parameters are provided, build the query accordingly
    if ($formatted_search) {
        if ($exact_search) {
            $search_query = "WHERE (sr_no = '$formatted_search' OR name = '$search' OR mobile = '$search' OR alt_mobile = '$search')";
        } else {
            // Partial search: use LIKE for partial matching
        $search_query = "WHERE (sr_no LIKE '%$formatted_search%' OR name LIKE '%$formatted_search%' OR mobile LIKE '%$formatted_search%' OR alt_mobile LIKE '%$formatted_search%')";
    }
}

    // Include date range ONLY if the "Include Date" checkbox is checked
    if ($include_date) {
        if ($from_date && $to_date) {
            $search_query .= (empty($search_query) ? "WHERE" : " AND") . " report_date BETWEEN '$from_date' AND '$to_date'";
        } elseif ($from_date) {
            $search_query .= (empty($search_query) ? "WHERE" : " AND") . " report_date >= '$from_date'";
        } elseif ($to_date) {
            $search_query .= (empty($search_query) ? "WHERE" : " AND") . " report_date <= '$to_date'";
        }
    }
}


// Fetch total number of records for pagination
$total_result = $conn->query("SELECT COUNT(*) AS total FROM receipts $search_query");
$total_rows = $total_result->fetch_assoc()['total'];

// Fetch reports with pagination
$query = "SELECT * FROM receipts $search_query ORDER BY report_date DESC LIMIT $start, $limit";
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
    $sheet->setCellValue('F1', 'Alt-Mobile');
    $sheet->setCellValue('G1', 'Metal Type');
    $sheet->setCellValue('H1', 'Weight');
    // Add other headers...

    // Fetch and populate data
    $export_query = "SELECT * FROM receipts $search_query";
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
    $pdf->Cell(40, 10, 'Mobile', 1);
    $pdf->Cell(40, 10, 'Alt Mobile', 1);
    $pdf->Cell(40, 10, 'Metal Type', 1);
    $pdf->Cell(40, 10, 'Weight (gm)', 1);
    // Add other headers...
    $pdf->Ln();

    // Fetch and populate data
    $export_query = "SELECT * FROM receipts $search_query";
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
        // Populate other fields...
        $pdf->Ln();
    }

    // Output to PDF file
    $pdf->Output('D', 'receipts.pdf');
    exit;
}

// Handle Delete Record
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $delete_query = "DELETE FROM receipts WHERE sr_no = '$delete_id'";
    if ($conn->query($delete_query) === TRUE) {
        echo "<script>alert('Record deleted successfully');</script>";
        echo "<script>window.location.href='receipts.php';</script>";
    } else {
        echo "<script>alert('Error deleting record: " . $conn->error . "');</script>";
    }
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
        <div class="col-md-3">
            <input type="text" class="form-control" name="search" placeholder="Search by Sr No, Name, Mobile" value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="col-md-3">
            <input type="date" class="form-control" name="from_date" placeholder="From Date" value="<?php echo htmlspecialchars($from_date); ?>">
        </div>
        <div class="col-md-3">
            <input type="date" class="form-control" name="to_date" placeholder="To Date" value="<?php echo htmlspecialchars($to_date); ?>">
        </div>
        <div class="col-md-2 mt-2">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="include_date" id="include_date" <?php echo $include_date ? 'checked' : ''; ?>>
                <label class="form-check-label" for="include_date">Include Date</label>
            </div>
        </div>
        <div class="col-md-2 mt-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="exact_search" id="exact_search" <?php echo $exact_search ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="exact_search">Exact Search</label>
                </div>
            </div>
        <div class="col-md-4 mt-2">
            <button type="submit" class="btn btn-primary mt-2">Search</button>

            <a href="?export=excel&search=<?php echo urlencode($search); ?>&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>" class="btn btn-success mt-2">Export to Excel</a>
            <a href="?export=pdf&search=<?php echo urlencode($search); ?>&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>" class="btn btn-danger mt-2">Export to PDF</a>
        </div>
    </form>

    <div class="table-responsive mt-4">
        <table class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                <th>Action</th>
                    <th>Sr No</th>
                    <th>Report Date</th>
                    <th>Name</th>
                    <th>Sample</th>
                    <th>Mobile</th>
                    <th>Alt Mobile</th>
                    <th>Metal Type</th>
                    <th>Weight</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <a href="?delete_id=<?php echo $row['sr_no']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this record?');">Delete</a>
                            </td>
                            <td><?php echo $row['sr_no']; ?></td>
                            <td><?php echo date('d-m-Y', strtotime($row['report_date'])); ?></td>
                            <td><?php echo $row['name']; ?></td>
                            <td><?php echo $row['sample']; ?></td>
                            <td><?php echo $row['mobile']; ?></td>
                            <td><?php echo $row['alt_mobile']; ?></td>
                            <td><?php echo $row['metal_type']; ?></td>
                            <td><?php echo $row['weight']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="9">No records found</td></tr>
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
                echo "<li class='page-item $active'><a class='page-link' href='?page=$i&search=$search&from_date=$from_date&to_date=$to_date&include_date=$include_date'>$i</a></li>";
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