-- =====================================================
-- USEFUL QUERIES WITH JOINS & SUBQUERIES
-- =====================================================

-- =====================================================
-- 1. FARMER DASHBOARD QUERIES
-- =====================================================

-- Get farmer's total sales with product details
SELECT 
    p.id,
    p.name AS product_name,
    p.variety,
    c.name AS category,
    SUM(oi.quantity) AS total_quantity_sold,
    SUM(oi.line_total) AS total_revenue,
    AVG(r.rating) AS avg_rating,
    COUNT(r.id) AS review_count,
    (
        SELECT SUM(quantity) FROM order_items 
        WHERE product_id = p.id AND order_id IN (
            SELECT id FROM orders WHERE status = 'delivered'
        )
    ) AS delivered_quantity
FROM products p
LEFT JOIN categories c ON p.category_id = c.id
LEFT JOIN order_items oi ON p.id = oi.product_id
LEFT JOIN orders o ON oi.order_id = o.id
LEFT JOIN reviews r ON p.id = r.product_id
WHERE p.farmer_id = ?
GROUP BY p.id
ORDER BY total_revenue DESC;

-- Get farmer's monthly sales trends
SELECT 
    DATE_TRUNC(o.placed_at, MONTH) AS month,
    COUNT(DISTINCT oi.order_id) AS order_count,
    SUM(oi.quantity) AS quantity_sold,
    SUM(oi.line_total) AS revenue,
    (
        SELECT COUNT(*) FROM orders 
        WHERE buyer_id IN (
            SELECT id FROM users WHERE role = 'buyer'
        )
        AND placed_at >= DATE_TRUNC(o.placed_at, MONTH)
        AND placed_at < DATE_ADD(DATE_TRUNC(o.placed_at, MONTH), INTERVAL 1 MONTH)
    ) AS market_orders
FROM order_items oi
JOIN orders o ON oi.order_id = o.id
WHERE oi.farmer_id = ?
GROUP BY DATE_TRUNC(o.placed_at, MONTH)
ORDER BY month DESC;

-- Get farmer's loan details with repayment status
SELECT 
    l.id,
    l.loan_type,
    l.principal,
    l.interest_rate,
    l.tenure_months,
    l.status,
    l.remaining_balance,
    (
        SELECT COUNT(*) FROM loan_payments 
        WHERE loan_id = l.id AND status = 'paid'
    ) AS paid_installments,
    (
        SELECT COUNT(*) FROM loan_payments 
        WHERE loan_id = l.id AND status IN ('due', 'late', 'overdue')
    ) AS pending_installments,
    (
        SELECT SUM(penalty_amount) FROM loan_payments 
        WHERE loan_id = l.id
    ) AS total_penalties,
    lp.due_date AS next_due_date,
    lp.total_amount AS next_payment_amount
FROM loans l
LEFT JOIN loan_payments lp ON l.id = lp.loan_id 
    AND lp.status IN ('due', 'late', 'overdue')
    AND lp.due_date = (
        SELECT MIN(due_date) FROM loan_payments 
        WHERE loan_id = l.id AND status IN ('due', 'late', 'overdue')
    )
WHERE l.farmer_id = ?
ORDER BY l.created_at DESC;

-- =====================================================
-- 2. BUYER DASHBOARD QUERIES
-- =====================================================

-- Get buyer's order history with farmer details
SELECT 
    o.id AS order_id,
    o.order_number,
    o.placed_at,
    o.status,
    o.final_amount,
    u.full_name AS farmer_name,
    fp.farm_name,
    GROUP_CONCAT(DISTINCT p.name SEPARATOR ', ') AS products,
    COUNT(DISTINCT oi.product_id) AS product_count,
    SUM(oi.quantity) AS total_quantity,
    AVG(r.rating) AS avg_product_rating
FROM orders o
JOIN order_items oi ON o.id = oi.order_id
JOIN products p ON oi.product_id = p.id
JOIN users u ON p.farmer_id = u.id
LEFT JOIN farmer_profiles fp ON u.id = fp.user_id
LEFT JOIN reviews r ON o.id = r.order_id
WHERE o.buyer_id = ?
GROUP BY o.id
ORDER BY o.placed_at DESC;

-- Get buyer's favorite farmers and products
SELECT 
    u.id AS farmer_id,
    u.full_name AS farmer_name,
    fp.farm_name,
    COUNT(DISTINCT p.id) AS product_count,
    SUM(oi.quantity) AS total_purchased,
    SUM(oi.line_total) AS total_spent,
    AVG(r.rating) AS avg_farmer_rating,
    (
        SELECT COUNT(*) FROM wishlist_items wi 
        WHERE wi.product_id IN (
            SELECT id FROM products WHERE farmer_id = u.id
        )
    ) AS wishlisted_products
