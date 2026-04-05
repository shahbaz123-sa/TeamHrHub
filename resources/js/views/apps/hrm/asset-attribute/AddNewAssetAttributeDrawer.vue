<script setup>
import { PerfectScrollbar } from "vue3-perfect-scrollbar"

const props = defineProps({
  isOpen: Boolean,
  forView: Boolean,
  assetAttribute: Object,
  assetTypes: Array,
  loading: Boolean,
})

const emit = defineEmits(["update:isOpen", "submit"])

const isFormValid = ref(false)
const refForm = ref()

const form = ref({
  id: null,
  name: "",
  asset_type_id: null,
  field_type: "string",
  options: "",
})

// Field type options
const fieldTypeOptions = [
  { title: "Text", value: "string" },
  { title: "Number", value: "number" },
  { title: "Date", value: "date" },
  { title: "Boolean", value: "boolean" },
  { title: "Select", value: "select" },
]

// Reset form when opening/closing drawer
watch(
  () => props.isOpen,
  (val) => {
    refForm.value?.reset()
    refForm.value?.resetValidation()
    form.value = props.assetAttribute
      ? { 
          ...props.assetAttribute,
          options: props.assetAttribute.options 
            ? (Array.isArray(props.assetAttribute.options) 
                ? JSON.stringify(props.assetAttribute.options) 
                : props.assetAttribute.options)
            : ""
        }
      : { 
          id: null, 
          name: "", 
          asset_type_id: null, 
          field_type: "string", 
          options: "" 
        }
  }
)

// Watch field type changes to clear options when not select
watch(
  () => form.value.field_type,
  (newType) => {
    if (newType !== "select") {
      form.value.options = ""
    }
  }
)

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      let options = null
      
      if (form.value.field_type === "select" && form.value.options) {
        try {
          options = typeof form.value.options === 'string' 
            ? JSON.parse(form.value.options) 
            : form.value.options
        } catch (e) {
          console.error("Invalid JSON in options:", e)
          options = null
        }
      }

      const formData = {
        name: form.value.name,
        asset_type_id: form.value.asset_type_id,
        field_type: form.value.field_type,
        options: options,
      }

      if (form.value.id) {
        formData.id = form.value.id
      }

      emit("submit", formData)
    }
  })
}

const closeForm = () => {
  emit("update:isOpen", false)
}

// Helper function to format options for display
const formatOptionsForDisplay = (options) => {
  if (!options) return ""
  try {
    if (typeof options === 'string') {
      return options
    }
    return JSON.stringify(options)
  } catch {
    return ""
  }
}
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
      :title="form.id ? 'Update Asset Attribute' : 'Add New Asset Attribute'"
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
                  label="Attribute Name"
                  placeholder="Enter attribute name"
                  :readonly="props.forView"
                />
              </VCol>

              <VCol cols="12">
                <AppSelect
                  v-model="form.asset_type_id"
                  :items="props.assetTypes"
                  item-title="name"
                  item-value="id"
                  :rules="[requiredValidator]"
                  label="Asset Type"
                  placeholder="Select asset type"
                  :readonly="props.forView"
                />
              </VCol>

              <VCol cols="12">
                <AppSelect
                  v-model="form.field_type"
                  :items="fieldTypeOptions"
                  item-title="title"
                  item-value="value"
                  :rules="[requiredValidator]"
                  label="Field Type"
                  placeholder="Select field type"
                  :readonly="props.forView"
                />
              </VCol>

              <VCol cols="12" v-if="form.field_type === 'select'">
                <AppTextarea
                  v-model="form.options"
                  label="Options (JSON Array)"
                  placeholder='["Option 1", "Option 2", "Option 3"]'
                  rows="3"
                  :readonly="props.forView"
                  hint="Enter options as JSON array format"
                  persistent-hint
                />
                <div class="text-caption text-medium-emphasis mt-1">
                  Example: ["Small", "Medium", "Large"] or ["Yes", "No"]
                </div>
              </VCol>

              <VCol cols="12" v-if="props.forView && form.options">
                <VLabel class="text-body-2 font-weight-medium mb-2">Options Preview:</VLabel>
                <VChip
                  v-for="(option, index) in JSON.parse(form.options || '[]')"
                  :key="index"
                  size="small"
                  class="me-2 mb-2"
                  variant="tonal"
                  color="primary"
                >
                  {{ option }}
                </VChip>
              </VCol>

              <VCol cols="12" class="d-flex justify-end gap-4 mt-6" v-if="!props.forView">
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

              <VCol cols="12" class="d-flex justify-end gap-4 mt-6" v-if="props.forView">
                <VBtn
                  variant="tonal"
                  color="primary"
                  @click="closeForm"
                >
                  Close
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </PerfectScrollbar>
  </VNavigationDrawer>
</template>

<style scoped>
.scrollable-content {
  block-size: 100vh;
}
</style>
