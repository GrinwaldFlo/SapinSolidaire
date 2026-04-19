<div x-show="showImageModal" x-transition.opacity class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4" @click.self="showImageModal = false" @keydown.escape.window="showImageModal = false" x-cloak>
    <div class="relative max-w-full max-h-full flex items-center justify-center">
        <button type="button" @click="showImageModal = false" class="absolute -top-3 -right-3 bg-white dark:bg-zinc-700 text-gray-800 dark:text-white rounded-full w-8 h-8 flex items-center justify-center shadow-lg hover:bg-gray-100 dark:hover:bg-zinc-600 z-10">
            ✕
        </button>
        <img :src="imageUrl" :alt="imageAlt" @click="showImageModal = false" class="max-w-full max-h-[90vh] rounded-lg shadow-2xl cursor-pointer">
    </div>
</div>
