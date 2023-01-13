<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\EventType;
use App\Http\Controllers\Controller;
use App\Models\Cashback;
use App\Services\SidoohAccounts;
use App\Services\SidoohNotify;
use App\Services\SidoohSavings;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CashbackController extends Controller
{
    /**
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function index(Request $request): JsonResponse
    {
        $relations = explode(',', $request->query('with'));
        $cashbacks = Cashback::select([
            'id',
            'amount',
            'type',
            'account_id',
            'transaction_id',
            'updated_at',
        ])->latest()->with('transaction:id,description,amount')->limit(100)->get();

        if (in_array('account', $relations)) {
            $cashbacks = withRelation('account', $cashbacks, 'account_id', 'id');
        }

        return $this->successResponse($cashbacks);
    }

    /**
     * @throws \Exception
     */
    public function show(Request $request, Cashback $cashback): JsonResponse
    {
        $relations = explode(',', $request->query('with'));

        if (in_array('account', $relations) && $cashback->account_id) {
            $cashback->account = SidoohAccounts::find($cashback->account_id);
        }
        if (in_array('transaction', $relations)) {
            $cashback->load('transaction');
        }

        return $this->successResponse($cashback);
    }

    public function invest(Request $request): JsonResponse
    {
        $request->validate(['date' => 'date|date_format:d-m-Y']);

        $date = $request->filled('date')
            ? Carbon::createFromFormat('d-m-Y', $request->input('date'))
            : new Carbon;

        $savings = Cashback::selectRaw('SUM(amount) as amount, account_id')->whereNotNull('account_id')->whereDate(
            'created_at',
            $date->format('Y-m-d')
        )->groupBy('account_id')->get()->map(fn (Cashback $cashback) => [
            'account_id'     => $cashback->account_id,
            'current_amount' => round($cashback->amount * .2, 4),
            'locked_amount'  => round($cashback->amount * .8, 4),
        ]);

        $message = "STATUS:SAVINGS\n\n";

        if ($savings->count()) {
            try {
                $responses = SidoohSavings::save($savings->toArray());

                $totalCompleted = count($responses['completed']);
                $totalFailed = count($responses['failed']);

                //TODO: Store in DB so that we don't repeat saving

                if ($totalCompleted > 0) {
                    $message .= "Processed earnings for $totalCompleted accounts\n";
                }
                if ($totalFailed > 0) {
                    $message .= "Failed for $totalFailed accounts";
                }
            } catch (Exception $e) {
                // Notify failure
                Log::error($e);

                SidoohNotify::notify(
                    admin_contacts(),
                    "ERROR:SAVINGS\nError Saving Cashback!!!",
                    EventType::ERROR_ALERT
                );

                $this->successResponse($savings);
            }
        } else {
            $message .= 'No earnings to allocate.';
        }

        SidoohNotify::notify(admin_contacts(), $message, EventType::STATUS_UPDATE);

        return $this->successResponse($savings);
    }
}
