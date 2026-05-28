<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import Button from '@/Components/Ui/Button.vue';
import { ref, reactive, computed, watch, onMounted, onUnmounted } from 'vue';
import { formatDate, formatDateTime } from '@/utils/date.js';

const props = defineProps({
    application: { type: Object, required: true },
    student: { type: Object, required: true },
    academic_records: { type: Array, default: () => [] },
    pools: { type: Object, required: true },
    selections: { type: Object, required: true },
    picks: { type: Object, default: () => ({}) },
    categories: { type: Object, required: true },
    latest_withdrawal: { type: Object, default: null },
});

// --- Withdraw flow
const showWithdrawModal = ref(false);
const withdrawForm = useForm({ reason: '' });
const submitWithdrawal = () => withdrawForm.post(`/student/applications/${props.application.id}/withdraw`, {
    onSuccess: () => { showWithdrawModal.value = false; withdrawForm.reset(); },
    preserveScroll: true,
});

const canWithdraw = computed(() =>
    ['submitted', 'verified'].includes(props.application.status)
    && !(props.latest_withdrawal && props.latest_withdrawal.status === 'pending')
);
const pendingWithdrawal = computed(() => props.latest_withdrawal?.status === 'pending');
const rejectedWithdrawal = computed(() => props.latest_withdrawal?.status === 'rejected');

const isDraft = computed(() => props.application.status === 'draft');

const REQUIRED_CATEGORIES = ['minor', 'aec', 'sec', 'vac'];
const OPTIONAL_CATEGORIES = ['mdc', 'internship', 'research'];

// --- DRAFT MODE STATE (form) ---
const selectionState = reactive({});
for (const cat of Object.keys(props.categories)) {
    selectionState[cat] = [...(props.selections[cat] || [])];
}
for (const p of (props.pools.major || [])) {
    if (p.is_default && !selectionState.major.includes(p.id)) {
        selectionState.major.push(p.id);
    }
}

const declarations = reactive({
    declaration_anti_ragging: !!props.application.declaration_anti_ragging,
    declaration_information_true: !!props.application.declaration_information_true,
});

const specialRequest = ref(props.application.special_request || '');
const lastSavedAt = ref(null);
const saving = ref(false);
const submitError = ref(null);

const toggle = (cat, id) => {
    const arr = selectionState[cat];
    const idx = arr.indexOf(id);
    if (idx >= 0) arr.splice(idx, 1);
    else arr.push(id);
};
const isPicked = (cat, id) => selectionState[cat]?.includes(id);

const autosave = async () => {
    if (!isDraft.value) return;
    saving.value = true;
    try {
        await fetch(`/student/applications/${props.application.id}/draft`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                selections: selectionState,
                special_request: specialRequest.value,
                declaration_anti_ragging: declarations.declaration_anti_ragging,
                declaration_information_true: declarations.declaration_information_true,
            }),
        });
        lastSavedAt.value = new Date();
    } finally {
        saving.value = false;
    }
};

let timer = null;
const scheduleSave = () => { clearTimeout(timer); timer = setTimeout(autosave, 1200); };
watch([selectionState, declarations, specialRequest], scheduleSave, { deep: true });
let interval = null;
onMounted(() => { if (isDraft.value) interval = setInterval(autosave, 30000); });
onUnmounted(() => { clearInterval(interval); clearTimeout(timer); });

const submit = () => {
    submitError.value = null;
    router.post(`/student/applications/${props.application.id}/submit`, {
        selections: selectionState,
        special_request: specialRequest.value,
        declaration_anti_ragging: declarations.declaration_anti_ragging,
        declaration_information_true: declarations.declaration_information_true,
    }, {
        onError: (errors) => { submitError.value = Object.values(errors).flat().join(' · '); },
    });
};

const canSubmit = computed(() => {
    if (!isDraft.value) return false;
    if (!declarations.declaration_anti_ragging) return false;
    if (!declarations.declaration_information_true) return false;
    for (const cat of REQUIRED_CATEGORIES) {
        if (!(selectionState[cat] || []).length) return false;
    }
    return true;
});

const categoryRequired = (cat) => REQUIRED_CATEGORIES.includes(cat);
const categoryOptional = (cat) => OPTIONAL_CATEGORIES.includes(cat);

