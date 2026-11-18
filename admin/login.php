<?php
require __DIR__ . '/db.php';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $q = $pdo->prepare('SELECT * FROM admin_users WHERE username=?');
    $q->execute([$_POST['user'] ?? '']);
    $u = $q->fetch();
    if ($u && password_verify($_POST['pass'] ?? '', $u['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin'] = $u['username'];
        header('Location: index.php'); exit;
    }
    $err = 'Wrong username or password';
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin login | ALABIBAS</title>
<style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f4f4f2;font:15px system-ui}
form{background:#fff;padding:28px;border:1px solid #ddd;width:300px;display:grid;gap:12px}h1{margin:0 0 4px;font-size:20px}
input,button{padding:10px;font:inherit;border:1px solid #ccc}button{background:#111;color:#fff;border-color:#111;cursor:pointer}p{color:#b00020;min-height:1em;margin:0}</style></head>
<body><form method="post"><h1>ALABIBAS admin</h1><input name="user" placeholder="Username" required autofocus><input name="pass" type="password" placeholder="Password" required><p><?= htmlspecialchars($err) ?></p><button>Log in</button></form></body></html>
