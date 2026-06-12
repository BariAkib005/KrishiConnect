ALTER TABLE users
    ADD COLUMN admin_pin_hash VARCHAR(255) DEFAULT NULL AFTER password_hash;

ALTER TABLE products
    ADD COLUMN product_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending' AFTER rating;

UPDATE products
SET product_status = 'approved'
WHERE status = 'active';

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

-- Replace the hash with password_hash('YOUR_6_DIGIT_PIN', PASSWORD_DEFAULT).
-- Demo PIN: 123456
UPDATE users
SET admin_pin_hash = '$2y$10$kNblpFV7dJN1Lo9cqJwgYOS0jZc75doDMHqifrlqvoNmzWXp9wzsu'
WHERE role = 'admin' AND admin_pin_hash IS NULL;
