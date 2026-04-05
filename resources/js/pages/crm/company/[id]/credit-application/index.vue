<script setup>
import DocumentImageViewer from '@/components/common/DocumentImageViewer.vue'
import { humanize } from '@/utils/helpers/str'
import { onMounted } from 'vue'

const route = useRoute()
const accessToken = useCookie('accessToken').value

const companyId = ref(null)
const creditApplications = ref([])
const isLoading = ref(false)
const form = ref([])
const showBulkConfirmDialog = ref(false)
const bulkActionType = ref(null) // 'approve' | 'reject'

const resetForm = () => {
  form.value = []
}

const fetchCreditApplications = async () => {
  isLoading.value = true
  try {
    const { data } = await $api(`/credit-applications/company/${route.params.id}`, {
      method: 'GET',
    })
    creditApplications.value = data ?? []

    if(creditApplications.value.length > 0) {
      creditApplications.value.forEach(item => {
        form.value[item.id] = {
          approve_amount: null,
          reject_reason: null,
        }
      })
    }

    companyId.value = route.params.id
  } catch (error) {
    $toast.error('Failed to fetch credit applications')
  } finally {
    isLoading.value = false
  }
}

const customer = computed(() => creditApplications.value[0]?.customer)
const company = computed(() => creditApplications.value[0]?.company)
const pendingCount = computed(() => creditApplications.value.filter(a => a.status === 'PENDING').length)
const approvedCount = computed(() => creditApplications.value.filter(a => a.status === 'APPROVED').length)
const rejectedCount = computed(() => creditApplications.value.filter(a => a.status === 'REJECTED').length)

const totalRequestedAmount = computed(() => {
  return creditApplications.value.reduce((sum, app) => sum + (parseFloat(app.requested_credit_limit) || 0), 0)
})

const totalApprovedAmount = computed(() => {
  return creditApplications.value.reduce((sum, app) => sum + (parseFloat(app.approved_credit_limit) || 0), 0)
})

const totalUsedAmount = computed(() => {
  return creditApplications.value.reduce((sum, app) => sum + (parseFloat(app.used_credit_limit) || 0), 0)
})

const totalPendingAmount = computed(() => {
  return creditApplications.value
    .filter(a => a.status === 'PENDING')
    .reduce((sum, app) => sum + (parseFloat(app.requested_credit_limit) || 0), 0)
})

const resolveStatus = (status) => {
  const statusMap = {
    PENDING: { text: 'Pending', color: 'warning' },
    UNDER_REVIEW: { text: 'Under Review', color: 'warning' },
    APPROVED: { text: 'Approved', color: 'success' },
    REJECTED: { text: 'Rejected', color: 'error' },
  }
  return statusMap[status] || { text: status, color: 'secondary' }
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: '2-digit' })
}

const approveRequest = async (id) => {

  if(!form.value[id]?.approve_amount) {
    $toast.error("Please enter approve ammount");
    return
  }
  
  try {
    await $api(`/credit-applications/${id}/approve`, {
      method: 'PATCH',
      body: {
        approved_credit_limit: form.value[id].approve_amount
      },
      headers: { Authorization: `Bearer ${accessToken}` },
    })

    resetForm()
    $toast.success('Request approved successfully')
    await fetchCreditApplications()
  } catch (error) {

    let message = 'Failed to approve request'

    if (error.status === 422) {
      message = Object.values(error._data?.errors).slice(0, 2).join("\n")
    }

    $toast.error(message)
  }
}

const rejectRequest = async (id) => {

  if(!form.value[id]?.reject_reason) {
    $toast.error("Please enter reject reason");
    return
  }

  try {
    await $api(`/credit-applications/${id}/reject`, {
      method: 'PATCH',
      body: {
        rejection_reason: form.value[id].reject_reason
      },
      headers: { Authorization: `Bearer ${accessToken}` },
    })

    resetForm()    
    $toast.success('Request rejected successfully')
    await fetchCreditApplications()
  } catch (error) {
    
    let message = 'Failed to reject request'

    if (error.status === 422) {
      message = Object.values(error._data?.errors).slice(0, 2).join("\n")
    }

    $toast.error(message)
  }
}

