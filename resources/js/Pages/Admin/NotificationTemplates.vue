<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import InputText from '@/Components/Ui/InputText.vue';
import Button from '@/Components/Ui/Button.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    templates: { type: Array, required: true },
    event_keys: { type: Array, required: true },
    channels: { type: Array, required: true },
});

const showForm = ref(false);
const editingId = ref(null);
const filterEvent = ref('');
const filterChannel = ref('');

const form = useForm({
    event: 'application_submitted',
    channel: 'email',
    subject: '',
    body: '',
    dlt_template_id: '',
    whatsapp_template_name: '',
});

const filteredTemplates = computed(() => {
    return props.templates.filter(t => {
        if (filterEvent.value && t.event !== filterEvent.value) return false;
        if (filterChannel.value && t.channel !== filterChannel.value) return false;
        return true;
    });
});

const startCreate = () => { editingId.value = null; form.reset(); showForm.value = true; };
const startEdit = (t) => {
    editingId.value = t.id;
    form.event = t.event;
    form.channel = t.channel;
    form.subject = t.subject ?? '';
    form.body = t.body;
    form.dlt_template_id = t.dlt_template_id ?? '';
    form.whatsapp_template_name = t.whatsapp_template_name ?? '';
    showForm.value = true;
};
const cancel = () => { showForm.value = false; editingId.value = null; form.reset(); form.clearErrors(); };

const submit = () => {
    const opts = { onSuccess: cancel };
    if (editingId.value) {
        form.patch(route('admin.notification-templates.update', editingId.value), opts);
    } else {
        form.post(route('admin.notification-templates.store'), opts);
    }
};
const toggle = (t) => router.post(route('admin.notification-templates.toggle', t.id), {}, { preserveScroll: true });
const destroy = (t) => {
    if (!confirm(`Delete template for ${t.event} (${t.channel})?`)) return;
    router.delete(route('admin.notification-templates.destroy', t.id), { preserveScroll: true });
};

const channelBadge = (c) => ({
    sms: 'bg-blue-100 text-blue-800',
    email: 'bg-purple-100 text-purple-800',
    whatsapp: 'bg-green-100 text-green-800',
}[c] || 'bg-gray-100');
</script>

