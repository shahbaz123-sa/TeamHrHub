<script setup>
import { defineEmits, defineProps, nextTick, ref, watch } from "vue";
import { PerfectScrollbar } from "vue3-perfect-scrollbar";

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  documentType: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(["update:isDrawerOpen", "submit"]);

const isFormValid = ref(false);
const refForm = ref();
const form = ref({
  id: null,
  name: "",
  description: "",
  is_default: false,
  order: null,
});

// Reset form
const resetForm = () => {
  form.value = {
    id: null,
    name: "",
    description: "",
    is_default: false,
    order: null,
  };
};

// Watch for documentType prop changes
watch(
  () => props.documentType,
  (val) => {
    if (val) {
      form.value = {
        id: val.id,
        name: val.name || "",
        description: val.description || "",
        is_default: val.is_default || false,
        order: val.order || null,
      };
    } else {
      resetForm();
    }
  },
  { immediate: true }
);

watch(
  () => form.value.is_default,
  (isDefault) => {
    if (!isDefault) {
      form.value.order = 0;
    } else {
      if (!form.value.order || Number(form.value.order) <= 0) {
        form.value.order = null;
      }
    }
  },
  { immediate: true }
);

const orderValidator = (value) => {
  const isDefault = form.value.is_default;
  const num = value === null || value === undefined ? null : Number(value);

  if (!isDefault) {
    return num === 0 || "Order must be 0 when not default";
  }

  if (num === null || Number.isNaN(num)) return "Order is required";
  return num > 0 || "Order must be greater than 0 when default";
};
// Close drawer handler
const closeNavigationDrawer = () => {
  emit("update:isDrawerOpen", false);
  nextTick(() => {
    refForm.value?.reset();
    refForm.value?.resetValidation();
    resetForm();
  });
};

// Form submission
const onSubmit = async () => {
  const { valid } = await refForm.value.validate();
  if (valid) {
    if (!form.value.is_default) {
      form.value.order = 0;
    }
    emit("submit", { ...form.value });
    closeNavigationDrawer();
  }
};

const handleDrawerModelValueUpdate = (val) => {
  emit("update:isDrawerOpen", val);
};
</script>

<template>
  <VNavigationDrawer
    temporary
    :width="400"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <!-- Title -->
    <AppDrawerHeaderSection
      :title="form.id ? 'Edit Document Type' : 'Add New Document Type'"
      @cancel="closeNavigationDrawer"
    />
    <VDivider />
    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat>
        <VCardText>
          <VForm ref="refForm" v-model="isFormValid" @submit.prevent="onSubmit">
            <VRow>
              <!-- Name -->
              <VCol cols="12">
                <AppTextField
                  v-model="form.name"
                  :rules="[requiredValidator]"
                  label="Name"
                  placeholder="Document Type Name"
                />
              </VCol>
              <!-- Description -->
              <VCol cols="12">
                <AppTextarea
                  v-model="form.description"
                  label="Description"
                  placeholder="Document Type description"
                  rows="3"
                />
              </VCol>
              <VCol cols="12" md="6">
                <VCheckbox v-model="form.is_default" :label="'Is Default'" hide-details density="compact" />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField
                  v-model.number="form.order"
                  type="number"
                  :rules="[orderValidator]"
                  :disabled="!form.is_default"
                  label="Display Order"
                  placeholder="0"
                  min="0"
                />
              </VCol>
              <!-- Submit and Cancel -->
              <VCol cols="12" class="d-flex justify-end gap-4 mt-6">
                <VBtn type="submit" :loading="props.loading">
                  {{ form.id ? 'Update' : 'Save' }}
                </VBtn>
                <VBtn
                  type="reset"
                  variant="tonal"
                  color="error"
                  @click="closeNavigationDrawer"
                >
                  Cancel
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </PerfectScrollbar>
  </VNavigationDrawer>
</template>
