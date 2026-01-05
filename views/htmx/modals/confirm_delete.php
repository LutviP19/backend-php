<div x-data="{ 
        openDelete: false, 
        targetName: '', 
        errorMessage: '' // State untuk menyimpan pesan error
     }" 
     @open-delete-modal.window="
        openDelete = true;
        targetName = $event.detail.name;
        errorMessage = ''; // Reset error setiap kali modal dibuka
        
        $nextTick(() => {
            let btn = $refs.confirmBtn;
            btn.setAttribute('hx-delete', $event.detail.url + $event.detail.id);
            btn.setAttribute('hx-target', '#product-row-' + $event.detail.id);
            htmx.process(btn);
        });
     "
     x-show="openDelete" x-cloak class="relative z-[110]">
    
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"></div>

    <div class="fixed inset-0 z-10 flex items-center justify-center p-4">
        <div @click.away="openDelete = false" class="bg-white w-full max-w-sm rounded-3xl shadow-2xl p-6 text-center">
            
            <div :class="errorMessage ? 'bg-amber-50 text-amber-500' : 'bg-rose-50 text-rose-500'" 
                 class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl transition-colors">
                <i class="fas" :class="errorMessage ? 'fa-exclamation-triangle' : 'fa-trash-alt'"></i>
            </div>

            <h3 class="text-lg font-bold text-slate-800" x-text="errorMessage ? 'Gagal Menghapus' : 'Hapus Data?'"></h3>
            
            <div class="mt-2">
                <template x-if="!errorMessage">
                    <p class="text-sm text-slate-500">
                        Anda yakin ingin menghapus <span class="font-bold text-slate-900" x-text="targetName"></span>?
                    </p>
                </template>
                <template x-if="errorMessage">
                    <p class="text-sm text-rose-600 font-medium bg-rose-50 p-3 rounded-xl" x-text="errorMessage"></p>
                </template>
            </div>
            
            <div class="flex gap-3 mt-6">
                <button @click="openDelete = false" 
                        :disabled="$el.classList.contains('htmx-request')"
                        class="flex-1 px-4 py-3 rounded-xl bg-slate-100 text-slate-600 font-bold hover:bg-slate-200 disabled:opacity-50">
                    <span x-text="errorMessage ? 'Tutup' : 'Batal'"></span>
                </button>
                
                <button x-show="!errorMessage"
                        x-ref="confirmBtn"
                        hx-swap="outerHTML"
                        hx-indicator="#delete-spinner"
                        @htmx:after-request="if($event.detail.successful) openDelete = false"
                        @htmx:response-error="errorMessage = $event.detail.xhr.responseText || 'Terjadi kesalahan sistem.'"
                        class="flex-1 px-4 py-3 rounded-xl bg-rose-600 text-white font-bold hover:bg-rose-700 shadow-lg shadow-rose-200 transition flex items-center justify-center gap-2 disabled:bg-rose-400 disabled:cursor-not-allowed">
                    
                    <svg id="delete-spinner" class="animate-spin h-4 w-4 text-white htmx-indicator" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>

                    <span x-text="'Ya, Hapus'"></span>
                </button>
            </div>
        </div>
    </div>
</div>