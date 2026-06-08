<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\YooKassaService;

class PaymentsController extends Controller
{
    protected $yooKassa;

    public function __construct(YooKassaService $yooKassa)
    {
        $this->yooKassa = $yooKassa;
    }
    public function create(Request $request)
    {
        $amount = $request->input('amount');
        return $this->yooKassa->create($amount);
    }

    public function success(Request $request)
    {

    }
}
