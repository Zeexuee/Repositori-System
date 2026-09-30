<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Single source of truth for incoming mail statuses and their allowed transitions.
 *
 * Standard operating procedure:
 *   DRAFT           -> RECEIVE, REVISI, DISPOSISI
 *   RECEIVE         -> REVISI, RETURN, DISPOSISI
 *   REVISI          -> RECEIVE, DISPOSISI
 *   WAITING_RETURN  -> RETURN (only by completing the signature workflow)
 *   RETURN          -> REVISI, DISPOSISI
 *   DISPOSISI       -> final state, no further transition
 */
final class IncomingMailStatus
{
    public const DRAFT = 'DRAFT';

    public const RECEIVE = 'RECEIVE';

    public const REVISI = 'REVISI';

    public const WAITING_RETURN = 'WAITING_RETURN';

    public const RETURN = 'RETURN';

    public const DISPOSISI = 'DISPOSISI';

    /**
     * Legacy values that must be interpreted as a current status.
     *
     * @var array<string, string>
     */
    private const ALIASES = [
        'RECEIVED' => self::RECEIVE,
        'REGISTERED' => self::RECEIVE,
        'RETURNED' => self::RETURN,
        'WAITING RETURN' => self::WAITING_RETURN,
        'PROGRES' => self::DRAFT,
        'PROGRESS' => self::DRAFT,
        'IN_PROGRESS' => self::DRAFT,
        'PENDING' => self::DRAFT,
        'PENDING_DISPOSITION' => self::DISPOSISI,
    ];

    /**
     * Allowed transitions per current status.
     *
     * @var array<string, array<int, string>>
     */
    private const TRANSITIONS = [
        self::DRAFT => [self::RECEIVE, self::REVISI, self::DISPOSISI],
        self::RECEIVE => [self::REVISI, self::RETURN, self::DISPOSISI],
        self::REVISI => [self::RECEIVE, self::DISPOSISI],
        self::WAITING_RETURN => [self::RETURN],
        self::RETURN => [self::REVISI, self::DISPOSISI],
        self::DISPOSISI => [],
    ];

    /**
     * Human readable labels used across the interface.
     *
     * @var array<string, string>
     */
    private const LABELS = [
        self::DRAFT => 'DRAFT',
        self::RECEIVE => 'RECEIVE',
        self::REVISI => 'REVISI',
        self::WAITING_RETURN => 'WAITING RETURN',
        self::RETURN => 'RETURN',
        self::DISPOSISI => 'DISPOSISI',
    ];

    /**
     * All statuses a document can currently hold.
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        return array_keys(self::TRANSITIONS);
    }

    /**
     * Normalize any stored or submitted value into a supported status.
     */
    public static function canonical(?string $status): ?string
    {
        $normalized = Str::upper(trim((string) $status));

        if ($normalized === '') {
            return null;
        }

        $normalized = self::ALIASES[$normalized] ?? $normalized;

        return isset(self::TRANSITIONS[$normalized]) ? $normalized : null;
    }

    /**
     * Statuses reachable from the given status.
     *
     * @return array<int, string>
     */
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

    /**
     * Whether the return signature workflow may be started or continued.
     */
    public static function canProcessReturn(?string $from): bool
    {
        $canonical = self::canonical($from);

        return $canonical === self::WAITING_RETURN || self::canTransition($canonical, self::RETURN);
    }

    public static function label(?string $status): string
    {
        $canonical = self::canonical($status);

        return $canonical === null
            ? Str::upper(trim((string) $status))
            : self::LABELS[$canonical];
    }

    /**
     * Transition map keyed by status, for use by the interface.
     *
     * @return array<string, array<int, array{value: string, label: string}>>
     */
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
