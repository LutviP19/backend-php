<style>
    /* Antialiasing Global */
    .antialiased {
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }

    /* Styling khusus agar thumb slider terlihat lebih modern */
    input[type=range]::-webkit-slider-thumb {
        appearance: none;
        height: 20px;
        width: 20px;
        border-radius: 50%;
        background: white;
        border: 3px solid #6366f1; /* Indigo-500 */
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        cursor: pointer;
        transition: all 0.2s;
    }
    input[type=range]::-webkit-slider-thumb:hover {
        transform: scale(1.2);
        box-shadow: 0 10px 15px -3px rgb(99 102 241 / 0.3);
    }

    /* Sembunyikan spinner secara default */
    .htmx-indicator {
        display: none;
    }
    /* Tampilkan spinner saat htmx sedang mengirim data */
    .htmx-request .htmx-indicator {
        display: inline-block;
    }
    /* Sembunyikan teks tombol saat loading */
    .htmx-request .button-text {
        display: none;
    }

    /* Sembunyikan scrollbar untuk Chrome, Safari dan Opera */
    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }

    /* Sembunyikan scrollbar untuk IE, Edge dan Firefox */
    .no-scrollbar {
        -ms-overflow-style: none;  /* IE and Edge */
        scrollbar-width: none;  /* Firefox */
    }
</style>

