<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import { ref, watch } from 'vue';
import { formatDateTime } from '@/utils/date.js';

const props = defineProps({
    users: { type: Object, required: true },
    filter: { type: Object, required: true },
    assignable_roles: { type: Array, required: true },
    current_user_id: { type: Number, required: true },
});

// ----- filters -----
const q = ref(props.filter.q || '');
const role = ref(props.filter.role || '');
const status = ref(props.filter.status || '');

const applyFilters = () => {
    const params = {};
    if (q.value) params.q = q.value;
    if (role.value) params.role = role.value;
    if (status.value) params.status = status.value;
    router.get(route('admin.users.index'), params, { preserveState: true, preserveScroll: true, replace: true });
};

let timer = null;
watch([q, role, status], () => {
    clearTimeout(timer);
    timer = setTimeout(applyFilters, 300);
});

// ----- create form -----
const showCreate = ref(false);
const createForm = useForm({
    name: '',
    email: '',
    mobile: '',
    password: '',
    password_confirmation: '',
    role: 'staff',
    status: 'active',
});

const submitCreate = () => createForm.post(route('admin.users.store'), {
    preserveScroll: true,
    onSuccess: () => {
        createForm.reset();
        createForm.role = 'staff';
        createForm.status = 'active';
        showCreate.value = false;
    },
});

// ----- edit form -----
const editingId = ref(null);
const editForm = useForm({ name: '', email: '', mobile: '', role: 'staff', status: 'active' });

const openEdit = (u) => {
    editingId.value = u.id;
    editForm.name = u.name;
    editForm.email = u.email;
    editForm.mobile = u.mobile;
    editForm.role = u.roles[0]?.name || 'staff';
    editForm.status = u.status;
};

const submitEdit = () => editForm.patch(route('admin.users.update', editingId.value), {
    preserveScroll: true,
    onSuccess: () => { editingId.value = null; },
});

// ----- password reset -----
const resettingId = ref(null);
const pwForm = useForm({ password: '', password_confirmation: '' });

const submitReset = () => pwForm.post(route('admin.users.reset-password', resettingId.value), {
    preserveScroll: true,
    onSuccess: () => {
        pwForm.reset();
        resettingId.value = null;
    },
});

// ----- delete -----
const remove = (u) => {
    if (u.id === props.current_user_id) return;
    if (confirm(`Permanently delete ${u.email}?`)) {
        router.delete(route('admin.users.destroy', u.id), { preserveScroll: true });
    }
};

const roleBadge = (r) => ({
    super_admin: 'bg-red-100 text-red-800',
    admin: 'bg-blue-100 text-blue-800',
    staff: 'bg-purple-100 text-purple-800',
}[r] || 'bg-gray-100');

const statusBadge = (s) => ({
    active: 'bg-green-100 text-green-800',
    inactive: 'bg-gray-100 text-gray-700',
    suspended: 'bg-amber-100 text-amber-800',
}[s] || 'bg-gray-100');

const isSuper = (u) => u.roles?.some(r => r.name === 'super_admin');
</script>

