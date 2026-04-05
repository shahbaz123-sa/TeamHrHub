<script setup>
import {hasPermission} from "@/utils/permission.js";
import {useToast} from "vue-toast-notification";
import "vue-toast-notification/dist/theme-sugar.css";
import {onMounted, ref, watch, nextTick} from "vue";
import axios from 'axios';

const $toast = useToast();
const accessToken = useCookie('accessToken');

const searchQuery = ref("");
const exemptedEmployees = ref({ data: [], total: 0 });
const loading = ref(false);
const error = ref(null);
const isSubmitting = ref(false);
const isExporting = ref(false);
const page = ref(1)
const itemsPerPage = ref(15)
const sortBy = ref()
const orderBy = ref()
const branches = ref([]);
const departments = ref([]);
const statuses = ref([]);

const departmentSearch = ref('');
const departmentsLoading = ref(false);
const selectedDepartment = ref(null);
const selectedStatus = ref(null);
const selectedBranch = ref(null);

const updateOptions = (options) => {
  const uiKey = options.sortBy?.[0]?.key;
  const uiOrder = options.sortBy?.[0]?.order;

  const sortKeyMap = {
    'employee': 'employee.name',
  };

  const serverSortKey = uiKey ? (sortKeyMap[uiKey] ?? uiKey) : undefined;
  const sortChanged = serverSortKey !== sortBy.value;

  sortBy.value = serverSortKey;
  orderBy.value = uiOrder;

  if (typeof options.page !== 'undefined') page.value = options.page;
  if (typeof options.itemsPerPage !== 'undefined') itemsPerPage.value = options.itemsPerPage;
  if (sortChanged) {
    page.value = 1;
  }
  fetchEmployeeRules();
}

const headers = [
  {
    title: "Employee Name",
    key: "name",
  },
  {
    title: "Emp. Code",
    key: "employee_code",
  },
  {
    title: "Department",
    key: "department",
  },
  {
    title: 'Employement Type',
    key: 'employement_type',
  },
  {
    title: 'Branch',
    key: 'branch',
  },
  {
    title: 'Attendance Exemption',
    key: 'attendance_exemption',
  },
]

const buildQueryParams = () => {
  const query = {
    q: searchQuery.value || undefined,
    department_id: selectedDepartment.value || undefined,
    employment_type_id: selectedStatus.value || undefined,
    branch_id: selectedBranch.value || undefined,
    per_page: itemsPerPage.value,
    page: page.value,
    ...(sortBy.value && { sortBy: sortBy.value }),
    ...(orderBy.value && { orderBy: orderBy.value }),
  };
  return query;
}

const fetchEmployeeRules = async () => {
  loading.value = true;
  error.value = null;
  try {
    const response = await axios.get('/api/employee-by-rules', { params: buildQueryParams(), headers: { Authorization: `Bearer ${accessToken.value}` } });
    const data = response.data;

    exemptedEmployees.value = {
      data: data.data || data.items || [],
      total: data.meta?.total ?? data.total ?? (data.length || 0),
    };
  } catch (err) {
    error.value = err;
    $toast.error("Failed to load rules");
  } finally {
    loading.value = false;
  }
};

const fetchBranches = async () => {
  try {
    const { data } = await axios.get("/api/branches", {
      headers: {
        Authorization: `Bearer ${accessToken.value}`,
      },
    });
    branches.value = data.data ? data.data.map((item) => ({ title: item.name, value: item.id })) : data.map((item) => ({ title: item.name, value: item.id }));
  } catch (error) {
    $toast.error("Failed to load branches");
  }
};

const fetchDepartments = async () => {
  try {
    departmentsLoading.value = true;
    const { data } = await axios.get("/api/departments?context=filters", {
      headers: {
        Authorization: `Bearer ${accessToken.value}`,
      },
    })
    departments.value = data.data ? data.data.map((dept) => ({ title: dept.name, value: dept.id })) : data.map((dept) => ({ title: dept.name, value: dept.id }));
  } catch (error) {
    // ignore
  } finally {
    departmentsLoading.value = false;
  }
}

const fetchEmploymentTypes = async () => {
  try {
    const { data } = await axios.get('/api/employment-types', { headers: { Authorization: `Bearer ${accessToken.value}` } });
    const list = data.data || data;
    statuses.value = list.map(t => ({ title: t.name, value: t.id }));
  } catch (error) {
    statuses.value = [];
  }
}


