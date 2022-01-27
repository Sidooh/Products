<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCashbackRequest;
use App\Http\Requests\UpdateCashbackRequest;
use App\Models\Cashback;
use Illuminate\Http\Response;

class CashbackController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreCashbackRequest $request
     * @return Response
     */
    public function store(StoreCashbackRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param Cashback $cashback
     * @return Response
     */
    public function show(Cashback $cashback)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Cashback $cashback
     * @return Response
     */
    public function edit(Cashback $cashback)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateCashbackRequest $request
     * @param Cashback              $cashback
     * @return Response
     */
    public function update(UpdateCashbackRequest $request, Cashback $cashback)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Cashback $cashback
     * @return Response
     */
    public function destroy(Cashback $cashback)
    {
        //
    }
}
