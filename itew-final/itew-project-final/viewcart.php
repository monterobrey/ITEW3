<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

require 'db_config.php';

// Handle quantity updates
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_cart'])) {
    $product_id = $_POST['product_id'];
    $new_quantity = (int)$_POST['quantity'];
    
    // Verify available quantity
    $stmt = $conn->prepare("SELECT quantity FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    
    if ($product && $new_quantity <= $product['quantity']) {
        if ($new_quantity > 0) {
            $_SESSION['cart'][$product_id]['quantity'] = $new_quantity;
        } else {
            unset($_SESSION['cart'][$product_id]);
        }
    } else {
        echo "<script>alert('Insufficient stock!');</script>";
    }
}

// Handle remove item
if (isset($_GET['remove'])) {
    $product_id = $_GET['remove'];
    unset($_SESSION['cart'][$product_id]);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
    <!-- Reuse the same navbar from store.php -->
    
    <div class="container mt-5">
        <h2>Shopping Cart</h2>
        <?php if (empty($_SESSION['cart'])): ?>
            <div class="alert alert-info">Your cart is empty.</div>
            <a href="store.php" class="btn btn-primary">Continue Shopping</a>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $grand_total = 0;
                        foreach ($_SESSION['cart'] as $product_id => $item): 
                            $total = $item['price'] * $item['quantity'];
                            $grand_total += $total;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td>$<?php echo htmlspecialchars($item['price']); ?></td>
                            <td>
                                <form method="POST" class="d-flex gap-2">
                                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                           min="0" class="form-control w-25">
                                    <button type="submit" name="update_cart" class="btn btn-sm btn-primary">Update</button>
                                </form>
                            </td>
                            <td>$<?php echo number_format($total, 2); ?></td>
                            <td>
                                <a href="?remove=<?php echo $product_id; ?>" class="btn btn-sm btn-danger"
                                   onclick="return confirm('Remove this item?')">Remove</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Grand Total:</strong></td>
                            <td><strong>$<?php echo number_format($grand_total, 2); ?></strong></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between">
                <a href="store.php" class="btn btn-primary">Continue Shopping</a>
                <a href="checkout.php" class="btn btn-success">Proceed to Checkout</a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>