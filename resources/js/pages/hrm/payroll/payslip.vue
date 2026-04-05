<template>
  <section>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0 help-center-breadcrumbs"
      :items="[{ title: 'Payroll' }, { title: 'Payslip' }]"
    />
    <VCard>
      <h3 class="mt-5 ml-6">{{ getCardTitle() }}</h3>
      <VDivider class="mt-3 ml-6" />
      <VCardText>
        <VRow class="align-center">
          <!-- Month/Year Selection and Generate Button Section -->
          <VCol cols="12" lg="6" xl="5">
            <div class="d-flex gap-3 flex-wrap align-center">
              <AppSelect
                v-model="selectedMonth"
                :items="monthOptions"
                placeholder="Month"
                class="month-select"
                @update:model-value="onMonthYearChange"
              />
              <AppSelect
                v-model="selectedYear"
                :items="yearOptions"
                placeholder="Year"
                class="year-select"
                @update:model-value="onMonthYearChange"
              />
              <VBtn
                color="primary"
                :disabled="!selectedMonth || !selectedYear || isGenerating"
                :loading="isGenerating"
                @click="generatePayrolls"
                class="generate-btn"
              >
                <VIcon start icon="tabler-plus" />
                <span class="d-none d-sm-inline">Generate Payslips</span>
                <span class="d-sm-none">Generate</span>
              </VBtn>
            </div>
          </VCol>

          <!-- Spacer for desktop -->
          <VCol class="d-none d-lg-block" />

          <!-- Search and Controls Section -->
          <VCol cols="12" lg="6" xl="5">
            <div class="d-flex gap-3 flex-wrap align-center justify-lg-end">
              <AppTextField
                v-model="searchQuery"
                placeholder="Search Employee"
                prepend-inner-icon="tabler-search"
                class="search-field"
                clearable
                @input="onSearchChange"
                @keyup.enter="manualFetch"
              />
              <div class="d-flex align-center gap-2">
                <VLabel class="me-1 text-body-2 d-none d-sm-inline flex-shrink-0">
                  Show:
                </VLabel>
                <AppSelect
                  v-model="itemsPerPage"
                  :items="[
                    { value: 5, title: '5' },
                    { value: 10, title: '10' },
                    { value: 20, title: '20' },
                    { value: 50, title: '50' },
                    { value: -1, title: 'All' },
                  ]"
                  class="items-per-page-select"
                />
                <VBtn
                  :icon="allowFetch ? 'tabler-refresh' : 'tabler-lock'"
                  :variant="allowFetch ? 'outlined' : 'tonal'"
                  :color="allowFetch ? 'primary' : 'warning'"
                  size="small"
                  :disabled="!selectedMonth || isFetching"
                  @click="manualFetch"
                  :title="allowFetch ? 'Refresh Data' : 'Fetch Disabled - Click to Enable'"
                />
              </div>
            </div>
          </VCol>
        </VRow>
      </VCardText>

      <VDivider class="mt-4" />

      <!-- No Month Selected Message -->
      <div v-if="!selectedMonth" class="text-center pa-8">
        <VIcon icon="tabler-calendar" size="64" color="info" class="mb-4" />
        <h3 class="text-h5 mb-2">Select a Month</h3>
        <p class="text-body-1 text-medium-emphasis">
          Please select a month from the dropdown above to view or generate payrolls.
        </p>
      </div>

      <!-- No Payroll Message -->
      <div v-if="selectedMonth && !hasPayrollForMonth && !loading" class="text-center pa-8">
        <VIcon icon="tabler-calendar-x" size="64" color="warning" class="mb-4" />
        <h3 class="text-h5 mb-2">No Payroll Generated</h3>
        <p class="text-body-1 text-medium-emphasis mb-4">
          No payroll has been generated for <strong>{{ selectedMonth }}</strong> yet.
        </p>
        <p class="text-body-2 text-medium-emphasis">
          Click the "Generate Payslips" button above to create payrolls for all employees.
        </p>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="text-center pa-8">
        <VProgressCircular indeterminate color="primary" size="64" class="mb-4" />
        <p class="text-body-1">Loading payroll data...</p>
      </div>

      <!-- Payslip Table - Only show when payroll exists for selected month -->
      <VDataTableServer
        v-if="selectedMonth && hasPayrollForMonth && !loading"
        v-model:items-per-page="itemsPerPage"
        v-model:model-value="selectedRows"
        v-model:page="page"
        :headers="headers"
        :items="payslipData"
        :items-length="totalEmployees"
        :loading="loading"
        loading-text="Loading data..."
        class="text-no-wrap"
        @update:options="updateOptions"
      >

        <!-- Employee ID -->
      
      <template #item.employee="{ item }">
          <div class="d-flex align-center gap-x-4">
            <VAvatar size="34" variant="tonal" :color="'primary'">
              <span>{{ item.employee?.name.charAt(0) }}</span>
            </VAvatar>
            <div class="d-flex flex-column">
              <h6 class="text-base">
               
                {{ item.employee?.name }}
                <!-- </RouterLink> -->
              </h6>
              <div class="text-sm">
                {{ item.employee?.official_email || item.employee?.personal_email }}
              </div>
            </div>
          </div>
        </template>

        <!-- Employee Name -->
        <template #item.employee_code="{ item }">
                <div class="text-sm">
                {{ item.employee?.employee_code || item.employee_code }}
                </div>          
         </template>
        

        <!-- Salary -->
        <template #item.salary="{ item }">
          <div class="text-high-emphasis text-body-1">
            Rs {{ formatCurrency(item.basic_salary || item.salary) }}
          </div>
        </template>

        <!-- Total Allowances -->
        <template #item.total_allowances="{ item }">
          <div class="text-success text-body-1">
            Rs {{ formatCurrency(item.total_allowances) }}
          </div>
        </template>

        <!-- Total Deductions -->
        <template #item.total_deductions="{ item }">
          <div class="text-error text-body-1">
            Rs {{ formatCurrency(item.total_deductions) }}
          </div>
        </template>

        <!-- Total Loans -->
        <template #item.total_loans="{ item }">
          <div class="text-warning text-body-1">
            Rs {{ formatCurrency(item.total_loans) }}
          </div>
        </template>

        <!-- Net Salary -->
        <template #item.net_salary="{ item }">
          <div class="text-primary text-body-1 font-weight-bold">
            Rs {{ formatCurrency(item.net_salary) }}
          </div>
        </template>

        <!-- View/Download Payslip -->
        <template #item.download_payslip="{ item }">
          <div class="d-flex gap-2">
            <VBtn 
              icon="tabler-eye" 
              variant="outlined" 
              color="primary" 
              size="small"
              :disabled="!selectedMonth"
              @click="downloadPayslip(item, 'pdf')"
              title="View PDF Payslip"
            />
            <VBtn 
              icon="tabler-download" 
              variant="outlined" 
              color="success" 
              size="small"
              :disabled="!selectedMonth"
              @click="downloadPayslip(item, 'csv')"
              title="Download CSV Payslip"
            />
          </div>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <VBtn 
            icon="tabler-trash" 
            variant="text" 
            color="error" 
            size="small"
            @click="askDeletePayslip(item.id)"
          />
        </template>
        <template #bottom>
          <TablePagination v-model:page="page" :items-per-page="itemsPerPage" :total-items="totalEmployees" />
        </template>
      </VDataTableServer>
    </VCard>
    
    <!-- Delete Confirmation Dialog -->
    <ConfirmationDialog
      v-model="isDeleteDialogOpen"
      title="Delete Payslip"
      description="Are you sure you want to delete this payslip? This action cannot be undone."
      cancel-text="Cancel"
      confirm-text="Delete"
      :loading="deleteSubmitting"
      @confirm="confirmDeletePayslip"
    />
  </section>
