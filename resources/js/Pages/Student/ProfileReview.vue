<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import Button from '@/Components/Ui/Button.vue';
import { formatDate } from '@/utils/date.js';

const props = defineProps({
    student: { type: Object, required: true },
    status: { type: Object, required: true },
    academic_records: { type: Array, required: true },
});

const sections = [
    { key: 'personal', label: 'Personal Details', href: '/student/profile/personal' },
    { key: 'family', label: 'Family Details', href: '/student/profile/family' },
    { key: 'address', label: 'Address & Contact', href: '/student/profile/address' },
    { key: 'academic', label: 'Academic Records', href: '/student/academic-records' },
    { key: 'other', label: 'Other Details', href: '/student/profile/other' },
    { key: 'uploads', label: 'Documents Uploaded', href: '/student/uploads' },
];

const form = useForm({ declaration_information_true: false });

const submit = () => form.post('/student/profile/submit', { preserveScroll: true });

const inr = (n) => n ? '₹ ' + Number(n).toLocaleString('en-IN') : '—';
const yes = (b) => b ? 'Yes' : 'No';
const orDash = (v) => v ?? '—';
</script>

<template>
    <Head title="Review &amp; Submit Profile" />
    <PortalLayout title="Review &amp; Submit Profile" :breadcrumb="['Student', 'Submit Profile']">
        <p class="text-sm text-ink-mute mb-4">
            Verify each section. Once you submit, your profile will be <strong>locked</strong> —
            you cannot edit any personal/family/address/academic field or re-upload documents
            without contacting admissions. After locking you can apply for programmes.
        </p>

        <!-- Section status -->
        <section class="bg-white border border-border rounded mb-6">
            <header class="px-4 py-2 border-b border-border bg-cream">
                <h2 class="font-serif text-base text-maroon">Section Status — {{ status.percent }}% complete</h2>
            </header>
            <table class="w-full text-sm">
                <tbody>
                    <tr v-for="s in sections" :key="s.key" class="border-t border-border">
                        <td class="px-4 py-2 w-12">
                            <span class="inline-block w-2 h-2 rounded-full"
                                :class="status[s.key] ? 'bg-green-600' : 'bg-amber-500'"></span>
                        </td>
                        <td class="px-4 py-2 font-medium">{{ s.label }}</td>
                        <td class="px-4 py-2">
                            <span v-if="status[s.key]" class="text-xs text-green-700 font-semibold">✓ Complete</span>
                            <span v-else class="text-xs text-amber-700 font-semibold">
                                Pending — {{ (status.missing[s.key] || []).length }} item(s) remaining
                            </span>
                        </td>
                        <td class="px-4 py-2 text-right">
                            <Link :href="s.href" class="text-xs text-maroon hover:underline">
                                {{ status[s.key] ? 'Review →' : 'Complete →' }}
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>

        <!-- Snapshot summary panels -->
        <section class="bg-white border border-border rounded mb-4">
            <header class="px-4 py-2 border-b border-border bg-cream">
                <h2 class="font-serif text-base text-maroon">Personal</h2>
            </header>
            <div class="p-4 grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-2 text-sm">
                <div><span class="text-xs text-ink-mute">Full Name:</span> {{ orDash(student.aadhaar_full_name) }}</div>
                <div><span class="text-xs text-ink-mute">DOB:</span> {{ formatDate(student.dob) }}</div>
                <div><span class="text-xs text-ink-mute">Gender:</span> {{ orDash(student.gender) }}</div>
                <div><span class="text-xs text-ink-mute">Aadhaar:</span> •••• •••• {{ orDash(student.aadhaar_last4) }}</div>
                <div><span class="text-xs text-ink-mute">ABC ID:</span> {{ orDash(student.abc_id) }}</div>
                <div><span class="text-xs text-ink-mute">Category:</span> {{ student.category?.code || '—' }}</div>
                <div><span class="text-xs text-ink-mute">Religion:</span> {{ orDash(student.religion) }}</div>
                <div><span class="text-xs text-ink-mute">Mother Tongue:</span> {{ orDash(student.mother_tongue) }}</div>
                <div><span class="text-xs text-ink-mute">Blood Group:</span> {{ orDash(student.blood_group) }}</div>
            </div>
        </section>

        <section class="bg-white border border-border rounded mb-4">
            <header class="px-4 py-2 border-b border-border bg-cream">
                <h2 class="font-serif text-base text-maroon">Family</h2>
            </header>
            <div class="p-4 grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-2 text-sm">
                <div><span class="text-xs text-ink-mute">Father:</span> {{ orDash(student.father_name) }}</div>
                <div><span class="text-xs text-ink-mute">Father Occupation:</span> {{ orDash(student.father_occupation) }}</div>
                <div><span class="text-xs text-ink-mute">Father Mobile:</span> {{ orDash(student.father_mobile) }}</div>
                <div><span class="text-xs text-ink-mute">Mother:</span> {{ orDash(student.mother_name) }}</div>
                <div><span class="text-xs text-ink-mute">Total Family Income:</span> {{ inr(student.annual_family_income) }}</div>
                <div><span class="text-xs text-ink-mute">Emergency Contact:</span> {{ orDash(student.emergency_contact) }}</div>
                <div><span class="text-xs text-ink-mute">Siblings:</span> {{ orDash(student.siblings_count) }}</div>
                <div><span class="text-xs text-ink-mute">First-Gen Graduate:</span> {{ yes(student.is_first_generation_graduate) }}</div>
                <div><span class="text-xs text-ink-mute">Single Parent:</span> {{ yes(student.is_single_parent) }}</div>
            </div>
        </section>

        <section class="bg-white border border-border rounded mb-4">
            <header class="px-4 py-2 border-b border-border bg-cream">
                <h2 class="font-serif text-base text-maroon">Address</h2>
            </header>
            <div class="p-4 text-sm space-y-1">
                <div>{{ student.house_no }}, {{ student.locality }}, {{ student.taluka || '' }}</div>
                <div>{{ student.district }} — {{ student.pincode }}, {{ student.state }}, {{ student.country }}</div>
                <div class="text-xs text-ink-mute mt-2">Domicile: {{ student.domicile_state }} / {{ student.domicile_district || '—' }}</div>
            </div>
        </section>

        <section class="bg-white border border-border rounded mb-4">
            <header class="px-4 py-2 border-b border-border bg-cream">
                <h2 class="font-serif text-base text-maroon">Academic Records</h2>
            </header>
            <table class="w-full text-sm">
                <thead class="text-xs uppercase text-ink-mute">
                    <tr>
                        <th class="text-left px-4 py-1">Level</th>
                        <th class="text-left px-4 py-1">Board</th>
                        <th class="text-left px-4 py-1">Year</th>
                        <th class="text-right px-4 py-1">%</th>
                        <th class="text-left px-4 py-1">Stream</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="r in academic_records" :key="r.id" class="border-t border-border">
                        <td class="px-4 py-2 font-mono uppercase">{{ r.level }}</td>
                        <td class="px-4 py-2">{{ r.board }}</td>
                        <td class="px-4 py-2 font-mono">{{ r.passing_year }}</td>
                        <td class="px-4 py-2 text-right font-mono">{{ r.percentage }}%</td>
                        <td class="px-4 py-2">{{ r.stream || '—' }}</td>
                    </tr>
                    <tr v-if="!academic_records.length">
                        <td colspan="5" class="px-4 py-3 text-center text-ink-mute text-xs">No academic records yet.</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <!-- Final submit -->
        <section class="bg-white border-2 border-maroon rounded">
            <header class="px-4 py-2 border-b border-border bg-maroon text-white">
                <h2 class="font-serif text-base">Final Declaration &amp; Submit</h2>
            </header>
            <form @submit.prevent="submit" class="p-4 space-y-4">
                <div v-if="!status.all_complete" class="bg-amber-50 border border-amber-300 text-amber-800 text-sm p-3 rounded">
                    ⚠ Please complete every section above before submitting your profile.
                </div>
                <div v-if="status.locked" class="bg-green-50 border border-green-300 text-green-800 text-sm p-3 rounded">
                    🔒 Your profile is already submitted and locked. You can now apply for programmes from
                    <Link href="/student/applications" class="underline">My Applications</Link>.
                </div>

                <label class="flex items-start gap-2 text-sm">
                    <input type="checkbox" v-model="form.declaration_information_true" class="mt-1" :disabled="status.locked" />
                    <span>
                        I hereby declare that all the information provided in this profile is true and correct
                        to the best of my knowledge. I understand that any false declaration may result in
                        cancellation of admission and other consequences as per college rules.
                    </span>
                </label>

                <p v-if="form.errors.declaration_information_true" class="text-xs text-red-600">
                    {{ form.errors.declaration_information_true }}
                </p>

                <Button type="submit"
                    :loading="form.processing"
                    :disabled="!status.all_complete || status.locked || !form.declaration_information_true">
                    {{ status.locked ? 'Profile Locked' : 'Submit Profile (Lock It)' }}
                </Button>
            </form>
        </section>
    </PortalLayout>
</template>
