<script setup>
import { hasPermission } from "@/utils/permission";
import AddNewAllowanceDrawer from "@/views/apps/hrm/allowance/list/AddNewAllowanceDrawer.vue";

const searchQuery = ref("");
const allowances = ref([]);
const loading = ref(false);
const error = ref(null);
const isDrawerVisible = ref(false);
const currentAllowance = ref(null);
const isSubmitting = ref(false);

const headers = [
  { title: "Name", key: "name" },
  { title: "Description", key: "description" },
  { title: "Created At", key: "created_at" },
  { title: "Updated At", key: "updated_at" },
  { title: "Actions", key: "actions", sortable: false },
];

const fetchAllowances = async () => {
  loading.value = true;
  try {
    const response = await $api(`/allowances?q=${searchQuery.value}`);
    allowances.value = response.data || [];
  } catch (err) {
    error.value = err;
    $toast.error("Failed to fetch allowances");
  } finally {
    loading.value = false;
  }
};

watch(
  searchQuery,
  () => {
    fetchAllowances();
  },
  { deep: true }
);

onMounted(() => {
  fetchAllowances();
});

const formatDate = (dateString) => {
  return dateString ? new Date(dateString).toISOString().split("T")[0] : "-";
};

const openAddAllowance = () => {
  currentAllowance.value = null;
  isDrawerVisible.value = true;
};

const openEditAllowance = (allowance) => {
  currentAllowance.value = { ...allowance };
  isDrawerVisible.value = true;
};

const handleSubmit = async (formData) => {
  isSubmitting.value = true;
  try {
    const method = formData.id ? "PUT" : "POST";
    const url = formData.id ? `/allowances/${formData.id}` : "/allowances";
    const payload = {
      name: formData.name,
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
    $toast.success(`Allowance ${formData.id ? "updated" : "created"} successfully`);
    await fetchAllowances();
    isDrawerVisible.value = false;
  } catch (err) {
    $toast.error(`Failed to ${formData.id ? "update" : "create"} allowance`);
  } finally {
    isSubmitting.value = false;
  }
};

const deleteAllowance = async (id) => {
  try {
    await $api(`/allowances/${id}`, { method: "DELETE" });
    fetchAllowances();
    $toast.success("Allowance deleted successfully!");
  } catch (err) {
    $toast.error("Failed to delete allowance");
  }
};
</script>

<template>
  <section>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0 help-center-breadcrumbs"
      :items="[{ title: 'HR Administration' }, { title: 'Allowance Options' }]"
    />
    <VCard>
      <VCardText class="d-flex flex-wrap gap-4">
        <VSpacer />
        <div class="d-flex align-center flex-wrap gap-4">
          <!-- Search -->
          <AppTextField
            v-model="searchQuery"
            placeholder="Search allowances"
            style="inline-size: 15.625rem;"
          />
          <VBtn v-if="hasPermission('allowance_option.create')" prepend-icon="tabler-plus" @click="openAddAllowance">
            Add New
          </VBtn>
        </div>
      </VCardText>
      <VDivider />
      <!-- Loading state -->
      <div v-if="loading" class="text-center pa-4">
        <VProgressCircular indeterminate />
        <p>Loading allowances...</p>
      </div>
      <!-- Error state -->
      <VAlert v-else-if="error" type="error" variant="tonal" class="ma-4">
        {{ error.message || "Failed to load allowances" }}
      </VAlert>
      <!-- Data table -->
      <VDataTable
        v-else
        :headers="headers"
        :items="allowances"
        class="text-no-wrap"
      >
        <!-- Name -->
        <template #item.name="{ item }">
          <div class="d-flex align-center gap-x-4">
            <VAvatar size="34" variant="tonal" color="primary">
              <span>{{ item.name?.charAt(0)?.toUpperCase() || "A" }}</span>
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
          <IconBtn v-if="hasPermission('allowance_option.update')" @click="openEditAllowance(item)">
            <VIcon icon="tabler-pencil" />
          </IconBtn>
          <IconBtn v-if="hasPermission('allowance_option.delete')" @click="deleteAllowance(item.id)">
            <VIcon icon="tabler-trash" />
          </IconBtn>
        </template>
      </VDataTable>
    </VCard>
    <!-- Add/Edit Allowance Drawer -->
    <AddNewAllowanceDrawer
      v-model:is-drawer-open="isDrawerVisible"
      :allowance="currentAllowance"
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
