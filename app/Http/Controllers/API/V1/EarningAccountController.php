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
        $request->validate([
            'page'      => 'nullable|integer|min:1',
            'page_size' => 'nullable|integer|between:10,1000',
        ]);

        $relations = $request->string('with')->explode(',');
        $perPage = $request->integer('page_size', 100);
        $page = $request->integer('page', 1);

        $earningAccounts = EarningAccount::select([
            'id',
            'type',
            'self_amount',
            'invite_amount',
            'account_id',
            'updated_at',
        ])->latest()->limit($perPage)->offset($perPage * ($page - 1))->get();

        if ($relations->contains('account')) {
            $earningAccounts = withRelation('account', $earningAccounts, 'account_id', 'id');
        }

        return $this->successResponse(paginate($earningAccounts, EarningAccount::count(), $perPage, $page));
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
