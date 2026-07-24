<script setup>
import { computed } from 'vue';
import { useSite } from '@/Composables/useSite.js';

const props = defineProps({
    size: { type: String, default: 'md' },
    tone: { type: String, default: 'dark' },
    subtitle: { type: String, default: undefined },
});

const { collegeShort, collegeName, estdYear, portalTagline } = useSite();

const SIZES = {
    sm: { crest: 'w-9 h-9 text-[9px]', name: 'text-base', sub: 'text-[10px]' },
    md: { crest: 'w-10 h-10 text-xs', name: 'text-base', sub: 'text-xs' },
    lg: { crest: 'w-12 h-12 text-[10px]', name: 'text-xl', sub: 'text-xs' },
};

const dims = computed(() => SIZES[props.size] || SIZES.md);
const isDark = computed(() => props.tone === 'dark');

const line = computed(() => (props.subtitle === undefined ? portalTagline.value : props.subtitle));
</script>

<template>
    <div class="flex items-center gap-3">
        <div
            class="rounded-full bg-maroon text-white font-bold flex flex-col items-center justify-center leading-tight text-center shrink-0"
            :class="dims.crest"
        >
            <span>{{ collegeShort }}</span>
            <span v-if="estdYear">{{ estdYear }}</span>
        </div>
        <div class="leading-tight">
            <div class="font-serif" :class="[dims.name, isDark ? 'text-white' : 'text-maroon']">
                {{ collegeName }}
            </div>
            <div v-if="line" :class="[dims.sub, isDark ? 'text-gray-300' : 'text-ink-mute']">
                {{ line }}
            </div>
        </div>
    </div>
</template>
