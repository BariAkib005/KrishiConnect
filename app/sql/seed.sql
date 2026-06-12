-- Standard demo accounts use the password: password123
-- Demo admin PIN: 123456
INSERT INTO users (full_name, email, phone, role, password_hash, admin_pin_hash, status)
VALUES
  ('Admin User', 'admin@krishiconnect.test', '01000000000', 'admin', '$2y$10$5IBaLo5N4dMHQT1b..Brsu/XJspRQxyRTseETEik.d4xooRqApw6C', '$2y$10$kNblpFV7dJN1Lo9cqJwgYOS0jZc75doDMHqifrlqvoNmzWXp9wzsu', 'active'),
  ('Rafiq Ahmed', 'farmer@krishiconnect.test', '01700000000', 'farmer', '$2y$10$5IBaLo5N4dMHQT1b..Brsu/XJspRQxyRTseETEik.d4xooRqApw6C', NULL, 'active'),
  ('Anita Rahman', 'buyer@krishiconnect.test', '01800000000', 'buyer', '$2y$10$5IBaLo5N4dMHQT1b..Brsu/XJspRQxyRTseETEik.d4xooRqApw6C', NULL, 'active');

INSERT INTO categories (name, slug)
VALUES
  ('Vegetables', 'vegetables'),
  ('Grains', 'grains'),
  ('Fruits', 'fruits'),
  ('Spices', 'spices');

INSERT INTO products (farmer_id, category_id, name, variety, description, price, unit, quantity_available, rating, product_status, status)
VALUES
  (2, 1, 'Tomato', 'Hybrid', 'Fresh red tomatoes from Gazipur.', 60, 'kg', 400, 4.3, 'approved', 'active'),
  (2, 1, 'Brinjal', 'Local', 'Organic brinjal with rich taste.', 40, 'kg', 280, 4.5, 'approved', 'active'),
  (2, 1, 'Potato', 'Granola', 'Premium potatoes harvested this week.', 25, 'kg', 600, 4.1, 'approved', 'active');

INSERT INTO product_images (product_id, image_path, is_primary)
VALUES
  (1, 'images/vegetables/tomato.jpg', 1),
  (2, 'images/vegetables/brinjal.jpg', 1),
  (3, 'images/vegetables/potato.jpg', 1);