</template>

<script setup>
import { createUrl } from '@/@core/composable/createUrl'
import { useApi } from '@/composables/useApi'
import { $toast } from '@/utils/toast'
import { onMounted, onUnmounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const accessToken = useCookie("accessToken")
const searchQuery = ref("")
const searchTimeout = ref(null)
const loading = ref(false)
const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()
const payslipData = ref([])
const totalEmployees = ref(0)
const selectedRows = ref([])
const selectedDepartment = ref()
const selectedPayrollType = ref()
const selectedMonth = ref(null)
const selectedYear = ref(null)
const isGenerating = ref(false)
const hasPayrollForMonth = ref(false)
const isFetching = ref(false)
const fetchCounter = ref(0)
const allowFetch = ref(true)
const isDeleteDialogOpen = ref(false)
const deleteSubmitting = ref(false)
const deleteTargetId = ref(null)

// Filter options
const departments = ref([])
const payrollTypes = ref([
  { title: 'Basic Salary', value: 'Basic Salary' },
  { title: 'Gross Salary', value: 'Gross Salary' },
  { title: 'Daily Wages', value: 'Daily Wages' },
  { title: 'Contract', value: 'Contract' },
])

// Month and year options
const monthOptions = ref([])
const yearOptions = ref([])

// Initialize payslip data

// Headers for the data table
const headers = [
  {
    title: "Employee",
    key: "employee",
    sortable: true,
  },
   {
    title: "Employee Code",
    key: "employee_code",
    sortable: true,
  },
  {
    title: "Salary",
    key: "salary",
    sortable: true,
  },
  {
    title: "Total Allowances",
    key: "total_allowances",
    sortable: true,
  },
  {
    title: "Total Deductions",
    key: "total_deductions",
    sortable: true,
  },
  {
    title: "Total Loans",
    key: "total_loans",
    sortable: true,
  },
  {
    title: "Net Salary",
    key: "net_salary",
    sortable: true,
  },
  {
    title: "Payslip",
    key: "download_payslip",
    sortable: false,
  },
  {
    title: "Actions",
    key: "actions",
    sortable: false,
  },
]

// Generate month options (1-12)
const generateMonthOptions = () => {
  const months = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
  ]
  
  monthOptions.value = months.map((month, index) => ({
    title: month,
    value: index + 1 // 1-12
  }))
}

