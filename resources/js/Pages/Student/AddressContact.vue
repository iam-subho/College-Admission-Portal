<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import InputText from '@/Components/Ui/InputText.vue';
import Button from '@/Components/Ui/Button.vue';
import SectionStatusBanner from '@/Components/Ui/SectionStatusBanner.vue';
import { computed, watch } from 'vue';

const props = defineProps({
    student: { type: Object, required: true },
});

const STATES = [
    'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh',
    'Goa', 'Gujarat', 'Haryana', 'Himachal Pradesh', 'Jharkhand', 'Karnataka',
    'Kerala', 'Madhya Pradesh', 'Maharashtra', 'Manipur', 'Meghalaya', 'Mizoram',
    'Nagaland', 'Odisha', 'Punjab', 'Rajasthan', 'Sikkim', 'Tamil Nadu',
    'Telangana', 'Tripura', 'Uttar Pradesh', 'Uttarakhand', 'West Bengal',
    'Delhi', 'Chandigarh', 'Jammu & Kashmir', 'Ladakh', 'Puducherry',
    'Andaman & Nicobar Islands', 'Dadra & Nagar Haveli and Daman & Diu', 'Lakshadweep',
];

const form = useForm({
    address: props.student.address || '',
    house_no: props.student.house_no || '',
    locality: props.student.locality || '',
    country: props.student.country || 'India',
    state: props.student.state || '',
    district: props.student.district || '',
    taluka: props.student.taluka || '',
    pincode: props.student.pincode || '',
    domicile_state: props.student.domicile_state || '',
    domicile_district: props.student.domicile_district || '',

    correspondence_same_as_permanent: props.student.correspondence_same_as_permanent ?? true,
    correspondence_house_no: props.student.correspondence_house_no || '',
    correspondence_locality: props.student.correspondence_locality || '',
    correspondence_taluka: props.student.correspondence_taluka || '',
    correspondence_district: props.student.correspondence_district || '',
    correspondence_state: props.student.correspondence_state || '',
    correspondence_country: props.student.correspondence_country || 'India',
    correspondence_pincode: props.student.correspondence_pincode || '',
});

const user = computed(() => props.student.user || {});

// Mirror permanent → correspondence when "same as" is checked
watch(
    () => form.correspondence_same_as_permanent,
    (same) => {
        if (same) {
            form.correspondence_house_no = form.house_no;
            form.correspondence_locality = form.locality;
            form.correspondence_taluka = form.taluka;
            form.correspondence_district = form.district;
            form.correspondence_state = form.state;
            form.correspondence_country = form.country;
            form.correspondence_pincode = form.pincode;
        }
    },
);

const submit = (next) => {
    form.post('/student/profile/address', {
        preserveScroll: true,
        onSuccess: () => { if (next) router.visit('/student/academic-records'); },
    });
};
</script>

