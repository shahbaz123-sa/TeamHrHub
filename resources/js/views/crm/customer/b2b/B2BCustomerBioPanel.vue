<script setup>
import { watch } from 'vue';


const props = defineProps({
  customerData: {
    type: null,
    required: true,
  },
})

const resolveStatus = statusMsg => {
  if (statusMsg === 'APPROVED')
    return 'success'
  else if (statusMsg === 'PENDING')
    return 'warning'
  else if (statusMsg === 'REJECTED')
    return 'error'
}

const customerData = ref({})

watch(() => props.customerData, (newCustomerData) => {
  if(newCustomerData) {
    customerData.value = {
      id: newCustomerData.company.id,
      fullName: newCustomerData?.company?.company_name ?? "",
      username: props.customerData?.username ?? "",
      phone_number: props.customerData.phone_number ?? "",
      email: props.customerData.email ?? "",
      type: newCustomerData?.company?.company_type ?? "",
      status: newCustomerData.company.status ?? "PENDING",
      industry: newCustomerData?.company?.industry_type ?? "",
      ntn: newCustomerData?.company?.national_tax_number ?? "",
      cnic: newCustomerData?.company?.cnic_number ?? "",
      address: newCustomerData?.company?.company_address ?? "",
      avatar: newCustomerData?.company?.company_image ?? "",
      contact: {
        name: newCustomerData?.company?.contact?.name ?? "",
        designation: newCustomerData?.company?.contact?.designation ?? "",
        email: newCustomerData?.email ?? "",
        phone_number: newCustomerData?.company?.contact?.phone_number ?? "",
      }
    }
  }

}, { deep: true, immediate: true })
</script>

<template>
  <VRow>
    <!-- SECTION Customer Details -->
    <VCol cols="12">
      <VCard v-if="customerData">
        <VCardText class="text-center pt-12">
          <!-- 👉 Avatar -->
          <VAvatar
            rounded
            :size="120"
            :color="!customerData?.fullName ? 'primary' : undefined"
            :variant="!customerData?.avatar ? 'tonal' : undefined"
          >
            <VImg
              v-if="customerData?.avatar"
              :src="customerData?.avatar"
            />
          </VAvatar>

          <!-- 👉 Customer fullName -->
          <h5 class="text-h5 mt-4">
            {{ customerData.fullName }}
          </h5>
        </VCardText>

        <!-- 👉 Customer Details -->
        <VCardText>
          <h5 class="text-h5">
            Details
          </h5>

          <VDivider class="my-4" />

          <VList class="card-list mt-2">
            <VListItem>
              <h6 class="text-h6">
                Company Name:
                <span class="text-body-1 d-inline-block">
                  {{ customerData.fullName }}
                </span>
              </h6>
            </VListItem>

            <VListItem>
              <div class="d-flex gap-x-2 align-center">
                <h6 class="text-h6">
                  Company Type:
                  <span class="text-body-1 d-inline-block">
                  {{ customerData.type }}
                </span>
                </h6>
              </div>
            </VListItem>

            <VListItem>
              <div class="d-flex gap-x-2 align-center">
                <h6 class="text-h6">
                  Status:
                </h6>
                <VChip
                  label
                  :color="resolveStatus(customerData.status)"
                  size="small"
                >
                  {{ customerData.status }}
                </VChip>
              </div>
            </VListItem>

            <VListItem>
              <div class="d-flex gap-x-2 align-center">
                <h6 class="text-h6">
                  Industry:
                  <span class="text-body-1 d-inline-block">
                  {{ customerData.industry }}
                </span>
                </h6>
              </div>
            </VListItem>
            
            <VListItem>
              <div class="d-flex gap-x-2 align-center">
                <h6 class="text-h6">
                  NTN #:
                  <span class="text-body-1 d-inline-block">
                  {{ customerData.ntn }}
                </span>
                </h6>
              </div>
            </VListItem>
            
            <VListItem>
              <div class="d-flex gap-x-2 align-center">
                <h6 class="text-h6">
                  CNIC #:
                  <span class="text-body-1 d-inline-block">
                  {{ customerData.cnic }}
                </span>
                </h6>
              </div>
            </VListItem>

            <VListItem>
              <h6 class="text-h6">
                Address:
                <span class="text-body-1 d-inline-block">
                  {{ customerData.address }}
                </span>
              </h6>
            </VListItem>
          </VList>
        </VCardText>

        <VCardText>
          <h5 class="text-h5">
            User Details
          </h5>
          
          <VList class="card-list mt-5">
            <VListItem>
              <h6 class="text-h6">
                Billing Email:
                <span class="text-body-1 d-inline-block">
                  {{ customerData.email }}
                </span>
              </h6>
            </VListItem>

            <VListItem>
              <h6 class="text-h6">
                Username:
                <span class="text-body-1 d-inline-block">
                  {{ customerData.username }}
                </span>
              </h6>
            </VListItem>

            <VListItem>
              <h6 class="text-h6">
                Contact:
                <span class="text-body-1 d-inline-block">
                  {{ customerData.phone_number }}
                </span>
              </h6>
            </VListItem>

          </VList>
        </VCardText>
        
        <VCardText>
          <h5 class="text-h5">
            Authorized Contact Person
          </h5>

          <VList class="card-list mt-5">
            <VListItem>
              <h6 class="text-h6">
                Name:
                <span class="text-body-1 d-inline-block">
                  {{ customerData.contact?.name }}
                </span>
              </h6>
            </VListItem>

            <VListItem>
              <div class="d-flex gap-x-2 align-center">
                <h6 class="text-h6">
                  Designation:
                  <span class="text-body-1 d-inline-block">
                  {{ customerData.contact?.designation }}
                </span>
                </h6>
              </div>
            </VListItem>
            
            <VListItem>
              <div class="d-flex gap-x-2 align-center">
                <h6 class="text-h6">
                  Email:
                  <span class="text-body-1 d-inline-block">
                  {{ customerData.contact?.email }}
                </span>
                </h6>
              </div>
            </VListItem>
            
            <VListItem>
              <div class="d-flex gap-x-2 align-center">
                <h6 class="text-h6">
                  Contact:
                  <span class="text-body-1 d-inline-block">
                  {{ customerData.contact?.phone_number }}
                </span>
                </h6>
              </div>
            </VListItem>

          </VList>
        </VCardText>
      </VCard>
    </VCol>
    <!-- !SECTION -->

  </VRow>
</template>

<style lang="scss" scoped>
.card-list {
  --v-card-list-gap: 0.5rem;
}

.current-plan {
  background: linear-gradient(45deg, rgb(var(--v-theme-primary)) 0%, #9e95f5 100%);
  color: #fff;
}
</style>
