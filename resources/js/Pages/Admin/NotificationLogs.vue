<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import { ref, watch } from 'vue';
import { formatDateTime } from '@/utils/date.js';

const props = defineProps({
    logs: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const event = ref(props.filters.event || '');
const channel = ref(props.filters.channel || '');
const status = ref(props.filters.status || '');

const apply = () => {
    const params = {};
    if (event.value) params.event = event.value;
    if (channel.value) params.channel = channel.value;
    if (status.value) params.status = status.value;
    router.get(route('admin.notification-logs.index'), params, { preserveState: true, preserveScroll: true, replace: true });
};

let timer = null;
watch([event, channel, status], () => {
    clearTimeout(timer);
    timer = setTimeout(apply, 250);
});

const statusBadge = (s) => ({
    queued: 'bg-amber-100 text-amber-800',
    sent: 'bg-blue-100 text-blue-800',
    delivered: 'bg-green-100 text-green-800',
    failed: 'bg-red-100 text-red-800',
    stub: 'bg-gray-100 text-gray-700',
}[s] || 'bg-gray-100');

const channelBadge = (c) => ({
    sms: 'bg-blue-100 text-blue-800',
    email: 'bg-purple-100 text-purple-800',
    whatsapp: 'bg-green-100 text-green-800',
}[c] || 'bg-gray-100');
</script>

<template>
    <Head title="Notification Logs" />
    <PortalLayout title="Notification Logs" :breadcrumb="['Admin', 'Notification Logs']">

        <p class="text-sm text-ink-mute mb-3">
            Every queued / sent / failed notification is logged here. Stub-mode sends show as <strong>stub</strong>.
        </p>

        <div class="flex flex-wrap gap-3 items-end mb-4">
            <div>
                <label class="block text-xs font-medium text-ink mb-1">Event</label>
                <input v-model="event" type="text" placeholder="e.g. seat_allotted" class="px-3 py-2 text-sm border border-border rounded" />
            </div>
            <div>
                <label class="block text-xs font-medium text-ink mb-1">Channel</label>
                <select v-model="channel" class="px-3 py-2 text-sm border border-border rounded bg-white">
                    <option value="">All</option>
                    <option value="sms">SMS</option>
                    <option value="email">Email</option>
                    <option value="whatsapp">WhatsApp</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-ink mb-1">Status</label>
                <select v-model="status" class="px-3 py-2 text-sm border border-border rounded bg-white">
                    <option value="">All</option>
                    <option value="queued">Queued</option>
                    <option value="sent">Sent</option>
                    <option value="delivered">Delivered</option>
                    <option value="failed">Failed</option>
                    <option value="stub">Stub</option>
                </select>
            </div>
        </div>

        <div class="bg-white border border-border rounded overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-cream text-ink-mute text-xs uppercase tracking-wider">
                    <tr>
                        <th class="text-left px-4 py-2">When</th>
                        <th class="text-left px-4 py-2">Event</th>
                        <th class="text-left px-4 py-2">Channel</th>
                        <th class="text-left px-4 py-2">Recipient</th>
                        <th class="text-left px-4 py-2">Status</th>
                        <th class="text-left px-4 py-2">Provider</th>
                        <th class="text-left px-4 py-2">Body / Error</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="l in logs.data" :key="l.id" class="border-t border-border align-top">
                        <td class="px-4 py-2 text-xs">{{ formatDateTime(l.created_at) }}</td>
                        <td class="px-4 py-2 font-mono text-xs">{{ l.event }}</td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-0.5 text-xs rounded" :class="channelBadge(l.channel)">{{ l.channel }}</span>
                        </td>
                        <td class="px-4 py-2 text-xs">
                            {{ l.recipient }}
                            <div v-if="l.user" class="text-ink-mute">{{ l.user.name }}</div>
                        </td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-0.5 text-xs rounded" :class="statusBadge(l.status)">{{ l.status }}</span>
                        </td>
                        <td class="px-4 py-2 font-mono text-xs">
                            {{ l.provider_code || '—' }}
                            <div v-if="l.provider_message_id" class="text-ink-mute">{{ l.provider_message_id }}</div>
                        </td>
                        <td class="px-4 py-2 text-xs max-w-md">
                            <div v-if="l.status === 'failed'" class="text-red-600">{{ l.error }}</div>
                            <div v-else class="text-ink-mute whitespace-pre-line">{{ l.rendered_body?.substring(0, 200) }}{{ l.rendered_body?.length > 200 ? '…' : '' }}</div>
                        </td>
                    </tr>
                    <tr v-if="!logs.data.length">
                        <td colspan="7" class="px-4 py-6 text-center text-ink-mute text-sm">No logs match. Fire an event to see entries appear here.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="logs.last_page > 1" class="mt-4 flex items-center justify-center gap-1 text-sm">
            <template v-for="(l, i) in logs.links" :key="i">
                <Link v-if="l.url" :href="l.url" v-html="l.label"
                    class="px-3 py-1 rounded border"
                    :class="l.active ? 'bg-maroon text-white border-maroon' : 'border-border hover:bg-cream'" />
                <span v-else v-html="l.label"
                    class="px-3 py-1 rounded border border-border text-ink-mute opacity-50 cursor-not-allowed" />
            </template>
        </div>
    </PortalLayout>
</template>
