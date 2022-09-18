<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\EnterpriseAccountRequest;
use App\Models\Enterprise;
use App\Models\EnterpriseAccount;
use App\Repositories\EnterpriseAccountRepository;
use App\Services\SidoohAccounts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnterpriseAccountController extends Controller
{
    public function __construct(private readonly EnterpriseAccountRepository $repo)
    {
    }

    /**
     * Display a listing of the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Enterprise   $enterprise
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Enterprise $enterprise): JsonResponse
    {
        $enterprises = $enterprise->enterpriseAccounts()->select([
            "id",
            "type",
            "account_id",
            "enterprise_id",
            "created_at"
        ])->latest()->get();

        return $this->successResponse($enterprises);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Enterprise   $enterprise
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(EnterpriseAccountRequest $request, Enterprise $enterprise): JsonResponse
    {
        $data = $request->validated();

        $enterpriseAccount = $this->repo->store($enterprise, $data["type"], $data["account_id"]);

        return $this->successResponse($enterpriseAccount);
    }

    /**
     * Display the specified resource.
     *
     * @param \Illuminate\Http\Request      $request
     * @param \App\Models\EnterpriseAccount $enterpriseAccount
     * @throws \Exception
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, EnterpriseAccount $enterpriseAccount): JsonResponse
    {
        $relations = explode(",", $request->query("with"));

        if(in_array("account", $relations)) {
            $enterpriseAccount->account = SidoohAccounts::find($enterpriseAccount->account_id);
        }

        return $this->successResponse($enterpriseAccount);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
