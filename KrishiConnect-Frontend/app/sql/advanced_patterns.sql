-- =====================================================
-- ADVANCED QUERY PATTERNS & BEST PRACTICES
-- =====================================================

-- =====================================================
-- 1. WINDOW FUNCTIONS (MySQL 8.0+)
-- =====================================================

-- Get sales rank for each farmer per month
SELECT 
    DATE_TRUNC(o.placed_at, MONTH) AS sale_month,
    oi.farmer_id,
    u.full_name,
    SUM(oi.line_total) AS monthly_revenue,
    RANK() OVER (PARTITION BY DATE_TRUNC(o.placed_at, MONTH) ORDER BY SUM(oi.line_total) DESC) AS revenue_rank,
    ROW_NUMBER() OVER (PARTITION BY oi.farmer_id ORDER BY o.placed_at DESC) AS transaction_num
FROM order_items oi
JOIN orders o ON oi.order_id = o.id
JOIN users u ON oi.farmer_id = u.id
WHERE o.status = 'delivered'
GROUP BY sale_month, oi.farmer_id;

-- Get running total of loan payments
SELECT 
    lp.loan_id,
    lp.payment_number,
    lp.total_amount,
    lp.due_date,
    SUM(lp.total_amount) OVER (
        PARTITION BY lp.loan_id 
        ORDER BY lp.payment_number
    ) AS running_total,
    LAG(lp.total_amount) OVER (
        PARTITION BY lp.loan_id 
        ORDER BY lp.payment_number
    ) AS prev_payment,
    LEAD(lp.total_amount) OVER (
        PARTITION BY lp.loan_id 
        ORDER BY lp.payment_number
    ) AS next_payment
FROM loan_payments lp
WHERE lp.status IN ('paid', 'due', 'late');

-- =====================================================
-- 2. RECURSIVE COMMON TABLE EXPRESSIONS (CTE)
-- =====================================================

-- Generate payment schedule for new loan
WITH RECURSIVE payment_schedule AS (
    -- Base case: first payment
    SELECT 
        1 as payment_number,
        100000 / 12 as principal,
        (100000 * 10) / (100 * 12) as interest,
        DATE_ADD(CURDATE(), INTERVAL 1 MONTH) as due_date
    UNION ALL
    -- Recursive case: generate next payment
    SELECT 
        payment_number + 1,
        100000 / 12,
        ((100000 - (payment_number * 100000 / 12)) * 10) / (100 * 12),
        DATE_ADD(due_date, INTERVAL 1 MONTH)
    FROM payment_schedule
    WHERE payment_number < 12
)
SELECT 
    payment_number,
    principal,
    interest,
    principal + interest as total_payment,
    due_date
FROM payment_schedule;

-- Generate farmer hierarchy for org chart
WITH RECURSIVE farmer_network AS (
    -- Base: top farmers by revenue
    SELECT 
        u.id,
        u.full_name,
        1 as level,
        CAST(u.id AS CHAR(500)) as path
    FROM users u
    LEFT JOIN order_items oi ON u.id = oi.farmer_id
    LEFT JOIN orders o ON oi.order_id = o.id
    WHERE u.role = 'farmer'
    GROUP BY u.id
    HAVING SUM(oi.line_total) > 100000
    UNION ALL
    -- Recursive: related farmers
    SELECT 
        u.id,
        u.full_name,
        fn.level + 1,
        CONCAT(fn.path, ',', u.id)
    FROM users u
    JOIN order_items oi ON u.id = oi.farmer_id
    JOIN farmer_network fn ON fn.id = (
        SELECT farmer_id FROM order_items 
        WHERE farmer_id != u.id 
        LIMIT 1
    )
    WHERE fn.level < 3
)
SELECT * FROM farmer_network;

-- =====================================================
-- 3. MULTIPLE AGGREGATIONS IN SINGLE QUERY
-- =====================================================

