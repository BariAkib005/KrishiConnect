CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    phone VARCHAR(40) DEFAULT NULL,
    role ENUM('farmer','buyer','finance','admin') NOT NULL DEFAULT 'farmer',
    password_hash VARCHAR(255) NOT NULL,
    admin_pin_hash VARCHAR(255) DEFAULT NULL,
    status ENUM('active','pending','suspended') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE farmer_profiles (
    user_id INT PRIMARY KEY,
    farm_name VARCHAR(160),
    location VARCHAR(160),
    land_area DECIMAL(10,2) DEFAULT 0,
    soil_type VARCHAR(80),
    irrigation VARCHAR(120),
    kyc_status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE buyer_profiles (
    user_id INT PRIMARY KEY,
    company_name VARCHAR(160),
    address VARCHAR(200),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE user_settings (
    user_id INT PRIMARY KEY,
    email_notifications TINYINT(1) NOT NULL DEFAULT 1,
    sms_notifications TINYINT(1) NOT NULL DEFAULT 1,
    language VARCHAR(40) NOT NULL DEFAULT 'English',
    region VARCHAR(80) NOT NULL DEFAULT 'Bangladesh',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE user_payment_methods (
    user_id INT PRIMARY KEY,
    method VARCHAR(40) NOT NULL DEFAULT 'bKash',
    account_number VARCHAR(80) DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE finance_profiles (
    user_id INT PRIMARY KEY,
    institution VARCHAR(160),
    designation VARCHAR(120),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    slug VARCHAR(80) NOT NULL UNIQUE
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
    rating DECIMAL(3,2) DEFAULT 0,
    product_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    status ENUM('active','inactive','sold_out') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
);

CREATE TABLE security_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(80) NOT NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_security_logs_action_created (action, created_at),
    INDEX idx_security_logs_user_created (user_id, created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_path VARCHAR(200) NOT NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE market_prices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    crop_name VARCHAR(120) NOT NULL,
    region VARCHAR(120) NOT NULL,
    unit VARCHAR(20) NOT NULL DEFAULT 'kg',
    price DECIMAL(10,2) NOT NULL,
    previous_price DECIMAL(10,2) DEFAULT 0,
    reported_on DATE NOT NULL
);

CREATE TABLE carts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    status ENUM('open','checked_out') NOT NULL DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cart_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    buyer_id INT NOT NULL,
    status ENUM('pending','confirmed','packed','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    payment_status ENUM('unpaid','paid','refunded') NOT NULL DEFAULT 'unpaid',
    payment_method VARCHAR(40),
    shipping_address VARCHAR(240),
    placed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    method VARCHAR(40) NOT NULL,
    status ENUM('pending','success','failed') NOT NULL DEFAULT 'pending',
    transaction_ref VARCHAR(120),
    paid_at TIMESTAMP NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

CREATE TABLE loans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT NOT NULL,
    loan_type VARCHAR(80) NOT NULL,
    principal DECIMAL(12,2) NOT NULL,
    interest_rate DECIMAL(5,2) NOT NULL,
    tenure_months INT NOT NULL,
    status ENUM('pending','active','closed','rejected') NOT NULL DEFAULT 'pending',
    approved_amount DECIMAL(12,2) DEFAULT 0,
    disbursed_at TIMESTAMP NULL,
    FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE loan_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT NOT NULL,
    requested_amount DECIMAL(12,2) NOT NULL,
    purpose VARCHAR(160),
    tenure_months INT DEFAULT NULL,
    location VARCHAR(120) DEFAULT NULL,
    farm_size VARCHAR(80) DEFAULT NULL,
    monthly_income VARCHAR(80) DEFAULT NULL,
    collateral VARCHAR(160) DEFAULT NULL,
    bank_name VARCHAR(120) DEFAULT NULL,
    bank_account VARCHAR(120) DEFAULT NULL,
    risk_level ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_by INT NULL,
    FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE loan_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loan_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    due_date DATE NOT NULL,
    paid_at DATE NULL,
    status ENUM('due','paid','late') NOT NULL DEFAULT 'due',
    FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE
);

CREATE TABLE conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject VARCHAR(160),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE conversation_participants (
    conversation_id INT NOT NULL,
    user_id INT NOT NULL,
    PRIMARY KEY (conversation_id, user_id),
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    sender_id INT NOT NULL,
    body TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(40) NOT NULL,
    title VARCHAR(160) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    metadata_json JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    rating INT NOT NULL,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE wishlists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE wishlist_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    wishlist_id INT NOT NULL,
    product_id INT NOT NULL,
    FOREIGN KEY (wishlist_id) REFERENCES wishlists(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE disputes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    opened_by INT NOT NULL,
    status ENUM('open','in_review','resolved') NOT NULL DEFAULT 'open',
    description TEXT,
    resolution TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    handled_by INT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (opened_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (handled_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE blog_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    excerpt TEXT,
    content MEDIUMTEXT,
    author_id INT NULL,
    cover_image VARCHAR(200),
    status ENUM('draft','published') NOT NULL DEFAULT 'draft',
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
);
