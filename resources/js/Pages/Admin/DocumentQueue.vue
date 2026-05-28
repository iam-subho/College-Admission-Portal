<script setup>
import { Head, router } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import InputText from '@/Components/Ui/InputText.vue';
import Button from '@/Components/Ui/Button.vue';
import { ref, watch } from 'vue';

const props = defineProps({
    documents: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const q = ref(props.filters.q || '');
const status = ref(props.filters.status || '');
const source = ref(props.filters.source || '');

const selected = ref(new Set());
const rejectingId = ref(null);
const rejectReason = ref('');

const apply = () => router.get('/admin/documents', { q: q.value, status: status.value, source: source.value }, { preserveState: true });

let timer = null;
watch([q, status, source], () => {
    clearTimeout(timer);
    timer = setTimeout(apply, 350);
});

const toggle = (id) => {
    if (selected.value.has(id)) selected.value.delete(id);
    else selected.value.add(id);
};

const bulkApprove = () => {
    if (selected.value.size === 0) return;
    if (! confirm(`Approve ${selected.value.size} document(s)?`)) return;
    router.post('/admin/documents/bulk-approve',
        { document_ids: [...selected.value] },
        { preserveScroll: true, onSuccess: () => { selected.value.clear(); } },
    );
};

const approve = (id) => router.post(`/admin/documents/${id}/approve`, {}, { preserveScroll: true });

const reject = (id) => {
    if (! rejectReason.value.trim()) return;
    router.post(`/admin/documents/${id}/reject`,
        { reason: rejectReason.value },
        { preserveScroll: true, onSuccess: () => { rejectingId.value = null; rejectReason.value = ''; } },
    );
};
</script>

<template>
    <Head title="Document Queue" />
    <PortalLayout title="Document Verification Queue" :breadcrumb="['Admin', 'Documents']">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
            <InputText v-model="q" label="Search (app no / name / email)" />
            <div>
                <label class="block text-xs font-medium text-ink mb-1">Status</label>
                <select v-model="status" class="w-full px-3 py-2 text-sm border border-border rounded">
                    <option value="">All</option>
                    <option>pending</option>
                    <option>approved</option>
                    <option>rejected</option>
                    <option>resubmit</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-ink mb-1">Source</label>
                <select v-model="source" class="w-full px-3 py-2 text-sm border border-border rounded">
                    <option value="">All</option>
                    <option>manual</option>
                    <option>digilocker</option>
                </select>
            </div>
            <div class="flex items-end">
                <Button @click="bulkApprove" :disabled="selected.size === 0">
                    Bulk Approve ({{ selected.size }})
                </Button>
            </div>
        </div>

        <div class="bg-white border border-border rounded overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-cream text-ink-mute text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-2 w-8"></th>
                        <th class="text-left px-4 py-2">App No.</th>
                        <th class="text-left px-4 py-2">Applicant</th>
                        <th class="text-left px-4 py-2">Document</th>
                        <th class="text-left px-4 py-2">Source</th>
                        <th class="text-left px-4 py-2">Status</th>
                        <th class="text-right px-4 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="d in documents.data" :key="d.id">
                        <tr class="border-t border-border">
                            <td class="px-4 py-2">
                                <input type="checkbox" :checked="selected.has(d.id)" @change="toggle(d.id)" class="accent-saffron" />
                            </td>
                            <td class="px-4 py-2 font-mono text-xs">{{ d.application?.application_number || '—' }}</td>
                            <td class="px-4 py-2">
                                {{ d.student?.user?.name }}
                                <div class="text-xs text-ink-mute">{{ d.student?.user?.email }}</div>
                            </td>
                            <td class="px-4 py-2">
                                <div>{{ d.type?.label }}</div>
                                <a :href="`/documents/${d.id}/download`" class="text-xs text-maroon hover:underline">View file</a>
                            </td>
                            <td class="px-4 py-2 text-xs">
                                <span class="px-2 py-0.5 rounded" :class="d.source === 'digilocker' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-700'">
                                    {{ d.source }}
                                </span>
                            </td>
                            <td class="px-4 py-2">
                                <span class="px-2 py-0.5 text-xs rounded"
                                    :class="{
                                        'bg-gray-100': d.status === 'pending',
                                        'bg-green-100 text-green-800': d.status === 'approved',
                                        'bg-red-100 text-red-800': d.status === 'rejected',
                                    }">{{ d.status }}</span>
                            </td>
                            <td class="px-4 py-2 text-right space-x-2">
                                <button v-if="d.status !== 'approved'" @click="approve(d.id)" class="text-xs px-2 py-1 bg-saffron text-white rounded">Approve</button>
                                <button v-if="d.status !== 'rejected'" @click="rejectingId = d.id; rejectReason = ''" class="text-xs px-2 py-1 border border-red-300 text-red-700 rounded">Reject</button>
                            </td>
                        </tr>
                        <tr v-if="rejectingId === d.id" class="bg-red-50">
                            <td colspan="7" class="px-4 py-2">
                                <div class="flex gap-2 items-center">
                                    <input v-model="rejectReason" placeholder="Reason for rejection (required)" class="flex-1 px-3 py-1.5 text-sm border border-border rounded" />
                                    <button @click="reject(d.id)" :disabled="!rejectReason.trim()" class="px-3 py-1.5 text-sm bg-red-600 text-white rounded disabled:opacity-50">Confirm Reject</button>
                                    <button @click="rejectingId = null" class="px-3 py-1.5 text-sm border border-border rounded">Cancel</button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr v-if="!documents.data.length">
                        <td colspan="7" class="px-4 py-6 text-center text-ink-mute text-sm">No documents match.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </PortalLayout>
</template>
