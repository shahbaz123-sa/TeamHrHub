<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toast-notification';
import DocumentImageViewer from "@/components/common/DocumentImageViewer.vue";

declare const useCookie: any;
declare function useApi(...args: any[]): Promise<any>;
declare function createUrl(...args: any[]): string;
declare const $api: any;

const accessToken = useCookie('accessToken');
const loading = ref(false);
const isExporting = ref(false);
const searchQuery = ref('');
const selectedDepartment = ref(null);
const departmentSearch = ref('');
const departments = ref([]);
const departmentsLoading = ref(false);
const selectedBranch = ref(null);
const branchSearch = ref('');
const branches = ref([]);
const branchesLoading = ref(false);
const selectedYear = ref(new Date().getFullYear());
const years = ref([]);
const itemsPerPage = ref(10);
const page = ref(1);
const sortBy = ref(null);
const orderBy = ref(null);
const selectedRows = ref([]);

const leaveTypes = ref([]);
const headers = ref([
  { title: 'Employee', key: 'employee', sortable: true },
  { title: 'Emp. Code', key: 'employee_code', sortable: true },
  { title: 'Department', key: 'department', sortable: true },
]);

const balancesData = ref<any>({ data: [], meta: { total: 0 } });
const balances = ref<any[]>([]);
const totalBalances = ref(0);
const $toast = useToast();

const fetchLeaveTypes = async () => {
  try {
    const { data, error } = await useApi(createUrl('/leave-types'));
    if (!error?.value) {
      const payload = data.value;
      const types = payload?.data || [];

      // Ensure UI always uses sort_order (and id tie-breaker) for column order
      const sortedTypes = [...types].sort((a: any, b: any) => {
        const ao = a?.sort_order ?? 0;
        const bo = b?.sort_order ?? 0;
        if (ao !== bo) return ao - bo;
        return (a?.id ?? 0) - (b?.id ?? 0);
      });

      leaveTypes.value = sortedTypes;

      const base = [
        { title: 'Employee', key: 'employee', sortable: true },
        { title: 'Emp. Code', key: 'employee_code', sortable: true },
        { title: 'Department', key: 'department', sortable: true },
      ];
      const ltCols = sortedTypes.map((t: any) => ({ title: t.name, key: `lt_${t.id}`, sortable: true, align: 'center' }));
      headers.value = base.concat(ltCols);
    }
  } catch (e) {
    leaveTypes.value = [];
  }
}

const fetchBalances = async () => {
  loading.value = true;
  const { data, error } = await useApi(createUrl('/leaves/balances', {
    query: {
      q: searchQuery.value,
      department_id: selectedDepartment.value,
      branch_id: selectedBranch.value,
      year: selectedYear.value,
      per_page: itemsPerPage.value,
      page: page.value,
      ...(sortBy.value && { sortBy: sortBy.value }),
      ...(orderBy.value && { orderBy: orderBy.value }),
    }
  }), { headers: { Authorization: `Bearer ${accessToken.value}` } });

  if (!error?.value) {
    const payload = data.value;
    balancesData.value = payload?.data || payload || { data: [], meta: { total: 0 } };
    const raw = balancesData.value?.data || [];

    const display = raw.map((item: any) => {
      const employee = item.employee || {};
      const row: any = {
        employee: employee,
        employee_code: employee.employee_code || (employee.employee_code ?? '-') ,
        department: (employee.department && employee.department.name) || (employee.department?.name ?? '-'),
      };
      const balancesMap = (item.balances || []).reduce((acc: any, b: any) => {
        const id = b.leave_type_id ?? b.leave_type?.id ?? null;
        if (id) acc[id] = b;
        return acc;
      }, {});

      if (leaveTypes.value.length) {
        leaveTypes.value.forEach((lt: any) => {
          const b = balancesMap[lt.id];
          if (b) {
            const remaining = b.remaining ?? 0;
            const quota = b.quota ?? lt.quota ?? 0;
            row[`lt_${lt.id}`] = `${remaining}/${quota}`;
          } else {
            row[`lt_${lt.id}`] = `0/${lt.quota ?? 0}`;
          }
        });
      } else {
        (item.balances || []).forEach((b: any) => {
          const key = b.leave_type_name || (b.leave_type && b.leave_type.name) || `lt_${b.leave_type_id}`;
          const remaining = b.remaining ?? 0;
          const quota = b.quota ?? 0;
          row[key] = `${remaining}/${quota}`;
        });
      }

      return row;
    });

    balances.value = display;
    totalBalances.value = balancesData.value?.total || balancesData.value?.meta?.total || 0;
  }
  loading.value = false;
}

