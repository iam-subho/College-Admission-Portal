<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import InputText from '@/Components/Ui/InputText.vue';
import Button from '@/Components/Ui/Button.vue';
import { ref } from 'vue';

defineProps({
    departments: { type: Array, required: true },
});

const showForm = ref(false);
const editingId = ref(null);

const blankForm = () => ({
    code: '',
    name: '',
    head_of_dept: '',
    description: '',
});

const form = useForm(blankForm());

const startCreate = () => { editingId.value = null; form.reset(); showForm.value = true; };

const startEdit = (d) => {
    editingId.value = d.id;
    form.code = d.code;
    form.name = d.name;
    form.head_of_dept = d.head_of_dept ?? '';
    form.description = d.description ?? '';
    showForm.value = true;
};

const cancel = () => { showForm.value = false; editingId.value = null; form.reset(); form.clearErrors(); };

const submit = () => {
    const opts = { onSuccess: cancel };
    if (editingId.value) {
        form.patch(route('admin.departments.update', editingId.value), opts);
    } else {
        form.post(route('admin.departments.store'), opts);
    }
};

const destroy = (id) => {
    if (confirm('Remove this department?')) {
        router.delete(route('admin.departments.destroy', id));
    }
};
</script>

<template>
    <Head title="Departments" />
    <PortalLayout title="Departments / Schools" :breadcrumb="['Admin', 'Departments']">
        <div class="flex justify-end mb-4">
            <Button @click="showForm ? cancel() : startCreate()">
                {{ showForm ? 'Cancel' : '+ New Department' }}
            </Button>
        </div>

        <div v-if="showForm" class="bg-white border border-border rounded p-4 mb-6">
            <h3 class="font-serif text-lg text-maroon mb-3">
                {{ editingId ? `Edit Department #${editingId}` : 'New Department' }}
            </h3>
            <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <InputText v-model="form.code" label="Code" required :error="form.errors.code" placeholder="e.g. COMP" />
                <InputText v-model="form.name" label="Name" required :error="form.errors.name" placeholder="e.g. School of Computing" />
                <InputText v-model="form.head_of_dept" label="Head of Department" :error="form.errors.head_of_dept" />
                <InputText v-model="form.description" label="Description" :error="form.errors.description" />
                <div class="md:col-span-2">
                    <Button type="submit" :loading="form.processing">
                        {{ editingId ? 'Save Changes' : 'Create Department' }}
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
                        <th class="text-left px-4 py-2">Head</th>
                        <th class="text-right px-4 py-2">Programmes</th>
                        <th class="text-right px-4 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="d in departments" :key="d.id" class="border-t border-border">
                        <td class="px-4 py-2 font-mono">{{ d.code }}</td>
                        <td class="px-4 py-2">{{ d.name }}</td>
                        <td class="px-4 py-2">{{ d.head_of_dept || '—' }}</td>
                        <td class="px-4 py-2 text-right">{{ d.programs_count }}</td>
                        <td class="px-4 py-2 text-right space-x-2 whitespace-nowrap">
                            <button @click="startEdit(d)" class="text-xs px-2 py-1 border border-border rounded hover:bg-cream">
                                Edit
                            </button>
                            <button @click="destroy(d.id)" class="text-xs text-red-600 hover:underline">Remove</button>
                        </td>
                    </tr>
                    <tr v-if="!departments.length">
                        <td colspan="5" class="px-4 py-6 text-center text-ink-mute text-sm">No departments yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </PortalLayout>
</template>
