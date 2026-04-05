<script setup>
import AppAutocomplete from '@/components/AppAutocomplete.vue'
import { slugRule } from '@/utils/form/validation'
import { isEmpty } from '@/utils/helpers/str'
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

const loadingAttributes = ref(false)
const fetchAttributes = async (optionIndex, search = '', attributeId = 0) => {

  loadingAttributes.value = true

  const { data } = await useApi(
    createUrl("/product/attributes", {
      query: {
        q: search,
        per_page: 10,
        status: 1,
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

watch(
  () => props.selectedVariation,
  (newVariation) => {
    if(newVariation && (newVariation.sku ?? '')) {
      variation.value = JSON.parse(JSON.stringify(newVariation))

      if(!isEmpty(newVariation.city_wise_prices))
      {
        newVariation.city_wise_prices.forEach(cityWisePrices => {
          variation.value.city_wise_prices[cityWisePrices.city_id] = cityWisePrices.price
        })
      }

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
        <VForm ref="refForm">
          <VRow>
              <VCol cols="12" md="6">
                <AppTextField readonly v-model="variation.name" label="Name" />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField readonly v-model="variation.sku" label="SKU" />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField readonly v-model="variation.price" label="Variant Price" type="number" />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField readonly v-model="variation.quantity" label="Quantity" type="number" />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField readonly v-model="variation.min_quantity" label="Min Quantity" type="number" />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField readonly v-model="variation.max_quantity" label="Max Quantity" type="number" />
              </VCol>
              <VCol cols="12" md="12">
                <div v-for="(opt, idx) in variation.options" :key="idx" class="mb-4">
                  <VRow>
                    <VCol cols="12" md="6">
                      <AppAutocomplete
                        v-model="opt.attribute"
                        return-object
                        label="Attribute"
                        :items="opt.attributes || []"
                        autocomplete
                        readonly
                      />
                    </VCol>
                    <VCol cols="12" md="6">
                      <AppAutocomplete
                        v-model="opt.value"
                        return-object
                        label="Attribute Value"
                        :items="opt.attributeValues || []"
                        autocomplete
                        readonly
                      />
                    </VCol>
                  </VRow>
                </div>
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
                    <tr v-for="(c, idx) in props.cities" :key="idx">
                      <td>{{ c.name }}</td>
                      <td>
                        <AppTextField
                          v-model="variation.city_wise_prices[c.id]"
                          type="number"
                          placeholder="0"
                          readonly
                        />
                      </td>
                    </tr>
                  </tbody>
                </VTable>
              </VCol>
          </VRow>
        </VForm>
      </VCardText>
    </VCard>
  </VDialog>
</template>
