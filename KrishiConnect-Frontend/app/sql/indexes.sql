-- =====================================================
-- PERFORMANCE INDEXES
-- =====================================================

-- =====================================================
-- USER & AUTHENTICATION INDEXES
-- =====================================================

CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_users_created_at ON users(created_at);
CREATE INDEX idx_users_role_status ON users(role, status);

CREATE INDEX idx_farmer_profiles_kyc_status ON farmer_profiles(kyc_status);
CREATE INDEX idx_farmer_profiles_location ON farmer_profiles(location);

CREATE INDEX idx_buyer_profiles_active ON buyer_profiles(user_id);

-- =====================================================
-- PRODUCT INDEXES
-- =====================================================

CREATE INDEX idx_products_farmer_id ON products(farmer_id);
CREATE INDEX idx_products_category_id ON products(category_id);
CREATE INDEX idx_products_status ON products(status);
CREATE INDEX idx_products_created_at ON products(created_at);
CREATE INDEX idx_products_farmer_status ON products(farmer_id, status);
CREATE INDEX idx_products_price ON products(price);
CREATE INDEX idx_products_rating ON products(rating);

CREATE INDEX idx_product_images_product_id ON product_images(product_id);
CREATE INDEX idx_product_batch_product_id ON product_batch(product_id);

CREATE INDEX idx_categories_slug ON categories(slug);

-- =====================================================
-- ORDER & CART INDEXES
-- =====================================================

CREATE INDEX idx_carts_user_id ON carts(user_id);
CREATE INDEX idx_carts_status ON carts(status);
CREATE INDEX idx_cart_items_cart_id ON cart_items(cart_id);
CREATE INDEX idx_cart_items_product_id ON cart_items(product_id);