const fetchDepartments = async () => {
  const { data } = await axios.get("/api/departments?context=filters", {
    headers: {
      Authorization: `Bearer ${accessToken.value}`,
    },
  });
  departments.value = data.data.map(t => ({ name: t.name, id: t.id }));
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

onMounted(async () => {
  const cur = new Date().getFullYear();
  years.value = Array.from({ length: 5 }).map((_, i) => ({ title: (cur - i).toString(), value: cur - i }));
  await fetchLeaveTypes();
  await fetchBalances();
  fetchDepartments();
  fetchBranches();
});

watch([searchQuery, selectedDepartment, selectedBranch, selectedYear, itemsPerPage, page], fetchBalances);

const updateOptions = (options: any) => {
  const uiKey = options.sortBy?.[0]?.key;
  const uiOrder = options.sortBy?.[0]?.order;
  // Map UI keys to server-side sortBy
  const sortKeyMap: any = {
    'employee': 'name',
    'employee_code': 'employee_code',
    'department': 'department',
  };
  let serverSortKey;
  if (uiKey && uiKey.startsWith('lt_')) {
    serverSortKey = uiKey; // e.g., lt_3
  } else {
    serverSortKey = uiKey ? (sortKeyMap[uiKey] ?? uiKey) : undefined;
  }
  const sortChanged = serverSortKey !== sortBy.value;
  sortBy.value = serverSortKey;
  orderBy.value = uiOrder;
  if (typeof options.page !== 'undefined') page.value = options.page;
  if (typeof options.itemsPerPage !== 'undefined') itemsPerPage.value = options.itemsPerPage;
  if (sortChanged) page.value = 1;
  fetchBalances();
}

const resetFilters = () => {
  searchQuery.value = '';
  selectedDepartment.value = null;
  departmentSearch.value = '';
  selectedBranch.value = null;
  branchSearch.value = '';
  selectedYear.value = new Date().getFullYear();
  itemsPerPage.value = 10;
  page.value = 1;
  sortBy.value = null;
  orderBy.value = null;
  selectedRows.value = [];
  fetchBalances();
}

const exportExcel = async () => {
  isExporting.value = true;
  loading.value = true;
  try {
    const params: any = {
      q: searchQuery.value || undefined,
      department_id: selectedDepartment.value || undefined,
      branch_id: selectedBranch.value || undefined,
      year: selectedYear.value || undefined,
      per_page: itemsPerPage.value,
      page: page.value,
      ...(sortBy.value && { sortBy: sortBy.value }),
      ...(orderBy.value && { orderBy: orderBy.value }),
    };

    const response = await axios.get('/api/leaveBalancesExport/excel', {
      params,
      responseType: 'blob',
      headers: { Authorization: `Bearer ${accessToken.value}` },
    });

    // detect error responses returned as JSON/html in blob
    const contentType = response.headers['content-type'] || '';
    if (contentType.includes('application/json') || contentType.includes('text/html')) {
      const text = await new Response(response.data).text();
      let parsed = null;
      try { parsed = JSON.parse(text); } catch (e) { parsed = null; }
      const msg = parsed?.message || parsed?.error || parsed || text.substring(0, 1000);
      console.error('Export returned error payload:', msg);
      $toast.error('Export failed: ' + (parsed?.message || parsed?.error || 'Server returned an error'));
      return;
    }

    const blob = new Blob([response.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;

    let filename = 'leave_balances';
    if (searchQuery.value) filename += `_${searchQuery.value}`;
    if (selectedDepartment.value) filename += `_dept_${selectedDepartment.value}`;
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
    console.error('Error exporting leave balances Excel:', error);
    $toast.error('Failed to export Excel.');
  } finally {
    isExporting.value = false;
    loading.value = false;
  }
}

const exportPdf = async () => {
  isExporting.value = true;
  loading.value = true;
  try {
    const params: any = {
      q: searchQuery.value || undefined,
      department_id: selectedDepartment.value || undefined,
      branch_id: selectedBranch.value || undefined,
      year: selectedYear.value || undefined,
      per_page: itemsPerPage.value,
      page: page.value,
      ...(sortBy.value && { sortBy: sortBy.value }),
      ...(orderBy.value && { orderBy: orderBy.value }),
    };

    const response = await axios.get('/api/leaveBalancesExport/pdf', {
      params,
      responseType: 'blob',
      headers: { Authorization: `Bearer ${accessToken.value}` },
    });

    const contentType = response.headers['content-type'] || '';
    if (contentType.includes('application/json') || contentType.includes('text/html')) {
      const text = await new Response(response.data).text();
      let parsed = null;
      try { parsed = JSON.parse(text); } catch (e) { parsed = null; }
      const msg = parsed?.message || parsed?.error || parsed || text.substring(0, 1000);
      console.error('Export returned error payload:', msg);
      $toast.error('Export failed: ' + (parsed?.message || parsed?.error || 'Server returned an error'));
      return;
    }

    const blob = new Blob([response.data], { type: 'application/pdf' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;

    let filename = 'leave_balances';
    if (searchQuery.value) filename += `_${searchQuery.value}`;
    if (selectedDepartment.value) filename += `_dept_${selectedDepartment.value}`;
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
    console.error('Error exporting leave balances PDF:', error);
    $toast.error('Failed to export PDF.');
  } finally {
    isExporting.value = false;
    loading.value = false;
  }
}
</script>

<template>
  <section>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0 help-center-breadcrumbs"
      :items="[{ title: 'Timesheet' }, { title: 'Leave Balances' }]
      "
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
              item-title="name"
              item-value="id"
              placeholder="Select Department"
              clearable
              no-data-text="No department found"
            />
          </VCol>

          <VCol cols="12" md="3">
            <VAutocomplete
              v-model="selectedBranch"
              v-model:search="branchSearch"
              :items="branches"
              :loading="branchesLoading"
              label=""
              item-title="title"
              item-value="value"
              placeholder="Select Branch"
              clearable
              no-data-text="No branch found"
            />
          </VCol>
          <VCol cols="12" sm="2">
            <AppSelect v-model="selectedYear" placeholder="Year" :items="years" clearable />
          </VCol>
          <VSpacer />
        </VRow>
      </VCardText>

      <VCardText>
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
            <VMenu activator="parent">
              <VList>
                <VListItem title="Export Excel" prepend-icon="tabler-file-spreadsheet" @click="exportExcel" />
                <VListItem title="Export PDF" prepend-icon="tabler-file-type-pdf" @click="exportPdf" />
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

      <VDivider class="mt-1" />

      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:model-value="selectedRows"
        v-model:page="page"
        :headers="headers"
        :items="balances"
        :items-length="totalBalances"
        class="text-no-wrap"
        @update:options="updateOptions"
        :loading="loading"
        loading-text="Loading data..."
      >
        <template #item.employee="{ item }">
          <div class="d-flex align-center gap-x-4">
            <VAvatar size="34" :color="!item.employee?.user?.avatar_url ? 'primary' : undefined"
                     :variant="!item.employee?.user?.avatar_url ? 'tonal' : undefined">
              <DocumentImageViewer v-if="item.employee?.user?.avatar_url" :type="'avatar'" :src="item.employee?.user?.avatar_url" :pdf-title="item.employee?.name" />
              <span v-else>{{ item.employee?.name.charAt(0) || '-' }}</span>
            </VAvatar>
            <div class="d-flex flex-column">
              <h6 class="text-base">{{ item.employee?.name || '—' }}</h6>
              <div class="text-sm">{{ item.employee?.official_email || item.employee?.personal_email || '—' }}</div>
            </div>
          </div>
        </template>

        <template #item.employee_code="{ item }">
          <div>{{ item.employee?.employee_code || '—' }}</div>
        </template>

        <template #item.department="{ item }">
          <div>
            <div>{{ item.employee?.department?.name || '—' }}</div>
          </div>
        </template>

        <template #bottom>
          <TablePagination v-model:page="page" :items-per-page="itemsPerPage" :total-items="totalBalances" />
        </template>
      </VDataTableServer>
    </VCard>
  </section>
</template>

