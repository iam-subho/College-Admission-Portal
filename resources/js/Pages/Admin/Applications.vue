<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import InputText from '@/Components/Ui/InputText.vue';
import { ref, watch } from 'vue';

const props = defineProps({
    applications: { type: Object, required: true },
    filters: { type: Object, required: true },
    counts: { type: Object, default: () => ({}) },
    active_session: { type: Object, default: null },
});

const q = ref(props.filters.q || '');
const view = ref(props.filters.view || 'ready');
const verdict = ref(props.filters.verdict || '');

const apply = () => router.get('/admin/applications', {
    q: q.value, view: view.value, verdict: verdict.value,
}, { preserveState: true });

let timer = null;
watch([q, verdict], () => {
    clearTimeout(timer);
    timer = setTimeout(apply, 350);
});
watch(view, apply);

const VIEWS = [
    { key: 'ready', label: 'Ready for Verification', hint: 'Submitted + paid' },
    { key: 'awaiting_payment', label: 'Awaiting Payment', hint: 'Submitted, fee not paid' },
    { key: 'draft', label: 'Draft', hint: 'Student still editing' },
    { key: 'verified', label: 'Verified', hint: 'Approved by admin' },
    { key: 'rejected', label: 'Rejected', hint: 'Rejected by admin' },
    { key: 'all', label: 'All', hint: 'No filter' },
];

const statusBadge = (a) => {
    if (a.status === 'submitted' && a.payment_status === 'pending') {
        return { label: 'Awaiting Payment', cls: 'bg-amber-100 text-amber-800' };
    }
    return {
        draft: { label: 'Draft', cls: 'bg-gray-100 text-gray-700' },
        submitted: { label: 'Submitted', cls: 'bg-blue-100 text-blue-800' },
        verified: { label: 'Verified', cls: 'bg-green-100 text-green-800' },
        rejected: { label: 'Rejected', cls: 'bg-red-100 text-red-800' },
        withdrawn: { label: 'Withdrawn', cls: 'bg-amber-100 text-amber-800' },
    }[a.status] || { label: a.status, cls: 'bg-gray-100 text-gray-700' };
};

const paymentBadge = (ps) => ({
    pending: { label: 'Pending', cls: 'bg-amber-100 text-amber-800' },
    paid: { label: 'Paid', cls: 'bg-green-100 text-green-800' },
    covered: { label: 'Covered', cls: 'bg-green-50 text-green-700' },
    not_required: { label: 'N/A', cls: 'bg-gray-100 text-gray-700' },
}[ps] || { label: ps, cls: 'bg-gray-100' });
</script>

<template>
    <Head title="Applications" />
    <PortalLayout title="Applications" :breadcrumb="['Admin', 'Applications']">
        <p v-if="active_session" class="text-sm text-ink-mute mb-4">
            Active session: <strong>{{ active_session.code }}</strong>
        </p>

        <!-- View tabs -->
        <div class="flex flex-wrap gap-2 mb-4 border-b border-border pb-2">
            <button
                v-for="v in VIEWS"
                :key="v.key"
                @click="view = v.key"
                :title="v.hint"
                :class="view === v.key
                    ? 'px-3 py-1.5 text-xs rounded-t bg-maroon text-white border border-maroon border-b-0'
                    : 'px-3 py-1.5 text-xs rounded-t bg-white text-ink border border-border hover:bg-cream'"
            >
                {{ v.label }}
                <span v-if="counts[v.key] !== undefined" class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] font-mono"
                    :class="view === v.key ? 'bg-white/20' : 'bg-cream text-ink-mute'">
                    {{ counts[v.key] }}
                </span>
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
            <InputText v-model="q" label="Search (number / name / email)" />
            <div>
                <label class="block text-xs font-medium text-ink mb-1">Eligibility</label>
                <select v-model="verdict" class="w-full px-3 py-2 text-sm border border-border rounded">
                    <option value="">All</option>
                    <option>pending</option><option>pass</option><option>fail</option>
                    <option>override_pass</option><option>override_fail</option>
                </select>
            </div>
        </div>

        <div class="bg-white border border-border rounded overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-cream text-ink-mute text-xs uppercase tracking-wider">
                    <tr>
                        <th class="text-left px-4 py-2">Number</th>
                        <th class="text-left px-4 py-2">Applicant</th>
                        <th class="text-left px-4 py-2">Programme</th>
                        <th class="text-left px-4 py-2">Status</th>
                        <th class="text-left px-4 py-2">Payment</th>
                        <th class="text-left px-4 py-2">Eligibility</th>
                        <th class="text-right px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="a in applications.data" :key="a.id" class="border-t border-border">
                        <td class="px-4 py-2 font-mono text-xs">{{ a.application_number || '—' }}</td>
                        <td class="px-4 py-2">
                            {{ a.student?.user?.name }}
                            <div class="text-xs text-ink-mute">{{ a.student?.user?.email }}</div>
                        </td>
                        <td class="px-4 py-2 text-xs">{{ a.program?.code }} · {{ a.program?.name }}</td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-0.5 text-xs rounded" :class="statusBadge(a).cls">
                                {{ statusBadge(a).label }}
                            </span>
                        </td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-0.5 text-xs rounded" :class="paymentBadge(a.payment_status).cls">
                                {{ paymentBadge(a.payment_status).label }}
                            </span>
                        </td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-0.5 text-xs rounded"
                                :class="['pass', 'override_pass'].includes(a.eligibility_verdict) ? 'bg-green-100 text-green-800' : (['fail', 'override_fail'].includes(a.eligibility_verdict) ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-700')">
                                {{ a.eligibility_verdict }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-right">
                            <Link :href="`/admin/applications/${a.id}`" class="text-xs text-maroon hover:underline">Review →</Link>
                        </td>
                    </tr>
                    <tr v-if="!applications.data.length">
                        <td colspan="7" class="px-4 py-6 text-center text-ink-mute text-sm">No applications match.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </PortalLayout>
</template>
