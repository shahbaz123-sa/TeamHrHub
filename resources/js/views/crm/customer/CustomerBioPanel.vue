<script setup>

const props = defineProps({
  customerData: {
    type: null,
    required: true,
  },
})

const customerData = {
  id: props.customerData.id,
  fullName: props.customerData?.profile?.full_name ?? "",
  username: props.customerData?.username ?? "",
  nationality: props.customerData?.profile?.nationality ?? "",
  contact: props.customerData.phone_number ?? "",
  email: props.customerData.email ?? "",
  status: props.customerData.status ?? "PENDING",
  type: props.customerData.type ?? "",
  avatar: props.customerData?.profile?.profile_image ?? "",
}
</script>

<template>
  <VRow>
    <!-- SECTION Customer Details -->
    <VCol cols="12">
      <VCard v-if="props.customerData">
        <VCardText class="text-center pt-12">
          <!-- 👉 Avatar -->
          <VAvatar
            rounded
            :size="120"
            :color="!customerData.username ? 'primary' : undefined"
            :variant="!customerData.avatar ? 'tonal' : undefined"
          >
            <VImg
              v-if="customerData.avatar"
              :src="customerData.avatar"
            />
          </VAvatar>

          <!-- 👉 Customer fullName -->
          <h5 class="text-h5 mt-4">
            {{ customerData.fullName }}
          </h5>

          <div class="d-flex justify-space-evenly gap-x-5 mt-6">
            <div class="d-flex align-center">
              <VAvatar
                variant="tonal"
                color="primary"
                rounded
                class="me-4"
              >
                <VIcon icon="tabler-shopping-cart" />
              </VAvatar>
              <div class="d-flex flex-column align-start">
                <h5 class="text-h5">
                  {{ props.customerData?.total_orders ?? 0 }}
                </h5>
                <div class="text-body-1">
                  Order
                </div>
              </div>
            </div>
            <div class="d-flex align-center">
              <VAvatar
                variant="tonal"
                color="primary"
                rounded
                class="me-3"
              >
                <VIcon icon="tabler-cash" />
              </VAvatar>
              <div class="d-flex flex-column align-start">
                <h5 class="text-h5">
                  PKR {{ props.customerData?.total_orders_amount ?? 0 }}
                </h5>
                <div class="text-body-1">
                  Spent
                </div>
              </div>
            </div>
          </div>
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
                Username:
                <span class="text-body-1 d-inline-block">
                  {{ customerData.username }}
                </span>
              </h6>
            </VListItem>

            <VListItem>
              <h6 class="text-h6">
                Billing Email:
                <span class="text-body-1 d-inline-block">
                  {{ customerData.email }}
                </span>
              </h6>
            </VListItem>

            <VListItem>
              <div class="d-flex gap-x-2 align-center">
                <h6 class="text-h6">
                  Status:
                </h6>
                <VChip
                  label
                  :color="customerData.status === 'COMPLETED' ? 'success' : 'warning'"
                  size="small"
                >
                  {{ customerData.status }}
                </VChip>
              </div>
            </VListItem>
            
            <VListItem>
              <div class="d-flex gap-x-2 align-center">
                <h6 class="text-h6">
                  Type:
                  <span class="text-body-1 d-inline-block">
                  {{ customerData.type }}
                </span>
                </h6>
              </div>
            </VListItem>

            <VListItem>
              <h6 class="text-h6">
                Contact:
                <span class="text-body-1 d-inline-block">
                  {{ customerData.contact }}
                </span>
              </h6>
            </VListItem>

            <VListItem>
              <h6 class="text-h6">
                Nationality:
                <span class="text-body-1 d-inline-block">
                  {{ customerData.nationality }}
                </span>
              </h6>
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
