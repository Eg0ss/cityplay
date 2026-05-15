<script setup>
defineProps({
    message: {
        type: String,
    },
});
</script>

<template>
    <div v-show="message">
        <p class="text-[10px] font-black uppercase tracking-widest text-red-500 mt-2">
            ⚠️ {{ message }}
        </p>
    </div>
</template>
