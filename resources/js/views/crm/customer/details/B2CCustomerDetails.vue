<script setup>
import CustomerBioPanel from '@/views/crm/customer/CustomerBioPanel.vue'
import CustomerTabAddressAndBilling from '@/views/crm/customer/CustomerTabAddressAndBilling.vue'
import CustomerTabOverview from '@/views/crm/customer/CustomerTabOverview.vue'

const props = defineProps({
  customerId: {
    type: [String, Number],
    required: true,
  },
  customerData: {
    type: [Object],
    required: true,
  }
})

const customerData = computed(() => props.customerData)
const userTab = ref(null)

const tabs = [
  {
    title: 'Overview',
    icon: 'tabler-user',
  },
  {
    title: 'Address & Billing',
    icon: 'tabler-map-pin',
  },
]
</script>

<template>
  <div>
    <!-- 👉 Customer Profile  -->
    <VRow v-if="customerData">
      <VCol
        v-if="customerData"
        cols="12"
        md="5"
        lg="4"
      >
        <CustomerBioPanel :customer-data="customerData" />
      </VCol>
      <VCol
        cols="12"
        md="7"
        lg="8"
      >
        <VTabs
          v-model="userTab"
          class="v-tabs-pill mb-3 disable-tab-transition"
        >
          <VTab
            v-for="tab in tabs"
            :key="tab.title"
          >
            <VIcon
              size="20"
              start
              :icon="tab.icon"
            />
            {{ tab.title }}
          </VTab>
        </VTabs>
        <VWindow
          v-model="userTab"
          class="disable-tab-transition"
          :touch="false"
        >
          <VWindowItem>
            <CustomerTabOverview />
          </VWindowItem>
          <VWindowItem>
            <CustomerTabAddressAndBilling :customer-data="customerData" />
          </VWindowItem>
        </VWindow>
      </VCol>
    </VRow>
    <div v-else class="text-center">
      <VProgressCircular indeterminate size="64" />
    </div>
  </div>
</template>
