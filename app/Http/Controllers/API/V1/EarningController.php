<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Cashback;
use App\Services\SidoohSavings;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EarningController extends Controller
{
    public function save(Request $request)
    {
        $request->validate(["date" => "date|date_format:d-m-Y"]);

        $date = null;
        if($request->has("date")) $date = Carbon::createFromFormat("d-m-Y", $request->input("date"));

        $savings = $this->collectSavings($date);

        SidoohSavings::save($savings);

        dump_json($savings);
    }

    public function collectSavings($date = null): array
    {
        if(!$date) $date = new Carbon;

        $cashbacks = Cashback::selectRaw("SUM(amount) as amount, account_id")->whereNotNull("account_id")
            ->whereDate("created_at", $date->format("Y-m-d"))->groupBy("account_id")->get();

        return $cashbacks->map(fn(Cashback $cashback) => [
            "account_id"     => $cashback->account_id,
            "current_amount" => $cashback->amount * .2,
            "locked_amount"  => $cashback->amount * .8
        ])->toArray();
    }
}
