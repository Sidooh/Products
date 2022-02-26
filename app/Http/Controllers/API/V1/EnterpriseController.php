<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\EnterpriseAccountType;
use App\Enums\FloatAccountType;
use App\Enums\VoucherType;
use App\Http\Controllers\Controller;
use App\Http\Requests\EnterpriseRequest;
use App\Http\Resources\EnterpriseAccountResource;
use App\Http\Resources\EnterpriseResource;
use App\Models\Enterprise;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EnterpriseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        return EnterpriseResource::collection(Enterprise::all());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(EnterpriseRequest $request): JsonResponse
    {
        $enterprise = Enterprise::create($request->all());
        $enterprise->floatAccount()->create();

        if($request->has('accounts')) {
            $this->createAccounts($enterprise, $request->input('accounts'));
        }

        return $this->successResponse(EnterpriseResource::make($enterprise->load('floatAccount')));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param EnterpriseRequest $request
     * @return JsonResponse
     */
    public function storeAccount(EnterpriseRequest $request): JsonResponse
    {
        $enterprise = Enterprise::find($request->input('enterprise_id'));

        $accounts = $this->createAccounts($enterprise, $request->input('accounts'));

        $message = "Enterprise account" . (count($accounts) > 1
                ? "s"
                : "") . " created!";
        return $this->successResponse(EnterpriseAccountResource::collection($accounts), $message);
    }

    private function createAccounts(Enterprise $enterprise, array $accountsData): Collection
    {
        $enterpriseAccountData = array_map(function($account) {
            return [
                ...$account,
                'type' => EnterpriseAccountType::EMPLOYEE,
            ];
        }, $accountsData);

        $voucherData = collect($accountsData)->map(function($account) use ($enterprise) {
            $account['enterprise_id'] = $enterprise->id;

            return [
                [
                    ...$account,
                    'type' => VoucherType::ENTERPRISE_LUNCH,
                ],
                [
                    ...$account,
                    'type' => VoucherType::ENTERPRISE_GENERAL,
                ],
            ];
        })->collapse()->toArray();

        Voucher::upsert($voucherData, ['account_id', 'type'], []);

        return $enterprise->enterpriseAccounts()->createMany($enterpriseAccountData);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(Enterprise $enterprise): JsonResponse
    {
        return $this->successResponse(EnterpriseResource::make($enterprise));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int     $id
     * @return JsonResponse
     */
    public function update(EnterpriseRequest $request, Enterprise $enterprise): JsonResponse
    {
        $enterprise->update($request->all());

        return $this->successResponse(EnterpriseResource::make($enterprise));
    }
}
