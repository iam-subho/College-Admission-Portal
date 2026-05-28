<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { formatDate } from '@/utils/date.js';

const props = defineProps({
    programmes: { type: Array, default: () => [] },
    active_session: { type: Object, default: null },
    notices: { type: Array, default: () => [] },
    site: { type: Object, default: () => ({}) },
});

const s = (key, fallback = '') => props.site?.[key] ?? fallback;

const page = usePage();
const user = computed(() => page.props.auth?.user || null);

// ----- Notice tabs -----
const noticeTab = ref('latest');
const filteredNotices = computed(() =>
    noticeTab.value === 'latest'
        ? props.notices
        : props.notices.filter(n => n.tab === noticeTab.value),
);

// ----- Font-size (accessibility) -----
const sizeIdx = ref(1); // 0 = A-, 1 = A, 2 = A+
const sizeClasses = ['text-[13px]', 'text-[14px]', 'text-[16px]'];
const rootSizeClass = computed(() => sizeClasses[sizeIdx.value]);

// ----- Language placeholder -----
const lang = ref('EN');

const fmtDate = (iso) => {
    const d = new Date(iso);
    return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short' });
};

const programmeBadge = (p) => (p.type || 'UG').toUpperCase();
</script>

<template>
    <Head :title="`Admissions — ${s('college_name', 'Sardar Vallabhbhai National College')}`" />

    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:top-1 focus:left-1 focus:z-50 bg-maroon text-white px-2 py-1 text-xs rounded">
        Skip to main content
    </a>

    <div :class="['min-h-screen bg-cream', rootSizeClass]">

        <!-- ===== Top Utility Bar ===== -->
        <div class="bg-navy-deep text-white text-[11px]">
            <div class="max-w-7xl mx-auto px-6 py-1.5 flex justify-between items-center">
                <div class="flex gap-4 text-gray-300">
                    <a href="#main" class="hover:text-white">Skip to Content</a>
                    <a href="#sitemap" class="hover:text-white">Sitemap</a>
                    <span class="text-gray-500">·</span>
                    <span class="hidden md:inline text-gray-400">Anand, Gujarat</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="flex gap-1 items-baseline">
                        <button @click="sizeIdx = 0" :class="sizeIdx === 0 ? 'text-saffron' : 'text-gray-400'" class="text-[10px] px-1 hover:text-white" aria-label="Decrease font size">A-</button>
                        <button @click="sizeIdx = 1" :class="sizeIdx === 1 ? 'text-saffron' : 'text-gray-400'" class="text-[12px] px-1 hover:text-white" aria-label="Default font size">A</button>
                        <button @click="sizeIdx = 2" :class="sizeIdx === 2 ? 'text-saffron' : 'text-gray-400'" class="text-[14px] px-1 hover:text-white" aria-label="Increase font size">A+</button>
                    </div>
                    <span class="text-gray-500">|</span>
                    <div class="flex gap-2">
                        <button @click="lang = 'EN'" :class="lang === 'EN' ? 'text-saffron' : 'text-gray-400'" class="hover:text-white">EN</button>
                        <button @click="lang = 'HI'" :class="lang === 'HI' ? 'text-saffron' : 'text-gray-400'" class="hover:text-white">HI</button>
                        <button @click="lang = 'GU'" :class="lang === 'GU' ? 'text-saffron' : 'text-gray-400'" class="hover:text-white">GU</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== Main Header ===== -->
        <header class="bg-white border-b border-border">
            <div class="max-w-7xl mx-auto px-6 py-3 flex items-center justify-between gap-4">
                <Link href="/" class="flex items-center gap-3">
                    <div class="w-14 h-14 rounded-full bg-maroon text-white flex flex-col items-center justify-center leading-tight text-[10px] font-bold border-2 border-saffron">
                        <span>{{ s('college_short', 'SVNC') }}</span><span>{{ s('estd_year', '1956') }}</span>
                    </div>
                    <div class="leading-tight">
                        <div class="font-serif text-xl text-maroon">{{ s('college_name', 'Sardar Vallabhbhai National College') }}</div>
                        <div v-if="s('college_name_hi') || s('college_name_gu')" class="font-serif text-sm text-ink-mute">
                            <span v-if="s('college_name_hi')">{{ s('college_name_hi') }}</span>
                            <span v-if="s('college_name_hi') && s('college_name_gu')"> · </span>
                            <span v-if="s('college_name_gu')">{{ s('college_name_gu') }}</span>
                        </div>
                        <div class="text-xs text-ink-mute mt-0.5">
                            <span v-if="s('city_state')">{{ s('city_state') }} · </span>Estd. {{ s('estd_year', '1956') }}
                        </div>
                    </div>
                </Link>
                <div class="hidden md:flex items-center gap-3 text-xs">
                    <div class="border border-border rounded px-3 py-1">
                        <div class="text-[9px] uppercase tracking-wider text-ink-mute">NAAC Accredited</div>
                        <div class="font-serif text-base text-maroon font-semibold leading-none">{{ s('naac_grade', 'A+') }}</div>
                    </div>
                    <div class="border border-border rounded px-3 py-1">
                        <div class="text-[9px] uppercase tracking-wider text-ink-mute">UGC</div>
                        <div class="font-serif text-sm text-maroon font-semibold leading-none">{{ s('ugc_status', '2(f) · 12(B)') }}</div>
                    </div>
                </div>
            </div>
        </header>

        <!-- ===== Main Nav ===== -->
        <nav class="bg-maroon text-white">
            <div class="max-w-7xl mx-auto px-6 flex items-center justify-between text-sm">
                <ul class="flex">
                    <li><a href="#main" class="block px-4 py-2.5 hover:bg-maroon-deep font-semibold border-b-2 border-saffron">Home</a></li>
                    <li><a href="#programmes" class="block px-4 py-2.5 hover:bg-maroon-deep">Programmes</a></li>
                    <li><a href="#admissions" class="block px-4 py-2.5 hover:bg-maroon-deep">Admissions</a></li>
                    <li><a href="#notices" class="block px-4 py-2.5 hover:bg-maroon-deep">Notices</a></li>
                    <li><a href="#helpdesk" class="block px-4 py-2.5 hover:bg-maroon-deep">Helpdesk</a></li>
                </ul>
                <div class="flex gap-2 py-1.5">
                    <template v-if="user">
                        <Link :href="`/${user.dashboard_route || 'dashboard'}`"
                            class="px-3 py-1 bg-saffron text-white rounded text-xs font-semibold hover:bg-saffron/90">
                            My Dashboard →
                        </Link>
                    </template>
                    <template v-else>
                        <Link href="/register" class="px-3 py-1 bg-saffron text-white rounded text-xs font-semibold hover:bg-saffron/90">Apply Now</Link>
                        <Link href="/login" class="px-3 py-1 border border-white/40 rounded text-xs font-semibold hover:bg-white/10">Sign In</Link>
                    </template>
                </div>
            </div>
        </nav>

        <!-- Tricolor strip -->
        <div class="h-1 flex">
            <div class="flex-1 bg-saffron"></div>
            <div class="flex-1 bg-white"></div>
            <div class="flex-1 bg-green-700"></div>
        </div>

        <main id="main" class="max-w-7xl mx-auto px-6 py-6 space-y-8">

            <!-- ===== HERO ===== -->
            <section class="grid grid-cols-1 lg:grid-cols-5 gap-6" id="admissions">
                <!-- Left: Headline + CTAs -->
                <div class="lg:col-span-3 bg-white border border-border rounded p-6">
                    <div class="inline-block px-2 py-0.5 mb-3 text-[10px] font-semibold tracking-wider uppercase bg-saffron-soft text-maroon rounded">
                        Session {{ active_session?.code || '2026-27' }} · Now Open
                    </div>
                    <h1 class="font-serif text-3xl md:text-4xl text-maroon leading-tight">
                        Admissions Open {{ active_session?.code || '2026-27' }}
                    </h1>
                    <p class="text-ink-mute mt-2 mb-4 text-sm">
                        {{ s('hero_pitch', 'Apply online for Under-graduate and Post-graduate programmes under NEP 2020 at Sardar Vallabhbhai National College, Anand.') }}
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs text-ink-mute mb-4">
                        <div v-if="active_session?.application_open_date" class="border border-border rounded p-2">
                            <div class="text-[10px] uppercase tracking-wider">Applications Open</div>
                            <div class="font-mono text-sm text-ink mt-0.5">{{ formatDate(active_session.application_open_date) }}</div>
                        </div>
                        <div v-if="active_session?.application_close_date" class="border border-border rounded p-2">
                            <div class="text-[10px] uppercase tracking-wider">Applications Close</div>
                            <div class="font-mono text-sm text-ink mt-0.5">{{ formatDate(active_session.application_close_date) }}</div>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <Link href="/register" class="px-5 py-2 bg-saffron text-white rounded text-sm font-semibold hover:bg-saffron/90 shadow-sm">
                            Apply Online →
                        </Link>
                        <Link href="/login" class="px-5 py-2 border-2 border-navy text-navy rounded text-sm font-semibold hover:bg-navy hover:text-white">
                            Track My Application
                        </Link>
                    </div>
                </div>

                <!-- Right: Notice Board — fixed height on both desktop & mobile, scrollable list -->
                <aside id="notices" class="lg:col-span-2 bg-white border-2 border-maroon rounded flex flex-col h-[420px]">
                    <header class="px-4 py-2 bg-maroon text-white flex justify-between items-center shrink-0">
                        <h2 class="font-serif text-base flex items-center gap-2">
                            <span>Notice Board</span>
                            <span class="inline-block w-2 h-2 bg-saffron rounded-full animate-pulse"></span>
                        </h2>
                        <span class="text-[10px] uppercase tracking-wider text-saffron-soft">Live updates</span>
                    </header>
                    <div class="px-3 pt-2 flex gap-1 border-b border-border shrink-0">
                        <button v-for="t in ['latest', 'admissions', 'examination']" :key="t"
                            @click="noticeTab = t"
                            class="px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wider border-b-2 -mb-px"
                            :class="noticeTab === t
                                ? 'border-saffron text-maroon'
                                : 'border-transparent text-ink-mute hover:text-maroon'">
                            {{ t }}
                        </button>
                    </div>
                    <ul class="overflow-y-auto flex-1 min-h-0 divide-y divide-border">
                        <li v-for="(n, i) in filteredNotices" :key="i" class="px-3 py-2.5 hover:bg-cream">
                            <a :href="n.href" class="flex items-start gap-2">
                                <span class="text-[10px] font-mono uppercase tracking-wider bg-saffron-soft text-maroon px-2 py-0.5 rounded shrink-0 mt-0.5">
                                    {{ fmtDate(n.date) }}
                                </span>
                                <span class="text-xs leading-snug text-ink hover:text-maroon flex-1">{{ n.title }}</span>
                            </a>
                        </li>
                        <li v-if="!filteredNotices.length" class="px-3 py-8 text-center text-xs text-ink-mute italic">
                            No notices in this category.
                        </li>
                    </ul>
                </aside>
            </section>

            <!-- ===== Quick Actions ===== -->
            <section>
                <h2 class="font-serif text-xl text-maroon mb-3">Quick Actions</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <Link href="/register" class="block bg-white border border-border rounded p-4 hover:border-maroon hover:shadow-sm transition">
                        <div class="text-2xl mb-1">📝</div>
                        <div class="font-serif text-base text-maroon">Apply</div>
                        <div class="text-xs text-ink-mute mt-0.5">Start a new application</div>
                    </Link>
                    <Link href="/login" class="block bg-white border border-border rounded p-4 hover:border-maroon hover:shadow-sm transition">
                        <div class="text-2xl mb-1">📋</div>
                        <div class="font-serif text-base text-maroon">Track Application</div>
                        <div class="text-xs text-ink-mute mt-0.5">Check status &amp; uploads</div>
                    </Link>
                    <Link href="/login" class="block bg-white border border-border rounded p-4 hover:border-maroon hover:shadow-sm transition">
                        <div class="text-2xl mb-1">💳</div>
                        <div class="font-serif text-base text-maroon">Pay Fees</div>
                        <div class="text-xs text-ink-mute mt-0.5">Application &amp; admission fee</div>
                    </Link>
                    <a href="#notices" class="block bg-white border border-border rounded p-4 hover:border-maroon hover:shadow-sm transition">
                        <div class="text-2xl mb-1">🏆</div>
                        <div class="font-serif text-base text-maroon">Merit / Results</div>
                        <div class="text-xs text-ink-mute mt-0.5">Cutoffs &amp; seat lists</div>
                    </a>
                </div>
            </section>

            <!-- ===== Programmes Grid ===== -->
            <section id="programmes">
                <div class="flex justify-between items-end mb-3">
                    <h2 class="font-serif text-xl text-maroon">Programmes Offered</h2>
                    <Link href="/programmes" class="text-xs text-maroon hover:underline">View all programmes →</Link>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div v-for="p in programmes" :key="p.id"
                        class="bg-white border border-border rounded overflow-hidden flex flex-col hover:border-maroon hover:shadow-sm transition">
                        <header class="px-3 py-2 bg-cream flex justify-between items-center">
                            <span class="text-xs font-mono font-bold text-maroon">{{ p.code }}</span>
                            <span class="text-[10px] font-mono uppercase tracking-wider px-1.5 py-0.5 rounded bg-saffron text-white">{{ programmeBadge(p) }}</span>
                        </header>
                        <div class="p-3 flex-1">
                            <div class="font-serif text-base text-ink leading-snug">{{ p.name }}</div>
                            <div class="text-xs text-ink-mute mt-1">{{ p.department?.name }}</div>
                            <div class="grid grid-cols-2 gap-2 mt-3 text-xs">
                                <div>
                                    <div class="text-[10px] uppercase text-ink-mute">Intake</div>
                                    <div class="font-mono font-semibold">{{ p.intake_capacity || '—' }}</div>
                                </div>
                                <div>
                                    <div class="text-[10px] uppercase text-ink-mute">Duration</div>
                                    <div class="font-mono font-semibold">{{ p.duration_years || '—' }} yr</div>
                                </div>
                            </div>
                        </div>
                        <Link href="/register"
                            class="block px-3 py-2 text-xs font-semibold text-center bg-maroon text-white hover:bg-maroon-deep">
                            Apply →
                        </Link>
                    </div>
                    <div v-if="!programmes.length" class="md:col-span-2 lg:col-span-4 bg-white border border-border rounded p-8 text-center text-sm text-ink-mute italic">
                        No active programmes — please check back during admission season.
                    </div>
                </div>
            </section>

        </main>

        <!-- ===== Footer ===== -->
        <footer id="sitemap" class="bg-navy-deep text-gray-300 mt-12">
            <div class="max-w-7xl mx-auto px-6 py-6 grid grid-cols-1 md:grid-cols-4 gap-6 text-xs">
                <div>
                    <div class="font-serif text-base text-white mb-2">{{ s('college_short', 'SVNC') }} Anand</div>
                    <p class="text-gray-400 leading-relaxed">
                        {{ s('college_name', 'Sardar Vallabhbhai National College') }},<br>
                        {{ s('address_line1', 'Mota Bazar, Vallabh Vidyanagar Road,') }}<br>
                        {{ s('address_line2', 'Anand — 388 001, Gujarat, India') }}
                    </p>
                </div>
                <div>
                    <div class="font-serif text-base text-white mb-2">Helpdesk</div>
                    <p>Helpline: <a :href="`tel:${s('helpline_phone')}`" class="text-saffron-soft hover:underline">{{ s('helpline_phone', '+91 2692 26 13 13') }}</a></p>
                    <p>Email: <a :href="`mailto:${s('helpline_email')}`" class="text-saffron-soft hover:underline">{{ s('helpline_email', 'admissions@svnc.ac.in') }}</a></p>
                    <p class="mt-2">{{ s('helpline_hours', 'Mon–Sat · 10:00–17:00 IST') }}</p>
                </div>
                <div>
                    <div class="font-serif text-base text-white mb-2">Quick Links</div>
                    <ul class="space-y-1">
                        <li><a href="#programmes" class="hover:text-white">Programmes</a></li>
                        <li><a href="#admissions" class="hover:text-white">Admissions Process</a></li>
                        <li><a href="#notices" class="hover:text-white">Notices</a></li>
                        <li><a href="#helpdesk" class="hover:text-white">Helpdesk &amp; FAQ</a></li>
                        <li><Link href="/login" class="hover:text-white">Login</Link></li>
                    </ul>
                </div>
                <div>
                    <div class="font-serif text-base text-white mb-2">Compliance</div>
                    <ul class="space-y-1 text-gray-400">
                        <li>UGC {{ s('ugc_status', '2(f) · 12(B)') }}</li>
                        <li>NAAC {{ s('naac_grade', 'A+') }} Accredited</li>
                        <li>DPDP Act 2023 compliant</li>
                        <li>Anti-Ragging Cell: {{ s('anti_ragging_phone', '1800-180-5522') }}</li>
                        <li>AISHE Registered</li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-white/10">
                <div class="max-w-7xl mx-auto px-6 py-3 flex flex-col md:flex-row justify-between items-center gap-2 text-[10px] text-gray-500">
                    <div>© {{ new Date().getFullYear() }} {{ s('college_name', 'Sardar Vallabhbhai National College') }}, Anand. All rights reserved.</div>
                    <div class="flex gap-3">
                        <a href="#" class="hover:text-white">Privacy</a>
                        <a href="#" class="hover:text-white">Terms</a>
                        <a href="#" class="hover:text-white">Accessibility</a>
                        <a href="#" class="hover:text-white">Sitemap</a>
                    </div>
                </div>
            </div>
        </footer>

    </div>
</template>
