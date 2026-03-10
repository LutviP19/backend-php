<?php
/**
 *  @package Backend-PHP
 */

 ?>

<div class="max-w-5xl mx-auto">
    <div class="relative flex flex-col mb-8">
        <h1 class="text-2xl font-bold text-emerald-400">Sample View</h1>

        <div class="flex items-center mt-3 gap-4 bg-slate-900/50 p-2 px-4 rounded-full border border-slate-700/50">
            <div class="flex gap-1.5">
                <div title="Chat Model" 
                     class="w-3.5 h-3.5 rounded-full bg-emerald-500 animate-pulse flex items-center justify-center text-[7px] font-bold text-emerald-950 leading-none">
                     Model
                </div>
                
                <div title="Embedding Model" 
                     class="w-3.5 h-3.5 rounded-full bg-blue-500 animate-pulse flex items-center justify-center text-[7px] font-bold text-blue-950 leading-none">
                     Engine
                </div>
            </div>
            
            <div hx-get="api.php?action=get_memory_stats" 
                 hx-trigger="load" 
                 class="border-l border-slate-700 pl-4">
                <div class="animate-pulse bg-slate-700 h-3 w-20 rounded"></div>
            </div>
        </div>
    </div>
</div>
