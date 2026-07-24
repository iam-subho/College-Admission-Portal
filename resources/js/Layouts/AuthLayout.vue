<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import CollegeMark from '@/Components/Ui/CollegeMark.vue';
import { useSite } from '@/Composables/useSite.js';

defineProps({
    title: { type: String, default: '' },
    subtitle: { type: String, default: '' },
});

const page = usePage();
const flash = computed(() => page.props.flash || {});
const { helplinePhone, helplineEmail } = useSite();
</script>

<template>
    <div class="min-h-screen bg-cream flex flex-col">
        <header class="bg-navy text-white">
            <div class="max-w-6xl mx-auto px-6 py-3 flex items-center justify-between">
                <Link href="/">
                    <CollegeMark />
                </Link>
                <Link href="/" class="text-xs underline-offset-4 hover:underline">Back to home</Link>
            </div>
            <div class="h-1 flex">
                <div class="flex-1 bg-saffron"></div>
                <div class="flex-1 bg-white"></div>
                <div class="flex-1 bg-green-700"></div>
            </div>
        </header>

        <main class="flex-1 flex items-center justify-center p-6">
            <div class="w-full max-w-md">
                <div v-if="flash.success" class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-800 text-sm rounded">
                    {{ flash.success }}
                </div>
                <div v-if="flash.error" class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-800 text-sm rounded">
                    {{ flash.error }}
                </div>

                <div class="bg-white border border-border rounded shadow-sm">
                    <div class="px-6 py-4 border-b border-border bg-maroon text-white rounded-t">
                        <h1 class="font-serif text-xl">{{ title }}</h1>
                        <p v-if="subtitle" class="text-xs opacity-80 mt-0.5">{{ subtitle }}</p>
                    </div>
                    <div class="p-6">
                        <slot />
                    </div>
                </div>

                <p class="text-xs text-ink-mute text-center mt-4">
                    Helpline: {{ helplinePhone }} · {{ helplineEmail }}
                </p>
            </div>
        </main>
    </div>
</template>
