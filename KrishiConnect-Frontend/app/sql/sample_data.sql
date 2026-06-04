-- =====================================================
-- SAMPLE DATA FOR TESTING
-- =====================================================

-- =====================================================
-- INSERT USERS
-- =====================================================

INSERT INTO users (full_name, email, phone, role, password_hash, status) VALUES
-- Farmers
('Rahul Kumar Singh', 'farmer1@example.com', '9876543210', 'farmer', 'hashed_password_1', 'active'),
('Priya Devi Sharma', 'farmer2@example.com', '9876543211', 'farmer', 'hashed_password_2', 'active'),
('Amit Patel', 'farmer3@example.com', '9876543212', 'farmer', 'hashed_password_3', 'active'),
('Neha Singh', 'farmer4@example.com', '9876543213', 'farmer', 'hashed_password_4', 'active'),

-- Buyers
('Rajesh Gupta', 'buyer1@example.com', '8765432110', 'buyer', 'hashed_password_5', 'active'),
('Sneha Reddy', 'buyer2@example.com', '8765432111', 'buyer', 'hashed_password_6', 'active'),
('Vikram Kumar', 'buyer3@example.com', '8765432112', 'buyer', 'hashed_password_7', 'active'),

-- Finance Officers
('Mohit Sharma', 'finance1@example.com', '7654321110', 'finance', 'hashed_password_8', 'active'),
('Ritu Verma', 'finance2@example.com', '7654321111', 'finance', 'hashed_password_9', 'active'),

-- Admin
('Admin User', 'admin@example.com', '6543210110', 'admin', 'hashed_password_10', 'active');

-- =====================================================
-- INSERT FARMER PROFILES
-- =====================================================

INSERT INTO farmer_profiles (user_id, farm_name, location, land_area, soil_type, irrigation, crops_cultivated, kyc_status, bank_name, bank_account) VALUES
(1, 'Singh Farm', 'Punjab, India', 5.5, 'Fertile Loam', 'Canal Irrigation', 'Wheat, Rice, Cotton', 'verified', 'State Bank', '1234567890'),
(2, 'Devi Agricultural', 'Haryana, India', 8.2, 'Black Soil', 'Drip Irrigation', 'Vegetables, Tomatoes', 'verified', 'HDFC Bank', '9876543210'),
(3, 'Patel Organic Farm', 'Gujarat, India', 3.5, 'Sandy Loam', 'Well Irrigation', 'Organic Vegetables', 'pending', 'ICICI Bank', '1122334455'),
(4, 'Green Valley Farm', 'Uttar Pradesh, India', 6.0, 'Alluvial Soil', 'Tubwell', 'Tomatoes, Potatoes', 'verified', 'Axis Bank', '5566778899');

-- =====================================================
-- INSERT BUYER PROFILES
-- =====================================================

INSERT INTO buyer_profiles (user_id, company_name, address, gstin, total_purchases) VALUES
(5, 'Fresh Produce Traders', '123 Market Street, Delhi', '07AABCT1234H1Z0', 15),
(6, 'Organic Foods Private Ltd', '456 Business Park, Mumbai', '27AABCT1234H1Z0', 23),
(7, 'Retail Chain Co.', '789 Trade Center, Bangalore', '29AABCT1234H1Z0', 8);

-- =====================================================
-- INSERT USER SETTINGS
-- =====================================================

INSERT INTO user_settings (user_id, email_notifications, sms_notifications, language, region, currency, two_factor_enabled) VALUES
(1, 1, 1, 'English', 'Punjab', 'INR', 0),
(2, 1, 1, 'English', 'Haryana', 'INR', 0),
(3, 1, 0, 'English', 'Gujarat', 'INR', 0),
(4, 1, 1, 'English', 'Uttar Pradesh', 'INR', 0),
(5, 1, 1, 'English', 'Delhi', 'INR', 1),
(6, 1, 1, 'English', 'Maharashtra', 'INR', 1),
(7, 1, 0, 'English', 'Karnataka', 'INR', 0);

-- =====================================================
-- INSERT PAYMENT METHODS
-- =====================================================