FROM users u
JOIN farmer_profiles fp ON u.id = fp.user_id
JOIN products p ON u.id = p.farmer_id
JOIN order_items oi ON p.id = oi.product_id
JOIN orders o ON oi.order_id = o.id
LEFT JOIN reviews r ON p.id = r.product_id
WHERE o.buyer_id = ?
GROUP BY u.id
ORDER BY total_spent DESC;

-- =====================================================
-- 3. FINANCE OFFICER QUERIES
-- =====================================================

-- Get loan portfolio analysis
SELECT 
    l.loan_type,
    COUNT(l.id) AS total_loans,
    SUM(l.principal) AS total_principal,
    SUM(l.disbursed_amount) AS total_disbursed,
    SUM(l.remaining_balance) AS total_outstanding,
    AVG(l.interest_rate) AS avg_interest_rate,
    (
        SELECT COUNT(*) FROM loans l2 
        WHERE l2.status = 'defaulted' 
        AND YEAR(l2.created_at) = YEAR(l.created_at)
    ) AS defaulted_count,
    (
        SELECT SUM(remaining_balance) FROM loans l2 
        WHERE l2.status = 'defaulted'
        AND YEAR(l2.created_at) = YEAR(l.created_at)
    ) AS defaulted_amount
FROM loans l
WHERE l.status IN ('active', 'completed', 'defaulted')
GROUP BY l.loan_type;

-- Get overdue loan payments
SELECT 
    l.id AS loan_id,
    u.full_name AS farmer_name,
    u.phone,
    l.remaining_balance,
    lp.payment_number,
    lp.total_amount,
    lp.due_date,
    DATEDIFF(CURDATE(), lp.due_date) AS days_overdue,
    (lp.total_amount * (l.interest_rate / 100 / 12)) * DATEDIFF(CURDATE(), lp.due_date) AS penalty_accrued,
    (
        SELECT SUM(paid_amount) FROM loan_payments 
        WHERE loan_id = l.id AND status = 'paid'
    ) AS total_repaid
FROM loan_payments lp
JOIN loans l ON lp.loan_id = l.id
JOIN users u ON l.farmer_id = u.id
WHERE lp.status IN ('late', 'overdue')
AND l.status IN ('active', 'defaulted')
ORDER BY days_overdue DESC;

-- Get loan officer's performance
SELECT 
    u.id,
    u.full_name,
    COUNT(DISTINCT la.id) AS applications_reviewed,
    (
        SELECT COUNT(*) FROM loan_applications 
        WHERE reviewed_by = u.id AND status = 'approved'
    ) AS approved_count,
    (
        SELECT COUNT(*) FROM loan_applications 
        WHERE reviewed_by = u.id AND status = 'rejected'
    ) AS rejected_count,
    (
        SELECT SUM(principal) FROM loans 
        WHERE approved_by = u.id
    ) AS total_approved_amount,
    (
        SELECT COUNT(*) FROM loans l 
        WHERE l.approved_by = u.id AND l.status = 'defaulted'
    ) AS default_count
FROM users u
LEFT JOIN loan_applications la ON u.id = la.reviewed_by
WHERE u.role = 'finance'
GROUP BY u.id;

-- =====================================================
-- 4. MARKETPLACE & PRODUCT QUERIES
-- =====================================================

-- Get trending products
SELECT 
    p.id,
    p.name,
    c.name AS category,
    u.full_name AS farmer_name,
    p.price,
    SUM(oi.quantity) AS sales_volume,
    COUNT(DISTINCT o.id) AS order_count,
    AVG(r.rating) AS avg_rating,
    COUNT(r.id) AS review_count,
    (
        SELECT COUNT(*) FROM wishlist_items 
        WHERE product_id = p.id
    ) AS wishlist_count
FROM products p
JOIN categories c ON p.category_id = c.id
JOIN users u ON p.farmer_id = u.id
LEFT JOIN order_items oi ON p.id = oi.product_id
LEFT JOIN orders o ON oi.order_id = o.id AND o.status = 'delivered'
LEFT JOIN reviews r ON p.id = r.product_id
WHERE p.status = 'active'
AND o.placed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY p.id
ORDER BY sales_volume DESC
LIMIT 20;

-- Get product comparison with market prices
SELECT 
    p.id,
    p.name,
    p.variety,
    p.price AS farmer_price,
    mp.price AS market_price,
    ROUND((mp.price - p.price) / p.price * 100, 2) AS price_difference_percent,
    mp.region AS market_region,
    mp.reported_on,
    u.full_name AS farmer_name,
    AVG(r.rating) AS avg_rating,
    COUNT(DISTINCT r.id) AS review_count
