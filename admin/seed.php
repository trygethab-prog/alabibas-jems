<?php
// ONE-TIME helper: adds your 8 starting products. Log in as admin, open /admin/seed.php once.
require __DIR__ . '/db.php';
require __DIR__ . '/products_schema.php';
if (empty($_SESSION['admin'])) { header('Location: login.php'); exit; }
header('Content-Type: text/plain; charset=utf-8');
$items = [
  ['White T-shirt', 'Tops', 499, 'images/top1.webp'], ['Black T-shirt', 'Tops', 499, 'images/top2.webp'],
  ['Crop shirt', 'Tops', 449, 'images/top3.webp'],    ['Polo shirt', 'Tops', 599, 'images/top4.webp'],
  ['Wide-leg pants', 'Bottoms', 899, 'images/bottom1.webp'], ['Jogger pants', 'Bottoms', 799, 'images/bottom2.webp'],
  ['Cargo pants', 'Bottoms', 999, 'images/bottom3.webp'],     ['Trousers', 'Bottoms', 899, 'images/bottom4.webp'],
];
$T = '`' . PRODUCTS_TABLE . '`'; $NM = '`' . COL_NAME . '`'; $added = 0;
foreach ($items as $i) {
    $q = $pdo->prepare("SELECT COUNT(*) FROM $T WHERE $NM=?"); $q->execute([$i[0]]);
    if ($q->fetchColumn()) { echo "Skipped (already exists): $i[0]\n"; continue; }
    $pdo->prepare("INSERT INTO $T ($NM, category, price, image, sizes, stock_type, quantity) VALUES (?,?,?,?, 'Small,Medium,Large', 'limited', 20)")->execute($i);
    echo "Added: $i[0]\n"; $added++;
}
echo "\nDone. $added product(s) added with 20 in stock each. Change stock in /admin/ (Inventory tab).\n";
