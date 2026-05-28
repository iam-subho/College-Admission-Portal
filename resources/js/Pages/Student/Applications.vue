<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import Button from '@/Components/Ui/Button.vue';

const props = defineProps({
    applications: { type: Array, required: true },
    programmes: { type: Array, required: true },
    active_session: { type: Object, default: null },
    profile_locked: { type: Boolean, default: false },
});

const form = useForm({ program_id: '' });
const start = () => form.post('/student/applications');

const statusBadge = (a) => {
    if (a.status === 'submitted' && a.payment_status === 'pending') {
        return { label: 'Awaiting Payment', cls: 'bg-amber-100 text-amber-800 border border-amber-300' };
    }
    return {
        draft: { label: 'Draft', cls: 'bg-gray-100 text-gray-700' },
        submitted: { label: 'Submitted', cls: 'bg-blue-100 text-blue-800' },
        verified: { label: 'Verified', cls: 'bg-green-100 text-green-800' },
        rejected: { label: 'Rejected', cls: 'bg-red-100 text-red-800' },
        withdrawn: { label: 'Withdrawn', cls: 'bg-amber-100 text-amber-800' },
    }[a.status] || { label: a.status, cls: 'bg-gray-100 text-gray-700' };
};

const needsPayment = (a) => a.status === 'submitted' && a.payment_status === 'pending';
</script>

<template>
    <Head title="My Applications" />
    <PortalLayout title="My Applications" :breadcrumb="['Student', 'Applications']">
        <div v-if="!profile_locked" class="bg-amber-50 border border-amber-300 text-amber-800 text-sm p-4 mb-6 rounded">
            ⚠ Your profile is not yet submitted. Please complete and lock your profile from
            <Link href="/student/profile/review" class="underline font-semibold">Submit Profile</Link>
            before you can apply for any programme.
        </div>

        <div v-else-if="active_session" class="bg-white border border-border rounded p-4 mb-6">
            <h2 class="font-serif text-lg text-maroon mb-2">Start a New Application</h2>
            <p class="text-xs text-ink-mute mb-3">
                Active session: <strong>{{ active_session.code }}</strong>.
            </p>
            <form @submit.prevent="start" class="flex gap-3 items-end">
                <div class="flex-1">
                    <label class="block text-xs font-medium text-ink mb-1">Programme</label>
                    <select v-model="form.program_id" class="w-full px-3 py-2 text-sm border border-border rounded">
                        <option value="">— select a programme —</option>
                        <option v-for="p in programmes" :key="p.id" :value="p.id">
                            {{ p.code }} · {{ p.name }} ({{ p.type }})
                        </option>
                    </select>
                </div>
                <Button type="submit" :loading="form.processing" :disabled="!form.program_id">Start Application</Button>
            </form>
        </div>

        <div class="bg-white border border-border rounded overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-cream text-ink-mute text-xs uppercase tracking-wider">
                    <tr>
                        <th class="text-left px-4 py-2">Number</th>
                        <th class="text-left px-4 py-2">Programme</th>
                        <th class="text-left px-4 py-2">Session</th>
                        <th class="text-left px-4 py-2">Status</th>
                        <th class="text-left px-4 py-2">Eligibility</th>
                        <th class="text-right px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="a in applications" :key="a.id" class="border-t border-border">
                        <td class="px-4 py-2 font-mono text-xs">{{ a.application_number || '—' }}</td>
                        <td class="px-4 py-2">{{ a.program?.code }} · {{ a.program?.name }}</td>
                        <td class="px-4 py-2">{{ a.session?.code }}</td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-0.5 text-xs rounded" :class="statusBadge(a).cls">
                                {{ statusBadge(a).label }}
                            </span>
                        </td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-0.5 text-xs rounded"
                                :class="{
                                    'bg-gray-100': a.eligibility_verdict === 'pending',
                                    'bg-green-100 text-green-800': ['pass', 'override_pass'].includes(a.eligibility_verdict),
                                    'bg-red-100 text-red-800': ['fail', 'override_fail'].includes(a.eligibility_verdict),
                                }">
                                {{ a.eligibility_verdict }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-right space-x-2 whitespace-nowrap">
                            <Link
                                v-if="needsPayment(a)"
                                :href="`/student/applications/${a.id}/payment`"
                                class="text-xs px-3 py-1 bg-amber-500 text-white rounded hover:bg-amber-600 font-semibold"
                            >
                                Pay Now →
                            </Link>
                            <Link
                                v-if="a.status !== 'draft' && !needsPayment(a)"
                                :href="`/student/admit-card/${a.id}`"
                                class="text-xs text-saffron hover:underline"
                            >
                                Admit Card
                            </Link>
                            <Link
                                v-if="a.status !== 'draft' && !needsPayment(a)"
                                :href="`/student/applications/${a.id}/merit`"
                                class="text-xs text-navy hover:underline"
                            >
                                Merit Result
                            </Link>
                            <Link
                                v-if="a.status !== 'draft' && !needsPayment(a)"
                                :href="`/student/allotment/${a.id}`"
                                class="text-xs text-green-700 hover:underline"
                            >
                                Allotment
                            </Link>
                            <Link :href="`/student/applications/${a.id}`" class="text-xs text-maroon hover:underline">
                                {{ a.status === 'draft' ? 'Continue →' : 'View →' }}
                            </Link>
                        </td>
                    </tr>
                    <tr v-if="!applications.length">
                        <td colspan="6" class="px-4 py-6 text-center text-ink-mute text-sm">
                            No applications yet. Start one above.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </PortalLayout>
</template>
