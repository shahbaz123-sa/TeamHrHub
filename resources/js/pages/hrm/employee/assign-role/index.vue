<template>
  <section>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0 help-center-breadcrumbs"
      :items="[{ title: 'Manage Roles' }, { title: 'Assign Roles' }]"
    />
    <VCard>
      <VCardText>
        <VForm ref="form" @submit.prevent="submit">
          <VRow>
            <VCol cols="12" md="3">
              <VAutocomplete
                v-model="selectedEmployee"
                :items="computedFilteredEmployees"
                label="Select Employees"
                item-title="name"
                item-value="id"
                placeholder="Choose employee"
                clearable
                no-data-text="No employees found"
                :loading="loadingEmployees"
                @update:search="onEmployeeSearch"
              />
            </VCol>
            <VCol cols="12" md="8">
              <VAutocomplete
                v-model="selectedRoles"
                :items="roles"
                label="Assign Roles"
                multiple
                item-title="name"
                item-value="id"
                placeholder="Choose roles"
                no-data-text="No roles found"
                clearable
                :disabled="!selectedEmployee || selectedEmployee?.length === 0"
                :searchable="true"
              />
            </VCol>
            <VCol cols="12" md="1">
              <VCardActions class="pl-0">
                <VBtn color="primary" @click="submit":loading="isSubmitting" :disabled="!selectedRoles || selectedRoles?.length === 0">Save</VBtn>
              </VCardActions>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
    </VCard>
    <VCard class="mt-4">
      <VCardText>
        <VRow>
          <!-- 👉 Select Department -->
          <VCol cols="12" sm="4">
            <VAutocomplete
              v-model="selectedDepartment"
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
            <VAutocomplete v-model="selectedEmploymentType" label="Employment Type" placeholder="Employment Type" :items="employmentTypes" clearable />
          </VCol>

          <!-- 👉 Select Status -->
          <VCol cols="12" sm="3">
            <VAutocomplete v-model="selectedEmploymentStatus" label="Employment Status" placeholder="Status" :items="employmentStatuses" clearable />
          </VCol>
          <VCol cols="12" sm="2">
            <VAutocomplete v-model="selectedUserStatus" label="User Status" placeholder="Status" :items="[{ value: true, title: 'Active' }, { value: false, title: 'In-active' }]" clearable />
          </VCol>
        </VRow>
      </VCardText>
      <VCardText>
          <VRow>
            <VCol cols="4">
              <AppTextField
                v-model="searchQuery"
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
<!--              <VBtn color="success" :loading="isExporting" :disabled="isExporting">-->
<!--                <VIcon start icon="tabler-file-export" />-->
<!--                Export-->
<!--              </VBtn>-->
              <VMenu
                activator="parent"
              >
                <VList>
<!--                  <VListItem-->
<!--                    title="Export PDF"-->
<!--                    prepend-icon="tabler-file-type-pdf"-->
<!--                    @click="exportPDF"-->
<!--                  />-->
<!--                  <VListItem-->
<!--                    title="Export Excel"-->
<!--                    prepend-icon="tabler-file-spreadsheet"-->
<!--                    @click="exportExcel"-->
<!--                  />-->
                </VList>
              </VMenu>
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
                  {{ item.official_email || item.personal_email }}
                </div>
              </div>
            </div>
          </template>

          <template #item.roles="{ item }">
            <div class="d-flex gap-4">
              <VChip
                v-for="role in item.roles"
                :key="role.id"
                label
                size="small"
                color="primary"
                class="font-weight-medium"
              >
                {{ role.name }}
              </VChip>
            </div>
          </template>

          <template #item.department.name="{ item }">
            <div class="text-capitalize text-high-emphasis text-body-1">
              {{ item.department?.name || "N/A" }}
            </div>
          </template>

          <template #item.reporting_to="{ item }">
            <div class="text-high-emphasis text-body-1">
              {{ item.reporting_to?.name || " " }}
            </div>
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
import { humanize } from '@/utils/helpers/str';
import {computed, nextTick, onMounted, ref} from "vue";

const form = ref()
const itemsPerPage = ref(10)
const page = ref(1)
const searchQuery = ref("")
const selectedEmployee = ref([])
const totalEmployeeRoles = ref(0)
const selectedRoles = ref([])
const employees = ref([])
const filteredEmployees = ref([])
const employeeSearchQuery = ref("")
const roles = ref([])
const accessToken = useCookie("accessToken")
const loadingEmployees = ref(false)
const loadingEmployeeRoles = ref(false)
const isSubmitting = ref(false)
const employeeRoles = ref([])
const departments = ref([])
const employmentStatuses = ref([])
const selectedEmploymentStatus = ref()
const selectedUserStatus = ref(true)
const employmentTypes = ref([])
const selectedEmploymentType = ref()
const selectedDepartment = ref()

