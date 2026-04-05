<template>
  <VBreadcrumbs class="px-0 pb-2 pt-0" :items="[{ title: 'Payroll' }, { title: 'Salary & Compensation' }, { title: 'Employee Salary & Compensation' }]" />
  <section>
    <VRow>
      <!-- Employee Salary Section (Top Left) -->
      <VCol cols="12">
        <VCard class="mb-6">
          <VCardTitle class="d-flex justify-space-between align-center pa-4">
            <span>Employee Salary</span>
              <VBtn
              v-if="shouldShowAddButton"
              icon="tabler-plus"
              size="small"
              color="primary"
              variant="tonal"
              @click="openSalaryModal"
              />
             
          </VCardTitle>
          <VDivider />
          <VCardText>
            <VRow class="align-center mb-4">
              <VCol cols="4">
                <span>Employee Set Salary</span>
              </VCol>
              <VCol cols="5">
                <span>Salary</span>
              </VCol>
               <VCol cols="3">
                <span>Action</span>
              </VCol>
            </VRow>
            <VDivider class="mb-3" />

            <VRow class="align-center">
              <VCol cols="4" class="d-flex align-center">
               
                <span class="text-body-1">Basic Salary</span>
              </VCol>
              <VCol cols="5">
                <span class="text-body-1 font-weight-bold">
                  {{ formatCurrency(currentSalary?.amount || 0) }}
                </span>
              </VCol>
               <VCol cols="3">
                 <VMenu v-if="hasValidSalary && (hasPermission('employee_salary.update') || hasPermission('employee_salary.delete'))">
                  <template #activator="{ props: menuProps }">
                    <VBtn
                      icon="tabler-dots-vertical"
                      size="x-small"
                      variant="text"
                      color="default"
                      v-bind="menuProps"
                      class="me-2"
                    />
                  </template>
                  <VList>
                    <VListItem v-if="hasPermission('employee_salary.update')" @click="editSalary">
                      <template #prepend>
                        <VIcon icon="tabler-edit" />
                      </template>
                      <VListItemTitle>Edit Salary</VListItemTitle>
                    </VListItem>
                    <VListItem v-if="hasPermission('employee_salary.delete')" @click="deleteSalary">
                      <template #prepend>
                        <VIcon icon="tabler-trash" />
                      </template>
                      <VListItemTitle>Delete Salary</VListItemTitle>
                    </VListItem>
                  </VList>
                </VMenu>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>

        <!-- Deduction Section (Bottom Left) -->
