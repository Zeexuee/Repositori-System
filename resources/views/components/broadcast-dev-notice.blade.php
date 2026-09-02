<div x-data="{ showNotice: true }"
    x-show="showNotice"
    x-cloak
    class="fixed inset-0 z-[99999] flex items-center justify-center p-4">
    
    <!-- Backdrop Tint -->
    <div x-show="showNotice"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="showNotice = false"
        class="fixed inset-0 bg-slate-900/30"></div>

    <!-- Simple Pop-up Window -->
    <div x-show="showNotice"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="relative bg-white rounded-2xl max-w-sm w-full p-5 shadow-xl border border-slate-200 space-y-4 z-10 text-slate-900">
        
        <!-- Header -->
        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
            <div class="flex items-center space-x-2">
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Informasi Fitur</h3>
            </div>
            <button type="button" @click="showNotice = false" class="text-slate-400 hover:text-slate-700 text-xs font-bold p-1">✕</button>
        </div>

        <!-- Content Message -->
        <div class="text-xs text-slate-600 leading-relaxed space-y-1">
            <p class="font-bold text-slate-900">Fitur Dalam Proses Pengembangan</p>
            <p>Fitur Broadcast Email Notifikasi saat ini sedang dalam proses pengembangan dan penyempurnaan.</p>
        </div>

        <!-- Button -->
        <div>
            <button type="button" @click="showNotice = false" class="w-full py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-xl transition-all cursor-pointer text-center">
                Mengerti
            </button>
        </div>
    </div>
</div>
