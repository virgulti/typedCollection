<?php

/**
 * main.php — Codice legacy con array grezzi
 *
 * Simula un backend con utenti, prodotti e ordini.
 * Ogni funzione riceve array come parametri e deve validarli manualmente.
 * Questo è il pattern "prima": fragile, verboso, senza garanzie di tipo.
 *
 * I dati grezzi arrivano da FakeRepository, condiviso con main-refactored.php.
 */

require_once __DIR__ . '/FakeRepository.php';

// =============================================================================
// VALIDAZIONE — ripetuta in ogni funzione che riceve array
// =============================================================================

function validateProduct(array $product): void
{
    $required = ['id', 'name', 'price', 'status', 'owner_id'];
    foreach ($required as $field) {
        if (!array_key_exists($field, $product)) {
            throw new \InvalidArgumentException("Prodotto non valido: campo '$field' mancante.");
        }
    }
    if (!is_numeric($product['price']) || $product['price'] < 0) {
        throw new \InvalidArgumentException("Prezzo non valido per il prodotto {$product['id']}.");
    }
    $validStatuses = ['available', 'out_of_stock', 'discontinued'];
    if (!in_array($product['status'], $validStatuses, true)) {
        throw new \InvalidArgumentException("Stato prodotto non valido: {$product['status']}.");
    }
}

function validateOrder(array $order): void
{
    $required = ['id', 'user_id', 'product_id', 'quantity', 'status'];
    foreach ($required as $field) {
        if (!array_key_exists($field, $order)) {
            throw new \InvalidArgumentException("Ordine non valido: campo '$field' mancante.");
        }
    }
    if (!is_int($order['quantity']) || $order['quantity'] < 1) {
        throw new \InvalidArgumentException("Quantità non valida nell'ordine {$order['id']}.");
    }
}

// =============================================================================
// OPERAZIONI — ogni funzione valida l'array in ingresso da zero
// =============================================================================

function getProductsByStatus(array $products, string $status): array
{
    foreach ($products as $product) {
        if (!is_array($product)) {
            throw new \InvalidArgumentException("Ogni elemento di products deve essere un array.");
        }
        validateProduct($product);
    }

    return array_values(
        array_filter($products, fn($p) => $p['status'] === $status)
    );
}

function changeProductStatus(array &$products, int $productId, string $newStatus): void
{
    $validStatuses = ['available', 'out_of_stock', 'discontinued'];
    if (!in_array($newStatus, $validStatuses, true)) {
        throw new \InvalidArgumentException("Stato non valido: $newStatus.");
    }

    foreach ($products as &$product) {
        if (!is_array($product)) {
            throw new \InvalidArgumentException("Ogni elemento di products deve essere un array.");
        }
        // Senza questo guard, un prodotto senza 'id' genererebbe un warning/errore
        if (!array_key_exists('id', $product)) {
            throw new \InvalidArgumentException("Prodotto non valido: campo 'id' mancante.");
        }
        if ($product['id'] === $productId) {
            $product['status'] = $newStatus;
            return;
        }
    }

    throw new \RuntimeException("Prodotto con id $productId non trovato.");
}

function getProductsByOwner(array $products, int $userId): array
{
    foreach ($products as $product) {
        if (!is_array($product)) {
            throw new \InvalidArgumentException("Ogni elemento di products deve essere un array.");
        }
        validateProduct($product);
    }

    return array_values(
        array_filter($products, fn($p) => $p['owner_id'] === $userId)
    );
}

function getOrdersByUser(array $orders, int $userId): array
{
    foreach ($orders as $order) {
        if (!is_array($order)) {
            throw new \InvalidArgumentException("Ogni elemento di orders deve essere un array.");
        }
        validateOrder($order);
    }

    return array_values(
        array_filter($orders, fn($o) => $o['user_id'] === $userId)
    );
}

