<?php
// Adds the product columns the store needs (runs once). Needs db.php loaded first.
if (!file_exists(__DIR__ . '/.products_schema_done')) {
    ensureCol($pdo, PRODUCTS_TABLE, 'price', 'DECIMAL(10,2) NOT NULL DEFAULT 0');
    ensureCol($pdo, PRODUCTS_TABLE, 'image', "VARCHAR(255) NOT NULL DEFAULT ''");
    ensureCol($pdo, PRODUCTS_TABLE, 'category', "VARCHAR(50) NOT NULL DEFAULT 'Other'");
    ensureCol($pdo, PRODUCTS_TABLE, 'sizes', "VARCHAR(100) NOT NULL DEFAULT 'Small,Medium,Large'");
    @file_put_contents(__DIR__ . '/.products_schema_done', '1');
}
