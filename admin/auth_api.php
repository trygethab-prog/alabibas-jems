<?php
// Customer register / login / logout. Sets the session that chat_api.php (and config.php) read.
require __DIR__ . '/db.php';
$pdo->exec("CREATE TABLE IF NOT EXISTS customers(
    id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, email VARCHAR(150) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
$a = $_GET['action'] ?? ''; $in = body();

function startCustomer($id, $name, $email) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = (string)$id; $_SESSION['username'] = $name; $_SESSION['user_email'] = $email;
    jsonOut(['name' => $name, 'email' => $email]);
}

if ($a === 'me') {
    !empty($_SESSION['user_id']) ? jsonOut(['name' => $_SESSION['username'], 'email' => $_SESSION['user_email'] ?? '']) : jsonOut(['error' => 'Not logged in'], 401);
}
if ($a === 'register') {
    $name = mb_substr(trim($in['name'] ?? ''), 0, 100); $email = strtolower(trim($in['email'] ?? '')); $pw = (string)($in['password'] ?? '');
    if ($name === '') jsonOut(['error' => 'Please enter your name.'], 400);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonOut(['error' => 'Please enter a valid email address.'], 400);
    if (strlen($pw) < 6) jsonOut(['error' => 'Your password must be at least 6 characters.'], 400);
    $q = $pdo->prepare('SELECT id FROM customers WHERE email=?'); $q->execute([$email]);
    if ($q->fetch()) jsonOut(['error' => 'An account with this email already exists. Please log in instead.'], 409);
    $pdo->prepare('INSERT INTO customers(name,email,password_hash) VALUES(?,?,?)')->execute([$name, $email, password_hash($pw, PASSWORD_DEFAULT)]);
    startCustomer($pdo->lastInsertId(), $name, $email);
}
if ($a === 'login') {
    $email = strtolower(trim($in['email'] ?? '')); $pw = (string)($in['password'] ?? '');
    $q = $pdo->prepare('SELECT * FROM customers WHERE email=?'); $q->execute([$email]); $u = $q->fetch();
    if (!$u || !password_verify($pw, $u['password_hash'])) jsonOut(['error' => 'Incorrect email or password.'], 401);
    startCustomer($u['id'], $u['name'], $u['email']);
}
if ($a === 'logout') {
    unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['user_email']);
    jsonOut(['ok' => true]);
}
jsonOut(['error' => 'Unknown action'], 400);
