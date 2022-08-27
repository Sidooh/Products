<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Cashback;
use App\Services\SidoohAccounts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashbackController extends Controller
{


    /**
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function index(Request $request): JsonResponse
    {
        $relations = explode(",", $request->query("with"));
        $cashbacks = Cashback::select([
            "id",
            "amount",
            "type",
            "account_id",
            "transaction_id",
            "updated_at"
        ])->latest()->with("transaction:id,description,amount")->limit(100)->get();

        if(in_array("account", $relations)) {
            $cashbacks = withRelation("account", $cashbacks, "account_id", "id");
        }

        return $this->successResponse($cashbacks);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Cashback     $cashback
     * @throws \Exception
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, Cashback $cashback): JsonResponse
    {
        $relations = explode(",", $request->query("with"));

        if(in_array("account", $relations) && $cashback->account_id) {
            $cashback->account = SidoohAccounts::find($cashback->account_id);
        }
        if(in_array("transaction", $relations)) {
            $cashback->load("transaction");
        }

        return $this->successResponse($cashback);
    }
}
