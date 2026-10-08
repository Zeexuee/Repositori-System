<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

final class OutgoingMailStatus
{
    public const WAITING_RETURN = 'WAITING_RETURN';
    public const RETURN = 'RETURN';
    public const PROGRES = 'PROGRES';
    public const REVISI = 'REVISI';
    public const APPROVED = 'APPROVED';
    public const RECEIVE = 'RECEIVE';
    public const SIGNED = 'SIGNED';

    private const ALIASES = [
        'RECEIVED' => self::RECEIVE,
        'PROGRESS' => self::PROGRES,
        'IN_PROGRESS' => self::PROGRES,
        'PENDING' => self::PROGRES,
        'RETURNED' => self::RETURN,
        'WAITING RETURN' => self::WAITING_RETURN,
    ];

    private const TRANSITIONS = [
        self::WAITING_RETURN => [self::RETURN, self::PROGRES, self::REVISI, self::RECEIVE, self::APPROVED],
        self::RETURN => [self::PROGRES, self::REVISI, self::RECEIVE, self::WAITING_RETURN],
        self::PROGRES => [self::APPROVED, self::RECEIVE, self::REVISI, self::RETURN],
        self::REVISI => [self::PROGRES, self::RETURN, self::RECEIVE],
        self::APPROVED => [self::SIGNED, self::RECEIVE, self::PROGRES],
        self::SIGNED => [self::RECEIVE, self::PROGRES],
        self::RECEIVE => [self::PROGRES, self::REVISI],
    ];

    private const LABELS = [
        self::WAITING_RETURN => 'WAITING RETURN',
        self::RETURN => 'RETURN',
        self::PROGRES => 'PROGRES',
        self::REVISI => 'REVISI',
        self::APPROVED => 'APPROVED',
        self::RECEIVE => 'RECEIVE',
        self::SIGNED => 'SIGNED',
    ];

    public static function all(): array
    {
        return array_keys(self::TRANSITIONS);
    }

    public static function canonical(?string $status): ?string
    {
        $normalized = Str::upper(trim((string) $status));
        if ($normalized === '') return null;
        $normalized = self::ALIASES[$normalized] ?? $normalized;
        return isset(self::TRANSITIONS[$normalized]) ? $normalized : null;
    }

    public static function allowedTransitions(?string $from): array
    {
        $canonical = self::canonical($from);
        return $canonical === null ? [] : self::TRANSITIONS[$canonical];
    }

    public static function canTransition(?string $from, ?string $to): bool
    {
        $target = self::canonical($to);
        return $target !== null && in_array($target, self::allowedTransitions($from), true);
    }

    public static function label(?string $status): string
    {
        $canonical = self::canonical($status);
        return $canonical === null ? Str::upper(trim((string) $status)) : self::LABELS[$canonical];
    }

    public static function transitionMap(): array
    {
        $map = [];

        foreach (self::TRANSITIONS as $from => $targets) {
            $map[$from] = array_map(
                static fn (string $target): array => [
                    'value' => $target,
                    'label' => self::LABELS[$target],
                ],
                $targets
            );
        }

        return $map;
    }
}