const headers = [
  { title: 'User', key: 'employee' },
  { title: 'Employee Code', key: 'employee_code' },
  { title: "Department", key: "department.name" },
  { title: "Reporting To", key: "reporting_to" },
  { title: 'Assigned Roles', key: 'roles', sortable: false },
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
    selectedEmploymentStatus.value = data[1].id;
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
  const { data, meta } = await $api('/employees/with-roles', {
    query: {
      q: searchQuery.value,
      department_id: selectedDepartment.value,
      employment_type_id: selectedEmploymentType.value,
      employment_status_id: selectedEmploymentStatus.value,
      user_status: selectedUserStatus.value,
      per_page: itemsPerPage.value,
      page: page.value,
    },
  })

  employeeRoles.value = data;

  // console.log('employeeRoles', meta);
  totalEmployeeRoles.value = meta?.total || 0
  loadingEmployeeRoles.value = false
}

const resetFilters = async () => {
  searchQuery.value = "";
  selectedDepartment.value = null;
  selectedEmploymentType.value = null;
  selectedEmploymentStatus.value = employmentStatuses.value[1]?.value;
  selectedUserStatus.value = true;
  itemsPerPage.value = 10
  page.value = 1
}

watch(selectedEmployee, async (employee) => {
  if (employee) {
    // Fetch roles for this employee
    const data = await $api(`/employees/${employee}/roles`, {
      headers: { Authorization: `Bearer ${accessToken}` }
    });
    
    selectedRoles.value = data.map(role => {
        return roles.value.filter((assignableRole) => assignableRole.id == role.id).length ? role.id : null
    })
  } else {
    selectedRoles.value = []
  }
})

watch([searchQuery, itemsPerPage, selectedEmploymentStatus, selectedUserStatus, selectedDepartment, selectedEmploymentType, page], () => fetchEmployeeRoles())

// Watch for employee search query changes
watch(employeeSearchQuery, (newQuery) => {
  if (newQuery && newQuery.trim() !== '') {
    // Debounce the search to avoid too many API calls
    setTimeout(() => {
      if (employeeSearchQuery.value === newQuery) {
        onEmployeeSearch(newQuery)
      }
    }, 300)
  } else {
    filteredEmployees.value = employees.value
  }
})

const submit = async () => {

  try {
    isSubmitting.value = true
    // API call to assign roles
    await $api("/employees/assign-roles", {
      method: "POST",
      body: {
        employee_id: selectedEmployee.value,
        role_ids: selectedRoles.value,
      },
      headers: {
        Authorization: `Bearer ${accessToken}`,
      },
    });
  
    $toast.success('Roles assigned successfully')
    fetchEmployeeRoles()
    form.value?.reset()
    isSubmitting.value = false
  } catch (error) {
    $toast.error(Object.values(error.response?._data?.errors).slice(0, 2).join("\n"))
    isSubmitting.value = false
  }
};

const fetchEmployees = async (search = '') => {
  try {
    loadingEmployees.value = true

    const { data, error } = await useApi(
      createUrl("/employees", { 
        query: { 
          for_role_assignment: true, 
          search, 
          per_page: 1000 
        } 
      }),
      {
        headers: {
          Authorization: `Bearer ${accessToken.value}`,
        },
      }
    );
    loadingEmployees.value = false

    if (error.value) {
      throw error.value;
    }

    employees.value = data.value.data.map(emp => ({ id: emp.id, name: emp.name }))
    filteredEmployees.value = employees.value

  } catch (err) {
    employees.value = [];
    filteredEmployees.value = [];
    loadingEmployees.value = false
  }
};

// Employee search functionality
const onEmployeeSearch = (searchQuery) => {
  employeeSearchQuery.value = searchQuery
  
  if (!searchQuery || searchQuery.trim() === '') {
    filteredEmployees.value = employees.value
  } else {
    filteredEmployees.value = employees.value.filter(employee => 
      employee.name.toLowerCase().includes(searchQuery.toLowerCase())
    )
  }
}

// Alternative search method for better compatibility
const filterEmployees = (searchQuery) => {
  if (!searchQuery || searchQuery.trim() === '') {
    return employees.value
  }
  return employees.value.filter(employee => 
    employee.name.toLowerCase().includes(searchQuery.toLowerCase())
  )
}

const fetchRoles = async () => {
  const { data } = await $api("/roles?for_role_assignment=true", {
    headers: {
      Authorization: `Bearer ${accessToken}`,
    },
  });
  roles.value = data.map(role => ({
    id: role.id,
    name: humanize(role.name),
  }));
};

// Computed property for filtered employees
const computedFilteredEmployees = computed(() => {
  if (!employeeSearchQuery.value || employeeSearchQuery.value.trim() === '') {
    return employees.value
  }
  
  return employees.value.filter(employee => 
    employee.name.toLowerCase().includes(employeeSearchQuery.value.toLowerCase())
  )
})

onMounted(() => {
  // fetchEmployeeRoles();
  fetchEmployees();
  fetchRoles();
  fetchDepartments();
  fetchEmploymentStatuses();
  fetchEmploymentTypes();
});
</script>
