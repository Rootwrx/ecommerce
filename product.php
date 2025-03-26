<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get product details
$product = getProductById($product_id);

// If product not found, redirect to home
if (!$product) {
    $_SESSION['error_message'] = 'Product not found';
    redirect(BASE_URL);
}

$pageTitle = $product['title'];

// Get related products
$relatedProducts = getProducts($product['category_name'], null, null, 4);

include 'includes/header.php';
?>

<div class="product-detail">
    <div class="product-detail-image">
        <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['title']; ?>">
    </div>
    
    <div class="product-detail-info">
        <h1 class="product-detail-title"><?php echo $product['title']; ?></h1>
        
        <div class="product-detail-price">$<?php echo number_format($product['price'], 2); ?></div>
        
        <div class="product-detail-category">
            Category: <a href="<?php echo BASE_URL; ?>/search.php?category=<?php echo urlencode($product['category_name']); ?>"><?php echo $product['category_name']; ?></a>
        </div>
        
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
            <div class="rating-count">(<?php echo $product['rating_count']; ?> reviews)</div>
        </div>
        
        <div class="product-detail-description">
            <h3>Description</h3>
            <p><?php echo $product['description']; ?></p>
        </div>
        
        <?php if (isLoggedIn()): ?>
            <form action="<?php echo BASE_URL; ?>/cart-actions.php" method="POST">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                
                <div class="quantity-input">
                    <label for="quantity">Quantity:</label>
                    <button type="button" class="quantity-decrease">-</button>
                    <input type="number" id="quantity" name="quantity" value="1" min="1">
                    <button type="button" class="quantity-increase">+</button>
                </div>
                
                <div class="product-detail-actions">
                    <button type="submit" class="btn">Add to Cart</button>
                    <a href="<?php echo BASE_URL; ?>" class="btn">Continue Shopping</a>
                </div>
            </form>
        <?php else: ?>
            <p>Please <a href="<?php echo BASE_URL; ?>/login.php">login</a> to add items to your cart.</p>
        <?php endif; ?>
    </div>
</div>

<section class="related-products">
    <h2 class="section-title">Related Products</h2>
    <div class="product-grid">
        <?php foreach ($relatedProducts as $relatedProduct): ?>
            <?php if ($relatedProduct['product_id'] != $product_id): ?>
                <div class="product-card">
                    <div class="product-image">
                        <a href="<?php echo BASE_URL; ?>/product.php?id=<?php echo $relatedProduct['product_id']; ?>">
                            <img src="<?php echo $relatedProduct['image']; ?>" alt="<?php echo $relatedProduct['title']; ?>">
                        </a>
                    </div>
                    <div class="product-details">
                        <h3 class="product-title">
                            <a href="<?php echo BASE_URL; ?>/product.php?id=<?php echo $relatedProduct['product_id']; ?>">
                                <?php echo $relatedProduct['title']; ?>
                            </a>
                        </h3>
                        <div class="product-price">$<?php echo number_format($relatedProduct['price'], 2); ?></div>
                        <div class="product-category"><?php echo $relatedProduct['category_name']; ?></div>
                        <div class="product-actions">
                            <a href="<?php echo BASE_URL; ?>/product.php?id=<?php echo $relatedProduct['product_id']; ?>" class="btn">View Details</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>