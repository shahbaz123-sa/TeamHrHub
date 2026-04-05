<script setup>
import { defineEmits, defineProps, ref } from "vue";
import { PerfectScrollbar } from "vue3-perfect-scrollbar";

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },
  department: {
    type: Object,
    default: null,
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(["update:isDrawerOpen", "submit"]);

const isFormValid = ref(false);
const refForm = ref();
const isLoading = ref(false);
const accessToken = useCookie("accessToken");
const form = ref({
  id: null,
  name: "",
  description: "",
  status: true,
});

// Reset form when opening/closing drawer
watch(
  () => props.isDrawerOpen,
  (val) => {
      refForm.value?.reset();
      refForm.value?.resetValidation();
      form.value = props.department
        ? { ...props.department }
        : { id: null, name: "", description: "", status: true };
  }
);

const closeForm = () => {
  emit("update:isDrawerOpen", false);
};

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    const formData = {
      name: form.value.name,
      description: form.value.description,
      status: form.value.status ? 1 : 0,
    };
    if (form.value.id) {
      formData.id = form.value.id;
    }
    emit("submit", formData);
  });
};

const handleDrawerModelValueUpdate = (val) => {
  emit("update:isDrawerOpen", val);
};
</script>

<template>
  <!-- Your template remains exactly the same -->
  <VNavigationDrawer
    temporary
    :width="400"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <AppDrawerHeaderSection
      :title="form.id ? 'Update' : 'Add New'"
      @cancel="closeForm"
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
                  placeholder="Department Name"
                />
              </VCol>
              <!-- Description -->
              <VCol cols="12">
                <AppTextarea
                  v-model="form.description"
                  label="Description"
                  placeholder="Department description"
                  rows="3"
                />
              </VCol>

              <!-- Status -->
              <VCol cols="12">
                <VSwitch
                  v-model="form.status"
                  label="Active"
                  color="primary"
                  :true-value="true"
                  :false-value="false"
                />
              </VCol>

              <!-- Submit and Cancel -->
              <VCol cols="12" class="d-flex justify-end gap-4 mt-6">
                <VBtn type="submit" :loading="isLoading || props.loading">
                  Save
                </VBtn>
                <VBtn
                  type="reset"
                  variant="tonal"
                  color="error"
                  @click="closeForm"
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
