<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import Button from '@/Components/Ui/Button.vue';
import { ref } from 'vue';
import { formatDateTime } from '@/utils/date.js';

defineProps({
    refunds: { type: Object, required: true },
    filter: { type: Object, required: true },
    counts: { type: Object, default: () => ({}) },
});

const TABS = [
    { key: 'pending', label: 'Pending' },
    { key: 'completed', label: 'Completed' },
    { key: 'failed', label: 'Failed' },
    { key: 'all', label: 'All' },
];

const setTab = (key) => router.get(route('admin.refunds.index'), { status: key }, { preserveState: true });

const paidModal = ref(null);
const failModal = ref(null);
const paidForm = useForm({ offline_reference: '' });
const failForm = useForm({ failure_reason: '' });

const openPaid = (r) => { paidModal.value = r; paidForm.reset(); };
const openFail = (r) => { failModal.value = r; failForm.reset(); };

const markPaid = () => paidForm.post(route('admin.refunds.mark-paid', paidModal.value.id), {
    onSuccess: () => { paidModal.value = null; },
    preserveScroll: true,
});
const markFailed = () => failForm.post(route('admin.refunds.mark-failed', failModal.value.id), {
    onSuccess: () => { failModal.value = null; },
    preserveScroll: true,
});

const statusBadge = (s) => ({
    pending: 'bg-amber-100 text-amber-800',
    initiated: 'bg-blue-100 text-blue-800',
    processing: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    failed: 'bg-red-100 text-red-800',
}[s] || 'bg-gray-100');

const inr = (n) => n != null ? '₹' + Number(n).toLocaleString('en-IN', { minimumFractionDigits: 2 }) : '—';
</script>

<template>
    <Head title="Refunds" />
    <PortalLayout title="Refunds" :breadcrumb="['Admin', 'Refunds']">

        <p class="text-xs text-ink-mute mb-3">
            Refund mode is <strong>offline</strong>. Process the bank transfer (NEFT/IMPS) manually, then mark the record as paid with the UTR reference.
        </p>

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
                        <th class="text-left px-4 py-2">Order</th>
                        <th class="text-right px-4 py-2">Refund Amount</th>
                        <th class="text-right px-4 py-2">Deduction</th>
                        <th class="text-left px-4 py-2">Slab</th>
                        <th class="text-left px-4 py-2">Status</th>
                        <th class="text-left px-4 py-2">UTR / Ref</th>
                        <th class="text-right px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="r in refunds.data" :key="r.id" class="border-t border-border align-top">
                        <td class="px-4 py-2 font-mono text-xs">{{ r.application?.application_number }}</td>
                        <td class="px-4 py-2 text-xs">
                            {{ r.application?.student?.user?.name }}
                            <div class="text-ink-mute">{{ r.application?.student?.user?.email }}</div>
                            <div v-if="r.application?.student?.user?.mobile" class="text-ink-mute">{{ r.application.student.user.mobile }}</div>
                        </td>
                        <td class="px-4 py-2 font-mono text-xs">
                            {{ r.order?.order_number || '—' }}
                            <div class="text-ink-mute">Paid {{ inr(r.order?.total) }}</div>
                        </td>
                        <td class="px-4 py-2 text-right font-mono">{{ inr(r.amount) }}</td>
                        <td class="px-4 py-2 text-right font-mono text-red-600">{{ inr(r.deduction_amount) }}</td>
                        <td class="px-4 py-2 text-xs">{{ r.policy_slab }}</td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-0.5 text-xs rounded" :class="statusBadge(r.status)">{{ r.status }}</span>
                            <div class="text-[10px] text-ink-mute mt-1">{{ r.refund_method }}</div>
                        </td>
                        <td class="px-4 py-2 font-mono text-xs">
                            <template v-if="r.offline_reference">
                                {{ r.offline_reference }}
                                <div class="text-ink-mute">{{ formatDateTime(r.completed_at) }}</div>
                            </template>
                            <span v-else class="text-ink-mute">—</span>
                        </td>
                        <td class="px-4 py-2 text-right space-x-1 whitespace-nowrap">
                            <template v-if="r.status === 'pending'">
                                <button @click="openPaid(r)"
                                    class="text-xs px-2 py-1 bg-green-600 text-white rounded hover:bg-green-700">
                                    Mark Paid
                                </button>
                                <button @click="openFail(r)"
                                    class="text-xs px-2 py-1 border border-red-300 text-red-600 rounded hover:bg-red-50">
                                    Mark Failed
                                </button>
                            </template>
                        </td>
                    </tr>
                    <tr v-if="!refunds.data.length">
                        <td colspan="9" class="px-4 py-6 text-center text-ink-mute text-sm">No refunds in this view.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Mark paid modal -->
        <div v-if="paidModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" @click.self="paidModal = null">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
                <header class="px-5 py-3 border-b border-border bg-cream">
                    <h3 class="font-serif text-base text-maroon">Mark Refund Paid</h3>
                </header>
                <div class="p-5 space-y-3 text-sm">
                    <p>
                        Refund of <strong class="font-mono">{{ inr(paidModal.amount) }}</strong> to
                        <strong>{{ paidModal.application?.student?.user?.name }}</strong> for application
                        <strong class="font-mono">{{ paidModal.application?.application_number }}</strong>.
                    </p>
                    <div>
                        <label class="block text-xs font-medium text-ink mb-1">Bank UTR / Reference number <span class="text-maroon">*</span></label>
                        <input v-model="paidForm.offline_reference" type="text" required
                            class="w-full px-3 py-2 text-sm border border-border rounded font-mono"
                            placeholder="e.g. NEFT-2026-05-28-123456" />
                        <p v-if="paidForm.errors.offline_reference" class="text-xs text-red-600 mt-1">{{ paidForm.errors.offline_reference }}</p>
                    </div>
                </div>
                <footer class="px-5 py-3 border-t border-border bg-cream flex justify-end gap-2">
                    <button @click="paidModal = null" class="px-3 py-1.5 text-sm border border-border rounded hover:bg-white">Cancel</button>
                    <Button @click="markPaid" :loading="paidForm.processing">Confirm Paid</Button>
                </footer>
            </div>
        </div>

        <!-- Mark failed modal -->
        <div v-if="failModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" @click.self="failModal = null">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
                <header class="px-5 py-3 border-b border-border bg-cream">
                    <h3 class="font-serif text-base text-maroon">Mark Refund Failed</h3>
                </header>
                <div class="p-5 space-y-3 text-sm">
                    <div>
                        <label class="block text-xs font-medium text-ink mb-1">Reason for failure <span class="text-maroon">*</span></label>
                        <textarea v-model="failForm.failure_reason" rows="3" required
                            class="w-full px-3 py-2 text-sm border border-border rounded"
                            placeholder="e.g. Bank account closed, NEFT bounced"></textarea>
                    </div>
                </div>
                <footer class="px-5 py-3 border-t border-border bg-cream flex justify-end gap-2">
                    <button @click="failModal = null" class="px-3 py-1.5 text-sm border border-border rounded hover:bg-white">Cancel</button>
                    <Button @click="markFailed" :loading="failForm.processing">Mark Failed</Button>
                </footer>
            </div>
        </div>
    </PortalLayout>
</template>
