<template>
  <section>
      <VBreadcrumbs
        class="px-0 pb-2 pt-0 help-center-breadcrumbs"
        :items="[{ title: 'Timesheet' }, { title: 'Attendance' }]"
      />
    <VCard>
      <VCardText>
        <VRow>
          <VCol cols="12" md="4">
            <VAutocomplete
              v-model="filters.departments"
              :items="departments"
              label=""
              item-title="name"
              item-value="id"
              placeholder="Select department"
              clearable
              multiple
              no-data-text="No department found"
            />
          </VCol>
          <VCol cols="12" md="4">
            <AppDateTimePicker
              v-model="filters.dateRange"
              label=""
              :config="{ mode: 'range' }"
              @update:model-value="onDateRangeChange"
              clearable
            />
          </VCol>
          <VCol cols="12" md="4">
            <AppSelect
              v-model="filters.status"
              :items="statusOptions"
              label=""
              placeholder="Select Attendance Status"
              clearable
            />
          </VCol>
          <VCol cols="12" md="4">
            <VAutocomplete
              v-model="filters.employment_status_id"
              :items="employmentStatuses"
              label=""
              item-title="title"
              item-value="value"
              placeholder="Employee Status"
              clearable
              no-data-text="No status found"
            />
          </VCol>
        </VRow>
        <VRow>
          <VCol cols="12" md="4">
            <AppTextField v-model="filters.searchQuery" placeholder="Search Attendance" />
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
                    title="PDF Export"
                    prepend-icon="tabler-file-type-pdf"
                    @click="exportPDF"
                  />
                  <VListItem
                    title="Department Wise PDF Export"
                    prepend-icon="tabler-file-text"
                    @click="exportPdfDeptBelow"
                  />
                  <VListItem
                    title="Department Wise Excel Export"
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
      <VDivider />
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:model-value="selectedRows"
        v-model:page="page"
        :headers="headers"
        :items="attendances"
        :items-length="totalAttendances"
        :loading="loading"
        loading-text="Loading data..."
        class="text-no-wrap"
        @update:options="updateOptions"
      >
        <template #item.date="{ item }">
          {{ formatDate(item.date) }}
        </template>

        <template #item.employee="{ item }">
          <div class="d-flex align-center gap-x-4">
            <VAvatar
              size="34"
              :color="!item?.profile_picture ? 'primary' : undefined"
              :variant="!item?.profile_picture ? 'tonal' : undefined"
            >
              <DocumentImageViewer v-if="item?.profile_picture" :type="'avatar'" :src="item?.profile_picture" :pdf-title="item?.employee_name" />
              <span v-else>{{ item?.employee_name.charAt(0) || '-' }}</span>
            </VAvatar>
            <div class="d-flex flex-column">
              <h6 class="text-base">
                {{ item.employee_name }}
              </h6>
              <div class="text-sm">
                {{ item.employee_official_email || item.employee_personal_email }}
              </div>
            </div>
          </div>
        </template>

        <template #item.employee.department.name="{ item }">
          <div class="d-flex flex-column">
            <div>{{ item.department || (item.employee?.department?.name ?? '—') }}</div>
            <small class="text-low-emphasis">{{ item.designation || item.employee?.designation?.title || '—' }}</small>
          </div>
        </template>

        <template #item.check_in="{ item }">
          <VTooltip
            v-if="item.check_in && item.latitude_in && item.longitude_in"
            :text="item.address_in || 'View on maps'"
            location="top">
            <template #activator="{ props }">
              <a
                :href="`https://www.google.com/maps?q=${item.latitude_in},${item.longitude_in}`"
                target="_blank"
                rel="noopener noreferrer"
                v-bind="props"
              >
                <i class="tabler tabler-map-pin"></i>
              </a>
            </template>
          </VTooltip>
          <VTooltip
            v-if="item.check_in && (!item.latitude_in || !item.longitude_in)"
            text="Location not available"
            location="top">
            <template #activator="{ props }">
              <i v-bind="props" class="tabler tabler-map-pin-off"></i>
            </template>
          </VTooltip>
          {{ convertTo12HourFormat(item.check_in) }}
        </template>

        <template #item.check_out="{ item }">
          <VTooltip
            v-if="item.check_out && item.latitude_out && item.longitude_out"
            :text="item.address_out || 'View on maps'"
            location="top">
            <template #activator="{ props }">
              <a
                :href="`https://www.google.com/maps?q=${item.latitude_out},${item.longitude_out}`"
                target="_blank"
                rel="noopener noreferrer"
                v-bind="props"
              >
                <i class="tabler tabler-map-pin"></i>
              </a>
            </template>
          </VTooltip>
          <VTooltip
            v-if="item.check_out && (!item.latitude_out || !item.longitude_out)"
            text="Location not available"
            location="top">
            <template #activator="{ props }">
              <i v-bind="props" class="tabler tabler-map-pin-off"></i>
            </template>
          </VTooltip>
          {{ convertTo12HourFormat(item.check_out) }}
        </template>

        <template #item.status="{ item }">
          <VChip 
            :color="getStatusColor(item.status)" 
            :size="$vuetify.display.mobile ? 'x-small' : 'small'"
            class="status-chip"
          >
            <span>{{ item.status.replace('-', ' ').replace(/\b\w/g, (c) => c.toUpperCase()) }}</span>
          </VChip>
        </template>

        <template v-if="hasPermission('attendance.update')" #item.actions="{ item }">
          <IconBtn 
            @click="editAttendance(item)"
            :size="$vuetify.display.mobile ? 'small' : 'default'"
          >
            <VIcon :icon="'tabler-pencil'" :size="$vuetify.display.mobile ? 'small' : 'default'" />
          </IconBtn>
        </template>
        
        <template #item.late_minutes="{ item }">
          <span class="time-cell">{{ convertIntoHoursMins(item.late_minutes) }}</span>
        </template>
        <template #item.early_leaving_minutes="{ item }">
          <span class="time-cell">{{ convertIntoHoursMins(item.early_leaving_minutes) }}</span>
        </template>
        <template #item.overtime_minutes="{ item }">
          <span class="time-cell">{{ convertIntoHoursMins(item.overtime_minutes) }}</span>
        </template>

        <!-- pagination -->
        <template #bottom>
          <TablePagination v-model:page="page" :items-per-page="itemsPerPage" :total-items="totalAttendances" />
        </template>
      </VDataTableServer>
    </VCard>

    <!-- Import Dialog -->
    <VDialog v-model="showImportModal" max-width="500">
      <VCard>
        <VCardTitle>Import Attendances</VCardTitle>
        <VCardText>
          <VFileInput
            v-model="importFile"
            label="Select file"
            accept=".xlsx,.xls,.csv"
            class="mb-4"
          />
          <VCheckbox v-model="overrideData" label="Override existing records" />
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn color="secondary" @click="showImportModal = false">
            Cancel
          </VBtn>
          <VBtn color="primary" :loading="isImporting" @click="importData">
            Import
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Edit Dialog -->
    <VDialog 
      v-model="showEditModal" 
      :max-width="$vuetify.display.mobile ? '95vw' : '600'"
      :fullscreen="$vuetify.display.mobile"
    >
      <VCard>
        <VCardTitle class="d-flex align-center">
          <span>Edit Attendance</span>
          <VSpacer />
          <VBtn 
            v-if="$vuetify.display.mobile"
            icon
            variant="text"
            @click="showEditModal = false"
          >
            <VIcon icon="tabler-x" />
          </VBtn>
        </VCardTitle>
        <VCardText>
          <VRow>
            <VCol cols="12" md="6">
              <AppTextField v-model="editForm.date" label="Date" disabled />
            </VCol>
            <VCol cols="12" md="6">
              <AppTextField
                v-model="editForm.employee_name"
                label="Employee"
                disabled
              />
            </VCol>
            <VCol cols="12" md="6">
              <AppTextField
                v-model="editForm.check_in"
                label="Check In"
                type="time"
              />
            </VCol>
            <VCol cols="12" md="6">
              <AppTextField
                v-model="editForm.check_out"
                label="Check Out"
                type="time"
              />
            </VCol>
            <VCol cols="12" md="6">
              <AppSelect
                v-model="editForm.status"
                :items="statusOptions"
                label="Status"
              />
            </VCol>
            <VCol cols="12">
              <AppTextarea v-model="editForm.note" label="Note" rows="3" />
            </VCol>
          </VRow>
        </VCardText>
        <VCardActions class="pa-4">
          <VBtn 
            color="secondary" 
            @click="showEditModal = false"
            :block="$vuetify.display.mobile"
            class="me-2"
          > 
            Cancel 
          </VBtn>
          <VBtn 
            color="primary" 
            @click="updateAttendance"
            :block="$vuetify.display.mobile"
          > 
            Save 
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </section>
</template>

