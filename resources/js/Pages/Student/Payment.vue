<script setup>
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import Button from '@/Components/Ui/Button.vue';
import { ref, computed, onMounted, watch } from 'vue';
import { formatDateTime } from '@/utils/date.js';

const props = defineProps({
    application: { type: Object, required: true },
    fee: { type: Object, required: true },              // { amount, source }
    gst_percent: { type: Number, default: 18 },
    gateways: { type: Array, required: true },
    order: { type: Object, default: null },
    resume_checkout: { type: Object, default: null },
    prefill: { type: Object, default: () => ({}) },
});

const page = usePage();
const checkoutPayload = computed(() => page.props?.flash?.checkout || null);
const flashError = computed(() => page.props?.flash?.error || null);

const inr = (n) => '₹ ' + Number(n).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');

const form = useForm({ gateway_id: props.gateways[0]?.id || '' });

const selectedGateway = computed(() =>
    props.gateways.find(g => g.id === form.gateway_id) || null,
);

const convenience = computed(() => selectedGateway.value?.convenience_fee || 0);
const gst = computed(() => Math.round(convenience.value * props.gst_percent) / 100);
const total = computed(() => Number(props.fee.amount) + Number(convenience.value) + Number(gst.value));

const startPayment = () => form.post(`/student/applications/${props.application.id}/payment/init`, {
    preserveScroll: true,
});

const mockPay = () => router.post(`/student/payments/${props.order.id}/mock-pay`, {}, {
    preserveScroll: true,
});

const isPaid = computed(() => props.order?.status === 'paid');

// ---- Razorpay Checkout integration ----

const launchingModal = ref(false);

const loadRazorpayScript = () => new Promise((resolve, reject) => {
    if (window.Razorpay) return resolve();
    const existing = document.querySelector('script[src="https://checkout.razorpay.com/v1/checkout.js"]');
    if (existing) {
        existing.addEventListener('load', () => resolve());
        existing.addEventListener('error', () => reject(new Error('checkout.js load failed')));
        return;
    }
    const s = document.createElement('script');
    s.src = 'https://checkout.razorpay.com/v1/checkout.js';
    s.async = true;
    s.onload = () => resolve();
    s.onerror = () => reject(new Error('checkout.js load failed'));
    document.head.appendChild(s);
});

const openRazorpay = async (payload) => {
    if (!payload || payload.gateway_code !== 'razorpay' || payload.mode === 'stub') return;
    if (!payload.key) {
        alert('Razorpay key_id is not configured for this gateway. Open Admin → Gateways and add test/live keys.');
        return;
    }

    launchingModal.value = true;
    try {
        await loadRazorpayScript();
    } catch (e) {
        launchingModal.value = false;
        alert('Failed to load Razorpay Checkout JS. Check internet connectivity.');
        return;
    }

    const options = {
        key: payload.key,
        order_id: payload.gateway_order_id,
        amount: payload.amount_paise,
        currency: payload.currency,
        name: payload.name || 'SVNC Admissions',
        description: payload.description || 'Application Fee',
        prefill: {
            name: props.prefill.name || '',
            email: props.prefill.email || '',
            contact: props.prefill.contact || '',
        },
        notes: {
            application_number: props.application.application_number,
            internal_order_id: String(payload.internal_order_id),
        },
        theme: { color: '#7a1e1e' }, // maroon
        callback_url: payload.callback_url,
        redirect: true, // Razorpay POSTs to callback_url and follows the redirect server-side
        modal: {
            ondismiss: () => { launchingModal.value = false; },
        },
    };

    const rzp = new window.Razorpay(options);
    rzp.on('payment.failed', (resp) => {
        launchingModal.value = false;
        alert(`Payment failed: ${resp.error?.description || 'Unknown error'}`);
    });
    rzp.open();
};

// Auto-launch on a fresh init (flash payload), OR resume payload from server for an in-flight order.
watch(checkoutPayload, (v) => { if (v) openRazorpay(v); }, { immediate: false });
onMounted(() => {
    if (checkoutPayload.value) {
        openRazorpay(checkoutPayload.value);
    } else if (props.resume_checkout && !isPaid.value) {
        openRazorpay(props.resume_checkout);
    }
});

// Resume button — re-attempts the modal using the server-provided resume payload.
const resumePayment = () => {
    if (props.resume_checkout) openRazorpay(props.resume_checkout);
};
</script>

