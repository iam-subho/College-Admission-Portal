<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import { ref, watch } from 'vue';
import { formatDateTime } from '@/utils/date.js';

const props = defineProps({
    consents: { type: Object, required: true },
    filter: { type: Object, required: true },
    scopes: { type: Array, required: true },
});

const scope = ref(props.filter.scope || '');
const email = ref(props.filter.email || '');
const from = ref(props.filter.from || '');
const to = ref(props.filter.to || '');

const apply = () => {
    const params = {};
    if (scope.value) params.scope = scope.value;
    if (email.value) params.email = email.value;
    if (from.value) params.from = from.value;
    if (to.value) params.to = to.value;
    router.get(route('admin.dpdp-consents.index'), params,
        { preserveState: true, preserveScroll: true, replace: true });
};

let timer = null;
watch([scope, email, from, to], () => {
    clearTimeout(timer);
    timer = setTimeout(apply, 300);
});

const expanded = ref({});
const toggle = (id) => { expanded.value[id] = !expanded.value[id]; };

const scopeBadge = (s) => ({
    registration: 'bg-blue-100 text-blue-800',
    profile_lock: 'bg-purple-100 text-purple-800',
    payment: 'bg-green-100 text-green-800',
    document_upload: 'bg-amber-100 text-amber-800',
    digilocker: 'bg-pink-100 text-pink-800',
}[s] || 'bg-gray-100');
</script>

<template>
    <Head title="DPDP Consents" />
    <PortalLayout title="DPDP Consent Log" :breadcrumb="['Admin', 'DPDP Consents']">

        <p class="text-sm text-ink-mute mb-3">
            Statutory record of explicit consents collected from students under the Digital Personal Data Protection Act, 2023. Every consent records the user, scope, IP, user agent and timestamp. Click a row for metadata.
        </p>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mb-4">
            <div>
                <label class="block text-xs font-medium text-ink mb-1">Scope</label>
                <select v-model="scope" class="w-full px-2 py-1.5 text-sm border border-border rounded bg-white">
                    <option value="">All</option>
                    <option v-for="s in scopes" :key="s" :value="s">{{ s }}</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-ink mb-1">User Email</label>
                <input v-model="email" type="text" placeholder="contains" class="w-full px-2 py-1.5 text-sm border border-border rounded" />
            </div>
            <div>
                <label class="block text-xs font-medium text-ink mb-1">From</label>
                <input v-model="from" type="date" class="w-full px-2 py-1.5 text-sm border border-border rounded" />
            </div>
            <div>
                <label class="block text-xs font-medium text-ink mb-1">To</label>
                <input v-model="to" type="date" class="w-full px-2 py-1.5 text-sm border border-border rounded" />
            </div>
        </div>

        <div class="bg-white border border-border rounded overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-cream text-ink-mute text-xs uppercase tracking-wider">
                    <tr>
                        <th class="text-left px-3 py-2 w-44">Accepted At</th>
                        <th class="text-left px-3 py-2 w-32">Scope</th>
                        <th class="text-left px-3 py-2 w-20">Version</th>
                        <th class="text-left px-3 py-2 w-56">User</th>
                        <th class="text-left px-3 py-2 w-32">IP</th>
                        <th class="text-left px-3 py-2">User Agent</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="entry in consents.data" :key="entry.id">
                        <tr class="border-t border-border cursor-pointer hover:bg-cream/50" @click="toggle(entry.id)">
                            <td class="px-3 py-2 text-xs">{{ formatDateTime(entry.accepted_at) }}</td>
                            <td class="px-3 py-2">
                                <span class="px-2 py-0.5 text-xs rounded font-mono" :class="scopeBadge(entry.scope)">{{ entry.scope || '—' }}</span>
                            </td>
                            <td class="px-3 py-2 font-mono text-xs">{{ entry.version }}</td>
                            <td class="px-3 py-2 text-xs">
                                <div>{{ entry.user?.name || 'Unknown' }}</div>
                                <div class="text-ink-mute">{{ entry.user?.email }}</div>
                            </td>
                            <td class="px-3 py-2 font-mono text-xs">{{ entry.ip || '—' }}</td>
                            <td class="px-3 py-2 text-xs truncate max-w-md">{{ entry.user_agent || '—' }}</td>
                        </tr>
                        <tr v-if="expanded[entry.id] && entry.metadata" class="border-t border-border bg-cream/30">
                            <td colspan="6" class="px-6 py-3 text-xs">
                                <div class="text-[10px] uppercase tracking-wider text-ink-mute mb-1">Context Metadata</div>
                                <pre class="bg-white border border-border rounded p-2 font-mono text-[11px] overflow-x-auto">{{ JSON.stringify(entry.metadata, null, 2) }}</pre>
                            </td>
                        </tr>
                    </template>
                    <tr v-if="!consents.data.length">
                        <td colspan="6" class="px-3 py-6 text-center text-ink-mute text-sm">No consents match.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="consents.last_page > 1" class="mt-4 flex items-center justify-center gap-1 text-sm">
            <template v-for="(l, i) in consents.links" :key="i">
                <Link v-if="l.url" :href="l.url" v-html="l.label"
                    class="px-3 py-1 rounded border"
                    :class="l.active ? 'bg-maroon text-white border-maroon' : 'border-border hover:bg-cream'" />
                <span v-else v-html="l.label"
                    class="px-3 py-1 rounded border border-border text-ink-mute opacity-50 cursor-not-allowed" />
            </template>
        </div>
    </PortalLayout>
</template>
