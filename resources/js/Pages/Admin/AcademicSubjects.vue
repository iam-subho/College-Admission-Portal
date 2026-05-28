<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import InputText from '@/Components/Ui/InputText.vue';
import Button from '@/Components/Ui/Button.vue';
import { ref, watch } from 'vue';

const props = defineProps({
    subjects: { type: Object, required: true },
    filters: { type: Object, required: true },
    counts: { type: Object, default: () => ({}) },
    streams: { type: Array, required: true },
});

const showForm = ref(false);
const editingId = ref(null);

const blankForm = () => ({
    code: '',
    name: '',
    level: '12th',
    stream: 'Common',
    is_language: false,
    ordering: 100,
});
const form = useForm(blankForm());

const startCreate = () => { editingId.value = null; form.reset(); showForm.value = true; };
const startEdit = (s) => {
    editingId.value = s.id;
    form.code = s.code;
    form.name = s.name;
    form.level = s.level;
    form.stream = s.stream ?? '';
    form.is_language = !!s.is_language;
    form.ordering = s.ordering ?? 100;
    showForm.value = true;
};
const cancel = () => { showForm.value = false; editingId.value = null; form.reset(); form.clearErrors(); };

const submit = () => {
    const opts = { onSuccess: cancel };
    if (editingId.value) {
        form.patch(route('admin.academic-subjects.update', editingId.value), opts);
    } else {
        form.post(route('admin.academic-subjects.store'), opts);
    }
};

const toggle = (s) => router.post(route('admin.academic-subjects.toggle', s.id), {}, { preserveScroll: true });
const destroy = (s) => {
    if (!confirm(`Delete subject "${s.code} · ${s.name}"?`)) return;
    router.delete(route('admin.academic-subjects.destroy', s.id), { preserveScroll: true });
};

// Filter bar
const q = ref(props.filters?.q || '');
const level = ref(props.filters?.level || '');
const stream = ref(props.filters?.stream || '');

