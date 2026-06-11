<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Contracts\Notifications\WhatsAppClient;
use App\Http\Requests\Admin\AdminOperationalSettingsUpdateRequest;
use App\Models\ActivityLog;
use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminOperationalSettingsController extends Controller
{
    private const SETTINGS_VERSION_KEY = 'SETTINGS_VERSION';

    public const DEFAULT_SETTINGS = [
        'ORDER_START_TIME' => '12:00',
        'ORDER_END_TIME' => '20:00',
        'ORDER_MINIMUM_MINUTES' => 30,
        'ORDER_CANCEL_MINUTES' => 60,
        'ORDER_SCHEDULING_WINDOW_DAYS' => 14,
        'WHATSAPP_ORDER_TO' => '',
    ];

    public function __construct(protected SettingService $settingService) {}

    public function show()
    {
        $keys = array_keys(self::DEFAULT_SETTINGS);
        $keys[] = self::SETTINGS_VERSION_KEY;

        $settings = Setting::whereIn('key', $keys)->get()->pluck('value', 'key')->all();

        foreach (self::DEFAULT_SETTINGS as $key => $default) {
            if (! array_key_exists($key, $settings)) {
                $settings[$key] = $default;
            }
        }

        $settings[self::SETTINGS_VERSION_KEY] = (int) ($settings[self::SETTINGS_VERSION_KEY] ?? 1);
        $settings['ORDER_MINIMUM_MINUTES'] = (int) $settings['ORDER_MINIMUM_MINUTES'];
        $settings['ORDER_CANCEL_MINUTES'] = (int) $settings['ORDER_CANCEL_MINUTES'];
        $settings['ORDER_SCHEDULING_WINDOW_DAYS'] = (int) $settings['ORDER_SCHEDULING_WINDOW_DAYS'];
        $settings['WHATSAPP_ORDER_TO'] = (string) ($settings['WHATSAPP_ORDER_TO'] ?? '');

        return response()->json($settings);
    }

    public function update(AdminOperationalSettingsUpdateRequest $request)
    {
        $data = $request->validated();
        $submittedVersion = (int) $data['version'];
        unset($data['version']);

        $user = $request->user();
        $changes = $this->mutateSettings($submittedVersion, function () use ($data, $user): array {
            $changes = [];

            foreach ($data as $key => $value) {
                $record = $this->settingService->findRecord($key);
                $oldValue = $record?->value;

                if ($oldValue != $value) {
                    $this->settingService->set($key, $value);
                    $changes[$key] = [
                        'old' => $oldValue,
                        'new' => $value,
                    ];
                }
            }

            if ($changes !== []) {
                ActivityLog::create([
                    'user_id' => $user?->id,
                    'subject_type' => 'Setting',
                    'action' => 'update_operational_settings',
                    'payload' => $changes,
                ]);
            }

            return $changes;
        });

        return response()->json([
            'message' => 'Configurações operacionais atualizadas com sucesso',
            'changes_count' => count($changes),
        ]);
    }

    public function reset(Request $request)
    {
        $validated = $request->validate([
            'version' => ['required', 'integer'],
        ]);
        $user = $request->user();
        $changes = $this->mutateSettings((int) $validated['version'], function () use ($user): array {
            $changes = [];
            foreach (self::DEFAULT_SETTINGS as $key => $value) {
                $record = $this->settingService->findRecord($key);
                $oldValue = $record?->value;

                if ($oldValue != $value) {
                    $this->settingService->set($key, $value);
                    $changes[$key] = [
                        'old' => $oldValue,
                        'new' => $value,
                    ];
                }
            }

            if ($changes !== []) {
                ActivityLog::create([
                    'user_id' => $user?->id,
                    'subject_type' => 'Setting',
                    'action' => 'reset_operational_settings',
                    'payload' => $changes,
                ]);
            }

            return $changes;
        });

        return response()->json([
            'message' => 'Configurações restauradas para os padrões',
            'changes_count' => count($changes),
        ]);
    }

    public function testWhatsApp(Request $request, WhatsAppClient $whatsAppClient)
    {
        $validated = $request->validate([
            'number' => ['required', 'string', 'regex:' . AdminOperationalSettingsUpdateRequest::E164_REGEX],
        ]);
        $number = trim($validated['number']);

        $success = $whatsAppClient->sendMessage(
            $number,
            'Teste de conexão de governação operacional Salgados do Marquês.'
        );

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Mensagem de teste enviada com sucesso.' : ($whatsAppClient->lastError() ?: 'Falha ao enviar mensagem de teste.'),
        ]);
    }

    protected function mutateSettings(int $submittedVersion, callable $callback): array
    {
        $changes = [];

        DB::transaction(function () use ($submittedVersion, $callback, &$changes): void {
            Setting::query()->firstOrCreate(
                ['key' => self::SETTINGS_VERSION_KEY],
                ['value' => '1', 'type' => 'integer', 'editable' => true]
            );

            $versionRecord = Setting::query()
                ->where('key', self::SETTINGS_VERSION_KEY)
                ->lockForUpdate()
                ->firstOrFail();

            $currentVersion = (int) $versionRecord->value;

            if ($submittedVersion !== $currentVersion) {
                throw new HttpResponseException(response()->json([
                    'error' => 'CONCURRENCY_ERROR',
                    'message' => 'As configurações foram alteradas por outro utilizador. Por favor, recarregue a página.',
                ], 409));
            }

            $changes = $callback();

            if ($changes !== []) {
                $versionRecord->value = (string) ($currentVersion + 1);
                $versionRecord->type = $versionRecord->type ?: 'integer';
                $versionRecord->editable = $versionRecord->editable ?? true;
                $versionRecord->save();

                Cache::forget('setting_' . self::SETTINGS_VERSION_KEY);
            }
        });

        return $changes;
    }
}