INSERT INTO user_payment_methods (user_id, method, account_number, account_holder, is_primary, is_active) VALUES
(1, 'bKash', '01712345678', 'Rahul Kumar Singh', 1, 1),
(2, 'Nagad', '01812345678', 'Priya Devi Sharma', 1, 1),
(5, 'bKash', '01912345678', 'Rajesh Gupta', 1, 1),
(5, 'Bank Transfer', '1234567890', 'Fresh Produce Traders', 0, 1),
(6, 'Nagad', '01712345679', 'Sneha Reddy', 1, 1);

-- =====================================================
-- INSERT CATEGORIES
-- =====================================================

INSERT INTO categories (name, slug, description, is_active) VALUES
('Vegetables', 'vegetables', 'Fresh vegetables and greens', 1),
('Fruits', 'fruits', 'Fresh seasonal fruits', 1),
('Grains', 'grains', 'Cereals and pulses', 1),
('Dairy', 'dairy', 'Milk and dairy products', 1),
('Organic', 'organic', 'Certified organic products', 1);

-- =====================================================
-- INSERT PRODUCTS
-- =====================================================

INSERT INTO products (farmer_id, category_id, name, variety, description, price, unit, quantity_available, status, harvest_date) VALUES
(1, 1, 'Fresh Tomatoes', 'Desi Variety', 'High quality locally grown tomatoes', 45.00, 'kg', 100, 'active', '2026-06-01'),
(1, 3, 'Basmati Rice', 'Premium', 'Long grain white basmati rice', 85.00, 'kg', 500, 'active', '2025-10-01'),
(2, 1, 'Bell Peppers', 'Mixed Colors', 'Colorful capsicum peppers', 80.00, 'kg', 50, 'active', '2026-06-02'),
(2, 1, 'Cucumber', 'Long Green', 'Fresh green cucumbers', 35.00, 'kg', 80, 'active', '2026-06-02'),
(3, 5, 'Organic Spinach', 'Organic', 'Pesticide-free green leafy vegetable', 120.00, 'kg', 30, 'active', '2026-06-03'),
(3, 5, 'Organic Potatoes', 'Organic', 'Naturally grown potatoes', 60.00, 'kg', 200, 'active', '2025-12-01'),
(4, 1, 'Potato', 'Red Variety', 'Fresh red potatoes', 50.00, 'kg', 150, 'active', '2026-05-15'),
(4, 2, 'Mango', 'Kesar', 'Sweet king of fruits', 120.00, 'kg', 100, 'active', '2026-06-01');

-- =====================================================
-- INSERT PRODUCT IMAGES
-- =====================================================

INSERT INTO product_images (product_id, image_path, is_primary) VALUES
(1, '/images/vegetables/tomato1.jpg', 1),
(1, '/images/vegetables/tomato2.jpg', 0),
(2, '/images/grains/rice1.jpg', 1),
(3, '/images/vegetables/pepper1.jpg', 1),
(4, '/images/vegetables/cucumber1.jpg', 1),
(5, '/images/organic/spinach1.jpg', 1),
(6, '/images/organic/potato1.jpg', 1),
(7, '/images/vegetables/potato1.jpg', 1),
(8, '/images/fruits/mango1.jpg', 1);

-- =====================================================
-- INSERT MARKET PRICES
-- =====================================================

INSERT INTO market_prices (crop_name, category_id, region, unit, price, reported_on) VALUES
('Tomato', 1, 'Delhi', 'kg', 50.00, '2026-06-03'),
('Tomato', 1, 'Mumbai', 'kg', 55.00, '2026-06-03'),
('Bell Pepper', 1, 'Delhi', 'kg', 85.00, '2026-06-03'),
('Basmati Rice', 3, 'India', 'kg', 90.00, '2026-06-03'),
('Mango', 2, 'India', 'kg', 125.00, '2026-06-03');

-- =====================================================
-- INSERT WISHLIST
-- =====================================================

INSERT INTO wishlists (user_id) VALUES (5), (6), (7);

