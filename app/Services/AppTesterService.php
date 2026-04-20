<?php

namespace App\Services;

use App\Models\AppTester;
use App\Models\User;
use App\Repositories\AppTesterRepository;

class AppTesterService
{
    public function __construct(
        protected AppTesterRepository $repository,
    ) {}

    public function register(array $data, ?string $fallbackSourcePath = null): AppTester
    {
        $isAndroid = $data['operating_system'] === 'android';
        $matchingUser = $this->repository->findMatchingUser(
            $data['email'],
            $data['phone']
        );

        return $this->repository->upsertRegistration([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'operating_system' => $data['operating_system'],
            'status' => $this->repository->resolveStatusForUser($matchingUser),
            'is_android_eligible' => $isAndroid,
            'consent_at' => now(),
            'source_path' => $data['source_path'] ?? $fallbackSourcePath,
        ]);
    }

    public function paginateForAdmin(array $filters, int $perPage = 20)
    {
        return $this->repository->paginateForAdmin($filters, $perPage);
    }

    public function stats(): array
    {
        return $this->repository->stats();
    }

    public function syncStatusForUser(User $user): int
    {
        $updated = 0;

        foreach ($this->repository->matchingTestersForUser($user) as $tester) {
            $status = $this->repository->resolveStatusForUser($user);

            if ($tester->status === $status) {
                continue;
            }

            $this->repository->updateStatus($tester, $status);
            $updated++;
        }

        return $updated;
    }

    public function syncStatusesFromUsers(): int
    {
        $updated = 0;

        User::query()
            ->select(['id', 'email', 'phone'])
            ->chunkById(200, function ($users) use (&$updated): void {
                foreach ($users as $user) {
                    $updated += $this->syncStatusForUser($user);
                }
            });

        return $updated;
    }
}