FROM products p
LEFT JOIN market_prices mp ON LOWER(p.name) = LOWER(mp.crop_name)
JOIN users u ON p.farmer_id = u.id
LEFT JOIN reviews r ON p.id = r.product_id
WHERE p.status = 'active'
ORDER BY p.created_at DESC;

-- =====================================================
-- 5. REVIEW & RATING QUERIES
-- =====================================================

-- Get detailed farmer reviews
SELECT 
    u.id AS farmer_id,
    u.full_name,
    COUNT(r.id) AS total_reviews,
    AVG(r.rating) AS avg_rating,
    SUM(CASE WHEN r.rating = 5 THEN 1 ELSE 0 END) AS five_star,
    SUM(CASE WHEN r.rating = 4 THEN 1 ELSE 0 END) AS four_star,
    SUM(CASE WHEN r.rating = 3 THEN 1 ELSE 0 END) AS three_star,
    SUM(CASE WHEN r.rating = 2 THEN 1 ELSE 0 END) AS two_star,
    SUM(CASE WHEN r.rating = 1 THEN 1 ELSE 0 END) AS one_star,
    (
        SELECT GROUP_CONCAT(DISTINCT comment SEPARATOR ' | ')
        FROM reviews 
        WHERE reviewed_user_id = u.id AND comment IS NOT NULL
        LIMIT 3
    ) AS recent_comments
FROM users u
LEFT JOIN reviews r ON u.id = r.reviewed_user_id
WHERE u.role = 'farmer'
GROUP BY u.id
HAVING total_reviews > 0
ORDER BY avg_rating DESC;

-- =====================================================
-- 6. DISPUTE & ISSUE RESOLUTION QUERIES
-- =====================================================

-- Get dispute resolution analytics
SELECT 
    d.dispute_type,
    COUNT(d.id) AS total_disputes,
    (
        SELECT COUNT(*) FROM disputes 
        WHERE status = 'resolved' AND dispute_type = d.dispute_type
    ) AS resolved_count,
    ROUND(
        (
            SELECT COUNT(*) FROM disputes 
            WHERE status = 'resolved' AND dispute_type = d.dispute_type
        ) / COUNT(d.id) * 100, 2
    ) AS resolution_rate,
    AVG(DATEDIFF(d.resolved_at, d.created_at)) AS avg_resolution_days,
    d.severity
FROM disputes d
GROUP BY d.dispute_type
ORDER BY total_disputes DESC;

-- Get open disputes requiring attention
SELECT 
    d.id,
    d.title,
    d.dispute_type,
    d.severity,
    DATEDIFF(CURDATE(), DATE(d.created_at)) AS days_open,
    u.full_name AS reported_by,
    o.order_number,
    l.id AS loan_id,
    d.description,
    (
        SELECT COUNT(*) FROM dispute_messages 
        WHERE dispute_id = d.id
    ) AS message_count
FROM disputes d
JOIN users u ON d.opened_by = u.id
LEFT JOIN orders o ON d.order_id = o.id
LEFT JOIN loans l ON d.loan_id = l.id
WHERE d.status IN ('open', 'in_review')
ORDER BY d.severity DESC, DATEDIFF(CURDATE(), DATE(d.created_at)) DESC;

-- =====================================================
-- 7. PAYMENT & FINANCIAL TRANSACTION QUERIES
-- =====================================================

-- Get payment summary by method
SELECT 
    p.method,
    COUNT(p.id) AS total_transactions,
    SUM(p.amount) AS total_amount,
    COUNT(CASE WHEN p.status = 'success' THEN 1 END) AS successful,
    COUNT(CASE WHEN p.status = 'failed' THEN 1 END) AS failed,
    COUNT(CASE WHEN p.status = 'pending' THEN 1 END) AS pending,
    ROUND(
        COUNT(CASE WHEN p.status = 'success' THEN 1 END) / COUNT(p.id) * 100, 2
    ) AS success_rate
FROM payments p
WHERE p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY p.method
ORDER BY total_amount DESC;

-- Get daily revenue trends
SELECT 
    DATE(o.placed_at) AS order_date,
    COUNT(DISTINCT o.id) AS order_count,
    SUM(o.final_amount) AS daily_revenue,
    AVG(o.final_amount) AS avg_order_value,
    COUNT(DISTINCT o.buyer_id) AS unique_buyers,
    (
        SELECT COUNT(DISTINCT farmer_id) FROM order_items oi 
        WHERE oi.order_id IN (
            SELECT id FROM orders WHERE DATE(placed_at) = DATE(o.placed_at)
        )
    ) AS unique_farmers
FROM orders o
WHERE o.status = 'delivered'
AND o.placed_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
GROUP BY DATE(o.placed_at)
ORDER BY order_date DESC;

