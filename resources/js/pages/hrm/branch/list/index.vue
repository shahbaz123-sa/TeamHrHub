<script setup>
import { hasPermission } from "@/utils/permission";
import AddNewBranchDrawer from "@/views/apps/branch/list/AddNewBranchDrawer.vue";

const searchQuery = ref("");

// Initialize branches data
const branches = ref([]);
const loading = ref(false);
const error = ref(null);

// Headers
const headers = [
  { title: "Name", key: "name" },
  { title: "Address", key: "address" },
  { title: "Phone", key: "phone" },
  { title: "Email", key: "email" },
  { title: "Created At", key: "created_at" },
  { title: "Updated At", key: "updated_at" },
  { title: "Actions", key: "actions", sortable: false },
];

// Fetch branches
const fetchBranches = async () => {
  loading.value = true;
  try {
    const response = await $api(`/branches?q=${searchQuery.value}`);
    branches.value = response.data || [];
  } catch (err) {
    error.value = err;
    console.error("Failed to fetch branches:", err);
  } finally {
    loading.value = false;
  }
};

// Watch for search changes
watch(
  searchQuery,
  () => {
    fetchBranches();
  },
  { deep: true }
);

// Initial fetch
onMounted(() => {
  fetchBranches();
});

const formatDate = (dateString) => {
  return dateString ? new Date(dateString).toISOString().split("T")[0] : "-";
};

// Drawer control
const isDrawerVisible = ref(false);
const currentBranch = ref(null);
const isSubmitting = ref(false);

const openAddBranch = () => {
  currentBranch.value = null;
  isDrawerVisible.value = true;
};

const openEditBranch = (branch) => {
  currentBranch.value = { ...branch };
  isDrawerVisible.value = true;
};

const handleSubmit = async (formData) => {
  isSubmitting.value = true;
  try {
    const method = formData.id ? "PUT" : "POST";
    const url = formData.id ? `/branches/${formData.id}` : "/branches";
    const payload = {...formData};
    await $api(url, {
      method,
      body: JSON.stringify(payload),
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
    });
    $toast.success(`Branch ${formData.id ? "updated" : "created"} successfully`);
    await fetchBranches();
    isDrawerVisible.value = false;
  } catch (err) {
    $toast.error(`Failed to ${formData.id ? "update" : "create"} branch`);
  } finally {
    isSubmitting.value = false;
  }
};

const deleteBranch = async (id) => {
  try {
    await $api(`/branches/${id}`, { method: "DELETE" });
    fetchBranches();
    $toast.success("Branch deleted successfully!");
  } catch (err) {
    $toast.error("Failed to delete branch:", err);
  }
};
</script>

<template>
  <section>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0 help-center-breadcrumbs"
      :items="[{ title: 'HR Administration' }, { title: 'Branches' }]"
    />
    <VCard>
      <VCardText class="d-flex flex-wrap gap-4">
        <VSpacer />

        <div class="d-flex align-center flex-wrap gap-4">
          <!-- Search -->
          <AppTextField
            v-model="searchQuery"
            placeholder="Search Branch"
            style="inline-size: 15.625rem;"
          />

          <VBtn v-if="hasPermission('branch.create')" prepend-icon="tabler-plus" @click="openAddBranch">
            Add New
          </VBtn>
        </div>
      </VCardText>

      <VDivider />

      <!-- Loading state -->
      <div v-if="loading" class="text-center pa-4">
        <VProgressCircular indeterminate />
        <p>Loading branches...</p>
      </div>

      <!-- Error state -->
      <VAlert v-else-if="error" type="error" variant="tonal" class="ma-4">
        {{ error.message || "Failed to load branches" }}
      </VAlert>

      <!-- Data table -->
      <VDataTable
        v-else
        :headers="headers"
        :items="branches"
        class="text-no-wrap"
      >
        <!-- Name -->
        <template #item.name="{ item }">
          <div class="d-flex align-center gap-x-4">
            <VAvatar size="34" variant="tonal" color="primary">
              <span>{{ item.name?.charAt(0)?.toUpperCase() || "B" }}</span>
            </VAvatar>
            <div class="d-flex flex-column">
              <h6 class="text-base font-weight-medium text-high-emphasis">
                {{ item.name }}
              </h6>
            </div>
          </div>
        </template>

        <!-- Address -->
        <template #item.address="{ item }">
          <div class="text-high-emphasis text-body-1">
            {{ item.address || "-" }}
          </div>
        </template>

        <!-- Phone -->
        <template #item.phone="{ item }">
          <div class="text-high-emphasis text-body-1">
            {{ item.phone || "-" }}
          </div>
        </template>

        <!-- Email -->
        <template #item.email="{ item }">
          <div class="text-high-emphasis text-body-1">
            {{ item.email || "-" }}
          </div>
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
          <IconBtn v-if="hasPermission('branch.update')" @click="openEditBranch(item)">
            <VIcon icon="tabler-pencil" />
          </IconBtn>
          <IconBtn v-if="hasPermission('branch.delete')" @click="deleteBranch(item.id)">
            <VIcon icon="tabler-trash" />
          </IconBtn>
        </template>
      </VDataTable>
    </VCard>

    <!-- Add/Edit Branch Drawer -->
    <AddNewBranchDrawer
      v-model:is-drawer-open="isDrawerVisible"
      :branch="currentBranch"
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
