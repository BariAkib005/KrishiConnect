<?php
/** SSLCommerz cancel callback — buyer backed out, order stays unpaid. */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$tranId = (string)($_REQUEST['tran_id'] ?? '');
if ($tranId === '') {
    redirect('pages/marketplace.php');
}

$pdo = db();
$stmt = $pdo->prepare('SELECT id FROM orders WHERE tran_id = ?');
$stmt->execute([$tranId]);
$order = $stmt->fetch();
if (!$order) {
    redirect('pages/marketplace.php');
}

redirect('pages/payment.php?order_id=' . (int)$order['id'] . '&error=cancelled');
