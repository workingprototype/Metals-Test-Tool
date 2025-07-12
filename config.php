<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Path to config file
$configFile = __DIR__ . '/config.json';  // Absolute path

// Load configuration from the JSON file
$configs = json_decode(file_get_contents($configFile), true);

// Update configuration settings when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    foreach ($_POST['config'] as $category => $settings) {
        // Handle zoomLevel separately since it's not an array
        if ($category === 'zoomLevel') {
            $configs[$category] = $settings; // Directly assign the value
        } else {
            // Handle nested arrays (Fast2SMS, WhatsApp, Database)
            foreach ($settings as $key => $value) {
                $configs[$category][$key] = $value;
            }
        }
    }

    // Save updated configuration to the file
    $json_data = json_encode($configs, JSON_PRETTY_PRINT);
    if (file_put_contents($configFile, $json_data) === false) {
        echo '<div class="alert alert-danger">Error saving configuration. Please check file permissions.</div>';
    } else {
        echo '<div class="alert alert-success">Configuration updated successfully!</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuration Settings</title>
    <link rel="stylesheet" href="vendor/assets/bootstrap.min.css">
    <style>
        .table th, .table td {
            vertical-align: middle;
        }
        .custom-switch-inline {
            display: inline-flex;
            align-items: center;
            gap: 10px; /* Space between the checkbox and other input fields */
        }

        .custom-checkbox {
            opacity: 0; /* Hide the default checkbox */
            position: absolute;
        }

        .toggle-label {
            display: inline-block;
            width: 50px;
            height: 24px;
            background-color: #ccc;
            border-radius: 50px;
            position: relative;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .toggle-label::after {
            content: '';
            position: absolute;
            top: 4px;
            left: 4px;
            width: 16px;
            height: 16px;
            background-color: #fff;
            border-radius: 50%;
            transition: transform 0.3s;
        }

        .custom-checkbox:checked + .toggle-label {
            background-color: #28a745; /* Green when checked */
        }

        .custom-checkbox:checked + .toggle-label::after {
            transform: translateX(26px); /* Move the knob to the right */
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
                <li class="nav-item">
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
                <li class="nav-item active">
                    <a class="nav-link" href="config.php">Config Page</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" id="backup-to-drive-btn">Backup to Drive</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="service.php">Backup Service</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="" onclick="window.close(); return false;">Exit</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container mt-4">
        <h2 class="mb-4">Configuration Settings</h2>
        <form method="POST" action="">
            <h5 class="mb-4">Config file stored in:<?php echo realpath($configFile); ?></h5>

            <!-- Fast2SMS Configuration -->
            <h4>Fast2SMS Config</h4>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Config Key</th>
                        <th>Config Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($configs['Fast2SMS'] as $key => $value): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($key); ?></td>
                            <td>
                                <input type="text" class="form-control" name="config[Fast2SMS][<?php echo $key; ?>]" value="<?php echo htmlspecialchars($value); ?>">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- WhatsApp Configuration -->
            <h4>WhatsApp Config</h4>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Config Key</th>
                        <th>Config Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($configs['WhatsApp'] as $key => $value): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($key); ?></td>
                            <td>
                                <input type="text" class="form-control" name="config[WhatsApp][<?php echo $key; ?>]" value="<?php echo htmlspecialchars($value); ?>">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- DB Configuration -->
            <h4>DB Config</h4>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Config Key</th>
                        <th>Config Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($configs['Database'] as $key => $value): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($key); ?></td>
                            <td>
                                <input type="text" class="form-control" name="config[Database][<?php echo $key; ?>]" value="<?php echo htmlspecialchars($value); ?>">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Zoom Level Configuration -->
            <h4>Zoom Level</h4>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Config Key</th>
                        <th>Config Value</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>zoomLevel</td>
                        <td>
                            <input type="text" class="form-control" name="config[zoomLevel]" value="<?php echo htmlspecialchars($configs['zoomLevel']); ?>">
                        </td>
                    </tr>
                </tbody>
            </table>

            <button type="submit" class="btn btn-success">Save Changes</button>
        </form>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const backupBtn = document.getElementById('backup-to-drive-btn');
        if (backupBtn) {
            backupBtn.addEventListener('click', (event) => {
                event.preventDefault();
                backupBtn.textContent = 'Backup starting...';
                backupBtn.style.pointerEvents = 'none'; // Disable button
                window.electron.ipcRenderer.send('backup:start');
            });

            window.electron.ipcRenderer.on('backup:status', (status) => {
                backupBtn.textContent = status; // Update button text with status
                
                // If backup is done (successfully or with error), re-enable the button
                if (status.toLowerCase().includes('success') || status.toLowerCase().includes('error')) {
                    setTimeout(() => {
                        backupBtn.textContent = 'Backup to Drive';
                        backupBtn.style.pointerEvents = 'auto';
                    }, 5000); // Reset after 5 seconds
                }
            });
        }
    });
</script>

    <!-- Bootstrap JS and jQuery -->
    <script src="vendor/assets/jquery-3.5.1.slim.min.js"></script>
    <script src="vendor/assets/popper.min.js"></script>
    <script src="vendor/assets/bootstrap.bundle.min.js"></script>

</body>
</html>