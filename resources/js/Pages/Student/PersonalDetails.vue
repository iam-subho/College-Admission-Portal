<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import InputText from '@/Components/Ui/InputText.vue';
import Button from '@/Components/Ui/Button.vue';
import SectionStatusBanner from '@/Components/Ui/SectionStatusBanner.vue';
import { computed } from 'vue';

const props = defineProps({
    student: { type: Object, required: true },
    categories: { type: Array, required: true },
});

const RELIGIONS = ['Hindu', 'Muslim', 'Christian', 'Sikh', 'Buddhist', 'Jain', 'Other'];
const BLOOD_GROUPS = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
const MOTHER_TONGUES = ['Gujarati', 'Hindi', 'English', 'Marathi', 'Tamil', 'Telugu', 'Kannada', 'Bengali', 'Punjabi', 'Urdu', 'Other'];
const NATIONALITIES = ['Indian', 'Non-Resident Indian (NRI)', 'Foreign National'];
const PWD_TYPES = ['Locomotor', 'Visual', 'Hearing', 'Speech', 'Intellectual', 'Multiple', 'Other'];

const form = useForm({
    aadhaar_full_name: props.student.aadhaar_full_name || '',
    abc_id: props.student.abc_id || '',
    gender: props.student.gender || '',
    dob: props.student.dob || '',
    nationality: props.student.nationality || 'Indian',
    foreign_national: !!props.student.foreign_national,
    aadhaar: '',
    reservation_category_id: props.student.reservation_category_id || '',
    sub_caste: props.student.sub_caste || '',
    religion: props.student.religion || '',
    is_minority: !!props.student.is_minority,
    mother_tongue: props.student.mother_tongue || '',
    blood_group: props.student.blood_group || '',

    category_certificate_no: props.student.category_certificate_no || '',
    category_cert_issuer: props.student.category_cert_issuer || '',
    category_cert_date: props.student.category_cert_date || '',
    category_cert_validity_year: props.student.category_cert_validity_year || '',
    income_certificate_no: props.student.income_certificate_no || '',

    pwd_type: props.student.pwd_type || '',
    pwd_percentage: props.student.pwd_percentage || '',
    udid_number: props.student.udid_number || '',
});

const selectedCategory = computed(() =>
    props.categories.find(c => c.id === form.reservation_category_id) || null,
);

const needsReservationCert = computed(() => {
    const code = selectedCategory.value?.code?.toUpperCase();
    return code && !['UR', 'GEN', 'GENERAL'].includes(code);
});

const submit = (next) => {
    form.post('/student/profile/personal', {
        preserveScroll: true,
        onSuccess: () => { if (next) router.visit('/student/profile/family'); },
    });
};
</script>

