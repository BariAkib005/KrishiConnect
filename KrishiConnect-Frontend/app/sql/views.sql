-- =====================================================
-- USEFUL VIEWS FOR REPORTS & DASHBOARDS
-- =====================================================

-- =====================================================
-- FARMER VIEWS
-- =====================================================

-- View: Farmer Sales Summary
CREATE VIEW vw_farmer_sales_summary AS
SELECT 
    u.id AS farmer_id,
    u.full_name,
    fp.farm_name,
    fp.location,
    COUNT(DISTINCT p.id) AS active_products,
    COUNT(DISTINCT oi.order_id) AS total_orders,
    SUM(oi.line_total) AS total_revenue,
    SUM(CASE WHEN o.status = 'delivered' THEN oi.quantity ELSE 0 END) AS delivered_quantity,
    AVG(r.rating) AS avg_rating,
    COUNT(r.id) AS total_reviews,
    (
        SELECT SUM(remaining_balance) FROM loans 
        WHERE farmer_id = u.id AND status = 'active'
    ) AS outstanding_loan_balance,
    MAX(o.placed_at) AS last_sale_date
FROM users u
LEFT JOIN farmer_profiles fp ON u.id = fp.user_id
LEFT JOIN products p ON u.id = p.farmer_id AND p.status = 'active'
LEFT JOIN order_items oi ON p.id = oi.product_id
LEFT JOIN orders o ON oi.order_id = o.id
LEFT JOIN reviews r ON p.id = r.product_id
WHERE u.role = 'farmer'
GROUP BY u.id;

-- View: Farmer Loan Status
CREATE VIEW vw_farmer_loan_status AS
SELECT 
    l.id AS loan_id,
    u.id AS farmer_id,
    u.full_name,
    l.loan_type,
    l.principal,
    l.interest_rate,
    l.tenure_months,
    l.remaining_balance,
    l.status,
    l.start_date,
    l.maturity_date,
    DATEDIFF(l.maturity_date, CURDATE()) AS days_to_maturity,
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
        WHERE loan_id = l.id AND status IN ('late', 'overdue')
    ) AS accrued_penalties
FROM loans l
JOIN users u ON l.farmer_id = u.id
WHERE u.role = 'farmer';

-- =====================================================
-- BUYER VIEWS
-- =====================================================

