<script setup>
import { useForm, Link, Head, usePage } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import InputText from '@/Components/Ui/InputText.vue';
import Button from '@/Components/Ui/Button.vue';
import { ref, computed } from 'vue';

const page = usePage();
const dev = computed(() => page.props.dev || null);

const sendForm = useForm({
    mobile: '',
    purpose: 'password_reset',
});

const resetForm = useForm({
    mobile: '',
    code: '',
    password: '',
    password_confirmation: '',
});

const otpSent = ref(false);

const sendOtp = () => sendForm.post('/password/forgot/send-otp', {
    preserveScroll: true,
    onSuccess: () => {
        resetForm.mobile = sendForm.mobile;
        otpSent.value = true;
    },
});

const reset = () => resetForm.post('/password/forgot/reset');
</script>

<template>
    <Head title="Forgot Password" />
    <AuthLayout title="Reset Password" subtitle="Verify your mobile and set a new password">
        <div v-if="!otpSent">
            <form @submit.prevent="sendOtp" class="space-y-4">
                <InputText
                    v-model="sendForm.mobile"
                    label="Registered Mobile Number"
                    required
                    prefix="+91"
                    maxlength="10"
                    autocomplete="tel"
                    :error="sendForm.errors.mobile"
                />
                <Button type="submit" block :loading="sendForm.processing">Send Reset OTP</Button>
            </form>
        </div>

        <div v-else>
            <div v-if="dev" class="mb-3 px-3 py-2 bg-amber-50 border border-amber-300 rounded text-xs">
                <span class="font-bold text-amber-800 uppercase tracking-wider">{{ dev.env }} mode:</span>
                <span class="text-amber-700"> use OTP </span>
                <code class="font-mono font-bold text-amber-900 bg-amber-100 px-1.5 py-0.5 rounded">{{ dev.master_otp }}</code>
                <span class="text-amber-700"> for any verification</span>
            </div>
            <form @submit.prevent="reset" class="space-y-4">
                <InputText
                    v-model="resetForm.code"
                    label="6-digit OTP"
                    required
                    maxlength="6"
                    :error="resetForm.errors.code"
                />
                <InputText
                    v-model="resetForm.password"
                    type="password"
                    label="New Password"
                    required
                    autocomplete="new-password"
                    :error="resetForm.errors.password"
                />
                <InputText
                    v-model="resetForm.password_confirmation"
                    type="password"
                    label="Confirm New Password"
                    required
                    autocomplete="new-password"
                />
                <Button type="submit" block :loading="resetForm.processing">Reset Password</Button>
            </form>
        </div>

        <p class="text-center text-xs text-ink-mute pt-4 border-t border-border mt-4">
            Remembered it?
            <Link href="/login" class="text-maroon font-semibold hover:underline">Sign in</Link>
        </p>
    </AuthLayout>
</template>
