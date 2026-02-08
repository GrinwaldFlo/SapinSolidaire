@if(!app()->isProduction())
<div class="sticky top-0 z-50 bg-yellow-100 border-b border-yellow-400 px-4 py-2 text-center text-sm font-medium text-yellow-800 dark:bg-yellow-900 dark:border-yellow-700 dark:text-yellow-100">
    ⚠️ Site en mode <strong>{{ app()->environment() }}</strong>
</div>
@endif