<script setup>
import { createUrl } from '@/@core/composable/createUrl';
import { convertIntoHoursMins, convertTo12HourFormat } from "@/utils/helpers/date.js";
import { hasPermission } from "@/utils/permission";
import axios from "axios";
import { onMounted, ref, watch, computed } from "vue";
import { useToast } from "vue-toast-notification";
import "vue-toast-notification/dist/theme-sugar.css";
import DocumentImageViewer from "@/components/common/DocumentImageViewer.vue";

// Initialize toast
const $toast = useToast();
const route = useRoute();
// Data
const loading = ref(false);
const attendances = ref([]);
const totalAttendances = ref(0);
const departments = ref([]);
const employmentStatuses = ref([]);
const itemsPerPage = ref(10);
const page = ref(1);
const sortBy = ref();
const orderBy = ref();
const selectedRows = ref([]);
const statusOptions = [
  { title: "Not marked", value: "not-marked" },
  { title: "Present", value: "present" },
  { title: "Late", value: "late" },
  { title: "Absent", value: "absent" },
  { title: "Leave", value: "leave" },
  { title: "Half Leave", value: "half-leave" },
  { title: "Short Leave", value: "short-leave" },
  { title: "Holiday", value: "holiday" },
];

const today = new Date().toISOString().slice(0, 10);
const filters = ref({
  searchQuery:"",
  dateRange:today,
  start_date: today,
  end_date: today,
  departments: [],
  department_id: "",
  designation_id: "",
  status: null,
  employment_status_id: null,
});

