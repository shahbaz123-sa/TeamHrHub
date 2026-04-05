<script setup>
import { hasPermission } from "@/utils/permission";
import AddNewDepartmentDrawer from "@/views/apps/department/list/AddNewDepartmentDrawer.vue";

const searchQuery = ref("");
const selectedStatus = ref();

// Initialize departments data
const departments = ref([]);
const loading = ref(false);
const error = ref(null);
const accessToken = useCookie("accessToken");
// Headers
const headers = [
  { title: "Name", key: "name" },
  { title: "Description", key: "description" },
  { title: "Status", key: "status" },
  { title: "Created At", key: "created_at" },
  { title: "Updated At", key: "updated_at" },
  { title: "Actions", key: "actions", sortable: false },
];

// Fetch departments with filters
const fetchDepartments = async () => {
  loading.value = true;
  try {
    const params = {};
    if (searchQuery.value) params.q = searchQuery.value;
    if (selectedStatus.value !== undefined && selectedStatus.value !== null) params.status = selectedStatus.value;
    const response = await $api("/departments", {
      params,
      headers: {
        Authorization: `Bearer ${accessToken.value}`,
      },
    });
    departments.value = response.data || [];
  } catch (err) {
    error.value = err;
    console.error("Failed to fetch departments:", err);
  } finally {
    loading.value = false;
  }
};

// Watch for search and status changes
watch(
  [searchQuery, selectedStatus],
  () => {
    fetchDepartments();
  },
  { deep: true }
);

// Initial fetch
onMounted(() => {
  fetchDepartments();
});

// Status handling
const statuses = [
  { title: "Active", value: true },
  { title: "Inactive", value: false },
];

const resolveStatusVariant = (status) => {
  return status ? "success" : "error";
};

const formatStatus = (status) => {
  return status ? "Active" : "Inactive";
};

const formatDate = (dateString) => {
  return dateString ? new Date(dateString).toISOString().split("T")[0] : "-";
};

// Drawer control
const isDrawerVisible = ref(false);
const currentDepartment = ref(null);
const isSubmitting = ref(false);

const openAddDepartment = () => {
  currentDepartment.value = null;
  isDrawerVisible.value = true;
};

const openEditDepartment = (department) => {
  currentDepartment.value = { ...department };
  isDrawerVisible.value = true;
};

const handleSubmit = async (formData) => {
  isSubmitting.value = true;
  try {
    const method = formData.id ? "PUT" : "POST";
    const url = formData.id ? `/departments/${formData.id}` : "/departments";
    const payload = {
      name: formData.name,
      description: formData.description,
      status: formData.status,
    };
    await $api(url, {
      method,
      body: JSON.stringify(payload),
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
        Authorization: `Bearer ${accessToken.value}`,
      },
    });
    await fetchDepartments();
    isDrawerVisible.value = false;
    $toast.success(`Department ${formData.id ? 'updated' : 'created'} successfully!`);
  } catch (err) {
    const message = err?._data?.message || err?.statusText || "Failed to save department";
    $toast.error(message);
  }
  finally {
    isSubmitting.value = false;
  }
};

const deleteDepartment = async (id) => {
  try {
    await $api(`/departments/${id}`, {
      method: "DELETE",
      headers: {
        Authorization: `Bearer ${accessToken.value}`,
      },
    });
    await fetchDepartments();
    $toast.success("Department deleted successfully!");
  } catch (err) {
    $toast.error("Failed to delete department");
  }
};
</script>

<template>
  <section>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0 help-center-breadcrumbs"
      :items="[{ title: 'HR Administration' }, { title: 'Departments' }]"
    />
    <VCard>
      <VCardText class="d-flex flex-wrap gap-4">
        <VSpacer />

        <div class="d-flex align-center flex-wrap gap-4">
          <!-- Search -->
          <AppTextField
            v-model="searchQuery"
            placeholder="Search Department"
            style="inline-size: 15.625rem;"
          />

          <!-- Status filter -->
          <AppSelect
            v-model="selectedStatus"
            placeholder="Status"
            :items="statuses"
            clearable
            clear-icon="tabler-x"
            style="inline-size: 10rem;"
          />

          <VBtn v-if="hasPermission('department.create')" prepend-icon="tabler-plus" @click="openAddDepartment">
            Add New
          </VBtn>
        </div>
      </VCardText>

      <VDivider />

      <!-- Loading state -->
      <div v-if="loading" class="text-center pa-4">
        <VProgressCircular indeterminate />
        <p>Loading departments...</p>
      </div>

      <!-- Error state -->
      <VAlert v-else-if="error" type="error" variant="tonal" class="ma-4">
        {{ error.message || "Failed to load departments" }}
      </VAlert>

      <!-- Data table -->
      <VDataTable
        v-else
        :headers="headers"
        :items="departments"
        class="text-no-wrap"
      >
        <!-- Name -->
        <template #item.name="{ item }">
          <div class="d-flex align-center gap-x-4">
            <VAvatar size="34" variant="tonal" color="primary">
              <span>{{ item.name?.charAt(0)?.toUpperCase() || "D" }}</span>
            </VAvatar>
            <div class="d-flex flex-column">
              <h6 class="text-base font-weight-medium text-high-emphasis">
                {{ item.name }}
              </h6>
            </div>
          </div>
        </template>

        <!-- Description -->
        <template #item.description="{ item }">
          <div class="text-high-emphasis text-body-1">
            {{ item.description || "-" }}
          </div>
        </template>

        <!-- Status -->
        <template #item.status="{ item }">
          <VChip
            :color="resolveStatusVariant(item.status)"
            size="small"
            label
            class="text-capitalize"
          >
            {{ formatStatus(item.status) }}
          </VChip>
        </template>

        <!-- Dates -->
        <template #item.created_at="{ item }">
          <div class="text-high-emphasis text-body-1">
            {{ formatDate(item.created_at) }}
          </div>
        </template>
        <template #item.updated_at="{ item }">
          <div class="text-high-emphasis text-body-1">
            {{ formatDate(item.updated_at) }}
          </div>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <IconBtn v-if="hasPermission('department.update')" @click="openEditDepartment(item)">
            <VIcon icon="tabler-pencil" />
          </IconBtn>
          <IconBtn v-if="hasPermission('department.delete')" @click="deleteDepartment(item.id)">
            <VIcon icon="tabler-trash" />
          </IconBtn>
        </template>
      </VDataTable>
    </VCard>

    <!-- Add/Edit Department Drawer -->
    <AddNewDepartmentDrawer
      v-model:is-drawer-open="isDrawerVisible"
      :department="currentDepartment"
      :loading="isSubmitting"
      @submit="handleSubmit"
    />
  </section>
</template>

<style lang="scss">
.text-capitalize {
  text-transform: capitalize;
}
</style>
