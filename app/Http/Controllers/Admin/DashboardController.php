<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;

class DashboardController extends Controller
{
    public function __construct(protected DashboardService $service) {}

    public function index()
    {
        return view('admin.dashboard', [
            'metrics' => $this->service->metrics(),
        ]);
    }
}