<template>
    <Head title="Application Fee Payment" />
    <PortalLayout title="Application Fee Payment" :breadcrumb="['Student', 'Applications', application.application_number, 'Payment']">

        <div v-if="flashError" class="mb-4 p-3 bg-red-50 border border-red-300 text-red-800 text-sm rounded">
            {{ flashError }}
        </div>

        <div class="mb-4 flex justify-between items-center">
            <div class="text-sm text-ink-mute">
                Application: <span class="font-mono">{{ application.application_number }}</span> ·
                {{ application.program?.code }} {{ application.program?.name }}
            </div>
            <span v-if="isPaid" class="px-2 py-0.5 text-xs rounded bg-green-100 text-green-800 font-semibold">✓ Paid</span>
            <span v-else class="px-2 py-0.5 text-xs rounded bg-amber-100 text-amber-800">Payment pending</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <!-- LEFT: Fee particulars -->
            <section class="md:col-span-3 bg-white border border-border rounded">
                <header class="px-4 py-2 border-b border-border bg-cream">
                    <h2 class="font-serif text-base text-maroon">Fee Particulars</h2>
                </header>
                <table class="w-full text-sm">
                    <thead class="text-xs uppercase text-ink-mute">
                        <tr>
                            <th class="text-left px-4 py-2 w-10">#</th>
                            <th class="text-left px-4 py-2">Head</th>
                            <th class="text-right px-4 py-2 w-32">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-t border-border">
                            <td class="px-4 py-2 text-center">1</td>
                            <td class="px-4 py-2">Application &amp; Processing Fee</td>
                            <td class="px-4 py-2 text-right font-mono">{{ inr(fee.amount) }}</td>
                        </tr>
                        <tr v-if="convenience > 0" class="border-t border-border">
                            <td class="px-4 py-2 text-center">2</td>
                            <td class="px-4 py-2">Convenience Charge</td>
                            <td class="px-4 py-2 text-right font-mono">{{ inr(convenience) }}</td>
                        </tr>
                        <tr v-if="gst > 0" class="border-t border-border">
                            <td class="px-4 py-2 text-center">3</td>
                            <td class="px-4 py-2">GST @ {{ gst_percent }}%</td>
                            <td class="px-4 py-2 text-right font-mono">{{ inr(gst) }}</td>
                        </tr>
                        <tr class="border-t-2 border-maroon bg-saffron-soft font-semibold">
                            <td colspan="2" class="px-4 py-2 text-right">Total Payable</td>
                            <td class="px-4 py-2 text-right font-mono text-maroon">{{ inr(total) }}</td>
                        </tr>
                    </tbody>
                </table>
                <p v-if="fee.source === 'default'" class="px-4 py-2 text-xs text-ink-mute italic">
                    Fee not configured in fee structures — using default rate.
                </p>
            </section>

            <!-- RIGHT: Gateway picker / payment action -->
            <section class="md:col-span-2 bg-white border border-border rounded">
                <header class="px-4 py-2 border-b border-border bg-cream">
                    <h2 class="font-serif text-base text-maroon">Payment Method</h2>
                </header>
                <div class="p-4 space-y-3">
                    <div v-if="!gateways.length" class="text-sm text-red-600">
                        No payment gateways are active. Please contact admissions.
                    </div>
                    <label v-for="g in gateways" :key="g.id"
                        class="block px-3 py-2 border rounded cursor-pointer"
                        :class="form.gateway_id === g.id ? 'border-maroon bg-saffron-soft' : 'border-border hover:bg-cream'">
                        <div class="flex items-center gap-2">
                            <input type="radio" :value="g.id" v-model="form.gateway_id" />
                            <div class="flex-1">
                                <div class="text-sm font-medium">{{ g.display_name }}</div>
                                <div class="text-xs text-ink-mute">
                                    {{ g.mode === 'stub' ? 'Test (no real payment)' : g.mode }}
                                    <span v-if="g.convenience_fee > 0">· convenience {{ inr(g.convenience_fee) }}</span>
                                </div>
                            </div>
                        </div>
                    </label>

                    <Button v-if="!isPaid && !order"
                        @click="startPayment"
                        :loading="form.processing || launchingModal"
                        :disabled="!form.gateway_id || !gateways.length"
                        block>
                        Proceed to Pay {{ inr(total) }}
                    </Button>

                    <div v-if="order && !isPaid" class="border border-saffron rounded p-3 bg-saffron-soft text-sm">
                        <div class="font-semibold text-maroon mb-1">Order created</div>
                        <div class="text-xs">No.: <span class="font-mono">{{ order.order_number }}</span></div>
                        <div class="text-xs">Gateway order: <span class="font-mono">{{ order.gateway_order_id }}</span></div>

                        <div v-if="selectedGateway?.mode === 'stub'" class="mt-3">
                            <Button @click="mockPay" block>🧪 Mock Pay (Stub mode)</Button>
                            <p class="text-[10px] text-ink-mute mt-1 italic">
                                Stub mode simulates a successful Razorpay webhook.
                            </p>
                        </div>
                        <div v-else class="mt-3">
                            <Button @click="resumePayment" :loading="launchingModal" :disabled="!resume_checkout" block>
                                {{ launchingModal ? 'Opening Razorpay…' : 'Re-open Razorpay Checkout' }}
                            </Button>
                            <p class="text-[10px] text-ink-mute mt-1 italic">
                                If the checkout window did not open, click above. A pop-up blocker can prevent the modal.
                            </p>
                        </div>
                    </div>

                    <div v-if="isPaid" class="border-2 border-green-600 rounded p-4 bg-green-50">
                        <div class="font-serif text-base text-green-800">✓ Payment Successful</div>
                        <div class="mt-2 text-xs text-ink-mute">Order: <span class="font-mono">{{ order.order_number }}</span></div>
                        <div class="text-xs text-ink-mute">Paid on {{ formatDateTime(order.paid_at) }}</div>
                    </div>
                </div>
            </section>
        </div>

        <div class="mt-6 flex justify-between">
            <button @click="router.visit(`/student/applications/${application.id}`)"
                class="text-xs text-maroon hover:underline">← Back to application</button>
        </div>
    </PortalLayout>
</template>
