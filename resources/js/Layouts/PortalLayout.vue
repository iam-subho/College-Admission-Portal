<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';

defineProps({
    title: { type: String, default: '' },
    breadcrumb: { type: Array, default: () => [] },
});

const page = usePage();
const user = computed(() => page.props.auth?.user || null);
const flash = computed(() => page.props.flash || {});

const role = computed(() => user.value?.roles?.[0] || null);
const userRoles = computed(() => user.value?.roles || []);
const isAdmin = computed(() => userRoles.value.some(r => ['super_admin', 'admin', 'staff'].includes(r)));
const isAdminOrSuper = computed(() => userRoles.value.some(r => ['super_admin', 'admin'].includes(r)));
const isStaff = computed(() => userRoles.value.includes('staff') && !isAdminOrSuper.value);
const isStudent = computed(() => role.value === 'student');

const profileStatus = computed(() => page.props.profile_status || null);

const studentNav = computed(() => [
    { label: 'Dashboard', route: 'student.dashboard' },
    { label: 'Personal Details', route: 'student.profile.personal', section: 'personal' },
    { label: 'Family Details', route: 'student.profile.family', section: 'family' },
    { label: 'Address & Contact', route: 'student.profile.address', section: 'address' },
    { label: 'Academic Records', route: 'student.academic-records.index', section: 'academic' },
    { label: 'Other Details', route: 'student.profile.other', section: 'other' },
    { label: 'Uploads', route: 'student.uploads.index', section: 'uploads' },
    { label: 'Submit Profile', route: 'student.profile.review' },
    { label: 'My Applications', route: 'student.applications.index' },
]);

const sectionDone = (section) => section && profileStatus.value && profileStatus.value[section];

// Grouped admin nav — items flagged `adminOnly: true` are hidden from staff.
// Whole groups collapse to nothing if all their items are admin-only and the
// user is a staff member.
const adminGroupsAll = [
    {
        key: 'overview', label: 'Overview',
        items: [
            { label: 'Dashboard', route: 'admin.dashboard' },
        ],
    },
    {
        key: 'setup', label: 'Setup',
        items: [
            { label: 'Admin & Staff Users', route: 'admin.users.index', adminOnly: true },
            { label: 'Sessions', route: 'admin.sessions.index', adminOnly: true },
            { label: 'Departments', route: 'admin.departments.index', adminOnly: true },
            { label: 'Programmes', route: 'admin.programmes.index', adminOnly: true },
            { label: 'Academic Subjects', route: 'admin.academic-subjects.index', adminOnly: true },
            { label: 'Reservations', route: 'admin.reservation-categories.index', adminOnly: true },
            { label: 'Course Pools', route: 'admin.course-pools.index', adminOnly: true },
            { label: 'Eligibility Rules', route: 'admin.eligibility-rules.index', adminOnly: true },
            { label: 'Fees', route: 'admin.fees.index', adminOnly: true },
        ],
    },
    {
        key: 'admissions', label: 'Admissions',
        items: [
            { label: 'Applications', route: 'admin.applications.index' },
            { label: 'Document Queue', route: 'admin.documents.index' },
            { label: 'Admission Tests', route: 'admin.admission-tests.index' },
            { label: 'Admission Rounds', route: 'admin.rounds.index', adminOnly: true },
            { label: 'Merit Lists', route: 'admin.merit-lists.index' },
            { label: 'Seat Allocations', route: 'admin.seat-allocations.index' },
            { label: 'Spot Admission', route: 'admin.spot-admission.index', adminOnly: true },
            { label: 'Withdrawals', route: 'admin.withdrawals.index', adminOnly: true },
            { label: 'Refunds', route: 'admin.refunds.index', adminOnly: true },
            { label: 'Refund Policies', route: 'admin.refund-policies.index', adminOnly: true },
        ],
    },
    {
        key: 'communications', label: 'Communications',
        items: [
            { label: 'Payment Gateways', route: 'admin.gateways.index', adminOnly: true },
            { label: 'SMS Providers', route: 'admin.sms-providers.index', adminOnly: true },
            { label: 'WhatsApp Providers', route: 'admin.whatsapp-providers.index', adminOnly: true },
            { label: 'Templates', route: 'admin.notification-templates.index', adminOnly: true },
            { label: 'Notification Logs', route: 'admin.notification-logs.index', adminOnly: true },
        ],
    },
    {
        key: 'site', label: 'Site Content',
        items: [
            { label: 'Notices', route: 'admin.notices.index', adminOnly: true },
            { label: 'Site Settings', route: 'admin.site-settings.index', adminOnly: true },
        ],
    },
    {
        key: 'compliance', label: 'Reports & Audit',
        items: [
            { label: 'Reports', route: 'admin.reports.index' },
            { label: 'Audit Log', route: 'admin.audit-log.index' },
            { label: 'DPDP Consents', route: 'admin.dpdp-consents.index', adminOnly: true },
        ],
    },
];

