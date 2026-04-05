<script setup>
import { isNullOrUndefined } from "@/@core/utils/helpers";
import { slugRule } from "@/utils/form/validation";
import { isEmpty } from "@/utils/helpers/str";
import { defineEmits, defineProps, ref, watch } from "vue";
import { PerfectScrollbar } from "vue3-perfect-scrollbar";

const props = defineProps({
  isDrawerOpen: { type: Boolean, required: true },
  loading: { type: Boolean, default: false },
  branches: { type: Array, default: () => [] },
  editingUom: { type: Object, default: null },
});

const emit = defineEmits(["update:isDrawerOpen", "save"]);

const isFormValid = ref(false);
const refForm = ref();
const isLoading = ref(false);

const form = ref({
  id: null,
  name: '',
  slug: '',
  is_active: true,
})

const closeNavigationDrawer = () => {
  emit("update:isDrawerOpen", false);
  emit("update:editingUom", null);
  resetForm();
};

const resetForm = () => {
  form.value = {
    id: null,
    name: '',
    slug: '',
    is_active: true,
  }
  refForm.value?.reset();
  refForm.value?.resetValidation();
};

watch(
  () => props.isDrawerOpen,
  (val) => {
    resetForm();
    if (val && props.editingUom) {
      form.value = {...props.editingUom};
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
      :title="form.id ? 'Edit Uom' : 'Add New Uom'"
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
                  v-model="form.slug"
                  :rules="[requiredValidator, slugRule]"
                  label="Slug"
                  placeholder="Enter slug"
                  :readonly="form.id > 0"
                  :data="form.id"
                />
              </VCol>

              <VCol cols="12" md="12">
                <AppTextField
                  v-model="form.name"
                  :rules="[requiredValidator]"
                  label="Name"
                  placeholder="Enter title"
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
                  {{ form.id ? 'Update Uom' : 'Create Uom' }}
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


