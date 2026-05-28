<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import { reactive } from 'vue';

const props = defineProps({
    groups: { type: Object, required: true },
});

const initial = {};
for (const group of Object.values(props.groups)) {
    for (const s of group) initial[s.key] = s.value ?? '';
}

const form = useForm({ values: reactive({ ...initial }) });

const submit = () => form.post(route('admin.site-settings.update'), { preserveScroll: true });

const groupLabel = (g) => ({
    identity: 'College Identity',
    homepage: 'Homepage Content',
    contact: 'Contact & Helpdesk',
    general: 'General',
}[g] || g);
</script>

<template>
    <Head title="Site Settings" />
    <PortalLayout title="Site Settings" :breadcrumb="['Admin', 'Site Settings']">
        <p class="text-sm text-ink-mute mb-4">
            Manage homepage content, contact details, and college identity shown on the public site.
            Changes go live immediately after saving.
        </p>

        <form @submit.prevent="submit" class="space-y-6">
            <section v-for="(items, group) in groups" :key="group" class="bg-white border border-border rounded">
                <header class="px-4 py-2 bg-cream border-b border-border">
                    <h3 class="font-serif text-base text-maroon">{{ groupLabel(group) }}</h3>
                </header>
                <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div v-for="s in items" :key="s.key">
                        <label class="block text-xs font-medium text-ink mb-1">
                            {{ s.label }}
                            <span class="text-ink-mute font-mono text-[10px]">· {{ s.key }}</span>
                        </label>
                        <textarea v-if="s.input_type === 'textarea'"
                            v-model="form.values[s.key]"
                            rows="3"
                            class="w-full px-2 py-1.5 text-sm border border-border rounded" />
                        <input v-else
                            v-model="form.values[s.key]"
                            :type="s.input_type"
                            class="w-full px-2 py-1.5 text-sm border border-border rounded" />
                        <p v-if="s.help" class="text-[10px] text-ink-mute mt-1">{{ s.help }}</p>
                    </div>
                </div>
            </section>

            <div class="flex gap-2">
                <button type="submit" :disabled="form.processing"
                    class="px-5 py-2 bg-maroon text-white rounded text-sm font-semibold hover:bg-maroon-deep disabled:opacity-60">
                    {{ form.processing ? 'Saving…' : 'Save Site Settings' }}
                </button>
            </div>
            <p v-for="(err, k) in form.errors" :key="k" class="text-xs text-red-600">{{ err }}</p>
        </form>
    </PortalLayout>
</template>