const adminGroups = computed(() => {
    if (isAdminOrSuper.value) return adminGroupsAll;
    // Staff: filter out adminOnly items, then drop empty groups.
    return adminGroupsAll
        .map(g => ({ ...g, items: g.items.filter(i => !i.adminOnly) }))
        .filter(g => g.items.length > 0);
});

const isCurrent = (routeName) => {
    try { return route().current(routeName); } catch { return false; }
};

// Collapsed state per group — persisted in localStorage, default expanded for the
// group containing the active route plus 'overview'.
const collapsed = ref({});

const restoreCollapsed = () => {
    try {
        const saved = JSON.parse(localStorage.getItem('admin_nav_collapsed') || '{}');
        collapsed.value = saved;
    } catch { /* ignore */ }
};

const persistCollapsed = () => {
    try { localStorage.setItem('admin_nav_collapsed', JSON.stringify(collapsed.value)); } catch { /* ignore */ }
};

const toggleGroup = (key) => {
    collapsed.value = { ...collapsed.value, [key]: !collapsed.value[key] };
    persistCollapsed();
};

const groupHasActive = (group) => group.items.some(i => isCurrent(i.route));

const groupIcon = (key) => ({
    overview: '◆',
    setup: '⚙',
    admissions: '✎',
    communications: '✉',
    site: '◈',
    compliance: '⚖',
}[key] || '•');

const isGroupCollapsed = (group) => {
    // If the group has the active item, force-expand regardless of saved state.
    if (groupHasActive(group)) return false;
    return !!collapsed.value[group.key];
};

onMounted(restoreCollapsed);

const navTitle = computed(() => {
    if (isAdminOrSuper.value) return 'Administration';
    if (isStaff.value) return 'Staff Console';
    if (isStudent.value) return 'Student Portal';
    return '';
});

const logout = () => router.post('/logout');
</script>

