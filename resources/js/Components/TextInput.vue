<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="w-full dark:bg-white/5 bg-gray-50 border-2 dark:border-white/5 border-gray-100 rounded-2xl py-4 px-6 focus:ring-2 focus:ring-[#FF9F1C] focus:border-transparent transition-all font-bold dark:text-white text-gray-900 placeholder-gray-400 dark:placeholder-gray-600 shadow-sm"
        v-model="model"
        ref="input"
    />
</template>
