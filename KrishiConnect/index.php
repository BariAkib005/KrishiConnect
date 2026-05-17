<?php
// Database connection
$servername = "localhost";
$username = "root"; // Change as needed
$password = ""; // Change as needed
$dbname = "krishiconnect_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch featured vegetables
$vegetablesSql = "SELECT * FROM products WHERE category='vegetables' LIMIT 4";
$vegetablesResult = $conn->query($vegetablesSql);

// Fetch success stories
$storiesSql = "SELECT * FROM success_stories LIMIT 3";
$storiesResult = $conn->query($storiesSql);

// Get site statistics
$statsSql = "SELECT * FROM site_statistics WHERE id=1";
$statsResult = $conn->query($statsSql);
$stats = $statsResult->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KrishiConnect - Connecting Farmers, Buyers and Finance</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container navbar">
            <div class="logo">
                <img src="images/krishiconnect-logo.png" alt="KrishiConnect Logo">
                <span>KrishiConnect</span>
            </div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="pages/marketplace.php">Marketplace</a></li>
                <li><a href="pages/microfinance.php">Microfinance</a></li>
                <li><a href="pages/about.php">About Us</a></li>
                <li><a href="pages/contact.php">Contact</a></li>
                <li><a href="pages/login.php" class="btn btn-primary">Login/Register</a></li>
            </ul>
            <button class="language-switch">বাংলা</button>
            <div class="hamburger">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container hero-content">
            <h1>Connecting Farmers Directly to Buyers and Financial Support</h1>
            <p>KrishiConnect empowers Bangladeshi farmers with direct market access and microfinance opportunities to grow their agricultural businesses sustainably.</p>
            <div class="btn-group">
                <a href="pages/farmer-register.php" class="btn btn-primary">I'm a Farmer</a>
                <a href="pages/buyer-register.php" class="btn btn-secondary">I'm a Buyer</a>
                <a href="pages/microfinance.php" class="btn btn-secondary">Microfinance</a>
            </div>
        </div>
    </section>

    <!-- Featured Products Section -->
    <section class="section">
        <div class="container">
            <div class="section-title">
                <h2>Seasonal Vegetables</h2>
                <p>Fresh produce directly from farms across Bangladesh</p>
            </div>
            <div class="featured-items">
                <?php
                if ($vegetablesResult->num_rows > 0) {
                    while($vegetable = $vegetablesResult->fetch_assoc()) {
                        echo '<div class="featured-item">
                            <img src="' . $vegetable['image_path'] . '" alt="' . $vegetable['name'] . '">
                            <div class="featured-item-content">
                                <h3>' . $vegetable['name'] . ' (' . $vegetable['local_name'] . ')</h3>
                                <p>' . $vegetable['description'] . '</p>
                                <div class="price">৳' . $vegetable['price'] . ' per ' . $vegetable['unit'] . '</div>
                            </div>
                        </div>';
                    }
                } else {
                    // Fallback for when database isn't set up yet
                    ?>
                    <div class="featured-item">
                        <img src="images/vegetables/brinjal.jpg" alt="Brinjal">
                        <div class="featured-item-content">
                            <h3>Brinjal (Begun)</h3>
                            <p>Fresh brinjal from Rangpur region</p>
                            <div class="price">৳40 per kg</div>
                        </div>
                    </div>
                    <div class="featured-item">
                        <img src="images/vegetables/tomato.jpg" alt="Tomato">
                        <div class="featured-item-content">
                            <h3>Tomato</h3>
                            <p>Organic tomatoes from Gazipur</p>
                            <div class="price">৳60 per kg</div>
                        </div>
                    </div>
                    <div class="featured-item">
                        <img src="images/vegetables/potato.jpg" alt="Potato">
                        <div class="featured-item-content">
                            <h3>Potato (Alu)</h3>
                            <p>Premium quality potatoes from Munshiganj</p>
                            <div class="price">৳25 per kg</div>
                        </div>
                    </div>
                    <div class="featured-item">
                        <img src="images/vegetables/cauliflower.jpg" alt="Cauliflower">
                        <div class="featured-item-content">
                            <h3>Cauliflower (Phulkopi)</h3>
                            <p>Fresh cauliflower from Bogra</p>
                            <div class="price">৳45 per piece</div>
                        </div>
                    </div>
                <?php } ?>
            </div>
            <div style="text-align: center; margin-top: 2rem;">
                <a href="pages/marketplace.php" class="btn btn-primary">Browse All Products</a>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="section stats">
        <div class="container">
            <div class="stats-container">
                <?php if ($statsResult->num_rows > 0) { ?>
                    <div class="stat-item">
                        <h3><?php echo number_format($stats['farmers_count']); ?>+</h3>
                        <p>Registered Farmers</p>
                    </div>
                    <div class="stat-item">
                        <h3><?php echo number_format($stats['buyers_count']); ?>+</h3>
                        <p>Active Buyers</p>
                    </div>
                    <div class="stat-item">
                        <h3>৳<?php echo number_format($stats['loans_disbursed'] / 1000000, 1); ?>M+</h3>
                        <p>Loans Disbursed</p>
                    </div>
                    <div class="stat-item">
                        <h3><?php echo number_format($stats['districts_covered']); ?>+</h3>
                        <p>Districts Covered</p>
                    </div>
                <?php } else { ?>
                    <div class="stat-item">
                        <h3>1,500+</h3>
                        <p>Registered Farmers</p>
                    </div>
                    <div class="stat-item">
                        <h3>5,000+</h3>
                        <p>Active Buyers</p>
                    </div>
                    <div class="stat-item">
                        <h3>৳30M+</h3>
                        <p>Loans Disbursed</p>
                    </div>
                    <div class="stat-item">
                        <h3>50+</h3>
                        <p>Districts Covered</p>
                    </div>
                <?php } ?>
            </div>
        </div>
    </section>

    <!-- Success Stories Section -->
    <section class="section">
        <div class="container">
            <div class="section-title">
                <h2>Success Stories</h2>
                <p>How KrishiConnect is transforming lives of Bangladeshi farmers</p>
            </div>
            <div class="stories-container">
                <?php
                if ($storiesResult->num_rows > 0) {
                    while($story = $storiesResult->fetch_assoc()) {
                        echo '<div class="story-card">
                            <img src="' . $story['image_path'] . '" alt="' . $story['farmer_name'] . '">
                            <div class="story-content">
                                <h3>' . $story['title'] . '</h3>
                                <p>' . $story['excerpt'] . '</p>
                                <a href="pages/success-story.php?id=' . $story['id'] . '" class="btn btn-primary">Read More</a>
                            </div>
                        </div>';
                    }
                } else {
                    // Fallback for when database isn't set up yet
                    ?>
                    <div class="story-card">
                        <img src="images/farmers/farmer1.jpg" alt="Farmer Rahman">
                        <div class="story-content">
                            <h3>Rahman's Journey to Self-Reliance</h3>
                            <p>After receiving a ৳50,000 loan through KrishiConnect, Rahman from Sylhet was able to expand his vegetable farm and increase his income by 40% within 6 months.</p>
                            <a href="#" class="btn btn-primary">Read More</a>
                        </div>
                    </div>
                    <div class="story-card">
                        <img src="images/farmers/farmer2.jpg" alt="Farmer Fatima">
                        <div class="story-content">
                            <h3>Fatima's Market Access Success</h3>
                            <p>Fatima from Khulna couldn't find buyers for her organic vegetables until she joined KrishiConnect. Now she has regular customers and has doubled her monthly income.</p>
                            <a href="#" class="btn btn-primary">Read More</a>
                        </div>
                    </div>
                    <div class="story-card">
                        <img src="images/farmers/farmer3.jpg" alt="Farmer Karim">
                        <div class="story-content">
                            <h3>Karim's Community Impact</h3>
                            <p>With a KrishiConnect microfinance loan, Karim started a cooperative that now employs 15 people in his village and supplies vegetables to Dhaka restaurants.</p>
                            <a href="#" class="btn btn-primary">Read More</a>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-container">
                <div class="footer-col">
                    <h3>KrishiConnect</h3>
                    <p>Empowering Bangladeshi farmers through direct market access and financial inclusion.</p>
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <?php $conn->close(); ?>
</body>
</html> 