<script setup>
import AppAutocomplete from "@/components/AppAutocomplete.vue";
import DropZone from "@/components/DropZone.vue";
import { router } from "@/plugins/1.router";
import { formatLongText } from "@/utils/helpers/str";
import ProductVariationDialog from "@/views/crm/product/ProductVariationDialog.vue";
import { onMounted, ref, watch } from "vue";
import { useRoute } from "vue-router";

const accessToken = useCookie('accessToken').value
const route = useRoute()

const statuses = ref([
  {
    title: 'Publish',
    value: 'publish',
  },
  {
    title: 'Inactive',
    value: 'inactive',
  },
])

const form = ref({
  name: null,
  sku: null,
  wc_slug: null,
  short_description: '',
  description: '',
  quantity: 0,
  min_quantity: 0,
  max_quantity: 0,
  price: 0,
  stock_status: true,
  ask_for_quote: false,
  status: 'publish',
  uom_id: null,
  brand_id: null,
  existing_images: [],
  images: [],
  variations: [],
  categories: [],
  city_wise_prices: [],
  tags: [],
  brand_id: null,
  has_variation: false,
});

const priceForAll = ref(false)

const applyPriceForAll = (price) => {
  cities.value.forEach(city => {
    form.value.city_wise_prices[city.id] = price
  })
}

const formatVariation = (variations) => {
  return variations.map(variation => {
    const options = formatVariationOptions(variation.attributes_values ?? []);

    const cityWisePrices = [];
    variation.city_wise_prices.forEach((cityWisePrice) => {
      cityWisePrices[cityWisePrice.city_id] = cityWisePrice.price;
    });

    return {
      id: variation.id,
      name: variation.name,
      sku: variation.sku,
      quantity: variation.quantity,
      min_quantity: variation.min_quantity,
      max_quantity: variation.min_quantity,
      price: variation.price,
      city_wise_prices: [...cityWisePrices],
      options
    }
  })
}

const formatVariationOptions = (options) => {
  return options.map(option => {
    const attribute = option.attribute ? { id: option.attribute.id, name: option.attribute.name } : null;
    const value = { id: option.id, name: option.name };
    return {
      attribute,
      value,
      attributes: [attribute],
      attributeValues: [value],
    }
  })
}

const updatingProduct = ref(false)
const updateProduct = async () => {
  updatingProduct.value = true
  try {
    const formDataToSend = new FormData()
    Object.entries(form.value).forEach(([k, v]) => {
      if (v !== null && v !== undefined && v !== '' && !(['images', 'variations', 'city_wise_prices'].includes(k))) {
        formDataToSend.append(k, v)
      }
    })

    form.value.images.forEach((image, index) => {
      formDataToSend.append(`images[${index}]`, image)
    })
    
    form.value.existing_images.forEach((image, index) => {
      formDataToSend.append(`existing_images[${index}]`, image.id)
    })

    if(form.value.has_variation)
    {
      formDataToSend.append('variations', JSON.stringify(form.value.variations.map(variation => ({
        ...variation,
        options: variation.options.map(opt => ({
          attribute: opt.attribute,
          value: opt.value,
        })),
      }))));
    } else {
      form.value.city_wise_prices.forEach((price, cityId) => {
        if(cityId > 0)
        {
          formDataToSend.append(`city_wise_prices[${cityId}]`, price)
        }
      })
    }
    
    formDataToSend.append('_method', 'PUT')

    await $api(`/products/${route.params.id}`, {
      method: 'POST',
      body: formDataToSend,
      headers: {
        Authorization: `Bearer ${accessToken}`,
      },
    });

    $toast.success("Product updated successfully");
    router.go(0);
  } catch (error) {
    let message = `Failed to add product`

    if (error && error.status === 201) {
      $toast.success("Product updated successfully")
      router.go(0)
      return
    }

    if (error && error.status === 422) {
      message = Object.values(error?._data?.errors).slice(0, 2).join("\n")
    }

    $toast.error(message)
  } finally {
    updatingProduct.value = false
  }
};