// Generate year options (from 1990 to current year + 5)
const generateYearOptions = () => {
  const currentYear = new Date().getFullYear()
  const startYear = 2020
  const endYear = currentYear 
  
  const years = []
  for (let year = endYear; year >= startYear; year--) {
    years.push({
      title: year.toString(),
      value: year
    })
  }
  
  yearOptions.value = years
}

// Manual fetch function with explicit permission
const manualFetch = async () => {
  console.log("Manual fetch requested")
  allowFetch.value = true
  await fetchPayslipData()
}


// Removed checkPayrollExists function to prevent infinite loops

// Fetch payslip data
const fetchPayslipData = async () => {
  // Global protection against unwanted calls
  if (!allowFetch.value) {
    console.log("Fetch is globally disabled, skipping...")
    return
  }
  
  // Prevent infinite loops
  if (isFetching.value) {
    console.log("Already fetching data, skipping...")
    return
  }
  
  // Increment fetch counter for debugging
  fetchCounter.value++
  
  // Combine month and year into the expected format (YYYY-MM)
  const combinedMonth = selectedMonth.value && selectedYear.value 
    ? `${selectedYear.value}-${String(selectedMonth.value).padStart(2, '0')}`
    : null
    
  console.log("Starting fetchPayslipData for month:", combinedMonth, "Call #", fetchCounter.value)
  isFetching.value = true
  loading.value = true
  try {
    // Always try to fetch payroll data for the selected month
    let endpoint = "/payrolls"
    let queryParams = {
      q: searchQuery.value,
      per_page: itemsPerPage.value === -1 ? 1000 : itemsPerPage.value,
      page: page.value,
      month: combinedMonth,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
    }
    
    console.log("Fetching payroll data from:", endpoint, "with params:", queryParams)

    const { data, error } = await useApi(
      createUrl(endpoint, { query: queryParams })
    )

    if (error.value) {
      console.error("Error fetching data:", error.value)
      throw error.value
    }

    console.log("Fetched data:", data.value)
    
    // Set the data
    payslipData.value = data.value?.data || []
    totalEmployees.value = data.value?.meta?.total || 0
    
    // Update the payroll existence flag based on actual data
    hasPayrollForMonth.value = payslipData.value.length > 0
    
  } catch (err) {
    console.error("Error fetching payslip data:", err)
    payslipData.value = []
    totalEmployees.value = 0
    $toast.error("Failed to fetch payslip data")
  } finally {
    loading.value = false
    isFetching.value = false
  }
}

