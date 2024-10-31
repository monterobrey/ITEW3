 // function to validate user's login information
 function validateLogin() {

    // gets the login information
    const email = document.getElementById('login-email').value.trim();
    const password = document.getElementById('login-password').value.trim();

    // if there is no input email or password
    if (!email || !password) {
        alert('Please fill in all fields.');
        return;
    }

    // if the user's email do not have @
    if (!email.includes('@')) {
        alert('Please enter a valid email address containing "@"');
        return;
    }

}

// Validates user's sign-up information
function validateSignup() {
    const email = document.getElementById('signup-email').value.trim();
    const username = document.getElementById('signup-username').value.trim();
    const password = document.getElementById('signup-password').value.trim();

    // if the user skip to input info of any of these fields
    if (!email || !username || !password) {
        alert('Please fill in all fields.');
        return;
    }

    // if the user's email do not have @
    if (!email.includes('@')) {
        alert('Please enter a valid email address containing "@"');
        return;
    }
}

function searchProducts() {
    // Get filter values
    const searchTerm = document.getElementById("searchTerm").value;
    const category = document.getElementById("category").value;
    const minPrice = document.getElementById("minPrice").value;
    const maxPrice = document.getElementById("maxPrice").value;

    // AJAX request to fetch filtered products
    fetch("search.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            searchTerm: searchTerm,
            category: category,
            minPrice: minPrice,
            maxPrice: maxPrice
        }),
    })
    .then(response => response.json())
    .then(data => {
        const productResults = document.getElementById("productResults");
        productResults.innerHTML = "";  // Clear previous results

        data.forEach(product => {
            productResults.innerHTML += `
                <tr>
                    <td><img src="${product.image}" alt="Product Image" class="product-thumbnail"></td>
                    <td>${product.productname}</td>
                    <td>${product.product_code}</td>
                    <td>${product.productcategory}</td>
                    <td>${product.description}</td>
                    <td>${product.quantity}</td>
                    <td>${product.price}</td>
                    <td>${product.availability ? 'Yes' : 'No'}</td>
                    <td>
                        <a href="#" class="btn btn-warning">Edit</a>
                        <a href="?delete=${product.product_id}" class="btn btn-danger">Delete</a>
                    </td>
                </tr>
            `;
        });
    })
    .catch(error => console.error('Error fetching products:', error));
}
