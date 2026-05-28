<script setup>
// Lightweight searchable dropdown — no external deps.
//
// Usage:
//   <SearchableSelect
//      v-model="selectedId"
//      :options="[{ value: 1, label: 'Mathematics', group: 'Science' }, ...]"
//      placeholder="Pick a subject"
//      empty-text="No subjects match"
//      :disabled="false"
//   />
//
// Options can carry an optional `group` key — entries with the same group are
// rendered under a sticky group header. Keyboard nav: arrow keys, Enter,
// Escape. Click outside to close.

import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';

const props = defineProps({
    modelValue: { type: [String, Number, null], default: null },
    options: { type: Array, default: () => [] },        // [{ value, label, group?, sub? }]
    placeholder: { type: String, default: 'Select…' },
    emptyText: { type: String, default: 'No matches' },
    disabled: { type: Boolean, default: false },
    error: { type: String, default: '' },
    label: { type: String, default: '' },
    required: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'change']);

const open = ref(false);
const search = ref('');
const highlightedIdx = ref(0);
const root = ref(null);
const listRef = ref(null);

const selected = computed(() => props.options.find(o => String(o.value) === String(props.modelValue)) || null);

const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.options;
    return props.options.filter(o =>
        (o.label || '').toLowerCase().includes(q)
        || (o.sub || '').toLowerCase().includes(q)
        || String(o.value).toLowerCase().includes(q)
    );
});

// Reset highlight when filter changes
watch(filtered, () => { highlightedIdx.value = 0; });

const onTrigger = () => {
    if (props.disabled) return;
    open.value = !open.value;
    if (open.value) {
        search.value = '';
        nextTick(() => {
            const input = root.value?.querySelector('input.searchable-select__input');
            input?.focus();
        });
    }
};

const close = () => { open.value = false; };

const select = (opt) => {
    emit('update:modelValue', opt.value);
    emit('change', opt);
    close();
};

const clear = () => {
    emit('update:modelValue', null);
    emit('change', null);
    close();
};

const onKey = (e) => {
    if (!open.value) {
        if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
            e.preventDefault();
            onTrigger();
        }
        return;
    }

    if (e.key === 'Escape') {
        close();
        return;
    }
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        highlightedIdx.value = Math.min(highlightedIdx.value + 1, filtered.value.length - 1);
        scrollIntoView();
    }
    if (e.key === 'ArrowUp') {
        e.preventDefault();
        highlightedIdx.value = Math.max(highlightedIdx.value - 1, 0);
        scrollIntoView();
    }
    if (e.key === 'Enter') {
        e.preventDefault();
        const opt = filtered.value[highlightedIdx.value];
        if (opt) select(opt);
    }
};

const scrollIntoView = () => {
    nextTick(() => {
        const el = listRef.value?.querySelector(`[data-idx="${highlightedIdx.value}"]`);
        el?.scrollIntoView({ block: 'nearest' });
    });
};

const onDocClick = (e) => {
    if (root.value && !root.value.contains(e.target)) close();
};

onMounted(() => document.addEventListener('mousedown', onDocClick));
onUnmounted(() => document.removeEventListener('mousedown', onDocClick));

// When grouped, decide whether to insert a group-header element before this item.
const showGroupBefore = (idx) => {
    const o = filtered.value[idx];
    if (!o?.group) return false;
    if (idx === 0) return true;
    return filtered.value[idx - 1]?.group !== o.group;
};
</script>

<template>
    <div class="searchable-select" ref="root">
        <label v-if="label" class="block text-xs font-medium text-ink mb-1">
            {{ label }} <span v-if="required" class="text-maroon">*</span>
        </label>

        <!-- Trigger -->
        <button type="button"
            class="w-full px-3 py-2 text-sm text-left border rounded bg-white flex items-center justify-between gap-2"
            :class="[
                error ? 'border-red-300' : 'border-border',
                disabled ? 'opacity-60 cursor-not-allowed' : 'hover:border-maroon focus:outline-none focus:ring-1 focus:ring-maroon',
                open ? 'ring-1 ring-maroon border-maroon' : '',
            ]"
            :disabled="disabled"
            @click="onTrigger"
            @keydown="onKey">
            <span v-if="selected" class="flex-1 truncate">
                {{ selected.label }}
                <span v-if="selected.sub" class="text-xs text-ink-mute ml-1">{{ selected.sub }}</span>
            </span>
            <span v-else class="flex-1 text-ink-mute italic">{{ placeholder }}</span>
            <span class="text-ink-mute text-xs">▾</span>
        </button>

        <p v-if="error" class="text-xs text-red-600 mt-1">{{ error }}</p>

        <!-- Dropdown -->
        <div v-if="open"
            class="absolute z-50 mt-1 w-full max-w-md bg-white border border-border rounded shadow-lg overflow-hidden"
            style="min-width: 280px;">
            <div class="px-2 py-2 border-b border-border bg-cream">
                <input type="text" v-model="search" @keydown="onKey"
                    placeholder="Type to search…"
                    class="searchable-select__input w-full px-2 py-1 text-sm border border-border rounded bg-white" />
            </div>
            <div ref="listRef" class="max-h-72 overflow-y-auto">
                <template v-if="filtered.length">
                    <template v-for="(opt, idx) in filtered" :key="opt.value">
                        <div v-if="showGroupBefore(idx)"
                            class="sticky top-0 px-3 py-1 text-[10px] uppercase tracking-wider text-ink-mute bg-cream border-b border-border">
                            {{ opt.group }}
                        </div>
                        <button type="button"
                            :data-idx="idx"
                            class="block w-full text-left px-3 py-1.5 text-sm hover:bg-saffron-soft"
                            :class="[
                                idx === highlightedIdx ? 'bg-saffron-soft text-maroon' : '',
                                String(opt.value) === String(modelValue) ? 'font-semibold' : '',
                            ]"
                            @mouseenter="highlightedIdx = idx"
                            @click="select(opt)">
                            {{ opt.label }}
                            <span v-if="opt.sub" class="text-xs text-ink-mute ml-1">{{ opt.sub }}</span>
                        </button>
                    </template>
                </template>
                <div v-else class="px-3 py-4 text-center text-xs text-ink-mute italic">
                    {{ emptyText }}
                </div>
            </div>
            <div v-if="selected" class="border-t border-border px-3 py-1 text-right">
                <button type="button" @click="clear" class="text-[10px] text-red-600 hover:underline">Clear selection</button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.searchable-select { position: relative; }
</style>
