<template>
  <VDialog :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)" max-width="800">
    <VCard :title="asset ? 'Edit Asset' : 'Add Asset'">
      <VDivider />
      <VCardText>
        <VAlert
          v-if="alert.show"
          :title="alert.title"
          :text="alert.message"
          variant="tonal"
          :color="alert.color"
        />
        <VForm ref="refForm" @submit.prevent="submitForm">
          <VRow>
            <VCol cols="12" md="6">
              <AppSelect v-model="form.asset_type_id" :items="assetTypes" label="Asset Type" required />
            </VCol>
            <VCol cols="12" md="6">
              <AppTextField v-model="form.name" label="Asset Name" placeholder="Enter asset name" required />
            </VCol>
            <VCol cols="12" md="6">
              <AppDateTimePicker v-model="form.purchase_date" label="Purchase Date" placeholder="Select date" required />
            </VCol>
            <VCol cols="12" md="6">
              <AppTextField v-model="form.make_model" label="Make model" placeholder="Enter make model" required />
            </VCol>
            <VCol cols="12" md="12">
              <AppTextField v-model="form.serial_no" label="Serial No" placeholder="Enter serial number" required />
            </VCol>
            <VCol cols="12" md="12">
              <AppTextarea v-model="form.description" label="Description" rows="3" />
            </VCol>
            <!-- Dynamic Asset Attributes Section -->
            <VCol cols="12" v-if="assetAttributes.length > 0">
              <VDivider class="my-4" />
              <h4 class="mb-4">Asset Attributes</h4>
              
              <VRow v-if="loadingAttributes">
                <VCol cols="12" class="text-center">
                  <VProgressCircular indeterminate color="primary" />
                  <p class="mt-2">Loading attributes...</p>
                </VCol>
              </VRow>
              <VRow v-else>
                <VCol 
                  v-for="attribute in assetAttributes" 
                  :key="attribute.id"
                  cols="12" 
                  :md="attribute.field_type === 'boolean' ? 6 : 6"
                >
                  <!-- String Field -->
                  <AppTextField
                    v-if="attribute.field_type === 'string'"
                    v-model="form.attributes[attribute.id]"
                    :label="attribute.name"
                    required
                  />
                  
                  <!-- Number Field -->
                  <AppTextField
                    v-else-if="attribute.field_type === 'number'"
                    v-model="form.attributes[attribute.id]"
                    :label="attribute.name"
                    type="number"
                    required
                  />
                  
                  <!-- Date Field -->
                  <AppDateTimePicker
                    v-else-if="attribute.field_type === 'date'"
                    v-model="form.attributes[attribute.id]"
                    :label="attribute.name"
                    required
                  />
                  
                  <!-- Boolean Field -->
                  <VCheckbox
                    v-else-if="attribute.field_type === 'boolean'"
                    v-model="form.attributes[attribute.id]"
                    :label="attribute.name"
                    color="primary"
                    :true-value="true"
                    :false-value="false"
                  />
                  
                  <!-- Select Field -->
                  <AppSelect
                    v-else-if="attribute.field_type === 'select'"
                    v-model="form.attributes[attribute.id]"
                    :label="attribute.name"
                    :items="getSelectOptions(attribute)"
                    required
                  />
                  
                  <!-- Default Text Field -->
                  <AppTextField
                    v-else
                    v-model="form.attributes[attribute.id]"
                    :label="attribute.name"
                    required
                  />
                </VCol>
              </VRow>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
      <VCardActions>
        <VSpacer />
        <VBtn color="primary" @click="submitForm">{{ asset ? 'Update' : 'Create' }}</VBtn>
        <VBtn color="secondary" @click="$emit('update:modelValue', false)">Cancel</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
<script setup>
import { defineEmits, defineProps, ref, watch } from 'vue'

const props = defineProps({
  modelValue: Boolean,
  asset: Object,
  assetTypes: Array,
  employees: Array,
})

const emit = defineEmits(['update:modelValue', 'refresh'])

const form = ref({
  asset_type_id: '',
  name: '',
  serial_no: '',
  purchase_date: '',
  make_model: '',
  description: '',
  // Dynamic attributes will be added here
  attributes: {},
})

const loading = ref(false)
const refForm = ref()
const assetAttributes = ref([])
const loadingAttributes = ref(false)

const alert = ref({ show: false, title: '', message: '', color: '' })

const resetAlert = () => {
  alert.value = { show: false, title: '', message: '', color: '' }
}

const resetForm = () => {
  form.value = {
    asset_type_id: '',
    name: '',
    serial_no: '',
    purchase_date: '',
    make_model: '',
    description: '',
    attributes: {},
  }
  refForm.value?.reset()
  refForm.value?.resetValidation()
}

