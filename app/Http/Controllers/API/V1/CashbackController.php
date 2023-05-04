<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\EventType;
use App\Enums\Status;
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
        $request->validate([
            'page'      => 'nullable|integer|min:1',
            'page_size' => 'nullable|integer|between:10,1000',
        ]);

        $relations = $request->string('with')->explode(',');
        $perPage = $request->integer('page_size', 100);
        $page = $request->integer('page', 1);

        $cashbacks = Cashback::select([
            'id',
            'amount',
            'type',
            'status',
            'account_id',
            'transaction_id',
            'updated_at',
        ])->latest()->limit($perPage)->offset($perPage * ($page - 1))->get();

        if ($relations->contains('account')) {
            $cashbacks = withRelation('account', $cashbacks, 'account_id', 'id');
        }

        return $this->successResponse(paginate($cashbacks, Cashback::count(), $perPage, $page));
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

        $date = $request->filled('date') ? Carbon::createFromFormat('d-m-Y', $request->input('date')) : new Carbon;

        $builder = Cashback::select('cashbacks.account_id')
            ->selectRaw('
                    SUM(CASE
                            WHEN t.product_id != 6 THEN cashbacks.amount
                        END) as amount,
                    SUM(CASE
                        WHEN t.product_id = 6 THEN cashbacks.amount
                    END) as merchant_amount
                    '
            )
            ->leftJoin('transactions as t', 't.id', '=', 'cashbacks.transaction_id')
            ->whereNotNull('cashbacks.account_id')
            ->whereNot('cashbacks.status', Status::COMPLETED)
            ->whereDate('cashbacks.created_at', $date->format('Y-m-d'))
            ->groupBy('cashbacks.account_id');

        $savings = $builder->get()->map(fn(Cashback $cashback) => [
            'account_id'      => $cashback->account_id,
            'current_amount'  => round($cashback->amount * .2, 4),
            'locked_amount'   => round($cashback->amount * .8, 4),
            'merchant_amount' => round($cashback->merchant_amount, 4),
        ]);

        $message = "STATUS:SAVINGS\n\n";

        if ($savings->count()) {
            try {
                $responses = SidoohSavings::save($savings->toArray());

                $completed = $responses['completed'];
                $failed = $responses['failed'];

                if (count($completed) > 0) {
                    $message .= 'Processed earnings for '.count($completed)."  accounts\n";

                    $builder->whereIn('cashbacks.account_id', array_keys($completed))->update(['cashbacks.status' => Status::COMPLETED]);
                }
                if (count($failed) > 0) {
                    $message .= 'Failed for '.count($failed).' accounts';

                    $builder->whereIn('cashbacks.account_id', array_keys($failed))->update(['cashbacks.status' => Status::FAILED]);
                }
            } catch (Exception $e) {
                // Notify failure
                Log::error($e);

                SidoohNotify::notify(
                    admin_contacts(),
                    "ERROR:SAVINGS\nError Saving Cashback!!!",
                    EventType::ERROR_ALERT
                );

                return $this->errorResponse($savings);
            }
        } else {
            $message .= 'No earnings to allocate.';
        }

        SidoohNotify::notify(admin_contacts(), $message, EventType::STATUS_UPDATE);

        return $this->successResponse($savings);
    }
}
