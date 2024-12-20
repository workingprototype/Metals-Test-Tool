<?php
// DB connection
$mysqli = new mysqli("localhost", "root", "", "metal_store");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
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
$total_result = $mysqli->query("SELECT COUNT(*) AS total FROM test_reports $search_query");
$total_rows = $total_result->fetch_assoc()['total'];

// Fetch reports with pagination
$query = "SELECT * FROM test_reports $search_query ORDER BY report_date DESC LIMIT $start, $limit";
$result = $mysqli->query($query);

// Handle Export to Excel
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set headers
    $sheet->setCellValue('A1', 'Sr No');
    $sheet->setCellValue('B1', 'Report Date');
    $sheet->setCellValue('C1', 'Name');
    $sheet->setCellValue('D1', 'Sample');
    // Add other headers...

    // Fetch and populate data
    $export_query = "SELECT * FROM test_reports $search_query";
    $export_result = $mysqli->query($export_query);

    $rowCount = 2; // Start from row 2
    while ($row = $export_result->fetch_assoc()) {
        $sheet->setCellValue('A'.$rowCount, $row['sr_no']);
        $sheet->setCellValue('B'.$rowCount, $row['report_date']);
        $sheet->setCellValue('C'.$rowCount, $row['name']);
        $sheet->setCellValue('D'.$rowCount, $row['sample']);
        // Populate other fields...
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
    // Add other headers...
    $pdf->Ln();

    // Fetch and populate data
    $export_query = "SELECT * FROM test_reports $search_query";
    $export_result = $mysqli->query($export_query);

    while ($row = $export_result->fetch_assoc()) {
        $pdf->Cell(20, 10, $row['sr_no'], 1);
        $pdf->Cell(30, 10, $row['report_date'], 1);
        $pdf->Cell(50, 10, $row['name'], 1);
        $pdf->Cell(40, 10, $row['sample'], 1);
        // Populate other fields...
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
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

    <table class="table table-striped table-bordered mt-4">
        <thead class="table-dark">
            <tr>
                <th>Sr No</th>
                <th>Report Date</th>
                <th>Name</th>
                <th>Sample</th>
                <!-- Add other headers as needed -->
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['sr_no']; ?></td>
                        <td><?php echo $row['report_date']; ?></td>
                        <td><?php echo $row['name']; ?></td>
                        <td><?php echo $row['sample']; ?></td>
                        <!-- Add other fields as needed -->
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4">No records found</td></tr>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
