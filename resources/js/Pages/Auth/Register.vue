<script setup>
import { useForm, Link, Head } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import InputText from '@/Components/Ui/InputText.vue';
import Button from '@/Components/Ui/Button.vue';
import { computed } from 'vue';
import { useSite } from '@/Composables/useSite.js';

defineProps({
    dpdp_version: { type: String, default: '' },
});

const { sessionCode } = useSite();
const subtitle = computed(() =>
    sessionCode.value ? `UG & PG admissions — Session ${sessionCode.value}` : 'UG & PG admissions',
);

const form = useForm({
    name: '',
    email: '',
    mobile: '',
    password: '',
    password_confirmation: '',
    dpdp_consent: false,
});

const submit = () => form.post('/register', {
    onError: () => form.reset('password', 'password_confirmation'),
});
</script>

<template>
    <Head title="Register" />
    <AuthLayout title="Create Account" :subtitle="subtitle">
        <form @submit.prevent="submit" class="space-y-4">
            <InputText
                v-model="form.name"
                label="Full Name (as per 10th marksheet)"
                required
                autocomplete="name"
                :error="form.errors.name"
            />
            <InputText
                v-model="form.email"
                type="email"
                label="Email Address"
                required
                autocomplete="email"
                :error="form.errors.email"
            />
            <InputText
                v-model="form.mobile"
                label="Mobile Number"
                required
                prefix="+91"
                maxlength="10"
                autocomplete="tel"
                placeholder="10-digit mobile"
                :error="form.errors.mobile"
            />
            <InputText
                v-model="form.password"
                type="password"
                label="Password"
                required
                autocomplete="new-password"
                :error="form.errors.password"
                placeholder="Min 8 chars, 1 uppercase, 1 digit"
            />
            <InputText
                v-model="form.password_confirmation"
                type="password"
                label="Confirm Password"
                required
                autocomplete="new-password"
            />

            <div class="bg-cream border border-border rounded p-3 text-xs text-ink-mute leading-relaxed">
                <label class="flex items-start gap-2 cursor-pointer">
                    <input
                        type="checkbox"
                        v-model="form.dpdp_consent"
                        class="mt-0.5 accent-saffron"
                    />
                    <span>
                        I consent to the collection and processing of my personal data
                        in accordance with the Digital Personal Data Protection Act 2023
                        for the purpose of admission processing. (DPDP version
                        <strong>{{ dpdp_version }}</strong>)
                    </span>
                </label>
                <p v-if="form.errors.dpdp_consent" class="text-xs text-red-600 mt-1">
                    {{ form.errors.dpdp_consent }}
                </p>
            </div>

            <Button type="submit" block :loading="form.processing">
                Create Account & Send OTP
            </Button>

            <p class="text-center text-xs text-ink-mute">
                Already registered?
                <Link href="/login" class="text-maroon font-semibold hover:underline">Sign in</Link>
            </p>
        </form>
    </AuthLayout>
</template>