-- =====================================================
-- 8. USER ACTIVITY & ENGAGEMENT QUERIES
-- =====================================================

-- Get active farmers with growth metrics
SELECT 
    u.id,
    u.full_name,
    fp.farm_name,
    COUNT(DISTINCT p.id) AS product_count,
    COUNT(DISTINCT oi.order_id) AS total_orders,
    SUM(oi.line_total) AS total_revenue,
    (
        SELECT COUNT(*) FROM order_items oi2
        WHERE oi2.farmer_id = u.id
        AND oi2.id IN (
            SELECT id FROM order_items 
            WHERE order_id IN (
                SELECT id FROM orders 
                WHERE placed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            )
        )
    ) AS orders_this_month,
    AVG(r.rating) AS avg_rating,
    COUNT(r.id) AS review_count,
    DATEDIFF(NOW(), MAX(o.placed_at)) AS days_since_last_order
FROM users u
JOIN farmer_profiles fp ON u.id = fp.user_id
LEFT JOIN products p ON u.id = p.farmer_id
LEFT JOIN order_items oi ON p.id = oi.product_id
LEFT JOIN orders o ON oi.order_id = o.id
LEFT JOIN reviews r ON p.id = r.product_id
WHERE u.status = 'active'
GROUP BY u.id
HAVING total_orders > 0
ORDER BY total_revenue DESC;

-- Get buyer engagement score
SELECT 
    u.id,
    u.full_name,
    COUNT(DISTINCT o.id) AS purchase_count,
    SUM(o.final_amount) AS lifetime_value,
    AVG(o.final_amount) AS avg_purchase_value,
    COUNT(DISTINCT wi.product_id) AS wishlisted_count,
    COUNT(DISTINCT r.id) AS reviews_submitted,
    (
        SELECT COUNT(*) FROM messages 
        WHERE sender_id = u.id
        AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ) AS messages_sent_30d,
    DATEDIFF(NOW(), MAX(o.placed_at)) AS days_since_last_purchase
FROM users u
LEFT JOIN orders o ON u.id = o.buyer_id
LEFT JOIN wishlist_items wi ON u.id = (
    SELECT user_id FROM wishlists WHERE id = wi.wishlist_id
)
LEFT JOIN reviews r ON u.id = r.reviewer_id
WHERE u.role = 'buyer' AND u.status = 'active'
GROUP BY u.id
ORDER BY lifetime_value DESC;

-- =====================================================
-- 9. INVENTORY & STOCK QUERIES
-- =====================================================

-- Get low stock products
SELECT 
    p.id,
    p.name,
    p.variety,
    p.quantity_available,
    p.unit,
    u.full_name AS farmer_name,
    c.name AS category,
    (
        SELECT AVG(quantity) FROM order_items oi
        WHERE oi.product_id = p.id
    ) AS avg_quantity_per_order,
    (
        SELECT SUM(quantity) FROM order_items oi
        WHERE oi.product_id = p.id
        AND oi.order_id IN (
            SELECT id FROM orders 
            WHERE placed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        )
    ) AS quantity_sold_30d,
    CEILING(
        COALESCE(
            (SELECT AVG(quantity) FROM order_items WHERE product_id = p.id), 1
        ) * 15
    ) AS recommended_stock
FROM products p
JOIN users u ON p.farmer_id = u.id
JOIN categories c ON p.category_id = c.id
WHERE p.status = 'active'
AND p.quantity_available <= CEILING(
    COALESCE((SELECT AVG(quantity) FROM order_items WHERE product_id = p.id), 1) * 15
)
ORDER BY p.quantity_available ASC;

-- =====================================================
-- 10. KYC & COMPLIANCE QUERIES
-- =====================================================

-- Get KYC verification status
SELECT 
    u.id,
    u.full_name,
    u.email,
    u.phone,
    fp.kyc_status,
    fp.kyc_verified_date,
    vu.full_name AS verified_by,
    fp.bank_account,
    fp.bank_name,
    COUNT(DISTINCT fd.id) AS documents_uploaded,
    COUNT(DISTINCT fc.id) AS certifications,
    DATEDIFF(NOW(), u.created_at) AS days_registered
FROM users u
JOIN farmer_profiles fp ON u.id = fp.user_id
LEFT JOIN users vu ON fp.kyc_verified_by = vu.id
LEFT JOIN farmer_documents fd ON u.id = fd.farmer_id
LEFT JOIN farmer_certifications fc ON u.id = fc.farmer_id
WHERE u.role = 'farmer'
GROUP BY u.id
ORDER BY fp.kyc_status, DATEDIFF(NOW(), u.created_at) DESC;
