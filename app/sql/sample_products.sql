-- ============================================================================
-- KrishiConnect — Marketplace seed catalog (16 approved products, 4 per group)
-- ============================================================================
-- This script is also applied automatically at runtime by
-- db_ensure_demo_data() in app/includes/db.php, so a fresh clone is populated
-- with zero manual steps. It is kept here as a runnable, reviewable reference.
--
-- Schema notes (mapping the requested dataset onto the real `products` table):
--   * `category`   -> resolved to the `category_id` foreign key via sub-select.
--   * `farmer_id`  -> resolved from a demo farmer's email via sub-select so the
--                     foreign key is valid regardless of auto-increment values.
--   * `image_url`  -> local asset paths under images/vegetables/ (one Unsplash/
--                     placehold.co fallback for litchi). The frontend helper
--                     product_image_src() URL-encodes paths, so filenames with
--                     spaces (e.g. "bitter gourd.jpg") resolve correctly.
--   * `status`     -> 'active'  (live)   and product_status = 'approved' so the
--                     rows immediately pass the marketplace WHERE filter
--                     (WHERE status = 'active' AND product_status = 'approved').
--   * Timestamps   -> created_at / updated_at default to CURRENT_TIMESTAMP.
-- Run order: requires categories + demo farmer accounts to already exist.
-- ============================================================================

INSERT INTO products
    (farmer_id, category_id, name, local_name, variety, description, image_url,
     price, unit, quantity_available, rating, product_status, status, is_organic, is_featured)
VALUES
-- ---------------------------------------------------------------------------
-- Vegetables
-- ---------------------------------------------------------------------------
((SELECT id FROM users WHERE email='farmer1@krishiconnect.com'), (SELECT id FROM categories WHERE slug='vegetables'),
 'Premium Bitter Gourd', 'Korola', 'Hybrid',
 'Tender, dark-green bitter gourd grown in the fertile fields of Jessore. Hand-picked for export-grade quality.',
 'images/vegetables/bitter gourd.jpg', 75.00, 'kg', 180.00, 4.6, 'approved', 'active', 1, 1),
((SELECT id FROM users WHERE email='farmer2@krishiconnect.com'), (SELECT id FROM categories WHERE slug='vegetables'),
 'Fresh Green Okra', 'Dherosh', 'Local',
 'Crisp, fibre-free okra harvested daily from Bogura. Ideal for frying and curries.',
 'images/vegetables/okra.jpg', 50.00, 'kg', 240.00, 4.2, 'approved', 'active', 0, 0),
((SELECT id FROM users WHERE email='farmer3@krishiconnect.com'), (SELECT id FROM categories WHERE slug='vegetables'),
 'Snow White Cauliflower', 'Fulkopi', 'Winter',
 'Compact, milky-white cauliflower heads from the cool highlands of Rangpur.',
 'images/vegetables/cauliflower.jpg', 45.00, 'kg', 300.00, 4.4, 'approved', 'active', 0, 1),
((SELECT id FROM users WHERE email='farmer4@krishiconnect.com'), (SELECT id FROM categories WHERE slug='vegetables'),
 'Red Amaranth Spinach', 'Lal Shak', 'Organic',
 'Iron-rich red spinach grown organically near Mymensingh without chemical fertilisers.',
 'images/vegetables/spinach.jpg', 30.00, 'bunch', 150.00, 4.5, 'approved', 'active', 1, 0),

-- ---------------------------------------------------------------------------
-- Fruits
-- ---------------------------------------------------------------------------
((SELECT id FROM users WHERE email='farmer5@krishiconnect.com'), (SELECT id FROM categories WHERE slug='fruits'),
 'Himsagar Mango', 'Aam', 'Himsagar',
 'The king of mangoes from Rajshahi — fibreless, fragrant, and intensely sweet. Naturally ripened.',
 'images/vegetables/himsagar.jpg', 180.00, 'kg', 500.00, 4.9, 'approved', 'active', 0, 1),
((SELECT id FROM users WHERE email='farmer6@krishiconnect.com'), (SELECT id FROM categories WHERE slug='fruits'),
 'Sweet Litchi', 'Lichu', 'China-3',
 'Juicy, thin-skinned litchi from Dinajpur orchards, picked at peak sweetness.',
 'https://placehold.co/600x400/e8a317/ffffff?text=Lichu', 250.00, 'kg', 220.00, 4.7, 'approved', 'active', 0, 1),
