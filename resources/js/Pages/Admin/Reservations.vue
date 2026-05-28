<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import InputText from '@/Components/Ui/InputText.vue';
import Button from '@/Components/Ui/Button.vue';
import { ref } from 'vue';

defineProps({
    categories: { type: Array, required: true },
});

const showForm = ref(false);
const editingId = ref(null);

const blankForm = () => ({
    code: '',
    name: '',
    is_horizontal: false,
    default_percentage: '',
    ordering: 10,
});

const form = useForm(blankForm());

const startCreate = () => { editingId.value = null; form.reset(); showForm.value = true; };

const startEdit = (c) => {
    editingId.value = c.id;
    form.code = c.code;
    form.name = c.name;
    form.is_horizontal = !!c.is_horizontal;
    form.default_percentage = c.default_percentage ?? '';
    form.ordering = c.ordering ?? 10;
    showForm.value = true;
};

const cancel = () => { showForm.value = false; editingId.value = null; form.reset(); form.clearErrors(); };

const submit = () => {
    const opts = { onSuccess: cancel };
    if (editingId.value) {
        form.patch(route('admin.reservation-categories.update', editingId.value), opts);
    } else {
        form.post(route('admin.reservation-categories.store'), opts);
    }
};

const toggle = (id) => router.post(route('admin.reservation-categories.toggle', id), {}, { preserveScroll: true });

const destroy = (c) => {
    if (!confirm(`Delete reservation category "${c.code} · ${c.name}"? This cannot be undone.`)) return;
    router.delete(route('admin.reservation-categories.destroy', c.id), { preserveScroll: true });
};
</script>

<template>
    <Head title="Reservation Categories" />
    <PortalLayout title="Reservation Categories" :breadcrumb="['Admin', 'Reservations']">
        <p class="text-sm text-ink-mute mb-4">
            Disabled categories will not appear in the reservation matrix editor or in student application forms.
            <strong>Vertical</strong> categories partition the intake (SC / ST / OBC-NCL / EWS / UR);
            <strong>Horizontal</strong> categories overlay across all verticals (PwD / Defence / Sports / Single Girl Child).
        </p>

        <div class="flex justify-end mb-4">
            <Button @click="showForm ? cancel() : startCreate()">
                {{ showForm ? 'Cancel' : '+ New Category' }}
            </Button>
        </div>

        <div v-if="showForm" class="bg-white border border-border rounded p-4 mb-6">
            <h3 class="font-serif text-lg text-maroon mb-3">
                {{ editingId ? `Edit Category #${editingId}` : 'New Reservation Category' }}
            </h3>
            <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <InputText v-model="form.code" label="Code" required :error="form.errors.code" placeholder="e.g. SC" />
                <div class="md:col-span-2">
                    <InputText v-model="form.name" label="Name" required :error="form.errors.name" placeholder="e.g. Scheduled Caste" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-ink mb-1">Kind <span class="text-maroon">*</span></label>
                    <select v-model="form.is_horizontal" class="w-full px-3 py-2 text-sm border border-border rounded bg-white">
                        <option :value="false">Vertical</option>
                        <option :value="true">Horizontal</option>
                    </select>
                </div>
                <InputText v-model="form.default_percentage" type="number" step="0.01" label="Default Percentage"
                    :error="form.errors.default_percentage" placeholder="e.g. 15.00" />
                <InputText v-model="form.ordering" type="number" label="Display Order"
                    :error="form.errors.ordering" />
                <div class="md:col-span-3">
                    <Button type="submit" :loading="form.processing">
                        {{ editingId ? 'Save Changes' : 'Create Category' }}
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
                        <th class="text-left px-4 py-2">Kind</th>
                        <th class="text-right px-4 py-2">Default %</th>
                        <th class="text-right px-4 py-2">Order</th>
                        <th class="text-right px-4 py-2">Status</th>
                        <th class="text-right px-4 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="c in categories" :key="c.id" class="border-t border-border">
                        <td class="px-4 py-2 font-mono">{{ c.code }}</td>
                        <td class="px-4 py-2">{{ c.name }}</td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-0.5 text-xs rounded"
                                :class="c.is_horizontal ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800'">
                                {{ c.is_horizontal ? 'Horizontal' : 'Vertical' }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-right font-mono">{{ c.default_percentage ?? '—' }}</td>
                        <td class="px-4 py-2 text-right font-mono text-xs">{{ c.ordering }}</td>
                        <td class="px-4 py-2 text-right">
                            <button @click="toggle(c.id)"
                                class="text-xs px-2 py-1 rounded"
                                :class="c.is_active ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                                {{ c.is_active ? 'Enabled' : 'Disabled' }}
                            </button>
                        </td>
                        <td class="px-4 py-2 text-right space-x-2 whitespace-nowrap">
                            <button @click="startEdit(c)" class="text-xs px-2 py-1 border border-border rounded hover:bg-cream">
                                Edit
                            </button>
                            <button @click="destroy(c)" class="text-xs text-red-600 hover:underline">
                                Delete
                            </button>
                        </td>
                    </tr>
                    <tr v-if="!categories.length">
                        <td colspan="7" class="px-4 py-6 text-center text-ink-mute text-sm">No reservation categories yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </PortalLayout>
</template>
