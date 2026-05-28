<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { formatDateTime } from '@/utils/date.js';

const props = defineProps({
    program: { type: Object, required: true },
    round: { type: Object, required: true },
    session: { type: Object, required: true },
    merit_list: { type: Object, required: true },
    entries: { type: Array, default: () => [] },
    cutoffs: { type: Array, default: () => [] },
});

const search = ref('');
const showAbsent = ref(false);

const filtered = computed(() => {
    return props.entries.filter(e => {
        if (!showAbsent.value && (e.is_absent || !e.is_qualifying)) return false;
        if (search.value) {
            const q = search.value.toLowerCase();
            if (!e.application_number?.toLowerCase().includes(q)) return false;
        }
        return true;
    });
});
</script>

<template>
    <Head :title="`Merit List · ${program.code} · Round ${round.round_number}`" />

    <main class="min-h-screen bg-cream">
        <!-- Compact public header (no portal nav) -->
        <header class="bg-navy text-white py-3 border-b-4 border-saffron">
            <div class="max-w-5xl mx-auto px-6 flex items-center justify-between">
                <Link href="/" class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-maroon text-white text-[9px] font-bold flex items-center justify-center leading-tight">
                        <div class="text-center">SVNC<br/>1956</div>
                    </div>
                    <div>
                        <div class="font-serif text-base">Sardar Vallabhbhai National College</div>
                        <div class="text-[10px] opacity-80">Anand · Online Admissions Portal</div>
                    </div>
                </Link>
                <Link href="/login" class="text-xs text-white/80 hover:text-white">Student Sign-In →</Link>
            </div>
        </header>

        <div class="max-w-5xl mx-auto px-6 py-6">
            <Link href="/" class="text-xs text-maroon hover:underline">← Home</Link>

            <h1 class="font-serif text-2xl text-maroon mt-3">Merit List</h1>
            <p class="text-sm text-ink-mute">
                {{ program.code }} · {{ program.name }} ({{ program.type }})<br/>
                Round {{ round.round_number }} — {{ round.name }} · Session {{ session.code }}
            </p>
            <p class="text-xs text-ink-mute mt-2">
                Published on {{ formatDateTime(merit_list.published_at) }} · {{ merit_list.total_candidates }} candidates ranked
            </p>

            <!-- Cutoffs panel -->
            <section v-if="cutoffs.length" class="bg-white border border-border rounded p-4 mt-4">
                <h2 class="font-serif text-sm text-maroon mb-2">Category Cutoffs</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
                    <div v-for="c in cutoffs" :key="c.category" class="border border-border rounded p-2 bg-cream/50">
                        <div class="text-xs font-mono text-maroon">{{ c.category }}</div>
                        <div class="text-xs text-ink-mute">{{ c.category_name }}</div>
                        <div class="mt-1 text-sm">
                            <strong>{{ c.cutoff_score !== null ? Number(c.cutoff_score).toFixed(2) : '—' }}</strong>
                            <span class="text-xs text-ink-mute"> · {{ c.seats_available }} seats</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Filter + search -->
            <div class="flex gap-3 items-end mt-5 mb-3 flex-wrap">
                <div>
                    <label class="block text-xs font-medium text-ink mb-1">Find by Application Number</label>
                    <input v-model="search" placeholder="e.g. SVNC/UG/2026/000001"
                        class="px-3 py-2 text-sm border border-border rounded bg-white w-72" />
                </div>
                <label class="flex items-center gap-2 text-xs pb-2">
                    <input type="checkbox" v-model="showAbsent" />
                    Include absent / non-qualifying
                </label>
            </div>

            <!-- Anonymised list — no names. -->
            <div class="bg-white border border-border rounded overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-cream text-ink-mute text-xs uppercase tracking-wider">
                        <tr>
                            <th class="text-right px-4 py-2 w-16">Rank</th>
                            <th class="text-right px-4 py-2 w-20">Cat Rank</th>
                            <th class="text-left px-4 py-2">Application No.</th>
                            <th class="text-left px-4 py-2 w-16">Cat</th>
                            <th class="text-right px-4 py-2">Score</th>
                            <th class="text-left px-4 py-2">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="e in filtered" :key="e.application_number" class="border-t border-border">
                            <td class="px-4 py-1.5 text-right font-mono font-semibold">{{ e.overall_rank }}</td>
                            <td class="px-4 py-1.5 text-right font-mono">{{ e.category_rank ?? '—' }}</td>
                            <td class="px-4 py-1.5 font-mono text-xs">{{ e.application_number }}</td>
                            <td class="px-4 py-1.5 text-xs font-mono">{{ e.category || '—' }}</td>
                            <td class="px-4 py-1.5 text-right font-mono font-semibold">{{ e.score.toFixed(2) }}</td>
                            <td class="px-4 py-1.5 text-xs">
                                <span v-if="e.is_absent" class="text-red-600">Absent</span>
                                <span v-else-if="!e.is_qualifying" class="text-amber-600">Below qualifying</span>
                                <span v-else class="text-green-700">Qualifying</span>
                            </td>
                        </tr>
                        <tr v-if="!filtered.length">
                            <td colspan="6" class="px-4 py-6 text-center text-ink-mute text-sm">No entries match.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <p class="mt-4 text-xs text-ink-mute italic">
                Names and personal information are intentionally omitted on this public page in line with DPDP best practices.
                Candidates can identify themselves by their application number. Discrepancies should be reported to admissions@svnc.edu.in within 7 days.
            </p>
        </div>
    </main>
</template>