// Fetch departments for filter dropdown
const fetchDepartments = async () => {
  try {
    const { data } = await useApi("/departments?context=filters")
    departments.value = data.map((dept) => ({
      title: dept.name,
      value: dept.id,
    }))
  } catch (error) {
    // Silently fail for departments
  }
}

// Update table options
const updateOptions = (options) => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
  // Disabled automatic fetch to prevent extra API calls
  // Users can manually refresh if needed
  console.log("Table options updated, but not fetching data automatically")
}

// Get dynamic card title based on state
const getCardTitle = () => {
  if (!selectedMonth.value || !selectedYear.value) {
    return "Payslip Management"
  }
  
  const combinedMonth = `${selectedYear.value}-${String(selectedMonth.value).padStart(2, '0')}`
  
  if (hasPayrollForMonth.value) {
    return `Payslip - ${combinedMonth} (Generated Payroll Data)`
  }
  return `Payslip - ${combinedMonth} (No Payroll Generated)`
}

// Format currency
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-IN').format(amount)
}

// Get payroll type color
const getPayrollTypeColor = (type) => {
  const colors = {
    'Basic Salary': 'primary',
    'Gross Salary': 'success',
    'Daily Wages': 'warning',
    'Contract': 'info',
  }
  return colors[type] || 'default'
}


// Delete payslip functions
const askDeletePayslip = (id) => {
  deleteTargetId.value = id
  isDeleteDialogOpen.value = true
}

const confirmDeletePayslip = async () => {
  if (!deleteTargetId.value) {
    isDeleteDialogOpen.value = false
    return
  }
  
  deleteSubmitting.value = true
  try {
    await useApi(`/payrolls/${deleteTargetId.value}`, {
      method: 'DELETE'
    })
    
    isDeleteDialogOpen.value = false
    deleteTargetId.value = null
    
    // Refresh the data
    allowFetch.value = true
    await fetchPayslipData()
    
    $toast.success('Payslip deleted successfully')
  } catch (error) {
    console.error('Error deleting payslip:', error)
    $toast.error('Failed to delete payslip')
  } finally {
    deleteSubmitting.value = false
  }
}

