<?php
require 'config/database.php';
$db = Database::getInstance()->getConnection();

// Fetch all products to build a map
$stmt = $db->query("SELECT id, name, category, price FROM products");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sizes = [];
$toppings = [];
$flavors = [];

foreach ($products as $p) {
    if ($p['category'] === 'size') $sizes[$p['name']] = $p;
    if ($p['category'] === 'topping') $toppings[$p['name']] = $p;
    if ($p['category'] === 'flavor') $flavors[$p['name']] = $p;
}

$sampleString = "Chocolate (Large Cup) + Chocolate Chips, Caramel Sauce, Chocolate Sauce, Fresh Fruits, Whipped Cream";

function parseOrderName($name, $sizes, $toppings, $flavors) {
    $custom = false;
    $sizeId = null;
    $toppingIds = [];
    
    // Check for size pattern (Size Name)
    preg_match('/ \((.*?)\)/', $name, $sizeMatch);
    if (!empty($sizeMatch) && isset($sizes[$sizeMatch[1]])) {
        $custom = true;
        $sizeId = $sizes[$sizeMatch[1]]['id'];
        
        // Remove size from name for further parsing
        $nameWithoutSize = str_replace($sizeMatch[0], '', $name);
    } else {
        $nameWithoutSize = $name;
    }
    
    // Check for toppings
    $parts = explode(' + ', $nameWithoutSize);
    $flavorName = trim($parts[0]);
    
    if (isset($parts[1])) {
        $toppingNames = explode(', ', $parts[1]);
        foreach ($toppingNames as $tName) {
            $tName = trim($tName);
            if (isset($toppings[$tName])) {
                $toppingIds[] = $toppings[$tName]['id'];
            }
        }
    }
    
    return [
        'is_custom' => $custom,
        'size_id' => $sizeId,
        'toppings' => $toppingIds,
        'flavor_name' => $flavorName
    ];
}

print_r(parseOrderName($sampleString, $sizes, $toppings, $flavors));