<!--        <VCard>-->
<!--          <VCardTitle class="d-flex justify-space-between align-center pa-4">-->
<!--            <span>Deductions</span>-->
<!--            <VBtn-->
<!--              icon="tabler-plus"-->
<!--              size="small"-->
<!--              color="primary"-->
<!--              variant="tonal"-->
<!--              v-if="hasPermission('employee_salary.create')"-->
<!--              @click="addDeduction"-->
<!--            />-->
<!--          </VCardTitle>-->
<!--          <VDivider />-->
<!--          <VCardText>-->
<!--            &lt;!&ndash; Deduction Table Headers &ndash;&gt;-->
<!--            <VRow class="text-caption text-uppercase mb-2">-->
<!--              <VCol cols="2">-->
<!--                <span>Deduction Option</span>-->
<!--              </VCol>-->
<!--              <VCol cols="2">-->
<!--                <span>Title</span>-->
<!--              </VCol>-->
<!--              <VCol cols="2">-->
<!--                <span>Type</span>-->
<!--              </VCol>-->
<!--              <VCol cols="3">-->
<!--                <span>Amount</span>-->
<!--              </VCol>-->
<!--              <VCol cols="3">-->
<!--                <span>Action</span>-->
<!--              </VCol>-->
<!--            </VRow>-->
<!--            <VDivider class="mb-3" />-->
<!--            &lt;!&ndash; Deduction Data Rows &ndash;&gt;-->
<!--            <template v-if="deductions.length > 0">-->
<!--              <VRow -->
<!--                v-for="deduction in deductions" -->
<!--                :key="deduction.id" -->
<!--                class="align-center mb-2"-->
<!--              >-->
<!--                <VCol cols="2">-->
<!--                  <span class="text-body-2">{{ deduction.deduction?.name || 'N/A' }}</span>-->
<!--                </VCol>-->
<!--                <VCol cols="2">-->
<!--                  <span class="text-body-2">{{ deduction.title || 'N/A' }}</span>-->
<!--                </VCol>-->
<!--                <VCol cols="2">-->
<!--                  <span class="text-body-2">{{ getAllowanceTypeLabel(deduction.type) }}</span>-->
<!--                </VCol>-->
<!--                <VCol cols="3">-->
<!--                  <span class="text-body-2">{{ formatCurrency(deduction.amount) }}</span>-->
<!--                </VCol>-->
<!--                <VCol cols="3">-->
<!--                  <VMenu v-if="hasPermission('employee_salary.update') || hasPermission('employee_salary.delete')">-->
<!--                    <template #activator="{ props: menuProps }">-->
<!--                      <VBtn-->
<!--                        v-bind="menuProps"-->
<!--                        icon="tabler-dots-vertical"-->
<!--                        size="small"-->
<!--                        variant="text"-->
<!--                        color="default"-->
<!--                      />-->
<!--                    </template>-->
<!--                    <VList>-->
<!--                      <VListItem v-if="hasPermission('employee_salary.update')" @click="editDeduction(deduction)">-->
<!--                        <template #prepend>-->
<!--                          <VIcon icon="tabler-edit" />-->
<!--                        </template>-->
<!--                        <VListItemTitle>Edit Deduction</VListItemTitle>-->
<!--                      </VListItem>-->
<!--                      <VListItem v-if="hasPermission('employee_salary.delete')" @click="deleteDeduction(deduction.id)">-->
<!--                        <template #prepend>-->
<!--                          <VIcon icon="tabler-trash" />-->
<!--                        </template>-->
<!--                        <VListItemTitle>Delete Deduction</VListItemTitle>-->
<!--                      </VListItem>-->
<!--                    </VList>-->
<!--                  </VMenu>-->
<!--                </VCol>-->
<!--              </VRow>-->
<!--            </template>-->
<!--            <template v-else>-->
<!--              <VRow class="align-center">-->
<!--                <VCol cols="12" class="text-center py-4">-->
<!--                  <span class="text-body-2 text-medium-emphasis">No deductions found</span>-->
<!--                </VCol>-->
<!--              </VRow>-->
<!--            </template>-->
<!--          </VCardText>-->
<!--        </VCard>-->
      </VCol>

      <!-- Right Column -->
      <VCol cols="12">
        <!-- Allowance Section (Top Right) -->
        <VCard class="mb-6">
          <VCardTitle class="d-flex justify-space-between align-center pa-4">
            <span>Allowance</span>
            <VBtn
              icon="tabler-plus"
              size="small"
              color="primary"
              variant="tonal"
              v-if="hasPermission('employee_salary.create')"
              @click="addAllowanceTop"
            />
          </VCardTitle>
          <VDivider />
          <VCardText>
            <!-- Allowance Table Headers -->
            <VRow class="text-caption text-uppercase mb-2">
              <VCol cols="2">
                <span>Allowance Option</span>
              </VCol>
              <VCol cols="2">
                <span>Title</span>
              </VCol>
              <VCol cols="2">
                <span>Type</span>
              </VCol>
              <VCol cols="3">
                <span>Amount</span>
              </VCol>
              <VCol cols="3">
                <span>Action</span>
              </VCol>
            </VRow>
            <VDivider class="mb-3" />
            <!-- Allowance Data Rows -->
            <template v-if="allowances.length > 0">
              <VRow 
                v-for="allowance in allowances" 
                :key="allowance.id" 
                class="align-center mb-2"
              >
                <VCol cols="2">
                  <span class="text-body-2">{{ allowance.allowance?.name || 'N/A' }}</span>
                </VCol>
                <VCol cols="2">
                  <span class="text-body-2">{{ allowance.title || 'N/A' }}</span>
                </VCol>
                <VCol cols="2">
                  <span class="text-body-2">{{ getAllowanceTypeLabel(allowance.type) }}</span>
                </VCol>
                <VCol cols="3">
                  <span class="text-body-2">{{ formatCurrency(allowance.amount) }}</span>
                </VCol>
                <VCol cols="3">
                  <VMenu v-if="hasPermission('employee_salary.update') || hasPermission('employee_salary.delete')">
                    <template #activator="{ props: menuProps }">
                      <VBtn
                        v-bind="menuProps"
                        icon="tabler-dots-vertical"
                        size="small"
                        variant="text"
                        color="default"
                      />
                    </template>
                    <VList>
                      <VListItem v-if="hasPermission('employee_salary.update')" @click="editAllowance(allowance)">
                        <template #prepend>
                          <VIcon icon="tabler-edit" />
                        </template>
                        <VListItemTitle>Edit Allowance</VListItemTitle>
                      </VListItem>
                      <VListItem v-if="hasPermission('employee_salary.delete')" @click="deleteAllowance(allowance.id)">
                        <template #prepend>
                          <VIcon icon="tabler-trash" />
                        </template>
                        <VListItemTitle>Delete Allowance</VListItemTitle>
                      </VListItem>
                    </VList>
                  </VMenu>
                </VCol>
              </VRow>
            </template>
            <template v-else>
              <VRow class="align-center">
                <VCol cols="12" class="text-center py-4">
                  <span class="text-body-2 text-medium-emphasis">No allowances found</span>
                </VCol>
              </VRow>
            </template>
          </VCardText>
        </VCard>

        <!-- Loan Section (Bottom Right) -->
