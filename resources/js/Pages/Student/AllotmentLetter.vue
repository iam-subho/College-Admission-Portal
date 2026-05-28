<script setup>
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import Button from '@/Components/Ui/Button.vue';
import { ref, computed } from 'vue';
import { formatDateTime, formatDate } from '@/utils/date.js';

const props = defineProps({
    application: { type: Object, required: true },
    allocation: { type: Object, default: null },
    admission_fee: { type: Object, default: () => ({ amount: 0, source: 'unset' }) },
    can_accept: { type: Boolean, default: false },
});

const page = usePage();
const flashError = computed(() => page.props?.flash?.error || null);

const showDecline = ref(false);
const declineForm = useForm({ reason: '' });

const accept = () => {
    if (!confirm('Accept this seat? You will then need to pay the admission fee within the acceptance window to confirm your admission.')) return;
    router.post(route('student.allotment.accept', props.allocation.id), {}, { preserveScroll: true });
};

const decline = () => declineForm.post(route('student.allotment.decline', props.allocation.id), {
    onSuccess: () => { showDecline.value = false; declineForm.reset(); },
});

const inr = (n) => '₹' + Number(n || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');

const statusBadge = computed(() => ({
    allotted: { label: 'Allotted · Awaiting Your Response', cls: 'bg-amber-100 text-amber-800' },
    accepted: { label: 'Accepted · Admission Fee Pending', cls: 'bg-blue-100 text-blue-800' },
    admitted: { label: '✓ Admission Confirmed', cls: 'bg-green-100 text-green-800' },
    declined: { label: 'Declined', cls: 'bg-red-100 text-red-800' },
    expired: { label: 'Window Expired', cls: 'bg-gray-200 text-gray-700' },
    withdrawn: { label: 'Withdrawn', cls: 'bg-amber-200 text-amber-900' },
}[props.allocation?.status] || { label: 'No allotment', cls: 'bg-gray-100' }));

const showAcceptUI = computed(() => props.can_accept && props.allocation?.status === 'allotted');
const showPayUI = computed(() => props.allocation?.status === 'accepted');
const showSuccess = computed(() => props.allocation?.status === 'admitted');
</script>

<template>
    <Head :title="`Allotment — ${application.application_number}`" />
    <PortalLayout title="Seat Allotment"
        :breadcrumb="['Student', 'Applications', application.application_number, 'Allotment']">

        <div v-if="flashError" class="mb-4 p-3 bg-red-50 border border-red-300 text-red-800 text-sm rounded">
            {{ flashError }}
        </div>

        <div class="mb-4 text-sm text-ink-mute">
            <span class="font-mono">{{ application.application_number }}</span> ·
            {{ application.program?.code }} {{ application.program?.name }} ·
            Session <strong>{{ application.session?.code }}</strong>
        </div>

        <!-- No allotment yet -->
        <div v-if="!allocation" class="bg-amber-50 border border-amber-300 rounded p-5">
            <h2 class="font-serif text-base text-amber-900 mb-1">No seat allotment yet</h2>
            <p class="text-sm text-amber-800">
                Once the admissions office generates seat allotments from the merit list, your offer (if any) will appear here.
                Watch the merit list page for your rank and the cutoff.
            </p>
        </div>

        <!-- Allotment present -->
        <article v-else class="bg-white border-2 rounded shadow-sm overflow-hidden" :class="showSuccess ? 'border-green-600' : 'border-maroon'">
            <header class="px-6 py-4 flex items-center justify-between"
                :class="showSuccess ? 'bg-green-600 text-white' : 'bg-maroon text-white'">
                <div>
                    <h2 class="font-serif text-lg">Seat Allotment Letter</h2>
                    <p class="text-xs opacity-90 mt-0.5">Allotment #{{ allocation.id }} · {{ allocation.source }} allotment</p>
                </div>
                <span class="px-3 py-0.5 text-xs uppercase font-mono rounded" :class="statusBadge.cls">
                    {{ statusBadge.label }}
                </span>
            </header>

            <div class="p-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-x-6 gap-y-3 text-sm mb-6">
                    <div>
                        <div class="text-[10px] uppercase tracking-wider text-ink-mute">Programme</div>
                        <div class="font-medium">{{ allocation.round?.program?.code }}</div>
                        <div class="text-xs text-ink-mute">{{ allocation.round?.program?.name }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] uppercase tracking-wider text-ink-mute">Round</div>
                        <div class="font-medium">{{ allocation.round?.name || ('Round ' + allocation.round?.round_number) }}</div>
                    </div>
                    <div v-if="allocation.category">
                        <div class="text-[10px] uppercase tracking-wider text-ink-mute">Category</div>
                        <div class="font-mono">{{ allocation.category.code }}</div>
                    </div>
                    <div v-if="allocation.rank_at_allotment">
                        <div class="text-[10px] uppercase tracking-wider text-ink-mute">Rank at Allotment</div>
                        <div class="font-mono">{{ allocation.rank_at_allotment }}<span v-if="allocation.category_rank_at_allotment" class="text-xs text-ink-mute"> (cat {{ allocation.category_rank_at_allotment }})</span></div>
                    </div>
                    <div>
                        <div class="text-[10px] uppercase tracking-wider text-ink-mute">Allotted On</div>
                        <div>{{ formatDateTime(allocation.allotted_at) }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] uppercase tracking-wider text-ink-mute">Accept By</div>
                        <div class="font-medium" :class="showAcceptUI ? 'text-red-600' : ''">{{ formatDateTime(allocation.expires_at) }}</div>
                    </div>
                </div>

                <!-- Accept/Decline panel -->
                <div v-if="showAcceptUI" class="border-2 border-amber-300 rounded p-4 bg-amber-50 mb-4">
                    <h3 class="font-serif text-base text-amber-900 mb-2">Your Response Required</h3>
                    <p class="text-sm text-amber-800 mb-3">
                        Accept this seat to lock in your admission, then pay the admission fee within the acceptance window.
                        Declining frees the seat for the next waitlisted candidate immediately.
                    </p>
                    <div class="flex gap-2 flex-wrap">
                        <Button @click="accept">Accept Seat</Button>
                        <button @click="showDecline = true"
                            class="px-3 py-2 text-sm border border-red-400 text-red-700 rounded hover:bg-red-50">
                            Decline Seat
                        </button>
                    </div>
                </div>

                <!-- Admission fee pending -->
                <div v-if="showPayUI" class="border-2 border-blue-300 rounded p-4 bg-blue-50 mb-4">
                    <h3 class="font-serif text-base text-blue-900 mb-1">Admission Fee Due</h3>
                    <p class="text-sm text-blue-800 mb-3">
                        Seat accepted. Pay the admission fee of <strong class="font-mono">{{ inr(admission_fee.amount) }}</strong>
                        before <strong>{{ formatDateTime(allocation.expires_at) }}</strong> to confirm your admission.
                    </p>
                    <a :href="`/student/allotment/${allocation.id}/admission-fee`"
                        class="inline-block px-5 py-2 bg-blue-700 text-white text-sm font-semibold rounded hover:bg-blue-800">
                        Pay Admission Fee →
                    </a>
                </div>

                <!-- Success -->
                <div v-if="showSuccess" class="border-2 border-green-600 rounded p-4 bg-green-50">
                    <h3 class="font-serif text-base text-green-900 mb-1">✓ Admission Confirmed</h3>
                    <p class="text-sm text-green-800">
                        Admission fee paid on {{ formatDateTime(allocation.admitted_at) }}. Welcome to SVNC.
                    </p>
                </div>
            </div>
        </article>

        <!-- Decline modal -->
        <div v-if="showDecline" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" @click.self="showDecline = false">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
                <header class="px-5 py-3 border-b border-border bg-cream">
                    <h3 class="font-serif text-base text-maroon">Decline Seat</h3>
                </header>
                <div class="p-5 space-y-3 text-sm">
                    <p class="text-amber-800 bg-amber-50 border border-amber-200 rounded p-2 text-xs">
                        Declining is final. The seat will be offered to the next waitlisted candidate immediately.
                    </p>
                    <div>
                        <label class="block text-xs font-medium text-ink mb-1">Reason for declining <span class="text-maroon">*</span></label>
                        <textarea v-model="declineForm.reason" rows="3" required
                            class="w-full px-3 py-2 text-sm border border-border rounded"
                            placeholder="e.g. accepted offer at another college"></textarea>
                        <p v-if="declineForm.errors.reason" class="text-xs text-red-600 mt-1">{{ declineForm.errors.reason }}</p>
                    </div>
                </div>
                <footer class="px-5 py-3 border-t border-border bg-cream flex justify-end gap-2">
                    <button @click="showDecline = false" class="px-3 py-1.5 text-sm border border-border rounded hover:bg-white">Cancel</button>
                    <Button @click="decline" :loading="declineForm.processing">Confirm Decline</Button>
                </footer>
            </div>
        </div>

        <div class="mt-4">
            <Link :href="`/student/applications/${application.id}`" class="text-xs text-maroon hover:underline">← Back to application</Link>
        </div>
    </PortalLayout>
</template>
