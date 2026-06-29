<?php

/**
 * main-refactored.php — Codice riscritto con TypedCollection
 *
 * Stesso dominio di main.php (utenti, prodotti, ordini) ma con:
 * - Classi entità al posto di array grezzi
 * - TypedCollection per type-safety a runtime
 * - Nessuna validazione manuale ripetuta nelle funzioni
 * - Firme di funzione che documentano il tipo atteso
 *
 * I dati grezzi arrivano da FakeRepository (condiviso con main.php)
 * e vengono idratati in entità + TypedCollection al confine del dominio.
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/FakeRepository.php';

use Virgulti\TypedCollection\BaseCollection;

// =============================================================================
// ENTITÀ
// =============================================================================

class User
{
    public function __construct(
        public readonly int    $id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $role,  // admin | customer
    ) {}
}

class Product
{
    public function __construct(
        public readonly int    $id,
        public readonly string $name,
        public readonly float  $price,
        public string          $status,  // available | out_of_stock | discontinued
        public readonly int    $ownerId,
    ) {
        if (!in_array($status, ['available', 'out_of_stock', 'discontinued'], true)) {
            throw new \InvalidArgumentException("Stato prodotto non valido: $status.");
        }
        if ($price < 0) {
            throw new \InvalidArgumentException("Il prezzo non può essere negativo.");
        }
    }
}

class Order
{
    public function __construct(
        public readonly int    $id,
        public readonly int    $userId,
        public readonly int    $productId,
        public readonly int    $quantity,
        public readonly string $status,  // pending | confirmed | shipped | delivered
    ) {
        if ($quantity < 1) {
            throw new \InvalidArgumentException("La quantità deve essere almeno 1.");
        }
    }
}

// =============================================================================
// TYPED COLLECTIONS
// =============================================================================

class UserCollection extends BaseCollection
{
    protected function getType(): string { return User::class; }
}

class ProductCollection extends BaseCollection
{
    protected function getType(): string { return Product::class; }
}

class OrderCollection extends BaseCollection
{
    protected function getType(): string { return Order::class; }
}

// =============================================================================
// IDRATAZIONE — dai dati grezzi di FakeRepository a entità + TypedCollection
// =============================================================================

function getUserRepository(): UserCollection
{
    return UserCollection::fromArray(array_map(
        fn(array $u) => new User($u['id'], $u['name'], $u['email'], $u['role']),
        FakeRepository::getUsers()
    ));
}

function getProductRepository(): ProductCollection
{
    return ProductCollection::fromArray(array_map(
        fn(array $p) => new Product($p['id'], $p['name'], $p['price'], $p['status'], $p['owner_id']),
        FakeRepository::getProducts()
    ));
}

function getOrderRepository(): OrderCollection
{
    return OrderCollection::fromArray(array_map(
        fn(array $o) => new Order($o['id'], $o['user_id'], $o['product_id'], $o['quantity'], $o['status']),
        FakeRepository::getOrders()
    ));
}

// =============================================================================
// OPERAZIONI — firme tipizzate, nessuna validazione manuale
// =============================================================================

function getProductsByStatus(ProductCollection $products, string $status): ProductCollection
{
    return $products->filter(fn(Product $p) => $p->status === $status);
}

function changeProductStatus(ProductCollection $products, int $productId, string $newStatus): void
{
    if (!in_array($newStatus, ['available', 'out_of_stock', 'discontinued'], true)) {
        throw new \InvalidArgumentException("Stato non valido: $newStatus.");
    }

    $index = $products->findBy('id', $productId);
    if ($index === false) {
        throw new \RuntimeException("Prodotto con id $productId non trovato.");
    }

    $products[$index]->status = $newStatus;
}

function getProductsByOwner(ProductCollection $products, int $userId): ProductCollection
{
    return $products->filter(fn(Product $p) => $p->ownerId === $userId);
}

function getOrdersByUser(OrderCollection $orders, int $userId): OrderCollection
{
    return $orders->filter(fn(Order $o) => $o->userId === $userId);
}

function calculateOrderTotal(OrderCollection $orders, ProductCollection $products): float
{
    return $orders->reduce(function (float $total, Order $order) use ($products): float {
        $index = $products->findBy('id', $order->productId);
        if ($index === false) return $total;
        return $total + $products[$index]->price * $order->quantity;
    }, 0.0);
}

function getAvailableProductsSortedByPrice(ProductCollection $products): ProductCollection
{
    return $products
        ->filter(fn(Product $p) => $p->status === 'available')
        ->sort(fn(Product $a, Product $b) => $a->price <=> $b->price);
}

function getPendingOrdersWithUserName(OrderCollection $orders, UserCollection $users): array
{
    return $orders
        ->filter(fn(Order $o) => $o->status === 'pending')
        ->map(function (Order $order) use ($users): array {
            $index = $users->findBy('id', $order->userId);
            $name  = $index !== false ? $users[$index]->name : 'Sconosciuto';
            return ['order_id' => $order->id, 'user_name' => $name, 'status' => $order->status];
        });
}

// =============================================================================
// ESECUZIONE — identica a main.php, output identico
// =============================================================================

$users    = getUserRepository();
$products = getProductRepository();
$orders   = getOrderRepository();

echo "=== Prodotti disponibili ===\n";
foreach (getProductsByStatus($products, 'available') as $p) {
    echo "  - {$p->name} ({$p->price}€)\n";
}

echo "\n=== Cambio stato: Laptop Pro -> out_of_stock ===\n";
changeProductStatus($products, 1, 'out_of_stock');
$available = getProductsByStatus($products, 'available');
echo "  Prodotti ancora disponibili: " . count($available) . "\n";

echo "\n=== Prodotti gestiti da Alice (id=1) ===\n";
foreach (getProductsByOwner($products, 1) as $p) {
    echo "  - {$p->name} [{$p->status}]\n";
}

echo "\n=== Ordini di Bob (id=2) ===\n";
$bobOrders = getOrdersByUser($orders, 2);
foreach ($bobOrders as $o) {
    echo "  - Ordine #{$o->id} | stato: {$o->status}\n";
}

echo "\n=== Totale speso da Bob ===\n";
echo "  " . calculateOrderTotal($bobOrders, $products) . "€\n";

echo "\n=== Prodotti disponibili per prezzo crescente ===\n";
foreach (getAvailableProductsSortedByPrice($products) as $p) {
    echo "  - {$p->name}: {$p->price}€\n";
}

echo "\n=== Ordini in attesa con nome utente ===\n";
foreach (getPendingOrdersWithUserName($orders, $users) as $row) {
    echo "  - Ordine #{$row['order_id']} — {$row['user_name']} [{$row['status']}]\n";
}