<div x-show="openModal" 
    x-data="{
                resetForm() {
                        this.$refs.form.reset();
                        // Memicu reset pada custom select (kembali ke pupuk)
                        this.$dispatch('reset-select');
                        count=0;
                        errors = {};
                }
            }" 
    class="p-8 antialiased">
    <div class="mb-6">
        <h3 class="text-xl font-black text-slate-800">Registrasi Unit Baru</h3>
        <p class="text-xs font-bold text-indigo-500 uppercase tracking-widest">Lengkapi Informasi Armada</p>
    </div>

    <form x-ref="form" 
        hx-post="<?= url('/data/asset-store') ?>" 
        hx-include="[name='view_mode']" 
        hx-target="#asset-container-body" 
        hx-swap="afterbegin" 
        @htmx:before-request="errors = {}" 
        @htmx:response-error="const response = JSON.parse($event.detail.xhr.responseText);
            errors = response;"
        @htmx:after-request="if(event.detail.successful) { 
            count++;
            window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Unit Berhasil Ditambahkan', type: 'success' } }));
            window.dispatchEvent(new CustomEvent('reset-filter')); // Memicu reset
            openModal = false;
            resetForm();
        }"
        class="space-y-5">

        <input type="hidden" name="action" value="add">
        
        <div class="grid grid-cols-1 gap-4">
            <div>
                <label class="text-[10px] font-black text-slate-400 uppercase ml-2 tracking-widest">Kode Unit</label>
                <input type="text" name="asset_id" placeholder="KBT-01" required 
                    :class="errors.asset_id ? 'border-rose-500 bg-rose-50 ring-rose-200' : 'border-slate-200 focus:border-indigo-500'"
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none transition-all text-sm">

                <template x-if="errors.asset_id">
                    <p x-text="errors.asset_id[0]" class="text-[10px] font-bold text-rose-600 mt-1 ml-1"></p>
                </template>
            </div>
            <div>
                <label class="text-[10px] font-black text-slate-400 uppercase ml-2 tracking-widest">Nama Unit</label>
                <input type="text" name="name" placeholder="Traktor Kubota" required 
                        :class="errors.name ? 'border-rose-500 bg-rose-50 ring-rose-200' : 'border-slate-200 focus:border-indigo-500'"
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none transition-all text-sm">

                <template x-if="errors.name">
                    <p x-text="errors.name[0]" class="text-[10px] font-bold text-rose-600 mt-1 ml-1"></p>
                </template>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div x-data="{ 
                    openCat: false, 
                    selectedCatId: '', 
                    selectedCatLabel: 'Pilih Kategori...',
                    categories: [
                        { id: 1, label: 'Alat Berat', slug: 'heavy-equipment', icon: 'fa-tractor' },
                        { id: 2, label: 'Teknologi', slug: 'technology', icon: 'fa-plane-up' },
                        { id: 3, label: 'Pendukung', slug: 'support', icon: 'fa-faucet-drip' },
                        { id: 4, label: 'Peralatan Gudang', slug: 'warehouse', icon: 'fa-dolly' },
                        { id: 5, label: 'Logistik', slug: 'logistics', icon: 'fa-truck' }
                    ]
                }"
                @reset-select.window="selectedCatId = ''">
                <label class="text-[10px] font-black text-slate-400 uppercase ml-2 tracking-widest">Kategori Aset</label>
                
                <div class="relative mt-1">
                    <button @click="openCat = !openCat" type="button" 
                            :class="errors.category_id ? 'border-2 border-rose-500 bg-rose-50' : 'border-2 border-slate-200 hover:border-indigo-500 focus:border-indigo-500/20'" 
                            class="w-full flex items-center justify-between px-5 py-3 bg-slate-50 rounded-2xl text-sm font-bold text-slate-700 hover:bg-slate-100 transition-all">
                        <span class="flex items-center gap-3">
                            <i x-show="selectedCatId" class="fas text-indigo-500" :class="categories.find(c => c.id === selectedCatId)?.icon"></i>
                            <span x-text="selectedCatLabel" :class="!selectedCatId ? 'text-slate-300' : ''"></span>
                        </span>
                        <i class="fas fa-chevron-down text-[10px] text-slate-400 transition-transform" :class="openCat ? 'rotate-180' : ''"></i>
                    </button>

                    <div x-show="openCat" 
                         @click.outside="openCat = false" 
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         class="absolute z-[130] w-full mt-2 bg-white border border-slate-100 rounded-2xl shadow-xl p-2 max-h-80 top-10 overflow-y-auto no-scrollbar">
                        
                        <template x-for="cat in categories" :key="cat.id">
                            <button type="button" 
                                    @click="selectedCatId = cat.id; selectedCatLabel = cat.label; openCat = false" 
                                    class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-xs font-bold text-slate-600 hover:bg-indigo-50 hover:text-indigo-600 transition-all">
                                <span class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center group-hover:bg-white">
                                        <i class="fas" :class="cat.icon"></i>
                                    </div>
                                    <span x-text="cat.label"></span>
                                </span>
                                <i x-show="selectedCatId === cat.id" class="fas fa-check text-[10px]"></i>
                            </button>
                        </template>
                    </div>

                    <input type="hidden" name="category_id" :value="selectedCatId" required>
                </div>
                <template x-if="errors.category_id">
                    <p x-text="errors.category_id[0]" class="text-[10px] font-bold text-rose-600 mt-1 ml-1"></p>
                </template>
            </div>

            <div x-data="{ openStatus: false, selectedStatus: 'ready', statusLabel: 'Ready' }" @reset-select.window="selectedStatus = 'ready'; statusLabel= 'Ready'">
                <label class="text-[10px] font-black text-slate-400 uppercase ml-2 tracking-widest">Status</label>
                <div class="relative mt-1">
                    <button @click="openStatus = !openStatus" type="button" 
                            :class="errors.status ? 'border-2 border-rose-500 bg-rose-50' : 'border-2 border-slate-200 hover:border-indigo-500'"
                            class="w-full flex items-center justify-between px-5 py-3 bg-slate-50 rounded-2xl text-sm font-bold text-slate-700">
                        <span class="flex items-center gap-3">
                            <div class="w-2.5 h-2.5 rounded-full" :class="selectedStatus === 'ready' ? 'bg-emerald-500' : (selectedStatus === 'maintenance' ? 'bg-rose-500' : 'bg-blue-500')"></div>
                            <span x-text="statusLabel"></span>
                        </span>
                        <i class="fas fa-chevron-down text-[10px]"></i>
                    </button>
                    <div x-show="openStatus" @click.outside="openStatus = false" class="absolute z-[120] w-full mt-2 bg-white border border-slate-100 rounded-2xl shadow-xl p-2">
                        <button type="button" @click="selectedStatus = 'ready'; statusLabel = 'Ready'; openStatus = false" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold text-slate-600 hover:bg-emerald-50 hover:text-emerald-600 transition-all"><div class="w-2 h-2 rounded-full bg-emerald-500"></div> Ready</button>
                        <button type="button" @click="selectedStatus = 'working'; statusLabel = 'Working'; openStatus = false" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold text-slate-600 hover:bg-blue-50 hover:text-blue-600 transition-all"><div class="w-2 h-2 rounded-full bg-blue-500"></div> Working</button>
                        <button type="button" @click="selectedStatus = 'maintenance'; statusLabel = 'Maintenance'; openStatus = false" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold text-slate-600 hover:bg-rose-50 hover:text-rose-600 transition-all"><div class="w-2 h-2 rounded-full bg-rose-500"></div> Maintenance</button>
                    </div>
                    <input type="hidden" name="status" :value="selectedStatus">
                </div>
                <template x-if="errors.status">
                    <p x-text="errors.status[0]" class="text-[10px] font-bold text-rose-600 mt-1 ml-1"></p>
                </template>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-2 gap-4">
            <div x-data="{ open: false, selected: 'fa-tractor' }" @reset-select.window="selected = 'fa-tractor'">
                <label class="text-[10px] font-black text-slate-400 uppercase ml-2 tracking-widest">Ikon Unit</label>
                <div class="relative mt-1">
                    <button @click="open = !open" type="button" 
                        :class="errors.icon ? 'border-2 border-rose-500 bg-rose-50' : 'border-2 border-slate-200 hover:border-indigo-500'" 
                        class="w-full flex items-center justify-between px-5 py-3 bg-slate-50 rounded-2xl text-sm font-bold text-slate-700">
                        <span class="flex items-center gap-3">
                            <i class="fas" :class="selected"></i>
                            <span x-text="selected.replace('fa-', '')"></span>
                        </span>
                        <i class="fas fa-search text-[10px] text-slate-400"></i>
                    </button>
                    <div x-show="open" @click.outside="open = false" class="absolute z-[120] w-full mt-2 bg-white border border-slate-100 rounded-2xl shadow-xl p-2 grid grid-cols-4 gap-1 max-h-48 overflow-y-auto">
                        <template x-for="icon in ['fa-tractor', 'fa-truck-pickup', 'fa-truck-monster', 'fa-gear', 'fa-plane-up', 'fa-helicopter', 'fa-truck', 'fa-truck-moving', 'fa-faucet-drip', 'fa-dolly', 'fa-plug', 'fa-spray-can-sparkles']">
                            <button type="button" @click="selected = icon; open = false" class="p-3 hover:bg-slate-50 rounded-xl text-slate-600 hover:text-indigo-600">
                                <i class="fas" :class="icon"></i>
                            </button>
                        </template>
                    </div>
                    <input type="hidden" name="icon" :value="selected">
                </div>
                <template x-if="errors.icon">
                    <p x-text="errors.icon[0]" class="text-[10px] font-bold text-rose-600 mt-1 ml-1"></p>
                </template>
            </div>

            <div x-data="{ open: false, selected: 'emerald' }" @reset-select.window="selected = 'emerald'">
                <label class="text-[10px] font-black text-slate-400 uppercase ml-2 tracking-widest">Warna Branding</label>
                <div class="relative mt-1">
                    <button @click="open = !open" type="button" 
                            :class="errors.color ? 'border-2 border-rose-500 bg-rose-50' : 'border-2 border-slate-200 hover:border-indigo-500'" 
                            class="w-full flex items-center justify-between px-5 py-3 bg-slate-50 rounded-2xl text-sm font-bold text-slate-700">
                        <span class="flex items-center gap-2">
                            <div class="w-4 h-4 rounded-full" :class="'bg-'+selected+'-500'"></div>
                            <span class="capitalize" x-text="selected"></span>
                        </span>
                        <i class="fas fa-palette text-slate-400"></i>
                    </button>
                    <div x-show="open" @click.outside="open = false" class="absolute z-[120] w-full mt-2 bg-white border border-slate-100 rounded-2xl shadow-xl p-3 grid grid-cols-5 gap-2">
                        <template x-for="color in ['emerald', 'indigo', 'blue', 'rose', 'amber', 'sky', 'slate', 'violet', 'orange', 'cyan']">
                            <button type="button" @click="selected = color; open = false" 
                                    :class="'bg-'+color+'-500'" 
                                    class="w-full aspect-square rounded-xl hover:scale-110 transition-transform"></button>
                        </template>
                    </div>
                    <input type="hidden" name="color" :value="selected">
                </div>
                <template x-if="errors.color">
                    <p x-text="errors.color[0]" class="text-[10px] font-bold text-rose-600 mt-1 ml-1"></p>
                </template>
            </div>

            
        </div>

        <div x-data="{ health: 100 }" @reset-select.window="health = 100" class="space-y-3">
            <div class="flex justify-between items-center px-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Initial Health</label>
                <span :class="health < 40 ? 'bg-rose-100 text-rose-600' : (health < 75 ? 'bg-amber-100 text-amber-600' : 'bg-emerald-100 text-emerald-600')" class="px-3 py-1 rounded-full text-xs font-black transition-colors" x-text="health + '%'"></span>
            </div>
            <div class="relative flex items-center h-10">
                <div class="absolute w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full transition-all duration-300" :style="`width: ${health}%;`" :class="health < 40 ? 'bg-rose-500' : (health < 75 ? 'bg-amber-500' : 'bg-indigo-500')"></div>
                </div>
                <input type="range" name="health" min="0" max="100" x-model="health" class="absolute w-full h-2 appearance-none bg-transparent cursor-pointer z-10">
            </div>
            <p class="text-[10px] font-bold text-center italic" :class="health < 40 ? 'text-rose-400' : 'text-slate-400'">
                <span x-show="health < 40">Kritis: Unit memerlukan perbaikan segera!</span>
                <span x-show="health >= 40 && health < 75">Waspada: Perlu dijadwalkan servis berkala.</span>
                <span x-show="health >= 75">Normal: Kondisi unit sangat baik.</span>
            </p>
        </div>

        <div class="flex gap-3 pt-4">
            <button type="button" @click="openModal = false; resetForm();" class="flex-1 py-4 bg-slate-200 text-slate-500 rounded-2xl font-bold text-sm hover:bg-slate-300 transition">Batal</button>
            <button type="submit" class="flex-1 py-4 bg-indigo-600 text-white rounded-2xl font-bold text-sm shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition">Simpan Unit</button>

            <span class="htmx-indicator flex items-center gap-2">
                <i class="fas fa-circle-notch animate-spin text-sm text-white"></i>
                <span class="font-semibold">Memproses...</span>
            </span>
        </div>
    </form>
</div>