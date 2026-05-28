<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import { ref } from 'vue';
import { formatDate } from '@/utils/date.js';

const props = defineProps({
    notices: { type: Array, required: true },
    tabs: { type: Array, required: true },
});

const showForm = ref(false);
const editingId = ref(null);

const form = useForm({
    notice_date: new Date().toISOString().slice(0, 10),
    title: '',
    tab: 'latest',
    url: '',
    sort_order: 0,
    is_active: true,
});

const openCreate = () => {
    editingId.value = null;
    form.reset();
    form.notice_date = new Date().toISOString().slice(0, 10);
    form.tab = 'latest';
    form.is_active = true;
    showForm.value = true;
};

const openEdit = (n) => {
    editingId.value = n.id;
    form.notice_date = n.notice_date?.slice(0, 10) || '';
    form.title = n.title;
    form.tab = n.tab;
    form.url = n.url || '';
    form.sort_order = n.sort_order;
    form.is_active = n.is_active;
    showForm.value = true;
};

const submit = () => {
    if (editingId.value) {
        form.patch(route('admin.notices.update', editingId.value), {
            preserveScroll: true,
            onSuccess: () => { showForm.value = false; },
        });
    } else {
        form.post(route('admin.notices.store'), {
            preserveScroll: true,
            onSuccess: () => { showForm.value = false; },
        });
    }
};

const toggle = (n) => router.post(route('admin.notices.toggle', n.id), {}, { preserveScroll: true });

const remove = (n) => {
    if (confirm(`Delete notice "${n.title}"?`)) {
        router.delete(route('admin.notices.destroy', n.id), { preserveScroll: true });
    }
};

const tabBadge = (t) => ({
    latest: 'bg-blue-100 text-blue-800',
    admissions: 'bg-saffron-soft text-maroon',
    examination: 'bg-purple-100 text-purple-800',
}[t] || 'bg-gray-100');
</script>

<template>
    <Head title="Notices" />
    <PortalLayout title="Notice Board" :breadcrumb="['Admin', 'Notices']">
        <div class="flex justify-between items-center mb-3">
            <p class="text-sm text-ink-mute">
                Notices shown on the public homepage Notice Board. Active notices appear in the chosen tab.
            </p>
            <button @click="openCreate" class="px-3 py-1.5 bg-saffron text-white rounded text-sm font-semibold hover:bg-saffron/90">
                + Add Notice
            </button>
        </div>

        <div v-if="showForm" class="bg-white border-2 border-saffron rounded mb-4 p-4">
            <h3 class="font-serif text-base text-maroon mb-3">{{ editingId ? 'Edit Notice' : 'New Notice' }}</h3>
            <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-12 gap-3">
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-ink mb-1">Date</label>
                    <input v-model="form.notice_date" type="date" class="w-full px-2 py-1.5 text-sm border border-border rounded" required />
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-ink mb-1">Tab</label>
                    <select v-model="form.tab" class="w-full px-2 py-1.5 text-sm border border-border rounded bg-white">
                        <option v-for="t in tabs" :key="t" :value="t">{{ t }}</option>
                    </select>
                </div>
                <div class="md:col-span-6">
                    <label class="block text-xs font-medium text-ink mb-1">Title</label>
                    <input v-model="form.title" type="text" maxlength="255" class="w-full px-2 py-1.5 text-sm border border-border rounded" required />
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-ink mb-1">Sort Order</label>
                    <input v-model.number="form.sort_order" type="number" min="0" max="9999" class="w-full px-2 py-1.5 text-sm border border-border rounded" />
                </div>
                <div class="md:col-span-9">
                    <label class="block text-xs font-medium text-ink mb-1">Link URL (optional)</label>
                    <input v-model="form.url" type="url" maxlength="500" placeholder="https://…" class="w-full px-2 py-1.5 text-sm border border-border rounded" />
                </div>
                <div class="md:col-span-3 flex items-end">
                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="form.is_active" type="checkbox" class="accent-saffron" />
                        Active (visible on homepage)
                    </label>
                </div>
                <div class="md:col-span-12 flex gap-2">
                    <button type="submit" :disabled="form.processing"
                        class="px-4 py-1.5 bg-maroon text-white rounded text-sm font-semibold hover:bg-maroon-deep disabled:opacity-60">
                        {{ form.processing ? 'Saving…' : (editingId ? 'Update' : 'Create') }}
                    </button>
                    <button type="button" @click="showForm = false" class="px-4 py-1.5 border border-border rounded text-sm hover:bg-cream">
                        Cancel
                    </button>
                </div>
            </form>
            <p v-for="(err, k) in form.errors" :key="k" class="text-xs text-red-600 mt-1">{{ err }}</p>
        </div>

        <div class="bg-white border border-border rounded overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-cream text-ink-mute text-xs uppercase tracking-wider">
                    <tr>
                        <th class="text-left px-3 py-2 w-28">Date</th>
                        <th class="text-left px-3 py-2 w-28">Tab</th>
                        <th class="text-left px-3 py-2">Title</th>
                        <th class="text-left px-3 py-2 w-20">Order</th>
                        <th class="text-left px-3 py-2 w-24">Status</th>
                        <th class="text-right px-3 py-2 w-44">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="n in notices" :key="n.id" class="border-t border-border hover:bg-cream/50">
                        <td class="px-3 py-2 text-xs font-mono">{{ formatDate(n.notice_date) }}</td>
                        <td class="px-3 py-2">
                            <span class="px-2 py-0.5 text-xs rounded font-mono" :class="tabBadge(n.tab)">{{ n.tab }}</span>
                        </td>
                        <td class="px-3 py-2">
                            <div>{{ n.title }}</div>
                            <a v-if="n.url" :href="n.url" target="_blank" class="text-[10px] text-ink-mute hover:text-maroon">{{ n.url }}</a>
                        </td>
                        <td class="px-3 py-2 text-xs font-mono">{{ n.sort_order }}</td>
                        <td class="px-3 py-2">
                            <span class="px-2 py-0.5 text-xs rounded font-mono"
                                :class="n.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'">
                                {{ n.is_active ? 'ACTIVE' : 'HIDDEN' }}
                            </span>
                        </td>
                        <td class="px-3 py-2 text-right text-xs">
                            <button @click="openEdit(n)" class="px-2 py-1 text-maroon hover:underline">Edit</button>
                            <button @click="toggle(n)" class="px-2 py-1 text-ink-mute hover:underline">{{ n.is_active ? 'Hide' : 'Publish' }}</button>
                            <button @click="remove(n)" class="px-2 py-1 text-red-600 hover:underline">Delete</button>
                        </td>
                    </tr>
                    <tr v-if="!notices.length">
                        <td colspan="6" class="px-3 py-6 text-center text-ink-mute text-sm">No notices yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </PortalLayout>
</template>
