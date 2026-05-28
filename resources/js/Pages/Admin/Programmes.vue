<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import InputText from '@/Components/Ui/InputText.vue';
import Button from '@/Components/Ui/Button.vue';
import { ref } from 'vue';

const props = defineProps({
    programmes: { type: Array, required: true },
    departments: { type: Array, required: true },
    categories: { type: Array, required: true },
    active_session: { type: Object, default: null },
});

const showForm = ref(false);
const editingId = ref(null);

const blankForm = () => ({
    code: '',
    name: '',
    department_id: '',
    type: 'UG',
    duration_years: 3,
    total_credits: 120,
    intake_capacity: 60,
    application_fee: '',
    admission_fee: '',
    description: '',
});

const form = useForm(blankForm());

const startCreate = () => { editingId.value = null; form.reset(); showForm.value = true; };
const startEdit = (p) => {
    editingId.value = p.id;
    form.code = p.code;
    form.name = p.name;
    form.department_id = p.department_id;
    form.type = p.type;
    form.duration_years = p.duration_years;
    form.total_credits = p.total_credits;
    form.intake_capacity = p.intake_capacity;
    form.application_fee = p.application_fee ?? '';
    form.admission_fee = p.admission_fee ?? '';
    form.description = '';
    showForm.value = true;
};
const cancel = () => { showForm.value = false; editingId.value = null; form.reset(); form.clearErrors(); };

const submit = () => {
    const opts = { onSuccess: cancel };
    if (editingId.value) {
        form.patch(route('admin.programmes.update', editingId.value), opts);
    } else {
        form.post(route('admin.programmes.store'), opts);
    }
};

// --- Fee override modal (Application + Admission tabs) ---
const feeModal = ref(null);
const feeTab = ref('application'); // 'application' | 'admission'

const openFeeModal = (p) => {
    feeTab.value = 'application';
    feeModal.value = {
        programme: p,
        application: {
            overrides: (p.fee_overrides || []).map((o) => ({
                reservation_category_id: o.category_id,
                application_fee: o.application_fee,
            })),
        },
        admission: {
            overrides: (p.admission_fee_overrides || []).map((o) => ({
                reservation_category_id: o.category_id,
                admission_fee: o.admission_fee,
            })),
        },
    };
};
const closeFeeModal = () => { feeModal.value = null; };

const addOverrideRow = () => {
    if (feeTab.value === 'application') {
        feeModal.value.application.overrides.push({ reservation_category_id: '', application_fee: '' });
    } else {
        feeModal.value.admission.overrides.push({ reservation_category_id: '', admission_fee: '' });
    }
};
const removeOverrideRow = (i) => {
    if (feeTab.value === 'application') {
        feeModal.value.application.overrides.splice(i, 1);
    } else {
        feeModal.value.admission.overrides.splice(i, 1);
    }
};

const saveOverrides = () => {
    const programmeId = feeModal.value.programme.id;
    if (feeTab.value === 'application') {
        const overrides = feeModal.value.application.overrides
            .filter((o) => o.reservation_category_id && o.application_fee !== '');
        router.post(
            route('admin.programmes.application-fees.sync', programmeId),
            { overrides },
            { onSuccess: closeFeeModal, preserveScroll: true },
        );
    } else {
        const overrides = feeModal.value.admission.overrides
            .filter((o) => o.reservation_category_id && o.admission_fee !== '');
        router.post(
            route('admin.programmes.admission-fees.sync', programmeId),
            { overrides },
            { onSuccess: closeFeeModal, preserveScroll: true },
        );
    }
};

const inr = (n) => n != null ? '₹' + Number(n).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—';
</script>

