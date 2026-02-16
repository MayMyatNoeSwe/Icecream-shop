<?php

$host = 'localhost';
$user = 'root';
$pass = '742001';

try {
    // Connect without db name first to create the database
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Reading schema.sql...<br>";
    $sql = file_get_contents(__DIR__ . '/database/schema.sql');

    echo "Initializing database...<br>";
    // Execute the SQL
    // Note: exec() can run multiple queries if the driver supports it
    $pdo->exec($sql);

    echo "<strong>Success!</strong> Database 'coffee_shop' initialized with tables and seed data.<br>";
    echo "<a href='index.php'>Go to Shop</a>";

} catch (PDOException $e) {
    echo "<strong>Error:</strong> " . $e->getMessage();
}
