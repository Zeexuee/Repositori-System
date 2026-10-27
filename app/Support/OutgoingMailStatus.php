<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Single source of truth for outgoing mail statuses and their allowed transitions.
 *
 * Standard operating procedure:
 *   READY_TO_DELIVERY -> RECEIVE (completing signature workflow), WAITING_RECEIVE (saving progress), REVISI
 *   WAITING_RECEIVE   -> RECEIVE (only by completing the signature workflow), REVISI
 *   REVISI            -> READY_TO_DELIVERY, WAITING_RECEIVE
 *   RECEIVE           -> final state, no further transition
 */
final class OutgoingMailStatus
{
    public const READY_TO_DELIVERY = 'READY_TO_DELIVERY';

    public const WAITING_RECEIVE = 'WAITING_RECEIVE';

    public const REVISI = 'REVISI';

    public const RECEIVE = 'RECEIVE';

    /**
     * Legacy values that must be interpreted as a current status.
     *
     * @var array<string, string>
     */
    private const ALIASES = [
        'READY_TO_DELIVERY' => self::READY_TO_DELIVERY,
        'READY TO DELIVERY' => self::READY_TO_DELIVERY,
        'READY_DELIVERY' => self::READY_TO_DELIVERY,
        'READY DELIVERY' => self::READY_TO_DELIVERY,
        'WAITING_RETURN' => self::WAITING_RECEIVE,
        'WAITING RETURN' => self::WAITING_RECEIVE,
        'WAITING_RECEIVE' => self::WAITING_RECEIVE,
        'WAITING RECEIVE' => self::WAITING_RECEIVE,
        'RECEIVED' => self::RECEIVE,
        'RETURN' => self::WAITING_RECEIVE,
        'RETURNED' => self::WAITING_RECEIVE,
        'PROGRESS' => self::WAITING_RECEIVE,
        'IN_PROGRESS' => self::WAITING_RECEIVE,
        'PENDING' => self::READY_TO_DELIVERY,
        'PROGRES' => self::WAITING_RECEIVE,
        'APPROVED' => self::RECEIVE,
        'SIGNED' => self::RECEIVE,
    ];

    /**
     * Allowed transitions per current status.
     *
     * @var array<string, array<int, string>>
     */
    private const TRANSITIONS = [
        self::READY_TO_DELIVERY => [self::RECEIVE, self::WAITING_RECEIVE, self::REVISI],
        self::WAITING_RECEIVE => [self::RECEIVE, self::READY_TO_DELIVERY, self::REVISI],
        self::REVISI => [self::READY_TO_DELIVERY, self::WAITING_RECEIVE],
        self::RECEIVE => [],
    ];

    /**
     * Human readable labels used across the interface.
     *
     * @var array<string, string>
     */
    private const LABELS = [
        self::READY_TO_DELIVERY => 'READY TO DELIVERY',
        self::WAITING_RECEIVE => 'WAITING RECEIVE',
        self::REVISI => 'REVISI',
        self::RECEIVE => 'RECEIVE',
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
     * Whether the receive signature workflow may be started or continued.
     */
    public static function canProcessReceive(?string $from): bool
    {
        $canonical = self::canonical($from);

        return $canonical === self::READY_TO_DELIVERY
            || $canonical === self::WAITING_RECEIVE
            || self::canTransition($canonical, self::RECEIVE);
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
        return [
            self::READY_TO_DELIVERY => [
                ['value' => self::RECEIVE, 'label' => self::LABELS[self::RECEIVE]],
                ['value' => self::REVISI, 'label' => self::LABELS[self::REVISI]],
            ],
            self::WAITING_RECEIVE => [
                ['value' => self::RECEIVE, 'label' => self::LABELS[self::RECEIVE]],
                ['value' => self::REVISI, 'label' => self::LABELS[self::REVISI]],
            ],
            self::REVISI => [
                ['value' => self::READY_TO_DELIVERY, 'label' => self::LABELS[self::READY_TO_DELIVERY]],
            ],
            self::RECEIVE => [],
        ];
    }
}