function calculateOrderTotal(array $orders, array $products): float
{
    foreach ($orders as $order) {
        if (!is_array($order)) {
            throw new \InvalidArgumentException("Ogni elemento di orders deve essere un array.");
        }
        if (!array_key_exists('product_id', $order) || !array_key_exists('quantity', $order)) {
            throw new \InvalidArgumentException("Ordine non valido: campo 'product_id' o 'quantity' mancante.");
        }
    }
    foreach ($products as $product) {
        if (!is_array($product)) {
            throw new \InvalidArgumentException("Ogni elemento di products deve essere un array.");
        }
        if (!array_key_exists('id', $product) || !array_key_exists('price', $product)) {
            throw new \InvalidArgumentException("Prodotto non valido: campo 'id' o 'price' mancante.");
        }
    }

    $total = 0.0;
    foreach ($orders as $order) {
        foreach ($products as $product) {
            if ($product['id'] === $order['product_id']) {
                $total += $product['price'] * $order['quantity'];
                break;
            }
        }
    }
    return $total;
}

function getAvailableProductsSortedByPrice(array $products): array
{
    foreach ($products as $product) {
        if (!is_array($product)) {
            throw new \InvalidArgumentException("Ogni elemento di products deve essere un array.");
        }
        validateProduct($product);
    }

    $available = array_filter($products, fn($p) => $p['status'] === 'available');
    usort($available, fn($a, $b) => $a['price'] <=> $b['price']);
    return array_values($available);
}

function getPendingOrdersWithUserName(array $orders, array $users): array
{
    foreach ($orders as $order) {
        if (!is_array($order)) {
            throw new \InvalidArgumentException("Ogni elemento di orders deve essere un array.");
        }
        if (!array_key_exists('id', $order) || !array_key_exists('user_id', $order) || !array_key_exists('status', $order)) {
            throw new \InvalidArgumentException("Ordine non valido: campo 'id', 'user_id' o 'status' mancante.");
        }
    }
    foreach ($users as $user) {
        if (!is_array($user)) {
            throw new \InvalidArgumentException("Ogni elemento di users deve essere un array.");
        }
        if (!array_key_exists('id', $user) || !array_key_exists('name', $user)) {
            throw new \InvalidArgumentException("Utente non valido: campo 'id' o 'name' mancante.");
        }
    }

    $pending = array_filter($orders, fn($o) => $o['status'] === 'pending');
    $result  = [];

    foreach ($pending as $order) {
        $userName = null;
        foreach ($users as $user) {
            if ($user['id'] === $order['user_id']) {
                $userName = $user['name'];
                break;
            }
        }
        $result[] = [
            'order_id'  => $order['id'],
            'user_name' => $userName ?? 'Sconosciuto',
            'status'    => $order['status'],
        ];
    }

    return $result;
}

// =============================================================================
// ESECUZIONE
// =============================================================================

$users    = FakeRepository::getUsers();
$products = FakeRepository::getProducts();
$orders   = FakeRepository::getOrders();

echo "=== Prodotti disponibili ===\n";
foreach (getProductsByStatus($products, 'available') as $p) {
    echo "  - {$p['name']} ({$p['price']}€)\n";
}

echo "\n=== Cambio stato: Laptop Pro -> out_of_stock ===\n";
changeProductStatus($products, 1, 'out_of_stock');
$available = getProductsByStatus($products, 'available');
echo "  Prodotti ancora disponibili: " . count($available) . "\n";

echo "\n=== Prodotti gestiti da Alice (id=1) ===\n";
foreach (getProductsByOwner($products, 1) as $p) {
    echo "  - {$p['name']} [{$p['status']}]\n";
}

echo "\n=== Ordini di Bob (id=2) ===\n";
$bobOrders = getOrdersByUser($orders, 2);
foreach ($bobOrders as $o) {
    echo "  - Ordine #{$o['id']} | stato: {$o['status']}\n";
}

echo "\n=== Totale speso da Bob ===\n";
echo "  " . calculateOrderTotal($bobOrders, $products) . "€\n";

echo "\n=== Prodotti disponibili per prezzo crescente ===\n";
foreach (getAvailableProductsSortedByPrice($products) as $p) {
    echo "  - {$p['name']}: {$p['price']}€\n";
}

echo "\n=== Ordini in attesa con nome utente ===\n";
foreach (getPendingOrdersWithUserName($orders, $users) as $row) {
    echo "  - Ordine #{$row['order_id']} — {$row['user_name']} [{$row['status']}]\n";
}
