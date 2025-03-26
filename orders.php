<?php
$pageTitle = 'Manage Orders';
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    $_SESSION['error_message'] = 'You do not have permission to access this page';
    redirect(BASE_URL);
}

// Get all orders
$stmt = $db->prepare("SELECT o.*, u.username 
                     FROM orders o 
                     JOIN users u ON o.user_id = u.user_id 
                     ORDER BY o.order_date DESC");
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

// Include admin header
include 'includes/admin-header.php';
?>

<div class="admin-orders">
    <h1 class="page-title">Manage Orders</h1>
    
    <table class="admin-table">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Date</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Payment Method</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td>#<?php echo $order['order_id']; ?></td>
                    <td><?php echo $order['username']; ?></td>
                    <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                    <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                    <td><?php echo ucfirst($order['status']); ?></td>
                    <td><?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></td>
                    <td>
                        <a href="<?php echo ADMIN_URL; ?>/view-order.php?id=<?php echo $order['order_id']; ?>" class="btn">View</a>
                        <a href="<?php echo ADMIN_URL; ?>/update-order-status.php?id=<?php echo $order['order_id']; ?>" class="btn">Update Status</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/admin-footer.php'; ?>