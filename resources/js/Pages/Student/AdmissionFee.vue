<script setup>
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import Button from '@/Components/Ui/Button.vue';
import { ref, computed, onMounted, watch } from 'vue';
import { formatDateTime } from '@/utils/date.js';
import { useSite } from '@/Composables/useSite.js';

const props = defineProps({
    allocation: { type: Object, required: true },
    fee: { type: Object, required: true },
    gst_percent: { type: Number, default: 18 },
    gateways: { type: Array, required: true },
    order: { type: Object, default: null },
    resume_checkout: { type: Object, default: null },
    prefill: { type: Object, default: () => ({}) },
});

const { portalName } = useSite();

const page = usePage();
const checkoutPayload = computed(() => page.props?.flash?.checkout || null);

const inr = (n) => '₹ ' + Number(n || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');

const form = useForm({ gateway_id: props.gateways[0]?.id || '' });

const selectedGateway = computed(() => props.gateways.find(g => g.id === form.gateway_id) || null);
const convenience = computed(() => selectedGateway.value?.convenience_fee || 0);
const gst = computed(() => Math.round(convenience.value * props.gst_percent) / 100);
const total = computed(() => Number(props.fee.amount) + Number(convenience.value) + Number(gst.value));

const startPayment = () => form.post(route('student.allotment.admission-fee.init', props.allocation.id), {
    preserveScroll: true,
});

const isPaid = computed(() => props.allocation?.status === 'admitted');

// ----- Razorpay checkout (reused from application fee flow) -----
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
        alert('Razorpay key_id is not configured. Open Admin → Gateways and add keys.');
        return;
    }
    launchingModal.value = true;
    try {
        await loadRazorpayScript();
    } catch (e) {
        launchingModal.value = false;
        alert('Failed to load Razorpay Checkout JS.');
        return;
    }

    const options = {
        key: payload.key,
        order_id: payload.gateway_order_id,
        amount: payload.amount_paise,
        currency: payload.currency,
        name: payload.name || portalName.value,
        description: payload.description || 'Admission Fee',
        prefill: { name: props.prefill.name, email: props.prefill.email, contact: props.prefill.contact },
        notes: { internal_order_id: String(payload.internal_order_id) },
        theme: { color: '#7a1e1e' },
        callback_url: payload.callback_url,
        redirect: true,
        modal: { ondismiss: () => { launchingModal.value = false; } },
    };
    const rzp = new window.Razorpay(options);
    rzp.on('payment.failed', (resp) => {
        launchingModal.value = false;
        alert(`Payment failed: ${resp.error?.description || 'Unknown error'}`);
    });
    rzp.open();
};

watch(checkoutPayload, (v) => { if (v) openRazorpay(v); });
onMounted(() => {
    if (checkoutPayload.value) openRazorpay(checkoutPayload.value);
    else if (props.resume_checkout && !isPaid.value) openRazorpay(props.resume_checkout);
});

const resumePayment = () => { if (props.resume_checkout) openRazorpay(props.resume_checkout); };
</script>

