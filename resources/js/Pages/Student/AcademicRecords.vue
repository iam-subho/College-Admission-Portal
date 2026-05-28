<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import InputText from '@/Components/Ui/InputText.vue';
import Button from '@/Components/Ui/Button.vue';
import SectionStatusBanner from '@/Components/Ui/SectionStatusBanner.vue';
import SearchableSelect from '@/Components/Ui/SearchableSelect.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    records: { type: Array, required: true },
    entrance_exams: { type: Array, required: true },
    levels: { type: Array, required: true },
    subject_options: { type: Object, default: () => ({}) }, // { '10th': [...], '12th': [...], 'ug': [...] }
});

// Active level's subject options (memoised on every render).
const subjectsForCurrentLevel = computed(() => props.subject_options?.[form.level] || []);

// Helper: when user selects a subject from dropdown, set both name + code on the row.
const onSubjectPick = (row, opt) => {
    if (!opt) {
        row.code = '';
        row.name = '';
        return;
    }
    row.code = opt.value;
    row.name = opt.label;
};

const MEDIUMS = ['English', 'Hindi', 'Gujarati', 'Marathi', 'Tamil', 'Bengali', 'Other'];
const EXAMS = ['CUET-UG 2026', 'JEE Main 2026', 'NEET-UG 2026', 'State CET', 'GUJCET', 'Other'];

const selectedLevel = ref('12th');

const recordFor = (level) => props.records.find(r => r.level === level) || {};

const blankSubject = () => ({ name: '', code: '', theory: '', practical: '', full_marks: '' });

const form = useForm({
    level: '12th',
    board: '',
    passing_year: '',
    school_name: '',
    school_code: '',
    roll_number: '',
    stream: '',
    medium: '',
    cgpa: '',
    full_marks: '',
    obtained_marks: '',
    aggregate_best5_pct: '',
    subjects: [blankSubject()],
});

const isTenth = computed(() => form.level === '10th');

const totalFm = computed(() =>
    form.subjects.reduce((acc, s) => acc + (parseFloat(s.full_marks) || 0), 0),
);
const totalOm = computed(() =>
    form.subjects.reduce((acc, s) =>
        acc + (parseFloat(s.theory) || 0) + (parseFloat(s.practical) || 0), 0),
);
const subjectPercentage = computed(() => totalFm.value > 0
    ? ((totalOm.value / totalFm.value) * 100).toFixed(2)
    : '0.00',
);

const tenthFm = computed(() => parseFloat(form.full_marks) || 0);
const tenthOm = computed(() => parseFloat(form.obtained_marks) || 0);
const tenthPercentage = computed(() => tenthFm.value > 0
    ? ((tenthOm.value / tenthFm.value) * 100).toFixed(2)
    : '0.00',
);

const rowTotal = (s) => (parseFloat(s.theory) || 0) + (parseFloat(s.practical) || 0);
const rowPct = (s) => {
    const fm = parseFloat(s.full_marks) || 0;
    if (fm <= 0) return '—';
    return ((rowTotal(s) / fm) * 100).toFixed(1) + '%';
};

const addSubject = () => form.subjects.push(blankSubject());
const removeSubject = (i) => {
    if (form.subjects.length === 1) { form.subjects[0] = blankSubject(); return; }
    form.subjects.splice(i, 1);
};

const loadLevel = (level) => {
    selectedLevel.value = level;
    const r = recordFor(level);
    form.level = level;
    form.board = r.board || '';
    form.passing_year = r.passing_year || '';
    form.school_name = r.school_name || '';
    form.school_code = r.school_code || '';
    form.roll_number = r.roll_number || '';
    form.stream = r.stream || '';
    form.medium = r.medium || '';
    form.cgpa = r.cgpa || '';
    form.aggregate_best5_pct = r.aggregate_best5_pct || '';

    if (level === '10th') {
        form.full_marks = r.full_marks || '';
        form.obtained_marks = r.obtained_marks || '';
        form.subjects = [blankSubject()];
    } else {
        form.full_marks = '';
        form.obtained_marks = '';
        form.subjects = Array.isArray(r.subjects) && r.subjects.length
            ? r.subjects.map(s => ({
                name: s.name || '',
                code: s.code || '',
                theory: s.theory ?? '',
                practical: s.practical ?? '',
                full_marks: s.full_marks ?? '',
            }))
            : [blankSubject()];
    }
};

const submit = () => {
    const payload = {
        level: form.level,
        board: form.board,
        passing_year: form.passing_year,
        school_name: form.school_name,
        school_code: form.school_code,
        roll_number: form.roll_number,
        stream: form.stream,
        medium: form.medium,
        cgpa: form.cgpa,
    };
    if (isTenth.value) {
        payload.full_marks = form.full_marks;
        payload.obtained_marks = form.obtained_marks;
    } else {
        payload.aggregate_best5_pct = form.aggregate_best5_pct;
        payload.subjects = form.subjects.filter(s => s.name && s.full_marks);
    }
    form.transform(() => payload).post('/student/academic-records');
};

