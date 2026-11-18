<?php
require __DIR__ . '/db.php';
require __DIR__ . '/products_schema.php';
if (empty($_SESSION['admin'])) jsonOut(['error' => 'Admin login required'], 401);
$a = $_GET['action'] ?? ''; $in = body();
$T = '`' . PRODUCTS_TABLE . '`'; $ID = '`' . COL_ID . '`'; $NM = '`' . COL_NAME . '`';
try {
switch ($a) {
case 'products':
    jsonOut($pdo->query("SELECT $ID id, $NM name, price, image, category, sizes, stock_type, quantity, sold_count FROM $T ORDER BY $ID DESC")->fetchAll());
case 'add_product':
    if (!trim($in['name'] ?? '')) jsonOut(['error' => 'Name is required'], 400);
    $pdo->prepare("INSERT INTO $T ($NM, category, price, image, sizes, stock_type, quantity) VALUES (?,?,?,?,?,?,?)")->execute([
        trim($in['name']), trim($in['category'] ?? '') ?: 'Other', max(0, (float)($in['price'] ?? 0)), trim($in['image'] ?? ''),
        trim($in['sizes'] ?? '') ?: 'Small,Medium,Large', ($in['stock_type'] ?? '') === 'unlimited' ? 'unlimited' : 'limited', max(0, (int)($in['quantity'] ?? 0))]);
    jsonOut(['ok' => true]);
case 'update_product':
    $set = []; $val = [];
    foreach (['name' => COL_NAME, 'category' => 'category', 'image' => 'image', 'sizes' => 'sizes'] as $k => $col)
        if (isset($in[$k])) { $set[] = "`$col`=?"; $val[] = trim($in[$k]); }
    if (isset($in['price'])) { $set[] = 'price=?'; $val[] = max(0, (float)$in['price']); }
    if (isset($in['stock_type'])) { $set[] = 'stock_type=?'; $val[] = $in['stock_type'] === 'unlimited' ? 'unlimited' : 'limited'; }
    if (isset($in['quantity'])) { $set[] = 'quantity=?'; $val[] = max(0, (int)$in['quantity']); }
    if ($set) { $val[] = (int)$in['id']; $pdo->prepare("UPDATE $T SET " . implode(',', $set) . " WHERE $ID=?")->execute($val); }
    jsonOut(['ok' => true]);
case 'delete_product':
    $pdo->prepare("DELETE FROM $T WHERE $ID=?")->execute([(int)$in['id']]);
    jsonOut(['ok' => true]);
case 'sold':   // atomic: can never oversell
    $q = max(1, (int)($in['qty'] ?? 1));
    $s = $pdo->prepare("UPDATE $T SET sold_count=sold_count+:a, quantity=IF(stock_type='unlimited',quantity,quantity-:b)
                        WHERE $ID=:id AND (stock_type='unlimited' OR quantity>=:c)");
    $s->execute([':a' => $q, ':b' => $q, ':c' => $q, ':id' => (int)$in['id']]);
    $s->rowCount() ? jsonOut(['ok' => true]) : jsonOut(['error' => 'Not enough stock'], 400);
case 'conversations':
    jsonOut($pdo->query("SELECT customer_id, MAX(customer_name) name, MAX(created_at) last_at,
        SUM(sender='customer' AND is_read=0) unread,
        (SELECT text FROM chat_messages m2 WHERE m2.customer_id=m.customer_id ORDER BY id DESC LIMIT 1) last_text
        FROM chat_messages m GROUP BY customer_id ORDER BY last_at DESC")->fetchAll());
case 'messages':
    $cid = (string)($_GET['cid'] ?? ''); $after = (int)($_GET['after'] ?? 0);
    $q = $pdo->prepare('SELECT id, sender, text FROM chat_messages WHERE customer_id=? AND id>? ORDER BY id'); $q->execute([$cid, $after]);
    $pdo->prepare("UPDATE chat_messages SET is_read=1 WHERE customer_id=? AND sender='customer'")->execute([$cid]);
    jsonOut($q->fetchAll());
case 'reply':
    $t = cleanText($in['text'] ?? ''); if ($t === '') jsonOut(['error' => 'Empty message'], 400);
    $pdo->prepare("INSERT INTO chat_messages(customer_id,sender,text) VALUES(?, 'admin', ?)")->execute([(string)$in['cid'], $t]);
    jsonOut(['ok' => true]);
}
jsonOut(['error' => 'Unknown action'], 400);
} catch (Throwable $e) { jsonOut(['error' => 'Database error: ' . $e->getMessage()], 500); }
