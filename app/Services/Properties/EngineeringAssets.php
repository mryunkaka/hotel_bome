<?php

namespace App\Services\Properties;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EngineeringAssets
{
    /** @param array<string, mixed> $data */
    public function create(int $propertyId, array $data, User $actor): int
    {
        return DB::transaction(function () use ($propertyId, $data, $actor): int {
            $this->authorize($propertyId, $actor);
            $now = now();
            $assetId = DB::table('engineering_assets')->insertGetId([
                'property_id' => $propertyId, 'created_by' => $actor->id,
                'code' => strtoupper($data['code']), 'name' => $data['name'], 'category' => $data['category'],
                'location' => $data['location'] ?? null, 'serial_number' => $data['serial_number'] ?? null,
                'status' => $data['status'], 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
            $this->event($propertyId, $assetId, $actor, 'created', null, $this->snapshot($assetId));

            return $assetId;
        });
    }

    /** @param array<string, mixed> $data */
    public function update(int $propertyId, int $assetId, array $data, User $actor): void
    {
        DB::transaction(function () use ($propertyId, $assetId, $data, $actor): void {
            $this->authorize($propertyId, $actor);
            $asset = $this->asset($propertyId, $assetId, true);
            abort_unless((bool) $asset->is_active, 404);
            $before = $this->snapshot($assetId);
            DB::table('engineering_assets')->where('property_id', $propertyId)->where('id', $assetId)->update([
                'code' => strtoupper($data['code']), 'name' => $data['name'], 'category' => $data['category'],
                'location' => $data['location'] ?? null, 'serial_number' => $data['serial_number'] ?? null,
                'status' => $data['status'], 'updated_at' => now(),
            ]);
            $this->event($propertyId, $assetId, $actor, 'updated', $before, $this->snapshot($assetId));
        });
    }

    public function deactivate(int $propertyId, int $assetId, string $reason, User $actor): void
    {
        DB::transaction(function () use ($propertyId, $assetId, $reason, $actor): void {
            $this->authorize($propertyId, $actor);
            $asset = $this->asset($propertyId, $assetId, true);
            abort_unless((bool) $asset->is_active, 404);
            $before = $this->snapshot($assetId);
            DB::table('engineering_assets')->where('property_id', $propertyId)->where('id', $assetId)->update(['is_active' => false, 'updated_at' => now()]);
            $this->event($propertyId, $assetId, $actor, 'deactivated', $before, $this->snapshot($assetId), $reason);
        });
    }

    /** @return object{id: int, property_id: int, is_active: bool|int} */
    public function asset(int $propertyId, int $assetId, bool $lock = false): object
    {
        $query = DB::table('engineering_assets')->where('property_id', $propertyId)->where('id', $assetId);
        if ($lock) {
            $query->lockForUpdate();
        }
        $asset = $query->first();
        if ($asset === null) {
            throw new NotFoundHttpException;
        }

        /** @var object{id: int, property_id: int, is_active: bool|int} $asset */
        return $asset;
    }

    private function authorize(int $propertyId, User $actor): void
    {
        abort_unless(DB::table('properties')->where('id', $propertyId)->where('is_active', true)->exists(), 404);
        if ($actor->is_platform_admin) {
            return;
        }
        abort_unless(DB::table('property_user')->join('users', 'users.id', '=', 'property_user.user_id')
            ->where('property_user.property_id', $propertyId)->where('property_user.user_id', $actor->id)
            ->where('property_user.role', 'operations')->where('users.status', 'active')->exists(), 404);
    }

    /** @return array<string, mixed> */
    private function snapshot(int $assetId): array
    {
        $asset = (array) DB::table('engineering_assets')->where('id', $assetId)->first();
        unset($asset['created_at'], $asset['updated_at']);

        return $asset;
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>  $after
     */
    private function event(int $propertyId, int $assetId, User $actor, string $type, ?array $before, array $after, ?string $reason = null): void
    {
        DB::table('engineering_asset_events')->insert([
            'property_id' => $propertyId, 'asset_id' => $assetId, 'actor_id' => $actor->id,
            'event_type' => $type, 'before_data' => $before ? json_encode($before, JSON_THROW_ON_ERROR) : null,
            'after_data' => json_encode($after, JSON_THROW_ON_ERROR), 'reason' => $reason, 'created_at' => now(),
        ]);
    }
}
