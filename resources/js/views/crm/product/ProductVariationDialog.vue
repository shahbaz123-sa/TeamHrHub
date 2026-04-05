<script setup>
import AppAutocomplete from '@/components/AppAutocomplete.vue'
import { slugRule } from '@/utils/form/validation'
import { onMounted, ref } from 'vue'

const props = defineProps({
  show: Boolean,
  cities: {
    type: Array,
    default: []
  },
  selectedVariation: {
    type: Object,
    default: null,
  }
})

const emit = defineEmits(['update:show', 'update:selectedVariation', 'save'])

const accessToken = useCookie('accessToken').value

const refForm = ref(null)
const priceForAll = ref(false)

const applyPriceForAll = (price) => {
  props.cities.forEach(city => {
    variation.value.city_wise_prices[city.id] = price
  })
}

const loadingAttributes = ref(false)
const fetchAttributes = async (optionIndex, search = '', attributeId = 0) => {

  loadingAttributes.value = true

  const { data } = await useApi(
    createUrl("/product/attributes", {
      query: {
        q: search,
        per_page: 10,
        status: 1,
        sort_by: 'name',
        order_by: 'asc'
      }
    }),
    {
      headers: {
        Authorization: `Bearer ${accessToken}`,
      },
    }
  );
  
  if((variation.value?.options[optionIndex]?.attributes ?? undefined) !== undefined)
  {
    variation.value.options[optionIndex].attributes = await data.value.data.map(category => ({ id: category.id, name: category.name }))
  }
  loadingAttributes.value = false

  if(search && attributeId > 0)
  {
    fetchAttributeValues(optionIndex, '', attributeId)
  }
}

const loadingAttributeValues = ref(false)
const fetchAttributeValues = async (optionIndex, search = '', attributeId = 0) => {

  if(attributeId === 0) return

  loadingAttributeValues.value = true

  const { data } = await useApi(
    createUrl("/product/attribute/values", {
      query: {
        q: search,
        per_page: 10,
        attribute_id: attributeId,
        status: 1,
        sort_by: 'name',
        order_by: 'asc'
      }
    }),
    {
      headers: {
        Authorization: `Bearer ${accessToken}`,
      },
    }
  );
  
  if((variation.value?.options[optionIndex]?.attributeValues ?? undefined) !== undefined)
  {
    variation.value.options[optionIndex].attributeValues = await data.value.data.map(category => ({ id: category.id, name: category.name }))
  }
  loadingAttributeValues.value = false
}

const variations = ref([])
const variation = ref({
  id: null,
  name: '',
  sku: '',
  quantity: 0,
  min_quantity: 0,
  max_quantity: 0,
  price: 0,
  city_wise_prices: [],
  options: [
    {
      attribute: null,
      value: null,
      attributes: [],
      attributeValues: [],
    }
  ]
})

const addOption = () => {
  const lastIndex = variation.value?.options?.length - 1;

  if(variation.value.options?.length)
  {
    if(!variation.value.options[lastIndex].attribute || !variation.value.options[lastIndex].value) {
      return;
    }
  }

  variation.value.options.push({
    attribute: null,
    value: null,
    attributes: [],
    attributeValues: [],
  })

  fetchAttributes(variation.value.options.length - 1)
}

const removeOption = idx => {
  variation.value.options.splice(idx, 1)
}

const showError = ref(false)
const errorMsg = ref(null)
const saveVariation = async () => {
  
  const { valid } = await refForm.value?.validate()

  if(!valid)
  {
    showError.value = true
    errorMsg.value = "Please fill required fields"
    return
  }
  
  if(isDuplicateVariation())
  {
    showError.value = true
    errorMsg.value = "Variation already exists"
    return
  }

  variations.value.push({
    ...variation.value,
    options: {...variation.value.options}
  })

  emit('save', JSON.parse(JSON.stringify(variation.value)))

  if(props.selectedVariation)
  {
    close()
  }
}

const isDuplicateVariation = () => {
  const selectedVariationId = props.selectedVariation?.id || null;
  return variations.value.some(v => {
    if(props.selectedVariation && selectedVariationId === v.id)
    {
      return false
    }

    if(v.sku === variation.value.sku || JSON.stringify(v.options) === JSON.stringify(variation.value.options))
    {
      return true
    }

    return false
  })
}

const close = () => {
  emit('update:show', false)
  resetForm()
}

const resetForm = () => {
  refForm.value?.reset()
  refForm.value?.resetValidation()
  variation.value = {
    id: null,
    name: '',
    sku: '',
    quantity: 0,
    min_quantity: 0,
    max_quantity: 0,
    price: 0,
    city_wise_prices: [],
    options: [
      {
        attribute: null,
        value: null,
        attributes: [],
        attributeValues: [],
      }
    ]
  }
  emit('update:selectedVariation', null)
}

watch(
  () => variation.value.options,
  (newOptions) => {
    newOptions.forEach((option, index) => {
      if(!option.attribute) {
        if (option.attributeValues?.length > 0) {
          option.attributeValues = []
        }
        if (option.value !== null) {
          option.value = null
        }
      }
    })
  },
  { deep: true }
)

