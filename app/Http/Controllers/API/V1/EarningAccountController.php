<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\EarningAccount;
use App\Services\SidoohAccounts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EarningAccountController extends Controller
{
    /**
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function index(Request $request): JsonResponse
    {
        $relations = explode(',', $request->query('with'));
        $earningAccounts = EarningAccount::select([
            'id',
            'type',
            'self_amount',
            'invite_amount',
            'account_id',
            'updated_at',
        ])->latest()->paginate();

        if (in_array('account', $relations)) {
            $earningAccounts = withRelation('account', $earningAccounts, 'account_id', 'id');
        }

        return $this->successResponse($earningAccounts);
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\EarningAccount  $earningAccount
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Exception
     */
    public function show(Request $request, EarningAccount $earningAccount): JsonResponse
    {
        $relations = explode(',', $request->query('with'));

        if (in_array('account', $relations) && $earningAccount->account_id) {
            $earningAccount->account = SidoohAccounts::find($earningAccount->account_id);
        }

        return $this->successResponse($earningAccount);
    }
}
