<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$c = App\Models\Customer::find(5);
echo "Customer 5 (Seller): {$c->name} ({$c->type})\n";
echo "Trx: {$c->totalTransactions()} | Items: {$c->totalItemsBought()} | Spent: {$c->totalSpent()} | Profit: {$c->totalProfit()} | Unpaid: {$c->unpaidReceivables()}\n\n";

$s = App\Models\Supplier::find(1);
echo "Supplier 1: {$s->name}\n";
echo "Trx: {$s->totalTransactions()} | Items: {$s->totalItemsSupplied()} | Purchases: {$s->totalPurchases()} | Unpaid: {$s->unpaidPayables()}\n";