const updateExemption = async (id, att_exem) => {
  try {
    isSubmitting.value = true;
    // API call to assign roles
    await $api("/employees/update-att-exemption", {
      method: "POST",
      body: {
        employee_id: id,
        attendance_exemption: att_exem,
      },
    });

    $toast.success('Attendance exemption updated successfully.');
    fetchEmployeeRules()
    isSubmitting.value = false
  } catch (error) {
    console.log('error', error);
    $toast.error(Object.values(error.response?._data?.errors).slice(0, 2).join("\n"))
    isSubmitting.value = false
  }
};

const resetFilters = async () => {
  searchQuery.value = "";
  selectedDepartment.value = null;
  departmentSearch.value = '';
  selectedStatus.value = null;
  selectedBranch.value = null;
  page.value = 1;
  // wait for DOM to update so VAutocomplete input clears and placeholder shows
  await nextTick();
  fetchEmployeeRules();
}

const exportExcel = async () => {
  isExporting.value = true;
  loading.value = true;
  try {
    const params = buildQueryParams();
    const response = await axios.get('/api/employee-rulesExport/excel', { params, responseType: 'blob', headers: { Authorization: `Bearer ${accessToken.value}` } });

    const blob = new Blob([response.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;

    let filename = 'employee_rules_report';
    if (searchQuery.value) filename += `_${searchQuery.value}`;
    if (selectedDepartment.value) filename += `_dept_${selectedDepartment.value}`;
    if (selectedStatus.value) filename += `_type_${selectedStatus.value}`;
    if (selectedBranch.value) filename += `_branch_${selectedBranch.value}`;
    const ts = new Date().toISOString().replace(/[:.]/g, '-');
    filename += `_${ts}`;

    link.setAttribute('download', `${filename}.xlsx`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);

    $toast.success('Excel exported successfully.');
  } catch (error) {
    console.error('Error exporting employee rules Excel:', error);
    $toast.error('Failed to export Excel.');
  } finally {
    isExporting.value = false;
    loading.value = false;
  }
}

const exportPDF = async () => {
  isExporting.value = true;
  loading.value = true;
  try {
    const params = buildQueryParams();
    const response = await axios.get('/api/employee-rulesExport/pdf', { params, responseType: 'blob', headers: { Authorization: `Bearer ${accessToken.value}` } });
    const blob = new Blob([response.data], { type: 'application/pdf' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;

    let filename = 'employee_rules_report';
    if (searchQuery.value) filename += `_${searchQuery.value}`;
    if (selectedDepartment.value) filename += `_dept_${selectedDepartment.value}`;
    if (selectedStatus.value) filename += `_type_${selectedStatus.value}`;
    if (selectedBranch.value) filename += `_branch_${selectedBranch.value}`;
    const ts = new Date().toISOString().replace(/[:.]/g, '-');
    filename += `_${ts}`;

    link.setAttribute('download', `${filename}.pdf`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);

    $toast.success('PDF exported successfully.');
  } catch (error) {
    console.error('Error exporting employee rules PDF:', error);
    $toast.error('Failed to export PDF.');
  } finally {
    isExporting.value = false;
    loading.value = false;
  }
}

// adjust watchers to mirror Leaves (reset page on filter change)
watch([searchQuery, selectedStatus, selectedDepartment, selectedBranch], () => {
  page.value = 1;
  fetchEmployeeRules();
}, { deep: true });

// Ensure per-page change resets page to 1
watch(itemsPerPage, (newVal, oldVal) => {
  if (newVal !== oldVal) {
    page.value = 1;
    fetchEmployeeRules();
  }
});

// Initial fetch
onMounted(() => {
  fetchEmployeeRules();
  fetchBranches();
  fetchDepartments();
  fetchEmploymentTypes();
});

</script>

<template>
  <section>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0 help-center-breadcrumbs"
      :items="[{ title: 'HR Administration' }, { title: 'Employee Rules' }]"
    />
    <VCard>
      <VCardText>
        <VRow>
          <VCol cols="12" md="4">
            <VAutocomplete
              v-model="selectedDepartment"
              v-model:search="departmentSearch"
              :items="departments"
              :loading="departmentsLoading"
              label=""
              item-title="title"
              item-value="value"
              placeholder="Select Department"
              clearable
              no-data-text="No department found"
            />
          </VCol>
          <VCol cols="12" sm="3">
            <AppSelect v-model="selectedStatus" placeholder="Employement Type" :items="statuses" clearable />
          </VCol>
          <VCol cols="12" sm="3">
            <AppSelect v-model="selectedBranch" :items="branches" placeholder="Select branch" clearable/>
          </VCol>
          <VSpacer />
        </VRow>
      </VCardText>
      <VCardText>
        <!-- <div class="d-flex gap-4 ma-6 align-center"> -->
        <VRow>
          <VCol cols="12" md="4" sm="4">
            <AppTextField v-model="searchQuery" placeholder="Search by employee" />
          </VCol>
          <VCol cols="auto">
            <VBtn
              color="secondary"
              variant="outlined"
              class="flex-grow-1 flex-md-grow-0"
              @click="resetFilters"
            >
              <VIcon start icon="tabler-refresh" />
              <span class="d-none d-md-inline">Reset</span>
              <span class="d-md-none">Reset</span>
            </VBtn>
          </VCol>
          <VCol cols="auto">
            <VBtn color="success" :loading="isExporting" :disabled="isExporting">
              <VIcon start icon="tabler-file-export" />
              Export
            </VBtn>
            <VMenu
              activator="parent"
            >
              <VList>
                <VListItem
                  title="Export PDF"
                  prepend-icon="tabler-file-type-pdf"
                  @click="exportPDF"
                />
                <VListItem
                  title="Export Excel"
                  prepend-icon="tabler-file-spreadsheet"
                  @click="exportExcel"
                />
              </VList>
            </VMenu>
          </VCol>
          <VSpacer />
          <VCol cols="auto">
            <AppSelect v-model="itemsPerPage" :items="[
              { value: 5, title: '5' },
              { value: 10, title: '10' },
              { value: 20, title: '20' },
              { value: 50, title: '50' },
              { value: -1, title: 'All' },
            ]" style="inline-size: 7rem;" />
          </VCol>
        </VRow>
      </VCardText>
    </VCard>
    <VRow>
      <VCol cols="12">
        <VCard>
          <VDivider />
          <VDataTableServer
            v-model:items-per-page="itemsPerPage"
            v-model:page="page"
            :headers="headers"
            :items="exemptedEmployees.data"
            :items-length="exemptedEmployees.total"
            :loading="loading"
            loading-text="Loading data..." class="text-no-wrap"
            :multi-sort="false"
            @update:options="updateOptions"
          >
            <template #item.name="{ item }">
              <div class="d-flex align-center gap-x-4">
                <VAvatar size="34" variant="tonal" :color="'primary'">
                  <span>{{ item.name.charAt(0) }}</span>
                </VAvatar>
                <div class="d-flex flex-column">
                  <h6 class="text-base">
                    {{ item.name }}
                  </h6>
                  <div class="text-sm">
                    {{ item.official_email || item.personal_email }}
                  </div>
                </div>
              </div>
            </template>
            <template #item.department="{ item }">
              <div class="d-flex align-center">
                <div>
                  <p class="text-base mb-0 text-high-emphasis">
                    {{ item.department || '-' }}
                  </p>
                  <p class="text-sm mb-0">
                    {{ item.designation ?? 'N/A' }}
                  </p>
                </div>
              </div>
            </template>

            <template #item.employement_type="{ item }">
              <div>
                {{ item.employement_type || '-' }}
              </div>
            </template>

            <template #item.branch="{ item }">
              <div>
                {{ item.branch || '-' }}
              </div>
            </template>

            <template #item.attendance_exemption="{ item }">
              <div class="d-flex justify-left">
                <VCheckbox
                  v-model="item.attendance_exemption"
                  :disabled="isSubmitting || !(hasPermission('employee_rules.edit') || hasPermission('employee_rules.create'))"
                  @change="updateExemption(item.id, item.attendance_exemption)"
                />
              </div>
            </template>

            <template #bottom>
              <TablePagination v-model:page="page" :items-per-page="itemsPerPage" :total-items="exemptedEmployees.total" />
            </template>
          </VDataTableServer>
        </VCard>
      </VCol>
    </VRow>
  </section>
</template>

<style lang="scss">
.text-capitalize {
  text-transform: capitalize;
}
</style>
