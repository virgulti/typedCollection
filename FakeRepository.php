<?php

/**
 * FakeRepository.php — Sorgente dati condivisa
 *
 * Simula la sorgente dati (es. righe lette da un DB) restituendo array grezzi.
 * Usato sia da main.php (che lavora direttamente sugli array)
 * sia da main-refactored.php (che idrata i dati in entità e TypedCollection).
 */

class FakeRepository
{
    public static function getUsers(): array
    {
        return [
            ['id' => 1, 'name' => 'Alice',   'email' => 'alice@example.com',   'role' => 'admin'],
            ['id' => 2, 'name' => 'Bob',     'email' => 'bob@example.com',     'role' => 'customer'],
            ['id' => 3, 'name' => 'Carol',   'email' => 'carol@example.com',   'role' => 'customer'],
            ['id' => 4, 'name' => 'Dave',    'email' => 'dave@example.com',    'role' => 'customer'],
        ];
    }

    public static function getProducts(): array
    {
        return [
            ['id' => 1, 'name' => 'Laptop Pro',    'price' => 1299.99, 'status' => 'available',    'owner_id' => 1],
            ['id' => 2, 'name' => 'Wireless Mouse', 'price' => 49.99,   'status' => 'available',    'owner_id' => 1],
            ['id' => 3, 'name' => 'Mechanical Keyboard','price' => 129.99,'status'=> 'out_of_stock','owner_id' => 2],
            ['id' => 4, 'name' => '4K Monitor',    'price' => 599.99,  'status' => 'available',    'owner_id' => 2],
            ['id' => 5, 'name' => 'USB-C Hub',     'price' => 39.99,   'status' => 'discontinued', 'owner_id' => 1],
            ['id' => 6, 'name' => 'Webcam HD',     'price' => 89.99,   'status' => 'available',    'owner_id' => 3],
        ];
    }

    public static function getOrders(): array
    {
        return [
            ['id' => 1, 'user_id' => 2, 'product_id' => 1, 'quantity' => 1, 'status' => 'delivered'],
            ['id' => 2, 'user_id' => 2, 'product_id' => 2, 'quantity' => 2, 'status' => 'shipped'],
            ['id' => 3, 'user_id' => 3, 'product_id' => 4, 'quantity' => 1, 'status' => 'pending'],
            ['id' => 4, 'user_id' => 3, 'product_id' => 2, 'quantity' => 3, 'status' => 'confirmed'],
            ['id' => 5, 'user_id' => 4, 'product_id' => 6, 'quantity' => 1, 'status' => 'pending'],
        ];
    }
}
