-- =====================================================
-- CORE USERS & AUTHENTICATION
-- =====================================================

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    phone VARCHAR(40) DEFAULT NULL,
    role ENUM('farmer','buyer','finance','admin') NOT NULL DEFAULT 'farmer',
    password_hash VARCHAR(255) NOT NULL,
    status ENUM('active','pending','suspended','inactive') NOT NULL DEFAULT 'active',
    profile_image VARCHAR(200) DEFAULT NULL,
    bio TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status)
);

-- =====================================================
-- FARMER PROFILES & KYC
-- =====================================================

CREATE TABLE farmer_profiles (
    user_id INT PRIMARY KEY,
    farm_name VARCHAR(160),
    location VARCHAR(160),
    land_area DECIMAL(10,2) DEFAULT 0,
    soil_type VARCHAR(80),
    irrigation VARCHAR(120),
    crops_cultivated VARCHAR(200),
    kyc_status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
    kyc_verified_date TIMESTAMP NULL,
    kyc_verified_by INT NULL,
    bank_account VARCHAR(120),
    bank_name VARCHAR(120),
    total_products_sold INT DEFAULT 0,
    total_revenue DECIMAL(12,2) DEFAULT 0,
    average_rating DECIMAL(3,2) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (kyc_verified_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_kyc_status (kyc_status)
);

CREATE TABLE farmer_certifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT NOT NULL,
    certification_name VARCHAR(160) NOT NULL,
    issuing_authority VARCHAR(160),
    issue_date DATE,
    expiry_date DATE,
    certificate_file VARCHAR(200),
    FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_farmer_id (farmer_id)
);

CREATE TABLE farmer_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT NOT NULL,
    document_type ENUM('aadhar','pan','driving_license','land_certificate','other') NOT NULL,
    document_number VARCHAR(120),
    document_file VARCHAR(200) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_farmer_id (farmer_id)
);

-- =====================================================
-- BUYER PROFILES
-- =====================================================

CREATE TABLE buyer_profiles (
    user_id INT PRIMARY KEY,
    company_name VARCHAR(160),
    address VARCHAR(200),
    gstin VARCHAR(40),
    total_purchases INT DEFAULT 0,
    total_spent DECIMAL(12,2) DEFAULT 0,
    average_rating DECIMAL(3,2) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- USER SETTINGS & PREFERENCES
-- =====================================================

CREATE TABLE user_settings (
    user_id INT PRIMARY KEY,
    email_notifications TINYINT(1) NOT NULL DEFAULT 1,
    sms_notifications TINYINT(1) NOT NULL DEFAULT 1,
    language VARCHAR(40) NOT NULL DEFAULT 'English',
    region VARCHAR(80) NOT NULL DEFAULT 'Bangladesh',
    currency VARCHAR(10) DEFAULT 'BDT',
    two_factor_enabled TINYINT(1) DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE user_payment_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    method VARCHAR(40) NOT NULL DEFAULT 'bKash',
    account_number VARCHAR(80) DEFAULT NULL,
    account_holder VARCHAR(120),
    is_primary TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    UNIQUE KEY idx_primary (user_id, is_primary)
);

-- =====================================================
-- FINANCE STAFF PROFILES
-- =====================================================

CREATE TABLE finance_profiles (
    user_id INT PRIMARY KEY,
    institution VARCHAR(160),
    designation VARCHAR(120),
    branch_location VARCHAR(160),
    max_approval_amount DECIMAL(12,2),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- PRODUCTS & INVENTORY MANAGEMENT
-- =====================================================

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    slug VARCHAR(80) NOT NULL UNIQUE,
    description TEXT,
    image_url VARCHAR(200),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slug (slug)
);

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT NOT NULL,
    category_id INT NOT NULL,
    name VARCHAR(120) NOT NULL,
    variety VARCHAR(120),
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    unit VARCHAR(20) NOT NULL DEFAULT 'kg',
    quantity_available DECIMAL(10,2) NOT NULL DEFAULT 0,
    quantity_sold DECIMAL(10,2) DEFAULT 0,
    rating DECIMAL(3,2) DEFAULT 0,
    review_count INT DEFAULT 0,
    status ENUM('active','inactive','sold_out') NOT NULL DEFAULT 'active',
    harvest_date DATE,
    expiry_date DATE,
    storage_location VARCHAR(160),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    INDEX idx_farmer_id (farmer_id),
    INDEX idx_category_id (category_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

CREATE TABLE product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_path VARCHAR(200) NOT NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id)
);

