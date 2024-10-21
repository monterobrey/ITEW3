<?php
session_start();

// Check if the user is logged in, if not try using the cookie to log them in, otherwise redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    if (isset($_COOKIE["user"])) {
        $_SESSION["loggedin"] = true;
        $_SESSION["username"] = $_COOKIE["user"];
    } else {
        header("location: index.php");
        exit;
    }
}

// Renew the session cookie for 30 more days
setcookie("user", $_SESSION["username"], time() + (86400 * 30), "/");

require 'db_config.php'; // Database connection logic

// Fetch products from the database
function fetchProducts($conn) {
    $products = [];
    $sql = "SELECT product_id, productname, product_code, productcategory, description, quantity, price, availability FROM products";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    return $products;
}

$products = fetchProducts($conn);
$duplicate_error = false; // Flag for duplicate error

// Handle form submission for adding new products
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_product'])) {
    $productname = trim($_POST['productname']);
    $product_code = trim($_POST['product_code']);
    $productcategory = trim($_POST['productcategory']);
    $description = trim($_POST['description']);
    $quantity = (int)$_POST['quantity'];
    $price = (float)$_POST['price'];
    $availability = isset($_POST['availability']) ? 1 : 0; // Boolean value for availability

    // Insert product into database
    $sql = "INSERT INTO products (productname, product_code, productcategory, description, quantity, price, availability) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("ssssiid", $productname, $product_code, $productcategory, $description, $quantity, $price, $availability);
        try {
            $stmt->execute();
            // Fetch updated product list
            $products = fetchProducts($conn);
        } catch (mysqli_sql_exception $e) {
            // Handle duplicate entry error
            if ($e->getCode() === 1062) { // Duplicate entry
                $duplicate_error = true; // Set the duplicate error flag
            } else {
                echo "<script>alert('Error adding product: " . $e->getMessage() . "');</script>";
            }
        }
        $stmt->close();
    } else {
        echo "<script>alert('Error preparing statement: " . $conn->error . "');</script>";
    }
}

// Handle form submission for updating products
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_product'])) {
    $product_id = (int)$_POST['product_id'];
    $productname = trim($_POST['productname']);
    $product_code = trim($_POST['product_code']);
    $productcategory = trim($_POST['productcategory']);
    $description = trim($_POST['description']);
    $quantity = (int)$_POST['quantity'];
    $price = (float)$_POST['price'];
    $availability = isset($_POST['availability']) ? 1 : 0;

    // Debug: Print out the values being updated
    error_log("Updating product: " . print_r($_POST, true));

    $sql = "UPDATE products SET productname=?, product_code=?, productcategory=?, description=?, quantity=?, price=?, availability=? WHERE product_id=?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ssssidii", $productname, $product_code, $productcategory, $description, $quantity, $price, $availability, $product_id);
        if ($stmt->execute()) {
            // Fetch updated product list
            $products = fetchProducts($conn);
            error_log("Product updated successfully");
        } else {
            error_log("Error updating product: " . $stmt->error);
            echo "<script>alert('Error updating product: " . $stmt->error . "');</script>";
        }
        $stmt->close();
    } else {
        error_log("Error preparing statement: " . $conn->error);
    }

    // Debug: Fetch the updated product to verify changes
    $verify_sql = "SELECT * FROM products WHERE product_id = ?";
    $verify_stmt = $conn->prepare($verify_sql);
    if ($verify_stmt) {
        $verify_stmt->bind_param("i", $product_id);
        $verify_stmt->execute();
        $result = $verify_stmt->get_result();
        $updated_product = $result->fetch_assoc();
        error_log("Updated product details: " . print_r($updated_product, true));
        $verify_stmt->close();
    }
}