const onDateRangeChange = (value) => {
  if (!value) return;
  if (Array.isArray(value) && value.length === 2) {
    [filters.value.start_date, filters.value.end_date] = value;
  } else if (typeof value === 'string' && value.includes('to')) {
    [filters.value.start_date, filters.value.end_date] = value.split(' to ').map(d => d.trim());
  }
  else{
    [filters.value.start_date, filters.value.end_date] = [value, value];
  }
};

const initialStatus = route.query.status || null;
filters.value.status = initialStatus;

const q = route.query.q || "";
filters.value.searchQuery = q;

const startDate  = route.query.start_date || today;
const endDate = route.query.end_date || today
filters.value.start_date = startDate;
filters.value.end_date = endDate;
filters.value.dateRange = startDate  + ' to ' + endDate;

const showImportModal = ref(false);
const importFile = ref(null);
const overrideData = ref(false);
const isImporting = ref(false);
const isExporting = ref(false);
const accessToken = useCookie("accessToken");
const showEditModal = ref(false);
const editForm = ref({
  id: null,
  date: "",
  employee_name: "",
  check_in: "",
  check_out: "",
  status: "",
  note: "",
});

const headers = [
  { title: "Employee Name", key: "employee" },
  { title: "Emp. Code", key: "employee_code", align: "center" },
  { title: "Department", key: "employee.department.name", align: "center" },
  { title: "Date", key: "date", align: "center" },
  { title: "Check In", key: "check_in", align: "center" },
  { title: "Check In Location", key: "location_in", align: "center" },
  { title: "Check Out", key: "check_out", align: "center" },
  { title: "Check Out Location", key: "location_out", align: "center" },
  { title: "Status", key: "status", align: "center" },
  { title: "Check In From", key: "address_in", align: "center" },
  { title: "Check Out From", key: "address_out", align: "center" },
  { title: "Late", key: "late_minutes", align: "center" },
  { title: "Early Leaving", key: "early_leaving_minutes", align: "center" },
  { title: "OT", key: "overtime_minutes", align: "center" },
];

if(hasPermission('attendance.update'))
{
  headers.push({ title: "Actions", key: "actions" })
}