INSERT INTO wishlist_items (wishlist_id, product_id, added_at) VALUES
(1, 1, NOW()),
(1, 5, NOW()),
(2, 3, NOW()),
(2, 8, NOW()),
(3, 6, NOW());

-- =====================================================
-- INSERT ORDERS
-- =====================================================

INSERT INTO orders (buyer_id, order_number, status, total_amount, final_amount, payment_status, payment_method, shipping_address, shipping_phone, placed_at) VALUES
(5, 'ORD-202406010001', 'delivered', 450.00, 450.00, 'paid', 'bKash', '123 Market Street, Delhi', '9876543210', DATE_SUB(NOW(), INTERVAL 10 DAY)),
(5, 'ORD-202406020001', 'delivered', 1700.00, 1700.00, 'paid', 'bKash', '123 Market Street, Delhi', '9876543210', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(6, 'ORD-202406030001', 'shipped', 2400.00, 2400.00, 'paid', 'Nagad', '456 Business Park, Mumbai', '8765432111', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(7, 'ORD-202406030002', 'pending', 600.00, 600.00, 'unpaid', 'bKash', '789 Trade Center, Bangalore', '8765432112', NOW());

-- =====================================================
-- INSERT ORDER ITEMS
-- =====================================================

INSERT INTO order_items (order_id, product_id, farmer_id, quantity, unit_price, line_total) VALUES
(1, 1, 1, 10, 45.00, 450.00),
(2, 2, 1, 10, 85.00, 850.00),
(2, 3, 2, 10, 80.00, 800.00),
(3, 3, 2, 20, 80.00, 1600.00),
(3, 5, 3, 5, 120.00, 600.00),
(3, 1, 1, 2, 50.00, 100.00),
(4, 5, 3, 5, 120.00, 600.00);

-- =====================================================
-- INSERT PAYMENTS
-- =====================================================

INSERT INTO payments (order_id, amount, method, status, transaction_ref, paid_at) VALUES
(1, 450.00, 'bKash', 'success', 'TXN-001', NOW()),
(2, 1700.00, 'bKash', 'success', 'TXN-002', NOW()),
(3, 2400.00, 'Nagad', 'success', 'TXN-003', NOW());

-- =====================================================
-- INSERT REVIEWS
-- =====================================================

INSERT INTO reviews (order_id, product_id, reviewer_id, reviewed_user_id, rating, title, comment, status) VALUES
(1, 1, 5, 1, 5, 'Excellent Quality', 'Fresh and good quality tomatoes. Very satisfied!', 'approved'),
(2, 2, 5, 1, 4, 'Good Rice', 'Good quality basmati rice. Quick delivery.', 'approved'),
(2, 3, 5, 2, 5, 'Fresh Peppers', 'Beautiful colored peppers, fresh and crisp!', 'approved'),
(3, 3, 6, 2, 4, 'Nice Quality', 'Good peppers, well packaged', 'approved'),
(3, 5, 6, 3, 5, 'Organic Verified', 'Truly organic spinach, healthy and fresh', 'approved');

-- =====================================================
-- INSERT LOAN PRODUCTS
-- =====================================================

INSERT INTO loan_products (name, description, min_amount, max_amount, min_interest_rate, max_interest_rate, min_tenure_months, max_tenure_months, processing_fee, is_active) VALUES
('Seasonal Crop Loan', 'For seasonal agricultural needs', 10000, 500000, 8.0, 12.0, 3, 12, 500.00, 1),
('Equipment Purchase Loan', 'For farm equipment and machinery', 50000, 1000000, 7.0, 10.0, 6, 60, 1000.00, 1),
('Organic Certification Loan', 'For organic farming certification and conversion', 25000, 300000, 6.0, 9.0, 12, 36, 750.00, 1),
('Emergency Working Capital', 'Quick working capital for urgent needs', 5000, 100000, 10.0, 15.0, 1, 6, 250.00, 1);

-- =====================================================
-- INSERT LOAN APPLICATIONS
-- =====================================================

INSERT INTO loan_applications (farmer_id, loan_product_id, requested_amount, purpose, tenure_months, location, farm_size, monthly_income, risk_level, credit_score, status, submitted_at) VALUES
(1, 1, 100000, 'Wheat cultivation expenses', 6, 'Punjab', '5 acres', '50000', 'low', 750, 'approved', DATE_SUB(NOW(), INTERVAL 30 DAY)),
(2, 2, 300000, 'Drip irrigation system', 12, 'Haryana', '8 acres', '75000', 'medium', 680, 'approved', DATE_SUB(NOW(), INTERVAL 25 DAY)),
(3, 3, 150000, 'Organic certification', 18, 'Gujarat', '3 acres', '40000', 'medium', 650, 'pending', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(4, 1, 80000, 'Seed and fertilizer', 6, 'Uttar Pradesh', '6 acres', '60000', 'low', 720, 'approved', DATE_SUB(NOW(), INTERVAL 15 DAY));

-- =====================================================
-- INSERT LOANS
-- =====================================================

INSERT INTO loans (loan_application_id, farmer_id, loan_product_id, loan_type, principal, interest_rate, tenure_months, status, approved_amount, disbursed_amount, start_date, maturity_date, total_interest, remaining_balance, approved_by) VALUES
(1, 1, 1, 'term_loan', 100000, 10.0, 6, 'active', 100000, 100000, '2026-05-01', '2026-11-01', 5000, 83333, 8),
(2, 2, 2, 'term_loan', 300000, 9.0, 12, 'active', 300000, 300000, '2026-04-15', '2027-04-15', 27000, 250000, 8),
(4, 4, 1, 'term_loan', 80000, 10.5, 6, 'active', 80000, 80000, '2026-05-20', '2026-11-20', 4200, 66667, 9);

-- =====================================================
-- INSERT LOAN PAYMENTS (Sample Schedule)
-- =====================================================

INSERT INTO loan_payments (loan_id, payment_number, principal_amount, interest_amount, total_amount, due_date, status) VALUES
-- Farmer 1 Loan (6 months)
(1, 1, 16667, 833, 17500, '2026-06-01', 'paid'),
(1, 2, 16667, 667, 17334, '2026-07-01', 'paid'),
(1, 3, 16667, 500, 17167, '2026-08-01', 'due'),
(1, 4, 16667, 333, 17000, '2026-09-01', 'due'),
(1, 5, 16667, 167, 16834, '2026-10-01', 'due'),
(1, 6, 16666, 0, 16666, '2026-11-01', 'due'),

-- Farmer 2 Loan (12 months)
(2, 1, 25000, 2250, 27250, '2026-05-15', 'paid'),
(2, 2, 25000, 2063, 27063, '2026-06-15', 'paid'),
(2, 3, 25000, 1875, 26875, '2026-07-15', 'due'),
(2, 4, 25000, 1687, 26687, '2026-08-15', 'due'),
(2, 5, 25000, 1500, 26500, '2026-09-15', 'due'),
(2, 6, 25000, 1313, 26313, '2026-10-15', 'due'),
(2, 7, 25000, 1125, 26125, '2026-11-15', 'due'),
(2, 8, 25000, 937, 25937, '2026-12-15', 'due'),
(2, 9, 25000, 750, 25750, '2027-01-15', 'due'),
(2, 10, 25000, 562, 25562, '2027-02-15', 'due'),
(2, 11, 25000, 375, 25375, '2027-03-15', 'due'),
(2, 12, 25000, 188, 25188, '2027-04-15', 'due');

-- =====================================================
-- INSERT DISPUTES
-- =====================================================

INSERT INTO disputes (order_id, opened_by, dispute_type, status, title, description, severity, created_at) VALUES
(2, 5, 'quality', 'open', 'Rice quality issue', 'Rice grains appear broken and dusty', 'medium', DATE_SUB(NOW(), INTERVAL 1 DAY));

-- =====================================================
-- INSERT CONVERSATIONS & MESSAGES
-- =====================================================

INSERT INTO conversations (subject, conversation_type, created_at) VALUES
('Tomato Supply Discussion', 'buyer_farmer', DATE_SUB(NOW(), INTERVAL 5 DAY)),
('Loan Question', 'support', DATE_SUB(NOW(), INTERVAL 2 DAY));

INSERT INTO conversation_participants (conversation_id, user_id, joined_at) VALUES
(1, 1, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(1, 5, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(2, 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 8, DATE_SUB(NOW(), INTERVAL 2 DAY));

INSERT INTO messages (conversation_id, sender_id, body, created_at) VALUES
(1, 5, 'Hi, can you supply fresh tomatoes regularly?', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(1, 1, 'Yes, we have daily supply available. What quantity do you need?', DATE_SUB(NOW(), INTERVAL 4 DAY)),
(1, 5, 'We need about 100kg per week at Rs. 40/kg', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(2, 1, 'What documents do I need for loan approval?', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 8, 'You need KYC verification, bank details, and farm documents', DATE_SUB(NOW(), INTERVAL 1 DAY));

-- =====================================================
-- INSERT NOTIFICATIONS
-- =====================================================

INSERT INTO notifications (user_id, type, title, message, is_read, created_at) VALUES
(1, 'order_placed', 'New Order', 'New order placed for your tomatoes', 0, DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(2, 'order_placed', 'New Order', 'New order placed for your peppers', 1, DATE_SUB(NOW(), INTERVAL 2 HOURS)),
(1, 'loan_payment_due', 'Payment Due', 'Your next loan payment is due on 2026-08-01', 0, DATE_SUB(NOW(), INTERVAL 3 DAYS)),
(5, 'order_shipped', 'Order Shipped', 'Your order ORD-202406030001 has been shipped', 1, DATE_SUB(NOW(), INTERVAL 1 DAY));

-- =====================================================
-- INSERT BLOG POSTS
-- =====================================================

INSERT INTO blog_posts (title, slug, excerpt, content, author_id, status, published_at, created_at) VALUES
('Modern Farming Techniques', 'modern-farming-techniques', 'Learn about latest farming methods...', 
'Content about modern farming techniques...', 8, 'published', NOW(), DATE_SUB(NOW(), INTERVAL 10 DAY)),
('Loan Management Tips', 'loan-management-tips', 'How to manage agricultural loans...', 
'Content about loan management...', 8, 'published', NOW(), DATE_SUB(NOW(), INTERVAL 5 DAY));

-- =====================================================
-- INSERT FAQ
-- =====================================================

INSERT INTO faq (question, answer, category, is_active, order_by) VALUES
('How do I apply for a loan?', 'You can apply for a loan through your farmer dashboard. Fill in the loan application form with required details.', 'Loans', 1, 1),
('What documents are needed for KYC?', 'You need Aadhar, PAN, and farm ownership documents for KYC verification.', 'KYC', 1, 2),
('How long does delivery take?', 'Delivery typically takes 2-5 days depending on your location.', 'Orders', 1, 3),
('What is your return policy?', 'We accept returns within 7 days if the product is defective or damaged.', 'Returns', 1, 4);

-- =====================================================
-- INSERT ACTIVITY LOGS (for audit trail)
-- =====================================================

INSERT INTO activity_logs (user_id, action, entity_type, entity_id, new_values, created_at) VALUES
(1, 'PRODUCT_CREATED', 'products', 1, JSON_OBJECT('name', 'Fresh Tomatoes', 'price', 45), DATE_SUB(NOW(), INTERVAL 20 DAY)),
(5, 'ORDER_CREATED', 'orders', 1, JSON_OBJECT('order_number', 'ORD-202406010001', 'total', 450), DATE_SUB(NOW(), INTERVAL 10 DAY)),
(1, 'LOAN_APPLICATION_CREATED', 'loan_applications', 1, JSON_OBJECT('amount', 100000, 'status', 'pending'), DATE_SUB(NOW(), INTERVAL 30 DAY));

-- =====================================================
-- Note: Password hashes should be real bcrypt hashes in production
-- Use PHP's password_hash() or similar for actual implementation
-- =====================================================
