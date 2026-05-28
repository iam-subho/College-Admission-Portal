<script setup>
import { useForm, Link, Head, usePage } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import InputText from '@/Components/Ui/InputText.vue';
import Button from '@/Components/Ui/Button.vue';
import { computed, ref } from 'vue';

const sendForm = useForm({
    mobile: '',
    purpose: 'login',
});

const verifyForm = useForm({
    mobile: '',
    code: '',
});

const page = usePage();
const otpSent = ref(false);

const sendOtp = () => sendForm.post('/otp/send', {
    preserveScroll: true,
    onSuccess: () => {
        verifyForm.mobile = sendForm.mobile;
        otpSent.value = true;
    },
});

const verifyOtp = () => verifyForm.post('/otp/verify-login');

const flashOtpSent = computed(() => page.props.flash?.otp_sent === true);
const dev = computed(() => page.props.dev || null);
</script>

<template>
    <Head title="OTP Login" />
    <AuthLayout title="Sign In with OTP" subtitle="Enter your registered mobile to receive a one-time code">
        <div v-if="!otpSent && !flashOtpSent">
            <form @submit.prevent="sendOtp" class="space-y-4">
                <InputText
                    v-model="sendForm.mobile"
                    label="Mobile Number"
                    required
                    prefix="+91"
                    maxlength="10"
                    placeholder="10-digit mobile"
                    autocomplete="tel"
                    :error="sendForm.errors.mobile"
                />
                <Button type="submit" block :loading="sendForm.processing">Send OTP</Button>
            </form>
        </div>

        <div v-else>
            <p class="text-xs text-ink-mute mb-3">
                Code sent to <strong>+91 {{ verifyForm.mobile }}</strong>.
            </p>
            <div v-if="dev" class="mb-3 px-3 py-2 bg-amber-50 border border-amber-300 rounded text-xs">
                <span class="font-bold text-amber-800 uppercase tracking-wider">{{ dev.env }} mode:</span>
                <span class="text-amber-700"> use OTP </span>
                <code class="font-mono font-bold text-amber-900 bg-amber-100 px-1.5 py-0.5 rounded">{{ dev.master_otp }}</code>
                <span class="text-amber-700"> for any verification</span>
            </div>
            <form @submit.prevent="verifyOtp" class="space-y-4">
                <InputText
                    v-model="verifyForm.code"
                    label="6-digit OTP"
                    required
                    maxlength="6"
                    placeholder="••••••"
                    :error="verifyForm.errors.code"
                />
                <Button type="submit" block :loading="verifyForm.processing">Verify & Sign In</Button>
                <div class="text-center">
                    <button
                        type="button"
                        @click="otpSent = false"
                        class="text-xs text-maroon hover:underline"
                    >
                        Use a different number
                    </button>
                </div>
            </form>
        </div>

        <p class="text-center text-xs text-ink-mute pt-4 border-t border-border mt-4">
            Prefer password?
            <Link href="/login" class="text-maroon font-semibold hover:underline">Email login</Link>
        </p>
    </AuthLayout>
</template>