// Methods
const fetchAttendances = async () => {
  loading.value = true;
  try {
    const query = {
      searchQuery: filters.value.searchQuery,
      start_date: filters.value.start_date,
      end_date: filters.value.end_date,
      department_id: filters.value.department_id,
      designation_id: filters.value.designation_id,
      status: filters.value.status,
      employment_status_id: filters.value.employment_status_id,
      per_page: itemsPerPage.value,
      page: page.value,
      ...(sortBy.value && { sortBy: sortBy.value }),
      ...(orderBy.value && { orderBy: orderBy.value }),
    };

// Append department arrays manually
    filters.value.departments.forEach((d, index) => {
      query[`departments[${index}]`] = d;
    });

    const { data, error } = await useApi(
      createUrl("/attendance", {
        query: query
      }),
    );

    if (error.value) {
      throw error.value;
    }

    attendances.value = data.value?.data || [];
    totalAttendances.value = data.value?.meta?.total || 0;
  } catch (err) {
    attendances.value = [];
    totalAttendances.value = 0;
  } finally {
    loading.value = false;
  }
};

// Update table options
const updateOptions = (options) => {
  const newSort = options.sortBy[0]?.key;
  const newOrder = options.sortBy[0]?.order;
  const sortChanged = newSort !== sortBy.value || newOrder !== orderBy.value;
  sortBy.value = newSort;
  orderBy.value = newOrder;
  if (sortChanged) {
    page.value = 1;
  }
  fetchAttendances();
};

const fetchDepartments = async () => {
  const { data } = await axios.get("/api/departments?context=filters", {
    headers: {
      Authorization: `Bearer ${accessToken.value}`,
    },
  });
  departments.value = data.data.map(t => ({ name: t.name, id: t.id }));
};

const fetchEmploymentStatuses = async () => {
  try {
    const { data } = await axios.get("/api/employment-statuses", {
      headers: { Authorization: `Bearer ${accessToken.value}` },
    });
    const list = Array.isArray(data) ? data : data?.data ?? [];
    employmentStatuses.value = list.map(s => ({ title: s.name, value: s.id }));
    filters.value.employment_status_id = list[1].id;
  } catch (err) {
    console.error("Failed to load employment statuses", err);
  }
};

const resetFilters = () => {
  filters.value = {
    searchQuery: "",
    dateRange:today,
    start_date: today,
    end_date: today,
    departments: [],
    department_id: "",
    designation_id: "",
    status: null,
    employment_status_id: null,
  };
  page.value = 1;
  fetchAttendances();
};

const exportExcel = async () => {
  isExporting.value = true;
  loading.value = true;
  try {
    const params = {
      ...filters.value,
    };

    const response = await axios.get("/api/attendanceExport/excel", { // or same endpoint if backend handles format param
      params,
      responseType: "blob",
      headers: {
        Authorization: `Bearer ${accessToken.value}`,
      },
    });

    const blob = new Blob([response.data], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;

    let filename = 'attendance_report';

    if (filters.value.start_date && filters.value.end_date) {
      filename += `_${filters.value.start_date}_to_${filters.value.end_date}`;
    } else if (filters.value.start_date) {
      filename += `_from_${filters.value.start_date}`;
    } else if (filters.value.end_date) {
      filename += `_until_${filters.value.end_date}`;
    }

    if (filters.value.status) {
      filename += `_${filters.value.status}`;
    }

    link.setAttribute("download", `${filename}.xlsx`);
    document.body.appendChild(link);
    link.click();
    link.remove();

    window.URL.revokeObjectURL(url);

  } catch (error) {
    console.error("Error exporting attendance Excel:", error);
    // Add a toast notification if needed
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
      ...filters.value,
    };
    const response = await axios.get("/api/attendanceExport/pdf", {
      params,
      responseType: "blob",
      headers: {
        Authorization: `Bearer ${accessToken.value}`,
      },
    });

    const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
    const link = document.createElement("a");
    link.href = url;

    let filename = 'attendance_report';

    if (filters.value.start_date && filters.value.end_date) {
      filename += `_${filters.value.start_date}_to_${filters.value.end_date}`;
    } else if (filters.value.start_date) {
      filename += `_from_${filters.value.start_date}`;
    } else if (filters.value.end_date) {
      filename += `_until_${filters.value.end_date}`;
    }

    if (filters.value.status) {
      filename += `_${filters.value.status}`;
    }

    link.setAttribute("download", `${filename}.pdf`);
    document.body.appendChild(link);
    link.click();
    link.remove();

    // Clean up the blob URL
    window.URL.revokeObjectURL(url);
  } catch (error) {
    console.error("Error exporting attendance PDF:", error);
  } finally {
    isExporting.value = false;
    loading.value = false;
  }
};