<!--        <VCard>-->
<!--          <VCardTitle class="d-flex justify-space-between align-center pa-4">-->
<!--            <span>Loan</span>-->
<!--            <VBtn-->
<!--              icon="tabler-plus"-->
<!--              size="small"-->
<!--              color="primary"-->
<!--              variant="tonal"-->
<!--              v-if="hasPermission('employee_salary.create')"-->
<!--              @click="addLoan"-->
<!--            />-->
<!--          </VCardTitle>-->
<!--          <VDivider />-->
<!--          <VCardText>-->
<!--            &lt;!&ndash; Loan Table Headers &ndash;&gt;-->
<!--            <VRow class="text-caption text-uppercase mb-2">-->
<!--              <VCol cols="3">-->
<!--                <span>Loan Option</span>-->
<!--              </VCol>-->
<!--              <VCol cols="3">-->
<!--                <span>Title</span>-->
<!--              </VCol>-->
<!--              <VCol cols="3">-->
<!--                <span>Amount</span>-->
<!--              </VCol>-->
<!--              <VCol cols="3">-->
<!--                <span>Action</span>-->
<!--              </VCol>-->
<!--            </VRow>-->
<!--            <VDivider class="mb-3" />-->
<!--            &lt;!&ndash; Loan Data Rows &ndash;&gt;-->
<!--            <template v-if="loans.length > 0">-->
<!--              <VRow -->
<!--                v-for="loan in loans" -->
<!--                :key="loan.id" -->
<!--                class="align-center mb-2"-->
<!--              >-->
<!--                <VCol cols="3">-->
<!--                  <span class="text-body-2">{{ loan.loan?.name || 'N/A' }}</span>-->
<!--                </VCol>-->
<!--                <VCol cols="3">-->
<!--                  <span class="text-body-2">{{ loan.title || 'N/A' }}</span>-->
<!--                </VCol>-->
<!--                <VCol cols="3">-->
<!--                  <span class="text-body-2">{{ formatCurrency(loan.amount) }}</span>-->
<!--                </VCol>-->
<!--                <VCol cols="3">-->
<!--                  <VMenu v-if="hasPermission('employee_salary.update') || hasPermission('employee_salary.delete')">-->
<!--                    <template #activator="{ props: menuProps }">-->
<!--                      <VBtn-->
<!--                        v-bind="menuProps"-->
<!--                        icon="tabler-dots-vertical"-->
<!--                        size="small"-->
<!--                        variant="text"-->
<!--                        color="default"-->
<!--                      />-->
<!--                    </template>-->
<!--                    <VList>-->
<!--                      <VListItem v-if="hasPermission('employee_salary.update')" @click="editLoan(loan)">-->
<!--                        <template #prepend>-->
<!--                          <VIcon icon="tabler-edit" />-->
<!--                        </template>-->
<!--                        <VListItemTitle>Edit Loan</VListItemTitle>-->
<!--                      </VListItem>-->
<!--                      <VListItem v-if="hasPermission('employee_salary.delete')" @click="deleteLoan(loan.id)">-->
<!--                        <template #prepend>-->
<!--                          <VIcon icon="tabler-trash" />-->
<!--                        </template>-->
<!--                        <VListItemTitle>Delete Loan</VListItemTitle>-->
<!--                      </VListItem>-->
<!--                    </VList>-->
<!--                  </VMenu>-->
<!--                </VCol>-->
<!--              </VRow>-->
<!--            </template>-->
<!--            <template v-else>-->
<!--              <VRow class="align-center">-->
<!--                <VCol cols="12" class="text-center py-4">-->
<!--                  <span class="text-body-2 text-medium-emphasis">No loans found</span>-->
<!--                </VCol>-->
<!--              </VRow>-->
<!--            </template>-->
<!--          </VCardText>-->
<!--        </VCard>-->
      </VCol>
    </VRow>

    <!-- Salary Modal -->
    <SalaryModal
      v-model="showSalaryModal"
      :employee-id="route.params.id"
      :salary-data="editingSalary"
      @saved="handleSalarySaved"
      @updated="handleSalaryUpdated"
    />

    <!-- Allowance Modal -->
    <AllowanceModal
      v-model="showAllowanceModal"
      :employee-id="route.params.id"
      :allowance-data="editingAllowance"
      @saved="handleAllowanceSaved"
      @updated="handleAllowanceUpdated"
    />

    <!-- Deduction Modal -->
    <DeductionModal
      v-model="showDeductionModal"
      :employee-id="route.params.id"
      :deduction-data="editingDeduction"
      @saved="handleDeductionSaved"
      @updated="handleDeductionUpdated"
    />

    <!-- Loan Modal -->
    <LoanModal
      v-model="showLoanModal"
      :employee-id="route.params.id"
      :loan-data="editingLoan"
      @saved="handleLoanSaved"
      @updated="handleLoanUpdated"
    />
  </section>
