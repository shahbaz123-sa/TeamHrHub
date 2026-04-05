<script setup>
import B2BCustomerDetails from '@/views/crm/customer/details/B2BCustomerDetails.vue'
import B2CCustomerDetails from '@/views/crm/customer/details/B2CCustomerDetails.vue'

const route = useRoute('apps-ecommerce-customer-details-id')

const customerData = ref()
const fetchCustomer = async () => {
  try {
    const { data } = await $api(`/customers/${route.params.id}`, {
      method: 'GET',
    });

    customerData.value = {...data};

  } catch (error) {
    $toast.error("Failed to fetch customer details");
  }
};

onMounted(() => {
  fetchCustomer() 
})

</script>

<template>
  <div>

    <!-- 👉 B2C Customer Details -->
    <B2CCustomerDetails
      v-if="customerData?.type === 'B2C'"
      :customer-id="route.params.id"
      :customer-data="customerData"
    />

    <!-- 👉 B2B Customer Details -->
    <B2BCustomerDetails
      v-if="customerData?.type === 'B2B'"
      :customer-id="route.params.id"
      :customer-data="customerData"
    />
  </div>
</template>
