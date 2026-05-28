<script setup>
import { useForm, Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import InputText from '@/Components/Ui/InputText.vue';
import Button from '@/Components/Ui/Button.vue';

const props = defineProps({
    mobile: { type: String, required: true },
});

const page = usePage();
const dev = computed(() => page.props.dev || null);

const form = useForm({
    mobile: props.mobile,
    code: '',
});

const resend = useForm({
    mobile: props.mobile,
    purpose: 'registration',
});

const submit = () => form.post('/register/verify');
const resendOtp = () => resend.post('/otp/send', { preserveScroll: true });
</script>

<template>
    <Head title="Verify Mobile" />
    <AuthLayout title="Verify Mobile" :subtitle="`We sent a 6-digit code to +91 ${mobile}`">
        <div v-if="dev" class="mb-3 px-3 py-2 bg-amber-50 border border-amber-300 rounded text-xs">
            <span class="font-bold text-amber-800 uppercase tracking-wider">{{ dev.env }} mode:</span>
            <span class="text-amber-700"> use OTP </span>
            <code class="font-mono font-bold text-amber-900 bg-amber-100 px-1.5 py-0.5 rounded">{{ dev.master_otp }}</code>
            <span class="text-amber-700"> for any verification</span>
        </div>
        <form @submit.prevent="submit" class="space-y-4">
            <InputText
                v-model="form.code"
                label="6-digit OTP"
                required
                maxlength="6"
                placeholder="••••••"
                :error="form.errors.code"
            />

            <Button type="submit" block :loading="form.processing">
                Verify & Continue
            </Button>

            <div class="text-center text-xs text-ink-mute">
                Didn't receive the code?
                <button
                    type="button"
                    @click="resendOtp"
                    :disabled="resend.processing"
                    class="text-maroon font-semibold hover:underline disabled:opacity-50"
                >
                    Resend
                </button>
            </div>
        </form>
    </AuthLayout>
</template>
