<script setup>
defineProps({
    variant: { type: String, default: 'primary' },
    type: { type: String, default: 'button' },
    disabled: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
    block: { type: Boolean, default: false },
});
</script>

<template>
    <button
        :type="type"
        :disabled="disabled || loading"
        class="px-4 py-2 text-sm font-semibold rounded transition-colors focus:outline-none focus:ring-2 focus:ring-offset-1"
        :class="[
            block ? 'w-full' : '',
            variant === 'primary' ? 'bg-saffron text-white hover:bg-saffron/90 focus:ring-saffron' : '',
            variant === 'secondary' ? 'bg-navy text-white hover:bg-navy-deep focus:ring-navy' : '',
            variant === 'ghost' ? 'bg-transparent text-maroon hover:bg-saffron-soft focus:ring-maroon border border-border' : '',
            variant === 'danger' ? 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-600' : '',
            (disabled || loading) ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer',
        ]"
    >
        <span v-if="loading" class="inline-block w-3 h-3 mr-2 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
        <slot />
    </button>
</template>