<template>
    <Head title="Address & Contact" />
    <PortalLayout title="Address & Contact" :breadcrumb="['Student', 'Address & Contact']">
        <p class="text-sm text-ink-mute mb-4">Permanent and correspondence addresses.</p>

        <SectionStatusBanner section="address" />

        <form @submit.prevent="submit(true)" class="space-y-6">
            <!-- Contact (read-only verified) -->
            <section class="bg-white border border-border rounded">
                <header class="px-4 py-2 border-b border-border bg-cream">
                    <h2 class="font-serif text-base text-maroon">Contact Details</h2>
                </header>
                <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-ink mb-1">Email Address</label>
                        <input :value="user.email" readonly class="w-full px-3 py-2 text-sm border border-border rounded bg-cream" />
                        <p class="text-xs text-green-700 mt-1">✓ Verified via OTP</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-ink mb-1">Mobile Number</label>
                        <input :value="user.mobile" readonly class="w-full px-3 py-2 text-sm border border-border rounded bg-cream" />
                        <p class="text-xs text-green-700 mt-1">✓ Verified via OTP</p>
                    </div>
                </div>
            </section>

            <!-- Permanent Address -->
            <section class="bg-white border border-border rounded">
                <header class="px-4 py-2 border-b border-border bg-cream">
                    <h2 class="font-serif text-base text-maroon">Permanent Address</h2>
                </header>
                <div class="p-4 space-y-4">
                    <InputText v-model="form.house_no" label="House / Building / Street" required :error="form.errors.house_no" />
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <InputText v-model="form.locality" label="Locality / Area" required :error="form.errors.locality" />
                        <InputText v-model="form.country" label="Country" required :error="form.errors.country" />
                        <InputText v-model="form.pincode" label="Pincode" maxlength="6" required :error="form.errors.pincode" />
                        <div>
                            <label class="block text-xs font-medium text-ink mb-1">State / UT <span class="text-maroon">*</span></label>
                            <select v-model="form.state" class="w-full px-3 py-2 text-sm border border-border rounded">
                                <option value="">— select —</option>
                                <option v-for="s in STATES" :key="s" :value="s">{{ s }}</option>
                            </select>
                        </div>
                        <InputText v-model="form.district" label="District" required :error="form.errors.district" />
                        <InputText v-model="form.taluka" label="Taluka / Tehsil" :error="form.errors.taluka" />
                    </div>
                    <textarea v-model="form.address" rows="2" placeholder="Optional — full address as a single line"
                        class="w-full px-3 py-2 text-sm border border-border rounded"></textarea>
                </div>
            </section>

            <!-- Correspondence Address -->
            <section class="bg-white border border-border rounded">
                <header class="px-4 py-2 border-b border-border bg-cream flex justify-between items-center">
                    <h2 class="font-serif text-base text-maroon">Correspondence Address</h2>
                    <label class="text-xs flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" v-model="form.correspondence_same_as_permanent" />
                        Same as Permanent Address
                    </label>
                </header>
                <div v-if="!form.correspondence_same_as_permanent" class="p-4 space-y-4">
                    <InputText v-model="form.correspondence_house_no" label="House / Building / Street" required :error="form.errors.correspondence_house_no" />
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <InputText v-model="form.correspondence_locality" label="Locality / Area" required :error="form.errors.correspondence_locality" />
                        <InputText v-model="form.correspondence_country" label="Country" required :error="form.errors.correspondence_country" />
                        <InputText v-model="form.correspondence_pincode" label="Pincode" maxlength="6" required :error="form.errors.correspondence_pincode" />
                        <div>
                            <label class="block text-xs font-medium text-ink mb-1">State / UT <span class="text-maroon">*</span></label>
                            <select v-model="form.correspondence_state" class="w-full px-3 py-2 text-sm border border-border rounded">
                                <option value="">— select —</option>
                                <option v-for="s in STATES" :key="s" :value="s">{{ s }}</option>
                            </select>
                        </div>
                        <InputText v-model="form.correspondence_district" label="District" required :error="form.errors.correspondence_district" />
                        <InputText v-model="form.correspondence_taluka" label="Taluka / Tehsil" :error="form.errors.correspondence_taluka" />
                    </div>
                </div>
                <p v-else class="px-4 py-3 text-xs text-ink-mute">
                    Correspondence will be sent to your permanent address.
                </p>
            </section>

            <!-- Domicile -->
            <section class="bg-white border border-border rounded">
                <header class="px-4 py-2 border-b border-border bg-cream">
                    <h2 class="font-serif text-base text-maroon">Domicile</h2>
                </header>
                <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-ink mb-1">Domicile State <span class="text-maroon">*</span></label>
                        <select v-model="form.domicile_state" class="w-full px-3 py-2 text-sm border border-border rounded">
                            <option value="">— select —</option>
                            <option v-for="s in STATES" :key="s" :value="s">{{ s }}</option>
                        </select>
                    </div>
                    <InputText v-model="form.domicile_district" label="Domicile District" :error="form.errors.domicile_district" />
                </div>
            </section>

            <div class="flex justify-between">
                <Button type="button" variant="ghost" @click="router.visit('/student/profile/family')">← Previous</Button>
                <div class="flex gap-2">
                    <Button type="button" variant="ghost" @click="submit(false)" :loading="form.processing">Save Draft</Button>
                    <Button type="submit" :loading="form.processing">Save &amp; Next →</Button>
                </div>
            </div>
        </form>
    </PortalLayout>
</template>
