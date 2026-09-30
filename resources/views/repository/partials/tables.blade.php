                <!-- Segmented Tab Navigation -->
                <div class="flex flex-wrap items-center gap-2 border-b border-slate-200/80 pb-3">
                    <button type="button" @click="activeTab = 'all'"
                        :class="activeTab === 'all' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white hover:bg-slate-100 text-slate-700 border border-slate-200'"
                        class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all flex items-center space-x-2 cursor-pointer">
                        <span>Semua Dokumen</span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold" :class="activeTab === 'all' ? 'bg-slate-700 text-white' : 'bg-slate-100 text-slate-600'">{{ $activeMonthData['total_count'] }}</span>
                    </button>
                    <button type="button" @click="activeTab = 'incoming'"
                        :class="activeTab === 'incoming' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white hover:bg-slate-100 text-slate-700 border border-slate-200'"
                        class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all flex items-center space-x-2 cursor-pointer">
                        <span>Surat Masuk</span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold" :class="activeTab === 'incoming' ? 'bg-slate-700 text-white' : 'bg-slate-100 text-slate-600'">{{ $activeMonthData['incoming_count'] }}</span>
                    </button>
                    <button type="button" @click="activeTab = 'outgoing'"
                        :class="activeTab === 'outgoing' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white hover:bg-slate-100 text-slate-700 border border-slate-200'"
                        class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all flex items-center space-x-2 cursor-pointer">
                        <span>Surat Keluar</span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold" :class="activeTab === 'outgoing' ? 'bg-slate-700 text-white' : 'bg-slate-100 text-slate-600'">{{ $activeMonthData['outgoing_count'] }}</span>
                    </button>
                </div>

                <!-- Surat Masuk Table -->
                <div x-show="activeTab === 'all' || activeTab === 'incoming'" class="bg-white border border-slate-200 rounded-2xl shadow-2xs overflow-hidden">
                    <div class="px-5 py-3.5 bg-slate-100/90 border-b border-slate-200 flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <h3 class="text-xs sm:text-sm font-bold uppercase tracking-wider text-slate-800">
                                Surat Masuk ({{ $activeMonthData['incoming_count'] }})
                            </h3>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse min-w-full">
                            <thead>
                                <tr class="border-b border-slate-200 bg-slate-100 text-xs sm:text-[13px] font-bold text-slate-700 uppercase tracking-wider">
                                    <th class="py-4 px-4 sm:px-5 text-center whitespace-nowrap w-14">No.</th>
                                    <th class="py-4 px-4 sm:px-5 whitespace-nowrap">Nomor Surat</th>
                                    <th class="py-4 px-4 sm:px-5 whitespace-nowrap">Tanggal Masuk</th>
                                    <th class="py-4 px-4 sm:px-5 whitespace-nowrap">Dari</th>
                                    <th class="py-4 px-4 sm:px-5 whitespace-nowrap">Kepada</th>
                                    <th class="py-4 px-4 sm:px-5 text-center whitespace-nowrap">Status</th>
                                    <th class="py-4 px-4 sm:px-5 min-w-[280px]">Perihal</th>
                                    <th class="py-4 px-4 sm:px-5 whitespace-nowrap">Tanggal Keluar</th>
                                    <th class="py-4 px-4 sm:px-5 min-w-[200px]">Disposisi</th>
                                    <th class="py-4 px-4 sm:px-5 whitespace-nowrap">Nama Penerima</th>
                                    <th class="py-4 px-4 sm:px-5 text-right whitespace-nowrap">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 text-sm">
                                @forelse ($activeMonthData['incoming'] as $index => $mail)
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="py-4 px-4 sm:px-5 text-center font-bold text-slate-500 whitespace-nowrap">
                                            {{ $index + 1 }}
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
                                                    'RECEIVE', 'RECEIVED' => 'bg-slate-900 text-white border-slate-900 font-bold',
                                                    'RETURN', 'RETURNED' => 'bg-white text-slate-800 border-slate-300 font-bold',
                                                    'PROGRES', 'PROGRESS', 'IN_PROGRESS', 'PENDING' => 'bg-slate-200 text-slate-900 border-slate-400 font-bold',
                                                    default => 'bg-slate-100 text-slate-700 border-slate-300',
                                                };
                                                $displayStatus = match ($mail->status) {
                                                    'RECEIVED' => 'RECEIVE',
                                                    'PROGRESS', 'IN_PROGRESS', 'PENDING' => 'PROGRES',
                                                    'RETURNED' => 'RETURN',
                                                    default => $mail->status,
                                                };
                                            @endphp
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border {{ $badgeClasses }}">
                                                {{ $displayStatus }}
                                            </span>
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
                                        <td class="py-4 px-4 sm:px-5 text-right whitespace-nowrap">
                                            <a href="{{ route('incoming-mails.show', $mail) }}"
                                                class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-900 border border-slate-200 rounded-lg font-semibold text-xs transition-all shadow-2xs">
                                                Detail
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="p-8 text-center text-slate-500 italic text-sm">
                                            Tidak ada data surat masuk yang cocok.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Surat Keluar Table -->
                <div x-show="activeTab === 'all' || activeTab === 'outgoing'" class="bg-white border border-slate-200 rounded-2xl shadow-2xs overflow-hidden">
                    <div class="px-5 py-3.5 bg-slate-100/90 border-b border-slate-200 flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <h3 class="text-xs sm:text-sm font-bold uppercase tracking-wider text-slate-800">
                                Surat Keluar ({{ $activeMonthData['outgoing_count'] }})
                            </h3>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse min-w-full">
                            <thead>
                                <tr class="border-b border-slate-200 bg-slate-100 text-xs sm:text-[13px] font-bold text-slate-700 uppercase tracking-wider">
                                    <th class="py-4 px-4 sm:px-5 text-center whitespace-nowrap w-14">No.</th>
                                    <th class="py-4 px-4 sm:px-5 whitespace-nowrap">Nomor Surat</th>
                                    <th class="py-4 px-4 sm:px-5 whitespace-nowrap">Tanggal Dibuat</th>
                                    <th class="py-4 px-4 sm:px-5 min-w-[320px]">Subjek / Perihal</th>
                                    <th class="py-4 px-4 sm:px-5 whitespace-nowrap">Penerima</th>
                                    <th class="py-4 px-4 sm:px-5 whitespace-nowrap">Pembuat</th>
                                    <th class="py-4 px-4 sm:px-5 text-center whitespace-nowrap">Status</th>
                                    <th class="py-4 px-4 sm:px-5 text-right whitespace-nowrap">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 text-sm">
                                @forelse ($activeMonthData['outgoing'] as $index => $mail)
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="py-4 px-4 sm:px-5 text-center font-bold text-slate-500 whitespace-nowrap">
                                            {{ $index + 1 }}
                                        </td>
                                        <td class="py-4 px-4 sm:px-5 font-bold text-slate-900 font-mono whitespace-nowrap">
                                            {{ $mail->mail_number ?? '-' }}
                                        </td>
                                        <td class="py-4 px-4 sm:px-5 text-slate-700 font-mono whitespace-nowrap">
                                            {{ $mail->created_at?->format('d/m/Y') ?? '-' }}
                                        </td>
                                        <td class="py-4 px-4 sm:px-5 text-slate-800 font-medium min-w-[320px] max-w-2xl break-words leading-relaxed" title="{{ $mail->subject }}">
                                            {{ $mail->subject }}
                                        </td>
                                        <td class="py-4 px-4 sm:px-5 text-slate-700 font-medium whitespace-nowrap">{{ $mail->recipient }}</td>
                                        <td class="py-4 px-4 sm:px-5 text-slate-600 font-medium whitespace-nowrap">{{ $mail->creator?->name ?? 'System' }}</td>
                                        <td class="py-4 px-4 sm:px-5 text-center whitespace-nowrap">
                                            @php
                                                $badgeClasses = match ($mail->status) {
                                                    'RECEIVE', 'RECEIVED' => 'bg-slate-900 text-white border-slate-900 font-bold',
                                                    'PROGRES', 'PROGRESS', 'IN_PROGRESS', 'PENDING' => 'bg-slate-200 text-slate-900 border-slate-400 font-bold',
                                                    'RETURN', 'RETURNED' => 'bg-white text-slate-800 border-slate-300 font-bold',
                                                    default => 'bg-slate-100 text-slate-700 border-slate-300',
                                                };
                                                $displayStatus = match ($mail->status) {
                                                    'RECEIVED' => 'RECEIVE',
                                                    'PROGRESS', 'IN_PROGRESS', 'PENDING' => 'PROGRES',
                                                    'RETURNED' => 'RETURN',
                                                    default => $mail->status,
                                                };
                                            @endphp
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border {{ $badgeClasses }}">
                                                {{ $displayStatus }}
                                            </span>
                                        </td>
                                        <td class="py-4 px-4 sm:px-5 text-right whitespace-nowrap">
                                            <a href="{{ route('outgoing-mails.show', $mail) }}"
                                                class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-900 border border-slate-200 rounded-lg font-semibold text-xs transition-all shadow-2xs">
                                                Detail
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="p-8 text-center text-slate-500 italic text-sm">
                                            Tidak ada data surat keluar yang cocok.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