<template>
    <Head title="Notification Templates" />
    <PortalLayout title="Notification Templates" :breadcrumb="['Admin', 'Notification Templates']">

        <p class="text-sm text-ink-mute mb-3">
            Edit the body of each notification by event × channel. Use
            <code class="font-mono bg-cream px-1 rounded" v-pre>{{variable}}</code>
            placeholders for substitution (e.g.
            <code class="font-mono bg-cream px-1 rounded" v-pre>{{name}}</code>,
            <code class="font-mono bg-cream px-1 rounded" v-pre>{{application_number}}</code>).
        </p>

        <div class="flex flex-wrap gap-3 items-end mb-4">
            <div>
                <label class="block text-xs font-medium text-ink mb-1">Filter event</label>
                <select v-model="filterEvent" class="px-3 py-2 text-sm border border-border rounded bg-white">
                    <option value="">All events</option>
                    <option v-for="e in event_keys" :key="e" :value="e">{{ e }}</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-ink mb-1">Filter channel</label>
                <select v-model="filterChannel" class="px-3 py-2 text-sm border border-border rounded bg-white">
                    <option value="">All channels</option>
                    <option v-for="c in channels" :key="c" :value="c">{{ c }}</option>
                </select>
            </div>
            <Button @click="showForm ? cancel() : startCreate()">
                {{ showForm ? 'Cancel' : '+ New Template' }}
            </Button>
        </div>

        <div v-if="showForm" class="bg-white border border-border rounded p-4 mb-6">
            <h3 class="font-serif text-lg text-maroon mb-3">
                {{ editingId ? `Edit Template #${editingId}` : 'New Template' }}
            </h3>
            <form @submit.prevent="submit" class="space-y-3">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-ink mb-1">Event <span class="text-maroon">*</span></label>
                        <select v-model="form.event" :disabled="!!editingId" class="w-full px-3 py-2 text-sm border border-border rounded bg-white disabled:opacity-60">
                            <option v-for="e in event_keys" :key="e" :value="e">{{ e }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-ink mb-1">Channel <span class="text-maroon">*</span></label>
                        <select v-model="form.channel" :disabled="!!editingId" class="w-full px-3 py-2 text-sm border border-border rounded bg-white disabled:opacity-60">
                            <option v-for="c in channels" :key="c" :value="c">{{ c }}</option>
                        </select>
                    </div>
                    <InputText v-model="form.subject" label="Subject (email only)"
                        :error="form.errors.subject" placeholder="Optional for SMS / WhatsApp" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-ink mb-1">Body <span class="text-maroon">*</span></label>
                    <textarea v-model="form.body" rows="6" required
                        class="w-full px-3 py-2 text-sm border border-border rounded font-mono"
                        placeholder="Dear NAME, your application NUMBER is recorded."></textarea>
                    <p v-if="form.errors.body" class="text-xs text-red-600 mt-1">{{ form.errors.body }}</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <InputText v-model="form.dlt_template_id" label="DLT Template ID (SMS, regulatory)"
                        :error="form.errors.dlt_template_id" placeholder="e.g. 1707170000000123" />
                    <InputText v-model="form.whatsapp_template_name" label="WhatsApp Template Name (pre-approved)"
                        :error="form.errors.whatsapp_template_name" placeholder="e.g. application_submitted" />
                </div>
                <Button type="submit" :loading="form.processing">
                    {{ editingId ? 'Save Changes' : 'Create Template' }}
                </Button>
            </form>
        </div>

        <div class="bg-white border border-border rounded overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-cream text-ink-mute text-xs uppercase tracking-wider">
                    <tr>
                        <th class="text-left px-4 py-2">Event</th>
                        <th class="text-left px-4 py-2">Channel</th>
                        <th class="text-left px-4 py-2">Body Preview</th>
                        <th class="text-left px-4 py-2">DLT / WA Template</th>
                        <th class="text-center px-4 py-2 w-16">Active</th>
                        <th class="text-right px-4 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="t in filteredTemplates" :key="t.id" class="border-t border-border align-top">
                        <td class="px-4 py-2 font-mono text-xs">{{ t.event }}</td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-0.5 text-xs rounded" :class="channelBadge(t.channel)">{{ t.channel }}</span>
                        </td>
                        <td class="px-4 py-2 text-xs text-ink-mute max-w-md">
                            <div v-if="t.subject" class="font-semibold text-ink">{{ t.subject }}</div>
                            <div class="whitespace-pre-line">{{ t.body.length > 200 ? t.body.substring(0, 200) + '…' : t.body }}</div>
                        </td>
                        <td class="px-4 py-2 text-xs font-mono text-ink-mute">
                            <div v-if="t.dlt_template_id">DLT: {{ t.dlt_template_id }}</div>
                            <div v-if="t.whatsapp_template_name">WA: {{ t.whatsapp_template_name }}</div>
                            <span v-if="!t.dlt_template_id && !t.whatsapp_template_name">—</span>
                        </td>
                        <td class="px-4 py-2 text-center">
                            <button @click="toggle(t)" class="text-xs">{{ t.is_active ? '✓' : '—' }}</button>
                        </td>
                        <td class="px-4 py-2 text-right space-x-2 whitespace-nowrap">
                            <button @click="startEdit(t)" class="text-xs px-2 py-1 border border-border rounded hover:bg-cream">Edit</button>
                            <button @click="destroy(t)" class="text-xs text-red-600 hover:underline">Delete</button>
                        </td>
                    </tr>
                    <tr v-if="!filteredTemplates.length">
                        <td colspan="6" class="px-4 py-6 text-center text-ink-mute text-sm">No templates match.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </PortalLayout>
</template>
