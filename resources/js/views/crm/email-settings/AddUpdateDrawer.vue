<script setup>
import { slugRule } from "@/utils/form/validation";
import { ref, watch } from 'vue';

const emit = defineEmits(['save'])
const props = defineProps({
  modelValue: Boolean,
  item: Object,
})

const refForm = ref();
const form = ref({
  notify_on: '',
  slug: '',
  recipients: '',
  is_active: true,
})

const loading = ref(false)

const resetForm = () => {
  form.value = {
    notify_on: '',
    slug: '',
    recipients: '',
    is_active: true,
  }
  refForm.value?.reset();
  refForm.value?.resetValidation();
}

const saveEmailSetting = async () => {
  loading.value = true
  const formData = new FormData();
  formData.append('notify_on', form.value.notify_on);
  formData.append('slug', form.value.slug);
  formData.append('recipients', form.value.recipients);
  formData.append('is_active', form.value.is_active ? 1 : 0);

  await emit('save', formData, props.item?.id);
  loading.value = false
}

const closeNavigationDrawer = () => {
  emit("update:modelValue", false);
  resetForm();
};

watch(
  () => props.item,
  (newVal) => {
    if (newVal) {
      form.value = {
        notify_on: newVal.notify_on || '',
        slug: newVal.slug || '',
        recipients: newVal.recipients || '',
        is_active: newVal.is_active,
      }
    } else {
      resetForm()
    }
  },
  { immediate: true }
)
</script>

<template>
  <VNavigationDrawer
    :model-value="modelValue"
    temporary
    location="end"
    width="400"
    @update:model-value="$emit('update:modelValue', $event)"
  >

    <AppDrawerHeaderSection
      :title="form.id ? 'Edit Setting' : 'Add New Setting'"
      @cancel="closeNavigationDrawer"
    />

    <VDivider />

    <div class="pa-6">
      <VForm ref="refForm" @submit.prevent="saveEmailSetting">
        <VRow>
          <VCol cols="12">
            <AppTextField
              v-model="form.slug"
              label="Slug"
              placeholder="Enter slug"
              :rules="[requiredValidator, slugRule]"
              :readonly="props.item?.id"
              required
            />
          </VCol>
        </VRow>

        <VRow>
          <VCol cols="12">
            <AppTextField
              v-model="form.notify_on"
              label="Notify On"
              placeholder="e.g., Order Created, Payment Received"
              :rules="[v => !!v || 'Notify On is required']"
              required
            />
          </VCol>
        </VRow>

        <VRow>
          <VCol cols="12">
            <VTextarea
              v-model="form.recipients"
              label="Recipients"
              placeholder="email1@example.com, email2@example.com"
              variant="outlined"
              :rules="[v => !!v || 'Recipients is required']"
              required
              hint="Comma-separated email addresses"
            />
          </VCol>
        </VRow>

        <VRow>
          <VCol cols="12">
            <VSwitch
              v-model="form.is_active"
              label="Active"
              color="primary"
              :data-value="form.is_active"
            />
          </VCol>
        </VRow>

        <VRow class="mt-4">
          <VCol cols="12">
            <VBtn
              type="submit"
              color="primary"
              :loading="loading"
              block
            >
              {{ item ? 'Update' : 'Create' }}
            </VBtn>
          </VCol>
        </VRow>
      </VForm>
    </div>
  </VNavigationDrawer>
</template>
