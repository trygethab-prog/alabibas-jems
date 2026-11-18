<?php
// Public: the product list the store page (products.html) shows. Products with price 0 are hidden.
require __DIR__ . '/db.php';
require __DIR__ . '/products_schema.php';
try {
    $rows = $pdo->query('SELECT `' . COL_ID . '` id, `' . COL_NAME . '` name, price, image, category, sizes, stock_type, quantity
                         FROM `' . PRODUCTS_TABLE . '` WHERE price > 0 ORDER BY category, id')->fetchAll();
    $out = array_map(function ($r) {
        return ['id' => (int)$r['id'], 'name' => $r['name'], 'price' => (float)$r['price'], 'image' => $r['image'],
                'category' => $r['category'],
                'sizes' => array_values(array_filter(array_map('trim', explode(',', $r['sizes'])))),
                'in_stock' => $r['stock_type'] === 'unlimited' || (int)$r['quantity'] > 0];
    }, $rows);
    header('Cache-Control: no-store');
    jsonOut($out);
} catch (Throwable $e) { jsonOut(['error' => 'Could not load products'], 500); }
