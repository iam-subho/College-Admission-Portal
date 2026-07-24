<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import { computed } from 'vue';
import { formatDate, formatDateTime } from '@/utils/date.js';
import { useSite } from '@/Composables/useSite.js';

const props = defineProps({
    application: { type: Object, required: true },
    candidate: { type: Object, default: null },
    config: { type: Object, default: null },
    schedule: { type: Object, default: null },
    can_download: { type: Boolean, default: false },
});

const { collegeName, cityState } = useSite();

const page = usePage();
const flashError = computed(() => page.props?.flash?.error || null);

const testRequired = computed(() => props.config?.is_test_enabled);
const testScheduled = computed(() => !!props.schedule);
const cardPublished = computed(() => !!props.candidate?.admit_card_published);
const downloadHref = computed(() => `/student/admit-card/${props.application.id}/download`);
</script>

<template>
    <Head :title="`Admit Card — ${application.application_number}`" />
    <PortalLayout title="Admission Test · Admit Card"
        :breadcrumb="['Student', 'Applications', application.application_number, 'Admit Card']">

        <div v-if="flashError" class="mb-4 p-3 bg-red-50 border border-red-300 text-red-800 text-sm rounded">
            {{ flashError }}
        </div>

        <div class="mb-4 text-sm text-ink-mute">
            Application: <span class="font-mono">{{ application.application_number }}</span> ·
            {{ application.program?.code }} {{ application.program?.name }} ·
            Session <strong>{{ application.session?.code }}</strong>
        </div>

        <!-- No test required for this programme -->
        <div v-if="!testRequired" class="bg-white border border-border rounded p-6 text-center">
            <div class="text-4xl mb-3">📘</div>
            <h2 class="font-serif text-lg text-maroon mb-2">No Admission Test Required</h2>
            <p class="text-sm text-ink-mute">
                Admission to <strong>{{ application.program?.code }} · {{ application.program?.name }}</strong>
                is based on board / aggregate marks only. There is no admission test, and no admit card is issued.
            </p>
            <p class="text-xs text-ink-mute mt-3">
                Watch the merit list announcement in your portal for the result.
            </p>
        </div>

        <!-- Test required but schedule not yet set -->
        <div v-else-if="!testScheduled" class="bg-amber-50 border border-amber-300 rounded p-5">
            <h2 class="font-serif text-base text-amber-900 mb-1">Test Schedule Pending</h2>
            <p class="text-sm text-amber-800">
                An admission test is required for this programme, but the test schedule has not yet been published.
                You will see the admit card here once the schedule is announced. Check back soon.
            </p>
        </div>

        <!-- Test scheduled but admit card not yet published -->
        <div v-else-if="!cardPublished" class="bg-white border border-border rounded p-5 space-y-3">
            <h2 class="font-serif text-base text-maroon">Admission Test Scheduled</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-2 text-sm">
                <div><span class="text-xs text-ink-mute">Date:</span> <strong>{{ formatDate(schedule.test_date) }}</strong></div>
                <div><span class="text-xs text-ink-mute">Venue:</span> {{ schedule.venue }}</div>
                <div v-if="schedule.reporting_time"><span class="text-xs text-ink-mute">Reporting:</span> {{ schedule.reporting_time }}</div>
                <div v-if="schedule.start_time && schedule.end_time">
                    <span class="text-xs text-ink-mute">Test:</span> {{ schedule.start_time }} — {{ schedule.end_time }}
                </div>
            </div>
            <p class="text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded p-2 mt-2">
                Your roll number has not yet been assigned. The admit card becomes downloadable once the admission office publishes admit cards.
            </p>
        </div>

        <!-- Admit card ready -->
        <div v-else class="bg-white border-2 border-maroon rounded shadow-sm overflow-hidden">
            <header class="px-6 py-4 bg-maroon text-white flex items-center justify-between">
                <div>
                    <h2 class="font-serif text-lg">Admit Card · Admission Test</h2>
                    <p class="text-xs opacity-90 mt-0.5">{{ collegeName }}<template v-if="cityState"> · {{ cityState }}</template></p>
                </div>
                <span class="text-xs uppercase font-mono px-2 py-0.5 rounded bg-white text-maroon">Published</span>
            </header>

            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-3 text-sm">
                    <div>
                        <div class="text-[10px] uppercase tracking-wider text-ink-mute">Roll Number</div>
                        <div class="font-mono font-bold text-2xl text-maroon">{{ candidate.roll_number }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] uppercase tracking-wider text-ink-mute">Application No.</div>
                        <div class="font-mono font-medium">{{ application.application_number }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] uppercase tracking-wider text-ink-mute">Test Date</div>
                        <div class="font-medium">{{ formatDate(schedule.test_date) }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] uppercase tracking-wider text-ink-mute">Reporting Time</div>
                        <div class="font-medium">{{ schedule.reporting_time || '—' }}</div>
                    </div>
                    <div class="md:col-span-2">
                        <div class="text-[10px] uppercase tracking-wider text-ink-mute">Venue</div>
                        <div class="font-medium">{{ schedule.venue }}</div>
                        <div v-if="schedule.venue_address" class="text-xs text-ink-mute mt-0.5">{{ schedule.venue_address }}</div>
                    </div>
                    <div v-if="config.max_marks">
                        <div class="text-[10px] uppercase tracking-wider text-ink-mute">Max Marks</div>
                        <div class="font-medium">{{ config.max_marks }}
                            <span v-if="config.qualifying_marks" class="text-xs text-ink-mute">(qualifying: {{ config.qualifying_marks }})</span>
                        </div>
                    </div>
                    <div v-if="schedule.start_time">
                        <div class="text-[10px] uppercase tracking-wider text-ink-mute">Test Duration</div>
                        <div class="font-medium">{{ schedule.start_time }} — {{ schedule.end_time }}</div>
                    </div>
                </div>

                <div v-if="config.instructions" class="mt-5 p-3 bg-cream border border-border rounded text-xs whitespace-pre-line">
                    <div class="font-semibold text-maroon mb-1">Instructions</div>
                    {{ config.instructions }}
                </div>

                <div class="mt-6 flex flex-wrap gap-3 items-center">
                    <a v-if="can_download" :href="downloadHref"
                        class="inline-block px-5 py-2 bg-maroon text-white text-sm font-semibold rounded hover:bg-maroon/90 shadow-sm">
                        🖨 Download Admit Card (PDF)
                    </a>
                    <Link :href="`/student/applications/${application.id}`"
                        class="text-xs text-maroon hover:underline">← Back to application</Link>
                </div>

                <div v-if="candidate.admit_card_downloaded_at" class="mt-3 text-xs text-ink-mute">
                    Last downloaded: {{ formatDateTime(candidate.admit_card_downloaded_at) }}
                </div>
            </div>
        </div>
    </PortalLayout>
</template>
