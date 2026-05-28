<script setup>
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import InputText from '@/Components/Ui/InputText.vue';
import Button from '@/Components/Ui/Button.vue';
import { ref, reactive, computed } from 'vue';
import { formatDate, formatDateTime } from '@/utils/date.js';

const props = defineProps({
    config: { type: Object, required: true },
    schedule: { type: Object, default: null },
    candidates: { type: Array, default: () => [] },
});

const page = usePage();
const csvPreview = computed(() => page.props?.flash?.csv_preview || null);

const TABS = [
    { key: 'settings', label: 'Settings' },
    { key: 'schedule', label: 'Schedule' },
    { key: 'candidates', label: 'Candidates' },
    { key: 'marks', label: 'Marks Entry' },
    { key: 'csv', label: 'CSV Upload' },
];
const tab = ref('settings');

// --- Settings form
const settingsForm = useForm({
    is_test_enabled: !!props.config.is_test_enabled,
    max_marks: props.config.max_marks ?? '',
    qualifying_marks: props.config.qualifying_marks ?? '',
    test_weight: props.config.test_weight ?? 0,
    marks_weight: props.config.marks_weight ?? 100,
    negative_marking_rule: props.config.negative_marking_rule ?? '',
    syllabus_url: props.config.syllabus_url ?? '',
    instructions: props.config.instructions ?? '',
});
const saveSettings = () => settingsForm.patch(route('admin.admission-tests.update', props.config.id), {
    preserveScroll: true,
});

// --- Schedule form
const scheduleForm = useForm({
    test_date: props.schedule?.test_date ?? '',
    reporting_time: props.schedule?.reporting_time ?? '',
    start_time: props.schedule?.start_time ?? '',
    end_time: props.schedule?.end_time ?? '',
    venue: props.schedule?.venue ?? '',
    venue_address: props.schedule?.venue_address ?? '',
});
const saveSchedule = () => scheduleForm.post(route('admin.admission-tests.schedule.save', props.config.id), {
    preserveScroll: true,
});

const publishAdmitCards = () => {
    if (!confirm('Publish admit cards for all paid candidates? Once published, students can download admit cards from their portal.')) return;
    router.post(route('admin.admission-tests.publish-admit-cards', props.config.id), {}, {
        preserveScroll: true,
    });
};

// --- Manual marks entry
const marksRows = reactive(props.candidates.map(c => ({
    candidate_id: c.id,
    application_number: c.application_number,
    applicant_name: c.applicant_name,
    roll_number: c.roll_number,
    raw_marks: c.raw_marks ?? '',
    attendance: c.attendance ?? 'present',
    is_locked: c.is_locked,
})));

const saveMarks = () => {
    router.post(route('admin.admission-tests.marks.save', props.config.id), {
        rows: marksRows.map(r => ({
            candidate_id: r.candidate_id,
            raw_marks: r.attendance === 'absent' ? null : (r.raw_marks === '' ? null : Number(r.raw_marks)),
            attendance: r.attendance,
        })),
    }, { preserveScroll: true });
};

// --- CSV upload
const csvForm = useForm({ file: null });
const onFileChange = (e) => { csvForm.file = e.target.files[0] || null; };
const uploadCsv = () => csvForm.post(route('admin.admission-tests.marks.preview', props.config.id), {
    preserveScroll: true,
    forceFormData: true,
});

const commitCsv = () => router.post(route('admin.admission-tests.marks.commit', props.config.id), {}, {
    preserveScroll: true,
});

// --- Page summary
const enabledBadge = computed(() => props.config.is_test_enabled
    ? { label: 'Test Enabled', cls: 'bg-green-100 text-green-800' }
    : { label: 'Test Disabled', cls: 'bg-gray-100 text-gray-700' });

// --- Next-action banner (state-aware). One row at the top of the page so the
// admin always knows what to do next without having to dig through tabs.
const candidateCount = computed(() => props.candidates.length);
const paidWithoutRoll = computed(() => props.candidates.filter(c => !c.admit_card_published).length);
const cardsAlreadyPublished = computed(() => !!props.schedule?.admit_cards_published);