// Export PDF with department-wise counts shown below employee list
const exportPdfDeptBelow = async () => {
  isExporting.value = true;
  loading.value = true;
  try {
    const params = {
      ...filters.value,
      ...(sortBy.value && { sortBy: sortBy.value }),
      ...(orderBy.value && { orderBy: orderBy.value }),
    };

    const response = await axios.get('/api/attendanceExport/pdf-dept-below', {
      params,
      responseType: 'blob',
      headers: {
        Authorization: `Bearer ${accessToken.value}`,
      },
    });

    const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
    const link = document.createElement('a');
    link.href = url;

    let filename = 'attendance_report_dept_below';
    if (filters.value.start_date && filters.value.end_date) {
      filename += `_${filters.value.start_date}_to_${filters.value.end_date}`;
    } else if (filters.value.start_date) {
      filename += `_from_${filters.value.start_date}`;
    } else if (filters.value.end_date) {
      filename += `_until_${filters.value.end_date}`;
    }
    if (filters.value.status) {
      filename += `_${filters.value.status}`;
    }

    link.setAttribute('download', `${filename}.pdf`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  } catch (error) {
    console.error('Error exporting attendance PDF (dept below):', error);
    $toast.error('Failed to export PDF.');
  } finally {
    isExporting.value = false;
    loading.value = false;
  }
};


const importData = async () => {
  if (!importFile.value) return;

  isImporting.value = true;
  try {
    const formData = new FormData();
    formData.append("file", importFile.value[0]);
    formData.append("override", overrideData.value);

    await axios.post("/api/attendance/import", formData, {
      headers: {
        "Content-Type": "multipart/form-data",
        Authorization: `Bearer ${accessToken.value}`,
      },
    });

    showImportModal.value = false;
    fetchAttendances();
  } finally {
    isImporting.value = false;
  }
};

const editAttendance = (item) => {
  const status =
    item.status && typeof item.status === "string"
      ? item.status.charAt(0).toLowerCase() + item.status.slice(1)
      : item.status;

  editForm.value = {
    id: item.id,
    date: item.date,
    employee_name: item.employee_name,
    check_in: item.check_in || "",
    check_out: item.check_out || "",
    status: status,
    note: item.note || "",
  };
  showEditModal.value = true;
};

const updateAttendance = async () => {
  try {
    // Prepare data with proper time format
    const updateData = { ...editForm.value };
    
    // Ensure time fields are in HH:MM:SS format
    if (updateData.check_in && !updateData.check_in.includes(':')) {
      // If it's just a time without colons, add them
      updateData.check_in = updateData.check_in;
    } else if (updateData.check_in && updateData.check_in.match(/^\d{1,2}:\d{2}$/)) {
      // If it's in HH:MM format, add seconds
      updateData.check_in = updateData.check_in + ':00';
    }
    
    if (updateData.check_out && !updateData.check_out.includes(':')) {
      // If it's just a time without colons, add them
      updateData.check_out = updateData.check_out;
    } else if (updateData.check_out && updateData.check_out.match(/^\d{1,2}:\d{2}$/)) {
      // If it's in HH:MM format, add seconds
      updateData.check_out = updateData.check_out + ':00';
    }
    
    const response = await axios.put(`/api/attendance/${editForm.value.id}`, updateData, {
      headers: {
        Authorization: `Bearer ${accessToken.value}`,
      },
    });
    
    // Show success message
    $toast.success(response.data.message || 'Attendance updated successfully');
    
    showEditModal.value = false;
    fetchAttendances();
  } catch (error) {
    console.error("Error updating attendance:", error);
    
    // Show error message
    let errorMessage = 'Failed to update attendance';
    
    if (error.response?.data?.message) {
      errorMessage = error.response.data.message;
    } else if (error.response?.data?.errors) {
      // Handle validation errors
      const errors = error.response.data.errors;
      const firstError = Object.values(errors)[0];
      errorMessage = Array.isArray(firstError) ? firstError[0] : firstError;
    }
    
    $toast.error(errorMessage);
  }
};

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
};

