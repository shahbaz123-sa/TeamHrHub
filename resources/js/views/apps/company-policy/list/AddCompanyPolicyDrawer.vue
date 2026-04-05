<script setup>
import { defineEmits, defineProps, nextTick, ref, watch } from "vue";
import { PerfectScrollbar } from "vue3-perfect-scrollbar";

const props = defineProps({
  isDrawerOpen: { type: Boolean, required: true },
  loading: { type: Boolean, default: false },
  branches: { type: Array, default: () => [] },
  editingPolicy: { type: Object, default: null },
});

const emit = defineEmits(["update:isDrawerOpen", "save"]);
const accessToken = useCookie("accessToken");

const isFormValid = ref(false);
const refForm = ref();
const isLoading = ref(false);

const form = ref({
  id: null,
  branch_id: null,
  display_order: 0,
  title: "",
  description: "",
  attachment: null,
});

const closeNavigationDrawer = () => {
  emit("update:isDrawerOpen", false);
  nextTick(() => {
    refForm.value?.reset();
    refForm.value?.resetValidation();
    resetForm();
  });
};

const resetForm = () => {
  form.value = {
    id: null,
    branch_id: null,
    display_order: 0,
    title: "",
    description: "",
    attachment: null,
  };
  refForm.value?.reset();
  refForm.value?.resetValidation();
};

watch(
  () => props.editingPolicy,
  (val) => {
    resetForm();
    if (val) {
      form.value = {
        id: val.id,
        branch_id: val.branch_id,
        display_order: val.display_order,
        title: val.title,
        description: val.description,
        attachment: null,
      };
    }
  },
  { immediate: true }
);

const onSubmit = async () => {
  const { valid } = await refForm.value.validate();
  if (!valid) return;

  isLoading.value = true;
  try {
    const fd = new FormData();
    if (form.value.branch_id) fd.append("branch_id", form.value.branch_id);
    fd.append("display_order", String(form.value.display_order ?? 0));
    fd.append("title", form.value.title ?? "");
    if (form.value.description) fd.append("description", form.value.description);
    if (form.value.attachment instanceof File) fd.append("attachment", form.value.attachment);

    emit("save", fd, form.value.id || null);
  } finally {
    isLoading.value = false;
  }
};

const handleDrawerModelValueUpdate = (val) => {
  emit("update:isDrawerOpen", val);
};
</script>

<template>
  <VNavigationDrawer
    temporary
    :width="480"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <AppDrawerHeaderSection
      :title="form.id ? 'Edit Policy' : 'Add New Policy'"
      @cancel="closeNavigationDrawer"
    />

    <VDivider />

    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat>
        <VCardText>
          <VForm ref="refForm" v-model="isFormValid" @submit.prevent="onSubmit">
            <VRow>
              <VCol cols="12">
                <AppSelect
                  v-model="form.branch_id"
                  :items="props.branches"
                  item-title="name"
                  item-value="id"
                  label="Branch"
                  placeholder="Select branch"
                />
              </VCol>

              <VCol cols="12" md="6">
                <AppTextField
                  v-model.number="form.display_order"
                  type="number"
                  :rules="[requiredValidator]"
                  label="Display Order"
                  placeholder="0"
                  min="0"
                />
              </VCol>

              <VCol cols="12" md="6">
                <AppTextField
                  v-model="form.title"
                  :rules="[requiredValidator]"
                  label="Policy Title"
                  placeholder="Enter title"
                />
              </VCol>

              <VCol cols="12">
                <AppTextarea
                  v-model="form.description"
                  label="Policy Description"
                  placeholder="Enter description"
                  rows="4"
                />
              </VCol>

              <VCol cols="12">
                <VFileInput
                  v-model="form.attachment"
                  label="Policy Attachment"
                  accept=".pdf,.doc,.docx,.png,.jpg,.jpeg"
                  prepend-icon="tabler-paperclip"
                  show-size
                  clearable
                />
              </VCol>

              <VCol cols="12" class="d-flex justify-end gap-4 mt-6">
                <VBtn type="submit" :loading="isLoading || props.loading">
                  {{ form.id ? 'Update Policy' : 'Create Policy' }}
                </VBtn>
                <VBtn type="reset" variant="tonal" color="error" @click="closeNavigationDrawer">
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


