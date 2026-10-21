<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Single source of truth for incoming mail statuses and their allowed transitions.
 *
 * Standard operating procedure:
 *   DRAFT           -> RECEIVE, REVISI, RETURN
 *   RECEIVE         -> REVISI, RETURN
 *   REVISI          -> RECEIVE (revision must be resolved before returning)
 *   WAITING_RETURN  -> RETURN (only by completing the signature workflow)
 *   RETURN          -> REVISI
 *
 * The former DISPOSISI and WAITING_DISPOSISI statuses were merged into RETURN and
 * WAITING_RETURN. The disposition note itself is retained as an optional field that
 * the return workflow collects, so RETURN is the single terminal handover status.
 */
final class IncomingMailStatus
{
    public const DRAFT = 'DRAFT';

    public const RECEIVE = 'RECEIVE';

    public const REVISI = 'REVISI';

    public const WAITING_RETURN = 'WAITING_RETURN';

    public const RETURN = 'RETURN';

    /**
     * Legacy values that must be interpreted as a current status.
     *
     * The disposition family collapses into the return family.
     *
     * @var array<string, string>
     */
    private const ALIASES = [
        'RECEIVED' => self::RECEIVE,
        'REGISTERED' => self::RECEIVE,
        'RETURNED' => self::RETURN,
        'WAITING RETURN' => self::WAITING_RETURN,
        'DISPOSISI' => self::RETURN,
        'DISPOSITION' => self::RETURN,
        'DISPATCHED' => self::RETURN,
        'WAITING_DISPOSISI' => self::WAITING_RETURN,
        'WAITING DISPOSISI' => self::WAITING_RETURN,
        'WAITING_DISPOSITION' => self::WAITING_RETURN,
        'WAITING DISPOSITION' => self::WAITING_RETURN,
        'PENDING_DISPOSITION' => self::WAITING_RETURN,
        'PROGRES' => self::DRAFT,
        'PROGRESS' => self::DRAFT,
        'IN_PROGRESS' => self::DRAFT,
        'PENDING' => self::DRAFT,
    ];

    /**
     * Allowed transitions per current status.
     *
     * @var array<string, array<int, string>>
     */
    private const TRANSITIONS = [
        self::DRAFT => [self::RECEIVE, self::REVISI, self::RETURN],
        self::RECEIVE => [self::REVISI, self::RETURN],
        self::REVISI => [self::RECEIVE],
        self::WAITING_RETURN => [self::RETURN],
        self::RETURN => [self::REVISI],
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
     * Statuses an operator may set directly, without a signature workflow.
     *
     * @return array<int, string>
     */
    public static function manuallyAssignable(): array
    {
        return array_values(array_diff(
            self::all(),
            [self::WAITING_RETURN, self::RETURN]
        ));
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
     *
     * This also covers what used to be the disposition workflow.
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
