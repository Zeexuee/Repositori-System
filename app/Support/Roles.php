<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Canonical role names used by Spatie Permission.
 *
 * The database stores the machine names below. The interface displays the
 * human readable labels in LABELS.
 */
final class Roles
{
    public const STAFF = 'staff';

    public const MASTER = 'master';

    /**
     * All roles recognised by the application.
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [self::STAFF, self::MASTER];
    }

    /**
     * Human readable labels keyed by role name.
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::STAFF => 'Staf',
            self::MASTER => 'Direksi',
        ];
    }

    /**
     * Resolve the display label for a stored role name.
     */
    public static function label(?string $role): string
    {
        return self::labels()[$role ?? ''] ?? (string) $role;
    }
}
