<?php

namespace App\Repositories;

use App\Models\AppTester;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AppTesterRepository extends BaseRepository
{
    public function __construct(AppTester $model)
    {
        parent::__construct($model);
    }

    public function upsertRegistration(array $payload): AppTester
    {
        return AppTester::updateOrCreate(
            ['email' => $payload['email']],
            $payload
        );
    }

    public function paginateForAdmin(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->query()->latest();

        $search = trim((string) ($filters['search'] ?? ''));
        $operatingSystem = trim((string) ($filters['operating_system'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));

        if (in_array($operatingSystem, ['android', 'ios'], true)) {
            $query->where('operating_system', $operatingSystem);
        }

        if (in_array($status, AppTester::STATUSES, true)) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function stats(): array
    {
        return [
            'total' => $this->query()->count(),
            'android' => $this->query()->where('operating_system', 'android')->count(),
            'ios' => $this->query()->where('operating_system', 'ios')->count(),
            'eligible' => $this->query()->where('is_android_eligible', true)->count(),
            'registered' => $this->query()->where('status', AppTester::STATUS_REGISTERED)->count(),
            'account_created' => $this->query()->where('status', AppTester::STATUS_ACCOUNT_CREATED)->count(),
            'testing' => $this->query()->where('status', AppTester::STATUS_TESTING)->count(),
        ];
    }

    public function findMatchingUser(string $email, string $phone): ?User
    {
        $email = mb_strtolower(trim($email));
        $phoneDigits = $this->normalizePhone($phone);

        $candidates = User::query()
            ->where(function ($query) use ($email, $phoneDigits) {
                if ($email !== '') {
                    $query->whereRaw('LOWER(email) = ?', [$email]);
                }

                if ($phoneDigits !== '') {
                    $method = $email !== '' ? 'orWhere' : 'where';
                    $query->{$method}('phone', 'like', '%' . $phoneDigits . '%');
                }
            })
            ->get();

        return $candidates->first(function (User $user) use ($email, $phoneDigits): bool {
            if ($email !== '' && mb_strtolower((string) $user->email) === $email) {
                return true;
            }

            return $phoneDigits !== ''
                && $this->normalizePhone((string) $user->phone) === $phoneDigits;
        });
    }

    public function matchingTestersForUser(User $user): Collection
    {
        $email = mb_strtolower(trim((string) $user->email));
        $phoneDigits = $this->normalizePhone((string) $user->phone);

        $candidates = $this->query()
            ->where(function ($query) use ($email, $phoneDigits) {
                if ($email !== '') {
                    $query->whereRaw('LOWER(email) = ?', [$email]);
                }

                if ($phoneDigits !== '') {
                    $method = $email !== '' ? 'orWhere' : 'where';
                    $query->{$method}('phone', 'like', '%' . $phoneDigits . '%');
                }
            })
            ->get();

        return $candidates->filter(function (AppTester $tester) use ($email, $phoneDigits): bool {
            if ($email !== '' && mb_strtolower((string) $tester->email) === $email) {
                return true;
            }

            return $phoneDigits !== ''
                && $this->normalizePhone((string) $tester->phone) === $phoneDigits;
        })->values();
    }

    public function updateStatus(AppTester $tester, string $status): AppTester
    {
        $tester->update(['status' => $status]);

        return $tester->fresh();
    }

    public function resolveStatusForUser(?User $user): string
    {
        if (!$user) {
            return AppTester::STATUS_REGISTERED;
        }

        if ($user->last_login !== null) {
            return AppTester::STATUS_TESTING;
        }

        return AppTester::STATUS_ACCOUNT_CREATED;
    }

    protected function normalizePhone(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }
}
