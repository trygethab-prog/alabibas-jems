<?php
// ===== EDIT THIS FILE =====
// XAMPP defaults are filled in. On Railway, the MYSQL* variables are read automatically.
define('DB_HOST', getenv('MYSQLHOST')     ?: 'localhost');
define('DB_PORT', getenv('MYSQLPORT')     ?: '3306');
define('DB_USER', getenv('MYSQLUSER')     ?: 'root');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: '');
define('DB_NAME', getenv('MYSQLDATABASE') ?: getenv('MYSQL_DATABASE') ?: 'alabibas');   // <-- your existing database name in phpMyAdmin

// Your existing products table. If yours differs, change these 3 lines.
define('PRODUCTS_TABLE', 'products');
define('COL_ID',   'id');
define('COL_NAME', 'name');

// Who is the logged-in customer on YOUR user site? Change the two $_SESSION keys
// to match what your login.php stores. Return null if nobody is logged in.
function currentCustomer() {
    if (!empty($_SESSION['user_id'])) {
        return ['id' => (string)$_SESSION['user_id'], 'name' => $_SESSION['username'] ?? 'Customer'];
    }
    return null;
}
