<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\IncomingMail;
use App\Models\OutgoingMail;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RepositoryController extends Controller
{
    /**
     * Display a listing of repository document archives grouped by month.
     */
    public function index(Request $request): View|JsonResponse
    {
        $validated = $request->validate([
            'month' => ['nullable', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'search' => ['nullable', 'string', 'max:200'],
            'sender' => ['nullable', 'string', 'max:255'],
            'recipient' => ['nullable', 'string', 'max:255'],
        ]);

        $selectedMonth = trim((string) ($validated['month'] ?? '')) ?: null;
        $search = trim((string) ($validated['search'] ?? ''));
        $sender = trim((string) ($validated['sender'] ?? ''));
        $recipient = trim((string) ($validated['recipient'] ?? ''));

        $sendersList = collect();
        $recipientsList = collect();
        $activeMonthData = null;
        $monthsData = [];

        if ($selectedMonth !== null) {
            // Restrict archive queries to the selected month for performance.
            [$year, $mNum] = array_map('intval', explode('-', $selectedMonth));

            // Populate autocomplete lists for the filter dropdowns in selected month.
            $sendersList = IncomingMail::whereYear('received_date', $year)
                ->whereMonth('received_date', $mNum)
                ->whereNotNull('sender')
                ->distinct()
                ->pluck('sender')
                ->filter()
                ->values();

            $recipientsList = IncomingMail::whereYear('received_date', $year)
                ->whereMonth('received_date', $mNum)
                ->whereNotNull('recipient')
                ->distinct()
                ->pluck('recipient')
                ->merge(
                    OutgoingMail::whereYear('created_at', $year)
                        ->whereMonth('created_at', $mNum)
                        ->whereNotNull('recipient')
                        ->distinct()
                        ->pluck('recipient')
                )
                ->filter()
                ->unique()
                ->values();

            $incomingQuery = IncomingMail::whereYear('received_date', $year)
                ->whereMonth('received_date', $mNum);

            if ($search !== '') {
                $incomingQuery->where(function ($query) use ($search) {
                    $query->where('mail_number', 'like', "%{$search}%")
                        ->orWhere('received_date', 'like', "%{$search}%")
                        ->orWhere('sender', 'like', "%{$search}%")
                        ->orWhere('recipient', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%")
                        ->orWhere('outgoing_date', 'like', "%{$search}%")
                        ->orWhere('disposition_note', 'like', "%{$search}%")
                        ->orWhere('recipient_name', 'like', "%{$search}%")
                        ->orWhere('receipt_number', 'like', "%{$search}%");
                });
            }

            if ($sender !== '') {
                $incomingQuery->where('sender', 'like', "%{$sender}%");
            }

            if ($recipient !== '') {
                $incomingQuery->where('recipient', 'like', "%{$recipient}%");
            }

            $incomingMails = $incomingQuery->latest('received_date')->get();

            $outgoingQuery = OutgoingMail::with('creator')
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $mNum);

            if ($search !== '') {
                $outgoingQuery->where(function ($query) use ($search) {
                    $query->where('mail_number', 'like', "%{$search}%")
                        ->orWhere('created_at', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%")
                        ->orWhere('disposition_note', 'like', "%{$search}%")
                        ->orWhere('recipient', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhereHas('creator', function ($creatorQuery) use ($search) {
                            $creatorQuery->where('name', 'like', "%{$search}%");
                        });
                });
            }

            if ($sender !== '') {
                $outgoingQuery->whereHas('creator', function ($query) use ($sender) {
                    $query->where('name', 'like', "%{$sender}%");
                });
            }

            if ($recipient !== '') {
                $outgoingQuery->where('recipient', 'like', "%{$recipient}%");
            }

            $outgoingMails = $outgoingQuery->latest('created_at')->get();

            $label = Carbon::createFromDate($year, $mNum, 1)->translatedFormat('F Y');

            $activeMonthData = [
                'key' => $selectedMonth,
                'label' => $label,
                'year' => (string) $year,
                'month_num' => sprintf('%02d', $mNum),
                'incoming_count' => $incomingMails->count(),
                'outgoing_count' => $outgoingMails->count(),
                'total_count' => $incomingMails->count() + $outgoingMails->count(),
                'incoming' => $incomingMails,
                'outgoing' => $outgoingMails,
            ];

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'html' => view('repository.partials.tables', compact('activeMonthData'))->render(),
                    'total' => $activeMonthData['total_count'],
                ]);
            }
        } else {
            // Build monthly summary cards for the archive overview.
            $incomingMails = IncomingMail::latest('received_date')->get();
            $outgoingMails = OutgoingMail::latest('created_at')->get();

            foreach ($incomingMails as $mail) {
                $date = $mail->received_date ?? $mail->created_at;
                if (! $date) {
                    continue;
                }
                $key = $date->format('Y-m');

                if (! isset($monthsData[$key])) {
                    $monthsData[$key] = [
                        'key' => $key,
                        'label' => Carbon::parse($key.'-01')->translatedFormat('F Y'),
                        'year' => $date->format('Y'),
                        'month_num' => $date->format('m'),
                        'incoming_count' => 0,
                        'outgoing_count' => 0,
                        'total_count' => 0,
                    ];
                }

                $monthsData[$key]['incoming_count']++;
                $monthsData[$key]['total_count']++;
            }

            foreach ($outgoingMails as $mail) {
                $date = $mail->created_at;
                if (! $date) {
                    continue;
                }
                $key = $date->format('Y-m');

                if (! isset($monthsData[$key])) {
                    $monthsData[$key] = [
                        'key' => $key,
                        'label' => Carbon::parse($key.'-01')->translatedFormat('F Y'),
                        'year' => $date->format('Y'),
                        'month_num' => $date->format('m'),
                        'incoming_count' => 0,
                        'outgoing_count' => 0,
                        'total_count' => 0,
                    ];
                }

                $monthsData[$key]['outgoing_count']++;
                $monthsData[$key]['total_count']++;
            }

            krsort($monthsData);

            if (empty($monthsData)) {
                $currentKey = now()->format('Y-m');
                $monthsData[$currentKey] = [
                    'key' => $currentKey,
                    'label' => now()->translatedFormat('F Y'),
                    'year' => now()->format('Y'),
                    'month_num' => now()->format('m'),
                    'incoming_count' => 0,
                    'outgoing_count' => 0,
                    'total_count' => 0,
                ];
            }
        }

        return view('repository.index', [
            'monthsData' => $monthsData,
            'selectedMonth' => $selectedMonth,
            'activeMonthData' => $activeMonthData,
            'sendersList' => $sendersList,
            'recipientsList' => $recipientsList,
        ]);
    }
}
