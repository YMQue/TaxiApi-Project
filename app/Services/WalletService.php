<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Admin;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function getWallet(int $userId, string $userType): Wallet
    {
        return Wallet::firstOrCreate(
            ['user_id' => $userId, 'user_type' => $userType],
            ['balance' => 0, 'currency' => 'SAR']
        );
    }

 

   

    public function getBalance(int $userId, string $userType): array
    {
        $wallet = $this->getWallet($userId, $userType);

        return [
            'balance'  => $wallet->balance,
            'currency' => $wallet->currency,
        ];
    }

    public function credit(
        int $userId,
        float $amount,
        string $userType,
        ?string $description = null,
        ?Model $reference = null,
        ?string $category = null
    ): Wallet {
        return $this->applyTransaction($userId, $amount, 'credit', $userType, $description, $reference, $category);
    }

    public function debit(
        int $userId,
        float $amount,
        string $userType,
        ?string $description = null,
        ?Model $reference = null,
        ?string $category = null,
        bool $allowNegative = false
    ): Wallet {
        return $this->applyTransaction($userId, $amount, 'debit', $userType, $description, $reference, $category, $allowNegative);
    }

    /** Credit the super admin wallet (commissions, cancellation fees...). */
    public function creditSystem(
        float $amount,
        ?string $description = null,
        ?Model $reference = null,
        string $category = 'commission'
    ): Wallet {
        return $this->credit($this->superAdminId(), $amount, 'admin', $description, $reference, $category);
    }

    /** Debit the super admin wallet (promo discounts the platform absorbs). */
    public function debitSystem(
        float $amount,
        ?string $description = null,
        ?Model $reference = null,
        string $category = 'promo_discount'
    ): Wallet {
        return $this->debit($this->superAdminId(), $amount, 'admin', $description, $reference, $category, allowNegative: true);
    }

    public function getTransactions(int $userId, string $userType)
    {
        $wallet = $this->getWallet($userId, $userType);

        return WalletTransaction::where('wallet_id', $wallet->id)
            ->latest('id')
            ->paginate(15);
    }

    private function superAdminId(): int
    {
        $id = Admin::where('role', 'super')->value('id');

        if (!$id) {
            throw new BusinessRuleException('Super admin not found.');
        }

        return $id;
    }

    private function applyTransaction(
        int $userId,
        float $amount,
        string $type,
        string $userType,
        ?string $description,
        ?Model $reference = null,
        ?string $category = null,
        bool $allowNegative = false
    ): Wallet {
        if ($amount <= 0) {
            throw new BusinessRuleException('Amount must be greater than zero.');
        }

        $this->getWallet($userId, $userType);

        return DB::transaction(function () use ($userId, $amount, $type, $userType, $description, $reference, $category, $allowNegative) {
            $wallet = Wallet::where('user_id', $userId)
                ->where('user_type', $userType)
                ->lockForUpdate()
                ->first();

            if ($type === 'debit' && !$allowNegative && $wallet->balance < $amount) {
                throw new BusinessRuleException('Insufficient wallet balance.');
            }

            $wallet->balance = $type === 'credit'
                ? $wallet->balance + $amount
                : $wallet->balance - $amount;
            $wallet->save();

            WalletTransaction::create([
                'wallet_id'      => $wallet->id,
                'type'           => $type,
                'category'       => $category,
                'amount'         => $amount,
                'balance_after'  => $wallet->balance,
                'description'    => $description,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id'   => $reference?->getKey(),
            ]);

            return $wallet;
        });
    }
}
