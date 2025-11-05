<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class NotificationController extends Controller
{
    protected NotificationService $service;

    public function __construct(NotificationService $service) { $this->service = $service; }

    public function registerToken(Request $request)
    {
        $request->validate(['token'=>'required','platform'=>'nullable|string']);
        $this->service->registerToken($request->user(), $request->token, $request->platform);
        return response()->json(['message'=>'Token registrado']);
    }

    public function send(Request $request)
    {
        Gate::authorize('manage', $request->user());
        $request->validate(['title'=>'required','body'=>'required']);
        $this->service->sendGlobal($request->title, $request->body);
        return response()->json(['message'=>'Notificações enviadas']);
    }
}
