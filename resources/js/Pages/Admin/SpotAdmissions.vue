<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import SearchableSelect from '@/Components/Ui/SearchableSelect.vue';
import InputText from '@/Components/Ui/InputText.vue';
import Button from '@/Components/Ui/Button.vue';
import { computed } from 'vue';

const props = defineProps({
    rounds: { type: Array, required: true },
    eligible_applications: { type: Array, required: true },
    categories: { type: Array, required: true },
});

const form = useForm({
    admission_round_id: '',
    application_id: '',
    reservation_category_id: '',
    remark: '',
});

// Filter applications by selected round's programme.
const selectedRound = computed(() => props.rounds.find(r => r.id === Number(form.admission_round_id)) || null);

const filteredApps = computed(() => {
    if (!selectedRound.value) return props.eligible_applications;
    return props.eligible_applications.filter(a => a.program_id === selectedRound.value.program_id);
});

const onAppPick = (opt) => {
    form.application_id = opt ? opt.value : '';
    if (opt?.category_id) form.reservation_category_id = opt.category_id;
};

const submit = () => form.post(route('admin.spot-admission.store'), {
    onSuccess: () => form.reset(),
    preserveScroll: true,
});
</script>

<template>
    <Head title="Spot Admission" />
    <PortalLayout title="Spot / Walk-in Admission" :breadcrumb="['Admin', 'Spot Admission']">

        <p class="text-sm text-ink-mute mb-4">
            Allot a vacant seat to a walk-in applicant directly, bypassing the merit list.
            Pick the round, the applicant (must be submitted + paid + not already allocated), and the category.
        </p>

        <form @submit.prevent="submit" class="bg-white border border-border rounded p-4 space-y-4">
            <div>
                <label class="block text-xs font-medium text-ink mb-1">Admission Round <span class="text-maroon">*</span></label>
                <select v-model="form.admission_round_id" class="w-full px-3 py-2 text-sm border border-border rounded bg-white">
                    <option value="">— Select Round —</option>
                    <option v-for="r in rounds" :key="r.id" :value="r.id">
                        {{ r.program?.code }} · {{ r.session?.code }} · Round {{ r.round_number }} ({{ r.status }})
                    </option>
                </select>
                <p v-if="form.errors.admission_round_id" class="text-xs text-red-600 mt-1">{{ form.errors.admission_round_id }}</p>
            </div>

            <div>
                <SearchableSelect
                    :model-value="form.application_id"
                    :options="filteredApps"
                    label="Applicant"
                    placeholder="Search by application number / name / email…"
                    empty-text="No eligible applications. Applicant must be submitted, paid, and not already allocated."
                    :error="form.errors.application_id"
                    @change="onAppPick" />
                <p class="text-[10px] text-ink-mute mt-1">
                    Showing applications matching the selected round's programme.
                </p>
            </div>

            <div>
                <label class="block text-xs font-medium text-ink mb-1">Allot Against Category</label>
                <select v-model="form.reservation_category_id" class="w-full px-3 py-2 text-sm border border-border rounded bg-white">
                    <option value="">— No category (UR) —</option>
                    <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.code }} · {{ c.name }}</option>
                </select>
                <p class="text-[10px] text-ink-mute mt-1">
                    Auto-filled from the applicant's profile category when an applicant is selected.
                </p>
            </div>

            <InputText v-model="form.remark" label="Audit Remark (optional)" placeholder="Why this spot allotment was made" />

            <div>
                <Button type="submit" :loading="form.processing" :disabled="!form.admission_round_id || !form.application_id">
                    Allot Seat (Spot)
                </Button>
            </div>
        </form>
    </PortalLayout>
</template>
