<?php
// Change the admin password. Only works while logged in as admin.
require __DIR__ . '/db.php';
if (empty($_SESSION['admin'])) { header('Location: login.php'); exit; }
$msg = ''; $ok = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cur = (string)($_POST['cur'] ?? ''); $new = (string)($_POST['new'] ?? ''); $new2 = (string)($_POST['new2'] ?? '');
    $q = $pdo->prepare('SELECT * FROM admin_users WHERE username=?'); $q->execute([$_SESSION['admin']]); $u = $q->fetch();
    if (!$u || !password_verify($cur, $u['password_hash'])) $msg = 'Your current password is wrong.';
    elseif (strlen($new) < 8) $msg = 'The new password must be at least 8 characters.';
    elseif ($new !== $new2) $msg = 'The new passwords do not match.';
    elseif ($new === $cur) $msg = 'The new password must be different from the current one.';
    else {
        $pdo->prepare('UPDATE admin_users SET password_hash=? WHERE id=?')->execute([password_hash($new, PASSWORD_DEFAULT), $u['id']]);
        $ok = true; $msg = 'Password changed. Use it the next time you log in.';
    }
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Change password | ALABIBAS admin</title>
<style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f4f4f2;font:15px system-ui}
form{background:#fff;padding:28px;border:1px solid #ddd;width:320px;display:grid;gap:12px}h1{margin:0 0 4px;font-size:20px}
input,button{padding:10px;font:inherit;border:1px solid #ccc}button{background:#111;color:#fff;border-color:#111;cursor:pointer}
p{margin:0;min-height:1em;color:#b00020}p.ok{color:#1b6e2d}a{color:#111;font-size:14px}</style></head>
<body><form method="post"><h1>Change admin password</h1>
<input name="cur" type="password" placeholder="Current password" required autofocus>
<input name="new" type="password" placeholder="New password (8+ characters)" required>
<input name="new2" type="password" placeholder="Repeat new password" required>
<p class="<?= $ok ? 'ok' : '' ?>"><?= htmlspecialchars($msg) ?></p>
<button>Change password</button><a href="index.php">&larr; Back to admin</a></form></body></html>
