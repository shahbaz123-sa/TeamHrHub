<template>
  <section>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0 help-center-breadcrumbs"
      :items="[{ title: 'Timesheet' }, { title: 'Leaves' }]"
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
          <VCol cols="12" sm="2">
            <AppSelect v-model="selectedStatus" placeholder="Status" :items="statuses" clearable />
          </VCol>
          <VCol cols="12" sm="3">
             <AppSelect v-model="selectedType" :loading="typesLoading" placeholder="Leave Type" :items="types" clearable />
          </VCol>
          <VSpacer />
          <VCol cols="12" sm="2" class="text-right">
            <VBtn v-if="hasPermission('leave.create')" prepend-icon="tabler-plus" @click="openAddModal">
              Apply Leave
            </VBtn>
          </VCol>
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
      <VDivider class="mt-1" />

      <VDataTableServer v-model:items-per-page="itemsPerPage" v-model:model-value="selectedRows" v-model:page="page"
        :headers="headers" :items="leaves" :items-length="totalLeaves" class="text-no-wrap"
        @update:options="updateOptions" :loading="loading" loading-text="Loading data...">
        <template #item.employee="{ item }">
          <div class="d-flex align-center gap-x-4">
            <VAvatar
              size="34"
              :color="!item.employee?.profile_picture ? 'primary' : undefined"
              :variant="!item.employee?.profile_picture ? 'tonal' : undefined"
            >
              <DocumentImageViewer v-if="item.employee?.profile_picture" :type="'avatar'" :src="item.employee?.profile_picture" :pdf-title="item.employee?.name" />
              <span v-else>{{ item.employee?.name.charAt(0) || '-' }}</span>
            </VAvatar>
            <div class="d-flex flex-column">
              <h6 class="text-base">
                {{ item.employee?.name || '—' }}
              </h6>
              <div class="text-sm">
                {{ item.employee?.official_email || item.employee?.personal_email || '—' }}
              </div>
            </div>
          </div>
        </template>
        <!-- Reporting To column rendering (same visual format as Employee) -->
        <template #item.reporting_to="{ item }">
          <div class="d-flex align-center gap-x-4">
            <VAvatar size="34" variant="tonal" :color="'primary'">
              <span>{{ item.employee?.reporting_to?.name ? item.employee.reporting_to.name.charAt(0) : '—' }}</span>
            </VAvatar>
            <div class="d-flex flex-column">
              <h6 class="text-base">
                {{ item.employee?.reporting_to?.name || item.employee?.reporting_to?.fullName || '—' }}
              </h6>
              <div class="text-sm">
                {{ item.employee?.reporting_to?.email || item.employee?.reporting_to?.official_email || '—' }}
              </div>
            </div>
          </div>
        </template>
        <template #item.days="{ item }">
          <div class="text-high-emphasis text-body-1">{{ formatDays(item.days) }}</div>
        </template>
        <template #item.leave_type.name="{ item }">
          <div class="text-high-emphasis text-body-1">{{ item.leave_type?.name || '—' }}</div>
        </template>
        <template #item.leave_reason="{ item }">
          <div class="text-high-emphasis text-body-1 leave-reason">
            {{ item.leave_reason }}
          </div>
        </template>
        <template #item.manager_status="{ item }">
          <VChip
            :color="item.manager_status === 'approved' ? 'success' : item.manager_status === 'rejected' ? 'error' : 'warning'"
            size="small" label class="text-capitalize">{{ item.manager_status }}</VChip>
        </template>
        <template #item.hr_status="{ item }">
          <VChip
            :color="item.hr_status === 'approved' ? 'success' : item.hr_status === 'rejected' ? 'error' : 'warning'"
            size="small" label class="text-capitalize">{{ item.hr_status }}</VChip>
        </template>
        <template #item.actions="{ item }">
          <VMenu location="bottom end">
            <template #activator="{ props }">
              <VBtn v-bind="props" icon="tabler-dots-vertical" variant="text" color="default" />
            </template>
            <VList density="compact">
              <VListItem @click="openViewModal(item)">
                <template #prepend>
                  <VIcon icon="tabler-eye" />
                </template>
                <VListItemTitle>View</VListItemTitle>
              </VListItem>
              <VListItem v-if="canEdit(item)" @click="openEditModal(item)">
                <template #prepend>
                  <VIcon icon="tabler-edit" />
                </template>
                <VListItemTitle>Edit</VListItemTitle>
              </VListItem>
              <VListItem v-if="canDelete(item)" @click="askDelete(item.id)">
                <template #prepend>
                  <VIcon icon="tabler-trash" />
                </template>
                <VListItemTitle>Delete</VListItemTitle>
              </VListItem>
              <VDivider class="my-1" />
              <VListItem v-if="canManagerApproveReject(item)" @click="openManagerDecisionModal(item)">
                <template #prepend>
                  <VIcon icon="tabler-circle-check" />
                </template>
                <VListItemTitle>Manager Approve/Reject</VListItemTitle>
              </VListItem>
              <VListItem v-if="canHrApproveReject(item)" @click="openDecisionModal(item)">
                <template #prepend>
                  <VIcon icon="tabler-circle-check" />
                </template>
                <VListItemTitle>Hr Approve/Reject</VListItemTitle>
              </VListItem>
            </VList>
          </VMenu>
        </template>
        <template #bottom>
          <TablePagination v-model:page="page" :items-per-page="itemsPerPage" :total-items="totalLeaves" />
        </template>
      </VDataTableServer>
    </VCard>

    <!-- Decision Modal -->
    <hr-approve-reject-dialog v-model="isDecisionDialogOpen" :leave="selectedLeave" @submitted="onDecisionSubmitted" />
    <!-- Manager Decision Model -->
    <manager-approve-reject-dialog v-model="isManagerDecisionDialogOpen" :leave="selectedLeave"
      @submitted="onDecisionSubmitted" />
    <!-- Delete Confirmation Dialog -->
    <ConfirmationDialog v-model="isDeleteDialogOpen" title="Are you sure"
      description="This action can not be undone. Do you want to continue?" cancel-text="No" confirm-text="Yes"
      :loading="deleteSubmitting" @confirm="confirmDelete" />
    <!-- Leave Add/Edit Modal -->
    <LeaveModal ref="leaveModalRef" v-model="isLeaveModalOpen" :leave-data="selectedLeaveForEdit"
                @submitted="onLeaveSubmitted" :mode="dialogMode"/>
  </section>
