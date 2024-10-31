<?php
include 'db_connection.php';  // Make sure to include your database connection

header('Content-Type: application/json');

// Read the JSON input from JavaScript
$request = json_decode(file_get_contents("php://input"), true);

$searchTerm = $request['searchTerm'];
$category = $request['category'];
$minPrice = $request['minPrice'];
$maxPrice = $request['maxPrice'];

// Start with base query
$query = "SELECT * FROM products WHERE 1=1";

// Add filters to the query based on received parameters
if (!empty($searchTerm)) {
    $query .= " AND productname LIKE ?";
    $params[] = "%{$searchTerm}%";
}

if (!empty($category)) {
    $query .= " AND productcategory = ?";
    $params[] = $category;
}

if (!empty($minPrice)) {
    $query .= " AND price >= ?";
    $params[] = $minPrice;
}

if (!empty($maxPrice)) {
    $query .= " AND price <= ?";
    $params[] = $maxPrice;
}

// Prepare and execute the query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Send the data back to JavaScript as JSON
echo json_encode($products);
?>
