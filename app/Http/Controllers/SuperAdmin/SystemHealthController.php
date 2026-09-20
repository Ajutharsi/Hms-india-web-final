<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\SystemHealthService;

class SystemHealthController extends Controller
{
    public function __construct(private readonly SystemHealthService $health)
    {
    }

    public function index()
    {
        return view('super-admin.system-health.index', [
            'health' => $this->health->all(),
        ]);
    }
}
