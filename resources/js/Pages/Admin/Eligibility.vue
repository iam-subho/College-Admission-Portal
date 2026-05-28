<script setup>
import { Head, router } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import InputText from '@/Components/Ui/InputText.vue';
import Button from '@/Components/Ui/Button.vue';
import SearchableSelect from '@/Components/Ui/SearchableSelect.vue';
import { ref, reactive, computed } from 'vue';

const props = defineProps({
    programmes: { type: Array, required: true },
    rules: { type: Array, required: true },
    rule_types: { type: Array, required: true },
    subject_options: { type: Object, default: () => ({}) },
});

// Flat subject list across all levels for the subject_minimum picker.
const allSubjectOptions = computed(() => {
    const out = [];
    for (const [level, items] of Object.entries(props.subject_options || {})) {
        const lbl = level === 'ug' ? 'UG' : `Class ${level.toUpperCase()}`;
        for (const o of items) {
            out.push({ ...o, group: lbl });
        }
    }
    return out;
});

const expanded = ref({});
const formModal = ref(null);

// Each rule_type maps to a known params shape so we can render typed inputs.
const TYPE_SCHEMA = {
    min_percentage: {
        label: 'Minimum Percentage',
        fields: [
            { key: 'min', label: 'Minimum %', type: 'number', step: '0.01', default: 50 },
            { key: 'level', label: 'Level', type: 'select', options: ['10th', '12th', 'ug'], default: '12th' },
        ],
    },
    board_in: {
        label: 'Board / University Whitelist',
        fields: [
            { key: 'boards', label: 'Allowed boards (comma-separated)', type: 'csv', default: 'CBSE,ICSE,Gujarat Board' },
            { key: 'level', label: 'Level', type: 'select', options: ['10th', '12th', 'ug'], default: '12th' },
        ],
    },
    subject_minimum: {
        label: 'Subject-Specific Minimum',
        fields: [
            { key: 'subject', label: 'Subject (picked from master list)', type: 'subject_picker', default: '' },
            { key: 'min', label: 'Minimum %', type: 'number', step: '0.01', default: 45 },
            { key: 'level', label: 'Level', type: 'select', options: ['10th', '12th', 'ug'], default: '12th' },
        ],
    },
    age_band: {
        label: 'Age Band',
        fields: [
            { key: 'min_age', label: 'Minimum age (years)', type: 'number', default: 17 },
            { key: 'max_age', label: 'Maximum age (years)', type: 'number', default: 25 },
            { key: 'as_of', label: 'As of date (YYYY-MM-DD)', type: 'text', default: '' },
        ],
    },
    gap_year_max: {
        label: 'Maximum Gap Years',
        fields: [
            { key: 'max_gap', label: 'Max gap years between qualifying exam and admission', type: 'number', default: 2 },
        ],
    },
};

const schemaFor = (t) => TYPE_SCHEMA[t] || { label: t, fields: [] };

const defaultParamsFor = (t) => {
    const out = {};
    for (const f of schemaFor(t).fields) {
        out[f.key] = f.default;
    }
    return out;
};

const rulesByProgram = (programId) => props.rules.filter(r => r.program_id === programId);

const openCreate = (programId) => {
    formModal.value = reactive({
        editing_id: null,
        program_id: programId,
        rule_type: 'min_percentage',
        params: defaultParamsFor('min_percentage'),
        label: '',
    });
};

const openEdit = (rule) => {
    // CSV fields stored as arrays; bring back to comma-string for the input.
    const params = { ...defaultParamsFor(rule.rule_type), ...(rule.params || {}) };
    for (const f of schemaFor(rule.rule_type).fields) {
        if (f.type === 'csv' && Array.isArray(params[f.key])) {
            params[f.key] = params[f.key].join(', ');
        }
    }
    formModal.value = reactive({
        editing_id: rule.id,
        program_id: rule.program_id,
        rule_type: rule.rule_type,
        params,
        label: rule.label || '',
    });
};

const onTypeChange = () => {
    formModal.value.params = defaultParamsFor(formModal.value.rule_type);
};

const closeModal = () => { formModal.value = null; };

const normalizeParams = (params, ruleType) => {
    const out = { ...params };
    for (const f of schemaFor(ruleType).fields) {
        if (f.type === 'csv' && typeof out[f.key] === 'string') {
            out[f.key] = out[f.key].split(',').map(s => s.trim()).filter(Boolean);
        }
        if (f.type === 'number' && out[f.key] !== '' && out[f.key] !== null) {
            out[f.key] = Number(out[f.key]);
        }
    }
    return out;
};

