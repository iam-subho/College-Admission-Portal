<script setup>
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import Button from '@/Components/Ui/Button.vue';
import { ref } from 'vue';
import { formatDateTime } from '@/utils/date.js';

defineProps({
    requests: { type: Object, required: true },
    filter: { type: Object, required: true },
    counts: { type: Object, default: () => ({}) },
});

const TABS = [
    { key: 'pending', label: 'Pending' },
    { key: 'approved', label: 'Approved' },
    { key: 'rejected', label: 'Rejected' },
    { key: 'all', label: 'All' },
];

const setTab = (key) => router.get(route('admin.withdrawals.index'), { status: key }, { preserveState: true });

const approveModal = ref(null);
const rejectModal = ref(null);

const approveForm = useForm({ admin_remark: '' });
const rejectForm = useForm({ admin_remark: '' });

const openApprove = (r) => { approveModal.value = r; approveForm.reset(); };
const openReject = (r) => { rejectModal.value = r; rejectForm.reset(); };

const approve = () => {
    approveForm.post(route('admin.withdrawals.approve', approveModal.value.id), {
        onSuccess: () => { approveModal.value = null; },
        preserveScroll: true,
    });
};
const reject = () => {
    rejectForm.post(route('admin.withdrawals.reject', rejectModal.value.id), {
        onSuccess: () => { rejectModal.value = null; },
        preserveScroll: true,
    });
};

const statusBadge = (s) => ({
    pending: 'bg-amber-100 text-amber-800',
    approved: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
    cancelled: 'bg-gray-100 text-gray-700',
}[s] || 'bg-gray-100');

const inr = (n) => n != null ? '₹' + Number(n).toLocaleString('en-IN', { minimumFractionDigits: 2 }) : '—';
</script>

