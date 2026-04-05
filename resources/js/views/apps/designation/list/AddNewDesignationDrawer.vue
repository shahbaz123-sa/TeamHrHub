<script setup>
import { defineEmits, defineProps, nextTick, ref } from "vue";
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
  designation: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(["update:isDrawerOpen", "submit"]);

const isFormValid = ref(false);
const refForm = ref();

const form = ref({
  id: null,
  title: "",
  description: "",
});

const resetForm = () => {
  form.value = {
    id: null,
    title: "",
    description: "",
  };
};

watch(
  () => props.designation,
  (val) => {
    if (val) {
      form.value = {
        id: val.id,
        title: val.title || "",
        description: val.description || "",
      };
    } else {
      resetForm();
    }
  },
  { immediate: true }
);

const closeNavigationDrawer = () => {
  emit("update:isDrawerOpen", false);
  nextTick(() => {
    refForm.value?.reset();
    refForm.value?.resetValidation();
    resetForm();
  });
};

const onSubmit = async () => {
  const { valid } = await refForm.value.validate();
  if (valid) {
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
      :title="form.id ? 'Edit Designation' : 'Add New Designation'"
      @cancel="closeNavigationDrawer"
    />

    <VDivider />

    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat>
        <VCardText>
          <VForm ref="refForm" v-model="isFormValid" @submit.prevent="onSubmit">
            <VRow>
              <!-- Title (changed from Name) -->
              <VCol cols="12">
                <AppTextField
                  v-model="form.title"
                  :rules="[requiredValidator]"
                  label="Title"
                  placeholder="Designation Title"
                />
              </VCol>

              <!-- Description -->
              <VCol cols="12">
                <AppTextarea
                  v-model="form.description"
                  label="Description"
                  placeholder="Designation description"
                  rows="3"
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