<template>
    <Head title="Personal Details" />
    <PortalLayout title="Personal Details" :breadcrumb="['Student', 'Personal Details']">
        <p class="text-sm text-ink-mute mb-4">As per your Aadhaar / DigiLocker records — please verify carefully before saving.</p>

        <SectionStatusBanner section="personal" />

        <form @submit.prevent="submit(true)" class="space-y-6">
            <!-- Identity -->
            <section class="bg-white border border-border rounded">
                <header class="px-4 py-2 border-b border-border bg-cream">
                    <h2 class="font-serif text-base text-maroon">Identity Information</h2>
                </header>
                <div class="p-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <InputText v-model="form.aadhaar_full_name" label="Full Name (as per Aadhaar)" required :error="form.errors.aadhaar_full_name" />
                    <InputText v-model="form.dob" type="date" label="Date of Birth" required :error="form.errors.dob" />
                    <div>
                        <label class="block text-xs font-medium text-ink mb-1">Gender <span class="text-maroon">*</span></label>
                        <select v-model="form.gender" class="w-full px-3 py-2 text-sm border border-border rounded">
                            <option value="">—</option>
                            <option>Male</option><option>Female</option><option>Other</option><option>Prefer not to say</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-ink mb-1">Nationality <span class="text-maroon">*</span></label>
                        <select v-model="form.nationality" class="w-full px-3 py-2 text-sm border border-border rounded">
                            <option v-for="n in NATIONALITIES" :key="n" :value="n">{{ n }}</option>
                        </select>
                    </div>
                    <InputText v-model="form.aadhaar" label="Aadhaar Number (12 digits)" maxlength="12" :error="form.errors.aadhaar"
                        :placeholder="student.aadhaar_last4 ? `•••• •••• ${student.aadhaar_last4}` : 'Enter to save'" />
                    <InputText v-model="form.abc_id" label="ABC / APAAR ID" :error="form.errors.abc_id" placeholder="1234-5678-9012-3456" />
                    <div>
                        <label class="block text-xs font-medium text-ink mb-1">Blood Group</label>
                        <select v-model="form.blood_group" class="w-full px-3 py-2 text-sm border border-border rounded">
                            <option value="">—</option>
                            <option v-for="b in BLOOD_GROUPS" :key="b" :value="b">{{ b }}</option>
                        </select>
                    </div>
                    <div class="flex items-center pt-5">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" v-model="form.foreign_national" />
                            Foreign national
                        </label>
                    </div>
                </div>
            </section>

            <!-- Category & Reservation -->
            <section class="bg-white border border-border rounded">
                <header class="px-4 py-2 border-b border-border bg-cream">
                    <h2 class="font-serif text-base text-maroon">Category &amp; Reservation</h2>
                </header>
                <div class="p-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-ink mb-1">Category <span class="text-maroon">*</span></label>
                        <select v-model="form.reservation_category_id" class="w-full px-3 py-2 text-sm border border-border rounded">
                            <option value="">— select —</option>
                            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.code }} · {{ c.name }}</option>
                        </select>
                    </div>
                    <InputText v-model="form.sub_caste" label="Sub-caste" :error="form.errors.sub_caste" />
                    <div>
                        <label class="block text-xs font-medium text-ink mb-1">Religion</label>
                        <select v-model="form.religion" class="w-full px-3 py-2 text-sm border border-border rounded">
                            <option value="">—</option>
                            <option v-for="r in RELIGIONS" :key="r" :value="r">{{ r }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-ink mb-1">Mother Tongue</label>
                        <select v-model="form.mother_tongue" class="w-full px-3 py-2 text-sm border border-border rounded">
                            <option value="">—</option>
                            <option v-for="m in MOTHER_TONGUES" :key="m" :value="m">{{ m }}</option>
                        </select>
                    </div>
                    <div class="flex items-center pt-5">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" v-model="form.is_minority" />
                            Belongs to a notified minority community
                        </label>
                    </div>
                </div>
                <p class="px-4 pb-4 -mt-2 text-xs text-ink-mute">
                    <strong>Note:</strong> The category certificate must be issued by a competent authority. Upload it in the “Uploads” section.
                </p>
            </section>

            <!-- Reservation Certificates -->
            <section v-if="needsReservationCert || form.category_certificate_no" class="bg-white border border-border rounded">
                <header class="px-4 py-2 border-b border-border bg-cream">
                    <h2 class="font-serif text-base text-maroon">Reservation Certificate</h2>
                </header>
                <div class="p-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <InputText v-model="form.category_certificate_no" label="Certificate No." :error="form.errors.category_certificate_no" />
                    <InputText v-model="form.category_cert_issuer" label="Issuing Authority" :error="form.errors.category_cert_issuer" />
                    <InputText v-model="form.category_cert_date" type="date" label="Date of Issue" :error="form.errors.category_cert_date" />
                    <InputText v-model="form.category_cert_validity_year" type="number" label="Validity Year" :error="form.errors.category_cert_validity_year" />
                    <InputText v-model="form.income_certificate_no" label="Income Certificate No. (for EWS / scholarship)" :error="form.errors.income_certificate_no" />
                </div>
            </section>

            <!-- Disability (PwD) -->
            <section class="bg-white border border-border rounded">
                <header class="px-4 py-2 border-b border-border bg-cream">
                    <h2 class="font-serif text-base text-maroon">Disability (PwD)</h2>
                </header>
                <div class="p-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-ink mb-1">Disability Type</label>
                        <select v-model="form.pwd_type" class="w-full px-3 py-2 text-sm border border-border rounded">
                            <option value="">— none —</option>
                            <option v-for="t in PWD_TYPES" :key="t" :value="t">{{ t }}</option>
                        </select>
                    </div>
                    <InputText v-model="form.pwd_percentage" type="number" label="Disability %" :error="form.errors.pwd_percentage" />
                    <InputText v-model="form.udid_number" label="UDID Number" :error="form.errors.udid_number" />
                </div>
            </section>

            <div class="flex justify-end gap-2">
                <Button type="button" variant="ghost" @click="submit(false)" :loading="form.processing">Save Draft</Button>
                <Button type="submit" :loading="form.processing">Save &amp; Next →</Button>
            </div>
        </form>
    </PortalLayout>
</template>
