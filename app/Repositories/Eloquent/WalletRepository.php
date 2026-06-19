<?php

namespace App\Repositories\Eloquent;

use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\Escrow;
use App\Repositories\Contracts\WalletRepositoryInterface;

class WalletRepository implements WalletRepositoryInterface
{
    public function findByUserId(int $userId): ?Wallet
    {
        return Wallet::where('user_id', $userId)->first();
    }

    public function createWallet(int $userId): Wallet
    {
        return Wallet::create([
            'user_id' => $userId,
            'balance' => 0.00,
        ]);
    }

    public function updateBalance(Wallet $wallet, float $amount): bool
    {
        $wallet->balance += $amount;
        return $wallet->save();
    }

    public function createTransaction(array $data): Transaction
    {
        return Transaction::create($data);
    }

    public function createEscrow(array $data): Escrow
    {
        return Escrow::create($data);
    }

    public function getTransactionHistory(int $walletId)
    {
        return Transaction::where('wallet_id', $walletId)
            ->latest()
            ->get();
    }
}
