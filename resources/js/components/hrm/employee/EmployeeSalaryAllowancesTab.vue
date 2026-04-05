<template>
  <div>
    <VAlert
      v-if="!employeeId"
      type="info"
      variant="tonal"
      density="comfortable"
    >
      Save the employee first to manage salary, tax and allowances.
    </VAlert>

    <div v-else>
      <VRow>
        <VCol cols="12" md="6">
          <VCard>
            <VCardTitle class="d-flex justify-space-between align-center">
              <div>
<!--                <div class="text-subtitle-2 text-medium-emphasis">Current Salary</div>-->
<!--                <div class="text-h6">{{ formatMoney(currentSalary?.amount || 0) }}</div>-->
              </div>
              <VBtn
                v-if="!readonly"
                color="primary"
                size="small"
                @click="openSalaryModal"
              >
                {{ currentSalary ? 'Edit Salary' : 'Add Salary' }}
              </VBtn>
            </VCardTitle>
            <VCardText>
              <div class="d-flex flex-column gap-2">
                <div class="d-flex justify-space-between">
                  <span class="text-h6 text-medium-emphasis">Current Salary</span>
                  <span class="text-h6">{{ formatMoney(currentSalary?.amount || 0) }}</span>
                </div>
                <div class="d-flex justify-space-between">
                  <span class="text-medium-emphasis">Payslip Type</span>
                  <span>{{ currentSalary?.payslip_type?.name || '—' }}</span>
                </div>
                <div class="d-flex justify-space-between">
                  <span class="text-medium-emphasis">Effective Date</span>
                  <span>{{ currentSalary?.effective_date || '—' }}</span>
                </div>
                <div class="d-flex justify-space-between">
                  <span class="text-medium-emphasis">Apply Tax</span>
                  <span>{{ currentSalary?.is_tax_applicable ? 'Yes' : 'No' }}</span>
                </div>
                <div class="d-flex justify-space-between">
                  <span class="text-medium-emphasis">Tax Amount</span>
                  <span>{{ formatMoney(currentSalary?.tax_amount || 0) }}</span>
                </div>
                <VDivider class="my-2" />
                <div class="d-flex justify-space-between font-weight-medium">
                  <span>Net Salary</span>
                  <span>{{ formatMoney(netSalary) }}</span>
                </div>
              </div>
            </VCardText>
          </VCard>
        </VCol>
        <VCol cols="12" md="6">
          <VCard>
            <VCardTitle class="d-flex justify-space-between align-center">
              <div class="text-h6">Allowances</div>
              <VBtn
                v-if="!readonly"
                color="secondary"
                variant="tonal"
                size="small"
                @click="openAllowanceModal()"
              >
                Add Allowance
              </VBtn>
            </VCardTitle>
            <VCardText>
              <VTable v-if="allowances.length" density="compact">
                <thead>
                  <tr>
                    <th>Title</th>
                    <th class="text-right">Amount</th>
                    <th v-if="!readonly" class="text-right">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in allowances" :key="item.id">
                    <td>{{ item.title || item.allowance?.name }}</td>
                    <td class="text-right">{{ formatMoney(item.amount) }}</td>
                    <td v-if="!readonly" class="text-right">
                      <VBtn icon="tabler-edit" variant="text" density="comfortable" @click="openAllowanceModal(item)" />
                      <VBtn icon="tabler-trash" color="error" variant="text" density="comfortable" @click="deleteAllowance(item.id)" />
                    </td>
                  </tr>
                </tbody>
              </VTable>
              <VAlert
                v-else
                type="info"
                variant="tonal"
                density="comfortable"
              >
                No allowances added yet.
              </VAlert>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <VRow class="mt-4">
        <VCol cols="12">
          <VCard>
            <VCardTitle class="text-h6">Salary History</VCardTitle>
            <VCardText>
              <VTimeline truncate-line="start" side="end">
                <VTimelineItem
                  v-for="entry in salaryHistory"
                  :key="entry.id"
                  dot-color="primary"
                  size="small"
                >
                  <div class="d-flex justify-space-between align-center">
                    <div>
                      <div class="font-weight-medium">{{ entry.action }}</div>
                      <div class="text-medium-emphasis text-caption">
                        {{ entry.created_at }} • {{ entry.performed_by_user?.name || 'System' }}
                      </div>
                    </div>
                    <div class="text-right">
                      <div>{{ formatMoney(entry.amount) }}</div>
                      <div class="text-caption text-medium-emphasis">Tax: {{ formatMoney(entry.tax_amount) }}</div>
                    </div>
                  </div>
                </VTimelineItem>
              </VTimeline>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>
    </div>

    <SalaryModal
      v-model="showSalaryModal"
      :employee-id="employeeId"
      :salary-data="currentSalary"
      @saved="refreshData"
      @updated="refreshData"
    />

    <AllowanceModal
      v-model="showAllowanceModal"
      :employee-id="employeeId"
      :allowance-data="editingAllowance"
      @saved="refreshData"
      @updated="refreshData"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import SalaryModal from '@/components/hrm/payroll/SalaryModal.vue'
import AllowanceModal from '@/components/hrm/payroll/AllowanceModal.vue'

const props = defineProps({
  employeeId: {
    type: [String, Number],
    default: null,
  },
  readonly: {
    type: Boolean,
    default: false,
  },
})

const currentSalary = ref(null)
const allowances = ref([])
const salaryHistory = ref([])
const loading = ref(false)

const showSalaryModal = ref(false)
const showAllowanceModal = ref(false)
const editingAllowance = ref(null)

const netSalary = computed(() => {
  const amount = parseFloat(currentSalary.value?.amount || 0)
  const tax = currentSalary.value?.is_tax_applicable ? parseFloat(currentSalary.value?.tax_amount || 0) : 0
  const allowanceTotal = allowances.value.reduce((sum, item) => sum + Number(item.amount || 0), 0)
  return amount - tax + allowanceTotal
})

const formatMoney = (value) => {
  const amount = Number(value || 0)
  return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount)
}

const fetchSalaryData = async () => {
  if (!props.employeeId) return
  try {
    loading.value = true
    const result = await $api(`/employees/${props.employeeId}/salary-data`)
    if (result?.status === 'success') {
      currentSalary.value = result.data.current_salary
      allowances.value = result.data.allowances || []
    }
  } catch (error) {
    console.error('Failed to load salary data', error)
  } finally {
    loading.value = false
  }
}

const fetchSalaryHistory = async () => {
  if (!props.employeeId) return
  try {
    const result = await $api(`/employees/${props.employeeId}/salary-history`)
    if (result?.status === 'success') {
      salaryHistory.value = result.data || []
    }
  } catch (error) {
    console.error('Failed to load salary history', error)
  }
}

const refreshData = async () => {
  await fetchSalaryData()
  await fetchSalaryHistory()
}

const openSalaryModal = () => {
  if (props.readonly) return
  showSalaryModal.value = true
}

const openAllowanceModal = (allowance = null) => {
  if (props.readonly) return
  editingAllowance.value = allowance
  showAllowanceModal.value = true
}

const deleteAllowance = async (allowanceId) => {
  if (props.readonly || !allowanceId) return
  try {
    await $api(`/employee-allowances/${allowanceId}`, { method: 'DELETE' })
    $toast.success('Allowance deleted')
    await refreshData()
  } catch (error) {
    console.error('Failed to delete allowance', error)
    $toast.error('Failed to delete allowance')
  }
}

watch(() => props.employeeId, () => {
  refreshData()
})

onMounted(() => {
  refreshData()
})
</script>
