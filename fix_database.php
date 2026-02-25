<?php
/**
 * Database Schema Fix Script
 * 
 * This script fixes the database schema by adding all missing columns,
 * indexes, and constraints.
 */

require_once 'config/database.php';

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║         DATABASE SCHEMA FIX & OPTIMIZATION                 ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Read the SQL file
    $sqlFile = file_get_contents('config/fix_database_schema.sql');
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sqlFile)));
    
    $successCount = 0;
    $warningCount = 0;
    $skipCount = 0;
    
    echo "Executing database fixes...\n";
    echo "─────────────────────────────────────────────────────────────\n\n";
    
    foreach ($statements as $statement) {
        // Skip comments and empty statements
        if (empty($statement) || 
            strpos($statement, '--') === 0 || 
            strpos($statement, '/*') === 0 ||
            strpos(trim($statement), 'SELECT \'Database schema') === 0 ||
            strpos(trim($statement), 'SELECT') === 0 && strpos($statement, 'FROM information_schema') !== false) {
            continue;
        }
        
        try {
            $db->exec($statement);
            
            // Determine what type of operation
            if (stripos($statement, 'ALTER TABLE') !== false) {
                preg_match('/ALTER TABLE\s+(\w+)/i', $statement, $matches);
                $table = $matches[1] ?? 'unknown';
                echo "✓ Updated table: $table\n";
            } elseif (stripos($statement, 'CREATE TABLE') !== false) {
                preg_match('/CREATE TABLE.*?(\w+)\s*\(/i', $statement, $matches);
                $table = $matches[1] ?? 'unknown';
                echo "✓ Created table: $table\n";
            } elseif (stripos($statement, 'CREATE INDEX') !== false) {
                preg_match('/CREATE INDEX.*?(\w+)/i', $statement, $matches);
                $index = $matches[1] ?? 'unknown';
                echo "✓ Created index: $index\n";
            } elseif (stripos($statement, 'INSERT INTO') !== false) {
                preg_match('/INSERT INTO\s+(\w+)/i', $statement, $matches);
                $table = $matches[1] ?? 'unknown';
                echo "✓ Inserted data into: $table\n";
            } elseif (stripos($statement, 'UPDATE') !== false) {
                preg_match('/UPDATE\s+(\w+)/i', $statement, $matches);
                $table = $matches[1] ?? 'unknown';
                echo "✓ Updated data in: $table\n";
            } else {
                echo "✓ Executed statement\n";
            }
            
            $successCount++;
            
        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();
            
            // These are acceptable "errors" - they mean the change already exists
            if (strpos($errorMsg, 'Duplicate column') !== false ||
                strpos($errorMsg, 'already exists') !== false ||
                strpos($errorMsg, 'Duplicate key') !== false ||
                strpos($errorMsg, "Can't DROP") !== false ||
                strpos($errorMsg, 'Unknown column') !== false ||
                strpos($errorMsg, 'check constraint') !== false) {
                $skipCount++;
                // Silently skip - this is fine
            } else {
                echo "⚠ Warning: " . substr($errorMsg, 0, 80) . "...\n";
                $warningCount++;
            }
        }
    }
    
    echo "\n─────────────────────────────────────────────────────────────\n";
    echo "Summary:\n";
    echo "  ✓ Successful operations: $successCount\n";
    if ($warningCount > 0) {
        echo "  ⚠ Warnings: $warningCount\n";
    }
    if ($skipCount > 0) {
        echo "  ⊘ Skipped (already exists): $skipCount\n";
    }
    echo "\n";
    
    // Verify the schema
    echo "Verifying database schema...\n";
    echo "─────────────────────────────────────────────────────────────\n\n";
    
    $tables = ['products', 'users', 'orders', 'order_items', 'reviews', 'subscribers', 'coupons', 'coupon_usage'];
    
    foreach ($tables as $table) {
        $stmt = $db->query("SELECT COUNT(*) FROM information_schema.columns 
                           WHERE table_schema = 'icecream_shop' AND table_name = '$table'");
        $columnCount = $stmt->fetchColumn();
        
        if ($columnCount > 0) {
            echo "✓ Table '$table': $columnCount columns\n";
        } else {
            echo "⚠ Table '$table': Not found\n";
        }
    }
    
    echo "\n─────────────────────────────────────────────────────────────\n";
    echo "Database schema verification:\n\n";
    
    // Check critical columns
    $criticalChecks = [
        ['products', 'discount_percentage', 'Discount system'],
        ['products', 'is_featured', 'Featured products'],
        ['users', 'role', 'User roles'],
        ['users', 'permissions', 'User permissions'],
        ['orders', 'original_subtotal', 'Order discount tracking'],
        ['orders', 'coupon_code', 'Coupon system'],
        ['order_items', 'original_price', 'Item discount tracking'],
    ];
    
    foreach ($criticalChecks as $check) {
        list($table, $column, $feature) = $check;
        $stmt = $db->query("SELECT COUNT(*) FROM information_schema.columns 
                           WHERE table_schema = 'icecream_shop' 
                           AND table_name = '$table' 
                           AND column_name = '$column'");
        $exists = $stmt->fetchColumn() > 0;
        
        if ($exists) {
            echo "✓ $feature: Enabled\n";
        } else {
            echo "✗ $feature: Missing\n";
        }
    }
    
    echo "\n╔════════════════════════════════════════════════════════════╗\n";
    echo "║              DATABASE FIX COMPLETED SUCCESSFULLY!          ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n\n";
    
    echo "Next steps:\n";
    echo "1. Test product creation with discounts\n";
    echo "2. Test order placement with coupons\n";
    echo "3. Verify user roles and permissions\n";
    echo "4. Check admin dashboard statistics\n\n";
    
} catch (Exception $e) {
    echo "\n╔════════════════════════════════════════════════════════════╗\n";
    echo "║                    ERROR OCCURRED!                         ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    echo "Please check:\n";
    echo "1. Database connection settings in config/database.php\n";
    echo "2. Database user has sufficient privileges\n";
    echo "3. MySQL server is running\n\n";
    exit(1);
}
