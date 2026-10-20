<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Normalise role names that were previously stored as "Staf" and "Direksi".
 *
 * The application now relies on the canonical machine names "staff" and
 * "master". Without this migration, users created through the user
 * management screen would keep an unrecognised role and lose all
 * Direksi-only privileges.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->mergeRole('Staf', 'staff');
        $this->mergeRole('Direksi', 'master');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Irreversible: the canonical role names are the supported state.
    }

    private function mergeRole(string $legacyName, string $canonicalName): void
    {
        $rolesTable = config('permission.table_names.roles', 'roles');
        $modelHasRolesTable = config('permission.table_names.model_has_roles', 'model_has_roles');
        $guard = config('auth.defaults.guard', 'web');

        $legacy = DB::table($rolesTable)
            ->where('guard_name', $guard)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($legacyName)])
            ->first();

        if ($legacy === null || mb_strtolower((string) $legacy->name) === mb_strtolower($canonicalName)) {
            return;
        }

        $canonical = DB::table($rolesTable)
            ->where('guard_name', $guard)
            ->where('name', $canonicalName)
            ->first();

        if ($canonical === null) {
            DB::table($rolesTable)->where('id', $legacy->id)->update([
                'name' => $canonicalName,
                'updated_at' => now(),
            ]);

            return;
        }

        // Both roles exist: move assignments to the canonical role, then drop the legacy one.
        $assignments = DB::table($modelHasRolesTable)->where('role_id', $legacy->id)->get();

        foreach ($assignments as $assignment) {
            $existingAssignment = DB::table($modelHasRolesTable)
                ->where('role_id', $canonical->id)
                ->where('model_type', $assignment->model_type)
                ->where('model_id', $assignment->model_id);

            if ($existingAssignment->exists()) {
                DB::table($modelHasRolesTable)->where('role_id', $legacy->id)
                    ->where('model_type', $assignment->model_type)
                    ->where('model_id', $assignment->model_id)
                    ->delete();
            } else {
                DB::table($modelHasRolesTable)->where('role_id', $legacy->id)
                    ->where('model_type', $assignment->model_type)
                    ->where('model_id', $assignment->model_id)
                    ->update(['role_id' => $canonical->id]);
            }
        }

        DB::table($rolesTable)->where('id', $legacy->id)->delete();
    }
};
