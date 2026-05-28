<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import InputText from '@/Components/Ui/InputText.vue';
import Button from '@/Components/Ui/Button.vue';
import { computed, ref } from 'vue';

const props = defineProps({
    programmes: { type: Array, required: true },
    pools: { type: Array, required: true },
    selected_program_id: { type: [Number, String, null], default: null },
    categories: { type: Object, required: true },
});

const switchProgramme = (e) => {
    router.get('/admin/course-pools', { program_id: e.target.value }, { preserveState: false });
};

const form = useForm({
    program_id: props.selected_program_id,
    category: 'minor',
    course_code: '',
    course_name: '',
    credits: '',
    is_default: false,
    ordering: 10,
    description: '',
});

const addCourse = () => form.post('/admin/course-pools', {
    preserveScroll: true,
    onSuccess: () => form.reset('course_code', 'course_name', 'credits', 'description'),
});

const removeCourse = (id) => router.delete(`/admin/course-pools/${id}`, { preserveScroll: true });

const grouped = computed(() => {
    const out = {};
    for (const p of props.pools) {
        out[p.category] ??= [];
        out[p.category].push(p);
    }
    return out;
});

const categoryKeys = computed(() => Object.keys(props.categories));
</script>

<template>
    <Head title="Course Pools (NEP 2020)" />
    <PortalLayout title="Course Pools (NEP 2020)" :breadcrumb="['Admin', 'Course Pools']">
        <div class="bg-white border border-border rounded p-4 mb-6">
            <label class="block text-xs font-medium text-ink mb-1">Programme</label>
            <select :value="selected_program_id" @change="switchProgramme"
                class="px-3 py-2 text-sm border border-border rounded">
                <option v-for="p in programmes" :key="p.id" :value="p.id">
                    {{ p.code }} · {{ p.name }} ({{ p.type }})
                </option>
            </select>
            <p class="text-xs text-ink-mute mt-2">
                Define the pool of subjects students can pick from in each NEP 2020 category.
                Students pick one (or more) from each category when applying.
            </p>
        </div>

        <!-- Existing pools grouped by category -->
        <section v-for="cat in categoryKeys" :key="cat" class="bg-white border border-border rounded mb-4">
            <header class="px-4 py-2 border-b border-border bg-cream flex justify-between items-center">
                <h2 class="font-serif text-base text-maroon">{{ categories[cat] }}</h2>
                <span class="text-xs text-ink-mute font-mono uppercase">{{ cat }}</span>
            </header>
            <table v-if="grouped[cat]?.length" class="w-full text-sm">
                <thead class="text-xs uppercase text-ink-mute">
                    <tr>
                        <th class="text-left px-4 py-1">Code</th>
                        <th class="text-left px-4 py-1">Course Name</th>
                        <th class="text-center px-4 py-1">Credits</th>
                        <th class="text-center px-4 py-1">Default</th>
                        <th class="text-center px-4 py-1">Active</th>
                        <th class="px-4 py-1"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="p in grouped[cat]" :key="p.id" class="border-t border-border">
                        <td class="px-4 py-2 font-mono text-xs">{{ p.course_code || '—' }}</td>
                        <td class="px-4 py-2">{{ p.course_name }}</td>
                        <td class="px-4 py-2 text-center font-mono">{{ p.credits || '—' }}</td>
                        <td class="px-4 py-2 text-center">
                            <span v-if="p.is_default" class="text-xs text-green-700 font-semibold">✓</span>
                            <span v-else class="text-xs text-ink-mute">—</span>
                        </td>
                        <td class="px-4 py-2 text-center">
                            <span v-if="p.is_active" class="text-xs text-green-700 font-semibold">●</span>
                            <span v-else class="text-xs text-ink-mute">○</span>
                        </td>
                        <td class="px-4 py-2 text-right">
                            <button @click="removeCourse(p.id)" class="text-xs text-red-600 hover:underline">Remove</button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p v-else class="px-4 py-3 text-xs text-ink-mute">No courses configured in this pool yet.</p>
        </section>

        <!-- Add new course form -->
        <section class="bg-white border-2 border-saffron rounded">
            <header class="px-4 py-2 border-b border-border bg-saffron-soft">
                <h2 class="font-serif text-base text-maroon">Add Course to Pool</h2>
            </header>
            <form @submit.prevent="addCourse" class="p-4 grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-medium text-ink mb-1">Category <span class="text-maroon">*</span></label>
                    <select v-model="form.category" class="w-full px-3 py-2 text-sm border border-border rounded">
                        <option v-for="(label, code) in categories" :key="code" :value="code">{{ label }}</option>
                    </select>
                </div>
                <InputText v-model="form.course_code" label="Course Code" placeholder="MATH-101" :error="form.errors.course_code" />
                <InputText v-model="form.course_name" label="Course Name" required :error="form.errors.course_name" />
                <InputText v-model="form.credits" type="number" label="Credits" :error="form.errors.credits" />
                <div class="md:col-span-3">
                    <label class="block text-xs font-medium text-ink mb-1">Description (optional)</label>
                    <input v-model="form.description" class="w-full px-3 py-2 text-sm border border-border rounded" />
                </div>
                <div class="flex items-end">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" v-model="form.is_default" />
                        Default (auto-picked)
                    </label>
                </div>
                <div class="md:col-span-4 flex justify-end">
                    <Button type="submit" :loading="form.processing" :disabled="!form.course_name">+ Add to Pool</Button>
                </div>
            </form>
        </section>
    </PortalLayout>
</template>
