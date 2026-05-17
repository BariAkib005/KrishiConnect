<?php
global $conn;
$page_title = "Farmer Dashboard";
require_once '../includes/config.php';

// Check if user is logged in and is a farmer
check_login();
if (!is_farmer()) {
    show_error_message("Access denied. You must be a farmer to access this page.");
    redirect(SITE_URL . '/index.php');
}

// Get farmer details
$user_id = $_SESSION['user_id'];
$sql = "SELECT f.*, u.full_name, u.email, u.phone, u.address, u.district
        FROM farmers f
        JOIN users u ON f.user_id = u.id
        WHERE f.user_id = $user_id";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $farmer = $result->fetch_assoc();
} else {
    show_error_message("Farmer profile not found.");
    redirect(SITE_URL . '/index.php');
}

// Get farmer's products
$sql = "SELECT * FROM products WHERE farmer_id = " . $farmer['id'] . " ORDER BY created_at DESC";
$productsResult = $conn->query($sql);

// Get farmer's pending orders
$sql = "SELECT o.*, b.company_name, u.full_name as buyer_name
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        JOIN farmers f ON p.farmer_id = f.id
        JOIN buyers b ON o.buyer_id = b.id
        JOIN users u ON b.user_id = u.id
        WHERE f.id = " . $farmer['id'] . "
        AND o.status IN ('pending', 'confirmed', 'processing')
        GROUP BY o.id
        ORDER BY o.order_date DESC";
$ordersResult = $conn->query($sql);

// Get farmer's loan applications
$sql = "SELECT * FROM loan_applications WHERE farmer_id = " . $farmer['id'] . " ORDER BY application_date DESC";
$loansResult = $conn->query($sql);

// Include header
include_once '../includes/header.php';
?>

<!-- Dashboard Header -->
<section class="dashboard-header">
    <div class="container">
        <h1>Welcome, <?php echo $farmer['full_name']; ?>!</h1>
        <div class="dashboard-nav">
            <a href="#products" class="btn btn-secondary">My Products</a>
            <a href="#orders" class="btn btn-secondary">Pending Orders</a>
            <a href="#loans" class="btn btn-secondary">Loan Status</a>
            <a href="<?php echo SITE_URL; ?>/pages/farmer-inventory.php" class="btn btn-primary">Manage Inventory</a>
        </div>
    </div>
</section>

<!-- Farmer Stats -->
<section class="section farmer-stats">
    <div class="container">
        <div class="stats-container">
            <div class="stat-item">
                <h3><?php echo $productsResult->num_rows; ?></h3>
                <p>Active Products</p>
            </div>
            <div class="stat-item">
                <h3><?php echo $ordersResult->num_rows; ?></h3>
                <p>Pending Orders</p>
            </div>
            <div class="stat-item">
                <?php
                $totalSales = 0;
                $salesSql = "SELECT SUM(oi.subtotal) as total
                            FROM order_items oi
                            JOIN products p ON oi.product_id = p.id
                            JOIN orders o ON oi.order_id = o.id
                            WHERE p.farmer_id = " . $farmer['id'] . "
                            AND o.status IN ('delivered', 'shipped')";
                $salesResult = $conn->query($salesSql);
                if ($salesResult->num_rows > 0) {
                    $salesData = $salesResult->fetch_assoc();
                    $totalSales = $salesData['total'] ? $salesData['total'] : 0;
                }
                ?>
                <h3>৳<?php echo number_format($totalSales); ?></h3>
                <p>Total Sales</p>
            </div>
            <div class="stat-item">
                <?php
                $loanAmount = 0;
                $loanSql = "SELECT SUM(amount) as total FROM loan_applications 
                            WHERE farmer_id = " . $farmer['id'] . " 
                            AND status = 'disbursed'";
                $loanResult = $conn->query($loanSql);
                if ($loanResult->num_rows > 0) {
                    $loanData = $loanResult->fetch_assoc();
                    $loanAmount = $loanData['total'] ? $loanData['total'] : 0;
                }
                ?>
                <h3>৳<?php echo number_format($loanAmount); ?></h3>
                <p>Active Loans</p>
            </div>
        </div>
    </div>
</section>

