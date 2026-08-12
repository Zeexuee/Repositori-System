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

        // Fetch all incoming & outgoing mails
        $incomingMails = IncomingMail::latest('received_date')->get();
        $outgoingMails = OutgoingMail::latest('created_at')->get();

        // Group documents by YYYY-MM
        $monthsData = [];

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
                    'incoming' => collect(),
                    'outgoing' => collect(),
                ];
            }

            $monthsData[$key]['incoming_count']++;
            $monthsData[$key]['total_count']++;
            $monthsData[$key]['incoming']->push($mail);
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
                    'incoming' => collect(),
                    'outgoing' => collect(),
                ];
            }

            $monthsData[$key]['outgoing_count']++;
            $monthsData[$key]['total_count']++;
            $monthsData[$key]['outgoing']->push($mail);
        }

        // Sort months descending (latest month first)
        krsort($monthsData);

        // If no records exist, default current month card
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
                'incoming' => collect(),
                'outgoing' => collect(),
            ];
        }

        $activeMonthData = null;
        if ($selectedMonth && isset($monthsData[$selectedMonth])) {
            $activeMonthData = $monthsData[$selectedMonth];
        }

        return view('repository.index', [
            'monthsData' => $monthsData,
            'selectedMonth' => $selectedMonth,
            'activeMonthData' => $activeMonthData,
        ]);
    }
}
