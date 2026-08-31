<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\IncomingMail;
use App\Models\OutgoingMail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RepositoryController extends Controller
{
    /**
     * Display a listing of repository document archives grouped by month.
     */
    public function index(Request $request): View
    {
        $selectedMonth = $request->query('month');
        $search = $request->query('search');
        $sender = $request->query('sender');
        $recipient = $request->query('recipient');

        $sendersList = collect();
        $recipientsList = collect();
        $activeMonthData = null;
        $monthsData = [];

        if (! empty($selectedMonth) && preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
            // STAFF HAS SELECTED A SPECIFIC MONTH CARD (e.g. ?month=2026-08)
            // SQL queries strictly restricted to selected month for performance
            [$year, $mNum] = explode('-', $selectedMonth);
            $year = (int) $year;
            $mNum = (int) $mNum;

            // Populate autocomplete lists for the filter dropdowns in selected month
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

            // Query Incoming Mails for selected month only
            $incomingQuery = IncomingMail::whereYear('received_date', $year)
                ->whereMonth('received_date', $mNum);

            if (! empty($search)) {
                $incomingQuery->where(function ($q) use ($search) {
                    $q->where('mail_number', 'like', "%{$search}%")
                      ->orWhere('subject', 'like', "%{$search}%")
                      ->orWhere('disposition_note', 'like', "%{$search}%")
                      ->orWhere('recipient_name', 'like', "%{$search}%");
                });
            }
            if (! empty($sender)) {
                $incomingQuery->where('sender', 'like', "%{$sender}%");
            }
            if (! empty($recipient)) {
                $incomingQuery->where('recipient', 'like', "%{$recipient}%");
            }

            $incomingMails = $incomingQuery->latest('received_date')->get();

            // Query Outgoing Mails for selected month only
            $outgoingQuery = OutgoingMail::whereYear('created_at', $year)
                ->whereMonth('created_at', $mNum);

            if (! empty($search)) {
                $outgoingQuery->where(function ($q) use ($search) {
                    $q->where('mail_number', 'like', "%{$search}%")
                      ->orWhere('subject', 'like', "%{$search}%")
                      ->orWhere('notes', 'like', "%{$search}%");
                });
            }
            if (! empty($sender)) {
                $outgoingQuery->whereHas('creator', function ($q) use ($sender) {
                    $q->where('name', 'like', "%{$sender}%");
                });
            }
            if (! empty($recipient)) {
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
        } else {
            // MAIN OVERVIEW PAGE: Fetch all incoming & outgoing mails to build month summary cards
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
                        'label' => Carbon::parse($key . '-01')->translatedFormat('F Y'),
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
                        'label' => Carbon::parse($key . '-01')->translatedFormat('F Y'),
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
