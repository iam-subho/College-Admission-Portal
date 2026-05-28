<script setup>
import { Head, router } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import InputText from '@/Components/Ui/InputText.vue';
import Button from '@/Components/Ui/Button.vue';
import { ref, reactive } from 'vue';

const props = defineProps({
    policies: { type: Array, required: true },
    sessions: { type: Array, required: true },
    fee_types: { type: Object, required: true },
    ugc_template: { type: Array, required: true },
});

const showForm = ref(false);
const editingId = ref(null);

const blankForm = () => ({
    academic_session_id: '',
    fee_type: 'application',
    name: '',
    deduction_cap: 1000,
    slabs: JSON.parse(JSON.stringify(props.ugc_template)),
});

const form = reactive(blankForm());

const startCreate = () => {
    editingId.value = null;
    Object.assign(form, blankForm());
    showForm.value = true;
};

const startEdit = (p) => {
    editingId.value = p.id;
    form.academic_session_id = p.academic_session_id;
    form.fee_type = p.fee_type;
    form.name = p.name;
    form.deduction_cap = p.deduction_cap ?? '';
    form.slabs = JSON.parse(JSON.stringify(p.slabs || []));
    showForm.value = true;
};

const cancel = () => {
    showForm.value = false;
    editingId.value = null;
    Object.assign(form, blankForm());
};

const addSlab = () => form.slabs.push({ from_days: null, to_days: null, refund_pct: 0, label: '' });
const removeSlab = (i) => form.slabs.splice(i, 1);

const loadUgc = () => { form.slabs = JSON.parse(JSON.stringify(props.ugc_template)); };

const save = () => {
    const opts = { onSuccess: cancel, preserveScroll: true };
    if (editingId.value) {
        router.patch(route('admin.refund-policies.update', editingId.value), form, opts);
    } else {
        router.post(route('admin.refund-policies.store'), form, opts);
    }
};

const remove = (p) => {
    if (!confirm(`Delete policy "${p.name}"?`)) return;
    router.delete(route('admin.refund-policies.destroy', p.id), { preserveScroll: true });
};

const inr = (n) => n != null ? '₹' + Number(n).toLocaleString('en-IN') : '—';
</script>

