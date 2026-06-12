<?php

return [
    'db' => [
        // 127.0.0.1 (not "localhost") forces a real TCP connection to the
        // configured port. "localhost" can make the MySQL client use a
        // socket/named pipe and silently ignore the port, which breaks the
        // automatic port detection in db.php.
        'host' => getenv('KRISHI_DB_HOST') ?: '127.0.0.1',
        // db.php auto-detects the port anyway (it also tries 3306/3307), so
        // this is just the first port tried. 3307 matches this machine's XAMPP.
        'port' => getenv('KRISHI_DB_PORT') ?: '3307',
        'name' => getenv('KRISHI_DB_NAME') ?: 'krishiconnect_db',
        'user' => getenv('KRISHI_DB_USER') ?: 'root',
        'pass' => getenv('KRISHI_DB_PASS') ?: '',
        'charset' => getenv('KRISHI_DB_CHARSET') ?: 'utf8mb4',
    ],
    'base_url' => getenv('KRISHI_BASE_URL') !== false ? getenv('KRISHI_BASE_URL') : null,

    // SSLCommerz payment gateway (sandbox by default).
   
    'sslcommerz' => [
        'store_id'   => getenv('SSLCZ_STORE_ID') ?: 'krish6a2c3cf073587',   
        'store_pass' => getenv('SSLCZ_STORE_PASS') ?: 'krish6a2c3cf073587@ssl', 
        // true = sandbox (test) endpoints, false = live endpoints.
        'sandbox'    => getenv('SSLCZ_SANDBOX') !== false ? getenv('SSLCZ_SANDBOX') !== '0' : true,
    ],
];
