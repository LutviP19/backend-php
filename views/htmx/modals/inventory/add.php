<div x-data="{ 
        openAdd: false, 
        keepOpen: false, 
        count: 0,
        errors: {}, // Tempat menyimpan pesan error
        resetForm() {
            this.$refs.form.reset();
            // Memicu reset pada custom select (kembali ke pupuk)
            this.$dispatch('reset-select');
            this.$nextTick(() => { this.$refs.namaInput.focus(); });
        }
     }"  
     @open-add-modal.window="openAdd = true; count = 0; $nextTick(() => { $refs.namaInput.focus(); })"
     x-show="openAdd" 
     x-cloak 
     class="relative z-[100]">
    
    <div x-show="openAdd" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"></div>

    <div x-show="openAdd" 
         class="fixed inset-0 z-10 overflow-y-auto flex items-center justify-center p-4">
        
        <div x-show="openAdd"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             @click.away="openAdd = false"
             class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden">
            
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <div class="flex items-center gap-3">
                    <h3 class="font-bold text-slate-800">Tambah Produk Baru</h3>
                    <template x-if="count > 0">
                        <span class="bg-indigo-100 text-indigo-700 text-[10px] px-2 py-0.5 rounded-full font-black animate-bounce">
                            <span x-text="count"></span> DATA
                        </span>
                    </template>
                </div>
                <button @click="openAdd = false" class="text-slate-400 hover:text-slate-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form x-ref="form"
                  hx-post="<?= url('/data/save-product') ?>" 
                  hx-target="#inventory-table-body" 
                  hx-swap="afterbegin"
                  @htmx:before-request="errors = {}" 
                  @htmx:response-error="
                    const response = JSON.parse($event.detail.xhr.responseText);
                    errors = response; 
                  "
                  @htmx:after-request="
                    if ($event.detail.successful) {
                        count++;
                        if (!keepOpen) {
                            openAdd = false;
                            resetForm();
                        } else {
                            resetForm();
                        }
                    }
                  "
                  class="p-6 space-y-4">
                
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1.5 ml-1">Nama Produk</label>
                    <input x-ref="namaInput" type="text" name="nama" required placeholder="Contoh: Pupuk NPK Mutu" 
                            autocomplete="off" 
                            :class="errors.nama ? 'border-rose-500 bg-rose-50 ring-rose-200' : 'border-slate-200 focus:border-indigo-500'"
                            class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none transition-all text-sm">

                   <template x-if="errors.nama">
                        <p x-text="errors.nama[0]" class="text-[10px] font-bold text-rose-600 mt-1.5 ml-1 flex items-center gap-1"></p>
                    </template>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1.5" x-data="{ 
                            open: false, 
                            selected: 'pupuk', 
                            options: {
                                'pupuk': { label: 'Pupuk', icon: 'fa-seedling' },
                                'benih': { label: 'Benih', icon: 'fa-leaf' },
                                'pestisida': { label: 'Pestisida', icon: 'fa-flask' },
                                'alat': { label: 'Alat Berat', icon: 'fa-tractor' }
                            }
                        }" @reset-select.window="selected = 'pupuk'">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Kategori</label>
                        <div class="relative">
                            <input type="hidden" name="kategori" :value="selected">
                            <button type="button" @click="open = !open" @click.away="open = false" 
                                    :class="errors.kategori ? 'border-rose-500 bg-rose-50' : 'border-slate-200 hover:border-indigo-500'" 
                                    class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-white flex items-center justify-between hover:border-indigo-500 transition-all outline-none">
                                <div class="flex items-center gap-3">
                                    <i class="fas text-indigo-500 text-xs" :class="options[selected].icon"></i>
                                    <span class="text-sm font-medium text-slate-700" x-text="options[selected].label"></span>
                                </div>
                                <i class="fas fa-chevron-down text-[10px] text-slate-400 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                            </button>

                            <div x-show="open" x-cloak class="absolute z-[110] w-full mt-2 bg-white border border-slate-100 rounded-2xl shadow-xl overflow-hidden">
                                <template x-for="(data, key) in options" :key="key">
                                    <button type="button" @click="selected = key; open = false"
                                            class="w-full px-4 py-2.5 flex items-center gap-3 hover:bg-indigo-50 transition-colors group text-left">
                                        <i class="fas text-xs text-slate-400 group-hover:text-indigo-600 w-4 text-center" :class="data.icon"></i>
                                        <span class="text-sm font-bold text-slate-600 group-hover:text-indigo-700" x-text="data.label"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <template x-if="errors.kategori">
                            <p x-text="errors.kategori[0]" class="text-[10px] font-bold text-rose-600 mt-1 ml-1"></p>
                        </template>
                    </div>
                    
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1.5 ml-1">Stok Awal</label>
                        <input type="number" name="stok" required placeholder="0" 
                               :class="errors.stok ? 'border-rose-500 bg-rose-50' : 'border-slate-200 focus:border-indigo-500'"
                               class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-indigo-500 outline-none transition-all text-sm">
                        <template x-if="errors.stok">
                            <p x-text="errors.stok[0]" class="text-[10px] font-bold text-rose-600 mt-1 ml-1"></p>
                        </template>
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1.5 ml-1">Harga Satuan (Rp)</label>
                    <input type="number" name="harga" required placeholder="150000"
                           :class="errors.harga ? 'border-rose-500 bg-rose-50' : 'border-slate-200 focus:border-indigo-500'"
                           class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-indigo-500 outline-none transition-all text-sm font-mono">
                    <template x-if="errors.harga">
                        <p x-text="errors.harga[0]" class="text-[10px] font-bold text-rose-600 mt-1 ml-1"></p>
                    </template>
                </div>

                <div class="space-y-3">
                    <div class="bg-indigo-50 p-4 rounded-2xl flex items-center justify-between border border-indigo-100">
                        <div>
                            <p class="text-xs font-bold text-indigo-900">Smart Alert</p>
                            <p class="text-[10px] text-indigo-600">Peringatan jika stok < 5.</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="status_kritis" value="1" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-slate-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-indigo-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:h-5 after:w-5 after:rounded-full after:transition-all"></div>
                        </label>
                    </div>

                    <div class="bg-slate-50 p-3 rounded-2xl flex items-center justify-between border border-slate-100">
                        <span class="text-xs font-bold text-slate-600">Input banyak data?</span>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" x-model="keepOpen" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-indigo-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:h-5 after:w-5 after:rounded-full after:transition-all"></div>
                        </label>
                    </div>
                </div>

                <button type="submit" class="w-full bg-indigo-600 text-white py-4 rounded-2xl font-bold shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition flex items-center justify-center gap-2">
                    <i class="fas fa-save"></i> 
                    <span x-text="keepOpen ? 'Simpan & Tambah Lagi' : 'Simpan Produk'"></span>
                </button>
            </form>
        </div>
    </div>
</div>