<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import InputText from '@/Components/Ui/InputText.vue';
import Button from '@/Components/Ui/Button.vue';
import SectionStatusBanner from '@/Components/Ui/SectionStatusBanner.vue';

const props = defineProps({
    student: { type: Object, required: true },
});

const QUALIFICATIONS = ['Below 10th', '10th', '12th', 'Diploma', 'Graduate', 'Post-Graduate', 'Doctorate', 'Other'];

const form = useForm({
    father_name: props.student.father_name || '',
    father_occupation: props.student.father_occupation || '',
    father_qualification: props.student.father_qualification || '',
    father_income: props.student.father_income || '',
    father_mobile: props.student.father_mobile || '',
    father_email: props.student.father_email || '',

    mother_name: props.student.mother_name || '',
    mother_occupation: props.student.mother_occupation || '',
    mother_qualification: props.student.mother_qualification || '',
    mother_income: props.student.mother_income || '',
    mother_mobile: props.student.mother_mobile || '',
    mother_email: props.student.mother_email || '',

    guardian_mobile: props.student.guardian_mobile || '',
    annual_family_income: props.student.annual_family_income || '',
    siblings_count: props.student.siblings_count ?? '',
    family_in_govt_service: !!props.student.family_in_govt_service,
    is_single_parent: !!props.student.is_single_parent,
    is_first_generation_graduate: !!props.student.is_first_generation_graduate,
    emergency_contact: props.student.emergency_contact || '',
});

const submit = (next) => {
    form.post('/student/profile/family', {
        preserveScroll: true,
        onSuccess: () => { if (next) router.visit('/student/profile/address'); },
    });
};
</script>

<template>
    <Head title="Family Details" />
    <PortalLayout title="Family Details" :breadcrumb="['Student', 'Family Details']">
        <p class="text-sm text-ink-mute mb-4">Information about parents / guardian and family.</p>

        <SectionStatusBanner section="family" />

        <form @submit.prevent="submit(true)" class="space-y-6">
            <!-- Father -->
            <section class="bg-white border border-border rounded">
                <header class="px-4 py-2 border-b border-border bg-cream">
                    <h2 class="font-serif text-base text-maroon">Father's Details</h2>
                </header>
                <div class="p-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <InputText v-model="form.father_name" label="Name" required :error="form.errors.father_name" />
                    <InputText v-model="form.father_occupation" label="Occupation" required :error="form.errors.father_occupation" />
                    <InputText v-model="form.father_income" type="number" label="Annual Income (₹)" :error="form.errors.father_income" />
                    <InputText v-model="form.father_mobile" label="Mobile" prefix="+91" maxlength="10" required :error="form.errors.father_mobile" />
                    <InputText v-model="form.father_email" type="email" label="Email" :error="form.errors.father_email" />
                    <div>
                        <label class="block text-xs font-medium text-ink mb-1">Qualification</label>
                        <select v-model="form.father_qualification" class="w-full px-3 py-2 text-sm border border-border rounded">
                            <option value="">—</option>
                            <option v-for="q in QUALIFICATIONS" :key="q" :value="q">{{ q }}</option>
                        </select>
                    </div>
                </div>
            </section>

            <!-- Mother -->
            <section class="bg-white border border-border rounded">
                <header class="px-4 py-2 border-b border-border bg-cream">
                    <h2 class="font-serif text-base text-maroon">Mother's Details</h2>
                </header>
                <div class="p-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <InputText v-model="form.mother_name" label="Name" required :error="form.errors.mother_name" />
                    <InputText v-model="form.mother_occupation" label="Occupation" :error="form.errors.mother_occupation" />
                    <InputText v-model="form.mother_income" type="number" label="Annual Income (₹)" :error="form.errors.mother_income" />
                    <InputText v-model="form.mother_mobile" label="Mobile" prefix="+91" maxlength="10" :error="form.errors.mother_mobile" />
                    <InputText v-model="form.mother_email" type="email" label="Email" :error="form.errors.mother_email" />
                    <div>
                        <label class="block text-xs font-medium text-ink mb-1">Qualification</label>
                        <select v-model="form.mother_qualification" class="w-full px-3 py-2 text-sm border border-border rounded">
                            <option value="">—</option>
                            <option v-for="q in QUALIFICATIONS" :key="q" :value="q">{{ q }}</option>
                        </select>
                    </div>
                </div>
            </section>

            <!-- Other -->
            <section class="bg-white border border-border rounded">
                <header class="px-4 py-2 border-b border-border bg-cream">
                    <h2 class="font-serif text-base text-maroon">Other Information</h2>
                </header>
                <div class="p-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <InputText v-model="form.annual_family_income" type="number" label="Total Annual Family Income (₹)" required :error="form.errors.annual_family_income" />
                    <InputText v-model="form.siblings_count" type="number" label="Number of Siblings" :error="form.errors.siblings_count" />
                    <InputText v-model="form.guardian_mobile" label="Guardian Mobile" prefix="+91" maxlength="10" :error="form.errors.guardian_mobile" />
                    <InputText v-model="form.emergency_contact" label="Emergency Contact" prefix="+91" maxlength="10" required :error="form.errors.emergency_contact" />

                    <div class="flex items-center pt-5">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" v-model="form.family_in_govt_service" />
                            Family member in Government Service
                        </label>
                    </div>
                    <div class="flex items-center pt-5">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" v-model="form.is_single_parent" />
                            Single parent
                        </label>
                    </div>
                    <div class="flex items-center pt-5 md:col-span-2">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" v-model="form.is_first_generation_graduate" />
                            First-generation graduate in family
                        </label>
                    </div>
                </div>
            </section>

            <div class="flex justify-between">
                <Button type="button" variant="ghost" @click="router.visit('/student/profile/personal')">← Previous</Button>
                <div class="flex gap-2">
                    <Button type="button" variant="ghost" @click="submit(false)" :loading="form.processing">Save Draft</Button>
                    <Button type="submit" :loading="form.processing">Save &amp; Next →</Button>
                </div>
            </div>
        </form>
    </PortalLayout>
</template>