</template>

<script setup>
import AllowanceModal from "@/components/hrm/payroll/AllowanceModal.vue";
import DeductionModal from "@/components/hrm/payroll/DeductionModal.vue";
import LoanModal from "@/components/hrm/payroll/LoanModal.vue";
import SalaryModal from "@/components/hrm/payroll/SalaryModal.vue";
import { AllowanceTypeLabels } from "@/constants/hrm/constants";
import { computed, onMounted, ref } from "vue";
import { useRoute } from "vue-router";

const route = useRoute();
const employee = ref({});
const allowances = ref([]);
const deductions = ref([]);
const loans = ref([]);
const currentSalary = ref(null);
const salaryHistory = ref([]);
const loading = ref(false);
const showSalaryModal = ref(false);
const editingSalary = ref(null);
const showAllowanceModal = ref(false);
const editingAllowance = ref(null);
const showDeductionModal = ref(false);
const editingDeduction = ref(null);
const showLoanModal = ref(false);
const editingLoan = ref(null);

// Computed properties
const totalAllowances = computed(() => {
  return allowances.value.reduce((sum, allowance) => sum + (allowance.amount || 0), 0);
});

const totalDeductions = computed(() => {
  return deductions.value.reduce((sum, deduction) => sum + (deduction.amount || 0), 0);
});

const totalLoans = computed(() => {
  return loans.value.reduce((sum, loan) => sum + (loan.amount || 0), 0);
});

const netSalary = computed(() => {
  const basicSalary = currentSalary.value?.amount || employee.value.amount || 0;
  return basicSalary + totalAllowances.value - totalDeductions.value - totalLoans.value;
});

// Computed properties for UI state
const hasValidSalary = computed(() => {
  const result = currentSalary.value && parseFloat(currentSalary.value.amount) > 0;
  // console.log('hasValidSalary computed:', result, 'currentSalary:', currentSalary.value);
  return result;
});

const shouldShowAddButton = computed(() => {
  const result = (!currentSalary.value || !currentSalary.value.amount || parseFloat(currentSalary.value.amount) <= 0) && (hasPermission('employee_salary.create'));
  // console.log('shouldShowAddButton computed:', result, 'currentSalary:', currentSalary.value);
  return result;
});

// Format currency
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-IN').format(amount || 0);
};

// Get allowance type label
const getAllowanceTypeLabel = (type) => {
  return AllowanceTypeLabels[type] || 'N/A';
};

// Get loan status label
const getLoanStatusLabel = (status) => {
  const statusLabels = {
    1: 'Active',
    2: 'Paid Off',
    3: 'Cancelled',
  };
  return statusLabels[status] || 'Unknown';
};

// Fetch comprehensive employee salary data
const fetchEmployeeSalaryData = async () => {
  try {
    loading.value = true;
    
    const result = await $api(`/employees/${route.params.id}/salary-data`);
    console.log('Employee salary data response:', result);
    
    if (result.status === 'success') {
      const salaryData = result.data;
      
      // Set employee data
      employee.value = salaryData.employee;
      
      // Set current salary
      currentSalary.value = salaryData.current_salary;
      
      // Set salary history
      salaryHistory.value = salaryData.salary_history;
      
      // Set allowances, deductions, and loans
      allowances.value = salaryData.allowances;
      deductions.value = salaryData.deductions;
      loans.value = salaryData.loans;
      
      console.log('Employee salary data loaded:', salaryData);
      console.log('Current salary set to:', currentSalary.value);
      console.log('Basic salary value:', currentSalary.value?.basic_salary);
    } else {
      console.error('API returned error:', result);
    }
  } catch (error) {
    console.error("Failed to fetch employee salary data:", error);
  } finally {
    loading.value = false;
  }
};

