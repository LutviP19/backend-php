<!DOCTYPE html>
<html lang="id" x-data="{ isDark: $persist(false) }" :class="isDark ? 'dark' : ''">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script src="<?= assets('js/htmx.min.js') ?>"></script>    
    <script defer src="<?= assets('/js/persist@3.min.js') ?>"></script>
    <script defer src="<?= assets('/js/alpinejs3.min.js') ?>"></script>
    <!-- <script src="<?= assets('/js/cdn-tailwindcss.js') ?>"></script>
    <script>
        tailwind.config = {
            darkMode: 'class', 
        }
    </script> -->

    <link rel="stylesheet" href="<?= assets('/assets/css/app.css') ?>">

    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"> -->
    <link rel="stylesheet" href="<?= assets('/assets/fontawesome-web/css/all.min.css') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        [x-cloak] { display: none !important; }
        .glass-effect {
            backdrop-filter: blur(16px);
            background: rgba(255, 255, 255, 0.8);
        }
        .dark .glass-effect {
            background: rgba(15, 23, 42, 0.6);
        }
    </style>
</head>
<body class="bg-slate-50 dark:bg-black flex items-center justify-center min-h-screen p-6 transition-colors duration-500" 
      x-data="{ showToast: false, message: '' }"
      @show-toast.window="showToast = true; message = $event.detail.value; setTimeout(() => showToast = false, 3000)">

    <div class="fixed top-0 left-0 w-full h-full overflow-hidden -z-10 pointer-events-none">
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-indigo-600/10 rounded-full blur-[120px]"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-emerald-600/10 rounded-full blur-[120px]"></div>
    </div>

    <div x-show="showToast" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-10 scale-95"
         x-transition:enter-end="opacity-100 transform translate-y-0 scale-100"
         class="fixed bottom-10 right-1/2 translate-x-1/2 md:right-10 md:translate-x-0 bg-slate-900 dark:bg-white text-white dark:text-slate-900 px-6 py-4 rounded-2xl shadow-2xl flex items-center gap-3 z-50 border border-white/10 dark:border-slate-200">
        <i class="fas fa-check-circle text-emerald-500"></i>
        <span x-text="message" class="font-bold text-sm"></span>
    </div>

    <div class="glass-effect p-8 md:p-12 rounded-[3rem] shadow-2xl w-full max-w-md border border-slate-200 dark:border-white/10 transition-all duration-500">
        
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-indigo-600 rounded-2xl text-white shadow-lg shadow-indigo-200 dark:shadow-none mb-6">
                <i class="fas fa-leaf text-2xl"></i>
            </div>
            <h2 class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight mb-2">SmartStock AI</h2>
            <p class="text-slate-500 dark:text-slate-400 font-medium italic">Sistem Koperasi AgriSmart</p>
        </div>

        <form hx-post="<?= url('/login/auth') ?>" hx-target="#error-area" class="space-y-4">
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-indigo-600 transition-colors">
                    <i class="fas fa-user text-sm"></i>
                </div>
                <input type="text" name="username" required placeholder="Username" 
                       class="w-full pl-11 pr-4 py-4 bg-slate-100/50 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-2xl focus:ring-2 focus:ring-indigo-500 focus:bg-white dark:focus:bg-slate-900 outline-none transition-all dark:text-white placeholder:text-slate-400">
            </div>

            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-indigo-600 transition-colors">
                    <i class="fas fa-lock text-sm"></i>
                </div>
                <input type="password" name="password" required placeholder="Password" 
                       class="w-full pl-11 pr-4 py-4 bg-slate-100/50 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-2xl focus:ring-2 focus:ring-indigo-500 focus:bg-white dark:focus:bg-slate-900 outline-none transition-all dark:text-white placeholder:text-slate-400">
            </div>
            
            <div class="flex justify-end">
                <a href="#" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline">Lupa Password?</a>
            </div>

            <div id="error-area" class="text-rose-500 text-xs font-bold italic empty:hidden bg-rose-50 dark:bg-rose-500/10 p-3 rounded-xl border border-rose-100 dark:border-rose-500/20"></div>

            <button type="submit" class="group w-full py-4 bg-indigo-600 text-white rounded-2xl font-bold hover:bg-indigo-700 transition-all shadow-xl shadow-indigo-200 dark:shadow-none flex items-center justify-center gap-3 active:scale-95">
                <span>Masuk Sekarang</span>
                <i class="fas fa-arrow-right text-xs group-hover:translate-x-1 transition-transform"></i>
            </button>
        </form>

        <p class="mt-8 text-center text-slate-400 dark:text-slate-500 text-xs">
            &copy; 2026 AgriSmart AI. Platform Presisi Pertanian.
        </p>
    </div>

    <button @click="isDark = !isDark" class="fixed top-6 right-6 w-10 h-10 rounded-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 text-slate-500 flex items-center justify-center">
        <i class="fas" :class="isDark ? 'fa-sun' : 'fa-moon'"></i>
    </button>
    
    <script>
        document.body.addEventListener("play-error-sound", function(evt) {
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioCtx.createOscillator();
            const gainNode = audioCtx.createGain();

            oscillator.type = 'sawtooth'; // Suara agak kasar khas error
            oscillator.frequency.setValueAtTime(150, audioCtx.currentTime); // Nada rendah
            oscillator.frequency.exponentialRampToValueAtTime(40, audioCtx.currentTime + 0.3);
            
            gainNode.gain.setValueAtTime(0.1, audioCtx.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.3);

            oscillator.connect(gainNode);
            gainNode.connect(audioCtx.destination);

            oscillator.start();
            oscillator.stop(audioCtx.currentTime + 0.3);
        });
    </script>
</body>
</html>