const getStatusColor = (status) => {
  switch (status) {
    case "Present":
      return "success";
    case "Absent":
      return "error";
    case "Late":
      return "warning";
    case "Half-leave":
      return "info";
    case "Short-leave":
      return "info";
    case "Leave":
      return "info";
    case "Not-marked":
      return "primary";
    default:
      return "secondary";
  }
};

const getInitials = (name) => {
  if (!name) return "";
  const parts = name.split(" ");
  return parts
    .map((part) => part[0])
    .join("")
    .toUpperCase();
};

// Lifecycle
onMounted(() => {
  // fetchAttendances();
  fetchDepartments();
  fetchEmploymentStatuses();
});

// Watch for filter changes
watch(
  [filters, itemsPerPage, page],
  () => {
    fetchAttendances();
  },
  { deep: true }
);
</script>

<style scoped>
.avatar-initials {
  display: flex;
  align-items: center;
  justify-content: center;
  block-size: 100%;
  font-weight: bold;
  inline-size: 100%;
}

/* Mobile Table Styles */
.mobile-table {
  overflow-x: auto;
}

.mobile-table :deep(.v-data-table__wrapper) {
  min-inline-size: 600px;
}

/* Employee Cell Styles */
.employee-cell {
  min-inline-size: 150px;
}

.employee-info {
  flex: 1;
  min-inline-size: 0;
}

.employee-info .font-weight-medium {
  overflow: hidden;
  max-inline-size: 120px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* Status Chip Styles */
.status-chip {
  white-space: nowrap;
}

/* Time Cell Styles */
.time-cell {
  font-size: 0.875rem;
  white-space: nowrap;
}

.location-text {
  font-size: 0.75rem;
  color: rgba(var(--v-theme-on-surface), 0.7);
  white-space: normal;
}

/* Mobile-specific adjustments */
@media (max-width: 960px) {
  .attendance-table :deep(.v-data-table-header) {
    font-size: 0.75rem;
  }

  .attendance-table :deep(.v-data-table__td) {
    padding-block: 8px;
    padding-inline: 4px;
  }

  .attendance-table :deep(.v-data-table__th) {
    padding-block: 8px;
    padding-inline: 4px;
  }

  .employee-cell {
    min-inline-size: 120px;
  }

  .employee-info .font-weight-medium {
    max-inline-size: 100px;
  }
}

@media (max-width: 600px) {
  .attendance-table :deep(.v-data-table__td) {
    padding-block: 6px;
    padding-inline: 2px;
  }

  .attendance-table :deep(.v-data-table__th) {
    padding-block: 6px;
    padding-inline: 2px;
  }

  .employee-cell {
    min-inline-size: 100px;
  }

  .employee-info .font-weight-medium {
    max-inline-size: 80px;
  }

  .time-cell {
    font-size: 0.75rem;
  }
}

/* Filter section mobile improvements */
@media (max-width: 600px) {
  .gap-2 {
    gap: 0.5rem;
  }

  .button-group {
    gap: 0.25rem;
  }
}

@media (max-width: 960px) {
  .button-group {
    gap: 0.5rem;
  }
}

/* Modal improvements for mobile */
@media (max-width: 600px) {
  .v-dialog--fullscreen .v-card {
    border-radius: 0;
  }

  .v-dialog--fullscreen .v-card__title {
    padding: 16px;
    border-block-end: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  }

  .v-dialog--fullscreen .v-card__text {
    padding: 16px;
  }

  .v-dialog--fullscreen .v-card__actions {
    padding: 16px;
    border-block-start: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  }
}

/* Button improvements for mobile */
@media (max-width: 600px) {
  .v-btn--size-x-small {
    min-block-size: 28px;
    padding-block: 0;
    padding-inline: 8px;
  }
}

/* Table horizontal scroll improvements */
.attendance-table :deep(.v-data-table__wrapper) {
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 4px;
}


/* Action button improvements */
.attendance-table :deep(.v-btn--size-small) {
  block-size: 32px;
  min-inline-size: 32px;
}

/* Responsive text sizing */
@media (max-width: 600px) {
  .text-body-2 {
    font-size: 0.75rem;
  }

  .text-caption {
    font-size: 0.625rem;
  }
}
</style>
