<script setup>
import { isEmpty } from "@/utils/helpers/str";
import { defineEmits, defineProps, onMounted, ref, watch } from "vue";
import { PerfectScrollbar } from "vue3-perfect-scrollbar";

const props = defineProps({
  isDrawerOpen: { type: Boolean, required: true },
  loading: { type: Boolean, default: false },
  editingNotice: { type: Object, default: null },
});

const emit = defineEmits(["update:isDrawerOpen", "save"]);

const isFormValid = ref(false);
const refForm = ref();
const isLoading = ref(false);

const form = ref({
  id: null,
  title: '',
  year: new Date().getFullYear(),
  type_id: null,
  pdf_attachment: null,
  excel_attachment: null,
  is_active: true,
})

const types = ref([])
const accessToken = useCookie('accessToken').value

const fetchTypes = async () => {
  try {
    const { data } = await $api('/notice/types', {
      query: {
        per_page: -1,
        status: 1
      },
      method: 'GET',
      headers: { Authorization: `Bearer ${accessToken}` }
    })
    types.value = data.map(t => ({ value: t.id, title: t.title }))
  } catch (e) {
    // ignore errors
  }
}

const closeNavigationDrawer = () => {
  emit("update:isDrawerOpen", false);
  emit("update:editingNotice", null);
  resetForm();
};

const resetForm = () => {
  form.value = {
    id: null,
    title: '',
    year: new Date().getFullYear(),
    pdf_attachment: null,
    excel_attachment: null,
    is_active: true,
  }
  refForm.value?.reset();
  refForm.value?.resetValidation();
};

watch(
  () => props.isDrawerOpen,
  (val) => {
    resetForm();
    if (val && props.editingNotice) {
      form.value = {...props.editingNotice};
    }
  },
  { immediate: true }
);

onMounted(fetchTypes)

const onSubmit = async () => {
  const { valid } = await refForm.value.validate();
  if (!valid) return;

  isLoading.value = true;
  try {
    const fd = new FormData();
    Object.entries(form.value).forEach(([key, value]) => {
      if(key === 'id' && isEmpty(value)) return
      else if((key === 'pdf_attachment' || key === 'excel_attachment') && !(value instanceof File)) return
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
      :title="form.id ? 'Edit Notice' : 'Add New Notice'"
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

              <VCol cols="12" md="12">
                <AppTextField
                  v-model="form.year"
                  type="number"
                  :rules="[requiredValidator]"
                  label="Year"
                  placeholder="Enter year"
                />
              </VCol>

              <VCol cols="12" md="12">
                <AppSelect
                  v-model="form.type_id"
                  :items="types"
                  label="Type"
                  clearable
                />
              </VCol>

              <VCol cols="12">
                <VFileInput
                  v-model="form.pdf_attachment"
                  label="PDF Attachment"
                  prepend-icon="tabler-paperclip"
                  clearable
                  density="comfortable"
                  variant="outlined"
                />
              </VCol>

              <VCol cols="12">
                <VFileInput
                  v-model="form.excel_attachment"
                  label="Excel Attachment"
                  prepend-icon="tabler-file-spreadsheet"
                  clearable
                  density="comfortable"
                  variant="outlined"
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
                  {{ form.id ? 'Update' : 'Create' }}
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