CREATE TABLE product_batch (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    batch_number VARCHAR(80) UNIQUE NOT NULL,
    quantity DECIMAL(10,2),
    harvest_date DATE,
    expiry_date DATE,
    storage_location VARCHAR(160),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id),
    INDEX idx_batch_number (batch_number)
);

CREATE TABLE market_prices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    crop_name VARCHAR(120) NOT NULL,
    category_id INT,
    region VARCHAR(120) NOT NULL,
    unit VARCHAR(20) NOT NULL DEFAULT 'kg',
    price DECIMAL(10,2) NOT NULL,
    previous_price DECIMAL(10,2) DEFAULT 0,
    reported_on DATE NOT NULL,
    reported_by INT,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_crop_region (crop_name, region),
    INDEX idx_reported_on (reported_on)
);

-- =====================================================
-- SHOPPING & TRANSACTIONS
-- =====================================================

CREATE TABLE carts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    status ENUM('open','checked_out','abandoned') NOT NULL DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
);

CREATE TABLE cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cart_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    INDEX idx_cart_id (cart_id),
    UNIQUE KEY idx_cart_product (cart_id, product_id)
);

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    buyer_id INT NOT NULL,
    order_number VARCHAR(40) UNIQUE NOT NULL,
    status ENUM('pending','confirmed','packed','shipped','delivered','cancelled','returned') NOT NULL DEFAULT 'pending',
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    final_amount DECIMAL(12,2) NOT NULL,
    payment_status ENUM('unpaid','paid','refunded','partial_refund') NOT NULL DEFAULT 'unpaid',
    payment_method VARCHAR(40),
    shipping_address VARCHAR(240),
    shipping_phone VARCHAR(40),
    estimated_delivery DATE,
    actual_delivery DATE,
    placed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    shipped_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    notes TEXT,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_buyer_id (buyer_id),
    INDEX idx_status (status),
    INDEX idx_payment_status (payment_status),
    INDEX idx_placed_at (placed_at),
    INDEX idx_order_number (order_number)
);

CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    farmer_id INT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    line_total DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_order_id (order_id),
    INDEX idx_farmer_id (farmer_id)
);

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    method VARCHAR(40) NOT NULL,
    status ENUM('pending','success','failed','cancelled') NOT NULL DEFAULT 'pending',
    transaction_ref VARCHAR(120) UNIQUE,
    gateway_response JSON,
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_order_id (order_id),
    INDEX idx_status (status),
    INDEX idx_paid_at (paid_at)
);

CREATE TABLE refunds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    payment_id INT,
    amount DECIMAL(12,2) NOT NULL,
    reason VARCHAR(200) NOT NULL,
    status ENUM('pending','approved','rejected','refunded') DEFAULT 'pending',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    processed_by INT,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE SET NULL,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_order_id (order_id),
    INDEX idx_status (status)
);

-- =====================================================
-- MICROFINANCE & LOANS
-- =====================================================

CREATE TABLE loan_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    description TEXT,
    min_amount DECIMAL(12,2) NOT NULL,
    max_amount DECIMAL(12,2) NOT NULL,
    min_interest_rate DECIMAL(5,2) NOT NULL,
    max_interest_rate DECIMAL(5,2) NOT NULL,
    min_tenure_months INT NOT NULL,
    max_tenure_months INT NOT NULL,
    processing_fee DECIMAL(5,2),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE loan_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT NOT NULL,
    loan_product_id INT NOT NULL,
    requested_amount DECIMAL(12,2) NOT NULL,
    purpose VARCHAR(160),
    tenure_months INT DEFAULT NULL,
    location VARCHAR(120) DEFAULT NULL,
    farm_size VARCHAR(80) DEFAULT NULL,
    monthly_income VARCHAR(80) DEFAULT NULL,
    collateral VARCHAR(160) DEFAULT NULL,
    collateral_value DECIMAL(12,2),
    bank_name VARCHAR(120) DEFAULT NULL,
    bank_account VARCHAR(120) DEFAULT NULL,
    risk_level ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
    credit_score INT,
    status ENUM('pending','submitted','under_review','approved','rejected','withdrawn') NOT NULL DEFAULT 'pending',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    reviewed_by INT NULL,
    rejection_reason TEXT,
    FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (loan_product_id) REFERENCES loan_products(id) ON DELETE RESTRICT,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_farmer_id (farmer_id),
    INDEX idx_status (status),
    INDEX idx_submitted_at (submitted_at)
);

