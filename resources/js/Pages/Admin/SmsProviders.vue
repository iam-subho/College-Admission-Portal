<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import InputText from '@/Components/Ui/InputText.vue';
import Button from '@/Components/Ui/Button.vue';
import { ref, reactive } from 'vue';

defineProps({
    providers: { type: Array, required: true },
    codes: { type: Array, required: true },
});

const showForm = ref(false);
const editingId = ref(null);

const blank = () => ({
    code: 'msg91',
    display_name: 'MSG91',
    mode: 'stub',
    priority: 100,
    config: { auth_key: '', sender_id: '', flow_id: '' },
});

const form = reactive(blank());

const startCreate = () => { editingId.value = null; Object.assign(form, blank()); showForm.value = true; };
const startEdit = (p) => {
    editingId.value = p.id;
    form.code = p.code;
    form.display_name = p.display_name;
    form.mode = p.mode;
    form.priority = p.priority;
    form.config = { auth_key: '', sender_id: '', flow_id: '' };
    showForm.value = true;
};
const cancel = () => { showForm.value = false; editingId.value = null; Object.assign(form, blank()); };

const submit = () => {
    const opts = { onSuccess: cancel, preserveScroll: true };
    if (editingId.value) {
        router.patch(route('admin.sms-providers.update', editingId.value), form, opts);
    } else {
        router.post(route('admin.sms-providers.store'), form, opts);
    }
};

const toggle = (p) => router.post(route('admin.sms-providers.toggle', p.id), {}, { preserveScroll: true });
const destroy = (p) => {
    if (!confirm(`Delete provider "${p.display_name}"?`)) return;
    router.delete(route('admin.sms-providers.destroy', p.id), { preserveScroll: true });
};
</script>

<template>
    <Head title="SMS Providers" />
    <PortalLayout title="SMS Providers" :breadcrumb="['Admin', 'SMS Providers']">

        <p class="text-sm text-ink-mute mb-3">
            Configure one or more SMS providers. Only one can be active at a time — activating one auto-disables others.
            <strong>Stub mode</strong> logs SMS without sending (good for dev). <strong>Live mode</strong> hits the provider API.
        </p>

        <div class="flex justify-end mb-4">
            <Button @click="showForm ? cancel() : startCreate()">{{ showForm ? 'Cancel' : '+ New Provider' }}</Button>
        </div>

        <div v-if="showForm" class="bg-white border border-border rounded p-4 mb-6">
            <h3 class="font-serif text-lg text-maroon mb-3">
                {{ editingId ? `Edit Provider #${editingId}` : 'New SMS Provider' }}
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-ink mb-1">Provider Code <span class="text-maroon">*</span></label>
                    <select v-model="form.code" class="w-full px-3 py-2 text-sm border border-border rounded bg-white">
                        <option v-for="c in codes" :key="c" :value="c">{{ c }}</option>
                    </select>
                </div>
                <InputText v-model="form.display_name" label="Display Name" required />
                <div>
                    <label class="block text-xs font-medium text-ink mb-1">Mode <span class="text-maroon">*</span></label>
                    <select v-model="form.mode" class="w-full px-3 py-2 text-sm border border-border rounded bg-white">
                        <option value="stub">Stub (logs only)</option>
                        <option value="test">Test (provider sandbox)</option>
                        <option value="live">Live</option>
                    </select>
                </div>
                <InputText v-model="form.config.auth_key" label="Auth Key (MSG91 API key)" placeholder="Leave blank to keep existing" />
                <InputText v-model="form.config.sender_id" label="Sender ID" placeholder="e.g. SVNCAD" />
                <InputText v-model="form.config.flow_id" label="Flow ID (optional)" placeholder="MSG91 flow id if using flows" />
                <InputText v-model="form.priority" type="number" label="Priority (lower = preferred)" />
                <div class="md:col-span-3">
                    <Button @click="submit">{{ editingId ? 'Save Changes' : 'Create Provider' }}</Button>
                </div>
            </div>
        </div>

        <div class="bg-white border border-border rounded overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-cream text-ink-mute text-xs uppercase tracking-wider">
                    <tr>
                        <th class="text-left px-4 py-2">Code</th>
                        <th class="text-left px-4 py-2">Display Name</th>
                        <th class="text-left px-4 py-2">Mode</th>
                        <th class="text-right px-4 py-2">Priority</th>
                        <th class="text-left px-4 py-2">Config Keys</th>
                        <th class="text-left px-4 py-2">Status</th>
                        <th class="text-right px-4 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="p in providers" :key="p.id" class="border-t border-border">
                        <td class="px-4 py-2 font-mono text-xs">{{ p.code }}</td>
                        <td class="px-4 py-2">{{ p.display_name }}</td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-0.5 text-xs rounded font-mono"
                                :class="p.mode === 'live' ? 'bg-red-100 text-red-800' : (p.mode === 'test' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-700')">
                                {{ p.mode }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-right font-mono text-xs">{{ p.priority }}</td>
                        <td class="px-4 py-2 text-xs font-mono text-ink-mute">{{ p.config.join(', ') || '—' }}</td>
                        <td class="px-4 py-2">
                            <button @click="toggle(p)" class="text-xs px-2 py-1 rounded"
                                :class="p.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700'">
                                {{ p.is_active ? 'Active' : 'Disabled' }}
                            </button>
                        </td>
                        <td class="px-4 py-2 text-right space-x-2 whitespace-nowrap">
                            <button @click="startEdit(p)" class="text-xs px-2 py-1 border border-border rounded hover:bg-cream">Edit</button>
                            <button @click="destroy(p)" class="text-xs text-red-600 hover:underline">Delete</button>
                        </td>
                    </tr>
                    <tr v-if="!providers.length">
                        <td colspan="7" class="px-4 py-6 text-center text-ink-mute text-sm">No providers yet. Add MSG91 above with stub mode for testing.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </PortalLayout>
</template>
