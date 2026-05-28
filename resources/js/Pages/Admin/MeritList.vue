<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import Button from '@/Components/Ui/Button.vue';
import { ref, computed } from 'vue';
import { formatDateTime } from '@/utils/date.js';

const props = defineProps({
    merit_list: { type: Object, required: true },
    entries: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
});

const categoryFilter = ref('');
const showOnlyQualifying = ref(false);

const filteredEntries = computed(() => {
    return props.entries.filter(e => {
        if (categoryFilter.value && e.category_code !== categoryFilter.value) return false;
        if (showOnlyQualifying.value && (!e.is_qualifying || e.is_absent)) return false;
        return true;
    });
});

const isDraft = computed(() => props.merit_list.status === 'draft');
const isPublished = computed(() => props.merit_list.status === 'published');

const generate = () => {
    if (!confirm('Re-generate this merit list? Existing draft entries will be replaced.')) return;
    router.post(route('admin.merit-lists.generate', props.merit_list.admission_round_id));
};
const publish = () => {
    if (!confirm('Publish this merit list?\n\nOnce published:\n• Students see their rank\n• Test scores become immutable\n• A public URL is created\n\nProceed?')) return;
    router.post(route('admin.merit-lists.publish', props.merit_list.id));
};
const destroy = () => {
    if (!confirm('Delete this draft merit list?')) return;
    router.delete(route('admin.merit-lists.destroy', props.merit_list.id));
};

const formula = computed(() => props.merit_list.formula_snapshot || {});
</script>

<template>
    <Head :title="`Merit List · ${merit_list.round?.program?.code} · ${merit_list.round?.name}`" />
    <PortalLayout :title="`Merit List · ${merit_list.round?.program?.code} · ${merit_list.round?.name}`"
        :breadcrumb="['Admin', 'Merit Lists', merit_list.round?.name]">

        <!-- Status / actions bar -->
        <div class="rounded-lg border-2 p-4 mb-4 flex items-start gap-4"
            :class="isPublished ? 'bg-green-50 border-green-300' : 'bg-amber-50 border-amber-300'">
            <div class="text-2xl">{{ isPublished ? '✓' : '⚠' }}</div>
            <div class="flex-1">
                <h3 class="font-serif text-base text-maroon">
                    {{ isPublished ? 'Published' : 'Draft (not visible to students)' }}
                </h3>
                <p class="text-xs text-ink-mute mt-1">
                    <template v-if="isPublished">
                        Published {{ formatDateTime(merit_list.published_at) }}. Test scores locked.
                    </template>
                    <template v-else>
                        Generated {{ formatDateTime(merit_list.generated_at) }}.
                        {{ merit_list.total_candidates }} candidates ranked.
                    </template>
                </p>
            </div>
            <div class="space-x-2 whitespace-nowrap">
                <Button v-if="isDraft" @click="generate">Re-Generate</Button>
                <Button v-if="isDraft" @click="publish">Publish List</Button>
                <button v-if="isDraft" @click="destroy"
                    class="px-3 py-1.5 text-xs border border-red-300 text-red-600 rounded hover:bg-red-50">
                    Delete Draft
                </button>
                <a v-if="isPublished"
                    :href="`/merit/${merit_list.round?.program?.code}/${merit_list.round?.round_number}`"
                    target="_blank"
                    class="text-xs text-maroon hover:underline">View Public Page →</a>
            </div>
        </div>

        <!-- Formula card -->
        <div class="bg-white border border-border rounded p-4 mb-4">
            <h3 class="font-serif text-sm text-maroon mb-2">Formula Snapshot</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-x-6 gap-y-1 text-sm">
                <div><span class="text-xs text-ink-mute">Type:</span> {{ formula.test_enabled ? 'Weighted (test + marks)' : 'Pure board marks' }}</div>
                <div><span class="text-xs text-ink-mute">Test weight:</span> <span class="font-mono">{{ formula.test_weight }}%</span></div>
                <div><span class="text-xs text-ink-mute">Marks weight:</span> <span class="font-mono">{{ formula.marks_weight }}%</span></div>
                <div><span class="text-xs text-ink-mute">Max test marks:</span> <span class="font-mono">{{ formula.max_test_marks ?? '—' }}</span></div>
                <div><span class="text-xs text-ink-mute">Qualifying marks:</span> <span class="font-mono">{{ formula.qualifying_marks ?? '—' }}</span></div>
                <div class="md:col-span-3">
                    <span class="text-xs text-ink-mute">Tie-breakers:</span>
                    <span class="font-mono">{{ (formula.tie_breakers || []).join(' → ') }}</span>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="flex gap-3 mb-3 flex-wrap items-end">
            <div>
                <label class="block text-xs font-medium text-ink mb-1">Filter category</label>
                <select v-model="categoryFilter" class="px-3 py-1.5 text-sm border border-border rounded bg-white">
                    <option value="">All</option>
                    <option v-for="c in categories" :key="c.id" :value="c.code">{{ c.code }} · {{ c.name }}</option>
                </select>
            </div>
            <label class="flex items-center gap-2 text-xs pb-2">
                <input type="checkbox" v-model="showOnlyQualifying" />
                Show only qualifying / present
            </label>
        </div>

        <!-- Entries table -->
        <div class="bg-white border border-border rounded overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-cream text-ink-mute text-xs uppercase tracking-wider">
                    <tr>
                        <th class="text-right px-4 py-2 w-16">Rank</th>
                        <th class="text-right px-4 py-2 w-20">Cat Rank</th>
                        <th class="text-left px-4 py-2">Application</th>
                        <th class="text-left px-4 py-2">Name</th>
                        <th class="text-left px-4 py-2">Cat</th>
                        <th class="text-right px-4 py-2">Total Score</th>
                        <th class="text-right px-4 py-2">Test</th>
                        <th class="text-right px-4 py-2">Board %</th>
                        <th class="text-left px-4 py-2">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="e in filteredEntries" :key="e.id" class="border-t border-border"
                        :class="e.is_absent ? 'opacity-50' : (!e.is_qualifying ? 'bg-red-50' : '')">
                        <td class="px-4 py-1.5 text-right font-mono font-semibold">{{ e.overall_rank }}</td>
                        <td class="px-4 py-1.5 text-right font-mono">{{ e.category_rank ?? '—' }}</td>
                        <td class="px-4 py-1.5 font-mono text-xs">{{ e.application_number }}</td>
                        <td class="px-4 py-1.5">{{ e.applicant_name }}</td>
                        <td class="px-4 py-1.5 text-xs font-mono">{{ e.category_code || '—' }}</td>
                        <td class="px-4 py-1.5 text-right font-mono font-semibold">{{ e.total_score.toFixed(2) }}</td>
                        <td class="px-4 py-1.5 text-right font-mono">{{ e.test_score !== null ? e.test_score.toFixed(2) : '—' }}</td>
                        <td class="px-4 py-1.5 text-right font-mono">{{ e.marks_pct.toFixed(2) }}</td>
                        <td class="px-4 py-1.5 text-xs">
                            <span v-if="e.is_absent" class="text-red-600">Absent</span>
                            <span v-else-if="!e.is_qualifying" class="text-amber-600">Below qualifying</span>
                            <span v-else class="text-green-700">Qualifying</span>
                        </td>
                    </tr>
                    <tr v-if="!filteredEntries.length">
                        <td colspan="9" class="px-4 py-6 text-center text-ink-mute text-sm">No entries match the filter.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            <Link :href="route('admin.merit-lists.index')" class="text-xs text-maroon hover:underline">← Back to merit lists</Link>
        </div>
    </PortalLayout>
</template>