const submitRule = () => {
    const opts = { onSuccess: closeModal, preserveScroll: true };
    const params = normalizeParams(formModal.value.params, formModal.value.rule_type);

    if (formModal.value.editing_id) {
        router.patch(route('admin.eligibility-rules.update', formModal.value.editing_id), {
            params,
            label: formModal.value.label || null,
        }, opts);
    } else {
        router.post(route('admin.eligibility-rules.store'), {
            program_id: formModal.value.program_id,
            rule_type: formModal.value.rule_type,
            params,
            label: formModal.value.label || null,
        }, opts);
    }
};

const renderParamValue = (params, key) => {
    const v = params?.[key];
    if (Array.isArray(v)) return v.join(', ');
    return v ?? '—';
};

const toggleActive = (rule) => router.patch(route('admin.eligibility-rules.update', rule.id), {
    params: rule.params || {},
    label: rule.label || null,
    is_active: !rule.is_active,
}, { preserveScroll: true });

const remove = (rule) => {
    if (!confirm('Remove this rule?')) return;
    router.delete(route('admin.eligibility-rules.destroy', rule.id), { preserveScroll: true });
};

const reEvaluating = ref({}); // { programmeId: true } while in flight

const reEvaluate = (p) => {
    const total = p.app_re_evaluable ?? 0;
    if (total === 0) {
        if (!confirm(`No re-evaluable applications for ${p.code} (overrides are skipped). Run anyway?`)) return;
    } else if (!confirm(`Re-evaluate ${total} application(s) for ${p.code}?\n\n` +
        `Admin-overridden verdicts are preserved. This rewrites the eligibility verdict + reasons against current rules.`)) {
        return;
    }
    reEvaluating.value[p.id] = true;
    router.post(route('admin.eligibility-rules.re-evaluate', p.id), {}, {
        preserveScroll: true,
        onFinish: () => { reEvaluating.value[p.id] = false; },
    });
};
</script>

