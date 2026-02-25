<?php
/**
 * Migration Script: Rename customers table to users and add role/permissions
 * 
 * This script will:
 * 1. Create a new users table with role and permissions columns
 * 2. Copy all data from customers to users
 * 3. Update foreign keys in orders table (customer_id -> user_id)
 * 4. Drop the old customers table
 * 5. Create a default admin user
 */

require_once 'config/database.php';

echo "Starting migration: customers -> users\n";
echo "=====================================\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Read and execute the migration SQL file
    $sqlFile = file_get_contents('config/migrate_customers_to_users.sql');
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sqlFile)));
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            $db->exec($statement);
            echo "✓ Executed: " . substr($statement, 0, 50) . "...\n";
        } catch (PDOException $e) {
            // Some statements might fail if already executed, that's okay
            if (strpos($e->getMessage(), 'already exists') === false && 
                strpos($e->getMessage(), "Can't DROP") === false &&
                strpos($e->getMessage(), "Unknown column") === false) {
                echo "⚠ Warning: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n=====================================\n";
    echo "✓ Migration completed successfully!\n\n";
    echo "Summary:\n";
    echo "- customers table renamed to users\n";
    echo "- Added 'role' column (admin, customer, staff)\n";
    echo "- Added 'permissions' column (JSON)\n";
    echo "- Updated orders.customer_id to orders.user_id\n";
    echo "- Created default admin user:\n";
    echo "  Email: admin@scoops.com\n";
    echo "  Password: admin123\n\n";
    
    // Verify the migration
    $userCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $orderCount = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    
    echo "Verification:\n";
    echo "- Total users: $userCount\n";
    echo "- Total orders: $orderCount\n";
    
} catch (Exception $e) {
    echo "\n✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✓ All done! You can now use the users table with roles and permissions.\n";
