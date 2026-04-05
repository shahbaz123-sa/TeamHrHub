<script setup>
import { createUrl } from "@/@core/composable/createUrl";
import AppAutocomplete from "@/components/AppAutocomplete.vue";
import { useApi } from "@/composables/useApi";
import { slugRule } from "@/utils/form/validation";
import { isEmpty } from "@/utils/helpers/str";
import { defineEmits, defineProps, onMounted, ref, watch } from "vue";
import { PerfectScrollbar } from "vue3-perfect-scrollbar";

const props = defineProps({
  isDrawerOpen: { type: Boolean, required: true },
  loading: { type: Boolean, default: false },
  branches: { type: Array, default: () => [] },
  editingCategory: { type: Object, default: null },
});

const emit = defineEmits(["update:isDrawerOpen", "save"]);

const isFormValid = ref(false);
const refForm = ref();
const isLoading = ref(false);

const parentCategories = ref([])
const loadingParentCategories = ref(false)

const accessToken = useCookie('accessToken').value

const fetchParentCategories = async (search = '') => {
  loadingParentCategories.value = true

  const { data } = await useApi(
    createUrl("/product/category/parents", {
      query: {
        q: search,
        per_page: -1,
        for: form.value.id
      }
    }),
    {
      headers: {
        Authorization: `Bearer ${accessToken}`,
      },
    }
  );
  
  parentCategories.value = await data.value.data.map(category => ({ id: category.id, name: category.name }))
  loadingParentCategories.value = false
}

const form = ref({
  id: null,
  name: '',
  slug: '',
  image: null,
  parent_id: null,
  description: '',
  is_active: true,
})

const closeNavigationDrawer = () => {
  emit("update:isDrawerOpen", false);
  emit("update:editingCategory", null);
  resetForm();
};

const resetForm = () => {

  form.value = {
    id: null,
    name: '',
    slug: '',
    image: null,
    parent_id: null,
    description: '',
    is_active: true,
  }

  refForm.value?.reset();
  refForm.value?.resetValidation();
};

const onSubmit = async () => {
  const { valid } = await refForm.value.validate();
  if (!valid) return;

  isLoading.value = true;
  try {
    const fd = new FormData();
    Object.entries(form.value).forEach(([key, value]) => {
      if(['id', 'parent_id'].includes(key) && !value) return
      else if(key === 'image' && !(value instanceof File)) return
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
  resetForm()
};

watch(
  () => props.isDrawerOpen,
  (val) => {
    resetForm();
    if (val && props.editingCategory) {
      form.value = {...props.editingCategory};
    }
  },
  { immediate: true }
);

onMounted(fetchParentCategories)
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
      :title="form.id ? 'Edit Category' : 'Add New Category'"
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
                <VFileInput
                  v-model="form.image"
                  :label="form.image ? 'Image (Leave empty to keep current)' : 'Image'"
                  :placeholder="'Keep current photo'"
                  prepend-icon="tabler-camera"
                  clearable
                  density="comfortable"
                  variant="outlined"
                />
              </VCol>

              <VCol cols="12">
                <AppAutocomplete
                  v-model="form.parent_id"
                  label="Select Category Parent"
                  :items="parentCategories"
                  autocomplete
                  :loading="loadingParentCategories"
                  @update:search="fetchParentCategories"
                  placeholder="Choose Parent"
                  clearable
                />
              </VCol>

              <VCol cols="12">
                <AppTextarea
                  v-model="form.description"
                  label="Description"
                  placeholder="Enter description"
                  rows="4"
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
                  {{ form.id ? 'Update Category' : 'Create Category' }}
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


