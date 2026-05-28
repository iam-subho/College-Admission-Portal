<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import InputText from '@/Components/Ui/InputText.vue';
import Button from '@/Components/Ui/Button.vue';
import { ref } from 'vue';

const props = defineProps({
    gateways: { type: Array, required: true },
    available_codes: { type: Array, required: true },
    modes: { type: Array, required: true },
});

const addForm = useForm({
    code: props.available_codes[0] || '',
    display_name: '',
    mode: 'stub',
    priority: 10,
    convenience_fee_rule: '',
});

const addGateway = () => addForm.post('/admin/gateways', {
    preserveScroll: true,
    onSuccess: () => addForm.reset(),
});

const toggle = (id) => router.post(`/admin/gateways/${id}/toggle`, {}, { preserveScroll: true });

const editing = ref(null); // gateway id
const editForm = useForm({
    display_name: '',
    mode: 'stub',
    priority: 10,
    convenience_fee_rule: '',
    config: { key_id: '', key_secret: '', webhook_secret: '' },
});

const openEdit = (g) => {
    editing.value = g.id;
    editForm.reset();
    editForm.display_name = g.display_name;
    editForm.mode = g.mode;
    editForm.priority = g.priority;
    editForm.convenience_fee_rule = g.convenience_fee_rule || '';
};

const closeEdit = () => { editing.value = null; };

const saveEdit = (id) => editForm.patch(`/admin/gateways/${id}`, {
    preserveScroll: true,
    onSuccess: closeEdit,
});
</script>

<template>
    <Head title="Payment Gateways" />
    <PortalLayout title="Payment Gateways" :breadcrumb="['Admin', 'Payment Gateways']">
        <p class="text-sm text-ink-mute mb-4">
            Manage active payment gateways and their credentials. Use <strong>stub</strong> mode to simulate
            payments end-to-end without real API calls.
        </p>

        <!-- Existing gateways -->
        <section class="bg-white border border-border rounded overflow-hidden mb-6">
            <table class="w-full text-sm">
                <thead class="bg-cream text-xs uppercase text-ink-mute">
                    <tr>
                        <th class="text-left px-4 py-2">Code</th>
                        <th class="text-left px-4 py-2">Display Name</th>
                        <th class="text-center px-4 py-2">Mode</th>
                        <th class="text-center px-4 py-2">Priority</th>
                        <th class="text-left px-4 py-2">Convenience</th>
                        <th class="text-center px-4 py-2">Creds</th>
                        <th class="text-center px-4 py-2">Active</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="g in gateways" :key="g.id">
                        <tr class="border-t border-border">
                            <td class="px-4 py-2 font-mono">{{ g.code }}</td>
                            <td class="px-4 py-2">{{ g.display_name }}</td>
                            <td class="px-4 py-2 text-center">
                                <span class="text-xs font-mono uppercase px-2 py-0.5 rounded"
                                    :class="g.mode === 'live'
                                        ? 'bg-green-100 text-green-800'
                                        : (g.mode === 'test' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-700')">
                                    {{ g.mode }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-center font-mono">{{ g.priority }}</td>
                            <td class="px-4 py-2 text-xs">{{ g.convenience_fee_rule || '—' }}</td>
                            <td class="px-4 py-2 text-center">
                                <span v-if="g.has_config" class="text-green-700">●</span>
                                <span v-else class="text-ink-mute">○</span>
                            </td>
                            <td class="px-4 py-2 text-center">
                                <button @click="toggle(g.id)" class="text-xs underline" :class="g.is_active ? 'text-green-700' : 'text-red-600'">
                                    {{ g.is_active ? 'On' : 'Off' }}
                                </button>
                            </td>
                            <td class="px-4 py-2 text-right">
                                <button @click="openEdit(g)" class="text-xs text-maroon hover:underline">Edit</button>
                            </td>
                        </tr>
                        <tr v-if="editing === g.id" class="border-t border-border bg-cream">
                            <td colspan="8" class="px-4 py-4">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-3">
                                    <InputText v-model="editForm.display_name" label="Display Name" />
                                    <div>
                                        <label class="block text-xs font-medium text-ink mb-1">Mode</label>
                                        <select v-model="editForm.mode" class="w-full px-3 py-2 text-sm border border-border rounded">
                                            <option v-for="m in modes" :key="m" :value="m">{{ m }}</option>
                                        </select>
                                    </div>
                                    <InputText v-model="editForm.priority" type="number" label="Priority" />
                                    <InputText v-model="editForm.convenience_fee_rule" label="Convenience Rule (flat:30 / pct:2.5)" />
                                </div>
                                <h4 class="text-xs font-semibold text-maroon mb-2">Credentials (encrypted at rest)</h4>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <InputText v-model="editForm.config.key_id" label="Key ID" />
                                    <InputText v-model="editForm.config.key_secret" label="Key Secret" type="password" />
                                    <InputText v-model="editForm.config.webhook_secret" label="Webhook Secret" type="password" />
                                </div>
                                <div class="flex gap-2 mt-3">
                                    <Button @click="saveEdit(g.id)" :loading="editForm.processing">Save</Button>
                                    <Button variant="ghost" @click="closeEdit">Cancel</Button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr v-if="!gateways.length">
                        <td colspan="8" class="px-4 py-6 text-center text-ink-mute text-sm">No gateways added yet.</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <!-- Add gateway -->
        <section class="bg-white border-2 border-saffron rounded">
            <header class="px-4 py-2 border-b border-border bg-saffron-soft">
                <h2 class="font-serif text-base text-maroon">Add Gateway</h2>
            </header>
            <form @submit.prevent="addGateway" class="p-4 grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                <div>
                    <label class="block text-xs font-medium text-ink mb-1">Code</label>
                    <select v-model="addForm.code" class="w-full px-3 py-2 text-sm border border-border rounded">
                        <option v-for="c in available_codes" :key="c" :value="c">{{ c }}</option>
                    </select>
                </div>
                <InputText v-model="addForm.display_name" label="Display Name" required />
                <div>
                    <label class="block text-xs font-medium text-ink mb-1">Mode</label>
                    <select v-model="addForm.mode" class="w-full px-3 py-2 text-sm border border-border rounded">
                        <option v-for="m in modes" :key="m" :value="m">{{ m }}</option>
                    </select>
                </div>
                <InputText v-model="addForm.priority" type="number" label="Priority" />
                <Button type="submit" :loading="addForm.processing" :disabled="!addForm.display_name">+ Add</Button>
            </form>
        </section>
    </PortalLayout>
</template>
