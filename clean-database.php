<?php
/**
 * Database Cleanup Script
 * This script clears all data from the database tables while preserving the structure
 * USE WITH CAUTION - This will delete all data!
 */

require_once __DIR__ . '/userAccounts/config.php';

// Set this to true to actually execute the cleanup
$CONFIRM_CLEANUP = true;

if (!$CONFIRM_CLEANUP) {
    die("⚠️ SAFETY CHECK: Set \$CONFIRM_CLEANUP = true in this script to proceed with database cleanup.\n");
}

echo "Starting database cleanup...\n\n";

// List of tables to clean (in order to respect foreign key constraints)
$tables = [
    'document_requests',
    'barangay_announcements',
    'emergency_hotlines',
    'users'
];

try {
    // Disable foreign key checks temporarily
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    
    foreach ($tables as $table) {
        // Check if table exists
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        
        if ($result && $result->num_rows > 0) {
            // Get row count before deletion
            $count_result = $conn->query("SELECT COUNT(*) as count FROM `$table`");
            $count = $count_result->fetch_assoc()['count'];
            
            // Truncate the table (faster than DELETE)
            $conn->query("TRUNCATE TABLE `$table`");
            
            echo "✓ Cleaned table '$table' - Removed $count records\n";
        } else {
            echo "⊘ Table '$table' does not exist - Skipped\n";
        }
    }
    
    // Re-enable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "\n✅ Database cleanup completed successfully!\n";
    echo "\nWhat was cleaned:\n";
    echo "- All users (admin, official, resident accounts)\n";
    echo "- All announcements\n";
    echo "- All emergency hotlines\n";
    echo "- All document requests\n";
    echo "\nWhat was preserved:\n";
    echo "- Database structure (tables, columns)\n";
    echo "- Location data (regions, provinces, municipalities, barangays)\n";
    
} catch (Exception $e) {
    echo "\n❌ Error during cleanup: " . $e->getMessage() . "\n";
    $conn->query("SET FOREIGN_KEY_CHECKS = 1"); // Re-enable on error
}

$conn->close();
?>
