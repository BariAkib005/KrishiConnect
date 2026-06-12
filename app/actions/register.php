<?php
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pages/register.php');
}

require_csrf_token($_POST['csrf_token'] ?? null, 'register', 'pages/register.php?error=csrf');

$fullName = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$role = $_POST['role'] ?? 'farmer';
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

// Only farmer/buyer/finance may be self-registered. `admin` is never
// self-serviceable; an unknown/forged role is rejected outright.
$allowedRoles = ['farmer', 'buyer', 'finance'];
if (!in_array($role, $allowedRoles, true)) {
    redirect('pages/register.php?error=role');
}

// ১. কোনো ফিল্ড ফাঁকা আছে কিনা চেক করা
if ($fullName === '' || $email === '' || $password === '') {
    redirect('pages/register.php?error=missing');
}

// ইমেইল ফরম্যাট ভ্যালিডেশন
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect('pages/register.php?error=email');
}

// ২. পাসওয়ার্ড এবং কনফার্ম পাসওয়ার্ড মিলেছে কিনা চেক করা
if ($password !== $confirm) {
    redirect('pages/register.php?error=nomatch');
}

// ৩. পাসওয়ার্ড সিকিউরিটি লিমিটেশন (Password Strength Check)
$uppercase    = preg_match('@[A-Z]@', $password); // কমপক্ষে ১টি বড় হাতের অক্ষর
$lowercase    = preg_match('@[a-z]@', $password); // কমপক্ষে ১টি ছোট হাতের অক্ষর
$number       = preg_match('@[0-9]@', $password); // কমপক্ষে ১টি সংখ্যা
$specialChars = preg_match('@[^\w]@', $password); // কমপক্ষে ১টি বিশেষ চিহ্ন (@, #, $, %, ইত্যাদি)

if (!$uppercase || !$lowercase || !$number || !$specialChars || strlen($password) < 8) {
    // পাসওয়ার্ড দুর্বল হলে 'weak_password' এরর দিয়ে আবার রেজিস্ট্রেশন পেজে পাঠিয়ে দেবে
    redirect('pages/register.php?error=weak_password');
}

// ৪. ডাটাবেজে ইমেইল অলরেডি আছে কিনা চেক করা
$pdo = db();
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    redirect('pages/register.php?error=exists');
}

// ৫. পাসওয়ার্ড হ্যাশ করা এবং ইউজার তৈরি করা
// Finance accounts can approve loans, so they start as "pending" and must be
// activated by an admin before they can sign in. Farmers/buyers are active now.
$status = $role === 'finance' ? 'pending' : 'active';

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare('INSERT INTO users (full_name, email, phone, role, password_hash, status) VALUES (?, ?, ?, ?, ?, ?)');
$stmt->execute([$fullName, $email, $phone, $role, $hash, $status]);

// Finance signups are not logged in — they wait for admin approval.
if ($status === 'pending') {
    redirect('pages/login.php?notice=pending');
}

$userId = (int)$pdo->lastInsertId();
login_user(['id' => $userId, 'role' => $role]);

// ৬. রোল অনুযায়ী ড্যাশবোর্ডে রিডাইরেক্ট করা
switch ($role) {
    case 'buyer':
        redirect('pages/buyer-dashboard.php');
    default:
        redirect('pages/dashboard.php');
}