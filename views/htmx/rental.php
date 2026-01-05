<div class="animate-in fade-in slide-in-from-bottom-4 duration-700">
    
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-800 tracking-tight">Penyewaan Alat Berat</h1>
            <p class="text-slate-500 italic">Manajemen traktor, drone, dan alat pertanian modern.</p>
        </div>
        <button @click="$dispatch('open-modal-sewa')" class="bg-indigo-600 text-white px-6 py-3 rounded-2xl font-bold shadow-lg shadow-indigo-200 hover:scale-105 transition">
            <i class="fas fa-plus mr-2"></i> Buat Sewa Baru
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Traktor Tersedia</p>
            <h3 class="text-2xl font-bold text-slate-800">05 <span class="text-sm font-normal text-slate-400">Unit</span></h3>
        </div>
        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Drone Aktif</p>
            <h3 class="text-2xl font-bold text-indigo-600">03 <span class="text-sm font-normal text-slate-400 tracking-normal">Di Lapangan</span></h3>
        </div>
        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm border-l-4 border-l-orange-500">
            <p class="text-[10px] font-bold text-orange-500 uppercase tracking-widest">Jadwal Servis</p>
            <h3 class="text-2xl font-bold text-slate-800">02 <span class="text-sm font-normal text-slate-400">Minggu Ini</span></h3>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-[2rem] border border-slate-200 overflow-hidden shadow-sm group hover:border-indigo-500 transition-all duration-300">
            <div class="p-6">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-slate-50 rounded-2xl text-slate-400 group-hover:bg-indigo-50 group-hover:text-indigo-600 transition">
                        <i class="fas fa-tractor text-2xl"></i>
                    </div>
                    <span class="px-3 py-1 bg-emerald-50 text-emerald-600 rounded-full text-[10px] font-bold italic">Tersedia</span>
                </div>
                <h4 class="text-lg font-bold text-slate-800">Kubota L-Series A1</h4>
                <p class="text-xs text-slate-400 mb-6">Terakhir servis: 40 jam yang lalu</p>
                
                <div class="space-y-2">
                    <div class="flex justify-between text-[10px] font-bold uppercase">
                        <span class="text-slate-400">Kesehatan Mesin</span>
                        <span class="text-slate-700">92%</span>
                    </div>
                    <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-emerald-500 w-[92%]"></div>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-between items-center">
                <span class="text-sm font-bold text-slate-600">Rp 150rb / Jam</span>
                <button class="text-indigo-600 text-sm font-bold hover:underline">Detail Aset</button>
            </div>
        </div>
        </div>

    <div x-data="{ open: false, durasi: 1, hargaPerJam: 150000 }" 
         x-show="open" 
         x-cloak
         @open-modal-sewa.window="open = true"
         class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        
        <div class="bg-white w-full max-w-lg rounded-[2.5rem] p-8 shadow-2xl animate-in zoom-in duration-300">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-slate-800">Input Sewa Baru</h3>
                <button @click="open = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
            </div>

            <form class="space-y-5">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Pilih Aset</label>
                    <select class="w-full bg-slate-50 border-none rounded-2xl p-3 text-sm focus:ring-2 focus:ring-indigo-500">
                        <option>Kubota L-Series A1 (Traktor)</option>
                        <option>DJI Agras T40 (Drone)</option>
                    </select>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Nama Petani</label>
                        <input type="text" placeholder="Contoh: Bp. Jajang" class="w-full bg-slate-50 border-none rounded-2xl p-3 text-sm focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Estimasi Durasi (Jam)</label>
                        <input type="number" x-model="durasi" class="w-full bg-slate-50 border-none rounded-2xl p-3 text-sm focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="p-6 bg-indigo-50 rounded-3xl border border-indigo-100">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm text-indigo-900">Total Biaya Estimasi:</span>
                        <span class="text-xl font-black text-indigo-600" x-text="'Rp ' + (durasi * hargaPerJam).toLocaleString()"></span>
                    </div>
                    <p class="text-[10px] text-indigo-400 italic leading-relaxed">
                        <i class="fas fa-info-circle mr-1"></i> Biaya akhir akan disesuaikan dengan jam kerja (engine hours) saat alat dikembalikan dan tertera di BAST.
                    </p>
                </div>

                <button type="submit" class="w-full py-4 bg-indigo-600 text-white rounded-2xl font-bold shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition">
                    Konfirmasi & Cetak BAST Digital
                </button>
            </form>
        </div>
    </div>
</div>