const openBulkConfirm = (action) => {
  bulkActionType.value = action
  showBulkConfirmDialog.value = true
}

const confirmBulkAction = async () => {
  if (!companyId.value || !bulkActionType.value) return

  try {
    if (bulkActionType.value === 'approve') {
      await $api(`/credit-applications/company/${companyId.value}/bulk-approve`, {
        method: 'POST',
        headers: { Authorization: `Bearer ${accessToken}` },
      })
      $toast.success('All pending requests approved')
    } else if (bulkActionType.value === 'reject') {
      await $api(`/credit-applications/company/${companyId.value}/bulk-reject`, {
        method: 'POST',
        headers: { Authorization: `Bearer ${accessToken}` },
      })
      $toast.success('All pending requests rejected')
    }

    showBulkConfirmDialog.value = false
    bulkActionType.value = null
    await fetchCreditApplications()
  } catch (e) {
    showBulkConfirmDialog.value = false
    bulkActionType.value = null
    $toast.error('Failed to perform bulk action')
  }
}

const cancelBulkConfirm = () => {
  showBulkConfirmDialog.value = false
  bulkActionType.value = null
}

onMounted(() => {
  fetchCreditApplications()
})
</script>

<template>
  <div>
    <div v-if="!isLoading && creditApplications.length > 0">
      <div class="mb-6">
        <RouterLink :to="{ name: 'crm-credit-application-list' }" class="text-link text-sm text-muted">
          ← Back to Requests
        </RouterLink>
        <h2>Credit Request Details</h2>
        <div class="text-sm text-muted">
          Review and approve or reject credit requests
        </div>
      </div>
  
      <VRow>
        <!-- Left Panel -->
        <VCol cols="12" md="8">
          <VCard class="mb-6">
            <VCardText>
              <VCardTitle class="mb-5 pl-0">
                Request Information
              </VCardTitle>
              <div class="d-flex align-center mb-4">
                <DocumentImageViewer
                  avatar-size="64"
                  type="avatar"
                  :src="company?.company_image"
                  :default-name="company?.company_name?.slice(0, 2)"
                />
                <div class="flex-grow-1 ml-3">
                  <div class="d-flex justify-space-between align-center">
                    <div>
                      <h5 class="text-h5 font-weight-bold">{{ company?.company_name ?? '' }}</h5>
                      <p class="text-body-2 mb-0">{{ customer?.email ?? '' }}</p>
                    </div>
                    <VChip color="warning" size="small" class="mt-2">
                      {{ pendingCount }} Pending
                    </VChip>
                  </div>
                </div>
              </div>
  
              <VDivider class="my-4" />
  
              <VRow>
                <VCol md="6" class="pb-0">
                  <div>
                    <p class="text-body-2 font-weight-bold mb-1">Phone Number</p>
                    <p class="text-body-2">{{ company?.contact?.phone_number ?? '' }}</p>
                  </div>
                </VCol>
                <VCol md="6" class="pb-0">
                  <div>
                    <p class="text-body-2 font-weight-bold mb-1">Business Type</p>
                    <p class="text-body-2">{{ creditApplications[0]?.business_type ?? '' }}</p>
                  </div>
                </VCol>
                <VCol md="12" class="pt-0">
                  <div>
                    <p class="text-body-2 font-weight-bold mb-1">Address</p>
                    <p class="text-body-2">{{ company?.company_address ?? '' }}</p>
                  </div>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
  
          <!-- Commodity Requests Section -->
          <VCard class="mb-6 ml-2 mr-2">
            <div class="d-flex justify-space-between align-center mt-4">
              <VCardTitle>Commodity Requests</VCardTitle>
              <div class="text-h6 mr-5">{{  creditApplications.length  }} Requests</div>
            </div>
            <VCardText class="pl-5 pr-5">
              <div v-for="(app, idx) in creditApplications" :key="app.id" class="mb-6" :class="idx > 0 ? 'border-t-md pt-4' : ''">
              
                <div class="border-sm rounded-lg pl-4 pr-4 pt-5 pb-5 mb-5">
                  
                  <div class="d-flex align-center mb-3">
                    <div class="flex-grow-1">
                      <h6 class="text-h5">
                        {{ app.category?.name ?? '' }}
                        <VChip :color="resolveStatus(app.status).color" size="small" class="ms-2">
                          {{ resolveStatus(app.status).text }}
                        </VChip>
                      </h6>
                    </div>
                  </div>
  
                  <VRow>
                    <VCol cols="6" lg="6" sm="12">
                      <h6 class="text-h6 mb-1">Details</h6>
                      <VCol cols="12" sm="12" class="pa-0">
                        <p class="text-body-2 text-muted mb-1">Request ID: {{ app.reference_no }}</p>
                      </VCol>
                      <VCol cols="12" sm="12" class="pa-0">
                        <p class="text-body-2 text-muted mb-1">Date Submitted: {{ formatDate(app.date) }}</p>
                      </VCol>
                      <VCol cols="12" sm="12" class="pa-0">
                        <p class="text-body-2 text-muted mb-1">Requested Amount:
                          <span class="text-body-2 font-weight-bold">PKR {{ (app.formatted_req_credit_limit ?? 0).toLocaleString() }}</span>
                        </p>
                      </VCol>
                      <VCol cols="12" sm="12" class="pa-0">
                        <p class="text-body-2 text-muted mb-1">Purpose of Credit:
                          <span class="text-body-2">{{ app.purpose_of_credit ?? '' }}</span>
                        </p>
                      </VCol>
                    </VCol>
                    <VCol cols="6" lg="6" sm="12">
                      <h6 class="text-h6 mb-1">Supporting Documents</h6>
                      <div v-if="app?.documents" v-for="doc in app?.documents" :key="doc.id">
                        <div class="d-flex justify-space-between align-center">
                          <p class="text-body-2 text-muted mb-0">{{ humanize(doc.document_type) }}</p>
                          <DocumentImageViewer pdf-icon="/images/icons/document.png" :src="doc.document_url">
                            <template #content>
                                <p class="text-body-1 mb-0 font-weight-bold text-link text-primary">View</p>
                            </template>
                          </DocumentImageViewer>
                        </div>
                      </div>
                    </VCol>
                  </VRow>
                  
                  <div v-if="app.status === 'PENDING'" class="mt-4">
                    <div class="mb-4">
                      <p class="text-body-2 text-muted mb-1">Approved Amount</p>
                      <VTextField
                        v-model="form[app.id].approve_amount"
                        type="number"
                        :readonly="app.status !== 'PENDING'"
                        density="compact"
                        max-width="50%"
                      >
                        <template #prepend-inner>
                          <span class="text-medium-emphasis">PKR</span>
                        </template>
                      </VTextField>
                    </div>
  
                    <div class="mb-4">
                      <p class="text-body-2 text-muted mb-1">Reject Reason</p>
                      <VTextField
                        v-model="form[app.id].reject_reason"
                        :readonly="app.status !== 'PENDING'"
                        density="compact" />
                    </div>
                  
                    <div class="d-flex gap-2">
                      <VBtn color="primary" variant="elevated" width="45%" @click="approveRequest(app.id)">
                        <VIcon icon="tabler-check" class="me-1" />
                        Approve
                      </VBtn>
                      <VBtn color="primary" variant="outlined" width="45%" @click="rejectRequest(app.id)">
                        <VIcon icon="tabler-x" class="me-1" />
                        Reject
                      </VBtn>
                    </div>
                  </div>

                  <div v-else-if="app.status === 'APPROVED'" class="mt-4">
                    <VAlert color="success" variant="tonal" dense :icon="false" class="border-sm">
                      <VRow>
                        <VCol cols="6">
                          <h6 class="text-h6 text-success">Approved Amount</h6>
                          <h5 class="mt-1 text-h5 text-success">PKR {{ app.formatted_app_credit_limit }}</h5>
                        </VCol>
                        <VCol cols="6">
                          <h6 class="text-h6 text-success">Approved Date</h6>
                          <h5 class="mt-1 text-h5 text-success">{{ app.approved_at }}</h5>
                        </VCol>
                      </VRow>
                    </VAlert>
                  </div>
                  
                  <div v-else-if="app.status === 'REJECTED'" class="mt-4">
                    <VAlert color="error" variant="tonal" dense :icon="false" class="border-sm">
                      <VRow>
                        <VCol cols="12">
                          <h6 class="text-h6 text-error">Rejection Reason</h6>
                          <h5 class="mt-1 text-h5 text-error">{{ app.rejection_reason }}</h5>
                        </VCol>
                      </VRow>
                    </VAlert>
                  </div>

                </div>
              
              </div>
            </VCardText>
          </VCard>
  
        </VCol>
  
        <!-- Right Panel -->
        <VCol cols="12" md="4">
          
  
          <!-- Approval Summary -->
          <VCard class="mb-6">
            <VCardTitle class="pt-7 pl-6">Approval Summary</VCardTitle>
            <VCardText>
              <div class="d-flex align-center mb-4">
                <VAvatar size="36" :rounded="false" variant="tonal" class="rounded-lg">
                  <VIcon icon="tabler-currency-dollar" size="26" />
                </VAvatar>
                <div class="flex-grow-1 ml-3">
                  <p class="text-body-2 text-muted mb-0">Total Requested Amount</p>
                  <h6 class="text-h6">PKR {{ totalRequestedAmount.toLocaleString() }}</h6>
                </div>
              </div>
              <div class="d-flex align-center mb-4">
                <VAvatar size="36" :rounded="false" variant="tonal" color="success" class="rounded-lg">
                  <VIcon icon="tabler-currency-dollar" size="26" />
                </VAvatar>
                <div class="flex-grow-1 ml-3">
                  <p class="text-body-2 text-muted mb-0">Total Approved Amount</p>
                  <h6 class="text-h6">PKR {{ totalApprovedAmount.toLocaleString() }}</h6>
                </div>
              </div>
              <div class="d-flex align-center mb-4">
                <VAvatar size="36" :rounded="false" variant="tonal" color="success" class="rounded-lg">
                  <VIcon icon="tabler-currency-dollar" size="26" />
                </VAvatar>
                <div class="flex-grow-1 ml-3">
                  <p class="text-body-2 text-muted mb-0">Total Used Amount</p>
                  <h6 class="text-h6">PKR {{ totalUsedAmount.toLocaleString() }}</h6>
                </div>
              </div>
              <div class="d-flex align-center">
                <VAvatar size="36" :rounded="false" variant="tonal" color="warning" class="rounded-lg">
                  <VIcon icon="tabler-calendar-time" size="26" />
                </VAvatar>
                <div class="flex-grow-1 ml-3">
                  <p class="text-body-2 text-muted mb-0">Total Requests</p>
                  <h6 class="text-h6">{{ creditApplications.length }} Requests</h6>
                </div>
              </div>
            </VCardText>
          </VCard>

          <VCard class="mb-6">
            <VCardTitle class="pt-7 pl-6">Status Summary</VCardTitle>
            <VCardText>
              <VAlert color="success" variant="tonal" dense :icon="false" class="mb-4">
                <div class="d-flex justify-space-between align-center">
                  <h6 class="text-h6 text-success">Approved</h6>
                  <h6 class="text-h6 text-success">{{ approvedCount }}</h6>
                </div>
              </VAlert>
              <VAlert color="warning" variant="tonal" dense :icon="false" class="mb-4">
                <div class="d-flex justify-space-between align-center">
                  <h6 class="text-h6 text-warning">Pending</h6>
                  <h6 class="text-h6 text-warning">{{ pendingCount }}</h6>
                </div>
              </VAlert>
              <VAlert color="error" variant="tonal" dense :icon="false" class="mb-4">
                <div class="d-flex justify-space-between align-center">
                  <h6 class="text-h6 text-error">Rejected</h6>
                  <h6 class="text-h6 text-error">{{ rejectedCount }}</h6>
                </div>
              </VAlert>
            </VCardText>
          </VCard>
  
          <!-- Bulk Actions -->
          <VCard class="mb-6" v-if="pendingCount > 0">
            <VCardTitle class="pt-7 pl-6">Bulk Actions</VCardTitle>
            <VCardText>
              <div class="d-flex flex-column gap-3">
                <VBtn
                  color="primary"
                  variant="elevated"
                  block
                  @click="openBulkConfirm('approve')"
                >
                  <VIcon icon="tabler-check" class="me-2" />
                  Approve All Pending Requests
                </VBtn>
                <VBtn
                  color="primary"
                  variant="outlined"
                  block
                  @click="openBulkConfirm('reject')"
                >
                  <VIcon icon="tabler-x" class="me-2" />
                  Reject All Pending Requests
                </VBtn>
              </div>
            </VCardText>
          </VCard>

          <!-- Bulk confirmation dialog -->
          <VDialog v-model="showBulkConfirmDialog" persistent max-width="500px">
            <VCard>

              <div class="mt-5 text-center">
                
                <VAvatar size="54" color="primary" variant="tonal">
                  <VIcon :icon="bulkActionType === 'approve' ? 'tabler-check' : 'tabler-x'" size="40" />
                </VAvatar>
                
                <VCardTitle class="text-h5">{{ bulkActionType === 'approve' ? 'Confirm Approval' : 'Confirm Rejection' }}</VCardTitle>
                
                <VCardText class="pb-3">
                  <p class="text-body-2">Are you sure you want to {{ bulkActionType }} all pending<br>commodity requests?</p>
                </VCardText>

                <div class="ml-5 mr-5 mb-3 pa-4 pb-0 rounded-lg" style="background-color: #f8f7fa;">
                  <div class="d-flex justify-space-between align-center">
                    <p class="text-body-2">Total Requests:</p>
                    <p class="text-body-2">{{ pendingCount }} Commodities</p>
                  </div>
                  <div class="d-flex justify-space-between align-center">
                    <p class="text-body-2">Total Amount:</p>
                    <p class="text-body-2 text-primary">PKR {{ totalPendingAmount }}</p>
                  </div>
                </div>

              </div>

              <VCardActions class="justify-start ml-3 mr-4">
                <VBtn color="secondary" width="50%" variant="outlined" @click="cancelBulkConfirm">Cancel</VBtn>
                <VBtn color="primary" width="50%" variant="elevated" @click="confirmBulkAction">Confirm</VBtn>
              </VCardActions>
            </VCard>
          </VDialog>
  
          <!-- Submitted Documents -->
          <VCard>
            <VCardTitle class="pt-7 pl-6 mb-3">Supporting Documents</VCardTitle>
            <VCardText>
              <div v-if="creditApplications.length > 0 && creditApplications[0].documents?.length > 0">
                <div v-for="application in creditApplications" :key="application.id" class="mb-6">
                  <h6 class="text-h6 mb-1">Documents for {{ application.reference_no ?? '' }}</h6>
                  <div v-if="application?.documents" v-for="doc in application?.documents" :key="doc.id" class="mb-4 pa-2 border-sm rounded-lg">
                    <DocumentImageViewer pdf-icon="/images/icons/document.png" :src="doc.document_url">
                      <template #content>
                        <div class="d-flex align-center">
                          <VAvatar size="40" :rounded="false">
                            <VImg src="/images/icons/document.png" :rounded="false" />
                          </VAvatar>
                          <div class="ml-4">
                            <p class="text-body-1 font-weight-bold mb-0">{{ humanize(doc.document_type) }}</p>
                            <p class="text-body-2 mb-0">{{ doc.file_name }}</p>
                            <p class="text-caption mb-0">{{ (doc.file_size / 1024).toFixed(2) }} KB • Uploaded {{ doc.uploaded_at }}</p>
                          </div>
                        </div>
                      </template>
                    </DocumentImageViewer>
                  </div>
                </div>
              </div>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>
    </div>

    <div v-else class="text-center">
      <VProgressCircular indeterminate size="64" />
    </div>
  </div>
</template>