const categories = ref([])
const loadingCategories = ref(false)
const fetchCategories = async (search = '') => {
  loadingCategories.value = true

  const { data } = await useApi(
    createUrl("/product/categories", {
      query: {
        q: search,
        per_page: -1,
        status: 1,
        with_id: form.value.categories.join(','),
        parent_id: selectedParentCategory.value,
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
  
  categories.value = await data.value.data.map(category => ({id: category.id, name: formatLongText(category.name)}))
  loadingCategories.value = false
}

const selectedParentCategory = ref()
const parentCategories = ref([])
const loadingParentCategories = ref(false)
const fetchParentCategories = async (search = '') => {
  loadingParentCategories.value = true

  const { data } = await useApi(
    createUrl("/product/category/parents", {
      query: {
        q: search,
        per_page: -1,
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
  
  parentCategories.value = await data.value.data.map(category => ({id: category.id, name: formatLongText(category.name)}))
  loadingParentCategories.value = false

  fetchCategories()
}

const tags = ref([])
const loadingTags = ref(false)
const fetchTags = async (search = '') => {
  loadingTags.value = true

  const { data } = await useApi(
    createUrl("/product/tags", {
      query: {
        q: search,
        per_page: -1,
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
  
  tags.value = await data.value.data.map(tag => ({ id: tag.id, name: formatLongText(tag.name) }))
  loadingTags.value = false
}

const uoms = ref([])
const loadingUoms = ref(false)
const fetchUoms = async (search = '') => {
  loadingUoms.value = true

  const { data } = await useApi(
    createUrl("/product/uoms", {
      query: {
        q: search,
        per_page: -1,
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
  
  uoms.value = await data.value.data.map(uom => ({ id: uom.id, name: uom.name }))
  loadingUoms.value = false
}

const brands = ref([])
const loadingBrands = ref(false)
const fetchBrands = async (search = '') => {
  loadingBrands.value = true
  const { data } = await useApi(
    createUrl("/product/brands", {
      query: {
        q: search,
        per_page: -1,
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
  brands.value = await data.value.data.map(brand => ({ id: brand.id, name: formatLongText(brand.name, 40) }))
  loadingBrands.value = false
}

const cities = ref([])
const loadingCity = ref(false)
const fetchCities = async (search = '') => {
  loadingCity.value = true
  const { data } = await useApi(
    createUrl("/product/cities", {
      query: {
        q: search,
        per_page: -1,
        sort_by: 'name',
        order_by: 'asc',
        status: 1,
      }
    }),
    {
      headers: {
        Authorization: `Bearer ${accessToken}`,
      },
    }
  );
  cities.value = await data.value.data.map(brand => ({ id: brand.id, name: brand.name }))
  loadingCity.value = false
}

const fetchProduct = async () => {
  try {
    const { data } = await $api(`/products/${route.params.id}`, {
      method: 'GET',
    });

    const product = {...data};

    form.value = {
      ...form.value,
      name: product.name,
      sku: product.sku,
      wc_slug: product.slug,
      short_description: product.short_description,
      description: product.description,
      quantity: product.quantity,
      min_quantity: product.min_quantity,
      max_quantity: product.max_quantity,
      price: product.price,
      stock_status: product.stock_status === 'instock',
      ask_for_quote: product.ask_for_quote,
      status: product.status,
      uom_id: product.uom_id,
      brand_id: product.brand_id,
      existing_images: product.images ?? [],
      variations: formatVariation(product.variations ?? []),
      categories: (product.categories ?? []).filter(category => category?.parent_id > 0).map(category => (category.id)),
      tags: (product.tags ?? []).map(tag => (tag.id)),
      has_variation: (product?.variations?.length || 0) > 0
    };

    (product.city_wise_prices ?? []).forEach(cityWisePrices => {
      form.value.city_wise_prices[cityWisePrices.city_id] = cityWisePrices.price
    })

    selectedParentCategory.value = (product.categories ?? []).map(category => (category.parent_id > 0 ? category.parent_id : category.id))
    fetchCategories()

  } catch (error) {
    $toast.error("Failed to fetch product details");
  }
};

const removeExistingImage = (index, image) => {
  form.value.existing_images.splice(index, 1)
}

const showVariationDialog = ref(false)
const selectedVariation = ref(null)
const openVariationDialog = (variation = null) => {
  showVariationDialog.value = true
  selectedVariation.value = variation
}

const addVariation = (variation) => {
  if(variation.id) {
    const index = form.value.variations.findIndex(v => v.id === variation.id)
    if(index !== -1) {
      form.value.variations[index] = variation
      return
    }
  }
  form.value.variations.push(variation)
}

const dropZoneRef = ref()
watch(
  () => dropZoneRef.value?.fileData,
  (newVal) => {
    form.value.images = newVal?.map(item => item.file)
  },
  { deep: true }
)
watch(selectedParentCategory, (newVal, oldVal) => {
  if (newVal === null || newVal === '') {
    categories.value = []
    form.value.categories = []
  }
})

const formLoading = ref(false);
onMounted(async () => {
  formLoading.value = true;
  await fetchParentCategories()
  await fetchTags()
  await fetchUoms()
  await fetchBrands()
  await fetchCities()
  await fetchProduct()
  formLoading.value = false;
})

</script>

<template>
  <div v-if="formLoading" class="d-flex justify-center align-center" style="block-size: 400px;">
    <VProgressCircular indeterminate size="64" />
  </div>
  <template v-else>
    <div>
      <div
        class="d-flex flex-wrap justify-start justify-sm-space-between gap-y-4 gap-x-6 mb-6"
      >
        <div class="d-flex flex-column justify-center">
          <h4 class="text-h4 font-weight-medium">Update product</h4>
        </div>
  
        <div class="d-flex gap-4 align-center flex-wrap">
          <VBtn
            :to="{ name: 'crm-product-list' }"
            variant="tonal"
            color="secondary"
          >
            Discard 
          </VBtn>
          <VBtn
            :loading="updatingProduct"
            @click="updateProduct">Update Product
          </VBtn>
        </div>
      </div>
  
      <VRow>
        <VCol md="8">
          <!-- 👉 Product Information -->
          <VCard class="mb-6" title="Product Information">
            <VCardText>
              <VRow>
                <VCol cols="12" md="6">
                  <AppTextField label="SKU" v-model="form.sku" readonly/>
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField label="Slug" v-model="form.wc_slug" readonly/>
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField label="Name" v-model="form.name" :rules="[requiredValidator]" />
                </VCol>
                <VCol cols="12">
                  <span class="mb-1">Short Description (optional)</span>
                  <ProductDescriptionEditor
                    v-model="form.short_description"
                    placeholder="Product short description"
                    class="border rounded"
                  />
                </VCol>
                <VCol cols="12">
                  <span class="mb-1">Description (optional)</span>
                  <ProductDescriptionEditor
                    v-model="form.description"
                    placeholder="Product description"
                    class="border rounded"
                  />
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
  
          <!-- 👉 Media -->
          <VCard class="mb-6">
            <VCardItem>
              <template #title> Product Image </template>
            </VCardItem>
  
            <VCardText>
              <DropZone ref="dropZoneRef"
                :existing-images="form.existing_images"
                @removeExistingImage="removeExistingImage"
              />
            </VCardText>
          </VCard>
  
          <!-- 👉 Variants -->
          <VCard title="Variants" class="mb-6">
            <VCardText>
              <div class="d-flex flex-raw align-center justify-space-between">
                <span>Has Variation</span>
                <VSwitch
                  density="compact"
                  v-model="form.has_variation"
                />
              </div>
              <!-- Table of variations -->
              <VTable v-if="form.variations.length">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>SKU</th>
                    <th>Quantity</th>
                    <th>Min</th>
                    <th>Max</th>
                    <th>Price</th>
                    <th>Attrs</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(v, idx) in form.variations" :key="idx">
                    <td>{{ v.name }}</td>
                    <td>{{ v.sku }}</td>
                    <td>{{ v.quantity }}</td>
                    <td>{{ v.min_quantity }}</td>
                    <td>{{ v.max_quantity }}</td>
                    <td>{{ v.price }}</td>
                    <td>
                      <div v-for="opt in v.options" :key="opt.attribute">
                        <span>{{ opt.attribute?.name }}: </span>
                        <span>{{ opt.value?.name }}, </span>
                      </div>
                    </td>
                    <td>

                      <div class="d-flex gap-1">
                        <IconBtn @click="openVariationDialog(v)">
                          <VIcon icon="tabler-edit" />
                        </IconBtn>
                        <IconBtn @click="form.variations.splice(idx, 1)">
                          <VIcon icon="tabler-trash" />
                        </IconBtn>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </VTable>
              <VBtn v-if="form.has_variation" class="mt-6" prepend-icon="tabler-plus" @click="openVariationDialog">
                Add Variation
              </VBtn>
            </VCardText>
          </VCard>

          <div v-if="!form.has_variation">
            <VCard title="City Wise Price" class="mb-6">
              <VCardText>
                
                <!-- Table of city wise prices -->
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
                    <tr v-for="(c, idx) in cities" :key="idx">
                      <td>{{ c.name }}</td>
                      <td>
                        <AppTextField
                          v-model="form.city_wise_prices[c.id]"
                          type="number"
                          placeholder="0"
                        />
                      </td>
                    </tr>
                  </tbody>
                </VTable>
              </VCardText>
            </VCard>
          </div>
  
        </VCol>
  
        <VCol md="4" cols="12">

          <!-- 👉 Organize -->
          <VCard title="Organize" class="mb-6">
            <VCardText>
              <div class="d-flex flex-column gap-y-4">
              
                <AppAutocomplete
                  v-model="form.brand_id"
                  label="Brand"
                  :items="brands"
                  :loading="loadingBrands"
                  @update:search="fetchBrands"
                  clear-icon="tabler-x"
                  autocomplete
                  clearable
                />
                
                <AppAutocomplete
                  v-model="selectedParentCategory"
                  label="Category"
                  :items="parentCategories"
                  :loading="loadingParentCategories"
                  @update:search="fetchParentCategories"
                  clear-icon="tabler-x"
                  autocomplete
                  clearable
                />

                <AppAutocomplete
                  v-model="form.categories"
                  label="Sub Category"
                  :items="categories"
                  :loading="loadingCategories"
                  @update:search="fetchCategories"
                  clear-icon="tabler-x"
                  multiple
                  autocomplete
                  clearable
                />
                
                <AppAutocomplete
                  v-model="form.tags"
                  label="Tag"
                  :items="tags"
                  :loading="loadingTags"
                  @update:search="fetchTags"
                  clear-icon="tabler-x"
                  multiple
                  autocomplete
                  clearable
                />
                
                <AppSelect
                  v-model="form.status"
                  placeholder="Select Status"
                  label="Status"
                  :items="statuses"
                  :rules="[requiredValidator]"
                />
 
                <div class="d-flex flex-raw align-center justify-space-between">
                  <span>In stock</span>
                  <VSwitch
                    density="compact"
                    v-model="form.stock_status"
                  />
                </div>

              </div>
            </VCardText>
          </VCard>

          <!-- 👉 Pricing -->
          <VCard title="Pricing" class="mb-6">
            <VCardText>
              <AppTextField
                v-model="form.price"
                type="number"
                label="Base Price"
                placeholder="0"
                class="mb-6"
              />

              <div class="d-flex flex-raw align-center justify-space-between">
                <span>Ask for Quote</span>
                <VSwitch
                  density="compact"
                  v-model="form.ask_for_quote"
                />
              </div>

            </VCardText>
          </VCard>
  
          <!-- 👉 Inventory -->
          <VCard class="mb-6" title="Inventory">
            <VCardText>
              <VRow>
                <VCol cols="12">
                  <AppTextField
                    v-model="form.quantity"
                    type="number"
                    label="Quantity"
                    placeholder="0"
                  />
                </VCol>
                <VCol cols="12">
                  <AppTextField
                    v-model="form.min_quantity"
                    type="number"
                    label="Min Quantity"
                    placeholder="0"
                  />
                </VCol>
                <VCol cols="12">
                  <AppTextField
                    v-model="form.max_quantity"
                    type="number"
                    label="Max Quantity"
                    placeholder="0"
                  />
                </VCol>
                <VCol cols="12">
                  <AppAutocomplete
                    v-model="form.uom_id"
                    label="Uom"
                    :items="uoms"
                    :loading="loadingUoms"
                    @update:search="fetchUoms"
                    clear-icon="tabler-x"
                    autocomplete
                    clearable
                  />
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
  
        </VCol>
      </VRow>
  
      <!-- 👉 Product Variations Dialog -->
      <ProductVariationDialog
        v-model:show="showVariationDialog"
        v-model:selected-variation="selectedVariation"
        :cities="cities"
        @save="addVariation"
      />
    </div>
  </template>
</template>

<style lang="scss" scoped>
.drop-zone {
  border: 2px dashed rgba(var(--v-theme-on-surface), 0.12);
  border-radius: 6px;
}
</style>

<style lang="scss">
.ProseMirror {
  p {
    margin-block-end: 0;
  }

  padding: 0.5rem;
  outline: none;

  p.is-editor-empty:first-child::before {
    block-size: 0;
    color: #adb5bd;
    content: attr(data-placeholder);
    float: inline-start;
    pointer-events: none;
  }
}
</style>
