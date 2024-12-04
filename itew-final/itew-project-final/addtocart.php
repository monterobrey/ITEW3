<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

require 'db_config.php';

// Initialize shopping cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Function to fetch all available products
function fetchProducts($conn) {
    $products = [];
    $sql = "SELECT product_id, productname, product_code, productcategory, description, quantity, price, availability, ProductImage FROM products WHERE availability = 1";
    $result = $conn->query($sql);
    
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    return $products;
}

// Function to convert BLOB to base64
function blobToImage($blob) {
    if ($blob) {
        $base64 = base64_encode($blob);
        return $base64 ? 'data:image/jpeg;base64,' . $base64 : '';
    }
    return '';
}

// Handle Add to Cart
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    
    // Check if product exists and is available
    $stmt = $conn->prepare("SELECT productname, price, quantity FROM products WHERE product_id = ? AND availability = 1");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    
    if ($product) {
        // Check if quantity is available
        if ($quantity <= $product['quantity']) {
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id]['quantity'] += $quantity;
            } else {
                $_SESSION['cart'][$product_id] = [
                    'name' => $product['productname'],
                    'price' => $product['price'],
                    'quantity' => $quantity
                ];
            }
            echo "<script>alert('Product added to cart!');</script>";
        } else {
            echo "<script>alert('Insufficient stock!');</script>";
        }
    }
}

// Get all available products
$products = fetchProducts($conn);
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shoe Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .product-card {
            height: 100%;
            transition: transform 0.2s;
        }
        .product-card:hover {
            transform: translateY(-5px);
        }
        .product-image {
            height: 200px;
            object-fit: contain;
        }
        .cart-count {
            position: relative;
            top: -10px;
            right: 5px;
            background-color: #CE1126;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg bg-body-tertiary">
        <div class="container-fluid">
            <a href="index.php"><img style="width: 100px;" src="Images/logo.jpg" class="logo"></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" style="color: #CE1126;" href="store.php">Store</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" style="color: #CE1126;" href="aboutus.php">About Us</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <a href="cart.php" class="btn btn-outline-danger me-2">
                        <i class="bi bi-cart3"></i>
                        <span class="cart-count"><?php echo array_sum(array_column($_SESSION['cart'], 'quantity')) ?? 0; ?></span>
                    </a>
                    <span class="navbar-text me-3">
                        <a href="profile.php" style="color: #CE1126; text-decoration: none;">
                            <?php echo htmlspecialchars($_SESSION["username"]); ?>
                        </a>
                    </span>
                    <a href="logout.php" class="btn btn-outline-danger">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <h2 class="mb-4">Available Products</h2>
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php foreach ($products as $product): ?>
            <div class="col">
                <div class="card product-card h-100">
                    <img src="<?php echo blobToImage($product['ProductImage']); ?>" 
                         class="card-img-top product-image" 
                         alt="<?php echo htmlspecialchars($product['productname']); ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($product['productname']); ?></h5>
                        <p class="card-text">
                            <?php echo htmlspecialchars($product['description']); ?><br>
                            <strong>Category: </strong><?php echo htmlspecialchars($product['productcategory']); ?><br>
                            <strong>Price: $<?php echo htmlspecialchars($product['price']); ?></strong><br>
                            <small>Available: <?php echo htmlspecialchars($product['quantity']); ?> units</small>
                        </p>
                        <form method="POST" class="d-flex gap-2">
                            <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                            <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['quantity']; ?>" 
                                   class="form-control w-25">
                            <button type="submit" name="add_to_cart" class="btn btn-danger">Add to Cart</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>