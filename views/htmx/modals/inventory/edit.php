<div x-data="{ 
                openEdit: false,
                errors: {} // Tempat menyimpan pesan error
            }" 
         @open-edit-modal.window="openEdit = true" 
         @close-edit-modal.window="openEdit = false" 
         x-show="openEdit" 
         x-cloak
         class="relative z-[100]">

    <div x-show="openEdit" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"></div>

    <div class="fixed inset-0 z-10 overflow-y-auto flex items-center justify-center p-4">
    
        <div x-show="openEdit"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             @click.away="openEdit = false"
             class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden">
            
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white text-xs">
                        <i class="fas fa-edit"></i>
                    </div>
                    <h3 class="font-bold text-slate-800">Edit Data Produk</h3>
                </div>
                <button @click="openEdit = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <div id="edit-modal-content">
                <div class="p-12 flex flex-col items-center justify-center gap-4">
                    <div class="w-10 h-10 border-4 border-slate-200 border-t-indigo-600 rounded-full animate-spin"></div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Mengambil Data...</p>
                </div>
            </div>

        </div>
    </div>
</div>