<?php

namespace App\Http\Controllers\Setup;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HealthController
{
    private const REQUIRED_SCHEMA_TABLES = [
        'users',
        'installation_bootstrap',
        'properties',
        'outlets',
        'room_types',
        'rooms',
        'room_status_events',
        'room_blocks',
        'room_block_cancellations',
        'property_user',
        'user_access_events',
        'property_membership_events',
        'user_account_events',
        'passkeys',
    ];

    public function __invoke(): JsonResponse
    {
        $database = $this->databaseIsAvailable();
        $storage = is_writable(storage_path('framework'));
        $schema = $database && $this->schemaIsAvailable();
        $healthy = $database && $storage && $schema;

        return response()->json([
            'application' => 'ok',
            'database' => $database ? 'ok' : 'unavailable',
            'storage' => $storage ? 'ok' : 'unavailable',
            'schema' => $schema ? 'ok' : 'unavailable',
        ], $healthy ? 200 : 503);
    }

    private function databaseIsAvailable(): bool
    {
        try {
            DB::select('SELECT 1');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function schemaIsAvailable(): bool
    {
        try {
            $requiredTables = self::REQUIRED_SCHEMA_TABLES;

            if (config('session.driver') === 'database') {
                $requiredTables[] = config('session.table', 'sessions');
            }

            foreach ($requiredTables as $table) {
                if (! Schema::hasTable($table)) {
                    return false;
                }
            }

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
