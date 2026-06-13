<?php
/** SSLCommerz failure callback for loan repayments — installment stays unpaid. */
require_once __DIR__ . '/../includes/helpers.php';

redirect('pages/repayment_ledger.php?error=failed');
