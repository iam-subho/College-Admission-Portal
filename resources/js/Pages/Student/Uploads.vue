<script setup>
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import Button from '@/Components/Ui/Button.vue';
import SectionStatusBanner from '@/Components/Ui/SectionStatusBanner.vue';
import { ref } from 'vue';

defineProps({
    items: { type: Array, required: true },
    application: { type: Object, default: null },
    digilocker_linked: { type: Boolean, default: false },
});

const uploading = ref({});

const upload = (typeId, file, applicationId) => {
    if (! file) return;
    const form = new FormData();
    form.append('file', file);
    if (applicationId) form.append('application_id', applicationId);
    uploading.value[typeId] = true;
    router.post(`/student/uploads/${typeId}`, form, {
        forceFormData: true,
        onFinish: () => { uploading.value[typeId] = false; },
    });
};

const fetchFromDigilocker = (typeId, applicationId) => {
    uploading.value[typeId] = true;
    router.post(`/student/uploads/${typeId}/digilocker`,
        applicationId ? { application_id: applicationId } : {},
        { onFinish: () => { uploading.value[typeId] = false; } },
    );
};

const statusBadge = {
    pending: 'bg-gray-100 text-gray-700',
    approved: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
    resubmit: 'bg-amber-100 text-amber-800',
};
</script>

<template>
    <Head title="Document Uploads" />
    <PortalLayout title="Document Uploads" :breadcrumb="['Student', 'Uploads']">
        <SectionStatusBanner section="uploads" />

        <div class="bg-white border border-border rounded p-4 mb-4 flex items-center justify-between">
            <div>
                <h2 class="font-serif text-lg text-maroon">DigiLocker</h2>
                <p class="text-xs text-ink-mute mt-1">
                    Link DigiLocker to auto-fetch your marksheets, Aadhaar, and certificates issued by Govt of India.
                </p>
            </div>
            <div>
                <span v-if="digilocker_linked" class="px-2 py-0.5 text-xs rounded bg-green-100 text-green-800 mr-2">
                    Linked
                </span>
                <Link v-if="!digilocker_linked" href="/digilocker/link" class="px-3 py-1.5 text-sm bg-navy text-white rounded">
                    Link DigiLocker
                </Link>
                <button v-else
                    @click="router.post('/digilocker/unlink', {}, { preserveScroll: true })"
                    class="px-3 py-1.5 text-sm border border-border rounded text-ink-mute hover:bg-cream">
                    Unlink
                </button>
            </div>
        </div>

        <p v-if="application" class="text-sm text-ink-mute mb-3">
            Application: <strong class="font-mono">{{ application.application_number || 'Draft' }}</strong>
        </p>

        <div class="space-y-3">
            <div v-for="item in items" :key="item.type.id" class="bg-white border border-border rounded p-4">
                <div class="flex justify-between items-start gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <h3 class="font-medium text-ink">
                                {{ item.type.label }}
                                <span v-if="item.type.required_by_default" class="text-maroon">*</span>
                            </h3>
                            <span v-if="item.document" class="px-2 py-0.5 text-xs rounded" :class="statusBadge[item.document.status] || 'bg-gray-100'">
                                {{ item.document.status }}
                            </span>
                            <span v-if="item.document?.source === 'digilocker'" class="px-2 py-0.5 text-xs rounded bg-blue-100 text-blue-800">
                                via DigiLocker
                            </span>
                        </div>
                        <p class="text-xs text-ink-mute mt-1">
                            Max {{ item.type.max_size_kb }} KB ·
                            <span v-if="item.type.digilocker_doc_type" class="ml-1">DigiLocker: {{ item.type.digilocker_doc_type }}</span>
                        </p>
                        <div v-if="item.document?.rejection_reason" class="mt-2 text-xs text-red-700 bg-red-50 border border-red-200 rounded p-2">
                            <strong>Rejected:</strong> {{ item.document.rejection_reason }}<br>
                            Please re-upload a corrected version.
                        </div>
                        <div v-if="item.document" class="text-xs text-ink-mute mt-2">
                            {{ item.document.original_name }} · {{ (item.document.size_bytes / 1024).toFixed(0) }} KB
                            <a :href="`/documents/${item.document.id}/download`" class="text-maroon hover:underline ml-2">View</a>
                        </div>
                    </div>
                    <div class="flex flex-col gap-2 w-48">
                        <label class="px-3 py-1.5 text-sm text-center border border-border rounded cursor-pointer hover:bg-cream"
                            :class="uploading[item.type.id] ? 'opacity-50' : ''">
                            <input type="file"
                                @change="(e) => upload(item.type.id, e.target.files[0], application?.id)"
                                class="hidden"
                                :accept="(item.type.allowed_mimes || []).join(',')" />
                            {{ item.document ? 'Re-upload' : 'Upload manually' }}
                        </label>
                        <button v-if="item.type.digilocker_doc_type && digilocker_linked"
                            @click="fetchFromDigilocker(item.type.id, application?.id)"
                            :disabled="uploading[item.type.id]"
                            class="px-3 py-1.5 text-sm bg-saffron text-white rounded hover:bg-saffron/90 disabled:opacity-50">
                            Fetch from DigiLocker
                        </button>
                        <p v-else-if="item.type.digilocker_doc_type && !digilocker_linked" class="text-xs text-ink-mute text-center">
                            Link DigiLocker to enable auto-fetch
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </PortalLayout>
</template>
