<script setup>
import { hasPermission } from "@/utils/permission";
import AddNewDesignationDrawer from "@/views/apps/designation/list/AddNewDesignationDrawer.vue";

const searchQuery = ref("");
const designations = ref([]);
const loading = ref(false);
const error = ref(null);
const isDrawerVisible = ref(false);
const currentDesignation = ref(null);
const isSubmitting = ref(false);

const headers = [
  { title: "Title", key: "title" },
  { title: "Description", key: "description" },
  { title: "Created At", key: "created_at" },
  { title: "Updated At", key: "updated_at" },
  { title: "Actions", key: "actions", sortable: false },
];

const fetchDesignations = async () => {
  loading.value = true;
  try {
    const response = await $api(`/api/designations?q=${searchQuery.value}`);
    designations.value = response.data || [];
  } catch (err) {
    error.value = err;
    $toast.error("Failed to fetch designations");
  } finally {
    loading.value = false;
  }
};

watch(
  searchQuery,
  () => {
    fetchDesignations();
  },
  { deep: true }
);

onMounted(() => {
  fetchDesignations();
});

const formatDate = (dateString) => {
  return dateString ? new Date(dateString).toISOString().split("T")[0] : "-";
};

const openAddDesignation = () => {
  currentDesignation.value = null;
  isDrawerVisible.value = true;
};

const openEditDesignation = (designation) => {
  currentDesignation.value = { ...designation };
  isDrawerVisible.value = true;
};

const handleSubmit = async (formData) => {
  isSubmitting.value = true;
  try {
    const method = formData.id ? "PUT" : "POST";
    const url = formData.id ? `/api/designations/${formData.id}` : "/api/designations";
    const payload = {
      title: formData.title,
      description: formData.description,
    };
    await $api(url, {
      method,
      body: JSON.stringify(payload),
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
    });
    $toast.success(`Designation ${formData.id ? "updated" : "created"} successfully`);
    await fetchDesignations();
    isDrawerVisible.value = false;
  } catch (err) {
    $toast.error(`Failed to ${formData.id ? "update" : "create"} designation`);
  } finally {
    isSubmitting.value = false;
  }
};

const deleteDesignation = async (id) => {
  try {
    await $api(`/api/designations/${id}`, { method: "DELETE" });
    fetchDesignations();
    $toast.success("Designation deleted successfully!");
  } catch (err) {
    $toast.error("Failed to delete designation");
  }
};
</script>

<template>
  <section>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0 help-center-breadcrumbs"
      :items="[{ title: 'HR Administration' }, { title: 'Designations' }]"
    />
    <VCard>
      <VCardText class="d-flex flex-wrap gap-4">
        <VSpacer />
        <div class="d-flex align-center flex-wrap gap-4">
          <!-- Search -->
          <AppTextField
            v-model="searchQuery"
            placeholder="Search Designation"
            style="inline-size: 15.625rem;"
          />
          <VBtn v-if="hasPermission('designation.create')" prepend-icon="tabler-plus" @click="openAddDesignation">
            Add New
          </VBtn>
        </div>
      </VCardText>
      <VDivider />
      <!-- Loading state -->
      <div v-if="loading" class="text-center pa-4">
        <VProgressCircular indeterminate />
        <p>Loading designations...</p>
      </div>
      <!-- Error state -->
      <VAlert v-else-if="error" type="error" variant="tonal" class="ma-4">
        {{ error.message || "Failed to load designations" }}
      </VAlert>
      <!-- Data table -->
      <VDataTable
        v-else
        :headers="headers"
        :items="designations"
        class="text-no-wrap"
      >
        <!-- Title -->
        <template #item.title="{ item }">
          <div class="d-flex align-center gap-x-4">
            <VAvatar size="34" variant="tonal" color="primary">
              <span>{{ item.title?.charAt(0)?.toUpperCase() || "D" }}</span>
            </VAvatar>
            <div class="d-flex flex-column">
              <h6 class="text-base font-weight-medium text-high-emphasis">
                {{ item.title }}
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
          <IconBtn v-if="hasPermission('designation.update')" @click="openEditDesignation(item)">
            <VIcon icon="tabler-pencil" />
          </IconBtn>
          <IconBtn v-if="hasPermission('designation.delete')" @click="deleteDesignation(item.id)">
            <VIcon icon="tabler-trash" />
          </IconBtn>
        </template>
      </VDataTable>
    </VCard>
    <!-- Add/Edit Designation Drawer -->
    <AddNewDesignationDrawer
      v-model:is-drawer-open="isDrawerVisible"
      :designation="currentDesignation"
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