<template>
    <Head title="Admin & Staff Users" />
    <PortalLayout title="Admin &amp; Staff Users" :breadcrumb="['Admin', 'Users']">

        <div class="flex justify-between items-center mb-3">
            <p class="text-sm text-ink-mute">
                Create and manage admin / staff accounts. Students self-register and are not shown here.
            </p>
            <button @click="showCreate = !showCreate"
                class="px-3 py-1.5 bg-saffron text-white rounded text-sm font-semibold hover:bg-saffron/90">
                {{ showCreate ? 'Cancel' : '+ Add User' }}
            </button>
        </div>

        <!-- Create form -->
        <div v-if="showCreate" class="bg-white border-2 border-saffron rounded mb-4 p-4">
            <h3 class="font-serif text-base text-maroon mb-3">New Admin / Staff User</h3>
            <form @submit.prevent="submitCreate" class="grid grid-cols-1 md:grid-cols-12 gap-3">
                <div class="md:col-span-4">
                    <label class="block text-xs font-medium mb-1">Full Name</label>
                    <input v-model="createForm.name" type="text" required maxlength="120"
                        class="w-full px-2 py-1.5 text-sm border border-border rounded" />
                    <p v-if="createForm.errors.name" class="text-xs text-red-600 mt-1">{{ createForm.errors.name }}</p>
                </div>
                <div class="md:col-span-4">
                    <label class="block text-xs font-medium mb-1">Email</label>
                    <input v-model="createForm.email" type="email" required maxlength="160"
                        class="w-full px-2 py-1.5 text-sm border border-border rounded" />
                    <p v-if="createForm.errors.email" class="text-xs text-red-600 mt-1">{{ createForm.errors.email }}</p>
                </div>
                <div class="md:col-span-4">
                    <label class="block text-xs font-medium mb-1">Mobile (10 digits)</label>
                    <input v-model="createForm.mobile" type="tel" required pattern="[6-9][0-9]{9}"
                        class="w-full px-2 py-1.5 text-sm border border-border rounded font-mono" />
                    <p v-if="createForm.errors.mobile" class="text-xs text-red-600 mt-1">{{ createForm.errors.mobile }}</p>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-xs font-medium mb-1">Role</label>
                    <select v-model="createForm.role"
                        class="w-full px-2 py-1.5 text-sm border border-border rounded bg-white">
                        <option v-for="r in assignable_roles" :key="r" :value="r">{{ r }}</option>
                    </select>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-xs font-medium mb-1">Status</label>
                    <select v-model="createForm.status"
                        class="w-full px-2 py-1.5 text-sm border border-border rounded bg-white">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-xs font-medium mb-1">Password (min 8)</label>
                    <input v-model="createForm.password" type="password" required minlength="8"
                        class="w-full px-2 py-1.5 text-sm border border-border rounded" />
                    <p v-if="createForm.errors.password" class="text-xs text-red-600 mt-1">{{ createForm.errors.password }}</p>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-xs font-medium mb-1">Confirm Password</label>
                    <input v-model="createForm.password_confirmation" type="password" required minlength="8"
                        class="w-full px-2 py-1.5 text-sm border border-border rounded" />
                </div>
                <div class="md:col-span-12">
                    <button type="submit" :disabled="createForm.processing"
                        class="px-4 py-1.5 bg-maroon text-white rounded text-sm font-semibold hover:bg-maroon-deep disabled:opacity-60">
                        {{ createForm.processing ? 'Creating…' : 'Create User' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Filters -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-2 mb-3">
            <div>
                <label class="block text-xs font-medium mb-1">Search</label>
                <input v-model="q" type="text" placeholder="name, email, mobile"
                    class="w-full px-2 py-1.5 text-sm border border-border rounded" />
            </div>
            <div>
                <label class="block text-xs font-medium mb-1">Role</label>
                <select v-model="role" class="w-full px-2 py-1.5 text-sm border border-border rounded bg-white">
                    <option value="">All</option>
                    <option value="super_admin">super_admin</option>
                    <option value="admin">admin</option>
                    <option value="staff">staff</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium mb-1">Status</label>
                <select v-model="status" class="w-full px-2 py-1.5 text-sm border border-border rounded bg-white">
                    <option value="">All</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="suspended">Suspended</option>
                </select>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white border border-border rounded overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-cream text-ink-mute text-xs uppercase tracking-wider">
                    <tr>
                        <th class="text-left px-3 py-2">Name</th>
                        <th class="text-left px-3 py-2">Email</th>
                        <th class="text-left px-3 py-2 w-32">Mobile</th>
                        <th class="text-left px-3 py-2 w-28">Role</th>
                        <th class="text-left px-3 py-2 w-24">Status</th>
                        <th class="text-left px-3 py-2 w-40">Last Login</th>
                        <th class="text-right px-3 py-2 w-56">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="u in users.data" :key="u.id">
                        <tr class="border-t border-border hover:bg-cream/50">
                            <td class="px-3 py-2">
                                {{ u.name }}
                                <span v-if="u.id === current_user_id" class="ml-1 text-[10px] font-mono text-saffron-deep">(you)</span>
                            </td>
                            <td class="px-3 py-2 text-xs">{{ u.email }}</td>
                            <td class="px-3 py-2 text-xs font-mono">{{ u.mobile || '—' }}</td>
                            <td class="px-3 py-2">
                                <span v-for="r in u.roles" :key="r.id"
                                    class="px-2 py-0.5 text-xs rounded font-mono" :class="roleBadge(r.name)">{{ r.name }}</span>
                            </td>
                            <td class="px-3 py-2">
                                <span class="px-2 py-0.5 text-xs rounded font-mono uppercase" :class="statusBadge(u.status)">{{ u.status }}</span>
                            </td>
                            <td class="px-3 py-2 text-xs text-ink-mute">{{ u.last_login_at ? formatDateTime(u.last_login_at) : '—' }}</td>
                            <td class="px-3 py-2 text-right text-xs">
                                <template v-if="!isSuper(u) || u.id === current_user_id">
                                    <button @click="openEdit(u)" class="px-2 py-1 text-maroon hover:underline">Edit</button>
                                    <button @click="resettingId = u.id" class="px-2 py-1 text-blue-700 hover:underline">Reset PW</button>
                                    <button v-if="u.id !== current_user_id" @click="remove(u)"
                                        class="px-2 py-1 text-red-600 hover:underline">Delete</button>
                                </template>
                                <span v-else class="text-ink-mute italic">Super admin (DB-only)</span>
                            </td>
                        </tr>

                        <!-- Inline edit row -->
                        <tr v-if="editingId === u.id" class="border-t border-saffron bg-saffron-soft/40">
                            <td colspan="7" class="px-4 py-3">
                                <form @submit.prevent="submitEdit" class="grid grid-cols-1 md:grid-cols-12 gap-3">
                                    <div class="md:col-span-3">
                                        <label class="block text-xs font-medium mb-1">Name</label>
                                        <input v-model="editForm.name" type="text" required maxlength="120"
                                            class="w-full px-2 py-1.5 text-sm border border-border rounded" />
                                    </div>
                                    <div class="md:col-span-3">
                                        <label class="block text-xs font-medium mb-1">Email</label>
                                        <input v-model="editForm.email" type="email" required maxlength="160"
                                            class="w-full px-2 py-1.5 text-sm border border-border rounded" />
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-medium mb-1">Mobile</label>
                                        <input v-model="editForm.mobile" type="tel" required pattern="[6-9][0-9]{9}"
                                            class="w-full px-2 py-1.5 text-sm border border-border rounded font-mono" />
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-medium mb-1">Role</label>
                                        <select v-model="editForm.role"
                                            class="w-full px-2 py-1.5 text-sm border border-border rounded bg-white">
                                            <option v-for="r in assignable_roles" :key="r" :value="r">{{ r }}</option>
                                        </select>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-medium mb-1">Status</label>
                                        <select v-model="editForm.status"
                                            class="w-full px-2 py-1.5 text-sm border border-border rounded bg-white">
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                            <option value="suspended">Suspended</option>
                                        </select>
                                    </div>
                                    <div class="md:col-span-12 flex gap-2">
                                        <button type="submit" :disabled="editForm.processing"
                                            class="px-4 py-1.5 bg-maroon text-white rounded text-sm font-semibold hover:bg-maroon-deep disabled:opacity-60">
                                            {{ editForm.processing ? 'Saving…' : 'Update' }}
                                        </button>
                                        <button type="button" @click="editingId = null" class="px-4 py-1.5 border border-border rounded text-sm hover:bg-cream">Cancel</button>
                                    </div>
                                </form>
                                <p v-for="(err, k) in editForm.errors" :key="k" class="text-xs text-red-600 mt-1">{{ err }}</p>
                            </td>
                        </tr>

                        <!-- Inline password reset row -->
                        <tr v-if="resettingId === u.id" class="border-t border-blue-300 bg-blue-50">
                            <td colspan="7" class="px-4 py-3">
                                <form @submit.prevent="submitReset" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                                    <div class="md:col-span-4">
                                        <label class="block text-xs font-medium mb-1">New password (min 8)</label>
                                        <input v-model="pwForm.password" type="password" required minlength="8"
                                            class="w-full px-2 py-1.5 text-sm border border-border rounded" />
                                    </div>
                                    <div class="md:col-span-4">
                                        <label class="block text-xs font-medium mb-1">Confirm</label>
                                        <input v-model="pwForm.password_confirmation" type="password" required minlength="8"
                                            class="w-full px-2 py-1.5 text-sm border border-border rounded" />
                                    </div>
                                    <div class="md:col-span-4 flex gap-2">
                                        <button type="submit" :disabled="pwForm.processing"
                                            class="px-4 py-1.5 bg-blue-700 text-white rounded text-sm font-semibold hover:bg-blue-800 disabled:opacity-60">
                                            {{ pwForm.processing ? 'Resetting…' : 'Reset Password' }}
                                        </button>
                                        <button type="button" @click="resettingId = null" class="px-4 py-1.5 border border-border rounded text-sm hover:bg-cream">Cancel</button>
                                    </div>
                                </form>
                                <p v-for="(err, k) in pwForm.errors" :key="k" class="text-xs text-red-600 mt-1">{{ err }}</p>
                            </td>
                        </tr>
                    </template>

                    <tr v-if="!users.data.length">
                        <td colspan="7" class="px-3 py-6 text-center text-ink-mute text-sm">No users match.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="users.last_page > 1" class="mt-4 flex items-center justify-center gap-1 text-sm">
            <template v-for="(l, i) in users.links" :key="i">
                <Link v-if="l.url" :href="l.url" v-html="l.label"
                    class="px-3 py-1 rounded border"
                    :class="l.active ? 'bg-maroon text-white border-maroon' : 'border-border hover:bg-cream'" />
                <span v-else v-html="l.label"
                    class="px-3 py-1 rounded border border-border text-ink-mute opacity-50 cursor-not-allowed" />
            </template>
        </div>
    </PortalLayout>
</template>