<template>
    <Head title="Withdrawals" />
    <PortalLayout title="Withdrawal Requests" :breadcrumb="['Admin', 'Withdrawals']">

        <div class="flex flex-wrap gap-2 mb-4 border-b border-border pb-2">
            <button v-for="t in TABS" :key="t.key" @click="setTab(t.key)"
                :class="filter.status === t.key
                    ? 'px-3 py-1.5 text-xs rounded-t bg-maroon text-white border border-maroon border-b-0'
                    : 'px-3 py-1.5 text-xs rounded-t bg-white text-ink border border-border hover:bg-cream'">
                {{ t.label }}
                <span v-if="counts[t.key] !== undefined" class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] font-mono"
                    :class="filter.status === t.key ? 'bg-white/20' : 'bg-cream text-ink-mute'">
                    {{ counts[t.key] }}
                </span>
            </button>
        </div>

        <div class="bg-white border border-border rounded overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-cream text-ink-mute text-xs uppercase tracking-wider">
                    <tr>
                        <th class="text-left px-4 py-2">Application</th>
                        <th class="text-left px-4 py-2">Applicant</th>
                        <th class="text-left px-4 py-2">Programme</th>
                        <th class="text-left px-4 py-2">Reason</th>
                        <th class="text-right px-4 py-2">Refundable</th>
                        <th class="text-left px-4 py-2">Slab</th>
                        <th class="text-left px-4 py-2">Status</th>
                        <th class="text-left px-4 py-2">Requested</th>
                        <th class="text-right px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="r in requests.data" :key="r.id" class="border-t border-border align-top">
                        <td class="px-4 py-2 font-mono text-xs">{{ r.application?.application_number }}</td>
                        <td class="px-4 py-2 text-xs">
                            {{ r.application?.student?.user?.name }}
                            <div class="text-ink-mute">{{ r.application?.student?.user?.email }}</div>
                        </td>
                        <td class="px-4 py-2 text-xs">{{ r.application?.program?.code }} · {{ r.application?.program?.name }}</td>
                        <td class="px-4 py-2 text-xs max-w-[260px]">{{ r.reason }}</td>
                        <td class="px-4 py-2 text-right font-mono">
                            <div>{{ inr(r.estimated_refund) }}</div>
                            <div class="text-[10px] text-red-600">−{{ inr(r.estimated_deduction) }}</div>
                        </td>
                        <td class="px-4 py-2 text-xs">{{ r.estimated_slab || '—' }}</td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-0.5 text-xs rounded" :class="statusBadge(r.status)">{{ r.status }}</span>
                            <div v-if="r.admin_remark" class="text-[10px] text-ink-mute mt-1 italic max-w-[200px]">{{ r.admin_remark }}</div>
                        </td>
                        <td class="px-4 py-2 text-xs">{{ formatDateTime(r.requested_at) }}</td>
                        <td class="px-4 py-2 text-right space-x-1 whitespace-nowrap">
                            <template v-if="r.status === 'pending'">
                                <button @click="openApprove(r)"
                                    class="text-xs px-2 py-1 bg-green-600 text-white rounded hover:bg-green-700">
                                    Approve
                                </button>
                                <button @click="openReject(r)"
                                    class="text-xs px-2 py-1 border border-red-300 text-red-600 rounded hover:bg-red-50">
                                    Reject
                                </button>
                            </template>
                            <Link :href="`/admin/applications/${r.application_id}`" class="text-xs text-maroon hover:underline">
                                View
                            </Link>
                        </td>
                    </tr>
                    <tr v-if="!requests.data.length">
                        <td colspan="9" class="px-4 py-6 text-center text-ink-mute text-sm">No withdrawal requests in this view.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Approve modal -->
        <div v-if="approveModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" @click.self="approveModal = null">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
                <header class="px-5 py-3 border-b border-border bg-cream">
                    <h3 class="font-serif text-base text-maroon">Approve Withdrawal</h3>
                </header>
                <div class="p-5 space-y-3 text-sm">
                    <p>
                        Approving will mark application
                        <strong class="font-mono">{{ approveModal.application?.application_number }}</strong>
                        as <strong>withdrawn</strong>.
                    </p>
                    <p class="text-xs">
                        Estimated refund: <strong class="font-mono">{{ inr(approveModal.estimated_refund) }}</strong>
                        (deduction <span class="text-red-600">{{ inr(approveModal.estimated_deduction) }}</span>) ·
                        Slab: {{ approveModal.estimated_slab }}
                    </p>
                    <div>
                        <label class="block text-xs font-medium text-ink mb-1">Admin remark (optional)</label>
                        <textarea v-model="approveForm.admin_remark" rows="3"
                            class="w-full px-3 py-2 text-sm border border-border rounded"
                            placeholder="Internal note for the audit trail"></textarea>
                    </div>
                </div>
                <footer class="px-5 py-3 border-t border-border bg-cream flex justify-end gap-2">
                    <button @click="approveModal = null" class="px-3 py-1.5 text-sm border border-border rounded hover:bg-white">Cancel</button>
                    <Button @click="approve" :loading="approveForm.processing">Approve &amp; Create Refund</Button>
                </footer>
            </div>
        </div>

        <!-- Reject modal -->
        <div v-if="rejectModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" @click.self="rejectModal = null">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
                <header class="px-5 py-3 border-b border-border bg-cream">
                    <h3 class="font-serif text-base text-maroon">Reject Withdrawal</h3>
                </header>
                <div class="p-5 space-y-3 text-sm">
                    <p>The student will see the reason for rejection in their portal.</p>
                    <div>
                        <label class="block text-xs font-medium text-ink mb-1">Reason for rejection <span class="text-maroon">*</span></label>
                        <textarea v-model="rejectForm.admin_remark" rows="3" required
                            class="w-full px-3 py-2 text-sm border border-border rounded"
                            placeholder="e.g. Withdrawal window has passed"></textarea>
                        <p v-if="rejectForm.errors.admin_remark" class="text-xs text-red-600 mt-1">{{ rejectForm.errors.admin_remark }}</p>
                    </div>
                </div>
                <footer class="px-5 py-3 border-t border-border bg-cream flex justify-end gap-2">
                    <button @click="rejectModal = null" class="px-3 py-1.5 text-sm border border-border rounded hover:bg-white">Cancel</button>
                    <Button @click="reject" :loading="rejectForm.processing">Reject Request</Button>
                </footer>
            </div>
        </div>
    </PortalLayout>
</template>
