<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import Button from '@/Components/Ui/Button.vue';
import { ref, computed } from 'vue';
import { formatDate, formatDateTime } from '@/utils/date.js';

const props = defineProps({
    application: { type: Object, required: true },
    documents: { type: Array, default: () => [] },
    audit: { type: Array, default: () => [] },
});

const TABS = [
    { key: 'profile', label: 'Profile' },
    { key: 'family', label: 'Family' },
    { key: 'address', label: 'Address' },
    { key: 'education', label: 'Education' },
    { key: 'subjects', label: 'Subjects (NEP)' },
    { key: 'documents', label: 'Documents' },
    { key: 'payments', label: 'Payments' },
    { key: 'eligibility', label: 'Eligibility' },
    { key: 'audit', label: 'Audit' },
];

const tab = ref('profile');

const inr = (n) => n != null ? '₹ ' + Number(n).toLocaleString('en-IN', { minimumFractionDigits: 2 }) : '—';
const orDash = (v) => (v ?? '') !== '' ? v : '—';
const yes = (b) => b ? 'Yes' : 'No';
const fmtDate = (iso) => formatDateTime(iso);
const fmtDay = (iso) => formatDate(iso);

const s = computed(() => props.application.student || {});

// ---- Eligibility override form ----
const showOverride = ref(false);
const overrideForm = useForm({ verdict: 'override_pass', remark: '' });
const submitOverride = () => overrideForm.post(`/admin/applications/${props.application.id}/override-eligibility`, {
    onSuccess: () => { showOverride.value = false; overrideForm.reset(); },
});

