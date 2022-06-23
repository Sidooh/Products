<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\AirtimeAccount;
use App\Models\EarningAccount;
use App\Models\Subscription;
use App\Models\UtilityAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $accountId
     * @return \Illuminate\Http\JsonResponse
     */
    public function airtimeAccounts(Request $request, int $accountId): JsonResponse
    {
        $accounts = AirtimeAccount::select(["id", "provider", "account_number"])->whereAccountId($accountId);

        if($request->exists('limit')) $accounts = $accounts->limit($request->input('limit'));

        $accounts = $accounts->latest()->get();

        return $this->successResponse($accounts);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @param int     $accountId
     * @return JsonResponse
     */
    public function utilityAccounts(Request $request, int $accountId): JsonResponse
    {
        $accounts = UtilityAccount::select(["id", "provider", "account_number"])->whereAccountId($accountId);

        if ($request->exists('limit')) $accounts = $accounts->limit($request->input('limit'));

        $accounts = $accounts->latest()->get();

        return $this->successResponse($accounts);
    }

    public function currentSubscription(Request $request, int $accountId): JsonResponse
    {
        $subscription = Subscription::whereAccountId($accountId)->latest()->first();

        return $this->successResponse($subscription);
    }

    public function earnings(Request $request, int $accountId): JsonResponse
    {
        $earnings = EarningAccount::select(["type", "self_amount", "invite_amount"])
            ->whereAccountId($accountId)
            ->get();

        return $this->successResponse($earnings);
    }
}
