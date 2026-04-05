<template>
  <section>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0 help-center-breadcrumbs"
      :items="[{ title: 'Access Management' }, { title: 'Logged-In Users' }]"
    />
    <VCard class="mt-4">
      <VCardText>
        <VRow>
          <!-- 👉 Select Department -->
          <VCol cols="12" sm="4">
            <VAutocomplete
              v-model="filters.department_id"
              :items="departments"
              label="Select Department"
              item-title="title"
              item-value="value"
              placeholder="Select Department"
              clearable
              no-data-text="No department found"
            />
          </VCol>
          <!-- 👉 Select Employment Type -->
          <VCol cols="12" sm="3">
            <VAutocomplete v-model="filters.employment_type_id" label="Employment Type" placeholder="Employment Type" :items="employmentTypes" clearable />
          </VCol>

          <!-- 👉 Select Status -->
          <VCol cols="12" sm="3">
            <VAutocomplete v-model="filters.employment_status_id" label="Employment Status" placeholder="Status" :items="employmentStatuses" clearable />
          </VCol>
<!--          <VCol cols="12" sm="2">-->
<!--            <VAutocomplete v-model="filters.user_status" label="User Status" placeholder="Status" :items="[{ value: true, title: 'Active' }, { value: false, title: 'In-active' }]" clearable />-->
<!--          </VCol>-->
        </VRow>
      </VCardText>
      <VCardText>
          <VRow>
            <VCol cols="4">
              <AppTextField
                v-model="filters.q"
                placeholder="Search User"
                clearable
              />
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
              <VMenu
                activator="parent"
              >
                <VList>
                </VList>
              </VMenu>
            </VCol>
            <VCol cols="auto">
              <VBtn
                color="error"
                @click="logoutAll"
              >
                <VIcon start icon="tabler-logout" />
                <span class="d-none d-md-inline">Logout All</span>
                <span class="d-md-none">Logout All</span>
              </VBtn>
            </VCol>
            <VSpacer />
            <VCol cols="auto">
              <AppSelect
              v-model="itemsPerPage"
              :items="[
              { value: 5, title: '5' },
              { value: 10, title: '10' },
              { value: 20, title: '20' },
              { value: 50, title: '50' },
              { value: -1, title: 'All' },
            ]"
              style="inline-size: 7rem;"
            />
            </VCol>
          </VRow>
      </VCardText>
      <VDivider/>
        <VDataTableServer
          v-model:items-per-page="itemsPerPage"
          v-model:page="page"
          :headers="headers"
          :items="employeeRoles"
          :items-length="totalEmployeeRoles"
          class="text-no-wrap"
          :loading="loadingEmployeeRoles"
          loading-text="Loading data..."
        >
          <template #item.employee="{ item }">
            <div class="d-flex align-center gap-x-4">
              <VAvatar size="34" :color="!item.profile_picture ? 'primary' : undefined"
                       :variant="!item.profile_picture ? 'tonal' : undefined">
                <VImg
                  v-if="item.profile_picture"
                  :src="item.profile_picture"
                  cover
                />
                <span v-else>{{ item.name.charAt(0) }}</span>
              </VAvatar>
              <div class="d-flex flex-column">
                <h6 class="text-base">
                  {{ item.name }}
                </h6>
                <div class="text-sm">
                  {{ item.user.email }}
                </div>
              </div>
            </div>
          </template>


          <template #item.department="{ item }">
            <div class="text-capitalize text-high-emphasis text-body-1">
              {{ item.department || "N/A" }}
            </div>
          </template>

          <template #item.reporting_to="{ item }">
            <div class="text-high-emphasis text-body-1">
              {{ item.reporting_to?.name || " " }}
            </div>
          </template>

          <template #item.last_login_at="{ item }">
            <div class="text-high-emphasis text-body-1">
              {{
                item.last_login_at
                  ? new Date(item.last_login_at)
                    .toLocaleString('en-GB', {
                      day: '2-digit',
                      month: '2-digit',
                      year: 'numeric',
                      hour: '2-digit',
                      minute: '2-digit',
                      hour12: true
                    })
                    .replace(',', '')
                    .replace(/\//g, '-')
                    .replace('am', 'AM')
                    .replace('pm', 'PM')
                  : 'N/A'
              }}
            </div>
          </template>
          <template #item.location="{ item }">
            <VTooltip v-if="item.location" text="View on maps" location="top">
              <template #activator="{ props }">
                <a
                  :href="`https://www.google.com/maps?q=${item.location}`"
                  target="_blank"
                  rel="noopener noreferrer"
                  v-bind="props"
                >
                  <i class="tabler tabler-map-pin"></i>
                </a>
              </template>
            </VTooltip>
            <i v-else class="tabler tabler-map-pin-off" title="Location not available"></i>
          </template>

          <template #item.browser="{ item }">
            <VTooltip :text="item.browser" location="top">
              <template #activator="{ props }">
                <div v-bind="props" class="text-truncate" style="max-width: 200px">
                  {{ item.browser }}
                </div>
              </template>
            </VTooltip>
          </template>

          <template #item.device_type="{ item }">
            <div class="text-capitalize">
              {{ item.device_type }}
            </div>
          </template>

          <template #item.roles="{ item }">
            <div class="d-flex gap-1">
              <VChip
                v-for="role in item.user.roles"
                :key="role"
                label
                size="small"
                color="primary"
                class="font-weight-medium"
              >
                {{ role }}
              </VChip>
            </div>
          </template>

          <template #item.actions="{ item }">
            <VBtn
              :color="userData['id'] !== item.user.id ? 'error' : 'secondary'"
              variant="tonal"
              size="small"
              :disabled="userData['id'] === item.user.id"
              @click="logout(item.user.id)"
            >
              <VIcon icon="tabler-logout" />
              Logout
            </VBtn>
          </template>

          <template #bottom>
            <TablePagination
              v-model:page="page"
              :items-per-page="itemsPerPage"
              :total-items="totalEmployeeRoles"
            />
          </template>
        </VDataTableServer>
    </VCard>
  </section>
</template>

<script setup>
import {onMounted, ref} from "vue";
import AppTextField from "@core/components/app-form-elements/AppTextField.vue";
import AppSelect from "@core/components/app-form-elements/AppSelect.vue";
import TablePagination from "@core/components/TablePagination.vue";

const itemsPerPage = ref(10)
const page = ref(1)
const totalEmployeeRoles = ref(0)
const accessToken = useCookie("accessToken")
const loadingEmployeeRoles = ref(false)
const employeeRoles = ref([])
const departments = ref([])
const employmentStatuses = ref([])
const employmentTypes = ref([])

const userData = useCookie("userData").value;

const filters = ref({
  q: "",
  department_id: null,
  employment_type_id: null,
  employment_status_id: null,
  user_status: true,
})

const headers = [
  { title: 'User Name', key: 'employee' },
  // { title: 'Employee Code', key: 'employee_code', align: 'center' },
  { title: "Department", key: "department", align: 'center' },
  // { title: "Reporting To", key: "reporting_to", align: 'center' },
  { title: 'Login At', key: 'last_login_at', align: 'center' },
  { title: 'Location', key: 'location', align: 'center' },
  { title: 'Browser', key: 'browser', align: 'center', width: 200 },
  { title: 'Device Type', key: 'device_type', align: 'center' },
  { title: 'Assigned Roles', key: 'roles', sortable: false },
  { title: 'Actions', key: 'actions', align: 'center', sortable: false },
]

const fetchDepartments = async () => {
  try {
    const { data } = await $api("/departments?context=filters", {
      headers: {
        Authorization: `Bearer ${accessToken}`,
      },
    })
    departments.value = data.map((dept) => ({
      title: dept.name,
      value: dept.id,
    }))
  } catch (error) {
  }
}

const fetchEmploymentStatuses = async () => {
  try {
    const { data } = await $api("/employment-statuses", {
      method: "GET",
    })
    employmentStatuses.value = data.map((item) => ({
      value: item.id,
      title: item.name,
    }))
    filters.value.employment_status_id = data[1].id;
  } catch (error) {
    $toast.error("Failed to load employment statuses")
  }
}

const fetchEmploymentTypes = async () => {
  try {
    const { data } = await $api("/employment-types", {
      method: "GET",
      headers: {
        Authorization: `Bearer ${accessToken}`,
      },
    })
    employmentTypes.value = data.map((item) => ({
      value: item.id,
      title: item.name,
    }))
  } catch (error) {
    $toast.error("Failed to load employment types")
  }
}

const fetchEmployeeRoles = async () => {
  loadingEmployeeRoles.value = true
  const { data, meta } = await $api('/employees/logged-in', {
    query: {
      ...filters.value,
      per_page: itemsPerPage.value,
      page: page.value,
    },
  })

  employeeRoles.value = data;
  totalEmployeeRoles.value = meta?.total || 0
  loadingEmployeeRoles.value = false
}

const logout = async (userId) => {
  await $api('/admin/users/force-logout', {
    method: 'POST',
    body: { user_ids: [userId] },
  });
  fetchEmployeeRoles();
};

const logoutAll = async () => {
  const user_ids = employeeRoles.value.map(e => e.user.id);
  if (user_ids.length === 0) return;
  await $api('/admin/users/force-logout', {
    method: 'POST',
    body: { user_ids },
  });
  fetchEmployeeRoles();
};

const resetFilters = async () => {
  filters.value = {
    q: "",
    department_id: null,
    employment_type_id: null,
    employment_status_id: employmentStatuses.value[1]?.id,
    user_status: true,
  }
  itemsPerPage.value = 10
  page.value = 1
}

watch(filters, () => fetchEmployeeRoles(), { deep: true })
watch([page, itemsPerPage], () => fetchEmployeeRoles())

onMounted(() => {
  fetchDepartments();
  fetchEmploymentStatuses();
  fetchEmploymentTypes();
});
</script>