// ---- Document preview modal ----
const previewDoc = ref(null);
const previewUrl = computed(() => previewDoc.value ? `/documents/${previewDoc.value.id}/preview` : null);
const isImage = computed(() => /^image\//.test(previewDoc.value?.mime || ''));
const isPdf = computed(() => previewDoc.value?.mime === 'application/pdf');
const openPreview = (d) => { previewDoc.value = d; };
const closePreview = () => { previewDoc.value = null; };

const awaitingPayment = computed(() =>
    props.application.status === 'submitted' && props.application.payment_status === 'pending'
);

const statusBadge = computed(() => {
    if (awaitingPayment.value) return 'bg-amber-100 text-amber-800 border border-amber-300';
    return {
        draft: 'bg-gray-100 text-gray-700',
        submitted: 'bg-blue-100 text-blue-800',
        verified: 'bg-green-100 text-green-800',
        rejected: 'bg-red-100 text-red-800',
        withdrawn: 'bg-amber-100 text-amber-800',
    }[props.application.status] || 'bg-gray-100';
});

const statusLabel = computed(() => awaitingPayment.value ? 'Awaiting Payment' : props.application.status);

const appPaymentBadge = computed(() => ({
    pending: { label: 'Payment Pending', cls: 'bg-amber-100 text-amber-800' },
    paid: { label: 'Paid', cls: 'bg-green-100 text-green-800' },
    covered: { label: 'Covered', cls: 'bg-green-50 text-green-700' },
    not_required: { label: 'No Fee', cls: 'bg-gray-100 text-gray-700' },
}[props.application.payment_status] || { label: props.application.payment_status, cls: 'bg-gray-100' }));

const verdictBadge = (v) => ({
    pending: 'bg-gray-100 text-gray-700',
    pass: 'bg-green-100 text-green-800',
    override_pass: 'bg-green-100 text-green-800',
    fail: 'bg-red-100 text-red-800',
    override_fail: 'bg-red-100 text-red-800',
}[v] || 'bg-gray-100 text-gray-700');

const docStatusBadge = (st) => ({
    pending: 'bg-gray-100 text-gray-700',
    approved: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
    resubmit: 'bg-amber-100 text-amber-800',
}[st] || 'bg-gray-100');

const paymentStatusBadge = (st) => ({
    initiated: 'bg-gray-100 text-gray-700',
    processing: 'bg-blue-100 text-blue-800',
    paid: 'bg-green-100 text-green-800',
    failed: 'bg-red-100 text-red-800',
    refunded: 'bg-amber-100 text-amber-800',
    expired: 'bg-gray-100 text-gray-700',
}[st] || 'bg-gray-100');

const subjectsByCategory = computed(() => {
    const out = {};
    for (const sel of (props.application.course_selections || [])) {
        out[sel.category] ??= [];
        out[sel.category].push(sel.pool);
    }
    return out;
});

const fullAddress = computed(() => [s.value.house_no, s.value.locality, s.value.taluka, s.value.district, s.value.pincode, s.value.state, s.value.country].filter(Boolean).join(', '));

// ---- NEP categories (must mirror PHP enum on ProgrammeCoursePool) ----
const NEP_CATEGORIES = {
    major: 'Major (Core Discipline)',
    minor: 'Minor',
    aec: 'Ability Enhancement (AEC)',
    sec: 'Skill Enhancement (SEC)',
    vac: 'Value-Added Course (VAC)',
    mdc: 'Multi-Disciplinary (MDC)',
    internship: 'Internship / Field Project',
    research: 'Research Project',
};

const totalCredits = computed(() => {
    let sum = 0;
    for (const cat of Object.keys(NEP_CATEGORIES)) {
        for (const p of (subjectsByCategory.value[cat] || [])) {
            sum += Number(p?.credits || 0);
        }
    }
    return sum;
});

// ---- Academic records subject parsing helpers ----
const recordSubjects = (r) => Array.isArray(r?.subjects) ? r.subjects : [];
const subjectRowTotal = (sub) => Number(sub?.theory || sub?.obtained_marks || 0) + Number(sub?.practical || 0);
const subjectRowMax = (sub) => Number(sub?.full_marks || 0);
const subjectRowPct = (sub) => {
    const max = subjectRowMax(sub);
    if (max <= 0) return '—';
    return ((subjectRowTotal(sub) / max) * 100).toFixed(1) + '%';
};
const levelLabel = (lvl) => ({ '10th': 'Class X (Secondary)', '12th': 'Class XII (Higher Secondary)', 'ug': 'Under-graduate' }[lvl] || lvl);

// ---- Document approve / reject ----
const rejectingId = ref(null);
const rejectForm = useForm({ reason: '' });

const approve = (doc) => router.post(`/admin/documents/${doc.id}/approve`, {}, { preserveScroll: true });
const startReject = (doc) => { rejectingId.value = doc.id; rejectForm.reset(); };
const cancelReject = () => { rejectingId.value = null; rejectForm.reset(); };
const submitReject = (doc) => rejectForm.post(`/admin/documents/${doc.id}/reject`, {
    preserveScroll: true,
    onSuccess: cancelReject,
});
</script>

<template>
    <Head :title="application.application_number || 'Application'" />
    <PortalLayout
        :title="`Application Review — ${application.application_number || 'Draft'}`"
        :breadcrumb="['Admin', 'Applications', application.application_number || '—']">

        <!-- Sticky summary card -->
        <div class="bg-white border border-border rounded mb-4">
            <div class="px-4 py-3 grid grid-cols-1 md:grid-cols-5 gap-4 text-sm">
                <div>
                    <div class="text-[10px] uppercase tracking-wider text-ink-mute">Applicant</div>
                    <div class="font-medium">{{ s.aadhaar_full_name || s.user?.name || '—' }}</div>
                    <div class="text-xs text-ink-mute">{{ s.user?.email }}</div>
                </div>
                <div>
                    <div class="text-[10px] uppercase tracking-wider text-ink-mute">Application No.</div>
                    <div class="font-mono font-semibold text-maroon">{{ application.application_number || '—' }}</div>
                </div>
                <div>
                    <div class="text-[10px] uppercase tracking-wider text-ink-mute">Programme</div>
                    <div class="font-medium">{{ application.program?.code }}</div>
                    <div class="text-xs text-ink-mute">{{ application.program?.name }}</div>
                </div>
                <div>
                    <div class="text-[10px] uppercase tracking-wider text-ink-mute">Status</div>
                    <span class="inline-block px-2 py-0.5 text-xs rounded uppercase font-mono" :class="statusBadge">{{ statusLabel }}</span>
                </div>
                <div>
                    <div class="text-[10px] uppercase tracking-wider text-ink-mute">Payment</div>
                    <span class="inline-block px-2 py-0.5 text-xs rounded font-mono" :class="appPaymentBadge.cls">
                        {{ appPaymentBadge.label }}
                    </span>
                </div>
                <div>
                    <div class="text-[10px] uppercase tracking-wider text-ink-mute">Eligibility</div>
                    <span class="inline-block px-2 py-0.5 text-xs rounded uppercase font-mono" :class="verdictBadge(application.eligibility_verdict)">
                        {{ application.eligibility_verdict || 'pending' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="bg-white border border-border rounded">
            <nav class="flex border-b border-border overflow-x-auto">
                <button v-for="t in TABS" :key="t.key"
                    @click="tab = t.key"
                    class="px-4 py-2 text-sm font-medium border-b-2 whitespace-nowrap"
                    :class="tab === t.key ? 'border-maroon text-maroon bg-saffron-soft' : 'border-transparent text-ink-mute hover:bg-cream'">
                    {{ t.label }}
                </button>
            </nav>

            <div class="p-4 min-h-[300px]">

                <!-- PROFILE -->
                <div v-if="tab === 'profile'" class="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-2 text-sm">
                    <div><span class="text-xs text-ink-mute">Full Name (Aadhaar):</span> <span class="font-medium">{{ orDash(s.aadhaar_full_name) }}</span></div>
                    <div><span class="text-xs text-ink-mute">DOB:</span> {{ fmtDay(s.dob) }}</div>
                    <div><span class="text-xs text-ink-mute">Gender:</span> {{ orDash(s.gender) }}</div>
                    <div><span class="text-xs text-ink-mute">Aadhaar:</span> <span class="font-mono">•••• •••• {{ orDash(s.aadhaar_last4) }}</span></div>
                    <div><span class="text-xs text-ink-mute">ABC ID:</span> <span class="font-mono">{{ orDash(s.abc_id) }}</span></div>
                    <div><span class="text-xs text-ink-mute">Nationality:</span> {{ orDash(s.nationality) }}{{ s.foreign_national ? ' (Foreign national)' : '' }}</div>
                    <div><span class="text-xs text-ink-mute">Category:</span> {{ s.category?.code || '—' }}</div>
                    <div><span class="text-xs text-ink-mute">Sub-caste:</span> {{ orDash(s.sub_caste) }}</div>
                    <div><span class="text-xs text-ink-mute">Minority:</span> {{ yes(s.is_minority) }}</div>
                    <div><span class="text-xs text-ink-mute">Religion:</span> {{ orDash(s.religion) }}</div>
                    <div><span class="text-xs text-ink-mute">Mother Tongue:</span> {{ orDash(s.mother_tongue) }}</div>
                    <div><span class="text-xs text-ink-mute">Blood Group:</span> {{ orDash(s.blood_group) }}</div>
                    <div><span class="text-xs text-ink-mute">PwD Type:</span> {{ orDash(s.pwd_type) }}</div>
                    <div><span class="text-xs text-ink-mute">PwD %:</span> {{ orDash(s.pwd_percentage) }}</div>
                    <div><span class="text-xs text-ink-mute">UDID:</span> <span class="font-mono">{{ orDash(s.udid_number) }}</span></div>
                    <div class="md:col-span-3 border-t border-border pt-2 mt-2 font-medium">Reservation Certificates</div>
                    <div><span class="text-xs text-ink-mute">Cert No.:</span> <span class="font-mono">{{ orDash(s.category_certificate_no) }}</span></div>
                    <div><span class="text-xs text-ink-mute">Issuer:</span> {{ orDash(s.category_cert_issuer) }}</div>
                    <div><span class="text-xs text-ink-mute">Date:</span> {{ fmtDay(s.category_cert_date) }}</div>
                    <div><span class="text-xs text-ink-mute">Validity Year:</span> {{ orDash(s.category_cert_validity_year) }}</div>
                    <div><span class="text-xs text-ink-mute">Income Cert No.:</span> <span class="font-mono">{{ orDash(s.income_certificate_no) }}</span></div>
                </div>

                <!-- FAMILY -->
                <div v-else-if="tab === 'family'" class="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-2 text-sm">
                    <div class="md:col-span-3 font-medium">Father</div>
                    <div><span class="text-xs text-ink-mute">Name:</span> {{ orDash(s.father_name) }}</div>
                    <div><span class="text-xs text-ink-mute">Occupation:</span> {{ orDash(s.father_occupation) }}</div>
                    <div><span class="text-xs text-ink-mute">Qualification:</span> {{ orDash(s.father_qualification) }}</div>
                    <div><span class="text-xs text-ink-mute">Income:</span> {{ inr(s.father_income) }}</div>
                    <div><span class="text-xs text-ink-mute">Mobile:</span> <span class="font-mono">{{ orDash(s.father_mobile) }}</span></div>
                    <div><span class="text-xs text-ink-mute">Email:</span> {{ orDash(s.father_email) }}</div>

                    <div class="md:col-span-3 font-medium border-t border-border pt-2 mt-2">Mother</div>
                    <div><span class="text-xs text-ink-mute">Name:</span> {{ orDash(s.mother_name) }}</div>
                    <div><span class="text-xs text-ink-mute">Occupation:</span> {{ orDash(s.mother_occupation) }}</div>
                    <div><span class="text-xs text-ink-mute">Qualification:</span> {{ orDash(s.mother_qualification) }}</div>
                    <div><span class="text-xs text-ink-mute">Income:</span> {{ inr(s.mother_income) }}</div>
                    <div><span class="text-xs text-ink-mute">Mobile:</span> <span class="font-mono">{{ orDash(s.mother_mobile) }}</span></div>
                    <div><span class="text-xs text-ink-mute">Email:</span> {{ orDash(s.mother_email) }}</div>

                    <div class="md:col-span-3 font-medium border-t border-border pt-2 mt-2">Other</div>
                    <div><span class="text-xs text-ink-mute">Total Family Income:</span> {{ inr(s.annual_family_income) }}</div>
                    <div><span class="text-xs text-ink-mute">Siblings:</span> {{ orDash(s.siblings_count) }}</div>
                    <div><span class="text-xs text-ink-mute">Govt Service:</span> {{ yes(s.family_in_govt_service) }}</div>
                    <div><span class="text-xs text-ink-mute">Single Parent:</span> {{ yes(s.is_single_parent) }}</div>
                    <div><span class="text-xs text-ink-mute">First-Gen Graduate:</span> {{ yes(s.is_first_generation_graduate) }}</div>
                    <div><span class="text-xs text-ink-mute">Guardian Mobile:</span> <span class="font-mono">{{ orDash(s.guardian_mobile) }}</span></div>
                    <div><span class="text-xs text-ink-mute">Emergency Contact:</span> <span class="font-mono">{{ orDash(s.emergency_contact) }}</span></div>
                </div>

                <!-- ADDRESS -->
                <div v-else-if="tab === 'address'" class="space-y-3 text-sm">
                    <div class="border border-border rounded p-3">
                        <div class="font-medium mb-1">Permanent</div>
                        <div>{{ fullAddress || '—' }}</div>
                        <div class="text-xs text-ink-mute mt-1">Domicile: {{ orDash(s.domicile_state) }} / {{ orDash(s.domicile_district) }}</div>
                    </div>
                    <div class="border border-border rounded p-3">
                        <div class="font-medium mb-1">Correspondence</div>
                        <div v-if="s.correspondence_same_as_permanent" class="text-xs text-ink-mute italic">Same as permanent</div>
                        <div v-else>
                            {{ [s.correspondence_house_no, s.correspondence_locality, s.correspondence_taluka, s.correspondence_district, s.correspondence_pincode, s.correspondence_state, s.correspondence_country].filter(Boolean).join(', ') || '—' }}
                        </div>
                    </div>
                </div>

                <!-- EDUCATION -->
                <div v-else-if="tab === 'education'" class="space-y-4 text-sm">
                    <div v-for="r in s.academic_records || []" :key="r.id" class="border border-border rounded">
                        <header class="px-4 py-2 bg-cream border-b border-border flex justify-between items-center">
                            <h3 class="font-serif text-base text-maroon">{{ levelLabel(r.level) }}</h3>
                            <div class="text-xs font-mono text-ink-mute">
                                <span class="px-2 py-0.5 rounded bg-saffron-soft text-maroon font-semibold">{{ r.percentage }}%</span>
                                <span v-if="r.cgpa" class="ml-2">CGPA {{ r.cgpa }}</span>
                            </div>
                        </header>
                        <div class="p-4 grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-1.5">
                            <div><span class="text-xs text-ink-mute">Board / Council:</span> {{ orDash(r.board) }}</div>
                            <div><span class="text-xs text-ink-mute">Year of Passing:</span> <span class="font-mono">{{ orDash(r.passing_year) }}</span></div>
                            <div><span class="text-xs text-ink-mute">Roll No.:</span> <span class="font-mono">{{ orDash(r.roll_number) }}</span></div>
                            <div class="md:col-span-2"><span class="text-xs text-ink-mute">School / Institution:</span> {{ orDash(r.school_name) }}</div>
                            <div><span class="text-xs text-ink-mute">School Code:</span> <span class="font-mono">{{ orDash(r.school_code) }}</span></div>
                            <div><span class="text-xs text-ink-mute">Stream:</span> {{ orDash(r.stream) }}</div>
                            <div><span class="text-xs text-ink-mute">Medium:</span> {{ orDash(r.medium) }}</div>
                            <div v-if="r.aggregate_best5_pct"><span class="text-xs text-ink-mute">Aggregate (Best 5):</span> <span class="font-mono">{{ r.aggregate_best5_pct }}%</span></div>
                        </div>

                        <!-- Subject-wise marks (for 12th / UG with subjects entered) -->
                        <div v-if="recordSubjects(r).length" class="px-4 pb-4">
                            <div class="text-[10px] uppercase tracking-wider font-semibold text-maroon mb-2">Subject-wise Marks</div>
                            <table class="w-full">
                                <thead class="text-xs uppercase text-ink-mute bg-cream/50">
                                    <tr>
                                        <th class="text-left px-2 py-1 w-8">#</th>
                                        <th class="text-left px-2 py-1">Subject</th>
                                        <th class="text-left px-2 py-1 w-24">Code</th>
                                        <th class="text-center px-2 py-1 w-20">Theory</th>
                                        <th class="text-center px-2 py-1 w-20">Practical</th>
                                        <th class="text-center px-2 py-1 w-20">Total</th>
                                        <th class="text-center px-2 py-1 w-20">Max</th>
                                        <th class="text-center px-2 py-1 w-16">%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(sub, i) in recordSubjects(r)" :key="i" class="border-t border-border">
                                        <td class="px-2 py-1 text-center text-xs">{{ i + 1 }}</td>
                                        <td class="px-2 py-1">{{ sub.name }}</td>
                                        <td class="px-2 py-1 font-mono text-xs">{{ orDash(sub.code) }}</td>
                                        <td class="px-2 py-1 text-center font-mono">{{ orDash(sub.theory) }}</td>
                                        <td class="px-2 py-1 text-center font-mono">{{ orDash(sub.practical) }}</td>
                                        <td class="px-2 py-1 text-center font-mono font-semibold">{{ subjectRowTotal(sub) }}</td>
                                        <td class="px-2 py-1 text-center font-mono">{{ subjectRowMax(sub) }}</td>
                                        <td class="px-2 py-1 text-center font-mono text-xs">{{ subjectRowPct(sub) }}</td>
                                    </tr>
                                    <tr class="border-t-2 border-maroon bg-saffron-soft font-semibold">
                                        <td colspan="5" class="px-2 py-1 text-right">Aggregate</td>
                                        <td class="px-2 py-1 text-center font-mono">{{ r.obtained_marks }}</td>
                                        <td class="px-2 py-1 text-center font-mono">{{ r.full_marks }}</td>
                                        <td class="px-2 py-1 text-center font-mono text-maroon">{{ r.percentage }}%</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!-- 10th-style record: just FM / OM / % -->
                        <div v-else-if="r.full_marks || r.obtained_marks" class="px-4 pb-4">
                            <div class="grid grid-cols-3 gap-4 text-center">
                                <div class="border border-border rounded p-2">
                                    <div class="text-[10px] uppercase text-ink-mute">Maximum Marks</div>
                                    <div class="font-mono text-lg">{{ r.full_marks }}</div>
                                </div>
                                <div class="border border-border rounded p-2">
                                    <div class="text-[10px] uppercase text-ink-mute">Marks Obtained</div>
                                    <div class="font-mono text-lg">{{ r.obtained_marks }}</div>
                                </div>
                                <div class="border border-maroon rounded p-2 bg-saffron-soft">
                                    <div class="text-[10px] uppercase text-ink-mute">Percentage</div>
                                    <div class="font-mono text-lg text-maroon font-bold">{{ r.percentage }}%</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p v-if="!(s.academic_records || []).length" class="text-xs text-ink-mute italic py-6 text-center">
                        No academic records on file.
                    </p>

                    <div v-if="(s.entrance_exams || []).length" class="border border-border rounded">
                        <header class="px-4 py-2 bg-cream border-b border-border">
                            <h3 class="font-serif text-base text-maroon">Entrance / Competitive Examinations</h3>
                        </header>
                        <table class="w-full">
                            <thead class="text-xs uppercase text-ink-mute">
                                <tr>
                                    <th class="text-left px-4 py-1">Exam</th>
                                    <th class="text-left px-4 py-1">Roll No.</th>
                                    <th class="text-left px-4 py-1">Score / Percentile</th>
                                    <th class="text-left px-4 py-1">Year</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="e in s.entrance_exams" :key="e.id" class="border-t border-border">
                                    <td class="px-4 py-2">{{ e.exam_name }}</td>
                                    <td class="px-4 py-2 font-mono">{{ orDash(e.roll_number) }}</td>
                                    <td class="px-4 py-2 font-mono">{{ orDash(e.score) }}</td>
                                    <td class="px-4 py-2 font-mono">{{ orDash(e.exam_year) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- SUBJECTS (NEP) -->
                <div v-else-if="tab === 'subjects'" class="text-sm">
                    <div class="mb-4 flex items-center justify-between">
                        <p class="text-xs text-ink-mute">Subject combination picked by the student for this application.</p>
                        <div class="text-xs font-mono">
                            Total Credits: <span class="px-2 py-0.5 rounded bg-saffron-soft text-maroon font-bold">{{ totalCredits }}</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div v-for="(label, cat) in NEP_CATEGORIES" :key="cat"
                            class="border border-border rounded overflow-hidden">
                            <header class="px-3 py-2 flex items-center justify-between border-b border-border"
                                :class="(subjectsByCategory[cat] || []).length ? 'bg-saffron-soft' : 'bg-cream'">
                                <div>
                                    <div class="text-[10px] uppercase font-mono tracking-wider"
                                        :class="(subjectsByCategory[cat] || []).length ? 'text-maroon' : 'text-ink-mute'">
                                        {{ cat }}
                                    </div>
                                    <div class="text-sm font-medium"
                                        :class="(subjectsByCategory[cat] || []).length ? 'text-maroon' : 'text-ink-mute'">
                                        {{ label }}
                                    </div>
                                </div>
                                <span v-if="(subjectsByCategory[cat] || []).length" class="text-xs font-mono text-green-700">✓ {{ (subjectsByCategory[cat] || []).length }} picked</span>
                                <span v-else class="text-xs text-ink-mute italic">not selected</span>
                            </header>
                            <ul v-if="(subjectsByCategory[cat] || []).length" class="divide-y divide-border">
                                <li v-for="p in subjectsByCategory[cat]" :key="p?.id" class="px-3 py-2 flex justify-between items-center">
                                    <div>
                                        <div class="font-medium">{{ p?.course_name }}</div>
                                        <div class="text-xs font-mono text-ink-mute">{{ orDash(p?.course_code) }}</div>
                                    </div>
                                    <span class="text-xs font-mono px-2 py-0.5 rounded bg-cream">{{ p?.credits || 0 }} cr</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div v-if="application.special_request" class="mt-4 p-3 border border-border rounded bg-cream">
                        <div class="text-xs text-ink-mute mb-1">Special Request</div>
                        <p class="italic">"{{ application.special_request }}"</p>
                    </div>
                </div>

                <!-- DOCUMENTS -->
                <div v-else-if="tab === 'documents'" class="space-y-2">
                    <div v-for="d in documents" :key="d.id" class="border border-border rounded">
                        <div class="flex justify-between items-start gap-3 px-3 py-2">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-medium text-sm">{{ d.type?.label }}</span>
                                    <span v-if="d.type?.required_by_default" class="text-[10px] text-maroon font-bold">*REQ</span>
                                    <span class="px-2 py-0.5 text-xs rounded" :class="docStatusBadge(d.status)">{{ d.status }}</span>
                                    <span v-if="d.source === 'digilocker'" class="px-2 py-0.5 text-xs rounded bg-blue-100 text-blue-800">via DigiLocker</span>
                                </div>
                                <div class="text-xs text-ink-mute font-mono mt-1">
                                    {{ d.original_name }} · {{ (d.size_bytes / 1024).toFixed(0) }} KB · {{ d.mime }} · disk: {{ d.disk }}
                                </div>
                                <div v-if="d.rejection_reason" class="text-xs text-red-700 mt-1">
                                    <strong>Rejection reason:</strong> {{ d.rejection_reason }}
                                </div>
                            </div>
                            <div class="flex gap-1.5 flex-wrap justify-end">
                                <button @click="openPreview(d)"
                                    class="px-2.5 py-1 text-xs bg-saffron text-white rounded hover:bg-saffron/90">
                                    👁 Preview
                                </button>
                                <a :href="`/documents/${d.id}/download`"
                                    class="px-2.5 py-1 text-xs border border-border rounded text-ink hover:bg-cream">
                                    ⬇ Download
                                </a>
                                <button v-if="d.status !== 'approved'"
                                    @click="approve(d)"
                                    class="px-2.5 py-1 text-xs bg-green-600 text-white rounded hover:bg-green-700">
                                    ✓ Approve
                                </button>
                                <button v-if="d.status !== 'rejected'"
                                    @click="startReject(d)"
                                    class="px-2.5 py-1 text-xs bg-red-600 text-white rounded hover:bg-red-700">
                                    ✗ Reject
                                </button>
                            </div>
                        </div>

                        <!-- Inline reject reason form -->
                        <div v-if="rejectingId === d.id" class="px-3 py-2 border-t border-border bg-red-50">
                            <label class="block text-xs font-medium text-red-800 mb-1">Rejection reason (audit-logged)</label>
                            <textarea v-model="rejectForm.reason" rows="2"
                                placeholder="e.g. Photo unclear — please re-scan in colour"
                                class="w-full px-3 py-2 text-sm border border-red-300 rounded mb-2"
                                :class="rejectForm.errors.reason ? 'border-red-500' : ''"></textarea>
                            <p v-if="rejectForm.errors.reason" class="text-xs text-red-600 mb-2">{{ rejectForm.errors.reason }}</p>
                            <div class="flex gap-2">
                                <button @click="submitReject(d)"
                                    :disabled="!rejectForm.reason || rejectForm.processing"
                                    class="px-3 py-1 text-xs bg-red-600 text-white rounded hover:bg-red-700 disabled:opacity-50">
                                    Confirm Rejection
                                </button>
                                <button @click="cancelReject" class="px-3 py-1 text-xs border border-border rounded hover:bg-white">Cancel</button>
                            </div>
                        </div>

                        <!-- Verification history -->
                        <div v-if="(d.verifications || []).length" class="px-3 py-2 border-t border-border text-xs">
                            <div class="text-[10px] uppercase text-ink-mute font-semibold mb-1">Verification History</div>
                            <ul class="space-y-0.5">
                                <li v-for="v in d.verifications" :key="v.id">
                                    <span class="font-mono uppercase"
                                        :class="v.action === 'approved' ? 'text-green-700' : 'text-red-600'">
                                        {{ v.action }}
                                    </span>
                                    by {{ v.verifier?.name || 'system' }}
                                    · {{ fmtDate(v.decided_at) }}
                                    <span v-if="v.remark" class="text-ink-mute">— "{{ v.remark }}"</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <p v-if="!documents.length" class="text-xs text-ink-mute italic py-4 text-center">No documents uploaded.</p>
                </div>

                <!-- PAYMENTS -->
                <div v-else-if="tab === 'payments'" class="space-y-3 text-sm">
                    <div v-for="o in application.payment_orders || []" :key="o.id"
                        class="border border-border rounded">
                        <div class="px-3 py-2 bg-cream flex justify-between items-center">
                            <div>
                                <span class="font-mono font-semibold">{{ o.order_number }}</span>
                                <span class="ml-2 text-xs text-ink-mute">{{ o.purpose }}</span>
                            </div>
                            <span class="px-2 py-0.5 text-xs rounded font-mono uppercase" :class="paymentStatusBadge(o.status)">{{ o.status }}</span>
                        </div>
                        <div class="px-3 py-2 grid grid-cols-1 md:grid-cols-4 gap-2 text-xs">
                            <div><span class="text-ink-mute">Gateway:</span> {{ o.gateway?.display_name }} <span class="text-ink-mute font-mono">({{ o.gateway?.mode }})</span></div>
                            <div><span class="text-ink-mute">Fee:</span> <span class="font-mono">{{ inr(o.amount) }}</span></div>
                            <div><span class="text-ink-mute">Convenience:</span> <span class="font-mono">{{ inr(o.convenience_fee) }}</span></div>
                            <div><span class="text-ink-mute">GST:</span> <span class="font-mono">{{ inr(o.gst) }}</span></div>
                            <div><span class="text-ink-mute">Total:</span> <span class="font-mono font-semibold">{{ inr(o.total) }}</span></div>
                            <div><span class="text-ink-mute">Gateway Order ID:</span> <span class="font-mono">{{ orDash(o.gateway_order_id) }}</span></div>
                            <div><span class="text-ink-mute">Paid:</span> {{ fmtDate(o.paid_at) }}</div>
                            <div><span class="text-ink-mute">Initiated:</span> {{ fmtDate(o.initiated_at) }}</div>
                        </div>
                        <div v-if="o.transactions?.length" class="px-3 py-2 border-t border-border">
                            <div class="text-[10px] uppercase text-ink-mute font-semibold mb-1">Transactions</div>
                            <table class="w-full text-xs">
                                <tr v-for="t in o.transactions" :key="t.id" class="border-t border-border">
                                    <td class="py-1 font-mono">{{ t.gateway_txn_id }}</td>
                                    <td class="py-1">{{ t.status }}</td>
                                    <td class="py-1">{{ t.method || '—' }}</td>
                                    <td class="py-1 text-right font-mono">{{ inr(t.amount) }}</td>
                                    <td class="py-1 text-right">{{ fmtDate(t.paid_at) }}</td>
                                </tr>
                            </table>
                        </div>
                        <div v-if="o.refunds?.length" class="px-3 py-2 border-t border-border">
                            <div class="text-[10px] uppercase text-ink-mute font-semibold mb-1">Refunds</div>
                            <table class="w-full text-xs">
                                <tr v-for="r in o.refunds" :key="r.id" class="border-t border-border">
                                    <td class="py-1 font-mono">{{ r.gateway_refund_id || '—' }}</td>
                                    <td class="py-1">{{ r.status }}</td>
                                    <td class="py-1 text-right font-mono">{{ inr(r.amount) }}</td>
                                    <td class="py-1 text-right">{{ fmtDate(r.completed_at) }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <p v-if="!(application.payment_orders || []).length" class="text-xs text-ink-mute italic">No payment orders.</p>
                </div>

                <!-- ELIGIBILITY -->
                <div v-else-if="tab === 'eligibility'" class="text-sm space-y-3">
                    <div class="flex items-center gap-3">
                        <span class="px-3 py-1 text-sm rounded font-semibold" :class="verdictBadge(application.eligibility_verdict)">
                            {{ application.eligibility_verdict || 'pending' }}
                        </span>
                        <button @click="showOverride = !showOverride" class="text-xs text-maroon hover:underline">
                            {{ showOverride ? 'Cancel' : 'Override verdict →' }}
                        </button>
                    </div>

                    <ul v-if="application.eligibility_reasons?.length" class="text-red-700 list-disc list-inside space-y-1 text-xs">
                        <li v-for="r in application.eligibility_reasons" :key="r">{{ r }}</li>
                    </ul>
                    <p v-else class="text-xs text-ink-mute">No rule failures recorded.</p>

                    <div v-if="application.eligibility_remark" class="p-3 bg-cream rounded text-xs">
                        <strong>Override remark:</strong> {{ application.eligibility_remark }}
                        <span v-if="application.eligibility_decided_by" class="text-ink-mute">— by {{ application.eligibility_decided_by.name }} on {{ fmtDate(application.eligibility_decided_at) }}</span>
                    </div>

                    <form v-if="showOverride" @submit.prevent="submitOverride" class="space-y-3 p-3 border-2 border-maroon rounded">
                        <div>
                            <label class="block text-xs font-medium text-ink mb-1">New verdict</label>
                            <select v-model="overrideForm.verdict" class="w-full px-3 py-2 text-sm border border-border rounded">
                                <option value="override_pass">Override → Pass</option>
                                <option value="override_fail">Override → Fail</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-ink mb-1">Remark (audit-logged)</label>
                            <textarea v-model="overrideForm.remark" rows="2"
                                class="w-full px-3 py-2 text-sm border border-border rounded"
                                :class="overrideForm.errors.remark ? 'border-red-500' : ''"></textarea>
                            <p v-if="overrideForm.errors.remark" class="text-xs text-red-600 mt-1">{{ overrideForm.errors.remark }}</p>
                        </div>
                        <Button type="submit" :loading="overrideForm.processing">Apply Override</Button>
                    </form>
                </div>

                <!-- AUDIT -->
                <div v-else-if="tab === 'audit'" class="text-sm">
                    <table class="w-full">
                        <thead class="text-xs uppercase text-ink-mute">
                            <tr>
                                <th class="text-left py-1 w-44">When</th>
                                <th class="text-left py-1 w-32">Actor</th>
                                <th class="text-left py-1 w-24">Event</th>
                                <th class="text-left py-1">Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="a in audit" :key="a.id" class="border-t border-border">
                                <td class="py-2 font-mono text-xs">{{ fmtDate(a.created_at) }}</td>
                                <td class="py-2 text-xs">{{ a.causer?.name || '—' }}</td>
                                <td class="py-2 text-xs font-mono uppercase">{{ a.event }}</td>
                                <td class="py-2 text-xs">{{ a.description }}</td>
                            </tr>
                            <tr v-if="!audit.length">
                                <td colspan="4" class="py-4 text-center text-ink-mute italic">No audit entries.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

        <!-- Document Preview Modal -->
        <Teleport to="body">
            <div v-if="previewDoc"
                class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4"
                @click.self="closePreview">
                <div class="bg-white rounded shadow-xl max-w-5xl w-full max-h-[90vh] flex flex-col">
                    <header class="px-4 py-2 border-b border-border flex justify-between items-center bg-cream">
                        <div>
                            <div class="font-serif text-base text-maroon">{{ previewDoc.document_type?.label }}</div>
                            <div class="text-xs text-ink-mute font-mono">{{ previewDoc.original_name }} · {{ previewDoc.mime }}</div>
                        </div>
                        <div class="flex gap-2 items-center">
                            <a :href="`/documents/${previewDoc.id}/download`" class="text-xs text-maroon hover:underline">⬇ Download</a>
                            <button @click="closePreview" class="text-xl leading-none text-ink-mute hover:text-maroon">×</button>
                        </div>
                    </header>
                    <div class="flex-1 overflow-auto p-2 bg-gray-100">
                        <img v-if="isImage" :src="previewUrl" :alt="previewDoc.original_name"
                            class="max-w-full mx-auto" />
                        <iframe v-else-if="isPdf" :src="previewUrl"
                            class="w-full h-[80vh] border-0 bg-white"></iframe>
                        <div v-else class="p-8 text-center text-sm text-ink-mute">
                            Preview not available for <span class="font-mono">{{ previewDoc.mime }}</span>.
                            <a :href="`/documents/${previewDoc.id}/download`" class="text-maroon underline ml-1">Download instead.</a>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </PortalLayout>
</template>
