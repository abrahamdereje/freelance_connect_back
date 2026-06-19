<?php

namespace App\Repositories\Contracts;

use App\Models\Wallet;
use App\Models\Transaction;

interface WalletRepositoryInterface
{
    public function findByUserId(int $userId): ?Wallet;
    public function createWallet(int $userId): Wallet;
    public function updateBalance(Wallet $wallet, float $amount): bool;
    public function createTransaction(array $data): Transaction;
    public function createEscrow(array $data);
    public function getTransactionHistory(int $walletId);
}