// View/Download payslip
const downloadPayslip = async (item, format) => {
  if (!selectedMonth.value || !selectedYear.value) {
    $toast.error("Please select both month and year first")
    return
  }

  // Combine month and year into the expected format (YYYY-MM)
  const combinedMonth = `${selectedYear.value}-${String(selectedMonth.value).padStart(2, '0')}`

  try {
    console.log('Generating payslip for:', item.employee_id, 'format:', format, 'month:', combinedMonth)
    console.log('Using payroll table data:', {
      basic_salary: item.basic_salary,
      total_allowances: item.total_allowances,
      total_deductions: item.total_deductions,
      total_loans: item.total_loans,
      net_salary: item.net_salary
    })
    
    // Generate payslip content
    let content = ''
    let filename = `${item.employee_id}_payslip_${combinedMonth}.${format}`
    
    if (format === 'csv') {
      // Helper function to properly escape CSV values
      const escapeCSV = (value) => {
        if (value === null || value === undefined) return ''
        const stringValue = String(value)
        // If the value contains comma, newline, or double quote, wrap it in quotes and escape internal quotes
        if (stringValue.includes(',') || stringValue.includes('\n') || stringValue.includes('"')) {
          return `"${stringValue.replace(/"/g, '""')}"`
        }
        return stringValue
      }

      // Use payroll table data directly
      const basicSalary = item.basic_salary || 0
      const totalAllowances = item.total_allowances || 0
      const totalDeductions = item.total_deductions || 0
      const totalLoans = item.total_loans || 0
      const netSalary = item.net_salary || 0
      
      // Create properly formatted CSV content
      const csvRows = [
        // Header row
        [
          'Employee Code',
          'Employee Name', 
          'Pay Period',
          'Basic Salary',
          'Total Allowances',
          'Total Deductions', 
          'Total Loans',
          'Net Salary',
          'Generated Date'
        ],
        // Data row
        [
          escapeCSV(item.employee?.employee_code || item.employee_code || 'N/A'),
          escapeCSV(item.employee?.name || item.name),
          escapeCSV(combinedMonth),
          escapeCSV(basicSalary),
          escapeCSV(totalAllowances),
          escapeCSV(totalDeductions),
          escapeCSV(totalLoans),
          escapeCSV(netSalary),
          escapeCSV(new Date().toLocaleDateString())
        ]
      ]

      // Convert rows to CSV format
      content = csvRows.map(row => row.join(',')).join('\n')
      
      // Add BOM for proper UTF-8 encoding in Excel
      const BOM = '\uFEFF'
      content = BOM + content
      
      // For CSV, download the file
      const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' })
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = filename
      link.style.display = 'none'
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      window.URL.revokeObjectURL(url)
      
      $toast.success('CSV payslip downloaded successfully')
    } else {
      // For PDF, create HTML content and display in new tab
      // Use payroll table data directly for PDF
      const basicSalary = item.basic_salary || 0
      const totalAllowances = item.total_allowances || 0
      const totalDeductions = item.total_deductions || 0
      const totalLoans = item.total_loans || 0
      const netSalary = item.net_salary || 0
      
      const htmlContent = `
        <!DOCTYPE html>
        <html>
        <head>
          <title>Payslip - ${item.employee_id}</title>
          <style>
            body {
              font-family: Arial, sans-serif;
              margin: 20px;
              background-color: #f5f5f5;
            }
            .payslip-container {
              max-width: 800px;
              margin: 0 auto;
              background: white;
              padding: 30px;
              border-radius: 8px;
              box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            .header {
              text-align: center;
              border-bottom: 2px solid #333;
              padding-bottom: 20px;
              margin-bottom: 30px;
            }
            .logo-container {
              margin-bottom: 15px;
            }
            .company-logo {
              max-width: 200px;
              height: auto;
            }
            .company-name {
              font-size: 24px;
              font-weight: bold;
              color: #333;
              margin-bottom: 5px;
            }
            .payslip-title {
              font-size: 18px;
              color: #666;
            }
            .employee-info {
              display: grid;
              grid-template-columns: 1fr 1fr;
              gap: 20px;
              margin-bottom: 30px;
            }
            .info-section h3 {
              color: #333;
              margin-bottom: 10px;
              border-bottom: 1px solid #ddd;
              padding-bottom: 5px;
            }
            .info-row {
              display: flex;
              justify-content: space-between;
              margin-bottom: 8px;
            }
            .info-label {
              font-weight: bold;
              color: #555;
            }
            .info-value {
              color: #333;
            }
            .salary-breakdown {
              margin-top: 30px;
            }
            .salary-breakdown h3 {
              color: #333;
              margin-bottom: 15px;
              border-bottom: 2px solid #333;
              padding-bottom: 10px;
            }
            .salary-item {
              display: flex;
              justify-content: space-between;
              padding: 10px 0;
              border-bottom: 1px solid #eee;
            }
            .salary-item:last-child {
              border-bottom: 2px solid #333;
              font-weight: bold;
              font-size: 16px;
              margin-top: 10px;
            }
            .salary-label {
              color: #555;
            }
            .salary-amount {
              color: #333;
              font-weight: bold;
            }
            .positive {
              color: #28a745;
            }
            .negative {
              color: #dc3545;
            }
            .net-salary {
              color: #007bff;
              font-size: 18px;
            }
            .footer {
              text-align: center;
              margin-top: 40px;
              padding-top: 20px;
              border-top: 1px solid #ddd;
              color: #666;
              font-size: 12px;
            }
            @media print {
              body { background-color: white; }
              .payslip-container { box-shadow: none; }
            }
          </style>
        </head>
        <body>
          <div class="payslip-container">
            <div class="header">
              <div class="logo-container">
                <img src="/images/company-logo.png" alt="Company Logo" class="company-logo">
              </div>
              <div class="company-name">ZAL ERP SYSTEM</div>
              <div class="payslip-title">PAYSLIP FOR ${combinedMonth}</div>
            </div>
            
            <div class="employee-info">
              <div class="info-section">
                <h3>Employee Information</h3>
                <div class="info-row">
                  <span class="info-label">Employee Code:</span>
                  <span class="info-value">${item.employee?.employee_code || item.employee_code || 'N/A'}</span>
                </div>
                <div class="info-row">
                  <span class="info-label">Name:</span>
                  <span class="info-value">${item.employee?.name || item.name}</span>
                </div>
              </div>
              
              <div class="info-section">
                <h3>Payroll Information</h3>
                <div class="info-row">
                  <span class="info-label">Pay Period:</span>
                  <span class="info-value">${combinedMonth}</span>
                </div>
                <div class="info-row">
                  <span class="info-label">Generated On:</span>
                  <span class="info-value">${new Date().toLocaleDateString()}</span>
                </div>
              </div>
            </div>
            
            <div class="salary-breakdown">
              <h3>Salary Breakdown</h3>
              <div class="salary-item">
                <span class="salary-label">Basic Salary:</span>
                <span class="salary-amount">Rs ${basicSalary.toLocaleString()}</span>
              </div>
              <div class="salary-item">
                <span class="salary-label">Total Allowances:</span>
                <span class="salary-amount positive">+ Rs ${totalAllowances.toLocaleString()}</span>
              </div>
              <div class="salary-item">
                <span class="salary-label">Total Deductions:</span>
                <span class="salary-amount negative">- Rs ${totalDeductions.toLocaleString()}</span>
              </div>
              <div class="salary-item">
                <span class="salary-label">Total Loans:</span>
                <span class="salary-amount negative">- Rs ${totalLoans.toLocaleString()}</span>
              </div>
              <div class="salary-item">
                <span class="salary-label">Net Salary:</span>
                <span class="salary-amount net-salary">Rs ${netSalary.toLocaleString()}</span>
              </div>
            </div>
            
            <div class="footer">
              <p>This is a computer-generated payslip based on stored payroll data. No signature required.</p>
              <p>Payroll Generated: ${item.created_at ? new Date(item.created_at).toLocaleDateString() : 'N/A'}</p>
              <p>Payslip Generated: ${new Date().toLocaleString()}</p>
            </div>
          </div>
        </body>
        </html>
      `
      
      // Open PDF in new tab
      const newWindow = window.open('', '_blank')
      newWindow.document.write(htmlContent)
      newWindow.document.close()
      
      $toast.success('PDF payslip opened in new tab')
    }
  } catch (error) {
    console.error("Error generating payslip:", error)
    $toast.error(`Failed to generate payslip: ${error.message}`)
  }
}