CREATE INDEX idx_orders_buyer_id ON orders(buyer_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_payment_status ON orders(payment_status);
CREATE INDEX idx_orders_placed_at ON orders(placed_at);
CREATE INDEX idx_orders_order_number ON orders(order_number);
CREATE INDEX idx_orders_buyer_placed ON orders(buyer_id, placed_at);
CREATE INDEX idx_orders_created_delivered ON orders(created_at, status);

CREATE INDEX idx_order_items_order_id ON order_items(order_id);
CREATE INDEX idx_order_items_product_id ON order_items(product_id);
CREATE INDEX idx_order_items_farmer_id ON order_items(farmer_id);

CREATE INDEX idx_payments_order_id ON payments(order_id);
CREATE INDEX idx_payments_status ON payments(status);
CREATE INDEX idx_payments_paid_at ON payments(paid_at);
CREATE INDEX idx_payments_transaction_ref ON payments(transaction_ref);

CREATE INDEX idx_refunds_order_id ON refunds(order_id);
CREATE INDEX idx_refunds_status ON refunds(status);

-- =====================================================
-- LOAN & FINANCE INDEXES
-- =====================================================

CREATE INDEX idx_loan_applications_farmer_id ON loan_applications(farmer_id);
CREATE INDEX idx_loan_applications_status ON loan_applications(status);
CREATE INDEX idx_loan_applications_submitted_at ON loan_applications(submitted_at);
CREATE INDEX idx_loan_applications_risk_level ON loan_applications(risk_level);

CREATE INDEX idx_loans_farmer_id ON loans(farmer_id);
CREATE INDEX idx_loans_status ON loans(status);
CREATE INDEX idx_loans_maturity_date ON loans(maturity_date);
CREATE INDEX idx_loans_created_at ON loans(created_at);
CREATE INDEX idx_loans_farmer_status ON loans(farmer_id, status);

CREATE INDEX idx_loan_payments_loan_id ON loan_payments(loan_id);
CREATE INDEX idx_loan_payments_due_date ON loan_payments(due_date);
CREATE INDEX idx_loan_payments_status ON loan_payments(status);
CREATE INDEX idx_loan_payments_paid_at ON loan_payments(paid_at);

CREATE INDEX idx_loan_disbursements_loan_id ON loan_disbursements(loan_id);
CREATE INDEX idx_loan_disbursements_status ON loan_disbursements(status);

-- =====================================================
-- REVIEW & RATING INDEXES
-- =====================================================

CREATE INDEX idx_reviews_product_id ON reviews(product_id);
CREATE INDEX idx_reviews_order_id ON reviews(order_id);
CREATE INDEX idx_reviews_reviewer_id ON reviews(reviewer_id);
CREATE INDEX idx_reviews_reviewed_user_id ON reviews(reviewed_user_id);
CREATE INDEX idx_reviews_rating ON reviews(rating);
CREATE INDEX idx_reviews_created_at ON reviews(created_at);
CREATE INDEX idx_reviews_product_rating ON reviews(product_id, rating);

CREATE INDEX idx_review_responses_review_id ON review_responses(review_id);

-- =====================================================
-- WISHLIST INDEXES
-- =====================================================

CREATE INDEX idx_wishlists_user_id ON wishlists(user_id);
CREATE INDEX idx_wishlist_items_wishlist_id ON wishlist_items(wishlist_id);
CREATE INDEX idx_wishlist_items_product_id ON wishlist_items(product_id);

-- =====================================================
-- MESSAGING & NOTIFICATION INDEXES
-- =====================================================

CREATE INDEX idx_conversations_type ON conversations(conversation_type);
CREATE INDEX idx_conversations_created_at ON conversations(created_at);

CREATE INDEX idx_conversation_participants_user_id ON conversation_participants(user_id);
CREATE INDEX idx_conversation_participants_joined ON conversation_participants(joined_at);

CREATE INDEX idx_messages_conversation_id ON messages(conversation_id);
CREATE INDEX idx_messages_sender_id ON messages(sender_id);
CREATE INDEX idx_messages_created_at ON messages(created_at);
CREATE INDEX idx_messages_read_status ON messages(conversation_id, read_at);

CREATE INDEX idx_notifications_user_id ON notifications(user_id);
CREATE INDEX idx_notifications_is_read ON notifications(is_read);
CREATE INDEX idx_notifications_created_at ON notifications(created_at);
CREATE INDEX idx_notifications_user_read ON notifications(user_id, is_read);
CREATE INDEX idx_notifications_related ON notifications(related_type, related_id);

-- =====================================================
-- DISPUTE INDEXES
-- =====================================================

CREATE INDEX idx_disputes_order_id ON disputes(order_id);
CREATE INDEX idx_disputes_loan_id ON disputes(loan_id);
CREATE INDEX idx_disputes_opened_by ON disputes(opened_by);
CREATE INDEX idx_disputes_status ON disputes(status);
CREATE INDEX idx_disputes_created_at ON disputes(created_at);
CREATE INDEX idx_disputes_severity ON disputes(severity);

CREATE INDEX idx_dispute_messages_dispute_id ON dispute_messages(dispute_id);
CREATE INDEX idx_dispute_documents_dispute_id ON dispute_documents(dispute_id);

-- =====================================================
-- ACTIVITY & AUDIT INDEXES
-- =====================================================

CREATE INDEX idx_activity_logs_user_id ON activity_logs(user_id);
CREATE INDEX idx_activity_logs_entity ON activity_logs(entity_type, entity_id);
CREATE INDEX idx_activity_logs_created_at ON activity_logs(created_at);
CREATE INDEX idx_activity_logs_action ON activity_logs(action);

CREATE INDEX idx_admin_logs_admin_id ON admin_logs(admin_id);
CREATE INDEX idx_admin_logs_target_user ON admin_logs(target_user_id);
CREATE INDEX idx_admin_logs_created_at ON admin_logs(created_at);

-- =====================================================
-- REPORTS & ANALYTICS INDEXES
-- =====================================================

CREATE INDEX idx_sales_reports_farmer_id ON sales_reports(farmer_id);
CREATE INDEX idx_sales_reports_month ON sales_reports(report_month);

CREATE INDEX idx_financial_reports_officer_id ON financial_reports(finance_officer_id);
CREATE INDEX idx_financial_reports_month ON financial_reports(report_month);

-- =====================================================
-- MARKET PRICE INDEXES
-- =====================================================

CREATE INDEX idx_market_prices_crop_region ON market_prices(crop_name, region);
CREATE INDEX idx_market_prices_reported_on ON market_prices(reported_on);
CREATE INDEX idx_market_prices_category_id ON market_prices(category_id);

-- =====================================================
-- COMPOSITE INDEXES FOR COMMON QUERIES
-- =====================================================

-- For dashboard queries
CREATE INDEX idx_orders_buyer_status_date ON orders(buyer_id, status, placed_at);
CREATE INDEX idx_products_farmer_category_status ON products(farmer_id, category_id, status);

-- For analytics queries
CREATE INDEX idx_order_items_farmer_product ON order_items(farmer_id, product_id);
CREATE INDEX idx_loan_payments_loan_status_date ON loan_payments(loan_id, status, due_date);

-- For search queries
CREATE INDEX idx_products_name ON products(name);
CREATE INDEX idx_users_full_name ON users(full_name);
CREATE INDEX idx_categories_name ON categories(name);

-- =====================================================
-- FULLTEXT INDEXES
-- =====================================================

ALTER TABLE products ADD FULLTEXT INDEX ft_products_search (name, variety, description);
ALTER TABLE blog_posts ADD FULLTEXT INDEX ft_blog_search (title, excerpt, content);
ALTER TABLE disputes ADD FULLTEXT INDEX ft_disputes_search (title, description);
