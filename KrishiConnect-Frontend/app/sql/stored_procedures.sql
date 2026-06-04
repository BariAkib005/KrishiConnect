-- =====================================================
-- STORED PROCEDURES FOR BUSINESS OPERATIONS
-- =====================================================
DELIMITER $$

-- =====================================================
-- ORDER MANAGEMENT PROCEDURES
-- =====================================================

-- Procedure: Create order from cart
CREATE PROCEDURE sp_create_order_from_cart(
    IN p_buyer_id INT,
    IN p_cart_id INT,
    IN p_shipping_address VARCHAR(240),
    IN p_shipping_phone VARCHAR(40),
    IN p_payment_method VARCHAR(40),
    OUT p_order_id INT,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE v_cart_exists INT;
    DECLARE v_cart_items INT;
    DECLARE v_total_amount DECIMAL(12,2);
    DECLARE v_order_number VARCHAR(40);
    DECLARE v_error INT DEFAULT 0;
    
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET v_error = 1;
    
    START TRANSACTION;
    
    -- Check if cart exists
    SELECT COUNT(*) INTO v_cart_exists 
    FROM carts WHERE id = p_cart_id AND user_id = p_buyer_id;
    
    IF v_cart_exists = 0 THEN
        SET p_message = 'Cart not found';
        LEAVE;
    END IF;
    
    -- Check cart items
    SELECT COUNT(*) INTO v_cart_items 
    FROM cart_items WHERE cart_id = p_cart_id;
    
    IF v_cart_items = 0 THEN
        SET p_message = 'Cart is empty';
        LEAVE;
    END IF;
    
    -- Calculate total amount
    SELECT SUM(quantity * unit_price) INTO v_total_amount 
    FROM cart_items WHERE cart_id = p_cart_id;
    
    -- Generate order number
    SET v_order_number = CONCAT('ORD-', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'));
    
    -- Create order
    INSERT INTO orders (
        buyer_id, order_number, total_amount, final_amount, 
        shipping_address, shipping_phone, payment_method, status
    ) VALUES (
        p_buyer_id, v_order_number, v_total_amount, v_total_amount,
        p_shipping_address, p_shipping_phone, p_payment_method, 'pending'
    );
    
    SET p_order_id = LAST_INSERT_ID();
    
    -- Create order items from cart items
    INSERT INTO order_items (order_id, product_id, farmer_id, quantity, unit_price, line_total)
    SELECT 
        p_order_id,
        ci.product_id,
        p.farmer_id,
        ci.quantity,
        ci.unit_price,
        ci.quantity * ci.unit_price
    FROM cart_items ci
    JOIN products p ON ci.product_id = p.id
    WHERE ci.cart_id = p_cart_id;
    
    -- Mark cart as checked out
    UPDATE carts SET status = 'checked_out' WHERE id = p_cart_id;
    
    IF v_error = 0 THEN
        COMMIT;
        SET p_message = 'Order created successfully';
    ELSE
        ROLLBACK;
        SET p_message = 'Error creating order';
        SET p_order_id = NULL;
    END IF;
END$$

-- Procedure: Process loan payment
CREATE PROCEDURE sp_process_loan_payment(
    IN p_loan_payment_id INT,
    IN p_paid_amount DECIMAL(12,2),
    IN p_payment_method VARCHAR(40),
    IN p_transaction_ref VARCHAR(120),
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE v_loan_id INT;
    DECLARE v_loan_status VARCHAR(20);
    DECLARE v_due_amount DECIMAL(12,2);
    DECLARE v_remaining_balance DECIMAL(12,2);
    DECLARE v_principal_paid DECIMAL(12,2);
    DECLARE v_interest_paid DECIMAL(12,2);
    DECLARE v_days_late INT;
    DECLARE v_penalty DECIMAL(10,2);
    DECLARE v_error INT DEFAULT 0;
    
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET v_error = 1;
    
    START TRANSACTION;
    
    -- Get loan payment details
    SELECT l.id, l.status, lp.total_amount, DATEDIFF(CURDATE(), lp.due_date) 
    INTO v_loan_id, v_loan_status, v_due_amount, v_days_late
    FROM loan_payments lp
    JOIN loans l ON lp.loan_id = l.id
    WHERE lp.id = p_loan_payment_id;
    
    -- Validate loan status
    IF v_loan_status NOT IN ('active', 'completed') THEN
        SET p_success = FALSE;
        SET p_message = 'Invalid loan status';
        ROLLBACK;
        LEAVE;
    END IF;
    
    -- Calculate penalty if late
    SET v_penalty = 0;
    IF v_days_late > 0 THEN
        SET v_penalty = v_due_amount * 0.01 * v_days_late; -- 1% per day
    END IF;
    
    -- Update loan payment
    UPDATE loan_payments 
    SET paid_at = NOW(),
        paid_amount = p_paid_amount,
        status = CASE 
            WHEN v_days_late > 0 THEN 'paid'
            ELSE 'paid'
        END,
        penalty_amount = v_penalty,
        payment_method = p_payment_method,
        transaction_ref = p_transaction_ref
    WHERE id = p_loan_payment_id;
    
    -- Get principal and interest split
    SELECT principal_amount, interest_amount 
    INTO v_principal_paid, v_interest_paid
    FROM loan_payments WHERE id = p_loan_payment_id;
    
    -- Update loan balance
    UPDATE loans 
    SET remaining_balance = remaining_balance - COALESCE(v_principal_paid, 0),
        total_paid = total_paid + p_paid_amount
    WHERE id = v_loan_id;
    
    -- Check if loan is fully paid
    SELECT remaining_balance INTO v_remaining_balance FROM loans WHERE id = v_loan_id;
    
    IF v_remaining_balance <= 0 THEN
        UPDATE loans SET status = 'completed' WHERE id = v_loan_id;
    END IF;
    
    -- Create notification for farmer
    INSERT INTO notifications (user_id, type, title, message)
    SELECT l.farmer_id, 'loan_payment', 'Loan Payment Received',
           CONCAT('Payment of ', p_paid_amount, ' has been received successfully')
    FROM loans l WHERE l.id = v_loan_id;
    
    IF v_error = 0 THEN
        COMMIT;
        SET p_success = TRUE;
        SET p_message = 'Payment processed successfully';
    ELSE
        ROLLBACK;
        SET p_success = FALSE;
        SET p_message = 'Error processing payment';
    END IF;
END$$

-- Procedure: Calculate and generate monthly reports
CREATE PROCEDURE sp_generate_monthly_reports(
    IN p_year INT,
    IN p_month INT,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE v_error INT DEFAULT 0;
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET v_error = 1;
    
    START TRANSACTION;
    
    -- Generate sales reports for farmers
    INSERT INTO sales_reports (farmer_id, report_month, total_sales_orders, 
                               total_quantity_sold, total_revenue, generated_at)
    SELECT 
        p.farmer_id,
        STR_TO_DATE(CONCAT(p_year, '-', LPAD(p_month, 2, '0'), '-01'), '%Y-%m-%d'),
        COUNT(DISTINCT o.id),
        SUM(oi.quantity),
        SUM(oi.line_total),
        NOW()
    FROM products p
    LEFT JOIN order_items oi ON p.id = oi.product_id
    LEFT JOIN orders o ON oi.order_id = o.id 
        AND YEAR(o.placed_at) = p_year 
        AND MONTH(o.placed_at) = p_month
    GROUP BY p.farmer_id
    ON DUPLICATE KEY UPDATE
        total_sales_orders = VALUES(total_sales_orders),
        total_quantity_sold = VALUES(total_quantity_sold),
        total_revenue = VALUES(total_revenue);
    
    IF v_error = 0 THEN
        COMMIT;
        SET p_success = TRUE;
        SET p_message = 'Reports generated successfully';
    ELSE
        ROLLBACK;
        SET p_success = FALSE;
        SET p_message = 'Error generating reports';
    END IF;
END$$

-- Procedure: Approve loan application
CREATE PROCEDURE sp_approve_loan_application(
    IN p_loan_app_id INT,
    IN p_approved_by INT,
    IN p_approved_amount DECIMAL(12,2),
    IN p_interest_rate DECIMAL(5,2),
    IN p_tenure_months INT,
    OUT p_loan_id INT,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE v_farmer_id INT;
    DECLARE v_loan_product_id INT;
    DECLARE v_principal DECIMAL(12,2);
    DECLARE v_total_interest DECIMAL(12,2);
    DECLARE v_error INT DEFAULT 0;
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET v_error = 1;
    
    START TRANSACTION;
    
    -- Get loan application details
    SELECT farmer_id, loan_product_id 
    INTO v_farmer_id, v_loan_product_id
    FROM loan_applications WHERE id = p_loan_app_id;
    
    -- Update application status
    UPDATE loan_applications 
    SET status = 'approved',
        reviewed_by = p_approved_by,
        reviewed_at = NOW()
    WHERE id = p_loan_app_id;
    
    -- Calculate total interest
    SET v_principal = p_approved_amount;
    SET v_total_interest = (p_approved_amount * p_interest_rate * p_tenure_months) / (100 * 12);
    
    -- Create loan
    INSERT INTO loans (
        loan_application_id, farmer_id, loan_product_id,
        loan_type, principal, interest_rate, tenure_months,
        start_date, maturity_date, total_interest,
        remaining_balance, status, approved_by, approved_amount
    ) VALUES (
        p_loan_app_id, v_farmer_id, v_loan_product_id,
        'term_loan', v_principal, p_interest_rate, p_tenure_months,
        CURDATE(), DATE_ADD(CURDATE(), INTERVAL p_tenure_months MONTH),
        v_total_interest, v_principal, 'pending', p_approved_by, v_principal
    );
    
    SET p_loan_id = LAST_INSERT_ID();
    
    -- Create payment schedule
    INSERT INTO loan_payments (
        loan_id, payment_number, principal_amount, interest_amount,
        total_amount, due_date, status
    )
    WITH RECURSIVE payment_schedule AS (
        SELECT 1 as payment_num,
               v_principal / p_tenure_months as principal_payment,
               (v_principal * p_interest_rate) / (100 * 12) as interest_payment,
               DATE_ADD(CURDATE(), INTERVAL 1 MONTH) as payment_date
        UNION ALL
        SELECT payment_num + 1,
               v_principal / p_tenure_months,
               ((v_principal - (payment_num * v_principal / p_tenure_months)) * p_interest_rate) / (100 * 12),
               DATE_ADD(payment_date, INTERVAL 1 MONTH)
        FROM payment_schedule
        WHERE payment_num < p_tenure_months
    )
    SELECT 
        p_loan_id,
        payment_num,
        principal_payment,
        interest_payment,
        principal_payment + interest_payment,
        payment_date,
        'due'
    FROM payment_schedule;
    
    -- Create notification
    INSERT INTO notifications (user_id, type, title, message)
    VALUES (v_farmer_id, 'loan_approved', 'Loan Approved',
            CONCAT('Your loan application has been approved for amount: ', p_approved_amount));
    
    IF v_error = 0 THEN
        COMMIT;
        SET p_success = TRUE;
        SET p_message = 'Loan approved successfully';
    ELSE
        ROLLBACK;
        SET p_success = FALSE;
        SET p_message = 'Error approving loan';
        SET p_loan_id = NULL;
    END IF;
END$$

-- Procedure: Reject loan application
CREATE PROCEDURE sp_reject_loan_application(
    IN p_loan_app_id INT,
    IN p_reviewed_by INT,
    IN p_rejection_reason TEXT,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE v_farmer_id INT;
    DECLARE v_error INT DEFAULT 0;
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET v_error = 1;
    
    START TRANSACTION;
    
    -- Get farmer ID
    SELECT farmer_id INTO v_farmer_id FROM loan_applications WHERE id = p_loan_app_id;
    
    -- Update application status
    UPDATE loan_applications 
    SET status = 'rejected',
        reviewed_by = p_reviewed_by,
        reviewed_at = NOW(),
        rejection_reason = p_rejection_reason
    WHERE id = p_loan_app_id;
    
    -- Create notification
    INSERT INTO notifications (user_id, type, title, message)
    VALUES (v_farmer_id, 'loan_rejected', 'Loan Application Rejected',
            CONCAT('Your loan application has been rejected. Reason: ', p_rejection_reason));
    
    IF v_error = 0 THEN
        COMMIT;
        SET p_success = TRUE;
        SET p_message = 'Loan rejected successfully';
    ELSE
        ROLLBACK;
        SET p_success = FALSE;
        SET p_message = 'Error rejecting loan';
    END IF;
END$$

-- Procedure: Mark order as delivered and trigger review request
CREATE PROCEDURE sp_deliver_order(
    IN p_order_id INT,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE v_buyer_id INT;
    DECLARE v_error INT DEFAULT 0;
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET v_error = 1;
    
    START TRANSACTION;
    
    -- Get buyer ID
    SELECT buyer_id INTO v_buyer_id FROM orders WHERE id = p_order_id;
    
    -- Update order status
    UPDATE orders 
    SET status = 'delivered',
        delivered_at = NOW(),
        actual_delivery = CURDATE()
    WHERE id = p_order_id;
    
    -- Create review requests for each product
    INSERT INTO notifications (user_id, type, title, message, metadata_json)
    SELECT 
        v_buyer_id,
        'review_request',
        'Please Rate Your Purchase',
        CONCAT('How was your experience with ', p.name, '?'),
        JSON_OBJECT('product_id', p.id, 'order_id', p_order_id)
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = p_order_id;
    
    -- Update payment status if all orders are delivered
    UPDATE orders 
    SET payment_status = 'paid'
    WHERE id = p_order_id AND payment_status = 'unpaid';
    
    IF v_error = 0 THEN
        COMMIT;
        SET p_success = TRUE;
        SET p_message = 'Order marked as delivered';
    ELSE
        ROLLBACK;
        SET p_success = FALSE;
        SET p_message = 'Error delivering order';
    END IF;
END$$

-- Procedure: Update farmer KYC status
CREATE PROCEDURE sp_update_farmer_kyc(
    IN p_farmer_id INT,
    IN p_kyc_status VARCHAR(20),
    IN p_verified_by INT,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE v_error INT DEFAULT 0;
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET v_error = 1;
    
    START TRANSACTION;
    
    UPDATE farmer_profiles 
    SET kyc_status = p_kyc_status,
        kyc_verified_by = p_verified_by,
        kyc_verified_date = CASE WHEN p_kyc_status = 'verified' THEN NOW() ELSE NULL END
    WHERE user_id = p_farmer_id;
    
    -- Create notification
    INSERT INTO notifications (user_id, type, title, message)
    VALUES (p_farmer_id, 'kyc_update', 'KYC Status Updated',
            CONCAT('Your KYC status has been updated to: ', p_kyc_status));
    
    -- Log activity
    INSERT INTO activity_logs (user_id, action, entity_type, entity_id, new_values)
    VALUES (p_verified_by, 'KYC_UPDATE', 'farmer_profile', p_farmer_id, 
            JSON_OBJECT('kyc_status', p_kyc_status));
    
    IF v_error = 0 THEN
        COMMIT;
        SET p_success = TRUE;
        SET p_message = 'KYC updated successfully';
    ELSE
        ROLLBACK;
        SET p_success = FALSE;
        SET p_message = 'Error updating KYC';
    END IF;
END$$

DELIMITER ;
