<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import Button from '@/Components/Ui/Button.vue';
import { ref } from 'vue';
import { formatDate } from '@/utils/date.js';

defineProps({
    configs: { type: Array, required: true },
    programs: { type: Array, required: true },
    sessions: { type: Array, required: true },
});

const showForm = ref(false);
const form = useForm({ program_id: '', academic_session_id: '' });

const create = () => form.post(route('admin.admission-tests.store'), {
    onSuccess: () => { showForm.value = false; form.reset(); },
});

const canPublishFromIndex = (c) => c.is_test_enabled && c.has_schedule && !c.admit_cards_published && c.candidate_count > 0;

const publishAdmitCardsInline = (c) => {
    if (!confirm(`Generate roll numbers and publish admit cards for ${c.candidate_count} candidate(s) of ${c.program?.code}?`)) return;
    router.post(route('admin.admission-tests.publish-admit-cards', c.id), {}, { preserveScroll: true });
};
</script>

<template>
    <Head title="Admission Tests" />
    <PortalLayout title="Admission Tests" :breadcrumb="['Admin', 'Admission Tests']">

        <div class="flex justify-end mb-4">
            <Button @click="showForm = !showForm">
                {{ showForm ? 'Cancel' : '+ New Test Configuration' }}
            </Button>
        </div>

        <div v-if="showForm" class="bg-white border border-border rounded p-4 mb-6">
            <h3 class="font-serif text-lg text-maroon mb-3">Configure Test for Programme</h3>
            <p class="text-xs text-ink-mute mb-3">
                Pick a programme and an academic session. A test configuration row will be created (disabled by default) —
                you can then toggle test ON, set marks/weights, and define the schedule.
            </p>
            <form @submit.prevent="create" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-ink mb-1">Programme <span class="text-maroon">*</span></label>
                    <select v-model="form.program_id" class="w-full px-3 py-2 text-sm border border-border rounded bg-white">
                        <option value="">— Select —</option>
                        <option v-for="p in programs" :key="p.id" :value="p.id">{{ p.code }} · {{ p.name }} ({{ p.type }})</option>
                    </select>
                    <p v-if="form.errors.program_id" class="text-xs text-red-600 mt-1">{{ form.errors.program_id }}</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-ink mb-1">Session <span class="text-maroon">*</span></label>
                    <select v-model="form.academic_session_id" class="w-full px-3 py-2 text-sm border border-border rounded bg-white">
                        <option value="">— Select —</option>
                        <option v-for="s in sessions" :key="s.id" :value="s.id">
                            {{ s.code }} {{ s.is_active ? '(active)' : '' }}
                        </option>
                    </select>
                    <p v-if="form.errors.academic_session_id" class="text-xs text-red-600 mt-1">{{ form.errors.academic_session_id }}</p>
                </div>
                <div class="md:col-span-3">
                    <Button type="submit" :loading="form.processing">Create &amp; Configure</Button>
                </div>
            </form>
        </div>

        <div class="bg-white border border-border rounded overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-cream text-ink-mute text-xs uppercase tracking-wider">
                    <tr>
                        <th class="text-left px-4 py-2">Programme</th>
                        <th class="text-left px-4 py-2">Session</th>
                        <th class="text-left px-4 py-2">Test</th>
                        <th class="text-left px-4 py-2">Schedule</th>
                        <th class="text-left px-4 py-2">Admit Cards</th>
                        <th class="text-right px-4 py-2">Candidates</th>
                        <th class="text-right px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="c in configs" :key="c.id" class="border-t border-border">
                        <td class="px-4 py-2">
                            <span class="font-mono text-xs">{{ c.program?.code }}</span> · {{ c.program?.name }}
                        </td>
                        <td class="px-4 py-2 text-xs font-mono">{{ c.session?.code }}</td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-0.5 text-xs rounded"
                                :class="c.is_test_enabled ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700'">
                                {{ c.is_test_enabled ? 'Enabled' : 'Disabled' }}
                            </span>
                            <span v-if="c.is_test_enabled" class="text-xs text-ink-mute ml-2">
                                weight {{ c.test_weight }}% · max {{ c.max_marks ?? '—' }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-xs">
                            <span v-if="c.has_schedule">{{ formatDate(c.schedule_date) }}</span>
                            <span v-else class="text-red-600">Not set</span>
                        </td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-0.5 text-xs rounded"
                                :class="c.admit_cards_published ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800'">
                                {{ c.admit_cards_published ? 'Published' : 'Pending' }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-right font-mono">{{ c.candidate_count }}</td>
                        <td class="px-4 py-2 text-right space-x-2 whitespace-nowrap">
                            <button
                                v-if="canPublishFromIndex(c)"
                                @click="publishAdmitCardsInline(c)"
                                class="text-xs px-3 py-1 bg-saffron text-white rounded hover:bg-saffron/90 font-semibold"
                                title="Generate roll numbers + publish admit cards for all paid candidates"
                            >
                                Generate Admit Cards ({{ c.candidate_count }})
                            </button>
                            <Link :href="route('admin.admission-tests.show', c.id)"
                                class="text-xs text-maroon hover:underline">Manage →</Link>
                        </td>
                    </tr>
                    <tr v-if="!configs.length">
                        <td colspan="7" class="px-4 py-6 text-center text-ink-mute text-sm">
                            No test configurations yet. Create one above.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </PortalLayout>
</template>
