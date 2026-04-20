<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppTester;
use App\Services\AppTesterService;
use Illuminate\Http\Request;

class AppTesterController extends Controller
{
    public function __construct(
        protected AppTesterService $service,
    ) {}

    public function index(Request $request)
    {
        $operatingSystem = $request->string('operating_system')->toString();
        $search = trim((string) $request->string('search'));
        $status = $request->string('status')->toString();

        return view('admin.app-testers.index', [
            'testers' => $this->service->paginateForAdmin([
                'operating_system' => $operatingSystem,
                'search' => $search,
                'status' => $status,
            ], 20),
            'filters' => [
                'operating_system' => $operatingSystem,
                'search' => $search,
                'status' => $status,
            ],
            'stats' => $this->service->stats(),
            'statuses' => AppTester::STATUSES,
        ]);
    }
}
