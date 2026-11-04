@forelse ($incomingMails as $index => $mail)
    <tr class="row-item hover:bg-slate-50/80 transition-colors" data-selected="false">
        <!-- Select Box Column Cell -->
        <td x-show="isSelectionMode" class="py-4 px-3 text-center whitespace-nowrap">
            <input type="checkbox"
                value="{{ $mail->id }}"
                aria-label="Pilih dokumen {{ $mail->mail_number }}"
                onclick="window.toggleMailSelection && window.toggleMailSelection('{{ $mail->id }}')"
                class="row-checkbox w-4 h-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 cursor-pointer">
        </td>
        <td class="py-4 px-4 sm:px-5 text-center font-bold text-slate-500 whitespace-nowrap">
            {{ ($incomingMails->firstItem() ?? 1) + $index }}
        </td>
        <td class="py-4 px-4 sm:px-5 font-bold text-slate-900 font-mono whitespace-nowrap">
            @if(App\Support\IncomingMailStatus::canonical($mail->progress) === App\Support\IncomingMailStatus::DRAFT && $mail->batchItems && $mail->batchItems->count() > 1)
                <span class="inline-flex items-center gap-1.5 bg-slate-100 text-slate-700 px-2.5 py-1 rounded-md text-xs border border-slate-200">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                    1 Draft ({{ $mail->batchItems->count() }} Dokumen)
                </span>
            @else
                {{ $mail->mail_number }}
            @endif
        </td>
        <td class="py-4 px-4 sm:px-5 text-slate-700 font-mono whitespace-nowrap">
            {{ $mail->received_date?->format('d/m/Y H:i') ?? '-' }}
        </td>
        <td class="py-4 px-4 sm:px-5 font-medium text-slate-800 max-w-[280px]">
            <div class="line-clamp-3 break-words leading-relaxed" title="{{ $mail->sender }}">
                {{ $mail->sender }}
            </div>
        </td>
        <td class="py-4 px-4 sm:px-5 text-slate-700 max-w-[280px]">
            @php
                $recipientsList = $mail->recipients_list;
            @endphp
            @if(count($recipientsList) > 1)
                <div class="flex flex-wrap gap-1 max-w-[280px] max-h-20 overflow-hidden" title="{{ implode(', ', $recipientsList) }}">
                    @foreach($recipientsList as $rec)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-slate-100 text-slate-800 border border-slate-200 break-words leading-tight max-w-[260px] truncate" title="{{ $rec }}">
                            {{ $rec }}
                        </span>
                    @endforeach
                </div>
            @elseif(count($recipientsList) === 1)
                <div class="line-clamp-3 break-words leading-relaxed" title="{{ $recipientsList[0] }}">
                    {{ $recipientsList[0] }}
                </div>
            @else
                <span class="text-slate-400">-</span>
            @endif
        </td>
        <td class="py-4 px-4 sm:px-5 text-center min-w-[120px] max-w-[180px]">
            @if(!empty($mail->status))
                <span class="inline-block max-w-[160px] px-2.5 py-1 rounded-md text-xs font-semibold bg-slate-100 text-slate-800 border border-slate-200 break-words [overflow-wrap:anywhere] leading-tight text-center line-clamp-3" title="{{ $mail->status }}">
                    {{ $mail->status }}
                </span>
            @else
                <span class="text-slate-400 text-xs">-</span>
            @endif
        </td>
        <td class="py-4 px-4 sm:px-5 text-center whitespace-nowrap min-w-[160px]">
            @php
                $canonicalProgress = App\Support\IncomingMailStatus::canonical($mail->progress);
                $displayProgress = App\Support\IncomingMailStatus::label($mail->progress);
                $isFinalProgress = App\Support\IncomingMailStatus::allowedTransitions($canonicalProgress) === [];
                $badgeClasses = match ($canonicalProgress) {
                    App\Support\IncomingMailStatus::RECEIVE => 'bg-slate-900 text-white border-slate-900 hover:bg-slate-800',
                    App\Support\IncomingMailStatus::REVISI => 'bg-amber-50 text-amber-800 border-amber-300 hover:bg-amber-100 font-bold',
                    App\Support\IncomingMailStatus::WAITING_RETURN => 'bg-blue-50 text-blue-800 border-blue-300 hover:bg-blue-100 font-bold',
                    App\Support\IncomingMailStatus::RETURN => 'bg-white text-slate-800 border-slate-300 hover:bg-slate-50 font-bold',
                    App\Support\IncomingMailStatus::DRAFT => 'bg-indigo-50 text-indigo-800 border-indigo-300 hover:bg-indigo-100 font-bold',
                    default => 'bg-slate-100 text-slate-700 border-slate-300',
                };

                // Dokumen yang sudah dikembalikan dapat diintip catatan disposisi & penerimanya.
                $canPeekDisposition = $canonicalProgress === App\Support\IncomingMailStatus::RETURN
                    || filled($mail->disposition_note);
            @endphp

            <div class="inline-flex items-center gap-1.5">
                @if (auth()->user()?->can('update', $mail) && ! $isFinalProgress)
                    @if ($canonicalProgress === App\Support\IncomingMailStatus::WAITING_RETURN)
                        <button type="button"
                            @click="window.resumeReturnWorkflow(@js((string) $mail->id), @js((string) $mail->mail_number))"
                            title="Lanjutkan pengisian catatan disposisi & tanda tangan pengembalian"
                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border cursor-pointer transition-all shadow-2xs {{ $badgeClasses }}">
                            <span>{{ $displayProgress }}</span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    @else
                        <button type="button"
                            @click="window.openStatusDropdown($event, @js((string) $mail->id), @js((string) $mail->mail_number), @js((string) $displayProgress))"
                            aria-haspopup="menu"
                            title="Klik untuk mengubah progres dokumen"
                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border cursor-pointer transition-all shadow-2xs {{ $badgeClasses }}">
                            <span>{{ $displayProgress }}</span>
                            <svg class="w-3 h-3 opacity-70 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                    @endif
                @else
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border {{ $badgeClasses }}"
                        @if ($isFinalProgress) title="{{ $displayProgress }} adalah progres akhir dan tidak dapat diubah lagi" @endif>
                        {{ $displayProgress }}
                    </span>
                @endif

                @if ($canPeekDisposition)
                    <button type="button"
                        @click="window.openDispositionPreviewModal(@js((string) $mail->id), @js((string) $mail->mail_number))"
                        title="Intip catatan disposisi & penerima dokumen"
                        aria-label="Intip catatan disposisi dokumen {{ $mail->mail_number }}"
                        class="inline-flex items-center justify-center w-6 h-6 rounded-full border border-slate-300 text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition-all cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </button>
                @endif
            </div>
        </td>
        <td class="py-4 px-4 sm:px-5 text-slate-800 min-w-[260px] max-w-lg">
            <div class="line-clamp-3 break-words leading-relaxed cursor-help" title="{{ $mail->subject }}">
                {{ $mail->subject }}
            </div>
        </td>
        <td class="py-4 px-4 sm:px-5 text-slate-600 font-mono whitespace-nowrap">
            {{ $mail->outgoing_date?->format('d/m/Y H:i') ?? '-' }}
        </td>
        <td class="py-4 px-4 sm:px-5 text-slate-600 max-w-[240px]">
            @if(!empty($mail->disposition_note))
                <div class="line-clamp-3 break-words leading-relaxed cursor-pointer hover:text-slate-900 transition-colors"
                    @click="window.openDispositionPreviewModal(@js((string) $mail->id), @js((string) $mail->mail_number))"
                    title="{{ $mail->disposition_note }} (Klik untuk intip lengkap)">
                    {{ $mail->disposition_note }}
                </div>
            @else
                <span class="text-slate-400">-</span>
            @endif
        </td>
        <td class="py-4 px-4 sm:px-5 text-right space-x-1.5 whitespace-nowrap">
            <a href="{{ route('incoming-mails.show', $mail) }}"
                class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-900 rounded-lg font-semibold text-xs transition-all border border-slate-200">
                Detail
            </a>
            @can('update', $mail)
                <button type="button"
                    @click="window.openUploadAttachmentModal(@js((string) $mail->id), @js((string) $mail->mail_number), @js((string) $mail->subject))"
                    class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-900 font-semibold rounded-lg text-xs transition-all border border-slate-200 cursor-pointer">
                    Upload Gambar
                </button>
                @if($mail->progress === 'DRAFT')
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
        <td colspan="13" class="p-8 text-center text-slate-500 italic text-sm">
            Tidak ada data surat masuk yang sesuai dengan pencarian.
        </td>
    </tr>
@endforelse
