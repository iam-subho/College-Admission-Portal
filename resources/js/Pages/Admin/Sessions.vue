<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import InputText from '@/Components/Ui/InputText.vue';
import Button from '@/Components/Ui/Button.vue';
import { ref } from 'vue';
import { formatDate } from '@/utils/date.js';

defineProps({
    sessions: { type: Array, required: true },
});

const showForm = ref(false);
const editingId = ref(null);

const blankForm = () => ({
    code: '',
    name: '',
    commencement_date: '',
    application_open_date: '',
    application_close_date: '',
    payment_mode: 'per_programme',
    application_fee: '',
    notes: '',
});

const form = useForm(blankForm());

const startEdit = (s) => {
    editingId.value = s.id;
    form.code = s.code;
    form.name = s.name;
    form.commencement_date = s.commencement_date || '';
    form.application_open_date = s.application_open_date || '';
    form.application_close_date = s.application_close_date || '';
    form.payment_mode = s.payment_mode || 'per_programme';
    form.application_fee = s.application_fee ?? '';
    form.notes = s.notes || '';
    showForm.value = true;
};

const cancel = () => {
    showForm.value = false;
    editingId.value = null;
    form.reset();
    form.clearErrors();
};

const submit = () => {
    const opts = {
        onSuccess: () => { cancel(); },
    };
    if (editingId.value) {
        form.patch(route('admin.sessions.update', editingId.value), opts);
    } else {
        form.post(route('admin.sessions.store'), opts);
    }
};

const activate = (id) => router.post(route('admin.sessions.activate', id));
const archive = (id) => router.post(route('admin.sessions.archive', id));
</script>

<template>
    <Head title="Sessions" />
    <PortalLayout title="Academic Sessions" :breadcrumb="['Admin', 'Sessions']">
        <div class="flex justify-end mb-4">
            <Button @click="showForm ? cancel() : (showForm = true)">
                {{ showForm ? 'Cancel' : '+ New Session' }}
            </Button>
        </div>

        <div v-if="showForm" class="bg-white border border-border rounded p-4 mb-6">
            <h3 class="font-serif text-lg text-maroon mb-3">
                {{ editingId ? `Edit Session #${editingId}` : 'Create Session' }}
            </h3>
            <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <InputText v-model="form.code" label="Code (e.g. 2026-27)" required :error="form.errors.code" />
                <InputText v-model="form.name" label="Name" required :error="form.errors.name" />
                <InputText v-model="form.application_open_date" type="date" label="Application Open Date" :error="form.errors.application_open_date" />
                <InputText v-model="form.application_close_date" type="date" label="Application Close Date" :error="form.errors.application_close_date" />
                <InputText v-model="form.commencement_date" type="date" label="Session Commencement Date" :error="form.errors.commencement_date" />
                <div>
                    <label class="block text-xs font-medium text-ink mb-1">
                        Application Payment Mode <span class="text-maroon">*</span>
                    </label>
                    <select v-model="form.payment_mode" class="w-full px-3 py-2 text-sm border border-border rounded bg-white">
                        <option value="per_programme">Per-Programme — student pays for each application</option>
                        <option value="one_time">One-Time — single fee covers all applications</option>
                    </select>
                    <p v-if="form.errors.payment_mode" class="text-xs text-red-600 mt-1">{{ form.errors.payment_mode }}</p>
                </div>
                <InputText
                    v-if="form.payment_mode === 'one_time'"
                    v-model="form.application_fee"
                    type="number"
                    step="0.01"
                    label="One-Time Application Fee (₹)"
                    required
                    :error="form.errors.application_fee"
                    placeholder="e.g. 500"
                />
                <div v-else class="text-xs text-ink-mute self-end pb-2">
                    Per-programme fees are set on each programme.
                </div>
                <div class="md:col-span-2">
                    <Button type="submit" :loading="form.processing">
                        {{ editingId ? 'Save Changes' : 'Create Session' }}
                    </Button>
                </div>
            </form>
        </div>

        <div class="bg-white border border-border rounded overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-cream text-ink-mute text-xs uppercase tracking-wider">
                    <tr>
                        <th class="text-left px-4 py-2">Code</th>
                        <th class="text-left px-4 py-2">Name</th>
                        <th class="text-left px-4 py-2">Status</th>
                        <th class="text-left px-4 py-2">Payment</th>
                        <th class="text-left px-4 py-2">Open</th>
                        <th class="text-left px-4 py-2">Close</th>
                        <th class="text-left px-4 py-2">Commencement</th>
                        <th class="text-right px-4 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="s in sessions" :key="s.id" class="border-t border-border">
                        <td class="px-4 py-2 font-mono">{{ s.code }}</td>
                        <td class="px-4 py-2">{{ s.name }}</td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-0.5 text-xs rounded"
                                :class="s.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700'">
                                {{ s.is_active ? 'Active' : s.status }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-xs">
                            <span v-if="s.payment_mode === 'one_time'" class="px-2 py-0.5 rounded bg-saffron-soft text-maroon">
                                One-time · ₹{{ s.application_fee ?? '—' }}
                            </span>
                            <span v-else class="text-ink-mute">Per-programme</span>
                        </td>
                        <td class="px-4 py-2">{{ formatDate(s.application_open_date) }}</td>
                        <td class="px-4 py-2">{{ formatDate(s.application_close_date) }}</td>
                        <td class="px-4 py-2">{{ formatDate(s.commencement_date) }}</td>
                        <td class="px-4 py-2 text-right space-x-2 whitespace-nowrap">
                            <button @click="startEdit(s)" class="text-xs px-2 py-1 border border-border rounded hover:bg-cream">
                                Edit
                            </button>
                            <button v-if="!s.is_active" @click="activate(s.id)" class="text-xs px-2 py-1 bg-saffron text-white rounded hover:bg-saffron/90">
                                Activate
                            </button>
                            <button v-if="s.is_active || s.status !== 'archived'" @click="archive(s.id)" class="text-xs px-2 py-1 border border-border rounded hover:bg-cream">
                                Archive
                            </button>
                        </td>
                    </tr>
                    <tr v-if="!sessions.length">
                        <td colspan="8" class="px-4 py-6 text-center text-ink-mute text-sm">No sessions yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </PortalLayout>
</template>
