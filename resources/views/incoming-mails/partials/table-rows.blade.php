@forelse ($incomingMails as $index => $mail)
    <tr class="row-item hover:bg-slate-50/80 transition-colors" data-selected="false">
        <!-- Select Box Column Cell -->
        <td x-show="isSelectionMode" x-cloak class="py-4 px-3 text-center whitespace-nowrap">
            <input type="checkbox"
                value="{{ $mail->id }}"
                aria-label="Pilih dokumen {{ $mail->mail_number }}"
                onclick="window.toggleMailSelection('{{ $mail->id }}')"
                class="row-checkbox w-4 h-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 cursor-pointer">
        </td>
        <td class="py-4 px-4 sm:px-5 text-center font-bold text-slate-500 whitespace-nowrap">
            {{ ($incomingMails->firstItem() ?? 1) + $index }}
        </td>
        <td class="py-4 px-4 sm:px-5 font-bold text-slate-900 font-mono whitespace-nowrap">
            {{ $mail->mail_number }}
        </td>
        <td class="py-4 px-4 sm:px-5 text-slate-700 font-mono whitespace-nowrap">
            {{ $mail->received_date?->format('d/m/Y H:i') ?? '-' }}
        </td>
        <td class="py-4 px-4 sm:px-5 font-medium text-slate-800 whitespace-nowrap">
            {{ $mail->sender }}
        </td>
        <td class="py-4 px-4 sm:px-5 text-slate-700 whitespace-nowrap">
            {{ $mail->recipient ?? '-' }}
        </td>
        <td class="py-4 px-4 sm:px-5 text-center whitespace-nowrap">
            @php
                $canonicalStatus = App\Support\IncomingMailStatus::canonical($mail->status);
                $displayStatus = App\Support\IncomingMailStatus::label($mail->status);
                $isFinalStatus = App\Support\IncomingMailStatus::allowedTransitions($canonicalStatus) === [];
                $badgeClasses = match ($canonicalStatus) {
                    App\Support\IncomingMailStatus::RECEIVE => 'bg-slate-900 text-white border-slate-900 hover:bg-slate-800',
                    App\Support\IncomingMailStatus::REVISI => 'bg-amber-50 text-amber-800 border-amber-300 hover:bg-amber-100 font-bold',
                    App\Support\IncomingMailStatus::WAITING_RETURN => 'bg-blue-50 text-blue-800 border-blue-300 hover:bg-blue-100 font-bold',
                    App\Support\IncomingMailStatus::RETURN => 'bg-white text-slate-800 border-slate-300 hover:bg-slate-50 font-bold',
                    App\Support\IncomingMailStatus::DRAFT => 'bg-indigo-50 text-indigo-800 border-indigo-300 hover:bg-indigo-100 font-bold',
                    App\Support\IncomingMailStatus::DISPOSISI => 'bg-slate-200 text-slate-900 border-slate-400 hover:bg-slate-300 font-bold',
                    default => 'bg-slate-100 text-slate-700 border-slate-300',
                };
            @endphp

            @if (auth()->user()?->can('update', $mail) && ! $isFinalStatus)
                @if ($canonicalStatus === App\Support\IncomingMailStatus::WAITING_RETURN)
                    <button type="button"
                        @click="window.resumeReturnWorkflow(@js((string) $mail->id), @js((string) $mail->mail_number))"
                        title="Lanjutkan pengisian tanda tangan pengembalian"
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border cursor-pointer transition-all shadow-2xs {{ $badgeClasses }}">
                        <span>{{ $displayStatus }}</span>
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                @else
                    <button type="button"
                        @click="window.openStatusDropdown($event, @js((string) $mail->id), @js((string) $mail->mail_number), @js((string) $displayStatus))"
                        aria-haspopup="menu"
                        title="Klik untuk mengubah status dokumen"
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border cursor-pointer transition-all shadow-2xs {{ $badgeClasses }}">
                        <span>{{ $displayStatus }}</span>
                        <svg class="w-3 h-3 opacity-70 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                @endif
            @else
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border {{ $badgeClasses }}"
                    @if ($isFinalStatus) title="{{ $displayStatus }} adalah status akhir dan tidak dapat diubah lagi" @endif>
                    {{ $displayStatus }}
                </span>
            @endif
        </td>
        <td class="py-4 px-4 sm:px-5 text-slate-800 min-w-[280px] max-w-xl break-words leading-relaxed" title="{{ $mail->subject }}">
            {{ $mail->subject }}
        </td>
        <td class="py-4 px-4 sm:px-5 text-slate-600 font-mono whitespace-nowrap">
            {{ $mail->outgoing_date?->format('d/m/Y H:i') ?? '-' }}
        </td>
        <td class="py-4 px-4 sm:px-5 text-slate-600 min-w-[200px] max-w-md break-words leading-relaxed" title="{{ $mail->disposition_note }}">
            {{ $mail->disposition_note ?? '-' }}
        </td>
        <td class="py-4 px-4 sm:px-5 text-right space-x-1.5 whitespace-nowrap">
            <a href="{{ route('incoming-mails.show', $mail) }}"
                class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-900 rounded-lg font-semibold text-xs transition-all border border-slate-200">
                Detail
            </a>
            @can('update', $mail)
                @if($mail->status === 'DRAFT')
                    <a href="{{ route('incoming-mails.edit', $mail) }}"
                        class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg text-xs transition-all shadow-xs border border-indigo-600">
                        Lanjutkan Draft
                    </a>
                @else
                    <a href="{{ route('incoming-mails.edit', $mail) }}"
                        class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-900 font-semibold rounded-lg text-xs transition-all border border-slate-200">
                        Edit
                    </a>
                @endif
            @endcan
        </td>
    </tr>
@empty
    <tr>
        <td colspan="12" class="p-8 text-center text-slate-500 italic text-sm">
            Tidak ada data surat masuk yang sesuai dengan pencarian.
        </td>
    </tr>
@endforelse
