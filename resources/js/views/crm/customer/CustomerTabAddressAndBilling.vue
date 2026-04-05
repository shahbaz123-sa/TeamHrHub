<script setup>

const props = defineProps({
  customerData: {
    type: null,
    required: true,
  },
})

const show = ref([
  true
])

const addressData = (props.customerData?.shipping_addresses ?? []).map((item) => ({
  title: '',
  subtitle: item.street_address,
  owner: item.full_name,
  defaultAddress: true,
  email: item.email,
  phone: item.phone_number,
  address: `${item.street_address}<br>${item.city}, ${item.state} ${item.postcode}`
}))

</script>

<template>

  <!-- 👉 Address Book -->
  <VCard class="mb-6">
    <VCardText>
      <div class="d-flex justify-space-between mb-6 flex-wrap align-center gap-y-4 gap-x-6">
        <h5 class="text-h5">
          Address Book
        </h5>
      </div>
      <template
        v-for="(address, index) in addressData"
        :key="index"
      >
        <div>
          <div class="d-flex justify-space-between py-3 gap-y-2 flex-wrap align-center">
            <div class="d-flex align-center gap-x-4">
              <VIcon
                :icon="show[index] ? 'tabler-chevron-down' : 'tabler-chevron-right'"
                class="flip-in-rtl text-high-emphasis"
                size="24"
                @click="show[index] = !show[index]"
              />
              <div>
                <div class="text-body-1">
                  {{ address.subtitle }}
                </div>
              </div>
            </div>
          </div>

          <VExpandTransition>
            <div v-show="show[index]">
              <div class="px-10 pb-3">
                <h6 class="mb-1 text-h6">
                  {{ address.owner }}
                </h6>
                <h6 class="mb-1 text-h6">
                  {{ address.email }}
                </h6>
                <h6 class="mb-1 text-h6">
                  {{ address.phone }}
                </h6>
                <div
                  class="text-body-1"
                  v-html="address.address"
                />
              </div>
            </div>
          </VExpandTransition>

          <VDivider v-if="index !== addressData.length - 1" />
        </div>
      </template>
    </VCardText>
  </VCard>
</template>
