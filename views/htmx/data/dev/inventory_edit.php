<form x-ref="form"
      hx-post="<?= url('/data/update-product') ?>" 
      hx-target="#product-row-<?= $data->id ?>" 
      @htmx:after-request="if($event.detail.successful) openEdit = false" 
      hx-swap="outerHTML" 
      @htmx:before-request="errors = {}" 
      @htmx:response-error="
        const response = JSON.parse($event.detail.xhr.responseText);
        errors = response; 
      "
      x-data="{ 
          errors: {},
          keepOpen: false,
          /* POPULATE CUSTOM SELECT DISINI */
          selected: '<?= $data->kategori ?>' 
      }"
      class="p-6 space-y-4">
    
    <input type="hidden" name="id" value="<?= intval($data->id) ?>">

    <div>
        <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1.5 ml-1">Nama Produk</label>
        <input type="text" name="nama" required placeholder="Contoh: Pupuk NPK Mutu" value="<?= htmlspecialchars($data->nama) ?>" 
                :class="errors.nama ? 'border-rose-500 bg-rose-50 ring-rose-200' : 'border-slate-200 focus:border-indigo-500'" 
                class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none transition-all text-sm">

        <template x-if="errors.nama">
            <p x-text="errors.nama[0]" class="text-[10px] font-bold text-rose-600 mt-1.5 ml-1 flex items-center gap-1">
                <i class="fas fa-exclamation-circle"></i> <span x-text="errors.nama[0]"></span>
            </p>
        </template>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div class="space-y-1.5" x-data="{ 
                open: false, 
                options: {
                    'pupuk': { label: 'Pupuk', icon: 'fa-seedling' },
                    'benih': { label: 'Benih', icon: 'fa-leaf' },
                    'pestisida': { label: 'Pestisida', icon: 'fa-flask' },
                    'alat': { label: 'Alat Berat', icon: 'fa-tractor' }
                }
            }">
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
                    <i class="fas fa-chevron-down text-[10px] text-slate-400"></i>
                </button>

                <div x-show="open" x-cloak class="absolute z-[110] w-full mt-2 bg-white border border-slate-100 rounded-2xl shadow-xl overflow-hidden">
                    <template x-for="(val, key) in options" :key="key">
                        <button type="button" @click="selected = key; open = false"
                                class="w-full px-4 py-3 flex items-center gap-3 hover:bg-indigo-50 transition-colors group">
                            <i class="fas text-xs text-slate-400 group-hover:text-indigo-600 w-5" :class="val.icon"></i>
                            <span class="text-sm font-bold text-slate-600 group-hover:text-indigo-700" x-text="val.label"></span>
                        </button>
                    </template>
                </div>
            </div>

            <template x-if="errors.kategori">
                <p x-text="errors.kategori[0]" class="text-[10px] font-bold text-rose-600 mt-1 ml-1"></p>
            </template>
        </div>

        <div>
            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1.5 ml-1">Stok</label>
            <input type="number" name="stok" required placeholder="0" value="<?= $data->stok ?>"
                    :class="errors.stok ? 'border-rose-500 bg-rose-50' : 'border-slate-200 focus:border-indigo-500'"
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-indigo-500 outline-none transition-all text-sm">

            <template x-if="errors.stok">
                <p x-text="errors.stok[0]" class="text-[10px] font-bold text-rose-600 mt-1 ml-1"></p>
            </template>
        </div>
    </div>

    <div>
        <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1.5 ml-1">Harga Satuan (Rp)</label>
        <input type="number" name="harga" required placeholder="150000" value="<?=  intval($data->harga) ?>"
                :class="errors.harga ? 'border-rose-500 bg-rose-50' : 'border-slate-200 focus:border-indigo-500'"
                class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-indigo-500 outline-none transition-all text-sm font-mono">
        <template x-if="errors.harga">
            <p x-text="errors.harga[0]" class="text-[10px] font-bold text-rose-600 mt-1 ml-1"></p>
        </template>
    </div>

    <div class="bg-indigo-50 p-4 rounded-2xl flex items-center justify-between border border-indigo-100">
        <div>
            <p class="text-xs font-bold text-indigo-900">Smart Alert</p>
            <p class="text-[10px] text-indigo-600">Peringatan jika stok < 5.</p>
        </div>
        <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" name="status_kritis" value="1" class="sr-only peer" <?= $data->status_kritis ? 'checked' : '' ?>>
            <div class="w-11 h-6 bg-slate-200 rounded-full peer peer-checked:bg-indigo-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
        </label>
    </div>

    <button type="submit" class="w-full bg-indigo-600 text-white py-4 rounded-2xl font-bold shadow-lg hover:bg-indigo-700 transition">
        Simpan Perubahan
    </button>
</form>