const applyFilters = () => {
    // Build params, omitting empty values so the URL stays clean.
    const params = {};
    if (q.value) params.q = q.value;
    if (level.value) params.level = level.value;
    if (stream.value) params.stream = stream.value;

    router.get(route('admin.academic-subjects.index'), params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

let timer = null;
watch([q, level, stream], () => {
    clearTimeout(timer);
    timer = setTimeout(applyFilters, 300);
});

const LEVELS = [
    { key: '10th', label: 'Class 10' },
    { key: '12th', label: 'Class 12' },
    { key: 'ug', label: 'UG' },
];
</script>

<template>
    <Head title="Academic Subjects" />
    <PortalLayout title="Academic Subjects" :breadcrumb="['Admin', 'Academic Subjects']">
        <p class="text-sm text-ink-mute mb-3">
            Master list of subjects offered at Class 10 / Class 12 / UG. Students pick from this list in their
            academic records form; eligibility rules match on these canonical names. Disable a row to remove it from dropdowns
            without losing existing references.
        </p>

        <div class="grid grid-cols-3 md:grid-cols-6 gap-2 mb-4">
            <div v-for="lvl in LEVELS" :key="lvl.key" class="bg-white border border-border rounded p-2 text-center">
                <div class="text-[10px] uppercase tracking-wider text-ink-mute">{{ lvl.label }}</div>
                <div class="font-mono font-semibold text-maroon">{{ counts[lvl.key] || 0 }}</div>
            </div>
        </div>

        <div class="flex flex-wrap items-end gap-3 mb-4">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-ink mb-1">Search (name / code)</label>
                <input v-model="q" placeholder="e.g. Mathematics or MATH"
                    class="w-full px-3 py-2 text-sm border border-border rounded" />
            </div>
            <div>
                <label class="block text-xs font-medium text-ink mb-1">Level</label>
                <select v-model="level" class="px-3 py-2 text-sm border border-border rounded bg-white">
                    <option value="">All</option>
                    <option value="10th">Class 10</option>
                    <option value="12th">Class 12</option>
                    <option value="ug">UG</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-ink mb-1">Stream</label>
                <select v-model="stream" class="px-3 py-2 text-sm border border-border rounded bg-white">
                    <option value="">All</option>
                    <option v-for="s in streams" :key="s" :value="s">{{ s }}</option>
                </select>
            </div>
            <Button @click="showForm ? cancel() : startCreate()">
                {{ showForm ? 'Cancel' : '+ New Subject' }}
            </Button>
        </div>

        <div v-if="showForm" class="bg-white border border-border rounded p-4 mb-6">
            <h3 class="font-serif text-lg text-maroon mb-3">
                {{ editingId ? `Edit Subject #${editingId}` : 'New Subject' }}
            </h3>
            <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <InputText v-model="form.code" label="Code" required :error="form.errors.code"
                    placeholder="e.g. MATH (case-sensitive identifier)" />
                <div class="md:col-span-2">
                    <InputText v-model="form.name" label="Display Name" required :error="form.errors.name"
                        placeholder="e.g. Mathematics" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-ink mb-1">Level <span class="text-maroon">*</span></label>
                    <select v-model="form.level" class="w-full px-3 py-2 text-sm border border-border rounded bg-white">
                        <option value="10th">Class 10</option>
                        <option value="12th">Class 12</option>
                        <option value="ug">UG</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-ink mb-1">Stream</label>
                    <select v-model="form.stream" class="w-full px-3 py-2 text-sm border border-border rounded bg-white">
                        <option value="">None</option>
                        <option v-for="s in streams" :key="s" :value="s">{{ s }}</option>
                    </select>
                </div>
                <InputText v-model="form.ordering" type="number" label="Display Order" />
                <div class="md:col-span-3">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" v-model="form.is_language" />
                        <span>This is a language subject</span>
                    </label>
                </div>
                <div class="md:col-span-3">
                    <Button type="submit" :loading="form.processing">
                        {{ editingId ? 'Save Changes' : 'Create Subject' }}
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
                        <th class="text-left px-4 py-2">Level</th>
                        <th class="text-left px-4 py-2">Stream</th>
                        <th class="text-center px-4 py-2">Lang</th>
                        <th class="text-right px-4 py-2">Order</th>
                        <th class="text-right px-4 py-2">Status</th>
                        <th class="text-right px-4 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="s in subjects.data" :key="s.id" class="border-t border-border">
                        <td class="px-4 py-2 font-mono text-xs">{{ s.code }}</td>
                        <td class="px-4 py-2">{{ s.name }}</td>
                        <td class="px-4 py-2 text-xs font-mono">{{ s.level }}</td>
                        <td class="px-4 py-2 text-xs">{{ s.stream || '—' }}</td>
                        <td class="px-4 py-2 text-center text-xs">{{ s.is_language ? '✓' : '—' }}</td>
                        <td class="px-4 py-2 text-right font-mono text-xs">{{ s.ordering }}</td>
                        <td class="px-4 py-2 text-right">
                            <button @click="toggle(s)" class="text-xs px-2 py-1 rounded"
                                :class="s.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700'">
                                {{ s.is_active ? 'Active' : 'Disabled' }}
                            </button>
                        </td>
                        <td class="px-4 py-2 text-right space-x-2 whitespace-nowrap">
                            <button @click="startEdit(s)" class="text-xs px-2 py-1 border border-border rounded hover:bg-cream">Edit</button>
                            <button @click="destroy(s)" class="text-xs text-red-600 hover:underline">Delete</button>
                        </td>
                    </tr>
                    <tr v-if="!subjects.data.length">
                        <td colspan="8" class="px-4 py-6 text-center text-ink-mute text-sm">No subjects match the filter.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="subjects.last_page > 1" class="mt-4 flex items-center justify-center gap-1 text-sm">
            <template v-for="(l, i) in subjects.links" :key="i">
                <Link v-if="l.url" :href="l.url" v-html="l.label"
                    class="px-3 py-1 rounded border"
                    :class="l.active ? 'bg-maroon text-white border-maroon' : 'border-border hover:bg-cream'" />
                <span v-else v-html="l.label"
                    class="px-3 py-1 rounded border border-border text-ink-mute opacity-50 cursor-not-allowed" />
            </template>
        </div>
    </PortalLayout>
</template>