<template>
    <div class="min-h-screen bg-cream">
        <header class="bg-navy text-white">
            <div class="max-w-7xl mx-auto px-6 py-3 flex items-center justify-between">
                <Link href="/" class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-maroon text-white text-xs font-bold flex items-center justify-center">
                        SVNC<br>1956
                    </div>
                    <div class="leading-tight">
                        <div class="font-serif text-base">Sardar Vallabhbhai National College</div>
                        <div class="text-xs text-gray-300">Online Admissions · Session 2026-27</div>
                    </div>
                </Link>
                <div class="flex items-center gap-4 text-sm">
                    <span v-if="user" class="text-gray-300">
                        {{ user.name }} · <span class="text-saffron">{{ role || 'user' }}</span>
                    </span>
                    <button @click="logout" class="px-3 py-1 text-xs border border-white/30 rounded hover:bg-white/10">
                        Logout
                    </button>
                </div>
            </div>
            <div class="h-1 flex">
                <div class="flex-1 bg-saffron"></div>
                <div class="flex-1 bg-white"></div>
                <div class="flex-1 bg-green-700"></div>
            </div>
        </header>

        <div class="max-w-7xl mx-auto flex">
            <aside v-if="isAdmin || isStudent" class="w-56 bg-white border-r border-border min-h-[calc(100vh-4rem)] py-4">
                <div class="px-4 mb-3 text-xs uppercase tracking-wider text-ink-mute font-semibold">
                    {{ navTitle }}
                </div>

                <!-- Student: flat list with profile-section badges -->
                <nav v-if="isStudent" class="flex flex-col">
                    <Link
                        v-for="item in studentNav"
                        :key="item.route"
                        :href="route(item.route)"
                        class="px-4 py-2 text-sm border-l-2 flex items-center justify-between gap-2"
                        :class="isCurrent(item.route)
                            ? 'border-saffron bg-saffron-soft text-maroon font-semibold'
                            : 'border-transparent text-ink hover:bg-cream'"
                    >
                        <span>{{ item.label }}</span>
                        <span v-if="item.section && profileStatus" class="text-[10px] font-mono px-1.5 py-0.5 rounded uppercase tracking-wider"
                            :class="sectionDone(item.section)
                                ? 'bg-green-100 text-green-800'
                                : 'bg-amber-100 text-amber-800'">
                            {{ sectionDone(item.section) ? '✓' : '•' }}
                        </span>
                    </Link>
                </nav>

                <!-- Admin: collapsible groups -->
                <nav v-else-if="isAdmin" class="flex flex-col">
                    <div v-for="group in adminGroups" :key="group.key" class="mb-2">
                        <button v-if="group.items.length > 1"
                            type="button"
                            @click="toggleGroup(group.key)"
                            class="w-full px-3 py-2 flex items-center justify-between bg-navy/90 text-white border-l-4 border-saffron hover:bg-navy"
                            :class="groupHasActive(group) ? 'bg-navy' : ''">
                            <span class="flex items-center gap-2">
                                <span class="text-saffron text-[10px]">{{ groupIcon(group.key) }}</span>
                                <span class="text-xs font-bold uppercase tracking-wider">{{ group.label }}</span>
                            </span>
                            <span class="text-[10px] text-saffron-soft">{{ isGroupCollapsed(group) ? '▸' : '▾' }}</span>
                        </button>
                        <div v-show="!isGroupCollapsed(group) || group.items.length === 1" class="py-1 bg-cream/40">
                            <Link
                                v-for="item in group.items"
                                :key="item.route"
                                :href="route(item.route)"
                                class="px-4 py-1.5 text-sm border-l-2 flex items-center gap-2 ml-3"
                                :class="isCurrent(item.route)
                                    ? 'border-saffron bg-saffron-soft text-maroon font-semibold'
                                    : 'border-transparent text-ink hover:bg-cream'"
                            >
                                <span>{{ item.label }}</span>
                            </Link>
                        </div>
                    </div>
                </nav>

                <div v-if="isStudent && profileStatus" class="mx-4 mt-4 p-3 border border-border rounded bg-cream">
                    <div class="text-[10px] uppercase tracking-wider text-ink-mute mb-1">Profile</div>
                    <div class="font-serif text-lg text-maroon">{{ profileStatus.percent }}%</div>
                    <div class="h-1.5 bg-white rounded mt-1 overflow-hidden">
                        <div class="h-full bg-saffron" :style="{ width: profileStatus.percent + '%' }"></div>
                    </div>
                    <div v-if="profileStatus.locked" class="mt-2 text-[10px] text-green-700 font-semibold">
                        🔒 Submitted &amp; locked
                    </div>
                    <div v-else-if="profileStatus.all_complete" class="mt-2 text-[10px] text-saffron-deep font-semibold">
                        Ready to submit →
                    </div>
                </div>
            </aside>

            <main class="flex-1 p-6">
                <div v-if="breadcrumb.length" class="text-xs text-ink-mute mb-3">
                    <span v-for="(crumb, i) in breadcrumb" :key="i">
                        <span v-if="i > 0" class="mx-1">›</span>
                        {{ crumb }}
                    </span>
                </div>

                <h1 v-if="title" class="font-serif text-2xl text-maroon mb-4">{{ title }}</h1>

                <div v-if="flash.success" class="mb-4 px-4 py-2 bg-green-50 border border-green-200 text-green-800 text-sm rounded">
                    {{ flash.success }}
                </div>
                <div v-if="flash.error" class="mb-4 px-4 py-2 bg-red-50 border border-red-200 text-red-800 text-sm rounded">
                    {{ flash.error }}
                </div>

                <slot />
            </main>
        </div>
    </div>
</template>
