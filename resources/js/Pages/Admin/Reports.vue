<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import Button from '@/Components/Ui/Button.vue';
import { ref, reactive, computed, watch } from 'vue';

const props = defineProps({
    groups: { type: Object, required: true },
    selected: { type: Object, default: null },
    filters: { type: Object, default: () => ({}) },
    rows: { type: Object, default: null },
    summary: { type: Object, default: () => ({}) },
});

const filterState = reactive({ ...(props.filters || {}) });

const GROUP_TITLES = {
    operational: 'Operational',
    financial: 'Financial',
    compliance: 'Compliance & Statutory',
    statutory: 'Statutory',
};

const apply = () => {
    if (!props.selected) return;
    router.get(route('admin.reports.show', props.selected.key), { ...filterState },
        { preserveState: true, preserveScroll: true, replace: true });
};

let timer = null;
watch(filterState, () => {
    clearTimeout(timer);
    timer = setTimeout(apply, 350);
}, { deep: true });

const exportUrl = computed(() => {
    if (!props.selected) return '#';
    const qs = new URLSearchParams(filterState).toString();
    return `/admin/reports/${props.selected.key}/export${qs ? '?' + qs : ''}`;
});

const aisheUrl = computed(() => {
    const qs = filterState.session ? `?session=${filterState.session}` : '';
    return `/admin/aishe/export${qs}`;
});

const fmtCell = (col, row) => {
    let v = row[col.key];
    if (v == null) return '—';
    if (col.num && typeof v === 'number') {
        return new Intl.NumberFormat('en-IN', { maximumFractionDigits: 2 }).format(v);
    }
    if (typeof v === 'string' && /^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2})?$/.test(v)) {
        return v.substring(0, 10);
    }
    return v;
};
</script>

<template>
    <Head :title="`Reports — ${selected?.title || 'Index'}`" />
    <PortalLayout title="Reports" :breadcrumb="['Admin', 'Reports', selected?.title || '']">

        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">

            <!-- Sidebar -->
            <aside class="md:col-span-1 bg-white border border-border rounded p-3">
                <h3 class="font-serif text-sm text-maroon px-1 mb-2">Reports</h3>
                <div v-for="(reports, group) in groups" :key="group" class="mb-3">
                    <div class="text-[10px] uppercase tracking-wider text-ink-mute px-1 mb-1">
                        {{ GROUP_TITLES[group] || group }}
                    </div>
                    <ul class="space-y-0.5">
                        <li v-for="r in reports" :key="r.key">
                            <Link :href="route('admin.reports.show', r.key)"
                                class="block px-2 py-1.5 text-xs rounded"
                                :class="selected?.key === r.key
                                    ? 'bg-maroon text-white font-semibold'
                                    : 'text-ink hover:bg-cream'">
                                {{ r.title }}
                            </Link>
                        </li>
                    </ul>
                </div>
                <div class="border-t border-border pt-3 mt-3">
                    <div class="text-[10px] uppercase tracking-wider text-ink-mute px-1 mb-1">Statutory</div>
                    <a :href="aisheUrl"
                        class="block px-2 py-1.5 text-xs rounded text-ink hover:bg-cream">
                        AISHE Export (CSV)
                    </a>
                </div>
            </aside>

            <!-- Main -->
            <section class="md:col-span-4">
                <div v-if="!selected" class="bg-white border border-border rounded p-6 text-center">
                    <p class="text-ink-mute text-sm">Pick a report from the sidebar to begin.</p>
                </div>

                <template v-else>
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="font-serif text-lg text-maroon">{{ selected.title }}</h2>
                        <a :href="exportUrl" target="_blank"
                            class="px-3 py-1.5 text-xs bg-saffron text-white rounded hover:bg-saffron/90">
                            ⬇ Export CSV
                        </a>
                    </div>

                    <!-- Filters -->
                    <div v-if="selected.filterSchema?.length" class="bg-white border border-border rounded p-3 mb-3">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div v-for="f in selected.filterSchema" :key="f.key">
                                <label class="block text-xs font-medium text-ink mb-1">{{ f.label }}</label>
                                <input v-if="f.type === 'date'" v-model="filterState[f.key]" type="date"
                                    class="w-full px-3 py-1.5 text-sm border border-border rounded" />
                                <input v-else v-model="filterState[f.key]" type="text"
                                    class="w-full px-3 py-1.5 text-sm border border-border rounded" />
                            </div>
                        </div>
                    </div>

                    <!-- Summary -->
                    <div v-if="summary && Object.keys(summary).length"
                        class="bg-cream border border-border rounded p-3 mb-3 grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                        <div v-for="(value, key) in summary" :key="key">
                            <div class="text-[10px] uppercase tracking-wider text-ink-mute">{{ key.replace(/_/g, ' ') }}</div>
                            <div class="font-mono font-semibold text-maroon">{{ value }}</div>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="bg-white border border-border rounded overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-cream text-ink-mute text-xs uppercase tracking-wider">
                                    <tr>
                                        <th v-for="c in selected.columns" :key="c.key"
                                            :class="c.num ? 'text-right px-3 py-2' : 'text-left px-3 py-2'">
                                            {{ c.label }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, i) in rows?.data || []" :key="i" class="border-t border-border">
                                        <td v-for="c in selected.columns" :key="c.key"
                                            :class="c.num ? 'text-right px-3 py-1.5 font-mono' : 'text-left px-3 py-1.5'">
                                            {{ fmtCell(c, row) }}
                                        </td>
                                    </tr>
                                    <tr v-if="!rows?.data?.length">
                                        <td :colspan="selected.columns.length" class="px-3 py-6 text-center text-ink-mute text-sm">
                                            No rows match the filters.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="rows?.last_page > 1" class="mt-3 flex items-center justify-center gap-1 text-sm">
                        <template v-for="(l, i) in rows.links" :key="i">
                            <Link v-if="l.url" :href="l.url" v-html="l.label"
                                class="px-3 py-1 rounded border"
                                :class="l.active ? 'bg-maroon text-white border-maroon' : 'border-border hover:bg-cream'" />
                            <span v-else v-html="l.label"
                                class="px-3 py-1 rounded border border-border text-ink-mute opacity-50 cursor-not-allowed" />
                        </template>
                    </div>
                </template>
            </section>
        </div>
    </PortalLayout>
</template>
