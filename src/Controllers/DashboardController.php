<?php

namespace Acelle\Vnpay\Controllers;

use Illuminate\Http\Request;
use Acelle\Http\Controllers\Controller as BaseController;
use Acelle\Vnpay\Vnpay;

class DashboardController extends BaseController
{
    public function index(Request $request)
    {
        return view('vnpay::index', [
            'vnpay' => Vnpay::initialize(),
        ]);
    }
}