// Handle deletion of products
if (isset($_GET['delete'])) {
    $product_id = (int)$_GET['delete'];
    $sql = "DELETE FROM products WHERE product_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    if ($stmt->execute()) {
        // Fetch updated product list
        $products = fetchProducts($conn);
    } else {
        echo "<script>alert('Error deleting product: " . $stmt->error . "');</script>";
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shoe Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg bg-body-tertiary">
        <a href="index.php"><img style="width: 100px; cursor: pointer;" src="Images/logo.jpg" class="logo"></a>
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a style="color: #CE1126;" class="nav-link active" aria-current="page" href="dashboard.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a style="color: #CE1126;" class="nav-link" href="#">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a style="color: #CE1126;" class="nav-link" href="#">Contact Us</a>
                    </li>
                </ul>
                <span class="navbar-text" style="margin-right: 20px;">
                    <a href="profile.php" style="color: #CE1126; text-decoration: none;">
                        <?php echo htmlspecialchars($_SESSION["username"]); ?>
                    </a>
                </span>
                <a href="logout.php" class="btn btn-outline-danger">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <h2>DASHBOARD</h2><br>

        <!-- Form for adding new products -->
        <form method="POST" class="mb-4">
            <h4>Add New Product</h4>
            <div class="mb-3">
                <label for="productname" class="form-label">Product Name</label>
                <input type="text" class="form-control" name="productname" id="productname" required>
            </div>
            <div class="mb-3">
                <label for="product_code" class="form-label">Product Code</label>
                <input type="text" class="form-control" name="product_code" id="product_code" required>
            </div>
            <div class="mb-3">
                <label for="productcategory" class="form-label">Product Category</label>
                <input type="text" class="form-control" name="productcategory" id="productcategory" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Product Description</label>
                <textarea class="form-control" name="description" required></textarea>
            </div>
            <div class="mb-3">
                <label for="quantity" class="form-label">Quantity</label>
                <input type="number" class="form-control" name="quantity" id="quantity" required>
            </div>
            <div class="mb-3">
                <label for="price" class="form-label">Price</label>
                <input type="number" step="0.01" class="form-control" name="price" id="price" required>
            </div>
            <div class="mb-3">
                <label for="availability" class="form-label">Available</label>
                <input type="checkbox" name="availability" id="availability">
            </div>
            <button type="submit" name="add_product" class="btn btn-success">Add Product</button>
        </form>

        <!-- Displaying the Product List -->
        <br><hr><br>
        <h4>Product List</h4>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Product Code</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Available</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td><?php echo htmlspecialchars($product['productname']); ?></td>
                    <td><?php echo htmlspecialchars($product['product_code']); ?></td>
                    <td><?php echo htmlspecialchars($product['productcategory']); ?></td>
                    <td><?php echo htmlspecialchars($product['description']); ?></td>
                    <td><?php echo htmlspecialchars($product['quantity']); ?></td>
                    <td><?php echo htmlspecialchars($product['price']); ?></td>
                    <td><?php echo htmlspecialchars($product['availability']) ? 'Yes' : 'No'; ?></td>
                    <td>
                        <a href="javascript:void(0)" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#updateProductModal" data-id="<?php echo htmlspecialchars($product['product_id']); ?>" data-name="<?php echo htmlspecialchars($product['productname']); ?>" data-code="<?php echo htmlspecialchars($product['product_code']); ?>" data-category="<?php echo htmlspecialchars($product['productcategory']); ?>" data-description="<?php echo htmlspecialchars($product['description']); ?>" data-quantity="<?php echo htmlspecialchars($product['quantity']); ?>" data-price="<?php echo htmlspecialchars($product['price']); ?>" data-availability="<?php echo htmlspecialchars($product['availability']); ?>">Edit</a>
                        <a href="?delete=<?php echo htmlspecialchars($product['product_id']); ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this product?');">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Update Product Modal -->
   
        <div class="modal fade" id="updateProductModal" tabindex="-1" aria-labelledby="updateProductModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="updateProductModalLabel">Update Product</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST">
                            <input type="hidden" name="product_id" id="product_id">
                            <div class="mb-3">
                                <label for="update_productname" class="form-label">Product Name</label>
                                <input type="text" class="form-control" name="productname" id="update_productname" required>
                            </div>
                            <div class="mb-3">
                                <label for="update_product_code" class="form-label">Product Code</label>
                                <input type="text" class="form-control" name="product_code" id="update_product_code" required>
                            </div>
                            <div class="mb-3">
                                <label for="update_productcategory" class="form-label">Product Category</label>
                                <input type="text" class="form-control" name="productcategory" id="update_productcategory" required>
                            </div>
                            <div class="mb-3">
                                <label for="update_description" class="form-label">Product Description</label>
                                <textarea class="form-control" name="description" id="update_description" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="update_quantity" class="form-label">Quantity</label>
                                <input type="number" class="form-control" name="quantity" id="update_quantity" required>
                            </div>
                            <div class="mb-3">
                                <label for="update_price" class="form-label">Price</label>
                                <input type="number" step="0.01" class="form-control" name="price" id="update_price" required>
                            </div>
                            <div class="mb-3">
                                <label for="update_availability" class="form-label">Available</label>
                                <input type="checkbox" name="availability" id="update_availability">
                            </div>
                            <button type="submit" name="update_product" class="btn btn-warning">Update Product</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Duplicate Entry Error Modal -->
        <div class="modal fade" id="duplicateErrorModal" tabindex="-1" aria-labelledby="duplicateErrorModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="duplicateErrorModalLabel">Duplicate Entry</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>A product with this code already exists. Please use a different code.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trigger the duplicate error modal if there is a duplicate entry -->
        <?php if ($duplicate_error): ?>
            <script>
                const duplicateErrorModal = new bootstrap.Modal(document.getElementById('duplicateErrorModal'));
                duplicateErrorModal.show();
            </script>
        <?php endif; ?>
    </div>

    <script>
        // Populate update modal with data
        const updateProductModal = document.getElementById('updateProductModal');
        updateProductModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget; // Button that triggered the modal
        const id = button.getAttribute('data-id');
        const name = button.getAttribute('data-name');
        const code = button.getAttribute('data-code');
        const category = button.getAttribute('data-category');
        const description = button.getAttribute('data-description');
        const quantity = button.getAttribute('data-quantity');
        const price = button.getAttribute('data-price');
        const availability = button.getAttribute('data-availability');

        const modalId = updateProductModal.querySelector('#product_id');
        const modalName = updateProductModal.querySelector('#update_productname');
        const modalCode = updateProductModal.querySelector('#update_product_code');
        const modalCategory = updateProductModal.querySelector('#update_productcategory');
        const modalDescription = updateProductModal.querySelector('#update_description');
        const modalQuantity = updateProductModal.querySelector('#update_quantity');
        const modalPrice = updateProductModal.querySelector('#update_price');
        const modalAvailability = updateProductModal.querySelector('#update_availability');

        modalId.value = id;
        modalName.value = name;
        modalCode.value = code;
        modalCategory.value = category;
        modalDescription.value = description; // Ensure this line is correct
        modalQuantity.value = quantity;
        modalPrice.value = price;
        modalAvailability.checked = availability == 1;
    });
    </script>
</body>
</html>