CREATE TABLE loans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loan_application_id INT,
    farmer_id INT NOT NULL,
    loan_product_id INT,
    loan_type VARCHAR(80) NOT NULL,
    principal DECIMAL(12,2) NOT NULL,
    interest_rate DECIMAL(5,2) NOT NULL,
    processing_fee DECIMAL(10,2) DEFAULT 0,
    tenure_months INT NOT NULL,
    status ENUM('pending','active','completed','defaulted','written_off') NOT NULL DEFAULT 'pending',
    approved_amount DECIMAL(12,2),
    disbursed_amount DECIMAL(12,2) DEFAULT 0,
    disbursed_at TIMESTAMP NULL,
    start_date DATE,
    maturity_date DATE,
    next_due_date DATE,
    total_interest DECIMAL(12,2),
    total_paid DECIMAL(12,2) DEFAULT 0,
    remaining_balance DECIMAL(12,2),
    approved_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (loan_application_id) REFERENCES loan_applications(id) ON DELETE SET NULL,
    FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (loan_product_id) REFERENCES loan_products(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_farmer_id (farmer_id),
    INDEX idx_status (status),
    INDEX idx_maturity_date (maturity_date)
);

CREATE TABLE loan_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loan_id INT NOT NULL,
    payment_number INT NOT NULL,
    principal_amount DECIMAL(12,2),
    interest_amount DECIMAL(12,2),
    total_amount DECIMAL(12,2) NOT NULL,
    due_date DATE NOT NULL,
    paid_at TIMESTAMP NULL,
    paid_amount DECIMAL(12,2),
    status ENUM('due','paid','late','overdue','waived') NOT NULL DEFAULT 'due',
    payment_method VARCHAR(40),
    transaction_ref VARCHAR(120),
    days_late INT DEFAULT 0,
    penalty_amount DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
    INDEX idx_loan_id (loan_id),
    INDEX idx_due_date (due_date),
    INDEX idx_status (status)
);

CREATE TABLE loan_disbursements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loan_id INT NOT NULL,
    disbursement_number INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    scheduled_date DATE NOT NULL,
    actual_date TIMESTAMP NULL,
    status ENUM('scheduled','completed','cancelled') DEFAULT 'scheduled',
    bank_account VARCHAR(120),
    reference_number VARCHAR(120),
    remarks TEXT,
    FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
    INDEX idx_loan_id (loan_id),
    INDEX idx_status (status)
);

CREATE TABLE loan_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loan_id INT NOT NULL,
    document_type ENUM('agreement','collateral_photo','property_deed','insurance','other') NOT NULL,
    document_path VARCHAR(200) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE
);

CREATE TABLE loan_guarantors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loan_id INT NOT NULL,
    guarantor_name VARCHAR(160) NOT NULL,
    guarantor_phone VARCHAR(40),
    relationship VARCHAR(80),
    address VARCHAR(200),
    identified_by INT,
    FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
    FOREIGN KEY (identified_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- MESSAGING & NOTIFICATIONS
-- =====================================================

CREATE TABLE conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject VARCHAR(160),
    conversation_type ENUM('buyer_farmer','support','dispute','loan') DEFAULT 'buyer_farmer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE conversation_participants (
    conversation_id INT NOT NULL,
    user_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_read_at TIMESTAMP NULL,
    PRIMARY KEY (conversation_id, user_id),
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    sender_id INT NOT NULL,
    body TEXT NOT NULL,
    message_type ENUM('text','image','document') DEFAULT 'text',
    attachment_path VARCHAR(200),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_conversation_id (conversation_id),
    INDEX idx_created_at (created_at)
);

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(40) NOT NULL,
    title VARCHAR(160) NOT NULL,
    message TEXT NOT NULL,
    related_id INT,
    related_type VARCHAR(40),
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    metadata_json JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
);

