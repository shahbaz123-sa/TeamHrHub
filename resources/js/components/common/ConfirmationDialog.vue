<template>
  <VDialog v-model="localOpen" max-width="500">
    <VCard :title="title">
      <VCardText>
        {{ description }}
      </VCardText>
      <VCardActions class="justify-end">
        <VBtn variant="plain" @click="localOpen = false">{{ cancelText }}</VBtn>
        <VBtn color="error" variant="flat" :loading="loading" @click="onConfirm">{{ confirmText }}</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
  
</template>

<script setup>
const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: 'Are you sure' },
  description: { type: String, default: 'This action can not be undone. Do you want to continue?' },
  confirmText: { type: String, default: 'Yes' },
  cancelText: { type: String, default: 'No' },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'confirm'])

const localOpen = computed({
  get: () => props.modelValue,
  set: val => emit('update:modelValue', val),
})

const onConfirm = () => {
  emit('confirm')
}
</script>



