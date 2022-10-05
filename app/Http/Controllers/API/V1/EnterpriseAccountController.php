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
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Enterprise  $enterprise
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function index(Request $request): JsonResponse
    {
        $relations = explode(',', $request->query('with'));

        $accounts = EnterpriseAccount::select([
            'id',
            'type',
            'account_id',
            'active',
            'enterprise_id',
            'created_at',
        ])->with('enterprise:id,name,settings')->orderBy('type', 'ASC')->orderBy('id', 'ASC')->get();

        if (in_array('account', $relations)) {
            $accounts = withRelation('account', $accounts, 'account_id', 'id');
        }

        return $this->successResponse($accounts);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\EnterpriseAccountRequest  $request
     * @param  \App\Models\Enterprise  $enterprise
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(EnterpriseAccountRequest $request, Enterprise $enterprise): JsonResponse
    {
        $data = $request->validated();

        $enterpriseAccount = $this->repo->store($enterprise, $data['type'], $data['account_id']);

        return $this->successResponse($enterpriseAccount);
    }

    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\EnterpriseAccount  $enterpriseAccount
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Exception
     */
    public function show(Request $request, EnterpriseAccount $enterpriseAccount): JsonResponse
    {
        $relations = explode(',', $request->query('with'));

        if (in_array('account', $relations)) {
            $enterpriseAccount->account = SidoohAccounts::find($enterpriseAccount->account_id);
        }

        return $this->successResponse($enterpriseAccount);
    }
}