<template>
    <Head title="Admission Fee Payment" />
    <PortalLayout title="Admission Fee Payment"
        :breadcrumb="['Student', 'Allotment', 'Admission Fee']">

        <div class="mb-4 flex justify-between items-center">
            <div class="text-sm text-ink-mute">
                Allotment #{{ allocation.id }} · Programme <span class="font-mono">{{ allocation.application?.program?.code }}</span>
            </div>
            <span v-if="isPaid" class="px-2 py-0.5 text-xs rounded bg-green-100 text-green-800 font-semibold">✓ Admitted</span>
            <span v-else class="px-2 py-0.5 text-xs rounded bg-amber-100 text-amber-800">Awaiting payment</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <section class="md:col-span-3 bg-white border border-border rounded">
                <header class="px-4 py-2 border-b border-border bg-cream">
                    <h2 class="font-serif text-base text-maroon">Admission Fee Particulars</h2>
                </header>
                <table class="w-full text-sm">
                    <tbody>
                        <tr class="border-t border-border">
                            <td class="px-4 py-2">Admission Fee</td>
                            <td class="px-4 py-2 text-right font-mono">{{ inr(fee.amount) }}</td>
                        </tr>
                        <tr v-if="convenience > 0" class="border-t border-border">
                            <td class="px-4 py-2">Convenience Charge</td>
                            <td class="px-4 py-2 text-right font-mono">{{ inr(convenience) }}</td>
                        </tr>
                        <tr v-if="gst > 0" class="border-t border-border">
                            <td class="px-4 py-2">GST @ {{ gst_percent }}%</td>
                            <td class="px-4 py-2 text-right font-mono">{{ inr(gst) }}</td>
                        </tr>
                        <tr class="border-t-2 border-maroon bg-saffron-soft font-semibold">
                            <td class="px-4 py-2 text-right">Total Payable</td>
                            <td class="px-4 py-2 text-right font-mono text-maroon">{{ inr(total) }}</td>
                        </tr>
                    </tbody>
                </table>
                <p v-if="fee.source === 'unset'" class="px-4 py-2 text-xs text-red-600 italic">
                    Admission fee is not configured on this programme. Contact admissions.
                </p>
            </section>

            <section class="md:col-span-2 bg-white border border-border rounded">
                <header class="px-4 py-2 border-b border-border bg-cream">
                    <h2 class="font-serif text-base text-maroon">Payment Method</h2>
                </header>
                <div class="p-4 space-y-3">
                    <div v-if="!gateways.length" class="text-sm text-red-600">
                        No active gateways. Contact admissions.
                    </div>
                    <label v-for="g in gateways" :key="g.id"
                        class="block px-3 py-2 border rounded cursor-pointer"
                        :class="form.gateway_id === g.id ? 'border-maroon bg-saffron-soft' : 'border-border hover:bg-cream'">
                        <div class="flex items-center gap-2">
                            <input type="radio" :value="g.id" v-model="form.gateway_id" />
                            <div class="flex-1">
                                <div class="text-sm font-medium">{{ g.display_name }}</div>
                                <div class="text-xs text-ink-mute">{{ g.mode }}</div>
                            </div>
                        </div>
                    </label>

                    <Button v-if="!isPaid && !order"
                        @click="startPayment"
                        :loading="form.processing || launchingModal"
                        :disabled="!form.gateway_id || !gateways.length || fee.amount <= 0"
                        block>
                        Proceed to Pay {{ inr(total) }}
                    </Button>

                    <div v-if="order && !isPaid" class="border border-saffron rounded p-3 bg-saffron-soft text-sm">
                        <div class="font-semibold text-maroon mb-1">Order created</div>
                        <div class="text-xs">No.: <span class="font-mono">{{ order.order_number }}</span></div>
                        <div class="mt-3">
                            <Button @click="resumePayment" :loading="launchingModal" :disabled="!resume_checkout" block>
                                {{ launchingModal ? 'Opening Razorpay…' : 'Re-open Razorpay Checkout' }}
                            </Button>
                        </div>
                    </div>

                    <div v-if="isPaid" class="border-2 border-green-600 rounded p-4 bg-green-50">
                        <div class="font-serif text-base text-green-800">✓ Admission Confirmed</div>
                        <div class="mt-2 text-xs text-ink-mute">Allocation: <span class="font-mono">#{{ allocation.id }}</span></div>
                        <div class="text-xs text-ink-mute">Admitted on {{ formatDateTime(allocation.admitted_at) }}</div>
                    </div>
                </div>
            </section>
        </div>

        <div class="mt-6">
            <button @click="router.visit(`/student/allotment/${allocation.application_id}`)"
                class="text-xs text-maroon hover:underline">← Back to allotment letter</button>
        </div>
    </PortalLayout>
</template>
