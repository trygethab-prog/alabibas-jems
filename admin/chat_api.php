<?php
// Customer side. A customer can only ever read/write THEIR OWN conversation.
require __DIR__ . '/db.php';
$me = currentCustomer();
if (!$me) jsonOut(['error' => 'Please log in to chat'], 401);
$a = $_GET['action'] ?? '';
if ($a === 'messages') {
    $q = $pdo->prepare('SELECT id, sender, text FROM chat_messages WHERE customer_id=? AND id>? ORDER BY id');
    $q->execute([$me['id'], (int)($_GET['after'] ?? 0)]);
    jsonOut($q->fetchAll());
}
if ($a === 'send') {
    $t = cleanText(body()['text'] ?? ''); if ($t === '') jsonOut(['error' => 'Empty message'], 400);
    $pdo->prepare("INSERT INTO chat_messages(customer_id,customer_name,sender,text) VALUES(?,?, 'customer', ?)")->execute([$me['id'], $me['name'], $t]);
    jsonOut(['ok' => true]);
}
jsonOut(['error' => 'Unknown action'], 400);
