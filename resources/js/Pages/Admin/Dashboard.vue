<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';

defineProps({
    stats: { type: Object, required: true },
});
</script>

<template>
    <Head title="Admin Dashboard" />
    <PortalLayout title="Admin Dashboard" :breadcrumb="['Home', 'Admin']">
        <p class="text-sm text-ink-mute mb-6">
            Online Admission System · Session 2026-27 · Last refreshed {{ new Date().toLocaleString('en-IN') }}
        </p>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white border border-border rounded p-4">
                <div class="text-xs uppercase tracking-wider text-ink-mute">Sessions</div>
                <div class="font-serif text-3xl text-maroon mt-1">{{ stats.sessions }}</div>
                <div class="text-xs text-ink-mute mt-1">
                    Active: {{ stats.active_session?.code || '—' }}
                </div>
            </div>
            <div class="bg-white border border-border rounded p-4">
                <div class="text-xs uppercase tracking-wider text-ink-mute">Departments</div>
                <div class="font-serif text-3xl text-maroon mt-1">{{ stats.departments }}</div>
            </div>
            <div class="bg-white border border-border rounded p-4">
                <div class="text-xs uppercase tracking-wider text-ink-mute">Programmes</div>
                <div class="font-serif text-3xl text-maroon mt-1">{{ stats.programmes }}</div>
            </div>
            <div class="bg-white border border-border rounded p-4">
                <div class="text-xs uppercase tracking-wider text-ink-mute">Fee Heads</div>
                <div class="font-serif text-3xl text-maroon mt-1">{{ stats.fee_heads }}</div>
            </div>
        </div>

        <div class="bg-saffron-soft border border-saffron/30 rounded p-4 text-sm text-maroon">
            <strong>Phase 2 complete.</strong> Use the sidebar to configure sessions, programmes, reservation
            matrix, and fee heads. Application flow + admit cards + merit will follow in later phases.
        </div>
    </PortalLayout>
</template>
