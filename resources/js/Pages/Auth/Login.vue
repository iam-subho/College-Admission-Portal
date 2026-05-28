<script setup>
import { useForm, Link, Head } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import InputText from '@/Components/Ui/InputText.vue';
import Button from '@/Components/Ui/Button.vue';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => form.post('/login', {
    onError: () => form.reset('password'),
});
</script>

<template>
    <Head title="Sign In" />
    <AuthLayout title="Sign In" subtitle="Use email + password, or one-time mobile OTP">
        <form @submit.prevent="submit" class="space-y-4">
            <InputText
                v-model="form.email"
                type="email"
                label="Email"
                required
                autocomplete="email"
                :error="form.errors.email"
            />
            <InputText
                v-model="form.password"
                type="password"
                label="Password"
                required
                autocomplete="current-password"
                :error="form.errors.password"
            />

            <div class="flex items-center justify-between text-xs">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" v-model="form.remember" class="accent-saffron" />
                    <span class="text-ink-mute">Remember me</span>
                </label>
                <Link href="/password/forgot" class="text-maroon hover:underline">Forgot password?</Link>
            </div>

            <Button type="submit" block :loading="form.processing">Sign In</Button>

            <div class="relative my-3">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-border"></div>
                </div>
                <div class="relative flex justify-center">
                    <span class="px-2 text-xs text-ink-mute bg-white">or</span>
                </div>
            </div>

            <Link
                href="/login/otp"
                class="block w-full text-center px-4 py-2 text-sm font-semibold rounded border border-border text-maroon hover:bg-saffron-soft"
            >
                Sign in with Mobile OTP
            </Link>

            <p class="text-center text-xs text-ink-mute pt-2">
                New here?
                <Link href="/register" class="text-maroon font-semibold hover:underline">Create an account</Link>
            </p>
        </form>
    </AuthLayout>
</template>
