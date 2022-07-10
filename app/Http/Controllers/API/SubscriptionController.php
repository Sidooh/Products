<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $relations = explode(",", $request->query("with"));

        $subscriptions = Subscription::select([
            "id",
            "amount",
            "start_date",
            "end_date",
            "status",
            "account_id",
            "subscription_type_id",
            "created_at"
        ])->latest()->with("subscriptionType:id,title,price,duration,active,period")->get();

        if(in_array("account", $relations)) {
            $subscriptions = withRelation("account", $subscriptions, "account_id", "id");
        }

        return response()->json($subscriptions);
    }

    public function getSubTypes(): JsonResponse
    {
        $subTypes = SubscriptionType::select([
            "id",
            "title",
            "price",
            "level_limit",
            "duration",
            "active",
            "period",
        ])->latest()->get();

        return response()->json($subTypes);
    }
}
