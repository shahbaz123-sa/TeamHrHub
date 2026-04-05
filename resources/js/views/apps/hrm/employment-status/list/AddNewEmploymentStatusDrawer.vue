<script setup>
import { PerfectScrollbar } from "vue3-perfect-scrollbar";

const props = defineProps({
  isOpen: Boolean,
  employmentStatus: Object,
  loading: Boolean,
});

const emit = defineEmits(["update:isOpen", "submit"]);

const isFormValid = ref(false);
const refForm = ref();

const form = ref({
  id: null,
  name: "",
  description: "",
});

watch(
  () => props.isOpen,
  (val) => {
    if (val) {
      form.value = props.employmentStatus
        ? { ...props.employmentStatus }
        : { id: null, name: "", description: "" };
    } else {
      refForm.value?.reset();
    }
  }
);

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      const formData = {
        name: form.value.name,
        description: form.value.description,
      };
      if (form.value.id) {
        formData.id = form.value.id;
      }
      emit("submit", formData);
    }
  });
};

const closeForm = () => {
  emit("update:isOpen", false);
};
</script>

<template>
  <VNavigationDrawer
    temporary
    :width="400"
    location="end"
    class="scrollable-content"
    :model-value="props.isOpen"
    @update:model-value="$emit('update:isOpen', $event)"
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
              <VCol cols="12">
                <AppTextField
                  v-model="form.name"
                  :rules="[requiredValidator]"
                  label="Name"
                  placeholder="Enter name"
                />
              </VCol>
              <VCol cols="12">
                <AppTextarea
                  v-model="form.description"
                  label="Description"
                  placeholder="Enter description"
                  rows="3"
                />
              </VCol>
              <VCol cols="12" class="d-flex justify-end gap-4 mt-6">
                <VBtn type="submit" :loading="props.loading"> Save </VBtn>
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