<!-- Products Section -->
<section id="products" class="section">
    <div class="container">
        <div class="section-title">
            <h2>My Products</h2>
            <a href="<?php echo SITE_URL; ?>/pages/add-product.php" class="btn btn-primary">Add New Product</a>
        </div>
        
        <?php if ($productsResult->num_rows > 0): ?>
            <div class="products-table">
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Added On</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($product = $productsResult->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="product-info">
                                        <img src="<?php echo $product['image_path']; ?>" alt="<?php echo $product['name']; ?>">
                                        <div>
                                            <h4><?php echo $product['name']; ?></h4>
                                            <p><?php echo $product['category']; ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td>৳<?php echo $product['price']; ?> per <?php echo $product['unit']; ?></td>
                                <td><?php echo $product['quantity_available']; ?> <?php echo $product['unit']; ?></td>
                                <td><?php echo date('d M Y', strtotime($product['created_at'])); ?></td>
                                <td>
                                    <?php if ($product['quantity_available'] > 0): ?>
                                        <span class="status available">Available</span>
                                    <?php else: ?>
                                        <span class="status out-of-stock">Out of Stock</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="<?php echo SITE_URL; ?>/pages/edit-product.php?id=<?php echo $product['id']; ?>" class="btn btn-small">Edit</a>
                                        <a href="<?php echo SITE_URL; ?>/pages/delete-product.php?id=<?php echo $product['id']; ?>" class="btn btn-small btn-danger" onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>You haven't added any products yet.</p>
                <a href="<?php echo SITE_URL; ?>/pages/add-product.php" class="btn btn-primary">Add Your First Product</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Orders Section -->
<section id="orders" class="section">
    <div class="container">
        <div class="section-title">
            <h2>Pending Orders</h2>
        </div>
        
        <?php if ($ordersResult->num_rows > 0): ?>
            <div class="orders-table">
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Buyer</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = $ordersResult->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo $order['buyer_name']; ?> (<?php echo $order['company_name']; ?>)</td>
                                <td><?php echo date('d M Y', strtotime($order['order_date'])); ?></td>
                                <td>৳<?php echo number_format($order['total_amount']); ?></td>
                                <td>
                                    <span class="status <?php echo strtolower($order['status']); ?>"><?php echo ucfirst($order['status']); ?></span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="<?php echo SITE_URL; ?>/pages/view-order.php?id=<?php echo $order['id']; ?>" class="btn btn-small">View</a>
                                        <?php if ($order['status'] == 'pending'): ?>
                                            <a href="<?php echo SITE_URL; ?>/pages/update-order.php?id=<?php echo $order['id']; ?>&status=confirmed" class="btn btn-small btn-primary">Accept</a>
                                            <a href="<?php echo SITE_URL; ?>/pages/update-order.php?id=<?php echo $order['id']; ?>&status=cancelled" class="btn btn-small btn-danger">Reject</a>
                                        <?php elseif ($order['status'] == 'confirmed'): ?>
                                            <a href="<?php echo SITE_URL; ?>/pages/update-order.php?id=<?php echo $order['id']; ?>&status=processing" class="btn btn-small btn-primary">Process</a>
                                        <?php elseif ($order['status'] == 'processing'): ?>
                                            <a href="<?php echo SITE_URL; ?>/pages/update-order.php?id=<?php echo $order['id']; ?>&status=shipped" class="btn btn-small btn-primary">Mark Shipped</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>You don't have any pending orders at the moment.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Loans Section -->
<section id="loans" class="section">
    <div class="container">
        <div class="section-title">
            <h2>Loan Applications</h2>
            <a href="<?php echo SITE_URL; ?>/pages/loan-application.php" class="btn btn-primary">Apply for Loan</a>
        </div>
        
        <?php if ($loansResult->num_rows > 0): ?>
            <div class="loans-table">
                <table>
                    <thead>
                        <tr>
                            <th>Loan ID</th>
                            <th>Amount</th>
                            <th>Purpose</th>
                            <th>Application Date</th>
                            <th>Duration</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($loan = $loansResult->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $loan['id']; ?></td>
                                <td>৳<?php echo number_format($loan['amount']); ?></td>
                                <td><?php echo $loan['purpose']; ?></td>
                                <td><?php echo date('d M Y', strtotime($loan['application_date'])); ?></td>
                                <td><?php echo $loan['duration_months']; ?> months</td>
                                <td>
                                    <span class="status <?php echo strtolower($loan['status']); ?>"><?php echo ucfirst($loan['status']); ?></span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>You haven't applied for any loans yet.</p>
                <a href="<?php echo SITE_URL; ?>/pages/loan-application.php" class="btn btn-primary">Apply for Your First Loan</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
// Include footer
include_once '../includes/footer.php';
?> 