<template>
    <Head title="Eligibility Rules" />
    <PortalLayout title="Eligibility Rules" :breadcrumb="['Admin', 'Eligibility Rules']">
        <p class="text-sm text-ink-mute mb-4">
            Per-programme rules evaluated at application submit. The verdict is shown to the admission officer
            for review but does NOT block submission. Multiple rules on the same programme are combined with AND.
        </p>

        <div class="space-y-3">
            <div v-for="p in programmes" :key="p.id" class="bg-white border border-border rounded">
                <button @click="expanded[p.id] = !expanded[p.id]"
                    class="w-full flex justify-between items-center px-4 py-3 text-left hover:bg-cream">
                    <div>
                        <span class="font-mono text-xs">{{ p.code }}</span>
                        <span class="ml-2">{{ p.name }}</span>
                        <span class="ml-2 text-xs text-ink-mute">{{ p.type }}</span>
                    </div>
                    <span class="text-xs text-ink-mute">
                        {{ rulesByProgram(p.id).length }} rule(s)
                    </span>
                </button>
                <div v-if="expanded[p.id]" class="border-t border-border p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                        <div class="text-xs text-ink-mute">
                            <span class="font-mono">{{ p.app_total ?? 0 }}</span> application(s) on file
                            <span v-if="(p.app_overridden ?? 0) > 0" class="ml-2">
                                · <span class="font-mono">{{ p.app_overridden }}</span> admin-overridden (will be preserved)
                            </span>
                        </div>
                        <div class="space-x-2">
                            <button @click="reEvaluate(p)"
                                :disabled="reEvaluating[p.id]"
                                :title="`Re-runs the eligibility engine for ${p.app_re_evaluable ?? 0} non-overridden application(s) of this programme`"
                                class="text-xs px-3 py-1 border border-saffron text-saffron rounded hover:bg-saffron-soft disabled:opacity-50">
                                {{ reEvaluating[p.id] ? 'Re-evaluating…' : `Re-evaluate Applications (${p.app_re_evaluable ?? 0})` }}
                            </button>
                            <button @click="openCreate(p.id)"
                                class="text-xs px-3 py-1 bg-maroon text-white rounded hover:bg-maroon/90">
                                + Add Rule
                            </button>
                        </div>
                    </div>
                    <table class="w-full text-sm">
                        <thead class="text-xs uppercase text-ink-mute">
                            <tr>
                                <th class="text-left py-1">Label / Type</th>
                                <th class="text-left py-1">Parameters</th>
                                <th class="text-center py-1 w-16">Active</th>
                                <th class="text-right py-1 w-32"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="r in rulesByProgram(p.id)" :key="r.id" class="border-t border-border">
                                <td class="py-2">
                                    <div class="font-medium">{{ r.label || schemaFor(r.rule_type).label }}</div>
                                    <div class="text-xs font-mono text-ink-mute">{{ r.rule_type }}</div>
                                </td>
                                <td class="py-2 text-xs">
                                    <span v-for="f in schemaFor(r.rule_type).fields" :key="f.key" class="mr-3">
                                        <span class="text-ink-mute">{{ f.key }}:</span>
                                        <span class="font-mono">{{ renderParamValue(r.params, f.key) }}</span>
                                    </span>
                                </td>
                                <td class="py-2 text-center">
                                    <button @click="toggleActive(r)" class="text-xs"
                                        :title="r.is_active ? 'Active — click to disable' : 'Disabled — click to enable'">
                                        {{ r.is_active ? '✓' : '—' }}
                                    </button>
                                </td>
                                <td class="py-2 text-right space-x-2 whitespace-nowrap">
                                    <button @click="openEdit(r)" class="text-xs px-2 py-1 border border-border rounded hover:bg-cream">
                                        Edit
                                    </button>
                                    <button @click="remove(r)" class="text-xs text-red-600 hover:underline">Remove</button>
                                </td>
                            </tr>
                            <tr v-if="!rulesByProgram(p.id).length">
                                <td colspan="4" class="py-3 text-center text-xs text-ink-mute">
                                    No rules defined. Click "Add Rule" to create one.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Rule create / edit modal -->
        <div v-if="formModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" @click.self="closeModal">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-xl max-h-[90vh] overflow-y-auto">
                <header class="px-5 py-3 border-b border-border bg-cream">
                    <h3 class="font-serif text-base text-maroon">
                        {{ formModal.editing_id ? `Edit Rule #${formModal.editing_id}` : 'New Eligibility Rule' }}
                    </h3>
                </header>
                <div class="p-5 space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-ink mb-1">Rule Type <span class="text-maroon">*</span></label>
                        <select v-model="formModal.rule_type" @change="onTypeChange" :disabled="!!formModal.editing_id"
                            class="w-full px-3 py-2 text-sm border border-border rounded bg-white disabled:opacity-60">
                            <option v-for="t in rule_types" :key="t" :value="t">{{ schemaFor(t).label }}</option>
                        </select>
                        <p v-if="formModal.editing_id" class="text-[10px] text-ink-mute mt-1">
                            Rule type cannot be changed after creation. Delete and recreate if needed.
                        </p>
                    </div>

                    <InputText v-model="formModal.label" label="Display Label (optional)"
                        placeholder="Defaults to the rule type name" />

                    <div class="border-t border-border pt-3">
                        <h4 class="text-sm font-semibold text-maroon mb-2">Parameters</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div v-for="f in schemaFor(formModal.rule_type).fields" :key="f.key"
                                :class="f.type === 'subject_picker' ? 'md:col-span-2' : ''">
                                <label v-if="f.type !== 'subject_picker'" class="block text-xs font-medium text-ink mb-1">{{ f.label }}</label>
                                <select v-if="f.type === 'select'" v-model="formModal.params[f.key]"
                                    class="w-full px-3 py-2 text-sm border border-border rounded bg-white">
                                    <option v-for="opt in f.options" :key="opt" :value="opt">{{ opt }}</option>
                                </select>
                                <input v-else-if="f.type === 'number'" v-model.number="formModal.params[f.key]" type="number"
                                    :step="f.step || '1'"
                                    class="w-full px-3 py-2 text-sm border border-border rounded" />
                                <SearchableSelect v-else-if="f.type === 'subject_picker'"
                                    :model-value="formModal.params[f.key]"
                                    :options="allSubjectOptions"
                                    :label="f.label"
                                    placeholder="Pick a subject from the master list…"
                                    empty-text="No subjects match. Add via Academic Subjects admin."
                                    @change="(opt) => (formModal.params[f.key] = opt ? opt.value : '')" />
                                <input v-else v-model="formModal.params[f.key]" type="text"
                                    class="w-full px-3 py-2 text-sm border border-border rounded" />
                                <p v-if="f.type === 'csv'" class="text-[10px] text-ink-mute mt-1">Comma-separated.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <footer class="px-5 py-3 border-t border-border bg-cream flex justify-end gap-2">
                    <button @click="closeModal" class="px-3 py-1.5 text-sm border border-border rounded hover:bg-white">Cancel</button>
                    <Button @click="submitRule">{{ formModal.editing_id ? 'Save Changes' : 'Create Rule' }}</Button>
                </footer>
            </div>
        </div>
    </PortalLayout>
</template>