watch(showError, (val) => {
  if(val) {
    setTimeout(() => {
      showError.value = false
    }, 2000);
  }
})

watch(
  () => props.selectedVariation,
  (newVariation) => {
    if(newVariation && (newVariation.sku ?? '')) {
      variation.value = JSON.parse(JSON.stringify(newVariation))

      if(variation.value?.options?.length)
      {
        variation.value.options.forEach((option, index) => {
          fetchAttributes(index, '', option.attribute?.id ?? 0)
          fetchAttributeValues(index, '', option.attribute?.id ?? 0)
        })
      } else {
        variation.value.options = [
          {
            attribute: null,
            value: null,
            attributes: [],
            attributeValues: [],
          }
        ]
      }
    } else {
      resetForm()
      fetchAttributes(0)
      fetchAttributeValues(0)
    }
  },
  { immediate: true }
)

onMounted(() => {
  resetForm()
  fetchAttributes(0)
  fetchAttributeValues(0)
})
</script>

<template>
  <VDialog
    :model-value="props.show"
    max-width="600"
    @afterLeave="close"
  >
    <VCard>
      <VCardTitle>Add Variation</VCardTitle>
      <VCardText>
        <VForm ref="refForm" :key="variation.options.length">
          <VRow>
              <VCol cols="12" md="6">
                <AppTextField v-model="variation.name" label="Name" :rules="[requiredValidator]" />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField v-model="variation.sku" label="SKU" :rules="[requiredValidator, slugRule]" :readonly="!!props.selectedVariation" />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField v-model="variation.price" label="Variant Price" type="number" :rules="[requiredValidator]" />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField v-model="variation.quantity" label="Quantity" type="number" :rules="[requiredValidator]" />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField v-model="variation.min_quantity" label="Min Quantity" type="number" :rules="[requiredValidator]" />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField v-model="variation.max_quantity" label="Max Quantity" type="number" :rules="[requiredValidator]" />
              </VCol>
              <VCol cols="12" md="12">
                <div v-for="(opt, idx) in variation.options" :key="idx" class="mb-4">
                  <VRow>
                    <VCol cols="12" md="5">
                      <AppAutocomplete
                        v-model="opt.attribute"
                        return-object
                        label="Attribute"
                        :items="opt.attributes || []"
                        autocomplete
                        :loading="loadingAttributes"
                        @update:search="fetchAttributes(idx, $event, opt.attribute?.id ?? 0)"
                        clearable
                        clear-icon="tabler-x"
                        :rules="[requiredValidator]"
                      />
                    </VCol>
                    <VCol cols="12" md="5">
                      <AppAutocomplete
                        v-model="opt.value"
                        return-object
                        label="Attribute Value"
                        :items="opt.attributeValues || []"
                        autocomplete
                        :loading="loadingAttributeValues"
                        @update:search="fetchAttributeValues(idx, $event, opt.attribute?.id ?? 0)"
                        clearable
                        clear-icon="tabler-x"
                        :rules="[requiredValidator]"
                      />
                    </VCol>
                    <VCol cols="12" md="1" class=" align-content-center">
                      <VIcon
                        class="mt-5"
                        icon="tabler-trash"
                        color="error"
                        size="small"
                        @click="removeOption(idx)" v-if="variation.options.length > 1">
                      </VIcon>
                    </VCol>
                  </VRow>
                </div>
                <VBtn color="primary" size="small" @click="addOption">Add Option</VBtn>
              </VCol>
              <VCol cols="12" md="12">
                <VCardTitle>City Wise Price</VCardTitle>
                <VTable>
                  <thead>
                    <tr>
                      <th>City</th>
                      <th>Price</th>
                    </tr>
                  </thead>
                  <tbody>
                    <VSpacer class="mt-2"/>
                    <tr>
                      <th>
                        <div class="d-flex flex-raw align-center justify-space-between">
                          <span>Price for all</span>
                          <VSwitch
                            density="compact"
                            v-model="priceForAll"
                          />
                        </div>
                      </th>
                      <td>
                        <AppTextField
                          type="number"
                          placeholder="0"
                          :disabled="!priceForAll"
                          @update:modelValue="applyPriceForAll"
                        />
                      </td>
                    </tr>
                    <tr v-for="(c, idx) in props.cities" :key="idx">
                      <td>{{ c.name }}</td>
                      <td>
                        <AppTextField
                          v-model="variation.city_wise_prices[c.id]"
                          type="number"
                          placeholder="0"
                          :rules="[requiredValidator]"
                        />
                      </td>
                    </tr>
                  </tbody>
                </VTable>
              </VCol>
              <VAlert
                v-if="showError"
                type="error"
                variant="tonal"
              >
                {{ errorMsg }}
              </VAlert>
          </VRow>
        </VForm>
      </VCardText>
      <VCardActions>
        <VSpacer />
        <VBtn color="primary" @click="saveVariation">Save</VBtn>
        <VBtn text @click="close">Cancel</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
