<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Automatic Backup Service</title>
    <link href="vendor/assets/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .card { max-width: 800px; margin: 50px auto; }
        .status-badge { font-size: 1.1rem; }
        #log-output {
            background-color: #212529;
            color: #f8f9fa;
            font-family: 'Courier New', Courier, monospace;
            padding: 15px;
            border-radius: 5px;
            max-height: 300px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <!-- Top Nav Menu -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <a class="navbar-brand" href="index.php">National Gold Testing</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Home Page</a></li>
                <li class="nav-item"><a class="nav-link" href="testreportform.php">Test Report Page</a></li>
                <li class="nav-item"><a class="nav-link" href="reports.php">Reports Page</a></li>
                <li class="nav-item"><a class="nav-link" href="receipts.php">Receipts Page</a></li>
                <li class="nav-item"><a class="nav-link" href="logs.php">Logs Page</a></li>
                <li class="nav-item"><a class="nav-link" href="config.php">Config Page</a></li>
                <li class="nav-item active"><a class="nav-link" href="service.php">Backup Service</a></li>
                <li class="nav-item"><a class="nav-link" href="#" onclick="window.close(); return false;">Exit</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="card text-center">
            <div class="card-header">
                <h3>Automatic Database Backup Service</h3>
            </div>
            <div class="card-body">
                <div class="alert alert-danger">
                    <strong>IMPORTANT:</strong> This page must remain open in a browser tab for the automatic backups to run. Closing this tab will stop the service.
                </div>
                <h5 class="card-title">Service Status</h5>
                <p id="status-message" class="card-text">
                    <span class="badge bg-secondary status-badge">Initializing...</span>
                </p>
                <p>Last backup attempt: <strong id="last-backup-time">N/A</strong></p>
                <p>Next scheduled backup in: <strong id="countdown-timer">Calculating...</strong></p>
                <button id="run-now-btn" class="btn btn-success my-3">Run Backup Now</button>

                <h5 class="mt-4">Log Output</h5>
                <div id="log-output">Service logs will appear here...</div>
            </div>
            <div class="card-footer text-muted">
                Service checks every 5 hours.
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const statusMessage = document.getElementById('status-message');
        const lastBackupTimeEl = document.getElementById('last-backup-time');
        const countdownTimerEl = document.getElementById('countdown-timer');
        const logOutput = document.getElementById('log-output');
        const runNowBtn = document.getElementById('run-now-btn');

        const backupInterval = 5 * 60 * 60 * 1000; // 5 hours in milliseconds
        let nextBackupTimestamp = Date.now() + backupInterval;

        function updateLog(message, isError = false) {
            const timestamp = new Date().toLocaleTimeString();
            const color = isError ? 'text-danger' : 'text-success';
            logOutput.innerHTML += `<div><span class="${color}">[${timestamp}]</span> ${message}</div>`;
            logOutput.scrollTop = logOutput.scrollHeight; // Auto-scroll to bottom
        }

        async function runBackup() {
            statusMessage.innerHTML = '<span class="badge bg-warning status-badge">Backup in progress...</span>';
            updateLog('Starting backup process...', false);
            runNowBtn.disabled = true;

            try {
                const response = await fetch('backup.php?source=auto');
                const resultText = await response.text();

                if (!response.ok || resultText.toLowerCase().includes('error')) {
                    // If the HTTP status is bad OR the response text contains "error"
                    throw new Error(resultText);
                }
                
                statusMessage.innerHTML = '<span class="badge bg-success status-badge">Service is Running</span>';
                lastBackupTimeEl.textContent = new Date().toLocaleString();
                updateLog('Backup successful. Server response:', false);
                updateLog(resultText);

            } catch (error) {
                statusMessage.innerHTML = '<span class="badge bg-danger status-badge">Error Occurred</span>';
                // The error.message will now contain the actual output from backup.php
                updateLog(`Backup failed: \n${error.message}`, true);
            } finally {
                nextBackupTimestamp = Date.now() + backupInterval;
                runNowBtn.disabled = false;
            }
        }

        function updateCountdown() {
            const now = Date.now();
            const remaining = nextBackupTimestamp - now;

            if (remaining <= 0) {
                countdownTimerEl.textContent = 'Executing now...';
                return;
            }

            const hours = Math.floor((remaining / (1000 * 60 * 60)) % 24);
            const minutes = Math.floor((remaining / 1000 / 60) % 60);
            const seconds = Math.floor((remaining / 1000) % 60);

            countdownTimerEl.textContent = `${hours}h ${minutes}m ${seconds}s`;
        }

        // --- Event Listeners ---
        runNowBtn.addEventListener('click', runBackup);

        // --- Initial Setup ---
        statusMessage.innerHTML = '<span class="badge bg-success status-badge">Service is Running</span>';
        setInterval(runBackup, backupInterval); // Schedule the main backup loop
        setInterval(updateCountdown, 1000); // Update the countdown every second
        updateCountdown(); // Initial call to set timer immediately
        updateLog('Automatic backup service started.');
    });
    </script>
    <script src="vendor/assets/bootstrap.bundle.min.js"></script>
</body>
</html>