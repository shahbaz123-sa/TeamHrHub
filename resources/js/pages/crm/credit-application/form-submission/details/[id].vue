<script setup>
import { humanize } from '@/utils/helpers/str'
import { onMounted } from 'vue'

const route = useRoute()
const creditApplicationData = ref(null)
const isLoading = ref(false)

const fetchCreditApplication = async () => {
  isLoading.value = true
  try {
  const { data } = await $api(`/credit-application/form-submissions/${route.params.id}`, {
      method: 'GET',
    })
    creditApplicationData.value = data
  } catch (error) {
    $toast.error('Failed to fetch Credit Application details')
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchCreditApplication()
})
</script>

<template>
  <div>
    <VRow v-if="creditApplicationData">
      <VCol cols="12" md="8" lg="9">
        <VCard>
          <VCardTitle class="mt-5">Credit Application Details</VCardTitle>
          <VCardText class="mt-5">
            <VRow>
              <VCol cols=4>
                <div>
                  <div class="text-body-2 text-muted">Name</div>
                  <h6 class="text-h6 mt-1">{{ creditApplicationData.full_name }}</h6>
                </div>
              </VCol>
              <VCol cols=4>
                <div>
                  <div class="text-body-2 text-muted">Email</div>
                  <h6 class="text-h6 mt-1">{{ creditApplicationData.email }}</h6>
                </div>
              </VCol>
              <VCol cols=4>
                <div>
                  <div class="text-body-2 text-muted">Phone Number</div>
                  <h6 class="text-h6 mt-1">{{ creditApplicationData.phone }}</h6>
                </div>
              </VCol>
            </VRow>
            <VDivider class="mt-7 mb-7" /><VRow>
              <VCol cols=4>
                <div>
                  <div class="text-body-2 text-muted">Industry Type</div>
                  <h6 class="text-h6 mt-1">{{ humanize(creditApplicationData.industry_type) }}</h6>
                </div>
              </VCol>
              <VCol cols=4>
                <div>
                  <div class="text-body-2 text-muted">Company Type</div>
                  <h6 class="text-h6 mt-1">{{ creditApplicationData.company_type }}</h6>
                </div>
              </VCol>
              <VCol cols=4>
                <div>
                  <div class="text-body-2 text-muted">Commodity</div>
                  <h6 class="text-h6 mt-1">{{ creditApplicationData.commodity }}</h6>
                </div>
              </VCol>
            </VRow>
            <VDivider class="mt-7 mb-7" />
            <VRow>
              <VCol cols=4>
                <div>
                  <div class="text-body-2 text-muted">Quantity</div>
                  <h6 class="text-h6 mt-1">{{ creditApplicationData.quantity }}</h6>
                </div>
              </VCol>
              <VCol cols=4>
                <div>
                  <div class="text-body-2 text-muted">Credit Term Duration</div>
                  <h6 class="text-h6 mt-1">{{ creditApplicationData.credit_term }}</h6>
                </div>
              </VCol>
              <VCol cols=4>
                <div>
                  <div class="text-body-2 text-muted">Message</div>
                  <h6 class="text-h6 mt-1">{{ creditApplicationData.message }}</h6>
                </div>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" md="4" lg="3"></VCol>
    </VRow>
    <div v-else class="text-center">
      <VProgressCircular indeterminate size="64" />
    </div>
  </div>
</template>
