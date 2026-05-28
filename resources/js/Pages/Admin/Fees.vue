<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import InputText from '@/Components/Ui/InputText.vue';
import Button from '@/Components/Ui/Button.vue';
import { ref } from 'vue';

defineProps({
    heads: { type: Array, required: true },
    structures: { type: Array, required: true },
    sessions: { type: Array, required: true },
    programs: { type: Array, required: true },
});

const showHeadForm = ref(false);
const headForm = useForm({ code: '', name: '', category: 'tuition', is_refundable: false, ordering: 0 });
const submitHead = () => headForm.post(route('admin.fees.heads.store'), {
    onSuccess: () => { showHeadForm.value = false; headForm.reset(); },
});

const inr = (n) => '₹' + Number(n).toLocaleString('en-IN');
</script>

<template>
    <Head title="Fees" />
    <PortalLayout title="Fee Heads & Structures" :breadcrumb="['Admin', 'Fees']">
        <section class="mb-8">
            <div class="flex justify-between items-center mb-3">
                <h2 class="font-serif text-lg text-maroon">Fee Heads</h2>
                <Button @click="showHeadForm = !showHeadForm">{{ showHeadForm ? 'Cancel' : '+ New Head' }}</Button>
            </div>

            <div v-if="showHeadForm" class="bg-white border border-border rounded p-4 mb-4">
                <form @submit.prevent="submitHead" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <InputText v-model="headForm.code" label="Code" required :error="headForm.errors.code" />
                    <InputText v-model="headForm.name" label="Name" required :error="headForm.errors.name" />
                    <div>
                        <label class="block text-xs font-medium text-ink mb-1">Category</label>
                        <select v-model="headForm.category" class="w-full px-3 py-2 text-sm border border-border rounded">
                            <option value="application">Application</option>
                            <option value="tuition">Tuition</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="flex items-center pt-5">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" v-model="headForm.is_refundable" class="accent-saffron" />
                            Refundable
                        </label>
                    </div>
                    <div class="md:col-span-4">
                        <Button type="submit" :loading="headForm.processing">Save</Button>
                    </div>
                </form>
            </div>

            <div class="bg-white border border-border rounded overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-cream text-ink-mute text-xs uppercase tracking-wider">
                        <tr>
                            <th class="text-left px-4 py-2">Code</th>
                            <th class="text-left px-4 py-2">Name</th>
                            <th class="text-left px-4 py-2">Category</th>
                            <th class="text-center px-4 py-2">Refundable</th>
                            <th class="text-center px-4 py-2">Active</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="h in heads" :key="h.id" class="border-t border-border">
                            <td class="px-4 py-2 font-mono">{{ h.code }}</td>
                            <td class="px-4 py-2">{{ h.name }}</td>
                            <td class="px-4 py-2 capitalize">{{ h.category }}</td>
                            <td class="px-4 py-2 text-center">{{ h.is_refundable ? '✓' : '—' }}</td>
                            <td class="px-4 py-2 text-center">{{ h.is_active ? '✓' : '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section>
            <h2 class="font-serif text-lg text-maroon mb-3">Fee Structures ({{ structures.length }})</h2>
            <div v-if="!structures.length" class="bg-white border border-border rounded p-6 text-center text-sm text-ink-mute">
                No fee structures yet. Create one once programmes are configured.
            </div>
            <div v-else class="bg-white border border-border rounded overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-cream text-ink-mute text-xs uppercase tracking-wider">
                        <tr>
                            <th class="text-left px-4 py-2">Session</th>
                            <th class="text-left px-4 py-2">Programme</th>
                            <th class="text-left px-4 py-2">Name</th>
                            <th class="text-right px-4 py-2">Total</th>
                            <th class="text-left px-4 py-2">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="s in structures" :key="s.id" class="border-t border-border">
                            <td class="px-4 py-2 font-mono">{{ s.session?.code }}</td>
                            <td class="px-4 py-2">{{ s.program?.code }} — {{ s.program?.name }}</td>
                            <td class="px-4 py-2">{{ s.name }}</td>
                            <td class="px-4 py-2 text-right font-mono">{{ inr(s.total_amount) }}</td>
                            <td class="px-4 py-2">{{ s.status }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </PortalLayout>
</template>
