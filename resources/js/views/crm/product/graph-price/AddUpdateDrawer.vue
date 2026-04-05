<script setup>
import { isEmpty } from '@/utils/helpers/str';
import { defineEmits, defineProps, onMounted, ref, watch } from 'vue';

const props = defineProps({
  isDrawerOpen: { type: Boolean, required: true },
  loading: { type: Boolean, default: false },
  editingGraphPrice: { type: Object, default: null },
});

const emit = defineEmits(['update:isDrawerOpen', 'save']);

const isFormValid = ref(false);
const refForm = ref();
const isLoading = ref(false);

const form = ref({
  id: null,
  category_name: null,
  product_name: null,
  brand_name: null,
  datetime: null,
  datetime_raw: null,
  price: null,
  price_raw: null,
  market: '',
  currency: '',
  unit_name: null,
})

const accessToken = useCookie('accessToken').value
const categories = ref([])
const products = ref([])
const brands = ref([])
const units = ref([])

const fetchLookups = async () => {
  try {
    const [cRes, pRes, bRes, unitRes] = await Promise.all([
      $api('/product/categories', { query: { per_page: -1 }, method: 'GET', headers: { Authorization: `Bearer ${accessToken}` } }),
      $api('/products', { query: { per_page: -1 }, method: 'GET', headers: { Authorization: `Bearer ${accessToken}` } }),
      $api('/product/brands', { query: { per_page: -1 }, method: 'GET', headers: { Authorization: `Bearer ${accessToken}` } }),
      $api('/product/uoms', { query: { per_page: -1 }, method: 'GET', headers: { Authorization: `Bearer ${accessToken}` } }),
    ])

    categories.value = cRes.data.map(i => ({ value: i.name, title: i.title || i.name || i.slug }))
    products.value = pRes.data.map(i => ({ value: i.name, title: i.name || i.title }))
    brands.value = bRes.data.map(i => ({ value: i.name, title: i.name }))
    units.value = unitRes.data.map(i => ({ value: i.name, title: i.name }))
  } catch (e) {
    // ignore
  }
}

const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
  emit('update:editingGraphPrice', null)
  resetForm()
}

const resetForm = () => {
  form.value = {
    id: null,
    category_name: null,
    product_name: null,
    brand_name: null,
    datetime: null,
    datetime_raw: null,
    price: null,
    price_raw: null,
    market: '',
    currency: '',
    unit_name: null,
  }
  refForm.value?.reset()
  refForm.value?.resetValidation()
}

watch(() => props.editingGraphPrice, (val) => {
  resetForm()
  if (val) {
    // ensure incoming edit object maps to new name fields
    form.value = {
      id: val.id ?? null,
      category_name: val.category_name ?? val.category?.name ?? null,
      product_name: val.product_name ?? val.product?.name ?? null,
      brand_name: val.brand_name ?? val.brand?.name ?? null,
      datetime: val.datetime ?? null,
      datetime_raw: val.datetime_raw ?? null,
      price: val.price ?? null,
      price_raw: val.price_raw ?? null,
      market: val.market ?? '',
      currency: val.currency ?? '',
      unit_name: val.unit_name ?? val.unit?.name ?? null,
    }
  }
}, { immediate: true })

watch(() => props.isDrawerOpen, (val) => { if (!val) resetForm() })

onMounted(fetchLookups)

const onSubmit = async () => {
  const { valid } = await refForm.value.validate()
  if (!valid) return

  isLoading.value = true
  try {
    const fd = new FormData()
    
    if (form.value.datetime && !form.value.datetime_raw) {
      form.value.datetime_raw = form.value.datetime
    }
    
    if (form.value.price && !form.value.price_raw) {
      form.value.price_raw = form.value.price
    }

    Object.entries(form.value).forEach(([key, value]) => {
      if (key === 'id' && isEmpty(value)) return
      fd.append(key, isEmpty(value) ? '' : value)
    })
    emit('save', fd, form.value.id || null)
  } finally {
    isLoading.value = false
  }
}

const handleDrawerModelValueUpdate = (val) => emit('update:isDrawerOpen', val)
</script>

<template>
  <VNavigationDrawer temporary :width="640" location="end" class="scrollable-content" :model-value="props.isDrawerOpen" @update:model-value="handleDrawerModelValueUpdate">
    <AppDrawerHeaderSection :title="form.id ? 'Edit Graph Price' : 'Add New Graph Price'" @cancel="closeNavigationDrawer" />
    <VDivider />
    <VCard flat>
      <VCardText>
        <VForm ref="refForm" v-model="isFormValid" @submit.prevent="onSubmit">
          <VRow>
            <VCol cols="12" md="6">
              <AppSelect v-model="form.category_name" :items="categories" label="Category (name)" clearable />
            </VCol>
            <VCol cols="12" md="6">
              <AppSelect v-model="form.product_name" :items="products" label="Product (name)" clearable />
            </VCol>

            <VCol cols="12" md="6">
              <AppSelect v-model="form.brand_name" :items="brands" label="Brand (name)" clearable />
            </VCol>

            <VCol cols="12" md="6">
              <AppDateTimePicker v-model="form.datetime" label="Datetime" clearable />
            </VCol>

            <VCol cols="12" md="6">
              <AppTextField v-model="form.market" label="Market" placeholder="e.g. Retail" />
            </VCol>

            <VCol cols="12" md="6">
              <AppTextField v-model="form.currency" label="Currency" placeholder="e.g. USD" />
            </VCol>

            <VCol cols="12" md="6">
              <AppSelect v-model="form.unit_name" :items="units" label="Unit (name)" clearable />
            </VCol>

            <VCol cols="12" class="d-flex justify-end gap-4 mt-6">
              <VBtn type="submit" :loading="isLoading || props.loading">{{ form.id ? 'Update' : 'Create' }}</VBtn>
              <VBtn type="reset" variant="tonal" color="error" @click="closeNavigationDrawer">Cancel</VBtn>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
    </VCard>
  </VNavigationDrawer>
</template>
