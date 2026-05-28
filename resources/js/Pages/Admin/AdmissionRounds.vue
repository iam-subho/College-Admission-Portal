<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import InputText from '@/Components/Ui/InputText.vue';
import Button from '@/Components/Ui/Button.vue';
import { ref } from 'vue';

defineProps({
    rounds: { type: Array, required: true },
    programs: { type: Array, required: true },
    sessions: { type: Array, required: true },
});

const showForm = ref(false);
const form = useForm({
    academic_session_id: '',
    program_id: '',
    round_number: 1,
    name: '',
});
const create = () => form.post(route('admin.rounds.store'), {
    onSuccess: () => { showForm.value = false; form.reset(); },
});

const statusBadge = (s) => ({
    planning: 'bg-gray-100 text-gray-700',
    open: 'bg-blue-100 text-blue-800',
    merit_drafted: 'bg-amber-100 text-amber-800',
    merit_published: 'bg-green-100 text-green-800',
    closed: 'bg-gray-200 text-gray-700',
    locked: 'bg-red-100 text-red-800',
}[s] || 'bg-gray-100');

const updateStatus = (r, status) => router.patch(route('admin.rounds.status.update', r.id), { status }, { preserveScroll: true });
const destroy = (r) => {
    if (!confirm(`Delete round "${r.name}"? This cannot be undone.`)) return;
    router.delete(route('admin.rounds.destroy', r.id));
};
</script>

<template>
    <Head title="Admission Rounds" />
    <PortalLayout title="Admission Rounds" :breadcrumb="['Admin', 'Admission Rounds']">

        <div class="flex justify-end mb-4">
            <Button @click="showForm = !showForm">{{ showForm ? 'Cancel' : '+ New Round' }}</Button>
        </div>

        <div v-if="showForm" class="bg-white border border-border rounded p-4 mb-6">
            <h3 class="font-serif text-lg text-maroon mb-3">Create Admission Round</h3>
            <p class="text-xs text-ink-mute mb-3">
                An admission round groups a programme + session + round number (e.g. "UGCS01 · 2026-27 · Round 1"). Merit lists are
                generated per round.
            </p>
            <form @submit.prevent="create" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-medium text-ink mb-1">Session <span class="text-maroon">*</span></label>
                    <select v-model="form.academic_session_id" class="w-full px-3 py-2 text-sm border border-border rounded bg-white">
                        <option value="">— Select —</option>
                        <option v-for="s in sessions" :key="s.id" :value="s.id">{{ s.code }} {{ s.is_active ? '(active)' : '' }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-ink mb-1">Programme <span class="text-maroon">*</span></label>
                    <select v-model="form.program_id" class="w-full px-3 py-2 text-sm border border-border rounded bg-white">
                        <option value="">— Select —</option>
                        <option v-for="p in programs" :key="p.id" :value="p.id">{{ p.code }} · {{ p.name }}</option>
                    </select>
                </div>
                <InputText v-model="form.round_number" type="number" label="Round Number" :error="form.errors.round_number" required />
                <InputText v-model="form.name" label="Display Name" placeholder="e.g. Round 1 — Aug 2026" required :error="form.errors.name" />
                <div class="md:col-span-4">
                    <Button type="submit" :loading="form.processing">Create Round</Button>
                </div>
            </form>
        </div>

        <div class="bg-white border border-border rounded overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-cream text-ink-mute text-xs uppercase tracking-wider">
                    <tr>
                        <th class="text-left px-4 py-2">Session</th>
                        <th class="text-left px-4 py-2">Programme</th>
                        <th class="text-left px-4 py-2">Round</th>
                        <th class="text-left px-4 py-2">Name</th>
                        <th class="text-left px-4 py-2">Status</th>
                        <th class="text-left px-4 py-2">Merit List</th>
                        <th class="text-right px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="r in rounds" :key="r.id" class="border-t border-border">
                        <td class="px-4 py-2 font-mono text-xs">{{ r.session?.code }}</td>
                        <td class="px-4 py-2">
                            <span class="font-mono text-xs">{{ r.program?.code }}</span> · {{ r.program?.name }}
                        </td>
                        <td class="px-4 py-2 font-mono text-xs">#{{ r.round_number }}</td>
                        <td class="px-4 py-2">{{ r.name }}</td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-0.5 text-xs rounded" :class="statusBadge(r.status)">{{ r.status }}</span>
                        </td>
                        <td class="px-4 py-2 text-xs">
                            <template v-if="r.merit_list">
                                <Link :href="route('admin.merit-lists.show', r.merit_list.id)" class="text-maroon hover:underline">
                                    {{ r.merit_list.status }} · {{ r.merit_list.total_candidates }} cands
                                </Link>
                            </template>
                            <template v-else>
                                <button @click="router.post(route('admin.merit-lists.generate', r.id))"
                                    class="text-xs px-2 py-1 border border-maroon text-maroon rounded hover:bg-saffron-soft">
                                    Generate Merit List
                                </button>
                            </template>
                        </td>
                        <td class="px-4 py-2 text-right space-x-2 whitespace-nowrap">
                            <select :value="r.status" @change="(e) => updateStatus(r, e.target.value)"
                                class="text-xs px-2 py-1 border border-border rounded">
                                <option value="planning">Planning</option>
                                <option value="open">Open</option>
                                <option value="closed">Closed</option>
                                <option value="locked">Locked</option>
                            </select>
                            <button @click="destroy(r)" class="text-xs px-2 py-1 border border-red-300 text-red-600 rounded hover:bg-red-50">
                                Delete
                            </button>
                        </td>
                    </tr>
                    <tr v-if="!rounds.length">
                        <td colspan="7" class="px-4 py-6 text-center text-ink-mute text-sm">
                            No admission rounds yet. Create one above.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </PortalLayout>
</template>