const inr = (n) => n ? '₹ ' + Number(n).toLocaleString('en-IN') : '—';
const orDash = (v) => (v ?? '') !== '' ? v : '—';
const fmtDate = (iso) => formatDateTime(iso);
const fmtDay = (iso) => formatDate(iso);

const print = () => window.print();

const awaitingPayment = computed(() =>
    props.application.status === 'submitted' && props.application.payment_status === 'pending'
);

const statusBadge = computed(() => {
    if (awaitingPayment.value) {
        return { label: 'Awaiting Payment', cls: 'bg-amber-100 text-amber-800 border border-amber-300' };
    }
    return {
        draft: { label: 'Draft', cls: 'bg-gray-100 text-gray-700' },
        submitted: { label: 'Submitted', cls: 'bg-blue-100 text-blue-800' },
        verified: { label: 'Verified', cls: 'bg-green-100 text-green-800' },
        rejected: { label: 'Rejected', cls: 'bg-red-100 text-red-800' },
        withdrawn: { label: 'Withdrawn', cls: 'bg-amber-100 text-amber-800' },
    }[props.application.status] || { label: props.application.status, cls: 'bg-gray-100 text-gray-700' };
});

const paymentBadge = computed(() => ({
    pending: { label: 'Payment Pending', cls: 'bg-amber-100 text-amber-800' },
    paid: { label: 'Paid', cls: 'bg-green-100 text-green-800' },
    covered: { label: 'Covered by earlier payment', cls: 'bg-green-50 text-green-700' },
    not_required: { label: 'No fee required', cls: 'bg-gray-100 text-gray-700' },
}[props.application.payment_status] || { label: props.application.payment_status, cls: 'bg-gray-100 text-gray-700' }));

const verdictBadge = computed(() => ({
    pending: 'bg-gray-100 text-gray-700',
    pass: 'bg-green-100 text-green-800',
    override_pass: 'bg-green-100 text-green-800',
    fail: 'bg-red-100 text-red-800',
    override_fail: 'bg-red-100 text-red-800',
}[props.application.eligibility_verdict] || 'bg-gray-100 text-gray-700'));

const fullAddress = computed(() => {
    const s = props.student;
    return [s.house_no, s.locality, s.taluka, s.district, `${s.pincode || ''}`, s.state, s.country]
        .filter(Boolean).join(', ');
});
</script>

