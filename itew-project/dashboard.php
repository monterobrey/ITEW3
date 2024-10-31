<?php
session_start();

// Check if the user is logged in, if not redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

require 'db_config.php'; // Database connection logic

// Function to fetch products including the BLOB image data
function fetchProducts($conn, $user_id) {
    $products = [];
    $sql = "SELECT product_id, productname, product_code, productcategory, description, quantity, price, availability, ProductImage FROM products WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }

    return $products;
}

// Function to convert BLOB to base64 encoded image
function blobToImage($blob) {
    if ($blob) {
        $base64 = base64_encode($blob);
        if ($base64 === false) {
            error_log("Failed to encode image data");
            return '';
        }
        return 'data:image/jpeg;base64,' . $base64;
    }
    return '';
}

$user_id = $_SESSION["user_id"];
$products = fetchProducts($conn, $user_id);
$duplicate_error = false; // Flag for duplicate error

// Handle form submission for adding new products
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_product'])) {
    $productname = trim($_POST['productname']);
    $product_code = trim($_POST['product_code']);
    $productcategory = trim($_POST['productcategory']);
    $description = trim($_POST['description']);
    $quantity = (int)$_POST['quantity'];
    $price = (float)$_POST['price'];
    $availability = isset($_POST['availability']) ? 1 : 0;

    // Handle file upload
    $image = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = array("jpg" => "image/jpg", "jpeg" => "image/jpeg", "gif" => "image/gif", "png" => "image/png");
        $filename = $_FILES["image"]["name"];
        $filetype = $_FILES["image"]["type"];
        $filesize = $_FILES["image"]["size"];

        // Verify file extension
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if (!array_key_exists($ext, $allowed)) die("Error: Please select a valid file format.");

        // Verify file size - 5MB maximum
        $maxsize = 5 * 1024 * 1024;
        if ($filesize > $maxsize) die("Error: File size is larger than the allowed limit.");

        // Verify MYME type of the file
        if (in_array($filetype, $allowed)) {
            // Check whether file exists before uploading it
            if (file_exists("upload/" . $filename)) {
                echo $filename . " is already exists.";
            } else {
                $image = file_get_contents($_FILES['image']['tmp_name']);
            }
        } else {
            echo "Error: There was a problem uploading your file. Please try again."; 
        }
    }

    // Insert product into database with user_id and image
    $sql = "INSERT INTO products (productname, product_code, productcategory, description, quantity, price, availability, user_id, ProductImage) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $null = NULL;
        $stmt->bind_param("ssssiiiib", $productname, $product_code, $productcategory, $description, $quantity, $price, $availability, $user_id, $null);
        if ($image !== null) {
            $stmt->send_long_data(8, $image);
        }
        try {
            $stmt->execute();
            // Fetch updated product list for the current user
            $products = fetchProducts($conn, $user_id);
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

    $sql = "UPDATE products SET productname=?, product_code=?, productcategory=?, description=?, quantity=?, price=?, availability=? WHERE product_id=? AND user_id=?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ssssidiii", $productname, $product_code, $productcategory, $description, $quantity, $price, $availability, $product_id, $user_id);
        if ($stmt->execute()) {
            // Fetch updated product list
            $products = fetchProducts($conn, $user_id);
            error_log("Product updated successfully");
        } else {
            error_log("Error updating product: " . $stmt->error);
            echo "<script>alert('Error updating product: " . $stmt->error . "');</script>";
        }
        $stmt->close();
    } else {
        error_log("Error preparing statement: " . $conn->error);
    }
}

// Handle deletion of products
if (isset($_GET['delete'])) {
    $product_id = (int)$_GET['delete'];
    $sql = "DELETE FROM products WHERE product_id=? AND user_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $product_id, $user_id);
    if ($stmt->execute()) {
        // Fetch updated product list
        $products = fetchProducts($conn, $user_id);
    } else {
        echo "<script>alert('Error deleting product: " . $stmt->error . "');</script>";
    }
    $stmt->close();
}

$conn->close();

// Debug: Print out image data lengths
// Comment out or remove this section once you've confirmed images are working
/*
echo "<div id='debug-info'>";
foreach ($products as $product) {
    echo "Product ID: " . $product['product_id'] . ", Image data length: " . strlen($product['ProductImage']) . "<br>";
}
echo "</div>";
*/