// This method is now replaced by fetchEmployeeSalaryData

// Methods for employee salary
const openSalaryModal = () => {
  // Check if salary already exists
  if (currentSalary.value && parseFloat(currentSalary.value.amount) > 0) {
    $toast.warning('Salary already exists for this employee. Please edit the existing salary instead.');
    return;
  }
  
  editingSalary.value = null;
  showSalaryModal.value = true;
};

const editSalary = () => {
  editingSalary.value = currentSalary.value;
  showSalaryModal.value = true;
};

const deleteSalary = async () => {
  if (!currentSalary.value) return;
  
  try {
    loading.value = true;
    await $api(`/salaries/${currentSalary.value.id}`, {
      method: 'DELETE',
    });
    
    await fetchEmployeeSalaryData();
    $toast.success('Salary deleted successfully');
  } catch (error) {
    console.error('Failed to delete salary:', error);
    $toast.error('Failed to delete salary');
  } finally {
    loading.value = false;
  }
};

const handleSalarySaved = async (salaryData) => {
  await fetchEmployeeSalaryData();
};

const handleSalaryUpdated = async (salaryData) => {
  await fetchEmployeeSalaryData();
};

const handleAllowanceSaved = async (allowanceData) => {
  await fetchEmployeeSalaryData();
};

const handleAllowanceUpdated = async (allowanceData) => {
  await fetchEmployeeSalaryData();
};

// Methods for allowances
const addAllowance = () => {
  editingAllowance.value = null;
  showAllowanceModal.value = true;
};

const addAllowanceTop = () => {
  editingAllowance.value = null;
  showAllowanceModal.value = true;
};

const editAllowance = (allowance) => {
  editingAllowance.value = allowance;
  showAllowanceModal.value = true;
};

const deleteAllowance = async (id) => {
  try {
    await $api(`/employee-allowances/${id}`, {
      method: 'DELETE',
    });
    
    await fetchEmployeeSalaryData();
    $toast.success('Allowance deleted successfully');
  } catch (error) {
    console.error('Failed to delete allowance:', error);
    $toast.error('Failed to delete allowance');
  }
};

// Methods for deductions
const addDeduction = () => {
  editingDeduction.value = null;
  showDeductionModal.value = true;
};

const editDeduction = (deduction) => {
  editingDeduction.value = deduction;
  showDeductionModal.value = true;
};

const deleteDeduction = async (id) => {
  try {
    await $api(`/employee-deductions/${id}`, {
      method: 'DELETE',
    });
    
    await fetchEmployeeSalaryData();
    $toast.success('Deduction deleted successfully');
  } catch (error) {
    console.error('Failed to delete deduction:', error);
    $toast.error('Failed to delete deduction');
  }
};

const handleDeductionSaved = async () => {
  await fetchEmployeeSalaryData();
};

const handleDeductionUpdated = async () => {
  await fetchEmployeeSalaryData();
};

// Methods for loans
const addLoan = () => {
  editingLoan.value = null;
  showLoanModal.value = true;
};

const editLoan = (loan) => {
  editingLoan.value = loan;
  showLoanModal.value = true;
};

const deleteLoan = async (id) => {
  try {
    await $api(`/employee-loans/${id}`, {
      method: 'DELETE',
    });
    
    await fetchEmployeeSalaryData();
    $toast.success('Loan deleted successfully');
  } catch (error) {
    console.error('Failed to delete loan:', error);
    $toast.error('Failed to delete loan');
  }
};

const handleLoanSaved = async () => {
  await fetchEmployeeSalaryData();
};

const handleLoanUpdated = async () => {
  await fetchEmployeeSalaryData();
};

onMounted(() => {
  console.log('Set Salary component mounted for employee ID:', route.params.id);
  fetchEmployeeSalaryData();
});
</script>

<style scoped>
.text-caption {
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
}

.border-e {
  border-inline-end:
    1px solid
    rgba(var(--v-border-color), var(--v-border-opacity));
}

@media (max-width: 959px) {
  .border-e {
    border-block-end:
      1px solid
      rgba(var(--v-border-color), var(--v-border-opacity));
    border-inline-end: none;
    margin-block-end: 1.5rem;
    padding-block-end: 1.5rem;
  }
}
</style>
