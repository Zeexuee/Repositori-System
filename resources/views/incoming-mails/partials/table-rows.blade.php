@forelse ($incomingMails as $index => $mail)
    <tr class="row-item hover:bg-slate-50/80 transition-colors" data-selected="false">
        <!-- Select Box Column Cell -->
        <td x-show="isSelectionMode" x-cloak class="py-4 px-3 text-center whitespace-nowrap">
            <input type="checkbox"
                value="{{ $mail->id }}"
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
            {{ $mail->received_date?->format('d/m/Y') ?? '-' }}
        </td>
        <td class="py-4 px-4 sm:px-5 font-medium text-slate-800 whitespace-nowrap">
            {{ $mail->sender }}
        </td>
        <td class="py-4 px-4 sm:px-5 text-slate-700 whitespace-nowrap">
            {{ $mail->recipient ?? '-' }}
        </td>
        <td class="py-4 px-4 sm:px-5 text-center whitespace-nowrap">
            @php
                $badgeClasses = match ($mail->status) {
                    'RECEIVE', 'RECEIVED' => 'bg-slate-900 text-white border-slate-900 hover:bg-slate-800',
                    'PROGRES', 'PROGRESS', 'IN_PROGRESS', 'PENDING' => 'bg-slate-200 text-slate-900 border-slate-400 hover:bg-slate-300 font-bold',
                    'REVISI' => 'bg-amber-50 text-amber-800 border-amber-300 hover:bg-amber-100 font-bold',
                    'RETURN', 'RETURNED' => 'bg-white text-slate-800 border-slate-300 hover:bg-slate-50 font-bold',
                    default => 'bg-slate-100 text-slate-700 border-slate-300',
                };
                $displayStatus = match ($mail->status) {
                    'RECEIVED' => 'RECEIVE',
                    'PROGRESS', 'IN_PROGRESS', 'PENDING' => 'PROGRES',
                    'RETURNED' => 'RETURN',
                    default => $mail->status,
                };
            @endphp

            @if (auth()->user()?->can('update', $mail))
                <button type="button"
                    onclick="window.openStatusDropdown(event, '{{ $mail->id }}', '{{ addslashes($mail->mail_number) }}', '{{ $displayStatus }}')"
                    title="Klik untuk mengubah status dokumen" 
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border cursor-pointer transition-all shadow-2xs {{ $badgeClasses }}">
                    <span>{{ $displayStatus }}</span>
                    <svg class="w-3 h-3 opacity-70 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
            @else
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border {{ $badgeClasses }}">
                    {{ $displayStatus }}
                </span>
            @endif
        </td>
        <td class="py-4 px-4 sm:px-5 text-slate-800 min-w-[280px] max-w-xl break-words leading-relaxed" title="{{ $mail->subject }}">
            {{ $mail->subject }}
        </td>
        <td class="py-4 px-4 sm:px-5 text-slate-600 font-mono whitespace-nowrap">
            {{ $mail->outgoing_date?->format('d/m/Y') ?? '-' }}
        </td>
        <td class="py-4 px-4 sm:px-5 text-slate-600 min-w-[200px] max-w-md break-words leading-relaxed" title="{{ $mail->disposition_note }}">
            {{ $mail->disposition_note ?? '-' }}
        </td>
        <td class="py-4 px-4 sm:px-5 text-slate-700 font-medium whitespace-nowrap">
            {{ $mail->recipient_name ?? '-' }}
        </td>
        <td class="py-4 px-4 sm:px-5 text-right space-x-1.5 whitespace-nowrap">
            <a href="{{ route('incoming-mails.show', $mail) }}"
                class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-900 rounded-lg font-semibold text-xs transition-all border border-slate-200">
                Detail
            </a>
            @can('update', $mail)
                <a href="{{ route('incoming-mails.edit', $mail) }}"
                    class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-900 font-semibold rounded-lg text-xs transition-all border border-slate-200">
                    Edit
                </a>
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