</template>

<script setup>
import ConfirmationDialog from '@/components/common/ConfirmationDialog.vue';
import { hasPermission, hasRole, isSuperAdmin } from '@/utils/permission.js';
import {nextTick, onMounted, ref} from "vue";
import HrApproveRejectDialog from './hrApproveRejectDialog.vue';
import LeaveModal from './leaveModal.vue';
import ManagerApproveRejectDialog from './managerApproveRejectDialog.vue';
import axios from "axios";
import DocumentImageViewer from "@/components/common/DocumentImageViewer.vue";

const route = useRoute();

const isExporting = ref(false);
const selectedDepartment = ref();
const departmentSearch = ref();
const departments = ref([]);
const departmentsLoading = ref(false);
const loading = ref(false)
const searchQuery = ref("")
const selectedStatus = ref()
const selectedType = ref()
const types = ref([])
const typesLoading = ref(false)
const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()
const selectedRows = ref([])
const accessToken = useCookie("accessToken")
const dialogMode = ref("create")
// Decision modal state and handlers
const isDecisionDialogOpen = ref(false)
const isManagerDecisionDialogOpen = ref(false)
const selectedLeave = ref(null)
// Leave add/edit modal state
const isLeaveModalOpen = ref(false)
const selectedLeaveForEdit = ref(null)
const q = route.query.employee_code || "";
searchQuery.value = q;

