<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import { ref, watch } from 'vue';
import { formatDateTime } from '@/utils/date.js';

const props = defineProps({
    entries: { type: Object, required: true },
    filter: { type: Object, required: true },
    log_names: { type: Array, required: true },
    events: { type: Array, required: true },
});

const log_name = ref(props.filter.log_name || '');
const event = ref(props.filter.event || '');
const causer_email = ref(props.filter.causer_email || '');
const from = ref(props.filter.from || '');
const to = ref(props.filter.to || '');
const q = ref(props.filter.q || '');

const apply = () => {
    const params = {};
    if (log_name.value) params.log_name = log_name.value;
    if (event.value) params.event = event.value;
    if (causer_email.value) params.causer_email = causer_email.value;
    if (from.value) params.from = from.value;
    if (to.value) params.to = to.value;
    if (q.value) params.q = q.value;
    router.get(route('admin.audit-log.index'), params,
        { preserveState: true, preserveScroll: true, replace: true });
};

let timer = null;
watch([log_name, event, causer_email, from, to, q], () => {
    clearTimeout(timer);
    timer = setTimeout(apply, 300);
});

const expanded = ref({});
const toggle = (id) => { expanded.value[id] = !expanded.value[id]; };

const eventBadge = (e) => ({
    created: 'bg-green-100 text-green-800',
    updated: 'bg-blue-100 text-blue-800',
    deleted: 'bg-red-100 text-red-800',
    restored: 'bg-amber-100 text-amber-800',
}[e] || 'bg-gray-100');

const shortSubject = (type) => {
    if (!type) return '—';
    return type.split('\\').pop();
};
</script>

<template>
    <Head title="Audit Log" />
    <PortalLayout title="Audit Log" :breadcrumb="['Admin', 'Audit Log']">

        <p class="text-sm text-ink-mute mb-3">
            All changes to critical models (applications, payments, refunds, documents, merit lists, seat allocations, withdrawals, eligibility rules, notification templates) are recorded here. Click a row to expand the diff.
        </p>

        <div class="grid grid-cols-2 md:grid-cols-6 gap-2 mb-4">
            <div>
                <label class="block text-xs font-medium text-ink mb-1">Entity</label>
                <select v-model="log_name" class="w-full px-2 py-1.5 text-sm border border-border rounded bg-white">
                    <option value="">All</option>
                    <option v-for="n in log_names" :key="n" :value="n">{{ n }}</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-ink mb-1">Action</label>
                <select v-model="event" class="w-full px-2 py-1.5 text-sm border border-border rounded bg-white">
                    <option value="">All</option>
                    <option v-for="e in events" :key="e" :value="e">{{ e }}</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-ink mb-1">User Email</label>
                <input v-model="causer_email" type="text" placeholder="contains" class="w-full px-2 py-1.5 text-sm border border-border rounded" />
            </div>
            <div>
                <label class="block text-xs font-medium text-ink mb-1">From</label>
                <input v-model="from" type="date" class="w-full px-2 py-1.5 text-sm border border-border rounded" />
            </div>
            <div>
                <label class="block text-xs font-medium text-ink mb-1">To</label>
                <input v-model="to" type="date" class="w-full px-2 py-1.5 text-sm border border-border rounded" />
            </div>
            <div>
                <label class="block text-xs font-medium text-ink mb-1">Description</label>
                <input v-model="q" type="text" placeholder="contains" class="w-full px-2 py-1.5 text-sm border border-border rounded" />
            </div>
        </div>

        <div class="bg-white border border-border rounded overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-cream text-ink-mute text-xs uppercase tracking-wider">
                    <tr>
                        <th class="text-left px-3 py-2 w-44">When</th>
                        <th class="text-left px-3 py-2 w-32">Entity</th>
                        <th class="text-left px-3 py-2 w-24">Action</th>
                        <th class="text-left px-3 py-2 w-44">Subject</th>
                        <th class="text-left px-3 py-2 w-44">User</th>
                        <th class="text-left px-3 py-2">Description</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="entry in entries.data" :key="entry.id">
                        <tr class="border-t border-border cursor-pointer hover:bg-cream/50" @click="toggle(entry.id)">
                            <td class="px-3 py-2 text-xs">{{ formatDateTime(entry.created_at) }}</td>
                            <td class="px-3 py-2 font-mono text-xs">{{ entry.log_name || '—' }}</td>
                            <td class="px-3 py-2">
                                <span class="px-2 py-0.5 text-xs rounded font-mono uppercase" :class="eventBadge(entry.event)">{{ entry.event || '—' }}</span>
                            </td>
                            <td class="px-3 py-2 text-xs font-mono">{{ shortSubject(entry.subject_type) }} #{{ entry.subject_id || '—' }}</td>
                            <td class="px-3 py-2 text-xs">
                                {{ entry.causer_name || 'System' }}
                                <div class="text-ink-mute">{{ entry.causer_email }}</div>
                            </td>
                            <td class="px-3 py-2 text-xs">{{ entry.description }}</td>
                        </tr>
                        <tr v-if="expanded[entry.id] && entry.changes" class="border-t border-border bg-cream/30">
                            <td colspan="6" class="px-6 py-3 text-xs">
                                <div v-if="entry.changes.old || entry.changes.attributes" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div v-if="entry.changes.old">
                                        <div class="text-[10px] uppercase tracking-wider text-red-600 mb-1">Before</div>
                                        <pre class="bg-red-50 border border-red-200 rounded p-2 font-mono text-[11px] overflow-x-auto">{{ JSON.stringify(entry.changes.old, null, 2) }}</pre>
                                    </div>
                                    <div v-if="entry.changes.attributes">
                                        <div class="text-[10px] uppercase tracking-wider text-green-700 mb-1">After</div>
                                        <pre class="bg-green-50 border border-green-200 rounded p-2 font-mono text-[11px] overflow-x-auto">{{ JSON.stringify(entry.changes.attributes, null, 2) }}</pre>
                                    </div>
                                </div>
                                <pre v-else class="bg-white border border-border rounded p-2 font-mono text-[11px] overflow-x-auto">{{ JSON.stringify(entry.changes, null, 2) }}</pre>
                            </td>
                        </tr>
                    </template>
                    <tr v-if="!entries.data.length">
                        <td colspan="6" class="px-3 py-6 text-center text-ink-mute text-sm">No entries match.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="entries.last_page > 1" class="mt-4 flex items-center justify-center gap-1 text-sm">
            <template v-for="(l, i) in entries.links" :key="i">
                <Link v-if="l.url" :href="l.url" v-html="l.label"
                    class="px-3 py-1 rounded border"
                    :class="l.active ? 'bg-maroon text-white border-maroon' : 'border-border hover:bg-cream'" />
                <span v-else v-html="l.label"
                    class="px-3 py-1 rounded border border-border text-ink-mute opacity-50 cursor-not-allowed" />
            </template>
        </div>
    </PortalLayout>
</template>
