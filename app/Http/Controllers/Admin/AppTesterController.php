<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppTester;
use Illuminate\Http\Request;

class AppTesterController extends Controller
{
    public function index(Request $request)
    {
        $operatingSystem = $request->string('operating_system')->toString();
        $search = trim((string) $request->string('search'));

        $query = AppTester::query()->latest();

        if (in_array($operatingSystem, ['android', 'ios'], true)) {
            $query->where('operating_system', $operatingSystem);
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return view('admin.app-testers.index', [
            'testers' => $query->paginate(20)->withQueryString(),
            'filters' => [
                'operating_system' => $operatingSystem,
                'search' => $search,
            ],
            'stats' => [
                'total' => AppTester::count(),
                'android' => AppTester::where('operating_system', 'android')->count(),
                'ios' => AppTester::where('operating_system', 'ios')->count(),
                'eligible' => AppTester::where('is_android_eligible', true)->count(),
            ],
        ]);
    }
}