// Watch for asset type changes to load attributes
watch(() => form.value.asset_type_id, async (newAssetTypeId) => {
  if (newAssetTypeId) {
    await fetchAssetAttributes(newAssetTypeId)
  } else {
    assetAttributes.value = []
    form.value.attributes = {}
  }
})

// Watch for asset changes
watch(() => props.asset, async (val) => {
  if (props.asset) {
    form.value = { ...props.asset }
    if (props.asset.asset_type_id) {
      await fetchAssetAttributes(props.asset.asset_type_id)
    }
  } else {
    resetForm()
  }
  resetAlert()
})

// Fetch asset attributes for the selected asset type
const fetchAssetAttributes = async (assetTypeId) => {
  loadingAttributes.value = true
  try {
    const response = await $api(`/asset-attributes?asset_type_id=${assetTypeId}`)
    if (response.success) {
      assetAttributes.value = response.data || []
      
      // Initialize attribute values
      if (props.asset && props.asset.attributes) {
        // If editing, use existing attribute values and convert types
        form.value.attributes = {}
        
        Object.keys(props.asset.attributes).forEach(attrId => {
          const value = props.asset.attributes[attrId]
          const attribute = assetAttributes.value.find(attr => attr.id == attrId)
          
          if (attribute && attribute.field_type === 'boolean') {
            // Convert string 'true'/'false' to boolean
            form.value.attributes[attrId] = value === 'true' || value === true
          } else if (attribute && attribute.field_type === 'number') {
            // Convert to number
            form.value.attributes[attrId] = parseFloat(value) || 0
          } else {
            // Keep as string
            form.value.attributes[attrId] = value
          }
        })
      } else {
        // If creating new, initialize empty values based on field type
        form.value.attributes = {}
        assetAttributes.value.forEach(attr => {
          if (attr.field_type === 'boolean') {
            form.value.attributes[attr.id] = false
          } else if (attr.field_type === 'number') {
            form.value.attributes[attr.id] = 0
          } else {
            form.value.attributes[attr.id] = ''
          }
        })
      }
    } else {
      assetAttributes.value = []
      form.value.attributes = {}
    }
  } catch (err) {
    console.error('Failed to load asset attributes:', err)
    assetAttributes.value = []
    form.value.attributes = {}
  } finally {
    loadingAttributes.value = false
  }
}

// Get field component based on field type
const getFieldComponent = (attribute) => {
  switch (attribute.field_type) {
    case 'string':
      return 'AppTextField'
    case 'number':
      return 'AppTextField'
    case 'date':
      return 'AppDateTimePicker'
    case 'boolean':
      return 'VCheckbox'
    case 'select':
      return 'AppSelect'
    default:
      return 'AppTextField'
  }
}

// Get field props based on field type
const getFieldProps = (attribute) => {
  const baseProps = {
    label: attribute.name,
    required: true,
  }

  switch (attribute.field_type) {
    case 'string':
      return { ...baseProps, type: 'text' }
    case 'number':
      return { ...baseProps, type: 'number' }
    case 'date':
      return { ...baseProps }
    case 'boolean':
      return { ...baseProps, label: attribute.name }
    case 'select':
      return {
        ...baseProps,
        items: attribute.options || [],
        itemTitle: 'value',
        itemValue: 'value',
      }
    default:
      return baseProps
  }
}

// Get field options for select fields
const getSelectOptions = (attribute) => {
  if (attribute.field_type === 'select' && attribute.options) {
    return attribute.options.map(option => ({
      title: option,
      value: option,
    }))
  }
  return []
}

const submitForm = async () => {
  loading.value = true
  resetAlert()
  try {
    const method = props.asset && props.asset.id ? 'PUT' : 'POST'
    const url = props.asset && props.asset.id ? `/assets/${props.asset.id}` : '/assets'
    const payload = { ...form.value }
    
    const response = await $api(url, {
      method,
      body: JSON.stringify(payload),
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      withCredentials: true,
    })
    
    if (response.success) {
      $toast.success(response.message || `Asset ${method === 'POST' ? 'created' : 'updated'} successfully!`)
      emit('update:modelValue', false)
      emit('refresh')
    } else {
      throw new Error(response.message || 'Failed to save asset')
    }
  } catch (err) {
    let message = 'Failed to save asset'
    if (err.response && err.response.status === 422) {
      message = Object.values(err.response?._data?.errors).join('\n')
    } else if (err.message) {
      message = err.message
    }
    alert.value = { show: true, title: 'Error', message, color: 'error' }
  } finally {
    loading.value = false
    resetForm();
  }
}

</script>


<style scoped>
.v-card-title {
  background-color: unset;
  text-align: "center";
}
</style>