// Generate payrolls for ALL employees
const generatePayrolls = async () => {
  if (!selectedMonth.value || !selectedYear.value) {
    $toast.error("Please select both month and year")
    return
  }

  isGenerating.value = true
  
  // Combine month and year into the expected format (YYYY-MM)
  const combinedMonth = `${selectedYear.value}-${String(selectedMonth.value).padStart(2, '0')}`
  
  try {
    console.log("Starting payroll generation for month:", combinedMonth)
    
    // First, get all employees
    const { data: employeesData, error: employeesError } = await useApi(
      createUrl("/employees")
    )

    if (employeesError.value) {
      console.error("Error fetching employees:", employeesError.value)
      $toast.error("Failed to fetch employees")
      return
    }

    if (!employeesData.value || !employeesData.value.data || employeesData.value.data.length === 0) {
      $toast.error("No employees found")
      return
    }

    console.log("Found employees:", employeesData.value.data.length)
    const allEmployeeIds = employeesData.value.data.map(employee => employee.id)
    console.log("Employee IDs:", allEmployeeIds)
    console.log("Selected month:", combinedMonth)
    
    // Generate payrolls for all employees
    const promises = allEmployeeIds.map(async (employeeId) => {
      const requestBody = {
        employee_id: employeeId,
        month: combinedMonth
      }
      console.log(`Generating payroll for employee ${employeeId} with data:`, requestBody)
      
      // Use fetch directly for payroll generation
      const response = await fetch('/api/payrolls/generate', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${accessToken.value}`,
        },
        body: JSON.stringify(requestBody)
      })
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      
      const result = await response.json()
      return result
    })

    console.log("Starting parallel payroll generation...")
    const results = await Promise.allSettled(promises)
    
    let successCount = 0
    let errorCount = 0
    
    results.forEach((result, index) => {
      if (result.status === 'fulfilled') {
        const response = result.value
        if (response.status === 'success') {
          successCount++
          console.log(`Successfully generated payroll for employee ${allEmployeeIds[index]}`)
        } else {
          errorCount++
          console.error(`Failed to generate payroll for employee ${allEmployeeIds[index]}:`, response.message)
        }
      } else {
        errorCount++
        console.error(`Failed to generate payroll for employee ${allEmployeeIds[index]}:`, result.reason)
      }
    })

    console.log(`Generation complete: ${successCount} success, ${errorCount} errors`)

    if (successCount > 0) {
      $toast.success(`Successfully generated ${successCount} payroll(s) for all employees`)
      // Force refresh the data
      console.log("Refreshing data after payroll generation...")
      // Reset the payroll existence flag to force a fresh check
      hasPayrollForMonth.value = false
      allowFetch.value = true
      await fetchPayslipData()
    }
    
    if (errorCount > 0) {
      $toast.error(`Failed to generate ${errorCount} payroll(s) already exist`)
    }

  } catch (error) {
    console.error("Error generating payrolls:", error)
    $toast.error(`Failed to generate payrolls: ${error.message || error}`)
  } finally {
    isGenerating.value = false
  }
}

// Handle month/year change with debounce
let monthChangeTimeout = null
const onMonthYearChange = () => {
  console.log("onMonthYearChange triggered, selectedMonth:", selectedMonth.value, "selectedYear:", selectedYear.value)
  
  // Reset fetch counter when month/year changes
  fetchCounter.value = 0
  
  // Clear any existing timeout
  if (monthChangeTimeout) {
    clearTimeout(monthChangeTimeout)
  }
  
  // Set a new timeout to debounce the API call
  monthChangeTimeout = setTimeout(() => {
    if (selectedMonth.value && selectedYear.value && !isFetching.value) {
      console.log("Month/Year changed to:", selectedMonth.value, selectedYear.value, "Starting fetch...")
      allowFetch.value = true
      fetchPayslipData()
    } else {
      console.log("Skipping fetch - month:", selectedMonth.value, "year:", selectedYear.value, "isFetching:", isFetching.value)
    }
  }, 1000) // Increased to 1000ms debounce
}

// Handle search change with debounce
const onSearchChange = () => {
  console.log("onSearchChange triggered, searchQuery:", searchQuery.value)
  
  // Clear any existing timeout
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value)
  }
  
  // Set a new timeout to debounce the search
  searchTimeout.value = setTimeout(() => {
    if (selectedMonth.value) {
      console.log("Search changed to:", searchQuery.value, "Starting fetch...")
      allowFetch.value = true
      page.value = 1 // Reset to first page when searching
      fetchPayslipData()
    } else {
      console.log("Skipping search fetch - no month selected")
    }
  }, 500) // 500ms debounce for search
}

// Watch for search query changes
watch(searchQuery, (newValue, oldValue) => {
  console.log("Search query changed from:", oldValue, "to:", newValue)
  if (selectedMonth.value && newValue !== oldValue) {
    console.log("Triggering search due to query change")
    onSearchChange()
  }
})

// Initial setup when component mounts
onMounted(() => {
  generateMonthOptions()
  generateYearOptions()
  fetchDepartments()
})

// Cleanup when component unmounts
onUnmounted(() => {
  if (monthChangeTimeout) {
    clearTimeout(monthChangeTimeout)
  }
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value)
  }
  // Reset counter
  fetchCounter.value = 0
})
</script>

<style scoped>
/* Mobile Responsive Styles for Payslip Controls */
.month-select {
  max-inline-size: 140px;
  min-inline-size: 120px;
}

.year-select {
  max-inline-size: 120px;
  min-inline-size: 100px;
}

.generate-btn {
  min-inline-size: 120px;
}

.search-field {
  flex: 1;
  max-inline-size: 300px;
  min-inline-size: 200px;
}

.items-per-page-select {
  max-inline-size: 100px;
  min-inline-size: 80px;
}

/* Mobile optimizations */
@media (max-width: 600px) {
  .month-select,
  .year-select {
    max-inline-size: 120px;
    min-inline-size: 100px;
  }

  .generate-btn {
    min-inline-size: 100px;
  }

  .search-field {
    font-size: 16px; /* Prevents zoom on iOS */
    max-inline-size: 250px;
    min-inline-size: 150px;
  }

  .items-per-page-select {
    max-inline-size: 90px;
    min-inline-size: 70px;
  }
}

/* Tablet optimizations */
@media (min-width: 601px) and (max-width: 960px) {
  .search-field {
    max-inline-size: 280px;
    min-inline-size: 180px;
  }
}

/* Desktop optimizations */
@media (min-width: 1280px) {
  .search-field {
    max-inline-size: 320px;
  }

  .generate-btn {
    min-inline-size: 140px;
  }
}

/* Ensure proper spacing and alignment */
.v-card-text {
  padding-block: 24px;
  padding-inline: 24px;
}

/* Button text responsive behavior */
@media (max-width: 599px) {
  .generate-btn .d-sm-none {
    display: inline !important;
  }

  .generate-btn .d-none.d-sm-inline {
    display: none !important;
  }
}

@media (min-width: 600px) {
  .generate-btn .d-sm-none {
    display: none !important;
  }

  .generate-btn .d-none.d-sm-inline {
    display: inline !important;
  }
}

/* Flex wrap improvements for mobile */
@media (max-width: 1023px) {
  .justify-lg-end {
    justify-content: flex-start !important;
  }
}

/* Ensure consistent gap spacing */
.d-flex.gap-3 > * {
  margin-block-end: 8px;
}

@media (min-width: 1024px) {
  .d-flex.gap-3 > * {
    margin-block-end: 0;
  }
}
</style>
