<?php
// Turn on error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// =================================================================
// 1. CONFIGURATION & SETUP
// =================================================================

// Include libraries for PDF and Excel export
require 'vendor/autoload.php'; // For PhpSpreadsheet
require 'vendor/setasign/fpdf/fpdf.php'; // For FPDF

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

// Path to the config file
$configFile = 'config.json';
$configs = json_decode(file_get_contents($configFile), true);

// Extract database settings
$servername = $configs['Database']['db_host'];
$username = $configs['Database']['db_user'];
$password = $configs['Database']['db_password'];
$dbname = $configs['Database']['db_name'];

// Establish a secure database connection
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// =================================================================
// 2. HELPER FUNCTIONS
// =================================================================

/**
 * Adds a space between letters and numbers for sr_no formatting.
 */
function formatSrNo($input) {
    return preg_replace('/([A-Za-z])(\d)/', '$1 $2', $input);
}

/**
 * Generates intelligent, responsive pagination HTML.
 */
function generatePagination($currentPage, $totalPages, $queryParams = []) {
    if ($totalPages <= 1) {
        return '';
    }
    unset($queryParams['page']);
    $queryString = http_build_query($queryParams);
    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center flex-wrap">';
    $disabledPrev = ($currentPage <= 1) ? 'disabled' : '';
    $html .= "<li class='page-item {$disabledPrev}'><a class='page-link' href='?page=1&{$queryString}'>« First</a></li>";
    $html .= "<li class='page-item {$disabledPrev}'><a class='page-link' href='?page=" . ($currentPage - 1) . "&{$queryString}'>Previous</a></li>";
    $window = 2;
    for ($i = 1; $i <= $totalPages; $i++) {
        if ($i == 1 || $i == $totalPages || ($i >= $currentPage - $window && $i <= $currentPage + $window)) {
            $active = ($i == $currentPage) ? 'active' : '';
            $html .= "<li class='page-item {$active}'><a class='page-link' href='?page={$i}&{$queryString}'>{$i}</a></li>";
        } elseif ($i == $currentPage - $window - 1 || $i == $currentPage + $window + 1) {
            $html .= "<li class='page-item disabled'><span class='page-link'>...</span></li>";
        }
    }
    $disabledNext = ($currentPage >= $totalPages) ? 'disabled' : '';
    $html .= "<li class='page-item {$disabledNext}'><a class='page-link' href='?page=" . ($currentPage + 1) . "&{$queryString}'>Next</a></li>";
    $html .= "<li class='page-item {$disabledNext}'><a class='page-link' href='?page={$totalPages}&{$queryString}'>Last »</a></li>";
    $html .= '</ul></nav>';
    return $html;
}

// =================================================================
// 3. INPUT HANDLING & DEFAULTS
// =================================================================

$limit = 15; // Records per page
$page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Search & filter parameters
$search = $_GET['search'] ?? '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
$include_date = isset($_GET['include_date']);
$exact_search = isset($_GET['exact_search']);

$formatted_search = formatSrNo($search);
$today = date('Y-m-d');

// Default to today's receipts if no filters are set
if (empty($search) && empty($from_date) && empty($to_date)) {
    $from_date = $today;
    $to_date = $today;
    $include_date = true;
}

// =================================================================
// 4. ACTION HANDLING (DELETE / EXPORT)
// =================================================================

// Handle Delete Record (SECURE)
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    // IMPORTANT: Corrected table name to 'receipts'
    $stmt = $conn->prepare("DELETE FROM receipts WHERE sr_no = ?");
    $stmt->bind_param("s", $delete_id);
    if ($stmt->execute()) {
        echo "<script>alert('Receipt deleted successfully'); window.location.href='receipts.php';</script>";
    } else {
        echo "<script>alert('Error deleting receipt: " . $stmt->error . "'); window.location.href='receipts.php';</script>";
    }
    $stmt->close();
    exit;
}