//Search and Filter
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['search'])) {
    require 'db_config.php';
    $user_id = $_SESSION["user_id"];
    $searchTerm = $_POST['searchTerm'];
    $category = $_POST['category'];
    $minPrice = $_POST['minPrice'];
    $maxPrice = $_POST['maxPrice'];

    // Base SQL query with user_id
    $sql = "SELECT * FROM products WHERE user_id = ?";
    $params = [$user_id];
    $types = "i";

    // Adding filters based on search criteria
    if (!empty($searchTerm)) {
        $sql .= " AND productname LIKE ?";
        $params[] = "%" . $searchTerm . "%";
        $types .= "s";
    }
    if (!empty($category)) {
        $sql .= " AND productcategory = ?";
        $params[] = $category;
        $types .= "s";
    }
    if (!empty($minPrice)) {
        $sql .= " AND price >= ?";
        $params[] = $minPrice;
        $types .= "d";
    }
    if (!empty($maxPrice)) {
        $sql .= " AND price <= ?";
        $params[] = $maxPrice;
        $types .= "d";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $filteredProducts = $result->fetch_all(MYSQLI_ASSOC);

    // Generate HTML for filtered products
    foreach ($filteredProducts as $product) {
        echo "<tr>";
        echo "<td>" . (!empty($product['ProductImage']) ? '<img src="' . blobToImage($product['ProductImage']) . '" class="product-thumbnail">' : 'No image') . "</td>";
        echo "<td>" . htmlspecialchars($product['productname']) . "</td>";
        echo "<td>" . htmlspecialchars($product['product_code']) . "</td>";
        echo "<td>" . htmlspecialchars($product['productcategory']) . "</td>";
        echo "<td>" . htmlspecialchars($product['description']) . "</td>";
        echo "<td>" . htmlspecialchars($product['quantity']) . "</td>";
        echo "<td>" . htmlspecialchars($product['price']) . "</td>";
        echo "<td>" . ($product['availability'] ? 'Yes' : 'No') . "</td>";
        echo "<td>
                <a href='javascript:void(0)' class='btn btn-warning' data-id='" . htmlspecialchars($product['product_id']) . "'>Edit</a>
                <a href='?delete=" . htmlspecialchars($product['product_id']) . "' class='btn btn-danger' onclick='return confirm(\"Are you sure?\");'>Delete</a>
             </td>";
        echo "</tr>";
    }

    exit;
}


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
        <style>
            .product-thumbnail {
                width: 100px;  /* Increased size for visibility */
                height: 100px;
                object-fit: contain;  /* Changed to contain to show full image */
                cursor: pointer;
            }
            .full-image-modal .modal-body {
                text-align: center;
            }
            .full-image-modal .modal-body img {
                max-width: 100%;
                max-height: 80vh;
            }
            #debug-info {
                background-color: #f8d7da;
                border: 1px solid #f5c6cb;
                color: #721c24;
                padding: 10px;
                margin-bottom: 20px;
            }
            .full-image-modal .modal-body {
                padding: 20px;
            }

            .full-image-modal .img-fluid {
                max-height: 400px;
                width: auto;
                margin: 0 auto;
                display: block;
            }

            .full-image-modal h3 {
                color: #333;
                margin-bottom: 20px;
            }

            .full-image-modal p {
                margin-bottom: 10px;
            }

            .full-image-modal strong {
                color: #555;
            }
        </style>
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
                            <a style="color: #CE1126;" class="nav-link" href="aboutus.php">About Us</a>
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
    <div id="productResults" class="mt-4"></div>
        <div class="container mt-5">
            <h2>DASHBOARD</h2><br>

            <!-- Uncomment this button when you need to show debug info again -->
            <!--
            <button id="toggle-debug" class="btn btn-secondary mb-3">Toggle Debug Info</button>
            -->

            <!-- Form for adding new products -->
            <form method="POST" class="mb-4" enctype="multipart/form-data">
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
                        <select class="form-control" name="productcategory" id="productcategory" required>
                        <option value="Basketball">Basketball</option>
                        <option value="High-Tops">High-Tops</option>
                        <option value="Running">Running</option>
                        <option value="Sneakers">Sneakers</option>
                        <option value="Hiking">Hiking</option>
                        <option value="Loafers">Loafers</option>
                        <!-- Add other categories as needed -->
                    </select>
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
                <div class="mb-3">
                    <label for="image" class="form-label">Product Image</label>
                    <input type="file" class="form-control" name="image" id="image" accept="image/*" required>
                </div>
                <button type="submit" name="add_product" class="btn btn-success">Add Product</button>
            </form>
            


            <!-- Displaying the Product List -->
            <br><hr><br>
            <div id="productSearchSection" class="container my-4">
                <div class="row g-3">
                <!-- Search Term -->
                    <div class="col-md-4">
                    <input type="text" id="searchTerm" class="form-control" placeholder="Search by product name">
                    </div>

                <!-- Category Dropdown -->
                    <div class="col-md-3">
                    <select id="category" class="form-select">
                        <option value="">All Categories</option>
                        <option value="Basketball">Basketball</option>
                        <option value="High-Tops">High-Tops</option>
                        <option value="Running">Running</option>
                        <option value="Sneakers">Sneakers</option>
                        <option value="Hiking">Hiking</option>
                        <option value="Loafers">Loafers</option>
                    </select>
                    </div>

                <!-- Min Price -->
                    <div class="col-md-2">
                    <input type="number" id="minPrice" class="form-control" placeholder="Min Price" min="0">
                    </div>

                <!-- Max Price -->
                    <div class="col-md-2">
                    <input type="number" id="maxPrice" class="form-control" placeholder="Max Price" min="0">
                    </div>

                <!-- Search Button -->
                    <div class="col-md-1 d-grid">
                        <button onclick="searchProducts()" class="btn btn-danger" style="background-color: #d9534f; border-color: #d9534f;">Search</button>
                </div>

            <h4>Product List</h4>
            
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Image</th>
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
                <tbody id="productList">
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td>
                            <?php 
                            $imageSrc = blobToImage($product['ProductImage']);
                            if (!empty($imageSrc)): 
                            ?>
                                <img src="<?php echo $imageSrc; ?>" 
                                     alt="Product Image" 
                                     class="product-thumbnail"
                                     data-bs-toggle="modal"
                                     data-bs-target="#imageModal"
                                     data-full-image="<?php echo $imageSrc; ?>"
                                     data-name="<?php echo htmlspecialchars($product['productname']); ?>"
                                     data-code="<?php echo htmlspecialchars($product['product_code']); ?>"
                                     data-category="<?php echo htmlspecialchars($product['productcategory']); ?>"
                                     data-description="<?php echo htmlspecialchars($product['description']); ?>"
                                     data-quantity="<?php echo htmlspecialchars($product['quantity']); ?>"
                                     data-price="<?php echo htmlspecialchars($product['price']); ?>"
                                     data-availability="<?php echo htmlspecialchars($product['availability']) ? 'Yes' : 'No'; ?>">
                            <?php else: ?>
                                <span>No image</span>
                            <?php endif; ?>
                        </td>
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

            <!-- Full Image Modal with Product Details -->
            <div class="modal fade full-image-modal" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="imageModalLabel">Product Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <img src="" alt="Full size product image" id="fullSizeImage" class="img-fluid">
                                </div>
                                <div class="col-md-6">
                                    <h3 id="modalProductName"></h3>
                                    <p><strong>Product Code:</strong> <span id="modalProductCode"></span></p>
                                    <p><strong>Category:</strong> <span id="modalProductCategory"></span></p>
                                    <p><strong>Description:</strong> <span id="modalProductDescription"></span></p>
                                    <p><strong>Quantity:</strong> <span id="modalProductQuantity"></span></p>
                                    <p><strong>Price:</strong> $<span id="modalProductPrice"></span></p>
                                    <p><strong>Available:</strong> <span id="modalProductAvailability"></span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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

            // Script to handle full-size image modal with product details
            document.addEventListener('DOMContentLoaded', function() {
                var imageModal = document.getElementById('imageModal');
                imageModal.addEventListener('show.bs.modal', function (event) {
                    var button = event.relatedTarget;
                    var fullImageSrc = button.getAttribute('data-full-image');
                    var modalImage = imageModal.querySelector('#fullSizeImage');
                    modalImage.src = fullImageSrc;

                    // Populate product details
                    document.getElementById('modalProductName').textContent = button.getAttribute('data-name');
                    document.getElementById('modalProductCode').textContent = button.getAttribute('data-code');
                    document.getElementById('modalProductCategory').textContent = button.getAttribute('data-category');
                    document.getElementById('modalProductDescription').textContent = button.getAttribute('data-description');
                    document.getElementById('modalProductQuantity').textContent = button.getAttribute('data-quantity');
                    document.getElementById('modalProductPrice').textContent = button.getAttribute('data-price');
                    document.getElementById('modalProductAvailability').textContent = button.getAttribute('data-availability');
                });
            });

            // Uncomment this script when you need to toggle debug info again
            /*
            document.addEventListener('DOMContentLoaded', function() {
                const toggleDebugBtn = document.getElementById('toggle-debug');
                const debugInfo = document.getElementById('debug-info');

                if (toggleDebugBtn && debugInfo) {
                    debugInfo.style.display = 'none'; // Initially hide debug info

                    toggleDebugBtn.addEventListener('click', function() {
                        if (debugInfo.style.display === 'none') {
                            debugInfo.style.display = 'block';
                        } else {
                            debugInfo.style.display = 'none';
                        }
                    });
                }
            });
            */

            function searchProducts() {
                const searchTerm = document.getElementById('searchTerm').value;
                const category = document.getElementById('category').value;
                const minPrice = document.getElementById('minPrice').value;
                const maxPrice = document.getElementById('maxPrice').value;

                const xhr = new XMLHttpRequest();
                xhr.open("POST", "dashboard.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        document.getElementById("productList").innerHTML = xhr.responseText;
                    }
                };
                xhr.send(`search=true&searchTerm=${searchTerm}&category=${category}&minPrice=${minPrice}&maxPrice=${maxPrice}`);
            }

        </script>
    </body>
</html>
