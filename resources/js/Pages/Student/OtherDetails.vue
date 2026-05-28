<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import Button from '@/Components/Ui/Button.vue';
import SectionStatusBanner from '@/Components/Ui/SectionStatusBanner.vue';

const props = defineProps({
    student: { type: Object, required: true },
});

const NCC = ['None', 'A', 'B', 'C'];
const SPORTS = ['None', 'School', 'District', 'State', 'National', 'International'];
const ACCOMMODATION = [
    { value: 'day_scholar', label: 'Day Scholar' },
    { value: 'hosteller', label: 'Hosteller' },
];
const COMM_PREFS = [
    { value: 'email_sms', label: 'Email + SMS' },
    { value: 'email', label: 'Email only' },
    { value: 'sms', label: 'SMS only' },
    { value: 'whatsapp', label: 'WhatsApp' },
];

const form = useForm({
    ncc_certificate: props.student.ncc_certificate || 'None',
    is_nss_volunteer: !!props.student.is_nss_volunteer,
    sports_level: props.student.sports_level || 'None',
    awards: props.student.awards || '',
    accommodation: props.student.accommodation || 'day_scholar',
    transport_required: !!props.student.transport_required,
    communication_preference: props.student.communication_preference || 'email_sms',
});

const submit = (next) => {
    form.post('/student/profile/other', {
        preserveScroll: true,
        onSuccess: () => { if (next) router.visit('/student/uploads'); },
    });
};
</script>

<template>
    <Head title="Other Details" />
    <PortalLayout title="Other Details" :breadcrumb="['Student', 'Other Details']">
        <p class="text-sm text-ink-mute mb-4">Achievements, sports, NCC and other preferences.</p>

        <SectionStatusBanner section="other" />

        <form @submit.prevent="submit(true)" class="space-y-6">
            <!-- Co-curricular -->
            <section class="bg-white border border-border rounded">
                <header class="px-4 py-2 border-b border-border bg-cream">
                    <h2 class="font-serif text-base text-maroon">Co-curricular &amp; Extracurricular</h2>
                </header>
                <div class="p-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-ink mb-1">NCC Certificate</label>
                        <select v-model="form.ncc_certificate" class="w-full px-3 py-2 text-sm border border-border rounded">
                            <option v-for="c in NCC" :key="c" :value="c">{{ c }}</option>
                        </select>
                    </div>
                    <div class="flex items-center pt-5">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" v-model="form.is_nss_volunteer" />
                            NSS Volunteer
                        </label>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-ink mb-1">Sports Achievement</label>
                        <select v-model="form.sports_level" class="w-full px-3 py-2 text-sm border border-border rounded">
                            <option v-for="s in SPORTS" :key="s" :value="s">{{ s }}</option>
                        </select>
                    </div>
                </div>
                <div class="px-4 pb-4">
                    <label class="block text-xs font-medium text-ink mb-1">Awards / Achievements</label>
                    <textarea v-model="form.awards" rows="3"
                        placeholder="e.g. 1st prize in National Maths Olympiad 2024; Selected for State Tennis team 2025…"
                        class="w-full px-3 py-2 text-sm border border-border rounded"></textarea>
                </div>
            </section>

            <!-- Additional Information -->
            <section class="bg-white border border-border rounded">
                <header class="px-4 py-2 border-b border-border bg-cream">
                    <h2 class="font-serif text-base text-maroon">Additional Information</h2>
                </header>
                <div class="p-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-ink mb-1">Hosteller / Day Scholar</label>
                        <select v-model="form.accommodation" class="w-full px-3 py-2 text-sm border border-border rounded">
                            <option v-for="a in ACCOMMODATION" :key="a.value" :value="a.value">{{ a.label }}</option>
                        </select>
                    </div>
                    <div class="flex items-center pt-5">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" v-model="form.transport_required" />
                            College transport required
                        </label>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-ink mb-1">Preferred Communication</label>
                        <select v-model="form.communication_preference" class="w-full px-3 py-2 text-sm border border-border rounded">
                            <option v-for="c in COMM_PREFS" :key="c.value" :value="c.value">{{ c.label }}</option>
                        </select>
                    </div>
                </div>
            </section>

            <div class="flex justify-between">
                <Button type="button" variant="ghost" @click="router.visit('/student/academic-records')">← Previous</Button>
                <div class="flex gap-2">
                    <Button type="button" variant="ghost" @click="submit(false)" :loading="form.processing">Save Draft</Button>
                    <Button type="submit" :loading="form.processing">Save &amp; Next: Uploads →</Button>
                </div>
            </div>
        </form>
    </PortalLayout>
</template>
