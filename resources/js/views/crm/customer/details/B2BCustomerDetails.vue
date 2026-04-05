<script setup>
import { hasPermission } from '@/utils/permission'
import CustomerBioPanel from '@/views/crm/customer/b2b/B2BCustomerBioPanel.vue'
import CustomerDocument from '@/views/crm/customer/b2b/CustomerDocument.vue'
import { onMounted } from 'vue'

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

const accessToken = useCookie('accessToken').value

const isUpdatingCustomerStatus = ref(false)
const updateCompanyStatus = async (id, status) => {
  isUpdatingCustomerStatus.value = true
  try {
    const res = await $api(`/customers/${id}/company/status`, {
      method: 'PATCH',
      body: { status },
      headers: { Authorization: `Bearer ${accessToken}` },
    })
    $toast.success(res?.message || 'Company status updated')
    fetchCustomer()
  } catch (error) {
    let msg = 'Failed to update company status'
    if (error?.response?.status === 422) {
      msg = error.response._data?.message || msg
    }
    $toast.error(msg)
  } finally {
    isUpdatingCustomerStatus.value = false
  }
}
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
        <CustomerDocument :customer-data="customerData" @refresh="fetchCustomer" />
        <div v-if="customerData?.company?.status === 'PENDING'" class="approve-reject-btns">
          <div class="d-flex gap-x-4 float-right">
            <VBtn
              v-if="hasPermission('customer.update')"
              @click="updateCompanyStatus(customerData.id, 'APPROVED')"
              :loading="isUpdatingCustomerStatus"
            >
              Approve Profile
            </VBtn>
            <VBtn
              v-if="hasPermission('customer.update')"
              color="error"
              variant="tonal"
              @click="updateCompanyStatus(customerData.id, 'REJECTED')"
              :loading="isUpdatingCustomerStatus"
            >
              Reject
            </VBtn>
          </div>
        </div>
      </VCol>
    </VRow>
    <div v-else class="text-center">
      <VProgressCircular indeterminate size="64" />
    </div>
  </div>
</template>
