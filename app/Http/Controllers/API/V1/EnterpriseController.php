<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\EnterpriseRequest;
use App\Models\Enterprise;
use App\Repositories\EnterpriseRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnterpriseController extends Controller
{
    public function __construct(private readonly EnterpriseRepository $repo) { }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        $enterprises = Enterprise::select(["id", "name", "settings", "created_at"])->latest()->get();

        return $this->successResponse($enterprises);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(EnterpriseRequest $request): JsonResponse
    {
        $name = $request->string("name");
        $settings = $request->input("settings");
        $accounts = $request->input("accounts");

        $enterprise = $this->repo->store($name, $settings, $accounts);

        return $this->errorResponse($enterprise);
    }

    /**
     * Display the specified resource.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Enterprise   $enterprise
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, Enterprise $enterprise): JsonResponse
    {
        $relations = explode(",", $request->query("with"));

        if(in_array("enterprise_accounts", $relations)) {
            $enterprise->load("enterpriseAccounts:id,type,account_id,enterprise_id,created_at");
        }

        return $this->successResponse($enterprise);
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
