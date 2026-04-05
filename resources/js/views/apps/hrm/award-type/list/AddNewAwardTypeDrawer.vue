<script setup>
import { defineEmits, defineProps, nextTick, ref, watch } from "vue";
import { PerfectScrollbar } from "vue3-perfect-scrollbar";

const props = defineProps({
  isOpen: Boolean,
  awardType: Object,
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

const resetForm = () => {
  form.value = {
    id: null,
    name: "",
    description: "",
  };
};

watch(
  () => props.awardType,
  (val) => {
    if (val) {
      form.value = {
        id: val.id,
        name: val.name || "",
        description: val.description || "",
      };
    } else {
      resetForm();
    }
  },
  { immediate: true }
);

const closeForm = () => {
  emit("update:isOpen", false);
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
    closeForm();
  }
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
      :title="form.id ? 'Edit Award Type' : 'Add New Award Type'"
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
                  placeholder="Award Type Name"
                />
              </VCol>
              <VCol cols="12">
                <AppTextarea
                  v-model="form.description"
                  label="Description"
                  placeholder="Award Type description"
                  rows="3"
                />
              </VCol>
              <VCol cols="12" class="d-flex justify-end gap-4 mt-6">
                <VBtn type="submit" :loading="props.loading">
                  {{ form.id ? 'Update' : 'Save' }}
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