// Handle Export Actions
if (isset($_GET['export'])) {
    // Build the base query for export (no LIMIT)
    $where_clauses = [];
    $params = [];
    $types = '';

    if (!empty($search)) {
        $search_term = $exact_search ? $formatted_search : '%' . $formatted_search . '%';
        $operator = $exact_search ? '=' : 'LIKE';
        $where_clauses[] = "(sr_no $operator ? OR name $operator ? OR mobile $operator ? OR alt_mobile $operator ?)";
        array_push($params, $search_term, $search_term, $search_term, $search_term);
        $types .= 'ssss';
    }

    if ($include_date && !empty($from_date) && !empty($to_date)) {
        $where_clauses[] = "report_date BETWEEN ? AND ?";
        array_push($params, $from_date, $to_date);
        $types .= 'ss';
    }

    $export_sql = "SELECT * FROM receipts";
    if (!empty($where_clauses)) {
        $export_sql .= " WHERE " . implode(" AND ", $where_clauses);
    }
    $export_sql .= " ORDER BY report_date DESC, id DESC";
    
    $stmt = $conn->prepare($export_sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $export_result = $stmt->get_result();

    // EXPORT TO EXCEL
    if ($_GET['export'] == 'excel') {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Receipts');

        $headers = ['Sr No', 'Report Date', 'Name', 'Sample', 'Mobile', 'Alt Mobile', 'Metal Type', 'Weight'];
        $sheet->fromArray($headers, NULL, 'A1');

        $rowCount = 2;
        while ($row = $export_result->fetch_assoc()) {
            $sheet->setCellValue('A'.$rowCount, $row['sr_no']);
            $sheet->setCellValue('B'.$rowCount, $row['report_date']);
            $sheet->setCellValue('C'.$rowCount, $row['name']);
            $sheet->setCellValue('D'.$rowCount, $row['sample']);
            $sheet->setCellValueExplicit('E'.$rowCount, $row['mobile'] ?? '', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('F'.$rowCount, $row['alt_mobile'] ?? '', DataType::TYPE_STRING);
            $sheet->setCellValue('G'.$rowCount, $row['metal_type']);
            $sheet->setCellValue('H'.$rowCount, $row['weight']);
            $rowCount++;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="receipts.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        $stmt->close();
        exit;
    }

    // EXPORT TO PDF
    if ($_GET['export'] == 'pdf') {
        $pdf = new FPDF('L', 'mm', 'A4'); // Landscape
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 10);

        $headers = ['Sr No', 'Date', 'Name', 'Sample', 'Mobile', 'Alt Mobile', 'Metal', 'Weight(gm)'];
        foreach ($headers as $header) {
            $pdf->Cell(34, 7, $header, 1, 0, 'C');
        }
        $pdf->Ln();

        $pdf->SetFont('Arial', '', 9);
        while ($row = $export_result->fetch_assoc()) {
            $pdf->Cell(34, 6, $row['sr_no'] ?? 'N/A', 1);
            $pdf->Cell(34, 6, date('d-m-Y', strtotime($row['report_date'] ?? '')), 1);
            $pdf->Cell(34, 6, $row['name'] ?? 'N/A', 1);
            $pdf->Cell(34, 6, $row['sample'] ?? 'N/A', 1);
            $pdf->Cell(34, 6, $row['mobile'] ?? 'N/A', 1);
            $pdf->Cell(34, 6, $row['alt_mobile'] ?? 'N/A', 1);
            $pdf->Cell(34, 6, $row['metal_type'] ?? 'N/A', 1);
            $pdf->Cell(34, 6, $row['weight'] ?? 'N/A', 1);
            $pdf->Ln();
        }
        $pdf->Output('D', 'receipts.pdf');
        $stmt->close();
        exit;
    }
}

// =================================================================
// 5. DATA FETCHING FOR DISPLAY (SECURE & PAGINATED)
// =================================================================

$where_clauses = [];
$params = [];
$types = '';

// Build WHERE clause
if (!empty($search)) {
    $search_term = $exact_search ? $formatted_search : '%' . $formatted_search . '%';
    $operator = $exact_search ? '=' : 'LIKE';
    $where_clauses[] = "(sr_no $operator ? OR name $operator ? OR mobile $operator ? OR alt_mobile $operator ?)";
    array_push($params, $search_term, $search_term, $search_term, $search_term);
    $types .= 'ssss';
}

if ($include_date && !empty($from_date) && !empty($to_date)) {
    $where_clauses[] = "report_date BETWEEN ? AND ?";
    array_push($params, $from_date, $to_date);
    $types .= 'ss';
}

$sql_where = '';
if (!empty($where_clauses)) {
    $sql_where = " WHERE " . implode(" AND ", $where_clauses);
}

// Get total count
$count_sql = "SELECT COUNT(*) AS total FROM receipts" . $sql_where;
$stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_rows = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);
$stmt->close();

// Get paginated data
$data_sql = "SELECT * FROM receipts" . $sql_where . " ORDER BY report_date DESC, id DESC LIMIT ?, ?";
$stmt = $conn->prepare($data_sql);
if (!empty($params)) {
    $types .= 'ii';
    array_push($params, $start, $limit);
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param('ii', $start, $limit);
}
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

$queryParams = $_GET;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipts</title>
    <link href="vendor/assets/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .table th { white-space: nowrap; }
        .action-btn { min-width: 80px; }
    </style>
</head>
<body>

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
                    <a class="nav-link" href="exit.php" onclick="window.close(); return false;">Exit</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Search & Filter Receipts</h4>
            </div>
            <div class="card-body">
                <form method="GET" action="" class="row g-3 align-items-end">
                    <div class="col-lg-3 col-md-6">
                        <label for="search" class="form-label">Search Term</label>
                        <input type="text" class="form-control" id="search" name="search" placeholder="Sr No, Name, Mobile..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label for="from_date" class="form-label">From Date</label>
                        <input type="date" class="form-control" id="from_date" name="from_date" value="<?php echo htmlspecialchars($from_date ?? ''); ?>">
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label for="to_date" class="form-label">To Date</label>
                        <input type="date" class="form-control" id="to_date" name="to_date" value="<?php echo htmlspecialchars($to_date ?? ''); ?>">
                    </div>
                    <div class="col-lg-2 col-md-6 d-flex align-items-center pt-4">
                        <div class="form-check me-3">
                            <input class="form-check-input" type="checkbox" name="include_date" id="include_date" <?php echo $include_date ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="include_date">Use Dates</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="exact_search" id="exact_search" <?php echo $exact_search ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="exact_search">Exact Match</label>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-12 d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">Search</button>
                        <a href="receipts.php" class="btn btn-secondary flex-grow-1">Reset</a>
                    </div>
                </form>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center">
                <div>
                    <span class="badge bg-info">Total Receipts: <?php echo $total_rows; ?></span>
                    <span class="badge bg-light text-dark">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
                </div>
                <div class="d-flex gap-2">
                    <a href="?<?php echo http_build_query(array_merge($queryParams, ['export' => 'excel'])); ?>" class="btn btn-success btn-sm">Export to Excel</a>
                    <a href="?<?php echo http_build_query(array_merge($queryParams, ['export' => 'pdf'])); ?>" class="btn btn-danger btn-sm">Export to PDF</a>
                </div>
            </div>
        </div>

        <div class="table-responsive mt-4">
            <table class="table table-striped table-bordered table-hover">
                <thead class="table-dark sticky-top">
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
                                    <a href="?delete_id=<?php echo htmlspecialchars($row['sr_no'] ?? ''); ?>" class="btn btn-danger btn-sm action-btn" onclick="return confirm('Are you sure you want to delete this receipt?');">Delete</a>
                                </td>
                                <td><?php echo htmlspecialchars($row['sr_no'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars(date('d-m-Y', strtotime($row['report_date'] ?? ''))); ?></td>
                                <td><?php echo htmlspecialchars($row['name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($row['sample'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($row['mobile'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($row['alt_mobile'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($row['metal_type'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($row['weight'] ?? 'N/A'); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center">No receipts found matching your criteria.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Intelligent Pagination -->
        <div class="mt-4">
            <?php echo generatePagination($page, $total_pages, $queryParams); ?>
        </div>
    </div>

    <script src="vendor/assets/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$conn->close();
?>