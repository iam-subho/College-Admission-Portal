<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    section: { type: String, required: true },
});

const page = usePage();
const status = computed(() => page.props.profile_status || null);
const isComplete = computed(() => status.value && status.value[props.section]);
const missing = computed(() => (status.value?.missing?.[props.section] || []));
</script>

<template>
    <div v-if="status" class="mb-4 px-4 py-3 rounded border text-sm"
        :class="isComplete ? 'border-green-300 bg-green-50' : 'border-amber-300 bg-amber-50'">
        <div class="flex items-center gap-2 font-semibold"
            :class="isComplete ? 'text-green-800' : 'text-amber-800'">
            <span v-if="isComplete">✓ This section is complete.</span>
            <span v-else>⚠ This section is pending — please complete:</span>
        </div>
        <ul v-if="!isComplete && missing.length" class="text-xs mt-1 text-amber-700 list-disc list-inside">
            <li v-for="m in missing" :key="m">{{ m }}</li>
        </ul>
    </div>
</template>
