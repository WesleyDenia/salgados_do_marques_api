<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WhatsAppService;

class WhatsAppController extends Controller
{
    public function __construct(protected WhatsAppService $whatsApp) {}

    public function index()
    {
        return view('admin.whatsapp.index', [
            'session' => $this->whatsApp->sessionSnapshot(),
        ]);
    }
}
