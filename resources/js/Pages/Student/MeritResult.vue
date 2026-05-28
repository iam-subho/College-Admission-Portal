<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import { computed } from 'vue';
import { formatDateTime } from '@/utils/date.js';

const props = defineProps({
    application: { type: Object, required: true },
    merit_list: { type: Object, default: null },
    entry: { type: Object, default: null },
    cutoffs: { type: Array, default: () => [] },
});

const meritUrl = computed(() => props.merit_list
    ? `/merit/${props.application.program?.code}/${props.merit_list.round?.round_number}`
    : null);

const myCutoff = computed(() => {
    if (!props.entry?.reservation_category_id) return null;
    return props.cutoffs.find(c => c.category_id === props.entry.reservation_category_id) || null;
});

const isAboveCutoff = computed(() => {
    if (!props.entry || !myCutoff.value?.cutoff_score) return null;
    return Number(props.entry.total_score) >= Number(myCutoff.value.cutoff_score);
});
</script>

<template>
    <Head :title="`Merit Result — ${application.application_number}`" />
    <PortalLayout title="Merit Result"
        :breadcrumb="['Student', 'Applications', application.application_number, 'Merit Result']">

        <div class="mb-4 text-sm text-ink-mute">
            <span class="font-mono">{{ application.application_number }}</span> ·
            {{ application.program?.code }} {{ application.program?.name }} ·
            Session {{ application.session?.code }}
        </div>

        <!-- No published list yet -->
        <div v-if="!merit_list" class="bg-amber-50 border border-amber-300 rounded p-5">
            <h2 class="font-serif text-base text-amber-900 mb-1">Merit list not yet published</h2>
            <p class="text-sm text-amber-800">
                The admission office has not yet published the merit list for this round. Watch this space —
                you'll see your rank here as soon as it's released. Notification will also be sent to your registered email.
            </p>
        </div>

        <!-- Published list but no entry for this candidate (e.g. unpaid) -->
        <div v-else-if="!entry" class="bg-white border border-border rounded p-5">
            <h2 class="font-serif text-base text-maroon mb-1">Not in the merit list</h2>
            <p class="text-sm text-ink-mute">
                The merit list for this round has been published, but your application is not included.
                This usually means the application fee was not paid before the cutoff. Please contact admissions if you believe this is in error.
            </p>
            <p class="text-xs text-ink-mute mt-3">
                Published on {{ formatDateTime(merit_list.published_at) }}.
            </p>
        </div>

        <!-- Entry shown -->
        <article v-else class="bg-white border-2 rounded shadow-sm overflow-hidden"
            :class="entry.is_qualifying && !entry.is_absent ? 'border-green-600' : 'border-amber-400'">

            <header class="px-6 py-4 flex items-center justify-between"
                :class="entry.is_qualifying && !entry.is_absent ? 'bg-green-600 text-white' : 'bg-amber-500 text-white'">
                <div>
                    <h2 class="font-serif text-lg">
                        <template v-if="entry.is_absent">Marked Absent</template>
                        <template v-else-if="!entry.is_qualifying">Below Qualifying Marks</template>
                        <template v-else>Your Merit Rank</template>
                    </h2>
                    <p class="text-xs opacity-90 mt-0.5">Published {{ formatDateTime(merit_list.published_at) }}</p>
                </div>
                <a v-if="meritUrl" :href="meritUrl" target="_blank"
                    class="text-xs underline opacity-90 hover:opacity-100">View Public List →</a>
            </header>

            <div class="p-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-x-6 gap-y-3 text-sm">
                    <div>
                        <div class="text-[10px] uppercase tracking-wider text-ink-mute">Overall Rank</div>
                        <div class="font-mono font-bold text-2xl text-maroon">{{ entry.overall_rank }}</div>
                    </div>
                    <div v-if="entry.category_rank">
                        <div class="text-[10px] uppercase tracking-wider text-ink-mute">Category Rank ({{ entry.category?.code }})</div>
                        <div class="font-mono font-bold text-2xl">{{ entry.category_rank }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] uppercase tracking-wider text-ink-mute">Total Score</div>
                        <div class="font-mono font-bold text-xl">{{ Number(entry.total_score).toFixed(2) }}</div>
                    </div>
                    <div v-if="entry.test_score !== null">
                        <div class="text-[10px] uppercase tracking-wider text-ink-mute">Test Score</div>
                        <div class="font-mono text-base">{{ Number(entry.test_score).toFixed(2) }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] uppercase tracking-wider text-ink-mute">Board %</div>
                        <div class="font-mono text-base">{{ Number(entry.marks_pct).toFixed(2) }}%</div>
                    </div>
                </div>

                <!-- Cutoff comparison -->
                <div v-if="myCutoff" class="mt-5 p-4 bg-cream border border-border rounded">
                    <h3 class="font-serif text-sm text-maroon mb-2">Your category cutoff</h3>
                    <div class="text-sm space-y-1">
                        <div>Category: <strong>{{ myCutoff.category }}</strong> · seats {{ myCutoff.seats_available }}</div>
                        <div>Cutoff score: <strong class="font-mono">{{ myCutoff.cutoff_score !== null ? Number(myCutoff.cutoff_score).toFixed(2) : '—' }}</strong></div>
                        <div v-if="isAboveCutoff === true" class="text-green-700 font-semibold mt-2">
                            ✓ Your score is at or above the cutoff. Watch for the seat allotment letter.
                        </div>
                        <div v-else-if="isAboveCutoff === false" class="text-amber-700 font-semibold mt-2">
                            ⚠ Your score is below this round's cutoff. You may be on the waitlist for the next round.
                        </div>
                    </div>
                </div>
            </div>
        </article>

        <div class="mt-4">
            <Link :href="`/student/applications/${application.id}`" class="text-xs text-maroon hover:underline">
                ← Back to application
            </Link>
        </div>
    </PortalLayout>
</template>
