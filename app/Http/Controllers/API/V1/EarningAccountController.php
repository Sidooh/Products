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
     * @throws \Exception
     */
    public function show(Request $request, int $accountId): JsonResponse
    {
        $relations = explode(',', $request->query('with'));

        $data['earning_accounts'] = EarningAccount::whereAccountId($accountId)->get();

        if (in_array('account', $relations)) {
            $data['account'] = SidoohAccounts::find($accountId);
        }

        return $this->successResponse($data);
    }
}
