<?php
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/includes/helpers.php';
$user = require_role('admin');
$name = $user['full_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — KrishiConnect</title>
    <link rel="stylesheet" href="<?= asset_url('css/styles.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="<?= url('index.php'); ?>" class="logo">
                <img src="<?= asset_url('images/krishiconnect-logo.png'); ?>" alt="" style="filter:brightness(0) invert(1);height:28px">
                <span>KrishiConnect</span>
            </a>
        </div>
        <nav class="sidebar-nav">
            <a href="<?= url('pages/admin-dashboard.php'); ?>" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="<?= url('pages/user-moderation.php'); ?>"><i class="fas fa-users"></i> User Management</a>
            <a href="<?= url('admin/loan_management.php'); ?>"><i class="fas fa-hand-holding-usd"></i> Loan Management</a>
            <a href="<?= url('pages/marketplace.php'); ?>"><i class="fas fa-store"></i> Marketplace</a>
            <a href="<?= url('pages/reporting.php'); ?>"><i class="fas fa-chart-bar"></i> Reports</a>
            <a href="<?= url('pages/settings.php'); ?>"><i class="fas fa-cog"></i> Settings</a>
            <a href="<?= url('app/actions/logout.php'); ?>" style="margin-top:2rem;opacity:.6"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <main class="main-content">
        <div class="dash-header">
            <div><h1>Admin Dashboard</h1><p>System performance overview and management</p></div>
            <div class="meta"><i class="fas fa-shield-alt" style="color:var(--gold)"></i> Admin: <?= htmlspecialchars($name); ?> · Today 8:30 AM</div>
        </div>

        <div class="dash-cards">
            <div class="dash-card">
                <div class="info"><p>Total Users</p><h3>14,892</h3><div class="sub">+312 this week</div></div>
                <div class="icon green"><i class="fas fa-users"></i></div>
            </div>
            <div class="dash-card">
                <div class="info"><p>Active Listings</p><h3>3,470</h3><div class="sub">+58 today</div></div>
                <div class="icon gold"><i class="fas fa-shopping-basket"></i></div>
            </div>
            <div class="dash-card">
                <div class="info"><p>Open Disputes</p><h3>17</h3><div class="sub">4 critical</div></div>
                <div class="icon navy"><i class="fas fa-exclamation-triangle"></i></div>
            </div>
            <div class="dash-card">
                <div class="info"><p>Platform Uptime</p><h3>99.98%</h3><div class="sub">Last 30 days</div></div>
                <div class="icon red"><i class="fas fa-activity"></i></div>
            </div>
        </div>

        <div class="table-wrap">
            <h2><i class="fas fa-gavel" style="color:var(--gold);margin-right:8px"></i> Pending Loan Approvals</h2>
            <table>
                <thead><tr><th>ID</th><th>Farmer</th><th>Location</th><th>Type</th><th>Amount</th><th>Purpose</th><th>Score</th><th>Date</th><th>Action</th></tr></thead>
                <tbody>
                    <tr><td>#L-9856</td><td>Aminul Islam</td><td>Dinajpur</td><td>Seed Capital</td><td>৳25,000</td><td>Rice cultivation</td><td><span style="color:var(--gold)"><i class="fas fa-star"></i></span> 4.2</td><td>18 May</td><td><div style="display:flex;gap:4px"><button class="btn btn-primary btn-sm" style="padding:4px 12px">Approve</button><button class="btn btn-outline btn-sm" style="padding:4px 12px;color:var(--red);border-color:var(--red)">Reject</button></div></td></tr>
                    <tr><td>#L-9851</td><td>Sahana Begum</td><td>Sylhet</td><td>Equipment</td><td>৳85,000</td><td>Irrigation pump</td><td><span style="color:var(--gold)"><i class="fas fa-star"></i></span> 3.8</td><td>17 May</td><td><div style="display:flex;gap:4px"><button class="btn btn-primary btn-sm" style="padding:4px 12px">Approve</button><button class="btn btn-outline btn-sm" style="padding:4px 12px;color:var(--red);border-color:var(--red)">Reject</button></div></td></tr>
                    <tr><td>#L-9845</td><td>Farhad Khan</td><td>Rajshahi</td><td>Land Dev</td><td>৳150,000</td><td>Irrigation system</td><td><span style="color:var(--gold)"><i class="fas fa-star"></i></span> 4.7</td><td>16 May</td><td><div style="display:flex;gap:4px"><button class="btn btn-primary btn-sm" style="padding:4px 12px">Approve</button><button class="btn btn-outline btn-sm" style="padding:4px 12px;color:var(--red);border-color:var(--red)">Reject</button></div></td></tr>
                    <tr><td>#L-9832</td><td>Nasrin Jahan</td><td>Khulna</td><td>Seed Capital</td><td>৳35,000</td><td>Vegetable farming</td><td><span style="color:var(--gold)"><i class="fas fa-star"></i></span> 4.0</td><td>15 May</td><td><div style="display:flex;gap:4px"><button class="btn btn-primary btn-sm" style="padding:4px 12px">Approve</button><button class="btn btn-outline btn-sm" style="padding:4px 12px;color:var(--red);border-color:var(--red)">Reject</button></div></td></tr>
                </tbody>
            </table>
        </div>

        <div class="table-wrap">
            <h2><i class="fas fa-stream" style="color:var(--emerald);margin-right:8px"></i> Recent Activities</h2>
            <table>
                <thead><tr><th>Time</th><th>User</th><th>Type</th><th>Activity</th><th>Details</th></tr></thead>
                <tbody>
                    <tr><td>10:45 AM</td><td>Abdul Rahman</td><td><span class="badge-status badge-success">Farmer</span></td><td>New Listing</td><td>Added 100kg potatoes at ৳25/kg</td></tr>
                    <tr><td>10:30 AM</td><td>Fatima Begum</td><td><span class="badge-status badge-info">Buyer</span></td><td>New Order</td><td>Purchased ৳1,850 worth of vegetables</td></tr>
                    <tr><td>9:15 AM</td><td>Karim Miah</td><td><span class="badge-status badge-success">Farmer</span></td><td>Loan Payment</td><td>Paid ৳5,000 towards loan #L-7564</td></tr>
                    <tr><td>8:50 AM</td><td>Selim Ahmed</td><td><span class="badge-status badge-success">Farmer</span></td><td>Registration</td><td>New farmer from Barisal district</td></tr>
                    <tr><td>Yesterday</td><td>Organic Foods Ltd</td><td><span class="badge-status badge-info">Buyer</span></td><td>Registration</td><td>New business buyer from Dhaka</td></tr>
                </tbody>
            </table>
        </div>

        <h2 style="font-size:1.2rem;margin:1.5rem 0 1rem"><i class="fas fa-chart-pie" style="color:var(--emerald);margin-right:8px"></i> Analytics Overview</h2>
        <div class="dash-cards">
            <div class="card" style="text-align:center">
                <div style="width:120px;height:120px;border-radius:50%;background:conic-gradient(var(--emerald) 0% 60%,var(--gold) 60% 80%,var(--navy) 80% 100%);margin:0 auto 1rem;display:flex;align-items:center;justify-content:center"><div style="width:80px;height:80px;border-radius:50%;background:var(--white);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.1rem">14,892</div></div>
                <h3 style="font-size:1rem">User Distribution</h3>
                <p style="color:var(--gray);font-size:.8rem;margin-top:.25rem">60% Farmers · 20% Buyers · 20% Other</p>
            </div>
            <div class="card" style="text-align:center">
                <div style="width:120px;height:120px;border-radius:50%;background:conic-gradient(var(--gold) 0% 45%,var(--emerald) 45% 75%,var(--emerald-light) 75% 100%);margin:0 auto 1rem;display:flex;align-items:center;justify-content:center"><div style="width:80px;height:80px;border-radius:50%;background:var(--white);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.1rem">৳12.5M</div></div>
                <h3 style="font-size:1rem">Loan Distribution</h3>
                <p style="color:var(--gray);font-size:.8rem;margin-top:.25rem">45% Seed · 30% Equipment · 25% Land</p>
            </div>
            <div class="card" style="text-align:center">
                <div style="width:120px;height:120px;border-radius:50%;background:conic-gradient(var(--emerald) 0% 55%,var(--gold) 55% 85%,var(--red) 85% 100%);margin:0 auto 1rem;display:flex;align-items:center;justify-content:center"><div style="width:80px;height:80px;border-radius:50%;background:var(--white);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.1rem">92%</div></div>
                <h3 style="font-size:1rem">Repayment Rate</h3>
                <p style="color:var(--gray);font-size:.8rem;margin-top:.25rem">55% On-time · 30% Early · 15% Late</p>
            </div>
        </div>
    </main>
</div>

</body>
</html>
