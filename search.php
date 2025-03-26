<?php
$pageTitle = 'Search Products';
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Get search parameters
$search = isset($_GET['q']) ? sanitize($_GET['q']) : '';

// Fix for category parameter - ensure it's a string, not an array
// $category = isset($_GET['category']) ? sanitize(is_array($_GET['category']) ? $_GET['category'][0] : $_GET['category']) : '';
$category = isset($_GET['category']) ? sanitize($_GET['category']) : '';

// Fix sorting parameter and set default
$sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : '';

// Set price filter, ensuring they're floats
$min_price = isset($_GET['min_price']) ? (float) $_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float) $_GET['max_price'] : 1000;

// Get all categories for filter
$categories = getCategories();

// Get search parameters
// Fix for category parameter - ensure it's a string, not an array
if (isset($_GET['category'])) {
    if (is_array($_GET['category']) && !empty($_GET['category'])) {
        // If it's an array with at least one element, take the first one
        $category = sanitize($_GET['category'][0]);
    } else {
        // If it's not an array or it's an empty array, sanitize the single value
        $category = sanitize($_GET['category']);
    }
} else {
    $category = '';
}

// Get products based on filters
$products = getProducts($category, $search, $sort);

// Filter by price (done after query, but could be done in query for better performance)
$filtered_products = array_filter($products, function ($product) use ($min_price, $max_price) {
    return $product['price'] >= $min_price && $product['price'] <= $max_price;
});

// Reassign the filtered products back to the $products variable
$products = array_values($filtered_products);

include 'includes/header.php';
?>

<h1 class="page-title">
    <?php
    if (!empty($search)) {
        echo 'Search Results for "' . htmlspecialchars($search) . '"';
    } elseif (!empty($category)) {
        // Ensure category is a string before passing to htmlspecialchars
        $display_category = is_array($category) ? (isset($category[0]) ? $category[0] : '') : $category;
        // If the category is empty or invalid, fall back to "Products"
        echo 'Products in ' . htmlspecialchars($display_category);
    } else {
        echo 'All Products';
    }
    ?>
</h1>

<div class="search-container">
    <div class="filter-sidebar">
        <h2 class="filter-title">Filter Products</h2>

        <form action="<?php echo BASE_URL; ?>/search.php" method="GET">
            <?php if (!empty($search)): ?>
                <input type="hidden" name="q" value="<?php echo htmlspecialchars($search); ?>">
            <?php endif; ?>

            <div class="filter-section">
                <h3 class="filter-subtitle">Categories</h3>
                <div class="filter-options">
                    <label>
                        <input type="radio" name="category" value="" <?php echo empty($category) ? 'checked' : ''; ?>>
                        All Categories
                    </label>
                    <?php foreach ($categories as $cat): ?>
                        <label>
                            <?php
                            // $cat_value is already a string so no need for array handling
                            $cat_value = $cat['name'];
                            ?>
                            <input type="radio" name="category" value="<?php echo htmlspecialchars($cat_value); ?>" <?php echo ($category === $cat_value) ? 'checked' : ''; ?>>
                            <span>
                                <?php echo htmlspecialchars($cat_value); ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="filter-section">
                <h3 class="filter-subtitle">Price Range</h3>
                <div class="price-range">
                    <input type="number" name="min_price" value="<?php echo $min_price; ?>" min="0" placeholder="Min">
                    <span>to</span>
                    <input type="number" name="max_price" value="<?php echo $max_price; ?>" min="0" placeholder="Max">
                </div>
            </div>

            <div class="filter-section">
                <h3 class="filter-subtitle">Sort By</h3>
                <div class="filter-options">
                    <label>
                        <input type="radio" name="sort" value="" <?php echo empty($sort) ? 'checked' : ''; ?>>
                        Default
                    </label>
                    <label>
                        <input type="radio" name="sort" value="price_asc" <?php echo $sort === 'price_asc' ? 'checked' : ''; ?>>
                        Price: Low to High
                    </label>
                    <label>
                        <input type="radio" name="sort" value="price_desc" <?php echo $sort === 'price_desc' ? 'checked' : ''; ?>>
                        Price: High to Low
                    </label>
                    <label>
                        <input type="radio" name="sort" value="name_asc" <?php echo $sort === 'name_asc' ? 'checked' : ''; ?>>
                        Name: A to Z
                    </label>
                    <label>
                        <input type="radio" name="sort" value="name_desc" <?php echo $sort === 'name_desc' ? 'checked' : ''; ?>>
                        Name: Z to A
                    </label>
                    <label>
                        <input type="radio" name="sort" value="rating" <?php echo $sort === 'rating' ? 'checked' : ''; ?>>
                        Highest Rated
                    </label>
                </div>
            </div>

            <button type="submit" class="btn">Apply Filters</button>
        </form>
    </div>

    <div class="search-results">
        <?php if (empty($products)): ?>
            <div class="no-results">
                <p>No products found matching your criteria.</p>
                <p><a href="<?php echo BASE_URL; ?>/search.php">Clear all filters</a></p>
            </div>
        <?php else: ?>
            <div class="product-count">
                <p><?php echo count($products); ?> products found</p>
            </div>

            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <a href="<?php echo BASE_URL; ?>/product.php?id=<?php echo $product['product_id']; ?>">
                                <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['title']; ?>">
                            </a>
                        </div>
                        <div class="product-details">
                            <h3 class="product-title">
                                <a href="<?php echo BASE_URL; ?>/product.php?id=<?php echo $product['product_id']; ?>">
                                    <?php echo $product['title']; ?>
                                </a>
                            </h3>
                            <div class="product-price">$<?php echo number_format($product['price'], 2); ?></div>
                            <div class="product-category"><?php echo $product['category_name']; ?></div>
                            <div class="product-rating">
                                <div class="rating-stars">
                                    <?php
                                    $rating = round($product['rating_rate']);
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $rating) {
                                            echo '★';
                                        } else {
                                            echo '☆';
                                        }
                                    }
                                    ?>
                                </div>
                                <div class="rating-count">(<?php echo $product['rating_count']; ?>)</div>
                            </div>
                            <div class="product-actions">
                                <a href="<?php echo BASE_URL; ?>/product.php?id=<?php echo $product['product_id']; ?>"
                                    class="btn">View Details</a>
                                <?php if (isLoggedIn()): ?>
                                    <form action="<?php echo BASE_URL; ?>/cart-actions.php" method="POST">
                                        <input type="hidden" name="action" value="add">
                                        <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" class="btn">Add to Cart</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>