<script setup>
import { isNullOrUndefined } from "@/@core/utils/helpers";
import { isEmpty } from "@/utils/helpers/str";
import { defineEmits, defineProps, ref, watch } from "vue";
import { PerfectScrollbar } from "vue3-perfect-scrollbar";

const props = defineProps({
  isDrawerOpen: { type: Boolean, required: true },
  loading: { type: Boolean, default: false },
  editingFinancialReport: { type: Object, default: null },
});

const emit = defineEmits(["update:isDrawerOpen", "save"]);

const isFormValid = ref(false);
const refForm = ref();
const isLoading = ref(false);

const form = ref({
  id: null,
  title: '',
  press_release: null,
  financial_report: null,
  presentation: null,
  transcript: null,
  video: null,
  report_date: null,
  is_active: true,
})

const closeNavigationDrawer = () => {
  emit("update:isDrawerOpen", false);
  emit("update:editingFinancialReport", null);
  resetForm();
};

const resetForm = () => {
  form.value = {
    id: null,
    title: '',
    press_release: null,
    financial_report: null,
    presentation: null,
    transcript: null,
    video: null,
    report_date: null,
    is_active: true,
  }
  refForm.value?.reset();
  refForm.value?.resetValidation();
};

watch(
  () => props.isDrawerOpen,
  (val) => {
    resetForm();
    if (val && props.editingFinancialReport) {
      form.value = {...props.editingFinancialReport};
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
    Object.entries(form.value).forEach(([key, value]) => {
      if(key === 'id' && isEmpty(value)) return
      else if((key === 'press_release' || key === 'financial_report' || key === 'presentation' || key === 'transcript' || key === 'video') && !(value instanceof File)) return
      else if(key === 'is_active') {
        fd.append(key, value)
        return
      }
      fd.append(key, isEmpty(value) ? '' : value)
    })
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
      :title="form.id ? 'Edit Financial Report' : 'Add New Financial Report'"
      @cancel="closeNavigationDrawer"
    />

    <VDivider />

    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat>
        <VCardText>
          <VForm ref="refForm" v-model="isFormValid" @submit.prevent="onSubmit">
            <VRow>
              <VCol cols="12" md="12">
                <AppTextField
                  v-model="form.title"
                  :rules="[requiredValidator]"
                  label="Title"
                  placeholder="Enter title"
                />
              </VCol>

              <VCol cols="12">
                <VFileInput
                  v-model="form.press_release"
                  label="Press Release"
                  prepend-icon="tabler-paperclip"
                  clearable
                  density="comfortable"
                  variant="outlined"
                />
              </VCol>

              <VCol cols="12">
                <VFileInput
                  v-model="form.financial_report"
                  label="Financial Report"
                  prepend-icon="tabler-paperclip"
                  clearable
                  density="comfortable"
                  variant="outlined"
                />
              </VCol>

              <VCol cols="12">
                <VFileInput
                  v-model="form.presentation"
                  label="Presentation"
                  prepend-icon="tabler-paperclip"
                  clearable
                  density="comfortable"
                  variant="outlined"
                />
              </VCol>

              <VCol cols="12">
                <VFileInput
                  v-model="form.transcript"
                  label="Transcript"
                  prepend-icon="tabler-paperclip"
                  clearable
                  density="comfortable"
                  variant="outlined"
                />
              </VCol>

              <VCol cols="12">
                <VFileInput
                  v-model="form.video"
                  label="Video"
                  prepend-icon="tabler-video"
                  clearable
                  density="comfortable"
                  variant="outlined"
                />
              </VCol>

              <VCol cols="12">
                <AppDateTimePicker
                  v-model="form.report_date"
                  label="Report Date"
                  clearable
                  clear-icon="tabler-x"
                />
              </VCol>

              <VCol cols="12">
                <VSwitch
                  v-model="form.is_active"
                  label="Active"
                  color="primary"
                  :true-value="true"
                  :false-value="false"
                />
              </VCol>

              <VCol cols="12" class="d-flex justify-end gap-4 mt-6">
                <VBtn type="submit" :loading="isLoading || props.loading">
                  {{ form.id ? 'Update Report' : 'Create Report' }}
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
