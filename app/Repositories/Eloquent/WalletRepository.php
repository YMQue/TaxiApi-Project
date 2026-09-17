<?php
namespace App\Repositories\Eloquent;

use App\Models\Driver;
use App\Models\TopupRequest;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Repositories\Contracts\WalletRepositoryInterface;
use Bavix\Wallet\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WalletRepository implements WalletRepositoryInterface
{
    //--------Rider---------//
     public function getRiderBalance(User $user)
     {
        return[
            'balance' => $user->balance,
            'currency' => 'SAR',
        ];
     }

     public function riderTransactions(User $user,?string $type,int $perpage = 10)
     {
        return $user->transactions()
        ->when($type,fn($q)=>$q->where('type',$type))
        ->latest()
        ->paginate($perpage);
     }

     public function createTopupRequest(User $user , array $data)
     {
        return TopupRequest::create([
            'user_id' => $user->id,
            'amount'=> $data['amount'],
            'method'=> $data['method'],
            'payment_ref'=>$data['payment_ref'] ?? null,
            'status' => 'pending',
        ]);
     }

           //--------Driver---------//

    public function getDriverBalance(Driver $driver)
    {
         return[
            'balance' => $driver->balance,
            'currency' => 'SAR',
        ];
    }
    public function driverTransactions(Driver $driver,?string $type ,int $perpage = 10)
    {
        return $driver->transactions()
        ->when($type,fn($q)=>$q->where('type',$type))
        ->latest()
        ->paginate($perpage);
    }

    public function createDriverWithdrawalRequest(Driver $driver,array $data)
    {
         return DB::transaction(function() use($driver , $data){
                if(! $driver->canWithdraw($data['amount'])){
                    throw ValidationException::withMessages([
                        'amount' => 'you dont have this amount',
                    ]);
                }
                $transaction = $driver->withdraw(
                    $data['amount'],
                    [
                        'bank_name' => $data['bank_name'],
                        'account_number'=>$data['account_number'],
                        'iban'=>$data['iban'],
                    ],
                    false
                );
                return WithdrawalRequest::create([
                    'driver_id' => $driver->id,
                'amount' => $data['amount'],
                'bank_name' => $data['bank_name'],
                'account_number' => $data['account_number'],
                'iban' => $data['iban'] ?? null,
                'status' => 'pending',
                'wallet_transaction_id' => $transaction->id,
                ]);
         });

    }

     public function latestWithdrawalStatus(Driver $driver)
    {
        return WithdrawalRequest::where('driver_id',$driver->id)->latest()->first();
    }

    //--------Admin--------//

    public function pendingTopupRequests(int $perPage)
    {
        return TopupRequest::pending()->with('user')->latest()->paginate($perPage);
    }

    public function approveTopupRequest(TopupRequest $topup)
    {
        return DB::transaction(function() use($topup){
            if($topup->status !=='pending'){
                throw ValidationException::withMessages([
                    'status'=>'your request has been seen',
                ]);
            }
            $transaction = $topup->user->deposit(
                $topup->amount,
                [
                    'reference_type' => 'topup_request',
                    'reference_id' => $topup->id,
                    'description' => 'wallet has been charged',
                ],
                true
            );
            $topup->update([
                'status'=>'approved',
                'wallet_transaction_id' => $transaction->id,
                'processed_at' => now(),
            ]);
            return $topup->fresh();
        });
    }

    public function rejectTopupRequest(TopupRequest $topup, string $admin_note)
    {
        if ($topup->status !=='pending'){
            throw ValidationException::withMessages([
                 'status'=>'your request has been seen',
            ]);
        }
        $topup->update([
            'status'=>'rejected',
            'admin_note'=>$admin_note,
        ]);
        return $topup->fresh();
    }
    public function pendingWithdrawalRequests(int $perPage)
    {
          return WithdrawalRequest::pending()->with('driver')->latest()->paginate($perPage);
    }

    public function approveWithdrawalRequest(WithdrawalRequest $withdrawal)
    {
        DB::transaction(function() use($withdrawal){
            if($withdrawal->status !=='pending'){
                throw ValidationException::withMessages([
                    'status'=>'your request has been seen'
                ]);
            }
            $transaction = Transaction::findOrFail($withdrawal->wallet_transaction_id);
            $withdrawal->driver->confirm($transaction);
            $withdrawal->update([
                'status'=>'approved',
                'processed_at' => now(),
            ]);
            return $withdrawal->fresh();
        });
    }
    public function rejectWithdrawalRequest(WithdrawalRequest $withdrawal, string $admin_note)
    {
        return DB::transaction(function() use($withdrawal , $admin_note){
            if($withdrawal->status !=='pending'){
                throw ValidationException::withMessages([
                    'status'=>'your request has been seen'
                ]);
            }
            Transaction::where('id',$withdrawal->wallet_transaction_id)
            ->where('confirmed',false)
            ->delete();

            $withdrawal->update([
                'status' => 'rejected',
                'admin_note'=>$admin_note,
                'proccessed_at'=>now(),
            ]);
            return $withdrawal->fresh();
        });
    }

    public function manualAdjust(string $userType, int $userId, float $amount, string $reason)
    {
        $model = $userType === 'driver' ? Driver::findOrFail($userId) :User::findOrFail($userId);
        $meta = ['description' => $reason, 'reference_type' => 'manual_adjust'];
        $transaction = $amount >= 0
            ? $model->deposit($amount, $meta, true)
            : $model->forceWithdraw(abs($amount), $meta, true);

            return [
            'balance' => $model->fresh()->balanceFloat,
            'transaction_uuid' => $transaction->uuid,
        ];
    }


}