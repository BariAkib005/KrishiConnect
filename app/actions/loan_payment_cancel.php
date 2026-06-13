<?php
/** SSLCommerz cancel callback for loan repayments — farmer backed out. */
require_once __DIR__ . '/../includes/helpers.php';

redirect('pages/repayment_ledger.php?error=cancelled');
