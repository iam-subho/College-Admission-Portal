<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import Button from '@/Components/Ui/Button.vue';
import { formatDateTime } from '@/utils/date.js';

defineProps({
    rounds: { type: Array, required: true },
});

const generate = (r) => router.post(route('admin.merit-lists.generate', r.id));

const statusBadge = (s) => ({
    draft: 'bg-amber-100 text-amber-800',
    published: 'bg-green-100 text-green-800',
    archived: 'bg-gray-100 text-gray-700',
}[s] || 'bg-gray-100');
</script>

<template>
    <Head title="Merit Lists" />
    <PortalLayout title="Merit Lists" :breadcrumb="['Admin', 'Merit Lists']">

        <p class="text-sm text-ink-mute mb-4">
            Merit lists are generated per admission round. Create rounds in
            <Link :href="route('admin.rounds.index')" class="text-maroon underline">Admission Rounds</Link> first.
        </p>

        <div class="bg-white border border-border rounded overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-cream text-ink-mute text-xs uppercase tracking-wider">
                    <tr>
                        <th class="text-left px-4 py-2">Round</th>
                        <th class="text-left px-4 py-2">Programme</th>
                        <th class="text-left px-4 py-2">Session</th>
                        <th class="text-left px-4 py-2">Status</th>
                        <th class="text-right px-4 py-2">Candidates</th>
                        <th class="text-right px-4 py-2">Top Score</th>
                        <th class="text-left px-4 py-2">Generated</th>
                        <th class="text-left px-4 py-2">Published</th>
                        <th class="text-right px-4 py-2"></th>
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
                            <span v-if="r.merit_list" class="px-2 py-0.5 text-xs rounded" :class="statusBadge(r.merit_list.status)">
                                {{ r.merit_list.status }}
                            </span>
                            <span v-else class="text-xs text-ink-mute italic">No list yet</span>
                        </td>
                        <td class="px-4 py-2 text-right font-mono">{{ r.merit_list?.total_candidates || '—' }}</td>
                        <td class="px-4 py-2 text-right font-mono">{{ r.merit_list?.max_score ? Number(r.merit_list.max_score).toFixed(2) : '—' }}</td>
                        <td class="px-4 py-2 text-xs">{{ r.merit_list?.generated_at ? formatDateTime(r.merit_list.generated_at) : '—' }}</td>
                        <td class="px-4 py-2 text-xs">{{ r.merit_list?.published_at ? formatDateTime(r.merit_list.published_at) : '—' }}</td>
                        <td class="px-4 py-2 text-right space-x-2 whitespace-nowrap">
                            <template v-if="r.merit_list">
                                <Link :href="route('admin.merit-lists.show', r.merit_list.id)"
                                    class="text-xs text-maroon hover:underline">
                                    View →
                                </Link>
                            </template>
                            <template v-else>
                                <button @click="generate(r)"
                                    class="text-xs px-3 py-1 bg-maroon text-white rounded hover:bg-maroon/90">
                                    Generate Draft
                                </button>
                            </template>
                        </td>
                    </tr>
                    <tr v-if="!rounds.length">
                        <td colspan="9" class="px-4 py-6 text-center text-ink-mute text-sm">No admission rounds yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </PortalLayout>
</template>
