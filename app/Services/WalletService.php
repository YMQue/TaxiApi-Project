<?php
namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Wallet;
use App\Models\WalletTranactions;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WalletService{
   

public function getWallet(int $userId, string $userType): Wallet
    {
        return Wallet::firstOrCreate(
            ['user_id' => $userId, 'user_type' => $userType],
            ['balance' => 0, 'currency' => 'SAR']
        );
    }
 
    public function getBalance(int $userId, string $userType )
    {
        $wallet = $this->getWallet($userId, $userType);

    return [
        'balance'  =>  $wallet->balance,
        'currency' => $wallet->currency,
    ];
    }

     /**
     * Add money (top-up, ride earnings...).
     */

    public function credit(int $userId, float $amount, string $userType, ?string $description = null,?Model $reference = null): Wallet
    {
        return $this->applyTransaction($userId, $amount, 'credit', $userType, $description , $reference);
    }
 
    /**
     * Take money (ride payment, withdrawal...).
     */
    public function debit(int $userId, float $amount, string $userType, ?string $description = null,?Model $reference = null): Wallet
    {
        return $this->applyTransaction($userId, $amount, 'debit', $userType, $description, $reference);
    }


    public function getTransactions(int $userId, string $userType)
{
    $wallet = $this->getWallet($userId, $userType);

    return WalletTranactions::where('wallet_id', $wallet->id)
        ->latest('id')
        ->paginate(15);
}





     private function applyTransaction(int $userId, float $amount, string $type, string $userType, ?string $description,?Model $reference = null): Wallet
    {
        if ($amount <= 0) {
             throw new BusinessRuleException('Amount must be greater than zero.');
        }

         $this->getWallet($userId, $userType);

        return DB::transaction(function () use ($userId, $amount, $type, $userType, $description,$reference) {
            $wallet = Wallet::where('user_id', $userId)
                ->where('user_type', $userType)
                ->lockForUpdate()
                ->first();

                if ($type === 'debit' && $wallet->balance < $amount) {
                throw new BusinessRuleException('Insufficient wallet balance.');
            }

            $wallet->balance = $type === 'credit'
                ? $wallet->balance + $amount
                : $wallet->balance - $amount;
            $wallet->save();


            WalletTranactions::create([
                'wallet_id'     => $wallet->id,
                'type'          => $type,
                'amount'        => $amount,
                'balance_after' => $wallet->balance,
                'description'   => $description,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id'   => $reference?->getKey(),
            ]);

            return $wallet;
            });


  }

}