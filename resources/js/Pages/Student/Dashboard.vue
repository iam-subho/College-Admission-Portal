<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';

defineProps({
    student: { type: Object, required: true },
    profile_completion: { type: Number, default: 0 },
    applications: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Student Dashboard" />
    <PortalLayout title="Student Dashboard" :breadcrumb="['Home', 'Student']">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white border border-border rounded p-4">
                <div class="text-xs uppercase tracking-wider text-ink-mute">Profile Completion</div>
                <div class="font-serif text-3xl text-maroon mt-1">{{ profile_completion }}%</div>
                <div class="mt-2 h-2 bg-cream rounded overflow-hidden">
                    <div class="h-2 bg-saffron" :style="{ width: profile_completion + '%' }"></div>
                </div>
                <Link href="/student/profile" class="text-xs text-maroon hover:underline mt-2 inline-block">
                    {{ profile_completion < 100 ? 'Complete profile →' : 'View profile →' }}
                </Link>
            </div>
            <div class="bg-white border border-border rounded p-4">
                <div class="text-xs uppercase tracking-wider text-ink-mute">Applications</div>
                <div class="font-serif text-3xl text-maroon mt-1">{{ applications.length }}</div>
                <Link href="/student/applications" class="text-xs text-maroon hover:underline mt-2 inline-block">
                    Manage applications →
                </Link>
            </div>
            <div class="bg-white border border-border rounded p-4">
                <div class="text-xs uppercase tracking-wider text-ink-mute">Reg. Number</div>
                <div class="font-mono text-sm text-maroon mt-2">{{ student.registration_number || 'Pending' }}</div>
            </div>
        </div>

        <div v-if="applications.length" class="bg-white border border-border rounded p-4 mb-6">
            <h2 class="font-serif text-lg text-maroon mb-3">Application Status</h2>
            <table class="w-full text-sm">
                <thead class="text-xs uppercase tracking-wider text-ink-mute">
                    <tr>
                        <th class="text-left py-2">Number</th>
                        <th class="text-left py-2">Programme</th>
                        <th class="text-left py-2">Status</th>
                        <th class="text-right py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="a in applications" :key="a.id" class="border-t border-border">
                        <td class="py-2 font-mono text-xs">{{ a.application_number || 'Draft' }}</td>
                        <td class="py-2">{{ a.program?.code }} — {{ a.program?.name }}</td>
                        <td class="py-2">
                            <span class="px-2 py-0.5 text-xs rounded bg-blue-100 text-blue-800">{{ a.status }}</span>
                        </td>
                        <td class="py-2 text-right">
                            <Link :href="`/student/applications/${a.id}`" class="text-xs text-maroon hover:underline">
                                Open →
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </PortalLayout>
</template>