((SELECT id FROM users WHERE email='farmer7@krishiconnect.com'), (SELECT id FROM categories WHERE slug='fruits'),
 'Sagar Banana', 'Sagar Kola', 'Sagar',
 'Premium dessert bananas from Narsingdi — smooth, creamy, and perfectly sweet.',
 'images/vegetables/L14Av.jpg', 90.00, 'dozen', 400.00, 4.3, 'approved', 'active', 1, 0),
((SELECT id FROM users WHERE email='farmer8@krishiconnect.com'), (SELECT id FROM categories WHERE slug='fruits'),
 'Whole Jackfruit', 'Kathal', 'Local',
 'Bangladesh national fruit — large, aromatic jackfruit from Gazipur, sold by the piece.',
 'images/vegetables/jack fruit.jpg', 300.00, 'piece', 80.00, 4.1, 'approved', 'active', 0, 0),

-- ---------------------------------------------------------------------------
-- Grains
-- ---------------------------------------------------------------------------
((SELECT id FROM users WHERE email='farmer9@krishiconnect.com'), (SELECT id FROM categories WHERE slug='grains'),
 'Aromatic Kalijira Rice', 'Kalijira Chal', 'Kalijira',
 'Fine, fragrant baby rice from Dinajpur — the premium choice for polao and payesh.',
 'images/vegetables/kalijira chal.jpg', 140.00, 'kg', 600.00, 4.8, 'approved', 'active', 1, 1),
((SELECT id FROM users WHERE email='farmer10@krishiconnect.com'), (SELECT id FROM categories WHERE slug='grains'),
 'Miniket Rice', 'Miniket Chal', 'Miniket',
 'Slim, polished everyday rice milled in Naogaon. Clean, sortexed, and ready to cook.',
 'images/vegetables/miniket chal.jpg', 72.00, 'kg', 1000.00, 4.4, 'approved', 'active', 0, 1),
((SELECT id FROM users WHERE email='farmer1@krishiconnect.com'), (SELECT id FROM categories WHERE slug='grains'),
 'Red Lentil', 'Masoor Dal', 'Local',
 'Plump, fast-cooking red lentils from Faridpur — a daily protein staple.',
 'images/vegetables/masoor dal.jpg', 130.00, 'kg', 350.00, 4.5, 'approved', 'active', 0, 0),
((SELECT id FROM users WHERE email='farmer2@krishiconnect.com'), (SELECT id FROM categories WHERE slug='grains'),
 'Whole Wheat', 'Gom', 'Local',
 'Stone-ground-ready wheat grain from Thakurgaon, perfect for fresh atta and ruti.',
 'images/vegetables/wheat.jpg', 55.00, 'kg', 800.00, 4.0, 'approved', 'active', 0, 0),

-- ---------------------------------------------------------------------------
-- Spices
-- ---------------------------------------------------------------------------
((SELECT id FROM users WHERE email='farmer3@krishiconnect.com'), (SELECT id FROM categories WHERE slug='spices'),
 'Turmeric Powder', 'Holud', 'High Curcumin',
 'Sun-dried, stone-milled turmeric from Bogura with deep colour and high curcumin content.',
 'images/vegetables/holud.jpg', 220.00, 'kg', 120.00, 4.7, 'approved', 'active', 1, 1),
((SELECT id FROM users WHERE email='farmer4@krishiconnect.com'), (SELECT id FROM categories WHERE slug='spices'),
 'Dried Red Chili', 'Shukna Morich', 'Local',
 'Fiery, sun-dried red chillies from Jessore — bold heat and rich aroma.',
 'images/vegetables/dried-red-chilied-peppers.jpg', 320.00, 'kg', 90.00, 4.6, 'approved', 'active', 0, 1),
((SELECT id FROM users WHERE email='farmer5@krishiconnect.com'), (SELECT id FROM categories WHERE slug='spices'),
 'Fresh Ginger', 'Ada', 'Local',
 'Pungent, juicy ginger rhizomes from the hills of Sylhet. Cleaned and graded.',
 'images/vegetables/ginger.jpg', 200.00, 'kg', 160.00, 4.4, 'approved', 'active', 1, 0),
((SELECT id FROM users WHERE email='farmer6@krishiconnect.com'), (SELECT id FROM categories WHERE slug='spices'),
 'Premium Garlic', 'Roshun', 'Single Clove',
 'Strong, single-clove (mono) garlic from Natore — prized for its concentrated flavour.',
 'images/vegetables/garlic-growing-guide_0.jpg', 240.00, 'kg', 140.00, 4.3, 'approved', 'active', 0, 0);