<template>
    <Head :title="`Application — ${application.application_number || 'Draft'}`" />

    <!-- DRAFT MODE (the editable application form) -->
    <PortalLayout v-if="isDraft"
        :title="`Application — ${application.program?.code} ${application.program?.name}`"
        :breadcrumb="['Student', 'Applications', 'Draft']">

        <div class="bg-saffron-soft border border-saffron/30 rounded p-3 mb-4 flex justify-between items-center text-sm">
            <div>
                <strong>Draft</strong>
                <span class="ml-2 text-ink-mute">· {{ application.session?.code }}</span>
            </div>
            <div class="text-xs text-ink-mute">
                <span v-if="saving">Saving…</span>
                <span v-else-if="lastSavedAt">Saved at {{ lastSavedAt.toLocaleTimeString('en-IN') }}</span>
                <span v-else>Autosaves every 30s</span>
            </div>
        </div>

        <!-- 1. Profile snapshot -->
        <section class="bg-white border border-border rounded mb-4">
            <header class="px-4 py-2 border-b border-border bg-cream">
                <h2 class="font-serif text-base text-maroon">Your Profile (locked snapshot)</h2>
            </header>
            <div class="p-4 grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-2 text-sm">
                <div><span class="text-xs text-ink-mute">Name:</span> {{ orDash(student.aadhaar_full_name || student.user?.name) }}</div>
                <div><span class="text-xs text-ink-mute">DOB:</span> {{ fmtDay(student.dob) }}</div>
                <div><span class="text-xs text-ink-mute">Gender:</span> {{ orDash(student.gender) }}</div>
                <div><span class="text-xs text-ink-mute">Category:</span> {{ student.category?.code || '—' }}</div>
                <div><span class="text-xs text-ink-mute">Aadhaar:</span> •••• •••• {{ orDash(student.aadhaar_last4) }}</div>
                <div><span class="text-xs text-ink-mute">ABC ID:</span> {{ orDash(student.abc_id) }}</div>
                <div><span class="text-xs text-ink-mute">Email:</span> {{ orDash(student.user?.email) }}</div>
                <div><span class="text-xs text-ink-mute">Mobile:</span> {{ orDash(student.user?.mobile) }}</div>
                <div><span class="text-xs text-ink-mute">Domicile:</span> {{ orDash(student.domicile_state) }}</div>
            </div>
        </section>

        <!-- 2. Programme details -->
        <section class="bg-white border border-border rounded mb-4">
            <header class="px-4 py-2 border-b border-border bg-cream">
                <h2 class="font-serif text-base text-maroon">Programme</h2>
            </header>
            <div class="p-4 grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-2 text-sm">
                <div><span class="text-xs text-ink-mute">Code:</span> <span class="font-mono">{{ application.program?.code }}</span></div>
                <div><span class="text-xs text-ink-mute">Name:</span> {{ application.program?.name }}</div>
                <div><span class="text-xs text-ink-mute">Type:</span> {{ application.program?.type }}</div>
                <div><span class="text-xs text-ink-mute">Department:</span> {{ application.program?.department?.name }}</div>
            </div>
        </section>

        <!-- 3. NEP 2020 Subject Combination -->
        <section class="bg-white border border-border rounded mb-4">
            <header class="px-4 py-2 border-b border-border bg-cream">
                <h2 class="font-serif text-base text-maroon">Subject Combination (NEP 2020)</h2>
            </header>
            <div class="p-4 space-y-4">
                <div v-for="(label, cat) in categories" :key="cat" class="border border-border rounded p-3">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-semibold text-maroon">
                            {{ label }}
                            <span v-if="categoryRequired(cat)" class="text-xs text-maroon ml-1">*</span>
                            <span v-else-if="categoryOptional(cat)" class="text-xs text-ink-mute ml-1">(optional)</span>
                        </h3>
                        <span v-if="selectionState[cat]?.length" class="text-xs text-green-700 font-mono">
                            {{ selectionState[cat].length }} picked
                        </span>
                    </div>
                    <div v-if="!pools[cat]?.length" class="text-xs text-ink-mute italic">
                        No courses configured by admin for this category.
                    </div>
                    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <label v-for="p in pools[cat]" :key="p.id"
                            class="flex items-start gap-2 px-3 py-2 border rounded cursor-pointer text-sm"
                            :class="isPicked(cat, p.id) ? 'border-maroon bg-saffron-soft' : 'border-border hover:bg-cream'">
                            <input type="checkbox" :checked="isPicked(cat, p.id)"
                                @change="toggle(cat, p.id)"
                                :disabled="cat === 'major' && p.is_default" class="mt-0.5" />
                            <div class="flex-1">
                                <div class="font-medium">{{ p.course_name }}</div>
                                <div class="text-xs text-ink-mute font-mono">
                                    {{ p.course_code || '—' }} · {{ p.credits || '—' }} credits
                                    <span v-if="p.is_default" class="ml-2 text-green-700 font-semibold">Default</span>
                                </div>
                                <div v-if="p.description" class="text-xs text-ink-mute mt-1">{{ p.description }}</div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </section>

        <!-- 4. Special request -->
        <section class="bg-white border border-border rounded mb-4">
            <header class="px-4 py-2 border-b border-border bg-cream">
                <h2 class="font-serif text-base text-maroon">Special Request (Optional)</h2>
            </header>
            <div class="p-4">
                <textarea v-model="specialRequest" rows="3" maxlength="1000"
                    placeholder="Any specific request to the admissions committee…"
                    class="w-full px-3 py-2 text-sm border border-border rounded"></textarea>
                <div class="text-xs text-ink-mute mt-1">{{ specialRequest.length }} / 1000</div>
            </div>
        </section>

        <!-- 5. Declarations & Submit -->
        <section class="bg-white border-2 border-maroon rounded">
            <header class="px-4 py-2 border-b border-border bg-maroon text-white">
                <h2 class="font-serif text-base">Declarations &amp; Submit</h2>
            </header>
            <div class="p-4 space-y-3">
                <label class="flex items-start gap-2 text-sm">
                    <input type="checkbox" v-model="declarations.declaration_anti_ragging" class="mt-1" />
                    <span>
                        I have read the UGC anti-ragging regulations and undertake to abide by them.
                        Any act of ragging on my part will attract disciplinary action including expulsion.
                    </span>
                </label>
                <label class="flex items-start gap-2 text-sm">
                    <input type="checkbox" v-model="declarations.declaration_information_true" class="mt-1" />
                    <span>
                        I confirm that all information provided in this application is true.
                        Any false declaration may result in cancellation of admission.
                    </span>
                </label>
                <div v-if="submitError" class="text-xs text-red-600 bg-red-50 border border-red-200 rounded p-2">
                    {{ submitError }}
                </div>
                <Button @click="submit" :disabled="!canSubmit">Submit Application</Button>
                <p v-if="!canSubmit" class="text-xs text-ink-mute">
                    Pick at least one course from each required category (Minor, AEC, SEC, VAC) and accept both declarations.
                </p>
            </div>
        </section>
    </PortalLayout>

    <!-- ACKNOWLEDGEMENT RECEIPT (after submit) -->
    <PortalLayout v-else title=" " :breadcrumb="['Student', 'Applications', application.application_number]">
        <!-- Action bar (hidden in print) -->
        <div class="flex items-center justify-between mb-4 print:hidden">
            <button @click="router.visit('/student/applications')"
                class="text-xs text-maroon hover:underline">← Back to My Applications</button>
            <div class="flex gap-2">
                <button v-if="canWithdraw" @click="showWithdrawModal = true"
                    class="px-3 py-1.5 text-sm border border-red-400 text-red-700 rounded hover:bg-red-50">
                    Withdraw Application
                </button>
                <a :href="`/student/applications/${application.id}/download`"
                    class="px-3 py-1.5 text-sm bg-maroon text-white rounded hover:bg-maroon/90"
                    title="Download a PDF copy of this acknowledgement">
                    📄 Download PDF
                </a>
                <button @click="print"
                    class="px-3 py-1.5 text-sm bg-navy text-white rounded hover:bg-navy-deep">
                    🖨 Print
                </button>
            </div>
        </div>

        <!-- Withdrawal status banners -->
        <div v-if="pendingWithdrawal" class="print:hidden mb-4 bg-amber-50 border border-amber-300 rounded p-4 text-sm">
            <strong class="text-amber-900">Withdrawal request pending review.</strong>
            <span class="text-amber-800 ml-1">Submitted {{ formatDateTime(latest_withdrawal.requested_at) }}.</span>
            <p v-if="latest_withdrawal.estimated_refund != null" class="text-xs text-amber-800 mt-1">
                Estimated refund: <strong>₹{{ Number(latest_withdrawal.estimated_refund).toFixed(2) }}</strong>
                · slab: {{ latest_withdrawal.estimated_slab }}
            </p>
        </div>
        <div v-if="rejectedWithdrawal" class="print:hidden mb-4 bg-red-50 border border-red-300 rounded p-4 text-sm">
            <strong class="text-red-800">Previous withdrawal request was rejected.</strong>
            <p v-if="latest_withdrawal.admin_remark" class="text-xs text-red-700 mt-1 italic">"{{ latest_withdrawal.admin_remark }}"</p>
        </div>

        <!-- Awaiting Payment banner (hidden in print) -->
        <div v-if="awaitingPayment" class="print:hidden mb-4 bg-amber-50 border-2 border-amber-400 rounded-lg p-5 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="text-3xl">⚠</div>
                <div class="flex-1">
                    <h2 class="font-serif text-lg text-amber-900 mb-1">Application Fee Payment Pending</h2>
                    <p class="text-sm text-amber-800 mb-3">
                        Your application <strong class="font-mono">{{ application.application_number }}</strong> has been recorded
                        but is <strong>not yet considered complete</strong> until the application fee is paid.
                        It will not be sent for verification until then.
                    </p>
                    <Link
                        :href="`/student/applications/${application.id}/payment`"
                        class="inline-block px-5 py-2 bg-amber-600 text-white text-sm font-semibold rounded hover:bg-amber-700 shadow-sm">
                        Pay Application Fee →
                    </Link>
                </div>
            </div>
        </div>

        <article id="ack" class="bg-white border border-border rounded shadow-sm">
            <!-- Document header -->
            <header class="px-6 py-5 border-b-2 border-maroon">
                <div class="flex items-start justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-maroon text-white text-[10px] font-bold flex flex-col items-center justify-center leading-tight">
                                <span>SVNC</span><span>1956</span>
                            </div>
                            <div>
                                <h1 class="font-serif text-xl text-maroon leading-tight">Sardar Vallabhbhai National College</h1>
                                <p class="text-xs text-ink-mute">Anand, Gujarat · Online Admission · Session {{ application.session?.code }}</p>
                            </div>
                        </div>
                    </div>
                    <span class="px-3 py-1 text-xs font-mono uppercase rounded" :class="statusBadge.cls">
                        {{ statusBadge.label }}
                    </span>
                </div>

                <div class="mt-5 grid grid-cols-2 md:grid-cols-5 gap-4 text-sm">
                    <div>
                        <div class="text-[10px] uppercase tracking-wider text-ink-mute">Application No.</div>
                        <div class="font-mono font-semibold text-maroon">{{ application.application_number || '—' }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] uppercase tracking-wider text-ink-mute">Submitted</div>
                        <div class="font-medium">{{ fmtDate(application.submitted_at) }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] uppercase tracking-wider text-ink-mute">Programme</div>
                        <div class="font-medium">{{ application.program?.code }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] uppercase tracking-wider text-ink-mute">Payment</div>
                        <div class="inline-block px-2 py-0.5 rounded text-xs font-mono uppercase" :class="paymentBadge.cls">
                            {{ paymentBadge.label }}
                        </div>
                    </div>
                    <div>
                        <div class="text-[10px] uppercase tracking-wider text-ink-mute">Eligibility</div>
                        <div class="inline-block px-2 py-0.5 rounded text-xs font-mono uppercase" :class="verdictBadge">
                            {{ application.eligibility_verdict || 'pending' }}
                        </div>
                    </div>
                </div>
            </header>

            <!-- Section 1: Applicant -->
            <section class="px-6 py-4 border-b border-border">
                <h2 class="font-serif text-base text-maroon mb-2">1. Applicant</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-1.5 text-sm">
                    <div><span class="text-xs text-ink-mute">Full Name:</span> <span class="font-medium">{{ orDash(student.aadhaar_full_name || student.user?.name) }}</span></div>
                    <div><span class="text-xs text-ink-mute">DOB:</span> {{ fmtDay(student.dob) }}</div>
                    <div><span class="text-xs text-ink-mute">Gender:</span> {{ orDash(student.gender) }}</div>
                    <div><span class="text-xs text-ink-mute">Aadhaar:</span> <span class="font-mono">•••• •••• {{ orDash(student.aadhaar_last4) }}</span></div>
                    <div><span class="text-xs text-ink-mute">ABC ID:</span> <span class="font-mono">{{ orDash(student.abc_id) }}</span></div>
                    <div><span class="text-xs text-ink-mute">Nationality:</span> {{ orDash(student.nationality) }}</div>
                    <div><span class="text-xs text-ink-mute">Category:</span> {{ student.category?.code || '—' }}</div>
                    <div><span class="text-xs text-ink-mute">Religion:</span> {{ orDash(student.religion) }}</div>
                    <div><span class="text-xs text-ink-mute">Mother Tongue:</span> {{ orDash(student.mother_tongue) }}</div>
                    <div><span class="text-xs text-ink-mute">Email:</span> {{ orDash(student.user?.email) }}</div>
                    <div><span class="text-xs text-ink-mute">Mobile:</span> <span class="font-mono">{{ orDash(student.user?.mobile) }}</span></div>
                    <div><span class="text-xs text-ink-mute">Domicile:</span> {{ orDash(student.domicile_state) }}</div>
                </div>
            </section>

            <!-- Section 2: Programme -->
            <section class="px-6 py-4 border-b border-border">
                <h2 class="font-serif text-base text-maroon mb-2">2. Programme Applied For</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-1.5 text-sm">
                    <div><span class="text-xs text-ink-mute">Code:</span> <span class="font-mono font-semibold">{{ application.program?.code }}</span></div>
                    <div class="md:col-span-2"><span class="text-xs text-ink-mute">Name:</span> <span class="font-medium">{{ application.program?.name }}</span></div>
                    <div><span class="text-xs text-ink-mute">Type:</span> {{ application.program?.type }}</div>
                    <div class="md:col-span-2"><span class="text-xs text-ink-mute">Department:</span> {{ application.program?.department?.name }}</div>
                </div>
            </section>

            <!-- Section 3: Subject Combination -->
            <section class="px-6 py-4 border-b border-border">
                <h2 class="font-serif text-base text-maroon mb-2">3. Subject Combination (NEP 2020)</h2>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs text-ink-mute uppercase tracking-wider">
                            <th class="text-left py-1 w-40">Category</th>
                            <th class="text-left py-1">Course(s)</th>
                            <th class="text-right py-1 w-20">Credits</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(label, cat) in categories" :key="cat" class="border-t border-border">
                            <td class="py-2 font-medium">{{ label }}</td>
                            <td class="py-2">
                                <span v-if="!picks[cat]?.length" class="text-xs text-ink-mute italic">— not selected —</span>
                                <ul v-else class="space-y-0.5">
                                    <li v-for="p in picks[cat]" :key="p.code || p.name">
                                        <span class="font-medium">{{ p.name }}</span>
                                        <span v-if="p.code" class="text-xs text-ink-mute font-mono ml-2">({{ p.code }})</span>
                                    </li>
                                </ul>
                            </td>
                            <td class="py-2 text-right font-mono">
                                <template v-if="picks[cat]?.length">
                                    {{ picks[cat].reduce((acc, p) => acc + (p.credits || 0), 0) || '—' }}
                                </template>
                                <template v-else>—</template>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <!-- Section 4: Family & Address -->
            <section class="px-6 py-4 border-b border-border">
                <h2 class="font-serif text-base text-maroon mb-2">4. Family &amp; Address</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-1.5 text-sm">
                    <div><span class="text-xs text-ink-mute">Father:</span> {{ orDash(student.father_name) }} ({{ orDash(student.father_occupation) }})</div>
                    <div><span class="text-xs text-ink-mute">Mother:</span> {{ orDash(student.mother_name) }} ({{ orDash(student.mother_occupation) }})</div>
                    <div><span class="text-xs text-ink-mute">Annual Family Income:</span> <span class="font-mono">{{ inr(student.annual_family_income) }}</span></div>
                    <div><span class="text-xs text-ink-mute">Emergency Contact:</span> <span class="font-mono">{{ orDash(student.emergency_contact) }}</span></div>
                    <div class="md:col-span-2"><span class="text-xs text-ink-mute">Permanent Address:</span> {{ fullAddress || '—' }}</div>
                </div>
            </section>

            <!-- Section 5: Academic Records -->
            <section class="px-6 py-4 border-b border-border">
                <h2 class="font-serif text-base text-maroon mb-2">5. Academic Records</h2>

                <div v-if="!academic_records.length" class="text-xs text-ink-mute italic py-3 text-center">
                    No academic records on file.
                </div>

                <div v-for="r in academic_records" :key="r.id" class="mb-5 last:mb-0">
                    <!-- Record header (level summary) -->
                    <div class="flex flex-wrap items-baseline gap-x-4 gap-y-1 mb-2 bg-cream px-3 py-2 border border-border rounded-t">
                        <span class="font-semibold font-mono uppercase text-maroon">{{ r.level }}</span>
                        <span class="text-sm">{{ r.board }} <span v-if="r.passing_year" class="text-xs text-ink-mute font-mono">({{ r.passing_year }})</span></span>
                        <span v-if="r.stream" class="text-xs text-ink-mute">· {{ r.stream }}</span>
                        <span v-if="r.school_name" class="text-xs text-ink-mute">· {{ r.school_name }}</span>
                        <span v-if="r.roll_number" class="text-xs text-ink-mute font-mono">· Roll {{ r.roll_number }}</span>
                        <span class="ml-auto text-xs font-mono">
                            <template v-if="r.full_marks">
                                {{ Number(r.obtained_marks || 0) }} / {{ Number(r.full_marks) }}
                            </template>
                            <template v-if="r.percentage">
                                · <strong>{{ Number(r.percentage).toFixed(2) }}%</strong>
                            </template>
                            <template v-if="r.cgpa">
                                · CGPA <strong>{{ r.cgpa }}</strong>
                            </template>
                        </span>
                    </div>

                    <!-- Subject-wise table (12th, UG) -->
                    <table v-if="Array.isArray(r.subjects) && r.subjects.length" class="w-full text-sm border border-border border-t-0">
                        <thead class="bg-white">
                            <tr class="text-[10px] text-ink-mute uppercase tracking-wider border-b border-border">
                                <th class="text-left px-3 py-1.5">Subject</th>
                                <th class="text-left px-3 py-1.5 w-20">Code</th>
                                <th class="text-right px-3 py-1.5 w-20">Theory</th>
                                <th class="text-right px-3 py-1.5 w-24">Practical</th>
                                <th class="text-right px-3 py-1.5 w-24">Obtained</th>
                                <th class="text-right px-3 py-1.5 w-24">Full Marks</th>
                                <th class="text-right px-3 py-1.5 w-16">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(sub, i) in r.subjects" :key="i" class="border-t border-border">
                                <td class="px-3 py-1.5">{{ sub.name }}</td>
                                <td class="px-3 py-1.5 font-mono text-xs text-ink-mute">{{ orDash(sub.code) }}</td>
                                <td class="px-3 py-1.5 text-right font-mono">{{ sub.theory ?? '—' }}</td>
                                <td class="px-3 py-1.5 text-right font-mono">{{ sub.practical ?? '—' }}</td>
                                <td class="px-3 py-1.5 text-right font-mono font-semibold">{{ sub.obtained_marks ?? '—' }}</td>
                                <td class="px-3 py-1.5 text-right font-mono">{{ sub.full_marks ?? '—' }}</td>
                                <td class="px-3 py-1.5 text-right font-mono">{{ sub.percentage != null ? Number(sub.percentage).toFixed(2) : '—' }}</td>
                            </tr>
                        </tbody>
                        <tfoot v-if="r.full_marks" class="bg-cream">
                            <tr class="border-t border-border font-semibold">
                                <td colspan="4" class="px-3 py-1.5 text-right text-xs uppercase tracking-wider text-ink-mute">Aggregate</td>
                                <td class="px-3 py-1.5 text-right font-mono">{{ r.obtained_marks }}</td>
                                <td class="px-3 py-1.5 text-right font-mono">{{ r.full_marks }}</td>
                                <td class="px-3 py-1.5 text-right font-mono">{{ Number(r.percentage).toFixed(2) }}</td>
                            </tr>
                        </tfoot>
                    </table>

                    <!-- 10th or any record without subject breakdown -->
                    <div v-else class="border border-border border-t-0 px-3 py-2 text-xs text-ink-mute italic bg-white">
                        Aggregate-only record. No subject-wise breakdown captured for this qualification.
                    </div>
                </div>
            </section>

            <!-- Section 6: Declarations -->
            <section class="px-6 py-4 border-b border-border">
                <h2 class="font-serif text-base text-maroon mb-2">6. Declarations</h2>
                <ul class="text-sm space-y-1.5">
                    <li>
                        <span class="font-mono" :class="application.declaration_anti_ragging ? 'text-green-700' : 'text-red-600'">
                            {{ application.declaration_anti_ragging ? '✓' : '✗' }}
                        </span>
                        UGC anti-ragging regulations accepted.
                    </li>
                    <li>
                        <span class="font-mono" :class="application.declaration_information_true ? 'text-green-700' : 'text-red-600'">
                            {{ application.declaration_information_true ? '✓' : '✗' }}
                        </span>
                        Information furnished is declared true and correct.
                    </li>
                </ul>
            </section>

            <!-- Section 7: Special request (optional) -->
            <section v-if="application.special_request" class="px-6 py-4 border-b border-border">
                <h2 class="font-serif text-base text-maroon mb-2">7. Special Request</h2>
                <p class="text-sm italic text-ink">"{{ application.special_request }}"</p>
            </section>

            <!-- Section 8: Eligibility verdict -->
            <section v-if="application.eligibility_verdict" class="px-6 py-4 border-b border-border">
                <h2 class="font-serif text-base text-maroon mb-2">8. Eligibility Verdict</h2>
                <p class="text-sm">
                    <span class="text-xs text-ink-mute">Verdict:</span>
                    <span class="ml-1 px-2 py-0.5 rounded text-xs font-mono uppercase" :class="verdictBadge">
                        {{ application.eligibility_verdict }}
                    </span>
                </p>
                <ul v-if="application.eligibility_reasons?.length" class="mt-2 text-xs text-red-600 space-y-0.5">
                    <li v-for="r in application.eligibility_reasons" :key="r">• {{ r }}</li>
                </ul>
                <p class="text-xs text-ink-mute mt-2 italic">
                    Verdict is informational. Final eligibility is confirmed by the admissions committee during document verification.
                </p>
            </section>

            <!-- Signature & footer -->
            <footer class="px-6 py-6">
                <div class="grid grid-cols-2 gap-12 mt-2">
                    <div class="border-t border-border pt-1">
                        <div class="text-xs text-ink-mute">Applicant's Signature</div>
                        <div class="text-[10px] text-ink-mute mt-1">{{ fmtDate(application.submitted_at) }}</div>
                    </div>
                    <div class="border-t border-border pt-1 text-right">
                        <div class="text-xs text-ink-mute">For office use only</div>
                        <div class="text-[10px] text-ink-mute mt-1">Verified by ____________________</div>
                    </div>
                </div>
                <p class="mt-6 text-[10px] text-center text-ink-mute uppercase tracking-wider">
                    This is a system-generated acknowledgement. No physical signature required from the applicant for online verification.
                </p>
            </footer>
        </article>

        <!-- Withdraw modal -->
        <div v-if="showWithdrawModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4 print:hidden" @click.self="showWithdrawModal = false">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
                <header class="px-5 py-3 border-b border-border bg-cream">
                    <h3 class="font-serif text-base text-maroon">Withdraw Application</h3>
                </header>
                <div class="p-5 space-y-3 text-sm">
                    <p>
                        Submitting a withdrawal request for application
                        <strong class="font-mono">{{ application.application_number }}</strong>.
                    </p>
                    <p class="text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded p-2">
                        Once approved by the admissions office, this application will be marked as withdrawn.
                        A refund (if applicable per session policy) will be processed offline within 7-15 working days.
                    </p>
                    <div>
                        <label class="block text-xs font-medium text-ink mb-1">Reason for withdrawal <span class="text-maroon">*</span></label>
                        <textarea v-model="withdrawForm.reason" rows="4" required maxlength="1000"
                            class="w-full px-3 py-2 text-sm border border-border rounded"
                            placeholder="Please give a brief reason (min. 10 characters)"></textarea>
                        <p v-if="withdrawForm.errors.reason" class="text-xs text-red-600 mt-1">{{ withdrawForm.errors.reason }}</p>
                        <p class="text-[10px] text-ink-mute mt-1">{{ withdrawForm.reason.length }} / 1000</p>
                    </div>
                </div>
                <footer class="px-5 py-3 border-t border-border bg-cream flex justify-end gap-2">
                    <button @click="showWithdrawModal = false" class="px-3 py-1.5 text-sm border border-border rounded hover:bg-white">Cancel</button>
                    <Button @click="submitWithdrawal" :loading="withdrawForm.processing">Submit Withdrawal Request</Button>
                </footer>
            </div>
        </div>
    </PortalLayout>
</template>

<style>
@media print {
    /* Hide everything that is not the receipt itself */
    aside, header.bg-navy, .print\:hidden { display: none !important; }
    main { padding: 0 !important; }
    .max-w-7xl { max-width: none !important; }
    body, html { background: white !important; }
    #ack { box-shadow: none !important; border: 1px solid #ccc !important; }
}
</style>
