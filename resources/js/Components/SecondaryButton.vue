<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex items-center justify-center rounded-2xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 dark:text-white text-gray-700 px-8 py-4 text-[10px] font-black uppercase tracking-[0.3em] shadow-sm transition-all hover:dark:bg-white/10 hover:bg-gray-100 active:scale-95 disabled:opacity-50"
    >
        <slot />
    </button>
</template>
