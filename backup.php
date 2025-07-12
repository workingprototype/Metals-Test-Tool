<?php
// Set timezone for correct file naming
date_default_timezone_set('Asia/Kolkata');

// --- CONFIGURATION ---
$configFile = __DIR__ . '/config.json';
$configs = json_decode(file_get_contents($configFile), true);

$db_host = $configs['Database']['db_host'];
$db_user = $configs['Database']['db_user'];
$db_pass = $configs['Database']['db_password'];
$db_name = $configs['Database']['db_name'];

$keep_backups_for_days = 7; // Keep backups for 7 days

// --- DYNAMIC BACKUP DIRECTORY LOGIC ---
$is_automatic = isset($_GET['source']) && $_GET['source'] === 'auto';
$base_backup_dir = __DIR__ . '/backups/';
$backup_dir = $is_automatic ? $base_backup_dir . 'automatic/' : $base_backup_dir;

// --- SCRIPT LOGIC ---

header('Content-Type: text/plain');

// 1. Check if backup directory exists and is writable
if (!is_dir($backup_dir)) {
    if (!mkdir($backup_dir, 0755, true)) {
        die("Error: Backup directory does not exist and could not be created: " . $backup_dir);
    }
}
if (!is_writable($backup_dir)) {
    die("Error: Backup directory is not writable. Please check permissions: " . $backup_dir);
}

// 2. Create the backup filename with a timestamp (now .sql instead of .sql.gz)
$backup_file = $backup_dir . 'backup-' . date("Y-m-d-H-i-s") . '.sql';

// 3. Construct the mysqldump command
// We use escapeshellarg to prevent command injection vulnerabilities

// --- START OF LARAGON/WINDOWS FIX ---
// On Windows, PHP's exec() might not know the path to mysqldump.
// We provide the full, absolute path to the executable.

// <-- IMPORTANT: PASTE THE FULL PATH TO YOUR mysqldump.exe HERE
$mysqldump_path = '"C:\laragonmr\bin\mysql\mysql-8.0.30-winx64\bin\mysqldump.exe"';

// The command now uses the full path and outputs directly to a .sql file, removing the gzip pipe.
$command = sprintf(
    '%s --host=%s --user=%s --password=%s %s > %s',
    $mysqldump_path,
    escapeshellarg($db_host),
    escapeshellarg($db_user),
    escapeshellarg($db_pass),
    escapeshellarg($db_name),
    escapeshellarg($backup_file) // Directly output to the file
);
// --- END OF LARAGON/WINDOWS FIX ---

// 4. Execute the command
$output = null;
$return_var = null;
exec($command, $output, $return_var);

// 5. Check the result and provide feedback
if ($return_var === 0 && file_exists($backup_file) && filesize($backup_file) > 0) {
    echo "Database backup successfully created: " . basename($backup_file) . "\n";
} else {
    echo "Error: Database backup failed.\n";
    echo "Return Code: $return_var\n";
    echo "Output: " . implode("\n", $output) . "\n";
    if (file_exists($backup_file)) {
        unlink($backup_file);
    }
    exit; // Stop if backup failed
}

// 6. Clean up old backups (now looks for .sql files)
echo "Cleaning up old backups...\n";
$files = glob($backup_dir . 'backup-*.sql'); // <-- Changed to .sql
$cutoff_time = time() - ($keep_backups_for_days * 24 * 60 * 60);

$deleted_count = 0;
foreach ($files as $file) {
    if (filemtime($file) < $cutoff_time) {
        if (unlink($file)) {
            echo "Deleted old backup: " . basename($file) . "\n";
            $deleted_count++;
        }
    }
}
echo "Cleanup complete. Deleted $deleted_count old backup(s).\n";

echo "Process finished.\n";
?>