// Entrance exams
const examForm = useForm({ exam_name: '', roll_number: '', score: '', exam_year: '' });
const addExam = () => examForm.post('/student/academic-records/entrance-exams', {
    preserveScroll: true,
    onSuccess: () => examForm.reset(),
});
const deleteExam = (id) => router.delete(`/student/academic-records/entrance-exams/${id}`, { preserveScroll: true });

loadLevel('12th');
</script>

<template>
    <Head title="Academic Records" />
    <PortalLayout title="Academic Records" :breadcrumb="['Student', 'Academic Records']">
        <p class="text-sm text-ink-mute mb-4">Educational qualifications — verified via DigiLocker where available.</p>

        <SectionStatusBanner section="academic" />

        <div class="flex gap-2 mb-4">
            <button v-for="l in levels" :key="l"
                @click="loadLevel(l)"
                class="px-3 py-1 text-sm rounded border"
                :class="selectedLevel === l ? 'bg-maroon text-white border-maroon' : 'border-border text-ink hover:bg-cream'">
                {{ l === 'ug' ? 'Under-graduate' : 'Class ' + l.toUpperCase() }}
            </button>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <section class="bg-white border border-border rounded">
                <header class="px-4 py-2 border-b border-border bg-cream">
                    <h2 class="font-serif text-base text-maroon">
                        {{ isTenth ? 'Class X (Secondary / SSC)'
                            : (form.level === '12th' ? 'Class XII (Higher Secondary / HSC)' : 'Under-graduate') }}
                    </h2>
                </header>
                <div class="p-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <InputText v-model="form.board" label="Board / Council / University" required :error="form.errors.board" />
                    <InputText v-model="form.passing_year" type="number" label="Year of Passing" required :error="form.errors.passing_year" />
                    <InputText v-model="form.roll_number" label="Roll / Seat Number" :error="form.errors.roll_number" />
                    <InputText v-model="form.school_name" label="School / Institution Name" required :error="form.errors.school_name" />
                    <InputText v-model="form.school_code" label="School / Institution Code" :error="form.errors.school_code" />
                    <InputText v-if="!isTenth" v-model="form.stream" label="Stream / Subject Combination" :error="form.errors.stream" />
                    <div>
                        <label class="block text-xs font-medium text-ink mb-1">Medium of Instruction</label>
                        <select v-model="form.medium" class="w-full px-3 py-2 text-sm border border-border rounded">
                            <option value="">—</option>
                            <option v-for="m in MEDIUMS" :key="m" :value="m">{{ m }}</option>
                        </select>
                    </div>
                    <InputText v-model="form.cgpa" type="number" label="CGPA (if applicable)" :error="form.errors.cgpa" />
                    <InputText v-if="!isTenth" v-model="form.aggregate_best5_pct" type="number" label="Aggregate (Best 5) %" :error="form.errors.aggregate_best5_pct" />
                </div>

                <!-- 10th: simple FM / OM -->
                <div v-if="isTenth" class="px-4 pb-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <InputText v-model="form.full_marks" type="number" label="Maximum Marks" required :error="form.errors.full_marks" />
                    <InputText v-model="form.obtained_marks" type="number" label="Marks Obtained" required :error="form.errors.obtained_marks" />
                    <div>
                        <label class="block text-xs font-medium text-ink mb-1">Percentage (auto)</label>
                        <div class="px-3 py-2 text-sm border border-border rounded bg-cream font-mono">{{ tenthPercentage }}%</div>
                    </div>
                </div>

                <!-- 12th / UG: subject-wise marks (theory + practical) -->
                <div v-else class="px-4 pb-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-maroon">Subject-wise Marks</h3>
                        <button type="button" @click="addSubject"
                            class="text-xs px-3 py-1 bg-saffron text-white rounded hover:bg-saffron/90">+ Add Subject</button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-cream text-ink-mute text-xs uppercase tracking-wider">
                                <tr>
                                    <th class="text-left px-2 py-1.5 w-8">#</th>
                                    <th class="text-left px-2 py-1.5 w-64">Subject</th>
                                    <th class="text-left px-2 py-1.5 w-28">Code</th>
                                    <th class="text-center px-2 py-1.5 w-24">Theory</th>
                                    <th class="text-center px-2 py-1.5 w-24">Practical</th>
                                    <th class="text-center px-2 py-1.5 w-24">Total</th>
                                    <th class="text-center px-2 py-1.5 w-24">Max</th>
                                    <th class="text-center px-2 py-1.5 w-20">%</th>
                                    <th class="px-2 py-1.5 w-12"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(s, i) in form.subjects" :key="i" class="border-t border-border align-top">
                                    <td class="px-2 py-2 text-center text-xs">{{ i + 1 }}</td>
                                    <td class="px-2 py-2 min-w-[220px]">
                                        <SearchableSelect
                                            :model-value="s.code"
                                            :options="subjectsForCurrentLevel"
                                            placeholder="Pick subject…"
                                            empty-text="No subjects match. Contact admissions to add."
                                            @change="(opt) => onSubjectPick(s, opt)" />
                                    </td>
                                    <td class="px-2 py-2 text-xs font-mono text-ink-mute">{{ s.code || '—' }}</td>
                                    <td class="px-2 py-1"><input v-model="s.theory" type="number" class="w-full px-2 py-1 text-sm border border-border rounded font-mono text-center" /></td>
                                    <td class="px-2 py-1"><input v-model="s.practical" type="number" class="w-full px-2 py-1 text-sm border border-border rounded font-mono text-center" /></td>
                                    <td class="px-2 py-1 text-center font-mono text-sm font-semibold">{{ rowTotal(s) }}</td>
                                    <td class="px-2 py-1"><input v-model="s.full_marks" type="number" class="w-full px-2 py-1 text-sm border border-border rounded font-mono text-center" /></td>
                                    <td class="px-2 py-1 text-center text-xs font-mono">{{ rowPct(s) }}</td>
                                    <td class="px-2 py-1 text-right">
                                        <button type="button" @click="removeSubject(i)" class="text-xs text-red-600 hover:underline">Remove</button>
                                    </td>
                                </tr>
                                <tr class="border-t-2 border-maroon bg-saffron-soft font-semibold">
                                    <td colspan="5" class="px-2 py-2 text-right">Aggregate</td>
                                    <td class="px-2 py-2 text-center font-mono">{{ totalOm }}</td>
                                    <td class="px-2 py-2 text-center font-mono">{{ totalFm }}</td>
                                    <td class="px-2 py-2 text-center font-mono text-maroon">{{ subjectPercentage }}%</td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-if="form.errors.subjects" class="text-xs text-red-600 mt-2">{{ form.errors.subjects }}</p>
                </div>
            </section>

            <div class="flex justify-end gap-2">
                <Button type="submit" :loading="form.processing">Save {{ selectedLevel.toUpperCase() }} Record</Button>
            </div>
        </form>

        <!-- Entrance Exams -->
        <section class="mt-8 bg-white border border-border rounded">
            <header class="px-4 py-2 border-b border-border bg-cream">
                <h2 class="font-serif text-base text-maroon">Entrance / Competitive Examinations (Optional)</h2>
            </header>
            <div class="p-4">
                <table v-if="entrance_exams.length" class="w-full text-sm mb-4">
                    <thead class="text-xs uppercase text-ink-mute">
                        <tr>
                            <th class="text-left py-1">Exam</th>
                            <th class="text-left py-1">Roll No.</th>
                            <th class="text-left py-1">Score</th>
                            <th class="text-left py-1">Year</th>
                            <th class="py-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="e in entrance_exams" :key="e.id" class="border-t border-border">
                            <td class="py-2">{{ e.exam_name }}</td>
                            <td class="py-2 font-mono text-xs">{{ e.roll_number || '—' }}</td>
                            <td class="py-2 font-mono">{{ e.score || '—' }}</td>
                            <td class="py-2 font-mono">{{ e.exam_year || '—' }}</td>
                            <td class="py-2 text-right">
                                <button @click="deleteExam(e.id)" class="text-xs text-red-600 hover:underline">Remove</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p v-else class="text-xs text-ink-mute mb-3">No entrance exams added yet.</p>

                <form @submit.prevent="addExam" class="grid grid-cols-1 md:grid-cols-5 gap-2 items-end">
                    <div>
                        <label class="block text-xs font-medium text-ink mb-1">Exam</label>
                        <select v-model="examForm.exam_name" class="w-full px-2 py-1.5 text-sm border border-border rounded">
                            <option value="">—</option>
                            <option v-for="e in EXAMS" :key="e" :value="e">{{ e }}</option>
                        </select>
                    </div>
                    <InputText v-model="examForm.roll_number" label="Roll No." />
                    <InputText v-model="examForm.score" label="Score / Percentile" />
                    <InputText v-model="examForm.exam_year" type="number" label="Year" />
                    <Button type="submit" :loading="examForm.processing" :disabled="!examForm.exam_name">+ Add</Button>
                </form>
            </div>
        </section>

        <div class="mt-6 flex justify-between">
            <Button type="button" variant="ghost" @click="router.visit('/student/profile/address')">← Previous</Button>
            <Button type="button" @click="router.visit('/student/profile/other')">Next: Other Details →</Button>
        </div>
    </PortalLayout>
</template>
