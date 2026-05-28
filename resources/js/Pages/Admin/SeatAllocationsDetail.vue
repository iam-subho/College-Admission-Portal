<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import Button from '@/Components/Ui/Button.vue';
import { computed, ref } from 'vue';
import { formatDateTime } from '@/utils/date.js';

const props = defineProps({
    round: { type: Object, required: true },
    reservations: { type: Array, required: true },
    allocations: { type: Array, required: true },
});

const statusFilter = ref('');

const filtered = computed(() => {
    if (!statusFilter.value) return props.allocations;
    return props.allocations.filter(a => a.status === statusFilter.value);
});

const statusBadge = (s) => ({
    allotted: 'bg-amber-100 text-amber-800',
    accepted: 'bg-blue-100 text-blue-800',
    admitted: 'bg-green-100 text-green-800',
    declined: 'bg-red-100 text-red-800',
    expired: 'bg-gray-200 text-gray-700',
    withdrawn: 'bg-amber-200 text-amber-900',
}[s] || 'bg-gray-100');

const generate = () => {
    if (!confirm('Generate / re-generate seat allotment from the merit list?')) return;
    router.post(route('admin.seat-allocations.generate', props.round.id), {}, { preserveScroll: true });
};
const lock = () => {
    if (!confirm('Lock allotment? No further changes can be made.')) return;
    router.post(route('admin.seat-allocations.lock', props.round.id), {}, { preserveScroll: true });
};
</script>

<template>
    <Head :title="`Seats · ${round.program?.code} R${round.round_number}`" />
    <PortalLayout :title="`Seat Allocations · ${round.program?.code} · ${round.name}`"
        :breadcrumb="['Admin', 'Seat Allocations', round.name]">

        <div class="flex items-center justify-between mb-4">
            <div class="text-sm text-ink-mute">
                Round <strong>#{{ round.round_number }}</strong> · Programme <span class="font-mono">{{ round.program?.code }}</span>
                · Session <strong>{{ round.session?.code }}</strong>
                · Window <strong>{{ round.acceptance_window_days }} days</strong>
            </div>
            <div class="space-x-2">
                <Button @click="generate">{{ round.allotment_generated_at ? 'Re-Generate' : 'Generate' }}</Button>
                <button v-if="!round.allotment_locked_at" @click="lock"
                    class="px-3 py-1.5 text-xs border border-red-300 text-red-600 rounded hover:bg-red-50">
                    Lock Allotment
                </button>
                <span v-else class="px-2 py-0.5 text-xs rounded bg-red-100 text-red-700">Locked</span>
            </div>
        </div>

        <!-- Per-category seat board -->
        <section class="bg-white border border-border rounded mb-6 p-4">
            <h2 class="font-serif text-base text-maroon mb-3">Seat Board by Category</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                <div v-for="r in reservations" :key="r.category?.id" class="border border-border rounded p-3 bg-cream/50">
                    <div class="flex items-baseline justify-between">
                        <div class="font-mono text-sm text-maroon">{{ r.category?.code }}</div>
                        <div class="text-xs text-ink-mute">{{ r.category?.name }}</div>
                    </div>
                    <div class="mt-2 grid grid-cols-2 gap-1 text-xs font-mono">
                        <div>Seats: <strong>{{ r.seats }}</strong></div>
                        <div>Open: <strong class="text-green-700">{{ r.open_count }}</strong></div>
                        <div>Allotted: {{ r.allotted }}</div>
                        <div>Accepted: {{ r.accepted }}</div>
                        <div class="text-green-700">Admitted: {{ r.admitted }}</div>
                        <div class="text-red-600">Declined+Exp: {{ r.declined + r.expired }}</div>
                    </div>
                    <div class="mt-2 text-[10px] uppercase tracking-wider"
                        :class="r.open_count >= r.seats ? 'text-green-700' : 'text-amber-700'">
                        {{ r.open_count >= r.seats ? '✓ All seats covered' : `${r.seats - r.open_count} vacant` }}
                    </div>
                </div>
                <div v-if="!reservations.length" class="md:col-span-3 text-center text-xs text-ink-mute italic py-4">
                    No reservation matrix configured for this programme/session.
                </div>
            </div>
        </section>

        <!-- Filter -->
        <div class="flex items-end gap-3 mb-3">
            <div>
                <label class="block text-xs font-medium text-ink mb-1">Filter status</label>
                <select v-model="statusFilter" class="px-3 py-2 text-sm border border-border rounded bg-white">
                    <option value="">All</option>
                    <option value="allotted">Allotted (pending response)</option>
                    <option value="accepted">Accepted (fee pending)</option>
                    <option value="admitted">Admitted (fee paid)</option>
                    <option value="declined">Declined</option>
                    <option value="expired">Expired</option>
                    <option value="withdrawn">Withdrawn</option>
                </select>
            </div>
        </div>

        <div class="bg-white border border-border rounded overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-cream text-ink-mute text-xs uppercase tracking-wider">
                    <tr>
                        <th class="text-right px-4 py-2 w-16">Cat Rank</th>
                        <th class="text-left px-4 py-2">Application</th>
                        <th class="text-left px-4 py-2">Applicant</th>
                        <th class="text-left px-4 py-2">Category</th>
                        <th class="text-left px-4 py-2">Status</th>
                        <th class="text-left px-4 py-2">Source</th>
                        <th class="text-left px-4 py-2">Allotted</th>
                        <th class="text-left px-4 py-2">Expires</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="a in filtered" :key="a.id" class="border-t border-border align-top">
                        <td class="px-4 py-1.5 text-right font-mono">{{ a.category_rank ?? '—' }}</td>
                        <td class="px-4 py-1.5 font-mono text-xs">{{ a.application_number }}</td>
                        <td class="px-4 py-1.5 text-xs">
                            {{ a.applicant_name }}
                            <div class="text-ink-mute">{{ a.applicant_email }}</div>
                        </td>
                        <td class="px-4 py-1.5 text-xs font-mono">{{ a.category_code || '—' }}</td>
                        <td class="px-4 py-1.5">
                            <span class="px-2 py-0.5 text-xs rounded" :class="statusBadge(a.status)">{{ a.status }}</span>
                            <span v-if="a.is_expired && a.status === 'allotted'" class="ml-1 px-1 text-[10px] rounded bg-red-100 text-red-700">window passed</span>
                        </td>
                        <td class="px-4 py-1.5 text-xs">{{ a.source }}</td>
                        <td class="px-4 py-1.5 text-xs">{{ a.allotted_at ? formatDateTime(a.allotted_at) : '—' }}</td>
                        <td class="px-4 py-1.5 text-xs">{{ a.expires_at ? formatDateTime(a.expires_at) : '—' }}</td>
                    </tr>
                    <tr v-if="!filtered.length">
                        <td colspan="8" class="px-4 py-6 text-center text-ink-mute text-sm">No allocations match the filter.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            <Link :href="route('admin.seat-allocations.index')" class="text-xs text-maroon hover:underline">← Back to all rounds</Link>
        </div>
    </PortalLayout>
</template>