<template>
    <Head title="Programmes" />
    <PortalLayout title="Programmes" :breadcrumb="['Admin', 'Programmes']">
        <p v-if="active_session" class="text-sm text-ink-mute mb-4">
            Active session: <strong>{{ active_session.code }}</strong>
            <span v-if="active_session.payment_mode === 'one_time'" class="ml-2 px-2 py-0.5 text-xs rounded bg-saffron-soft text-maroon">
                One-time application fee mode (₹{{ active_session.application_fee }}) — per-programme application fees are ignored.
                Admission fees still use the per-programme values below.
            </span>
        </p>

        <div class="flex justify-end mb-4">
            <Button @click="showForm ? cancel() : startCreate()">
                {{ showForm ? 'Cancel' : '+ New Programme' }}
            </Button>
        </div>

        <div v-if="showForm" class="bg-white border border-border rounded p-4 mb-6">
            <h3 class="font-serif text-lg text-maroon mb-3">
                {{ editingId ? `Edit Programme #${editingId}` : 'New Programme' }}
            </h3>
            <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <InputText v-model="form.code" label="Code" required :error="form.errors.code" placeholder="UGCS01" />
                <div class="md:col-span-2">
                    <InputText v-model="form.name" label="Name" required :error="form.errors.name" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-ink mb-1">Department <span class="text-maroon">*</span></label>
                    <select v-model="form.department_id" class="w-full px-3 py-2 text-sm border border-border rounded bg-white">
                        <option value="">— Select —</option>
                        <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.code }} · {{ d.name }}</option>
                    </select>
                    <p v-if="form.errors.department_id" class="text-xs text-red-600 mt-1">{{ form.errors.department_id }}</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-ink mb-1">Type <span class="text-maroon">*</span></label>
                    <select v-model="form.type" class="w-full px-3 py-2 text-sm border border-border rounded bg-white">
                        <option value="UG">Undergraduate</option>
                        <option value="PG">Postgraduate</option>
                    </select>
                </div>
                <InputText v-model="form.duration_years" type="number" label="Duration (Years)" required />
                <InputText v-model="form.total_credits" type="number" label="Total Credits" required />
                <InputText v-model="form.intake_capacity" type="number" label="Intake Capacity" required :error="form.errors.intake_capacity" />
                <InputText v-model="form.application_fee" type="number" step="0.01" label="Application Fee (₹)"
                    :error="form.errors.application_fee" placeholder="e.g. 500.00" />
                <InputText v-model="form.admission_fee" type="number" step="0.01" label="Admission Fee (₹)"
                    :error="form.errors.admission_fee" placeholder="e.g. 10000.00" />
                <div></div>
                <div class="md:col-span-3">
                    <Button type="submit" :loading="form.processing">
                        {{ editingId ? 'Save Changes' : 'Save Programme' }}
                    </Button>
                </div>
            </form>
        </div>

        <div class="bg-white border border-border rounded overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-cream text-ink-mute text-xs uppercase tracking-wider">
                    <tr>
                        <th class="text-left px-4 py-2">Code</th>
                        <th class="text-left px-4 py-2">Programme</th>
                        <th class="text-left px-4 py-2">Department</th>
                        <th class="text-left px-4 py-2">Type</th>
                        <th class="text-right px-4 py-2">Intake</th>
                        <th class="text-right px-4 py-2">Reserved</th>
                        <th class="text-right px-4 py-2">App. Fee</th>
                        <th class="text-right px-4 py-2">Adm. Fee</th>
                        <th class="text-right px-4 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="p in programmes" :key="p.id" class="border-t border-border align-top">
                        <td class="px-4 py-2 font-mono">{{ p.code }}</td>
                        <td class="px-4 py-2">{{ p.name }}</td>
                        <td class="px-4 py-2 text-ink-mute text-xs">{{ p.department }}</td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-0.5 text-xs rounded bg-navy text-white">{{ p.type }}</span>
                        </td>
                        <td class="px-4 py-2 text-right font-mono">{{ p.intake_capacity }}</td>
                        <td class="px-4 py-2 text-right font-mono"
                            :class="p.reserved_seats === p.intake_capacity ? 'text-green-700' : 'text-red-600'">
                            {{ p.reserved_seats }}
                        </td>
                        <td class="px-4 py-2 text-right font-mono">
                            <div>{{ inr(p.application_fee) }}</div>
                            <div v-if="p.fee_overrides?.length" class="text-[10px] text-saffron font-sans">
                                {{ p.fee_overrides.length }} override{{ p.fee_overrides.length > 1 ? 's' : '' }}
                            </div>
                        </td>
                        <td class="px-4 py-2 text-right font-mono">
                            <div>{{ inr(p.admission_fee) }}</div>
                            <div v-if="p.admission_fee_overrides?.length" class="text-[10px] text-saffron font-sans">
                                {{ p.admission_fee_overrides.length }} override{{ p.admission_fee_overrides.length > 1 ? 's' : '' }}
                            </div>
                        </td>
                        <td class="px-4 py-2 text-right space-x-2 whitespace-nowrap">
                            <button @click="startEdit(p)" class="text-xs px-2 py-1 border border-border rounded hover:bg-cream">
                                Edit
                            </button>
                            <button @click="openFeeModal(p)" class="text-xs px-2 py-1 border border-saffron text-saffron rounded hover:bg-saffron-soft">
                                Fee Overrides
                            </button>
                        </td>
                    </tr>
                    <tr v-if="!programmes.length">
                        <td colspan="9" class="px-4 py-6 text-center text-ink-mute text-sm">No programmes yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="mt-3 text-xs text-ink-mute">
            Reserved shows the sum of vertical category seats for the active session. Should equal Intake.
        </p>

        <!-- Fee override modal with tabs -->
        <div v-if="feeModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" @click.self="closeFeeModal">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <header class="px-5 py-3 border-b border-border bg-cream flex items-center justify-between">
                    <h3 class="font-serif text-base text-maroon">
                        Fee Overrides — {{ feeModal.programme.code }} · {{ feeModal.programme.name }}
                    </h3>
                    <button @click="closeFeeModal" class="text-ink-mute hover:text-ink">✕</button>
                </header>

                <!-- Tabs -->
                <div class="flex gap-1 px-5 pt-3 border-b border-border">
                    <button @click="feeTab = 'application'"
                        :class="feeTab === 'application'
                            ? 'px-3 py-1.5 text-xs rounded-t bg-maroon text-white border border-maroon border-b-0'
                            : 'px-3 py-1.5 text-xs rounded-t bg-white text-ink border border-border hover:bg-cream'">
                        Application Fee
                        <span class="ml-1 font-mono text-[10px]">{{ inr(feeModal.programme.application_fee) }}</span>
                    </button>
                    <button @click="feeTab = 'admission'"
                        :class="feeTab === 'admission'
                            ? 'px-3 py-1.5 text-xs rounded-t bg-maroon text-white border border-maroon border-b-0'
                            : 'px-3 py-1.5 text-xs rounded-t bg-white text-ink border border-border hover:bg-cream'">
                        Admission Fee
                        <span class="ml-1 font-mono text-[10px]">{{ inr(feeModal.programme.admission_fee) }}</span>
                    </button>
                </div>

                <div class="p-5 space-y-4">
                    <div v-if="feeTab === 'application'" class="bg-saffron-soft border border-saffron/30 rounded p-3 text-xs">
                        <strong>Programme default application fee:</strong> {{ inr(feeModal.programme.application_fee) }}
                        <p class="mt-1 text-ink-mute">
                            Paid by every applicant at submission. Add per-category overrides (SC / ST / EWS etc.) below.
                            To change the default, edit the programme.
                        </p>
                    </div>
                    <div v-else class="bg-blue-50 border border-blue-200 rounded p-3 text-xs">
                        <strong>Programme default admission fee:</strong> {{ inr(feeModal.programme.admission_fee) }}
                        <p class="mt-1 text-ink-mute">
                            Paid only by students who are allotted a seat. Overrides commonly apply to SC / ST / OBC-NCL / EWS
                            with concessional rates.
                        </p>
                    </div>

                    <!-- Application Fee panel -->
                    <template v-if="feeTab === 'application'">
                        <div v-if="!feeModal.application.overrides.length" class="text-sm text-ink-mute italic text-center py-4">
                            No overrides yet. Default fee applies to all categories.
                        </div>
                        <div v-for="(row, i) in feeModal.application.overrides" :key="`app-${i}`"
                            class="flex items-end gap-2 border-b border-border pb-3">
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-ink mb-1">Category</label>
                                <select v-model="row.reservation_category_id" class="w-full px-3 py-2 text-sm border border-border rounded bg-white">
                                    <option value="">— Select —</option>
                                    <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.code }} · {{ c.name }}</option>
                                </select>
                            </div>
                            <div class="w-40">
                                <label class="block text-xs font-medium text-ink mb-1">Application Fee (₹)</label>
                                <input v-model="row.application_fee" type="number" step="0.01"
                                    class="w-full px-3 py-2 text-sm border border-border rounded" />
                            </div>
                            <button @click="removeOverrideRow(i)" class="px-2 py-2 text-xs border border-border rounded hover:bg-red-50 text-red-600">✕</button>
                        </div>
                    </template>

                    <!-- Admission Fee panel -->
                    <template v-else>
                        <div v-if="!feeModal.admission.overrides.length" class="text-sm text-ink-mute italic text-center py-4">
                            No overrides yet. Default admission fee applies to all categories.
                        </div>
                        <div v-for="(row, i) in feeModal.admission.overrides" :key="`adm-${i}`"
                            class="flex items-end gap-2 border-b border-border pb-3">
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-ink mb-1">Category</label>
                                <select v-model="row.reservation_category_id" class="w-full px-3 py-2 text-sm border border-border rounded bg-white">
                                    <option value="">— Select —</option>
                                    <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.code }} · {{ c.name }}</option>
                                </select>
                            </div>
                            <div class="w-40">
                                <label class="block text-xs font-medium text-ink mb-1">Admission Fee (₹)</label>
                                <input v-model="row.admission_fee" type="number" step="0.01"
                                    class="w-full px-3 py-2 text-sm border border-border rounded" />
                            </div>
                            <button @click="removeOverrideRow(i)" class="px-2 py-2 text-xs border border-border rounded hover:bg-red-50 text-red-600">✕</button>
                        </div>
                    </template>

                    <button @click="addOverrideRow" class="text-xs px-3 py-1.5 border border-dashed border-border rounded hover:bg-cream">
                        + Add Category Override
                    </button>
                </div>

                <footer class="px-5 py-3 border-t border-border bg-cream flex justify-end gap-2">
                    <button @click="closeFeeModal" class="px-3 py-1.5 text-sm border border-border rounded hover:bg-white">Cancel</button>
                    <Button @click="saveOverrides">
                        Save {{ feeTab === 'application' ? 'Application' : 'Admission' }} Overrides
                    </Button>
                </footer>
            </div>
        </div>
    </PortalLayout>
</template>
