<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\EarningAccountType;
use App\Enums\EventType;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\Cashback;
use App\Models\EarningAccount;
use App\Models\SavingsTransaction;
use App\Services\SidoohAccounts;
use App\Services\SidoohNotify;
use App\Services\SidoohSavings;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class EarningController extends Controller
{
    public function getEarningAccounts(Request $request): JsonResponse
    {
        $relations = explode(",", $request->query("with"));
        $earningAccounts = EarningAccount::select([
            "id",
            "type",
            "self_amount",
            "invite_amount",
            "account_id",
            "updated_at"
        ])->latest()->get();

        if(in_array("account", $relations)) {
            $earningAccounts = withRelation("account", $earningAccounts, "account_id", "id");
        }

        return response()->json($earningAccounts);
    }

    public function getCashbacks(Request $request): JsonResponse
    {
        $relations = explode(",", $request->query("with"));
        $cashbacks = Cashback::select([
            "id",
            "amount",
            "type",
            "account_id",
            "transaction_id",
            "updated_at"
        ])->latest()->with("transaction:id,description,amount")->get();

        if(in_array("account", $relations)) {
            $cashbacks = withRelation("account", $cashbacks, "account_id", "id");
        }

        return response()->json($cashbacks);
    }

    public function save(Request $request)
    {
        $request->validate(["date" => "date|date_format:d-m-Y"]);

        $date = null;
        if ($request->has("date")) $date = Carbon::createFromFormat("d-m-Y", $request->input("date"));

        $savings = $this->collectSavings($date);

        try {
            $responses = SidoohSavings::save($savings->toArray());

            $totalCompleted = count($responses['completed']);
            $totalFailed = count($responses['failed']);

            //TODO: Store in DB so that we don't repeat saving

            $message = "STATUS:SAVINGS\n\n";
            if ($totalCompleted > 0)
                $message .= "Allocated for $totalCompleted accounts\n";
            if ($totalFailed > 0)
                $message .= "Failed for $totalFailed accounts\n";

            SidoohNotify::notify([
                '254714611696',
                '254711414987',
                '254110039317'
            ], $message, EventType::STATUS_UPDATE);
        } catch (Exception $e) {
            // Notify failure
            Log::error($e);

            SidoohNotify::notify([
                '254714611696',
                '254711414987',
                '254110039317'
            ], "ERROR:SAVINGS\nError Saving Earnings!!!", EventType::ERROR_ALERT);

        }

        return $savings;
    }

    public function collectSavings($date = null): Collection
    {
        if (!$date) $date = new Carbon;

        $cashbacks = Cashback::selectRaw("SUM(amount) as amount, account_id")->whereNotNull("account_id")
            ->whereDate("created_at", $date->format("Y-m-d"))->groupBy("account_id")->get();

        return $cashbacks->map(fn(Cashback $cashback) => [
            "account_id" => $cashback->account_id,
            "current_amount" => $cashback->amount * .2,
            "locked_amount" => $cashback->amount * .8
        ]);
    }

    /**
     * @throws Throwable
     */
    public function processSavingsCallback(Request $request)
    {
        Log::info('...[CONTROLLER - EARNING]: Process Savings Callback...', $request->all());

        $saving = SavingsTransaction::whereReference($request->id)->firstOrFail();

        if ($request->status === Status::COMPLETED->name && $saving->status === Status::PENDING->name) {
            $saving->status = Status::COMPLETED;
            $saving->save();

            $saving->transaction->status = Status::COMPLETED;
            $saving->transaction->save();

            $account = SidoohAccounts::find($saving->transaction->account_id);

            $destination = $saving->transaction->destination;
            $amount = $saving->transaction->amount;
            $date = $saving->transaction->updated_at
                ->timezone('Africa/Nairobi')->format(config("settings.sms_date_time_format"));

            $earningAccounts = EarningAccount::select(["type", "self_amount", "invite_amount"])
                ->whereAccountId($account['id'])
                ->get();

            if ($earningAccounts->count() === 0) {
                return $this->errorResponse("No earnings found for user");
            }

            [$creditAccounts, $debitAccounts] = $earningAccounts
                ->partition(fn($a) => $a->type !== EarningAccountType::WITHDRAWALS->name);

            $totalEarned = $creditAccounts->reduce(fn($total, $account) => $total + $account->balance);
            $totalWithdrawn = $debitAccounts->reduce(fn($total, $account) => $total + $account->balance);

            $earning_balance = $totalEarned - $totalWithdrawn;

            $code = config('services.at.ussd.code');

            $method = 'MPESA';

            if ($destination !== $account['phone']) {
                $message = "You have redeemed KES$amount to MPESA $destination from your Sidooh account on $date. Your earnings balance is $earning_balance.";

                SidoohNotify::notify([$account['phone']], $message, EventType::WITHDRAWAL_PAYMENT);

                $message = "Congratulations! You have received KES$amount via $method from Sidooh account ${account['phone']} on $date. Sidooh Makes You Money with Every Purchase.\n\nDial $code NOW for FREE on your Safaricom line to BUY AIRTIME & START EARNING from your purchases.";

                SidoohNotify::notify([$destination], $message, EventType::WITHDRAWAL_PAYMENT);

            } else {
                $message = "You have redeemed KES$amount from your Sidooh account on $date to $method. Your earnings balance is $earning_balance.";

                SidoohNotify::notify([$account['phone']], $message, EventType::WITHDRAWAL_PAYMENT);
            }

        }

        if ($request->status === Status::FAILED->name && $saving->status === Status::PENDING->name) {
            $saving->status = Status::FAILED;
            $saving->save();

            $saving->transaction->status = Status::FAILED;
            $saving->transaction->save();

            $account = SidoohAccounts::find($saving->transaction->account_id);

            $message = "Sorry! We failed to complete your earnings withdrawal. ";
            $message .= "No amount was deducted from your account.\nWe apologize for the inconvenience. Please try again.";

            SidoohNotify::notify([$account['phone']], $message, EventType::WITHDRAWAL_FAILURE);
        }

        return $this->successResponse();
    }
}
