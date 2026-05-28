<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import Button from '@/Components/Ui/Button.vue';
import { formatDateTime } from '@/utils/date.js';

defineProps({
    rounds: { type: Array, required: true },
});

const generate = (r) => {
    if (!confirm(`Generate seat allotment from the merit list for round "${r.name}"? Top N qualifying candidates per category will be offered seats.`)) return;
    router.post(route('admin.seat-allocations.generate', r.id), {}, { preserveScroll: true });
};

const lock = (r) => {
    if (!confirm(`Lock allotment for round "${r.name}"? No further generations or rollovers can run.`)) return;
    router.post(route('admin.seat-allocations.lock', r.id), {}, { preserveScroll: true });
};
</script>

<template>
    <Head title="Seat Allocations" />
    <PortalLayout title="Seat Allocations" :breadcrumb="['Admin', 'Seat Allocations']">

        <p class="text-sm text-ink-mute mb-4">
            Generate seat allotments from a published merit list. Top N qualifying candidates per category get an allotment offer
            within the acceptance window. Declines and expiries roll over automatically to the next waitlisted candidate.
        </p>

        <div class="bg-white border border-border rounded overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-cream text-ink-mute text-xs uppercase tracking-wider">
                    <tr>
                        <th class="text-left px-4 py-2">Round</th>
                        <th class="text-left px-4 py-2">Programme</th>
                        <th class="text-left px-4 py-2">Session</th>
                        <th class="text-left px-4 py-2">Merit List</th>
                        <th class="text-right px-4 py-2">Allotted</th>
                        <th class="text-right px-4 py-2">Accepted</th>
                        <th class="text-right px-4 py-2">Admitted</th>
                        <th class="text-right px-4 py-2">Declined</th>
                        <th class="text-left px-4 py-2">Generated</th>
                        <th class="text-right px-4 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="r in rounds" :key="r.id" class="border-t border-border">
                        <td class="px-4 py-2 font-mono text-xs">#{{ r.round_number }} · {{ r.name }}</td>
                        <td class="px-4 py-2">
                            <span class="font-mono text-xs">{{ r.program?.code }}</span> · {{ r.program?.name }}
                        </td>
                        <td class="px-4 py-2 font-mono text-xs">{{ r.session?.code }}</td>
                        <td class="px-4 py-2">
                            <span v-if="r.merit_list_status === 'published'" class="px-2 py-0.5 text-xs rounded bg-green-100 text-green-800">Published</span>
                            <span v-else-if="r.merit_list_status === 'draft'" class="px-2 py-0.5 text-xs rounded bg-amber-100 text-amber-800">Draft</span>
                            <span v-else class="text-xs text-ink-mute italic">No list</span>
                        </td>
                        <td class="px-4 py-2 text-right font-mono">{{ r.counts.allotted }}</td>
                        <td class="px-4 py-2 text-right font-mono">{{ r.counts.accepted }}</td>
                        <td class="px-4 py-2 text-right font-mono text-green-700 font-semibold">{{ r.counts.admitted }}</td>
                        <td class="px-4 py-2 text-right font-mono text-red-600">{{ r.counts.declined + r.counts.expired }}</td>
                        <td class="px-4 py-2 text-xs">{{ r.allotment_generated_at ? formatDateTime(r.allotment_generated_at) : '—' }}</td>
                        <td class="px-4 py-2 text-right space-x-2 whitespace-nowrap">
                            <button v-if="r.merit_list_status === 'published'" @click="generate(r)"
                                class="text-xs px-2 py-1 bg-maroon text-white rounded hover:bg-maroon/90">
                                {{ r.allotment_generated_at ? 'Re-Generate' : 'Generate' }}
                            </button>
                            <Link :href="route('admin.seat-allocations.show', r.id)"
                                class="text-xs text-maroon hover:underline">View →</Link>
                        </td>
                    </tr>
                    <tr v-if="!rounds.length">
                        <td colspan="10" class="px-4 py-6 text-center text-ink-mute text-sm">No admission rounds yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </PortalLayout>
</template>