const nextAction = computed(() => {
    if (!props.config.is_test_enabled) {
        return {
            tone: 'amber',
            title: 'Test is currently disabled',
            body: 'Turn the test ON in the Settings tab to begin the admit-card / marks pipeline.',
            cta: { label: 'Go to Settings', action: () => (tab.value = 'settings') },
        };
    }
    if (!props.schedule) {
        return {
            tone: 'amber',
            title: 'No test schedule defined',
            body: 'Set the test date, time and venue. Admit cards cannot be generated without a schedule.',
            cta: { label: 'Set Schedule', action: () => (tab.value = 'schedule') },
        };
    }
    if (candidateCount.value === 0) {
        return {
            tone: 'gray',
            title: 'Waiting for paid applications',
            body: 'No paid applications match this programme + session yet. Candidates are auto-registered when a student pays the application fee.',
            cta: null,
        };
    }
    if (!cardsAlreadyPublished.value) {
        return {
            tone: 'green',
            title: `Ready to generate admit cards for ${candidateCount.value} candidate${candidateCount.value > 1 ? 's' : ''}`,
            body: 'Clicking the button will allocate roll numbers and make the admit card downloadable from the student portal. Reversible — you can re-publish at any time.',
            cta: { label: `Generate & Publish Admit Cards (${candidateCount.value})`, action: publishAdmitCards, primary: true },
        };
    }
    return {
        tone: 'green',
        title: 'Admit cards live',
        body: `Published for ${candidateCount.value} candidate${candidateCount.value > 1 ? 's' : ''}. Students can now download from their portal.`,
        cta: { label: 'Re-publish (assign missing rolls)', action: publishAdmitCards },
    };
});

const bannerCls = computed(() => ({
    amber: 'bg-amber-50 border-amber-300 text-amber-900',
    green: 'bg-green-50 border-green-300 text-green-900',
    gray: 'bg-gray-50 border-gray-300 text-ink',
}[nextAction.value.tone] || 'bg-gray-50 border-gray-300'));

const ctaBtnCls = (primary) => primary
    ? 'px-4 py-2 bg-maroon text-white rounded text-sm font-semibold hover:bg-maroon/90 shadow-sm'
    : 'px-3 py-1.5 border border-current rounded text-xs font-semibold hover:bg-white/40';
</script>