-- Comprehensive sales analytics
SELECT 
    DATE_TRUNC(o.placed_at, MONTH) as month,
    COUNT(DISTINCT o.id) as order_count,
    COUNT(DISTINCT o.buyer_id) as buyer_count,
    COUNT(DISTINCT oi.farmer_id) as farmer_count,
    
    -- Revenue metrics
    SUM(o.final_amount) as total_revenue,
    AVG(o.final_amount) as avg_order_value,
    MIN(o.final_amount) as min_order,
    MAX(o.final_amount) as max_order,
    STDDEV(o.final_amount) as revenue_stddev,
    
    -- Quantity metrics
    SUM(oi.quantity) as total_items_sold,
    AVG(oi.quantity) as avg_items_per_order,
    
    -- Product metrics
    COUNT(DISTINCT oi.product_id) as unique_products,
    
    -- Payment metrics
    SUM(CASE WHEN o.payment_status = 'paid' THEN o.final_amount ELSE 0 END) as paid_amount,
    SUM(CASE WHEN o.payment_status = 'unpaid' THEN o.final_amount ELSE 0 END) as unpaid_amount,
    
    -- Status breakdown
    SUM(CASE WHEN o.status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
    SUM(CASE WHEN o.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
    
    -- Rating metrics
    COUNT(DISTINCT r.id) as total_reviews,
    AVG(r.rating) as avg_rating,
    
    -- Quality metrics
    SUM(CASE WHEN r.rating >= 4 THEN 1 ELSE 0 END) as positive_reviews,
    SUM(CASE WHEN r.rating < 3 THEN 1 ELSE 0 END) as negative_reviews
FROM orders o
LEFT JOIN order_items oi ON o.id = oi.order_id
LEFT JOIN reviews r ON o.id = r.order_id
WHERE o.status != 'cancelled'
GROUP BY DATE_TRUNC(o.placed_at, MONTH)
ORDER BY month DESC;

-- =====================================================
-- 4. CONDITIONAL AGGREGATIONS
-- =====================================================

-- Payment status distribution
SELECT 
    u.full_name,
    SUM(CASE WHEN lp.status = 'paid' THEN lp.total_amount ELSE 0 END) as paid,
    SUM(CASE WHEN lp.status = 'due' THEN lp.total_amount ELSE 0 END) as due,
    SUM(CASE WHEN lp.status = 'late' THEN lp.total_amount ELSE 0 END) as late,
    SUM(CASE WHEN lp.status = 'overdue' THEN lp.total_amount ELSE 0 END) as overdue,
    SUM(lp.penalty_amount) as total_penalties,
    ROUND(
        SUM(CASE WHEN lp.status = 'paid' THEN lp.total_amount ELSE 0 END) / 
        SUM(lp.total_amount) * 100, 2
    ) as payment_completion_rate
FROM users u
JOIN loans l ON u.id = l.farmer_id
JOIN loan_payments lp ON l.id = lp.loan_id
GROUP BY u.id;

-- =====================================================
-- 5. SELF-JOIN FOR COMPARISONS
-- =====================================================

-- Compare farmer performance month-over-month
SELECT 
    MONTH(o1.placed_at) as month,
    u.full_name,
    
    SUM(CASE WHEN YEAR(o1.placed_at) = 2026 THEN oi1.line_total ELSE 0 END) as current_year_revenue,
    SUM(CASE WHEN YEAR(o2.placed_at) = 2025 THEN oi2.line_total ELSE 0 END) as previous_year_revenue,
    
    ROUND(
        (SUM(CASE WHEN YEAR(o1.placed_at) = 2026 THEN oi1.line_total ELSE 0 END) -
         SUM(CASE WHEN YEAR(o2.placed_at) = 2025 THEN oi2.line_total ELSE 0 END)) /
        SUM(CASE WHEN YEAR(o2.placed_at) = 2025 THEN oi2.line_total ELSE 0 END) * 100, 2
    ) as yoy_growth_percent
FROM users u
LEFT JOIN orders o1 ON u.id = (SELECT farmer_id FROM order_items WHERE order_id = o1.id LIMIT 1)
LEFT JOIN order_items oi1 ON o1.id = oi1.order_id AND YEAR(o1.placed_at) = 2026
LEFT JOIN orders o2 ON u.id = (SELECT farmer_id FROM order_items WHERE order_id = o2.id LIMIT 1)
LEFT JOIN order_items oi2 ON o2.id = oi2.order_id AND YEAR(o2.placed_at) = 2025
WHERE u.role = 'farmer'
GROUP BY MONTH(o1.placed_at), u.id
ORDER BY u.full_name, month;

-- =====================================================
-- 6. SUBQUERY IN SELECT CLAUSE
-- =====================================================

-- Get all orders with additional calculated fields
SELECT 
    o.id,
    o.order_number,
    o.final_amount,
    
    -- Dynamic subqueries
    (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count,
    (SELECT SUM(quantity) FROM order_items WHERE order_id = o.id) as total_quantity,
    (SELECT AVG(rating) FROM reviews WHERE order_id = o.id) as avg_review_rating,
    (SELECT COUNT(*) FROM reviews WHERE order_id = o.id) as review_count,
    
    -- Farmer info
    (SELECT u.full_name FROM order_items oi 
     JOIN users u ON oi.farmer_id = u.id 
     WHERE oi.order_id = o.id LIMIT 1) as farmer_name,
    
    -- Payment info
    (SELECT status FROM payments WHERE order_id = o.id LIMIT 1) as payment_status,
    (SELECT SUM(amount) FROM payments WHERE order_id = o.id) as total_paid,
    
    -- Status checks
    CASE 
        WHEN o.status = 'delivered' AND 
             (SELECT COUNT(*) FROM reviews WHERE order_id = o.id) = 0 
        THEN 'Awaiting Review'
        ELSE o.status 
    END as effective_status
FROM orders o
WHERE o.placed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY);

-- =====================================================
-- 7. UNION FOR DATA CONSOLIDATION
-- =====================================================

-- Consolidate all user activities
SELECT 
    u.id,
    u.full_name,
    'order_placed' as activity_type,
    COUNT(*) as count,
    MAX(o.placed_at) as last_activity
FROM users u
LEFT JOIN orders o ON u.id = o.buyer_id
WHERE u.role = 'buyer'
GROUP BY u.id

UNION ALL

SELECT 
    u.id,
    u.full_name,
    'product_sold' as activity_type,
    COUNT(*) as count,
    MAX(o.placed_at) as last_activity
FROM users u
LEFT JOIN order_items oi ON u.id = oi.farmer_id
LEFT JOIN orders o ON oi.order_id = o.id
WHERE u.role = 'farmer'
GROUP BY u.id

UNION ALL

SELECT 
    u.id,
    u.full_name,
    'loan_payment' as activity_type,
    COUNT(*) as count,
    MAX(lp.paid_at) as last_activity
FROM users u
LEFT JOIN loans l ON u.id = l.farmer_id
LEFT JOIN loan_payments lp ON l.id = lp.loan_id
WHERE u.role = 'farmer' AND lp.status = 'paid'
GROUP BY u.id

ORDER BY id, last_activity DESC;

-- =====================================================
-- 8. CASE STATEMENTS FOR CATEGORIZATION
-- =====================================================

-- Categorize users by activity level and spending
SELECT 
    u.id,
    u.full_name,
    COUNT(DISTINCT o.id) as order_count,
    SUM(o.final_amount) as total_spent,
    
    CASE 
        WHEN SUM(o.final_amount) IS NULL THEN 'Inactive'
        WHEN SUM(o.final_amount) < 10000 THEN 'Low Spender'
        WHEN SUM(o.final_amount) < 50000 THEN 'Medium Spender'
        WHEN SUM(o.final_amount) < 100000 THEN 'High Spender'
        ELSE 'Premium Customer'
    END as spending_category,
    
    CASE 
        WHEN COUNT(DISTINCT o.id) = 0 THEN 'No Activity'
        WHEN DATEDIFF(NOW(), MAX(o.placed_at)) > 90 THEN 'Dormant'
        WHEN DATEDIFF(NOW(), MAX(o.placed_at)) > 30 THEN 'Low Activity'
        WHEN DATEDIFF(NOW(), MAX(o.placed_at)) > 7 THEN 'Moderate Activity'
        ELSE 'Active'
    END as activity_category,
    
    CASE 
        WHEN AVG(r.rating) IS NULL THEN 'Unrated'
        WHEN AVG(r.rating) >= 4.5 THEN 'Excellent'
        WHEN AVG(r.rating) >= 4 THEN 'Good'
        WHEN AVG(r.rating) >= 3 THEN 'Average'
        ELSE 'Poor'
    END as rating_category,
    
    DATEDIFF(NOW(), MAX(o.placed_at)) as days_since_last_order,
    AVG(r.rating) as avg_rating
FROM users u
LEFT JOIN orders o ON u.id = o.buyer_id
LEFT JOIN reviews r ON u.id = r.reviewer_id
WHERE u.role = 'buyer'
GROUP BY u.id
ORDER BY total_spent DESC;

-- =====================================================
-- 9. HAVING CLAUSE FOR ADVANCED FILTERING
-- =====================================================

-- Find products with declining sales trend
SELECT 
    p.id,
    p.name,
    u.full_name as farmer_name,
    
    -- Current period (last 30 days)
    (SELECT SUM(oi.quantity) FROM order_items oi
     WHERE oi.product_id = p.id
     AND oi.order_id IN (
        SELECT id FROM orders WHERE placed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     )) as sales_last_30d,
    
    -- Previous period (30-60 days ago)
    (SELECT SUM(oi.quantity) FROM order_items oi
     WHERE oi.product_id = p.id
     AND oi.order_id IN (
        SELECT id FROM orders WHERE placed_at BETWEEN DATE_SUB(NOW(), INTERVAL 60 DAY) 
                                                  AND DATE_SUB(NOW(), INTERVAL 30 DAY)
     )) as sales_30_60d,
    
    -- Trend
    ROUND(
        ((SELECT COALESCE(SUM(oi.quantity), 0) FROM order_items oi
          WHERE oi.product_id = p.id
          AND oi.order_id IN (SELECT id FROM orders WHERE placed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY))) -
         (SELECT COALESCE(SUM(oi.quantity), 0) FROM order_items oi
          WHERE oi.product_id = p.id
          AND oi.order_id IN (SELECT id FROM orders WHERE placed_at BETWEEN DATE_SUB(NOW(), INTERVAL 60 DAY) 
                                                                         AND DATE_SUB(NOW(), INTERVAL 30 DAY)))) / 
        NULLIF((SELECT COALESCE(SUM(oi.quantity), 1) FROM order_items oi
                WHERE oi.product_id = p.id
                AND oi.order_id IN (SELECT id FROM orders WHERE placed_at BETWEEN DATE_SUB(NOW(), INTERVAL 60 DAY) 
                                                                              AND DATE_SUB(NOW(), INTERVAL 30 DAY))
               ), 0) * 100, 2
    ) as decline_percent
FROM products p
JOIN users u ON p.farmer_id = u.id
HAVING sales_last_30d < (sales_30_60d * 0.8)  -- Declined by 20%+
ORDER BY decline_percent ASC;

-- =====================================================
-- 10. PIVOT TABLE SIMULATION
-- =====================================================

-- Loan payment status pivot table
SELECT 
    MONTH(lp.due_date) as month,
    YEAR(lp.due_date) as year,
    
    SUM(CASE WHEN lp.status = 'paid' THEN 1 ELSE 0 END) as paid_count,
    SUM(CASE WHEN lp.status = 'due' THEN 1 ELSE 0 END) as due_count,
    SUM(CASE WHEN lp.status = 'late' THEN 1 ELSE 0 END) as late_count,
    SUM(CASE WHEN lp.status = 'overdue' THEN 1 ELSE 0 END) as overdue_count,
    
    SUM(CASE WHEN lp.status = 'paid' THEN lp.total_amount ELSE 0 END) as paid_amount,
    SUM(CASE WHEN lp.status = 'due' THEN lp.total_amount ELSE 0 END) as due_amount,
    SUM(CASE WHEN lp.status = 'late' THEN lp.total_amount ELSE 0 END) as late_amount,
    SUM(CASE WHEN lp.status = 'overdue' THEN lp.total_amount ELSE 0 END) as overdue_amount,
    
    COUNT(*) as total_payments,
    SUM(lp.total_amount) as total_amount
FROM loan_payments lp
GROUP BY YEAR(lp.due_date), MONTH(lp.due_date)
ORDER BY year DESC, month DESC;

-- =====================================================
-- PERFORMANCE TIPS
-- =====================================================

/*
1. INDEX USAGE
   - Always check EXPLAIN for full index scans
   - Use composite indexes on (farmer_id, status) instead of separate indexes
   - Avoid functions in WHERE clause that prevent index usage

2. JOIN OPTIMIZATION
   - Join smaller tables first
   - Use INNER JOIN instead of LEFT JOIN when possible
   - Avoid multiple LEFT JOINs; consider temporary tables

3. SUBQUERY OPTIMIZATION
   - Use EXISTS instead of IN for large datasets
   - Move subqueries to WHERE clause instead of SELECT when possible
   - Consider JOINs instead of subqueries

4. AGGREGATION
   - Use GROUP BY efficiently; don't group by unnecessary columns
   - Filter with WHERE before GROUP BY
   - Use HAVING only for aggregate conditions

5. LIMIT & PAGINATION
   - Always use LIMIT to prevent large result sets
   - Use OFFSET with LIMIT for pagination
   - For large OFFSET, consider keyset pagination

Example of efficient pagination:
SELECT * FROM orders 
WHERE id > (SELECT id FROM orders LIMIT offset, 1)
LIMIT page_size;

6. DISTINCT & GROUP BY
   - GROUP BY is often faster than DISTINCT
   - Don't use DISTINCT with GROUP BY together
   - Check if DISTINCT is really needed

7. NULL HANDLING
   - Use COALESCE() instead of IFNULL() for clarity
   - Index nullable columns if frequently filtered
   - NULL comparisons (IS NULL) use indexes

8. STATISTICS
   - Run ANALYZE TABLE regularly
   - Check slow query log for queries > 1 second
   - Use EXPLAIN EXTENDED for detailed analysis
*/
