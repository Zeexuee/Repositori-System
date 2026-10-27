@forelse ($outgoingMails as $index => $mail)
    <tr class="hover:bg-slate-50/80 transition-colors">
        <!-- Select Box -->
        <td x-show="isSelectionMode" class="py-4 px-3 text-center whitespace-nowrap">
            <input type="checkbox"
                value="{{ $mail->id }}"
                x-model="selectedIds"
                @change="handleSelectionChange"
                class="w-4 h-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 cursor-pointer mail-checkbox">
        </td>
        
        <td class="py-4 px-4 sm:px-5 text-center font-bold text-slate-500 whitespace-nowrap">
            {{ ($outgoingMails->firstItem() ?? 1) + $index }}
        </td>
        <td class="py-4 px-4 sm:px-5 font-bold text-slate-900 font-mono whitespace-nowrap">
            {{ $mail->mail_number ?? '-' }}
        </td>
        <td class="py-4 px-4 sm:px-5 text-slate-700 font-mono whitespace-nowrap">
            {{ $mail->created_at?->format('d/m/Y H:i') ?? '-' }}
        </td>
        <td class="py-4 px-4 sm:px-5 text-slate-800 font-medium min-w-[320px] max-w-2xl break-words leading-relaxed" title="{{ $mail->subject }}">
            {{ $mail->subject }}
        </td>
        <td class="py-4 px-4 sm:px-5 text-slate-700 font-medium whitespace-nowrap">{{ $mail->recipient }}</td>
        <td class="py-4 px-4 sm:px-5 text-slate-600 font-medium whitespace-nowrap">{{ $mail->creator?->name ?? 'System' }}</td>
        <td class="py-4 px-4 sm:px-5 text-center whitespace-nowrap">
            @php
                $canonicalStatus = App\Support\OutgoingMailStatus::canonical($mail->status) ?? $mail->status;
                $hasReturnProgress = $mail->returnRecipients && $mail->returnRecipients->isNotEmpty();
                if ($canonicalStatus === App\Support\OutgoingMailStatus::WAITING_RECEIVE && ! $hasReturnProgress) {
                    $canonicalStatus = App\Support\OutgoingMailStatus::READY_TO_DELIVERY;
                }
                $badgeClasses = match ($canonicalStatus) {
                    'RECEIVE' => 'bg-slate-900 text-white border-slate-900 hover:bg-slate-800',
                    'REVISI' => 'bg-slate-100 text-slate-800 border-slate-400 hover:bg-slate-200 font-bold',
                    'READY_TO_DELIVERY' => 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50 font-bold',
                    'WAITING_RECEIVE' => 'bg-blue-50 text-blue-800 border-blue-300 hover:bg-blue-100 font-bold',
                    default => 'bg-slate-100 text-slate-700 border-slate-300',
                };
                $displayStatus = App\Support\OutgoingMailStatus::label($canonicalStatus);
            @endphp

            @if (auth()->user()?->can('update', $mail))
                @if ($canonicalStatus === App\Support\OutgoingMailStatus::WAITING_RECEIVE || ($canonicalStatus !== App\Support\OutgoingMailStatus::RECEIVE && $hasReturnProgress))
                    <button type="button"
                        onclick="window.resumeReturnWorkflow('{{ $mail->id }}', '{{ addslashes($mail->mail_number ?? '-') }}')"
                        title="Lanjutkan pengisian tanda tangan penerimaan"
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border cursor-pointer transition-all shadow-2xs {{ $badgeClasses }}">
                        <span>{{ $displayStatus }}</span>
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                @else
                    <button type="button"
                        onclick="window.openStatusDropdown(event, '{{ $mail->id }}', '{{ addslashes($mail->mail_number ?? '-') }}', '{{ $canonicalStatus }}')"
                        title="Klik untuk mengubah status surat"
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border cursor-pointer transition-all shadow-2xs {{ $badgeClasses }}">
                        <span>{{ $displayStatus }}</span>
                        <svg class="w-3 h-3 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                @endif
            @else
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border {{ $badgeClasses }}">
                    {{ $displayStatus }}
                </span>
            @endif
        </td>
        <td class="py-4 px-4 sm:px-5 text-right space-x-2 whitespace-nowrap">
            <a href="{{ route('outgoing-mails.show', $mail) }}"
                class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-900 border border-slate-200 rounded-lg font-semibold text-xs transition-all shadow-2xs">
                Detail
            </a>
            @can('update', $mail)
                <button type="button"
                    @click="window.openUploadAttachmentModal(@js((string) $mail->id), @js((string) $mail->mail_number), @js((string) $mail->subject))"
                    class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-900 font-semibold rounded-lg text-xs transition-all border border-slate-200 cursor-pointer">
                    Upload Gambar
                </button>
                <a href="{{ route('outgoing-mails.edit', $mail) }}"
                    class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-900 border border-slate-200 rounded-lg font-semibold text-xs transition-all shadow-2xs">
                    Edit
                </a>
            @endcan
        </td>
    </tr>
@empty
    <tr>
        <td colspan="9" class="p-8 text-center text-slate-500 italic text-sm">
            Belum ada data surat keluar.
        </td>
    </tr>
@endforelse
