<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Cashback;
use App\Models\EarningAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        $earningAccounts = Cashback::select([
            "id",
            "amount",
            "type",
            "account_id",
            "transaction_id",
            "updated_at"
        ])->latest()->with("transaction:id,description,destination")->get();

        if(in_array("account", $relations)) {
            $earningAccounts = withRelation("account", $earningAccounts, "account_id", "id");
        }

        return response()->json($earningAccounts);
    }
}