<template>
    <Head title="Refund Policies" />
    <PortalLayout title="Refund Policies" :breadcrumb="['Admin', 'Refund Policies']">

        <p class="text-xs text-ink-mute mb-4">
            Define per-session refund slabs. <code class="font-mono">from_days</code> /
            <code class="font-mono">to_days</code> are days <em>before session start</em> (positive = before, negative = after).
            UGC-standard slabs are pre-loaded on new policies.
        </p>

        <div class="flex justify-end mb-4">
            <Button @click="showForm ? cancel() : startCreate()">
                {{ showForm ? 'Cancel' : '+ New Policy' }}
            </Button>
        </div>

        <div v-if="showForm" class="bg-white border border-border rounded p-4 mb-6">
            <h3 class="font-serif text-lg text-maroon mb-3">
                {{ editingId ? `Edit Refund Policy #${editingId}` : 'New Refund Policy' }}
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-medium text-ink mb-1">Session <span class="text-maroon">*</span></label>
                    <select v-model="form.academic_session_id" class="w-full px-3 py-2 text-sm border border-border rounded bg-white">
                        <option value="">— Select —</option>
                        <option v-for="s in sessions" :key="s.id" :value="s.id">{{ s.code }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-ink mb-1">Fee Type <span class="text-maroon">*</span></label>
                    <select v-model="form.fee_type" class="w-full px-3 py-2 text-sm border border-border rounded bg-white">
                        <option v-for="(label, key) in fee_types" :key="key" :value="key">{{ label }}</option>
                    </select>
                </div>
                <InputText v-model="form.name" label="Policy Name" placeholder="e.g. UGC 2026-27 Application Fee" />
                <InputText v-model="form.deduction_cap" type="number" step="0.01"
                    label="Deduction Cap (₹, only on 100% slab)" placeholder="UGC: 1000" />
            </div>

            <div class="border-t border-border pt-3">
                <div class="flex justify-between items-center mb-2">
                    <h4 class="text-sm font-semibold text-maroon">Slabs</h4>
                    <div class="space-x-2">
                        <button @click="loadUgc" type="button" class="text-xs px-2 py-1 border border-border rounded hover:bg-cream">
                            Reload UGC Template
                        </button>
                        <button @click="addSlab" type="button" class="text-xs px-2 py-1 border border-dashed border-border rounded hover:bg-cream">
                            + Add Slab
                        </button>
                    </div>
                </div>
                <table class="w-full text-sm">
                    <thead class="text-xs uppercase text-ink-mute">
                        <tr>
                            <th class="text-left py-1 px-2">Label</th>
                            <th class="text-right py-1 px-2 w-24">From Days</th>
                            <th class="text-right py-1 px-2 w-24">To Days</th>
                            <th class="text-right py-1 px-2 w-24">Refund %</th>
                            <th class="w-8"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(s, i) in form.slabs" :key="i" class="border-t border-border">
                            <td class="py-1 px-2">
                                <input v-model="s.label" placeholder="e.g. 15-30 days before"
                                    class="w-full px-2 py-1 text-xs border border-border rounded" />
                            </td>
                            <td class="py-1 px-2">
                                <input v-model.number="s.from_days" type="number" placeholder="null"
                                    class="w-full px-2 py-1 text-xs text-right font-mono border border-border rounded" />
                            </td>
                            <td class="py-1 px-2">
                                <input v-model.number="s.to_days" type="number" placeholder="null"
                                    class="w-full px-2 py-1 text-xs text-right font-mono border border-border rounded" />
                            </td>
                            <td class="py-1 px-2">
                                <input v-model.number="s.refund_pct" type="number" step="0.01" min="0" max="100"
                                    class="w-full px-2 py-1 text-xs text-right font-mono border border-border rounded" />
                            </td>
                            <td class="py-1 px-2 text-center">
                                <button @click="removeSlab(i)" type="button" class="text-xs text-red-600">✕</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                <Button @click="save">{{ editingId ? 'Save Changes' : 'Save Policy' }}</Button>
            </div>
        </div>

        <div class="bg-white border border-border rounded overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-cream text-ink-mute text-xs uppercase tracking-wider">
                    <tr>
                        <th class="text-left px-4 py-2">Session</th>
                        <th class="text-left px-4 py-2">Fee Type</th>
                        <th class="text-left px-4 py-2">Name</th>
                        <th class="text-right px-4 py-2">Slabs</th>
                        <th class="text-right px-4 py-2">Cap</th>
                        <th class="text-left px-4 py-2">Status</th>
                        <th class="text-right px-4 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="p in policies" :key="p.id" class="border-t border-border">
                        <td class="px-4 py-2 font-mono text-xs">{{ p.session?.code }}</td>
                        <td class="px-4 py-2 text-xs">{{ fee_types[p.fee_type] }}</td>
                        <td class="px-4 py-2">{{ p.name }}</td>
                        <td class="px-4 py-2 text-right font-mono">{{ (p.slabs || []).length }}</td>
                        <td class="px-4 py-2 text-right font-mono">{{ inr(p.deduction_cap) }}</td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-0.5 text-xs rounded" :class="p.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100'">
                                {{ p.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-right space-x-2 whitespace-nowrap">
                            <button @click="startEdit(p)" class="text-xs px-2 py-1 border border-border rounded hover:bg-cream">
                                Edit
                            </button>
                            <button @click="remove(p)" class="text-xs text-red-600 hover:underline">Delete</button>
                        </td>
                    </tr>
                    <tr v-if="!policies.length">
                        <td colspan="7" class="px-4 py-6 text-center text-ink-mute text-sm">
                            No refund policies yet. Create one above (UGC slabs pre-loaded).
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </PortalLayout>
</template>
