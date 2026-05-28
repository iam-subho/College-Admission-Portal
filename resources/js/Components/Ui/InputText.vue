<script setup>
defineProps({
    modelValue: { type: [String, Number], default: '' },
    label: { type: String, default: '' },
    error: { type: String, default: '' },
    type: { type: String, default: 'text' },
    required: { type: Boolean, default: false },
    autocomplete: { type: String, default: 'off' },
    placeholder: { type: String, default: '' },
    maxlength: { type: [String, Number], default: null },
    prefix: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
});
defineEmits(['update:modelValue']);
</script>

<template>
    <div>
        <label v-if="label" class="block text-xs font-medium text-ink mb-1">
            {{ label }} <span v-if="required" class="text-maroon">*</span>
        </label>
        <div class="flex" :class="{ 'opacity-60': disabled }">
            <span v-if="prefix" class="inline-flex items-center px-3 text-sm bg-cream border border-r-0 border-border rounded-l text-ink-mute">
                {{ prefix }}
            </span>
            <input
                :type="type"
                :value="modelValue"
                :autocomplete="autocomplete"
                :placeholder="placeholder"
                :maxlength="maxlength"
                :disabled="disabled"
                @input="$emit('update:modelValue', $event.target.value)"
                class="flex-1 w-full px-3 py-2 text-sm border border-border bg-white text-ink focus:outline-none focus:border-saffron focus:ring-1 focus:ring-saffron"
                :class="[
                    prefix ? 'rounded-r' : 'rounded',
                    error ? 'border-red-500' : '',
                ]"
            />
        </div>
        <p v-if="error" class="text-xs text-red-600 mt-1">{{ error }}</p>
    </div>
</template>