<template>
    <Head :title="`Admission Test — ${config.program?.code}`" />
    <PortalLayout :title="`Admission Test · ${config.program?.code} ${config.program?.name}`"
        :breadcrumb="['Admin', 'Admission Tests', config.program?.code]">

        <div class="flex items-center justify-between mb-4">
            <div class="text-sm text-ink-mute">
                <span class="font-mono">{{ config.program?.code }}</span> · {{ config.program?.name }}
                · Session <strong>{{ config.session?.code }}</strong>
            </div>
            <span class="px-2 py-0.5 text-xs rounded" :class="enabledBadge.cls">{{ enabledBadge.label }}</span>
        </div>

        <!-- Next-action banner -->
        <div class="rounded-lg border-2 p-4 mb-4 flex items-start gap-4" :class="bannerCls">
            <div class="text-2xl">
                {{ nextAction.tone === 'green' ? '✓' : (nextAction.tone === 'amber' ? '⚠' : '·') }}
            </div>
            <div class="flex-1">
                <h3 class="font-serif text-base">{{ nextAction.title }}</h3>
                <p class="text-sm mt-1 opacity-90">{{ nextAction.body }}</p>
            </div>
            <button v-if="nextAction.cta" @click="nextAction.cta.action"
                :class="ctaBtnCls(nextAction.cta.primary)">
                {{ nextAction.cta.label }}
            </button>
        </div>

        <!-- Tab strip -->
        <div class="flex flex-wrap gap-1 border-b border-border mb-4">
            <button v-for="t in TABS" :key="t.key" @click="tab = t.key"
                :class="tab === t.key
                    ? 'px-3 py-1.5 text-xs rounded-t bg-maroon text-white border border-maroon border-b-0'
                    : 'px-3 py-1.5 text-xs rounded-t bg-white text-ink border border-border hover:bg-cream'">
                {{ t.label }}
            </button>
        </div>

        <!-- SETTINGS -->
        <section v-if="tab === 'settings'" class="bg-white border border-border rounded p-4">
            <form @submit.prevent="saveSettings" class="space-y-4">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" v-model="settingsForm.is_test_enabled" class="accent-saffron" />
                    <span class="font-medium">Require admission test for this programme + session</span>
                </label>
                <p class="text-xs text-ink-mute">
                    If OFF, students applying to this programme skip the test step entirely — merit is computed from board / aggregate marks only.
                </p>

                <fieldset :disabled="!settingsForm.is_test_enabled" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <InputText v-model="settingsForm.max_marks" type="number" step="0.01" label="Max Marks"
                        :error="settingsForm.errors.max_marks" placeholder="e.g. 100" />
                    <InputText v-model="settingsForm.qualifying_marks" type="number" step="0.01" label="Qualifying Marks"
                        :error="settingsForm.errors.qualifying_marks" placeholder="e.g. 33" />
                    <InputText v-model="settingsForm.negative_marking_rule" label="Negative Marking (per wrong)"
                        :error="settingsForm.errors.negative_marking_rule" placeholder="e.g. 0.25 (blank = none)" />
                    <InputText v-model="settingsForm.test_weight" type="number" step="0.01" label="Test Weight (%)"
                        :error="settingsForm.errors.test_weight" />
                    <InputText v-model="settingsForm.marks_weight" type="number" step="0.01" label="Board Marks Weight (%)"
                        :error="settingsForm.errors.marks_weight" />
                    <InputText v-model="settingsForm.syllabus_url" label="Syllabus URL"
                        :error="settingsForm.errors.syllabus_url" placeholder="https://…" />
                    <div class="md:col-span-3">
                        <label class="block text-xs font-medium text-ink mb-1">Instructions for candidates</label>
                        <textarea v-model="settingsForm.instructions" rows="4"
                            class="w-full px-3 py-2 text-sm border border-border rounded"
                            placeholder="Reporting time, ID proof, allowed/banned items, etc."></textarea>
                    </div>
                </fieldset>

                <p v-if="(Number(settingsForm.test_weight) + Number(settingsForm.marks_weight)) > 100"
                    class="text-xs text-red-600">
                    test_weight + marks_weight should not exceed 100 (currently {{ Number(settingsForm.test_weight) + Number(settingsForm.marks_weight) }}).
                </p>

                <Button type="submit" :loading="settingsForm.processing">Save Settings</Button>
            </form>
        </section>

        <!-- SCHEDULE -->
        <section v-if="tab === 'schedule'" class="bg-white border border-border rounded p-4">
            <form @submit.prevent="saveSchedule" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <InputText v-model="scheduleForm.test_date" type="date" label="Test Date" required
                    :error="scheduleForm.errors.test_date" />
                <InputText v-model="scheduleForm.reporting_time" type="time" label="Reporting Time"
                    :error="scheduleForm.errors.reporting_time" />
                <div></div>
                <InputText v-model="scheduleForm.start_time" type="time" label="Test Start Time"
                    :error="scheduleForm.errors.start_time" />
                <InputText v-model="scheduleForm.end_time" type="time" label="Test End Time"
                    :error="scheduleForm.errors.end_time" />
                <div></div>
                <div class="md:col-span-3">
                    <InputText v-model="scheduleForm.venue" label="Venue" required :error="scheduleForm.errors.venue"
                        placeholder="e.g. SVNC Main Block, Hall A-101" />
                </div>
                <div class="md:col-span-3">
                    <label class="block text-xs font-medium text-ink mb-1">Venue Address</label>
                    <textarea v-model="scheduleForm.venue_address" rows="2"
                        class="w-full px-3 py-2 text-sm border border-border rounded"
                        placeholder="Full address for the admit card"></textarea>
                </div>
                <div class="md:col-span-3 flex items-center gap-3">
                    <Button type="submit" :loading="scheduleForm.processing">Save Schedule</Button>
                    <div v-if="schedule" class="text-xs text-ink-mute">
                        Last saved · {{ formatDate(schedule.updated_at) }}
                    </div>
                </div>
            </form>

            <div v-if="schedule" class="mt-6 p-4 bg-cream border border-border rounded">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="font-serif text-base text-maroon">Admit Cards</h4>
                        <p class="text-xs text-ink-mute mt-1">
                            <template v-if="schedule.admit_cards_published">
                                Published on {{ formatDateTime(schedule.admit_cards_published_at) }} by {{ schedule.publisher?.name || '—' }}
                            </template>
                            <template v-else>
                                Roll numbers will be generated and admit cards made downloadable for all paid candidates.
                            </template>
                        </p>
                    </div>
                    <Button @click="publishAdmitCards" :disabled="!schedule">
                        {{ schedule.admit_cards_published ? 'Re-Publish' : 'Publish Admit Cards' }}
                    </Button>
                </div>
            </div>
        </section>

        <!-- CANDIDATES -->
        <section v-if="tab === 'candidates'" class="bg-white border border-border rounded overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-cream text-ink-mute text-xs uppercase tracking-wider">
                    <tr>
                        <th class="text-left px-4 py-2">Roll #</th>
                        <th class="text-left px-4 py-2">Application</th>
                        <th class="text-left px-4 py-2">Applicant</th>
                        <th class="text-left px-4 py-2">Admit Card</th>
                        <th class="text-right px-4 py-2">Marks</th>
                        <th class="text-left px-4 py-2">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="c in candidates" :key="c.id" class="border-t border-border">
                        <td class="px-4 py-2 font-mono text-xs">{{ c.roll_number || '—' }}</td>
                        <td class="px-4 py-2 font-mono text-xs">{{ c.application_number }}</td>
                        <td class="px-4 py-2">
                            {{ c.applicant_name }}
                            <div class="text-xs text-ink-mute">{{ c.applicant_email }}</div>
                        </td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-0.5 text-xs rounded"
                                :class="c.admit_card_published ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800'">
                                {{ c.admit_card_published ? 'Published' : 'Pending' }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-right font-mono">{{ c.raw_marks ?? '—' }}</td>
                        <td class="px-4 py-2 text-xs">
                            <span v-if="c.attendance === 'absent'" class="text-red-600">Absent</span>
                            <span v-else-if="c.raw_marks != null">{{ c.entered_via }}{{ c.is_locked ? ' · locked' : '' }}</span>
                            <span v-else class="text-ink-mute italic">No score</span>
                        </td>
                    </tr>
                    <tr v-if="!candidates.length">
                        <td colspan="6" class="px-6 py-8 text-center text-ink-mute text-sm">
                            No candidates yet. Candidates are auto-registered when a student pays the application fee for this programme + session.
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>

        <!-- MARKS ENTRY -->
        <section v-if="tab === 'marks'" class="bg-white border border-border rounded overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-cream text-ink-mute text-xs uppercase tracking-wider">
                    <tr>
                        <th class="text-left px-4 py-2">Roll #</th>
                        <th class="text-left px-4 py-2">Application / Name</th>
                        <th class="text-left px-4 py-2 w-32">Attendance</th>
                        <th class="text-right px-4 py-2 w-32">Marks</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="r in marksRows" :key="r.candidate_id" class="border-t border-border">
                        <td class="px-4 py-2 font-mono text-xs">{{ r.roll_number || '—' }}</td>
                        <td class="px-4 py-2">
                            <div class="font-mono text-xs">{{ r.application_number }}</div>
                            <div class="text-xs text-ink-mute">{{ r.applicant_name }}</div>
                        </td>
                        <td class="px-4 py-2">
                            <select v-model="r.attendance" :disabled="r.is_locked"
                                class="w-full px-2 py-1 text-xs border border-border rounded">
                                <option value="present">Present</option>
                                <option value="absent">Absent</option>
                            </select>
                        </td>
                        <td class="px-4 py-2 text-right">
                            <input v-model="r.raw_marks" type="number" step="0.01" :disabled="r.is_locked || r.attendance === 'absent'"
                                class="w-24 px-2 py-1 text-xs text-right font-mono border border-border rounded" />
                        </td>
                    </tr>
                    <tr v-if="!marksRows.length">
                        <td colspan="4" class="px-6 py-8 text-center text-ink-mute text-sm">
                            No candidates to mark. Register candidates first (Candidates tab).
                        </td>
                    </tr>
                </tbody>
            </table>
            <div v-if="marksRows.length" class="p-4 border-t border-border bg-cream flex justify-end">
                <Button @click="saveMarks">Save All Marks</Button>
            </div>
        </section>

        <!-- CSV UPLOAD -->
        <section v-if="tab === 'csv'" class="bg-white border border-border rounded p-4">
            <p class="text-sm mb-4">
                Upload a CSV with two columns: <code class="font-mono bg-cream px-1 rounded">application_number,raw_marks</code>.
                Blank <code class="font-mono bg-cream px-1 rounded">raw_marks</code> marks the candidate as absent.
            </p>
            <form @submit.prevent="uploadCsv" class="flex items-end gap-3 mb-6">
                <div class="flex-1">
                    <label class="block text-xs font-medium text-ink mb-1">CSV File</label>
                    <input type="file" accept=".csv,text/csv" @change="onFileChange"
                        class="w-full px-3 py-2 text-sm border border-border rounded bg-white" />
                </div>
                <Button type="submit" :loading="csvForm.processing" :disabled="!csvForm.file">Preview</Button>
            </form>

            <div v-if="csvPreview" class="border border-border rounded">
                <div class="p-3 bg-cream border-b border-border flex justify-between items-center">
                    <div class="text-sm">
                        <strong>{{ csvPreview.summary.total }}</strong> rows ·
                        <span class="text-green-700">{{ csvPreview.summary.will_create + csvPreview.summary.will_update }} valid</span> ·
                        <span class="text-red-600">{{ csvPreview.summary.errors }} errors</span>
                        <span v-if="csvPreview.summary.locked > 0" class="text-amber-600">· {{ csvPreview.summary.locked }} locked</span>
                    </div>
                    <Button @click="commitCsv" :disabled="csvPreview.summary.will_create + csvPreview.summary.will_update === 0">
                        Commit Valid Rows
                    </Button>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-cream text-ink-mute text-xs uppercase tracking-wider">
                        <tr>
                            <th class="text-left px-4 py-2 w-12">Line</th>
                            <th class="text-left px-4 py-2">Application #</th>
                            <th class="text-right px-4 py-2 w-24">New Marks</th>
                            <th class="text-right px-4 py-2 w-24">Existing</th>
                            <th class="text-left px-4 py-2">Issues</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="r in csvPreview.rows" :key="r.line" class="border-t border-border"
                            :class="!r.valid ? 'bg-red-50' : (r.existing_marks != null ? 'bg-amber-50' : '')">
                            <td class="px-4 py-1.5 font-mono text-xs">{{ r.line }}</td>
                            <td class="px-4 py-1.5 font-mono text-xs">{{ r.application_number }}</td>
                            <td class="px-4 py-1.5 text-right font-mono">{{ r.raw_marks || '—' }}</td>
                            <td class="px-4 py-1.5 text-right font-mono text-xs text-ink-mute">{{ r.existing_marks ?? '—' }}</td>
                            <td class="px-4 py-1.5 text-xs">
                                <span v-if="r.errors.length" class="text-red-600">{{ r.errors.join(' · ') }}</span>
                                <span v-else class="text-green-700">OK</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="mt-6">
            <Link :href="route('admin.admission-tests.index')" class="text-xs text-maroon hover:underline">
                ← Back to all configurations
            </Link>
        </div>
    </PortalLayout>
</template>
