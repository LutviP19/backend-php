<div x-data="{ 
        show: false, 
        message: '', 
        type: 'error' 
     }"
     @htmx:response-error.window="
        type = 'error';
        message = 'Terjadi kesalahan pada server (Status: ' + $event.detail.xhr.status + ')';
        show = true;
        setTimeout(() => show = false, 5000);
     "
     @htmx:send-error.window="
        type = 'warning';
        message = 'Koneksi terputus. Periksa jaringan internet Anda.';
        show = true;
        setTimeout(() => show = false, 5000);
     "
     x-show="show"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 translate-y-10"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 translate-y-10"
     class="fixed bottom-6 right-6 z-[100] max-w-sm w-full"
     style="display: none;">
    
    <div :class="type === 'error' ? 'bg-rose-600' : 'bg-amber-500'" 
         class="p-4 rounded-2xl shadow-2xl flex items-center gap-4 text-white">
        <div class="bg-white/20 p-2 rounded-xl">
            <i class="fas" :class="type === 'error' ? 'fa-exclamation-triangle' : 'fa-wifi-slash'"></i>
        </div>
        <div class="flex-1">
            <p class="text-[10px] font-black uppercase tracking-widest opacity-80" x-text="type === 'error' ? 'System Error' : 'Connection Error'"></p>
            <p class="text-sm font-bold" x-text="message"></p>
        </div>
        <button @click="show = false" class="hover:bg-white/10 p-2 rounded-lg transition">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>