const headers = [
  { title: "Employee", key: "employee", sortable: true },
  { title: "Emp. Code", key: "employee.employee_code", sortable: true },
  { title: "Department", key: "employee.department.name", sortable: true },
  { title: "Start Date", key: "start_date", sortable: true },
  { title: "End date", key: "end_date", sortable: true },
  { title: "Days", key: "days", sortable: true },
  { title: "Type", key: "leave_type.name", sortable: true },
  { title: "Description", key: "leave_reason", sortable: true },
  { title: "Reporting To", key: "reporting_to", sortable: true },
  { title: "Manager", key: "manager_status", sortable: true },
  { title: "HR", key: "hr_status", sortable: false },
  { title: "Actions", key: "actions", sortable: false },
]
const leavesData = ref({ data: [], meta: { total: 0 } })
const leaves = ref([])
const totalLeaves = ref(0)
const authUser = useCookie('userData').value

const isDeleteDialogOpen = ref(false)
const deleteSubmitting = ref(false)
const deleteTargetId = ref(null);

const updateOptions = (options) => {
  // UI emits a header key; map it to the backend sort key when needed.
  const uiKey = options.sortBy?.[0]?.key;
  const uiOrder = options.sortBy?.[0]?.order;

  // Map UI keys to server-side sort keys (keep UI keys simple for slots)
  const sortKeyMap = {
    // when user sorts the Employee column (UI key 'employee'), sort by employee.name on backend
    'employee': 'employee.name',
    'reporting_to': 'employee.reporting_to.name',
  };

  const serverSortKey = uiKey ? (sortKeyMap[uiKey] ?? uiKey) : undefined;

  const sortChanged = serverSortKey !== sortBy.value;
  sortBy.value = serverSortKey;
  orderBy.value = uiOrder;

  // Sync pagination values if provided by table options
  if (typeof options.page !== 'undefined') page.value = options.page;
  if (typeof options.itemsPerPage !== 'undefined') itemsPerPage.value = options.itemsPerPage;

  if (sortChanged) {
    page.value = 1;
  }

  fetchLeaves();
}

const fetchLeaves = async () => {
  loading.value = true
  const { data, error } = await useApi(
    createUrl("/leaves", {
      query: {
        q: searchQuery.value,
        department_id: selectedDepartment.value,
        status: selectedStatus.value,
        leave_type_id: selectedType.value,
        per_page: itemsPerPage.value,
        page: page.value,
        ...(sortBy.value && { sortBy: sortBy.value }),
        ...(orderBy.value && { orderBy: orderBy.value }),
      },
    }),
    { headers: { Authorization: `Bearer ${accessToken.value}` } }
  )

  if (!error.value) {
    leavesData.value = data.value
    leaves.value = leavesData.value?.data || []
    totalLeaves.value = leavesData.value?.meta?.total || 0
  }
  loading.value = false
}

const fetchDepartments = async () => {
  departmentsLoading.value = true;
  try {
    const { data } = await $api("/departments?context=filters", {
      headers: {
        Authorization: `Bearer ${accessToken.value}`,
      },
    })
    departments.value = data.map((dept) => ({
      title: dept.name,
      value: dept.id,
    }))
  } catch (error) {
    // keep departments empty on error
    departments.value = [];
  } finally {
    departmentsLoading.value = false;
  }
}

const fetchLeaveTypes = async () => {
  typesLoading.value = true
  try {
    const { data, error } = await useApi('/leave-types', {
      headers: { Authorization: `Bearer ${accessToken.value}` },
    })
    if (!error.value) {
      const leaveTypes = data.value?.data ?? []
      types.value = leaveTypes.map(type => ({ title: type.name, value: type.id }))
    } else {
      types.value = []
    }
  } catch (error) {
    types.value = []
    console.error('Error fetching leave types:', error)
  } finally {
    typesLoading.value = false
  }
}

const statuses = [
  { title: "Pending", value: "pending" },
  { title: "Approved", value: "approved" },
  { title: "Rejected", value: "rejected" },
]