CREATE TABLE notification_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(40) UNIQUE NOT NULL,
    subject VARCHAR(160),
    template TEXT NOT NULL,
    variables JSON,
    is_active TINYINT(1) DEFAULT 1
);

-- =====================================================
-- REVIEWS & RATINGS
-- =====================================================

CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    reviewed_user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(160),
    comment TEXT,
    helpful_count INT DEFAULT 0,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id),
    INDEX idx_reviewed_user_id (reviewed_user_id),
    INDEX idx_rating (rating),
    UNIQUE KEY idx_unique_review (order_id, reviewer_id)
);

CREATE TABLE review_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL,
    responder_id INT NOT NULL,
    response TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
    FOREIGN KEY (responder_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- WISHLIST MANAGEMENT
-- =====================================================

CREATE TABLE wishlists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
);

CREATE TABLE wishlist_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    wishlist_id INT NOT NULL,
    product_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (wishlist_id) REFERENCES wishlists(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY idx_wishlist_product (wishlist_id, product_id)
);

-- =====================================================
-- DISPUTES & RESOLUTION
-- =====================================================

CREATE TABLE disputes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    loan_id INT,
    opened_by INT NOT NULL,
    dispute_type ENUM('quality','non_delivery','payment_issue','loan_issue','other') NOT NULL,
    status ENUM('open','in_review','resolved','rejected','closed') NOT NULL DEFAULT 'open',
    title VARCHAR(160) NOT NULL,
    description TEXT,
    severity ENUM('low','medium','high','critical') DEFAULT 'medium',
    resolution_summary TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    handled_by INT NULL,
    resolution_method VARCHAR(80),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
    FOREIGN KEY (opened_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (handled_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

CREATE TABLE dispute_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dispute_id INT NOT NULL,
    sender_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dispute_id) REFERENCES disputes(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE dispute_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dispute_id INT NOT NULL,
    uploaded_by INT NOT NULL,
    document_path VARCHAR(200) NOT NULL,
    document_type VARCHAR(80),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dispute_id) REFERENCES disputes(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- CONTENT MANAGEMENT & BLOG
-- =====================================================

CREATE TABLE blog_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    excerpt TEXT,
    content MEDIUMTEXT,
    featured_image VARCHAR(200),
    author_id INT NULL,
    category_id INT,
    views INT DEFAULT 0,
    status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_published_at (published_at)
);

CREATE TABLE blog_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    blog_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (blog_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE faq (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question VARCHAR(255) NOT NULL,
    answer TEXT NOT NULL,
    category VARCHAR(80),
    is_active TINYINT(1) DEFAULT 1,
    order_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- AUDIT & ACTIVITY TRACKING
-- =====================================================

CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created_at (created_at)
);

CREATE TABLE admin_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(150) NOT NULL,
    description TEXT,
    target_user_id INT,
    target_type VARCHAR(50),
    target_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (target_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_created_at (created_at)
);

-- =====================================================
-- REPORTS & ANALYTICS
-- =====================================================

CREATE TABLE sales_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT NOT NULL,
    report_month DATE NOT NULL,
    total_sales_orders INT DEFAULT 0,
    total_quantity_sold DECIMAL(12,2) DEFAULT 0,
    total_revenue DECIMAL(12,2) DEFAULT 0,
    total_profit DECIMAL(12,2) DEFAULT 0,
    top_product_id INT,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (top_product_id) REFERENCES products(id) ON DELETE SET NULL,
    UNIQUE KEY idx_farmer_month (farmer_id, report_month)
);

CREATE TABLE financial_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    finance_officer_id INT NOT NULL,
    report_month DATE NOT NULL,
    total_applications INT DEFAULT 0,
    total_approved INT DEFAULT 0,
    total_disbursed DECIMAL(12,2) DEFAULT 0,
    total_repaid DECIMAL(12,2) DEFAULT 0,
    total_pending_repayment DECIMAL(12,2) DEFAULT 0,
    default_rate DECIMAL(5,2) DEFAULT 0,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (finance_officer_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY idx_officer_month (finance_officer_id, report_month)
);