-- View: Buyer Purchase History
CREATE VIEW vw_buyer_purchase_summary AS
SELECT 
    u.id AS buyer_id,
    u.full_name,
    COUNT(DISTINCT o.id) AS total_orders,
    SUM(o.final_amount) AS total_spent,
    AVG(o.final_amount) AS avg_order_value,
    COUNT(DISTINCT oi.farmer_id) AS farmer_count,
    SUM(CASE WHEN o.status = 'delivered' THEN 1 ELSE 0 END) AS delivered_orders,
    SUM(CASE WHEN o.status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_orders,
    (
        SELECT COUNT(*) FROM disputes 
        WHERE opened_by = u.id AND status IN ('open', 'in_review')
    ) AS open_disputes,
    MAX(o.placed_at) AS last_purchase_date
FROM users u
LEFT JOIN orders o ON u.id = o.buyer_id
LEFT JOIN order_items oi ON o.id = oi.order_id
WHERE u.role = 'buyer'
GROUP BY u.id;

-- View: Product Availability & Pricing
CREATE VIEW vw_product_market_comparison AS
SELECT 
    p.id,
    p.name,
    p.variety,
    p.price AS farmer_price,
    p.quantity_available,
    p.unit,
    u.full_name AS farmer_name,
    c.name AS category,
    mp.price AS market_price,
    mp.region,
    ROUND((mp.price - p.price) / NULLIF(p.price, 0) * 100, 2) AS price_difference_pct,
    AVG(r.rating) AS avg_rating,
    COUNT(DISTINCT r.id) AS review_count,
    (
        SELECT COUNT(*) FROM wishlist_items 
        WHERE product_id = p.id
    ) AS wishlist_count
FROM products p
JOIN users u ON p.farmer_id = u.id
JOIN categories c ON p.category_id = c.id
LEFT JOIN market_prices mp ON LOWER(p.name) LIKE LOWER(CONCAT('%', mp.crop_name, '%'))
LEFT JOIN reviews r ON p.id = r.product_id
WHERE p.status = 'active';

-- =====================================================
-- MARKET & ANALYTICS VIEWS
-- =====================================================

-- View: Category Sales Performance
CREATE VIEW vw_category_performance AS
SELECT 
    c.id,
    c.name AS category,
    COUNT(DISTINCT p.id) AS total_products,
    COUNT(DISTINCT p.farmer_id) AS total_farmers,
    COUNT(DISTINCT oi.order_id) AS total_orders,
    SUM(oi.quantity) AS total_quantity_sold,
    SUM(oi.line_total) AS total_revenue,
    AVG(p.price) AS avg_product_price,
    AVG(r.rating) AS category_avg_rating,
    (
        SELECT COUNT(*) FROM wishlist_items 
        WHERE product_id IN (SELECT id FROM products WHERE category_id = c.id)
    ) AS wishlist_count
FROM categories c
LEFT JOIN products p ON c.id = p.category_id
LEFT JOIN order_items oi ON p.id = oi.product_id
LEFT JOIN reviews r ON p.id = r.product_id
WHERE p.status = 'active'
GROUP BY c.id;

-- View: Daily Sales Metrics
CREATE VIEW vw_daily_sales_metrics AS
SELECT 
    DATE(o.placed_at) AS sale_date,
    COUNT(DISTINCT o.id) AS order_count,
    COUNT(DISTINCT o.buyer_id) AS buyer_count,
    COUNT(DISTINCT oi.farmer_id) AS farmer_count,
    SUM(o.final_amount) AS daily_revenue,
    AVG(o.final_amount) AS avg_order_value,
    SUM(oi.quantity) AS total_items_sold,
    COUNT(DISTINCT d.id) AS disputes_opened,
    (
        SELECT COUNT(*) FROM payments 
        WHERE status = 'success' AND DATE(paid_at) = DATE(o.placed_at)
    ) AS successful_payments
FROM orders o
LEFT JOIN order_items oi ON o.id = oi.order_id
LEFT JOIN disputes d ON o.id = d.order_id
WHERE o.status != 'cancelled'
GROUP BY DATE(o.placed_at);

-- =====================================================
-- FINANCE VIEWS
-- =====================================================

-- View: Loan Portfolio Summary
CREATE VIEW vw_loan_portfolio_summary AS
SELECT 
    l.loan_type,
    l.status,
    COUNT(l.id) AS loan_count,
    SUM(l.principal) AS total_principal,
    SUM(l.disbursed_amount) AS total_disbursed,
    SUM(l.remaining_balance) AS outstanding_balance,
    AVG(l.interest_rate) AS avg_interest_rate,
    AVG(l.tenure_months) AS avg_tenure_months,
    ROUND(SUM(l.remaining_balance) / SUM(l.principal) * 100, 2) AS balance_to_principal_ratio
FROM loans l
GROUP BY l.loan_type, l.status;

-- View: Monthly Finance Report
CREATE VIEW vw_monthly_finance_report AS
SELECT 
    YEAR(la.submitted_at) AS year,
    MONTH(la.submitted_at) AS month,
    COUNT(DISTINCT la.id) AS applications_submitted,
    (
        SELECT COUNT(*) FROM loan_applications 
        WHERE status = 'approved' 
        AND YEAR(submitted_at) = YEAR(la.submitted_at) 
        AND MONTH(submitted_at) = MONTH(la.submitted_at)
    ) AS applications_approved,
    (
        SELECT COUNT(*) FROM loan_applications 
        WHERE status = 'rejected' 
        AND YEAR(submitted_at) = YEAR(la.submitted_at) 
        AND MONTH(submitted_at) = MONTH(la.submitted_at)
    ) AS applications_rejected,
    (
        SELECT SUM(principal) FROM loans 
        WHERE YEAR(created_at) = YEAR(la.submitted_at) 
        AND MONTH(created_at) = MONTH(la.submitted_at)
        AND status IN ('active', 'completed')
    ) AS amount_disbursed,
    (
        SELECT SUM(paid_amount) FROM loan_payments 
        WHERE YEAR(paid_at) = YEAR(la.submitted_at) 
        AND MONTH(paid_at) = MONTH(la.submitted_at)
        AND status = 'paid'
    ) AS amount_repaid
FROM loan_applications la
GROUP BY YEAR(la.submitted_at), MONTH(la.submitted_at);

-- View: Payment Default Risk
CREATE VIEW vw_payment_default_risk AS
SELECT 
    u.id,
    u.full_name,
    l.id AS loan_id,
    l.principal,
    l.remaining_balance,
    DATEDIFF(CURDATE(), MAX(lp.due_date)) AS days_overdue,
    COUNT(CASE WHEN lp.status = 'late' THEN 1 END) AS late_payments,
    COUNT(CASE WHEN lp.status = 'overdue' THEN 1 END) AS overdue_payments,
    SUM(CASE WHEN lp.status IN ('late', 'overdue') THEN lp.total_amount ELSE 0 END) AS pending_amount,
    (
        SELECT AVG(amount) FROM order_items 
        WHERE farmer_id = u.id AND order_id IN (
            SELECT id FROM orders WHERE placed_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
        )
    ) AS recent_avg_order_value,
    CASE 
        WHEN DATEDIFF(CURDATE(), MAX(lp.due_date)) > 90 THEN 'Critical'
        WHEN DATEDIFF(CURDATE(), MAX(lp.due_date)) > 30 THEN 'High'
        WHEN DATEDIFF(CURDATE(), MAX(lp.due_date)) > 0 THEN 'Medium'
        ELSE 'Low'
    END AS risk_level
FROM users u
JOIN loans l ON u.id = l.farmer_id
LEFT JOIN loan_payments lp ON l.id = lp.loan_id
WHERE l.status = 'active'
GROUP BY u.id, l.id;

-- =====================================================
-- QUALITY & COMPLIANCE VIEWS
-- =====================================================

-- View: User Compliance Status
CREATE VIEW vw_user_compliance_status AS
SELECT 
    u.id,
    u.full_name,
    u.role,
    u.status,
    u.created_at,
    DATEDIFF(NOW(), u.created_at) AS days_registered,
    CASE 
        WHEN u.role = 'farmer' THEN (
            SELECT kyc_status FROM farmer_profiles WHERE user_id = u.id
        )
        ELSE 'N/A'
    END AS kyc_status,
    (
        SELECT COUNT(*) FROM farmer_documents WHERE farmer_id = u.id
    ) AS documents_count,
    (
        SELECT COUNT(*) FROM disputes WHERE opened_by = u.id
    ) AS disputes_opened,
    (
        SELECT COUNT(*) FROM reviews WHERE reviewer_id = u.id AND status = 'approved'
    ) AS verified_reviews,
    CASE 
        WHEN (u.role = 'farmer' AND (SELECT kyc_status FROM farmer_profiles WHERE user_id = u.id) = 'verified') 
            THEN 'Verified'
        WHEN u.status = 'suspended' THEN 'Suspended'
        WHEN u.status = 'inactive' THEN 'Inactive'
        ELSE 'Active'
    END AS compliance_status
FROM users u;

-- View: Top Performing Entities
CREATE VIEW vw_top_performers AS
SELECT 
    'Farmer' AS entity_type,
    u.id,
    u.full_name,
    (
        SELECT COUNT(DISTINCT o.id) FROM orders o 
        JOIN order_items oi ON o.id = oi.order_id 
        WHERE oi.farmer_id = u.id AND o.status = 'delivered'
    ) AS transaction_count,
    (
        SELECT SUM(oi.line_total) FROM order_items oi 
        JOIN orders o ON oi.order_id = o.id 
        WHERE oi.farmer_id = u.id AND o.status = 'delivered'
    ) AS total_revenue,
    (
        SELECT AVG(r.rating) FROM reviews r 
        WHERE r.reviewed_user_id = u.id
    ) AS avg_rating
FROM users u
WHERE u.role = 'farmer'

UNION ALL

SELECT 
    'Buyer' AS entity_type,
    u.id,
    u.full_name,
    COUNT(DISTINCT o.id) AS transaction_count,
    SUM(o.final_amount) AS total_revenue,
    (
        SELECT AVG(r.rating) FROM reviews r 
        WHERE r.reviewer_id = u.id
    ) AS avg_rating
FROM users u
LEFT JOIN orders o ON u.id = o.buyer_id
WHERE u.role = 'buyer'
GROUP BY u.id;