watch([searchQuery, selectedStatus, selectedType, selectedDepartment], fetchLeaves, { deep: true })

onMounted(async () => {
  await Promise.all([
    fetchLeaves(),
    fetchDepartments(),
    fetchLeaveTypes(),
  ])
})

const resetFilters = () => {
  selectedDepartment.value = null
  departmentSearch.value = ''

  selectedStatus.value = null
  selectedType.value = null
  searchQuery.value = ''
  itemsPerPage.value = 10
  page.value = 1
  sortBy.value = null
  orderBy.value = null
  selectedRows.value = []
 }
const askDelete = id => {
  deleteTargetId.value = id
  isDeleteDialogOpen.value = true
}
const confirmDelete = async () => {
  if (!deleteTargetId.value) {
    isDeleteDialogOpen.value = false
    return
  }
  deleteSubmitting.value = true
  try {
    await $api(`/leaves/${deleteTargetId.value}`, {
      method: 'DELETE',
      headers: { Authorization: `Bearer ${accessToken.value}` },
    })
    isDeleteDialogOpen.value = false
    deleteTargetId.value = null
    await fetchLeaves()
    $toast.success('Leave deleted successfully.')
  } finally {
    deleteSubmitting.value = false
  }
}
const openDecisionModal = (item) => {
  selectedLeave.value = item
  isDecisionDialogOpen.value = true
}
const openManagerDecisionModal = (item) => {
  selectedLeave.value = item
  isManagerDecisionDialogOpen.value = true
}
const onDecisionSubmitted = async () => {
  await fetchLeaves()
}
// Leave modal handlers
const leaveModalRef = ref(null)
const openAddModal = () => {
  dialogMode.value = "create"
  selectedLeaveForEdit.value = null
  isLeaveModalOpen.value = true
  // Force refresh of leave balance data when modal opens
  nextTick(() => {
    if (leaveModalRef.value) {
      leaveModalRef.value.refreshLeaveBalance()
    }
  })
}
const openEditModal = (leave) => {
  dialogMode.value = "edit"
  selectedLeaveForEdit.value = leave
  isLeaveModalOpen.value = true
  // Force refresh of leave balance data when modal opens
  nextTick(() => {
    if (leaveModalRef.value) {
      leaveModalRef.value.refreshLeaveBalance()
    }
  })
}
const openViewModal = (leave) => {
  dialogMode.value = "view"
  selectedLeaveForEdit.value = leave
  isLeaveModalOpen.value = true
  // Force refresh of leave balance data when modal opens
  nextTick(() => {
    if (leaveModalRef.value) {
      leaveModalRef.value.refreshLeaveBalance()
    }
  })
}
const onLeaveSubmitted = async () => {
  await fetchLeaves()
}
const formatDays = (days) => {
  if (!days) return '0';

  // Format decimal days nicely
  const formatted = parseFloat(days).toFixed(2);

  // Remove trailing zeros for whole numbers
  return formatted.endsWith('.00') ? formatted.slice(0, -3) : formatted;
}
const canManagerApproveReject = leave => {
  return (
    isSuperAdmin()
    && leave.manager_status === 'pending'
    && leave.hr_status === 'pending'
  ) || (
      hasPermission('leave_manager_approval.create')
      && hasRole('Manager')
      && leave.manager_status === 'pending'
      && leave.hr_status === 'pending'
      && authUser.employee_id === leave.employee.reporting_to?.id
    )
}
const canHrApproveReject = leave => {
  return (
            isSuperAdmin()
            && leave.manager_status !== 'pending'
          ) || (
              hasPermission('leave_hr_approval.create')
              && hasRole('Hr')
              && leave.hr_status === 'pending'
            )
}
const canDelete = leave => {
  return (isSuperAdmin() && leave.manager_status === 'pending' && leave.hr_status === 'pending') ||
    (
      hasPermission('leave.delete')
      && leave.manager_status === 'pending'
      && leave.hr_status === 'pending'
      && (
        authUser.employee_id === leave.employee_id
        || authUser.employee_id === leave.employee.reporting_to?.id
        || isSuperAdmin()
      )
    );
}
const canEdit = leave => {
  return (isSuperAdmin() && leave.manager_status === 'pending' && leave.hr_status === 'pending') ||
    (
      hasPermission('leave.update') && leave.manager_status === 'pending' && leave.hr_status === 'pending'
      && (authUser.employee_id === leave.employee_id || authUser.employee_id === leave.employee.reporting_to?.id)
    );
}
const exportExcel = async () => {
  isExporting.value = true;
  loading.value = true;
  try {
    const params = {
      q: searchQuery.value || undefined,
      department_id: selectedDepartment.value || undefined,
      status: selectedStatus.value || undefined,
      leave_type_id: selectedType.value || undefined,
      per_page: itemsPerPage.value,
      page: page.value,
      ...(sortBy.value && { sortBy: sortBy.value }),
      ...(orderBy.value && { orderBy: orderBy.value }),
    };

    const response = await axios.get('/api/leaveExport/excel', {
      params,
      responseType: 'blob',
      headers: {
        Authorization: `Bearer ${accessToken.value}`,
      },
    });

    const blob = new Blob([response.data], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;

    let filename = 'leaves_report';
    if (searchQuery.value) filename += `_${searchQuery.value}`;
    if (selectedDepartment.value) filename += `_dept_${selectedDepartment.value}`;
    if (selectedType.value) filename += `_type_${selectedType.value}`;
    if (selectedStatus.value) filename += `_${selectedStatus.value}`;
    // Append timestamp to make filename unique
    const ts = new Date().toISOString().replace(/[:.]/g, '-');
    filename += `_${ts}`;

    // fallback extension
    link.setAttribute('download', `${filename}.xlsx`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);

    $toast.success('Excel exported successfully.');
  } catch (error) {
    console.error('Error exporting leaves Excel:', error);
    $toast.error('Failed to export Excel.');
  } finally {
    isExporting.value = false;
    loading.value = false;
  }
};
const exportPDF = async () => {
  isExporting.value = true;
  loading.value = true;
  try {
    const params = {
      q: searchQuery.value || undefined,
      department_id: selectedDepartment.value || undefined,
      status: selectedStatus.value || undefined,
      leave_type_id: selectedType.value || undefined,
      per_page: itemsPerPage.value,
      page: page.value,
      ...(sortBy.value && { sortBy: sortBy.value }),
      ...(orderBy.value && { orderBy: orderBy.value }),
    };

    const response = await axios.get('/api/leaveExport/pdf', {
      params,
      responseType: 'blob',
      headers: {
        Authorization: `Bearer ${accessToken.value}`,
      },
    });

    const blob = new Blob([response.data], { type: 'application/pdf' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;

    let filename = 'leaves_report';
    if (searchQuery.value) filename += `_${searchQuery.value}`;
    if (selectedDepartment.value) filename += `_dept_${selectedDepartment.value}`;
    if (selectedType.value) filename += `_type_${selectedType.value}`;
    if (selectedStatus.value) filename += `_${selectedStatus.value}`;
    const ts = new Date().toISOString().replace(/[:.]/g, '-');
    filename += `_${ts}`;

    link.setAttribute('download', `${filename}.pdf`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);

    $toast.success('PDF exported successfully.');
  } catch (error) {
    console.error('Error exporting leaves PDF:', error);
    $toast.error('Failed to export PDF.');
  } finally {
    isExporting.value = false;
    loading.value = false;
  }
};
</script>

<style scoped>
.leave-reason {
  display: -webkit-box;
  -webkit-line-clamp: 2;       /* limit to 2 lines */
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
  word-break: break-word;
  max-width: 200px;
  white-space: normal;          /* allow line breaks */
}

.text-capitalize {
  text-transform: capitalize;
}
</style>
