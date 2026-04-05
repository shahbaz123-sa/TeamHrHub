<script setup>
import { hasPermission } from "@/utils/permission";
import AddNewLoanOptionDrawer from "@/views/apps/hrm/loan-option/list/AddNewLoanOptionDrawer.vue";

const searchQuery = ref("");
const loanOptions = ref([]);
const loading = ref(false);
const error = ref(null);
const isDrawerVisible = ref(false);
const currentLoanOption = ref(null);
const isSubmitting = ref(false);

const headers = [
  { title: "Name", key: "name" },
  { title: "Description", key: "description" },
  { title: "Created At", key: "created_at" },
  { title: "Updated At", key: "updated_at" },
  { title: "Actions", key: "actions", sortable: false },
];

const fetchLoanOptions = async () => {
  loading.value = true;
  try {
    const response = await $api(`/loan-options?q=${searchQuery.value}`);
    loanOptions.value = response.data || [];
  } catch (err) {
    error.value = err;
    $toast.error("Failed to fetch loan options");
  } finally {
    loading.value = false;
  }
};

watch(
  searchQuery,
  () => {
    fetchLoanOptions();
  },
  { deep: true }
);

onMounted(() => {
  fetchLoanOptions();
});

const formatDate = (dateString) => {
  return dateString ? new Date(dateString).toISOString().split("T")[0] : "-";
};

const openAddLoanOption = () => {
  currentLoanOption.value = null;
  isDrawerVisible.value = true;
};

const openEditLoanOption = (loanOption) => {
  currentLoanOption.value = { ...loanOption };
  isDrawerVisible.value = true;
};

const handleSubmit = async (formData) => {
  isSubmitting.value = true;
  try {
    const method = formData.id ? "PUT" : "POST";
    const url = formData.id ? `/loan-options/${formData.id}` : "/loan-options";
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
    $toast.success(`Loan option ${formData.id ? "updated" : "created"} successfully`);
    await fetchLoanOptions();
    isDrawerVisible.value = false;
  } catch (err) {
    $toast.error(`Failed to ${formData.id ? "update" : "create"} loan option`);
  } finally {
    isSubmitting.value = false;
  }
};

const deleteLoanOption = async (id) => {
  try {
    await $api(`/loan-options/${id}`, { method: "DELETE" });
    fetchLoanOptions();
    $toast.success("Loan option deleted successfully!");
  } catch (err) {
    $toast.error("Failed to delete loan option");
  }
};
</script>

<template>
  <section>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0 help-center-breadcrumbs"
      :items="[{ title: 'HR Administration' }, { title: 'Loan Options' }]"
    />
    <VCard>
      <VCardText class="d-flex flex-wrap gap-4">
        <VSpacer />
        <div class="d-flex align-center flex-wrap gap-4">
          <!-- Search -->
          <AppTextField
            v-model="searchQuery"
            placeholder="Search loan options"
            style="inline-size: 15.625rem;"
          />
          <VBtn v-if="hasPermission('loan_option.create')" prepend-icon="tabler-plus" @click="openAddLoanOption">
            Add New
          </VBtn>
        </div>
      </VCardText>
      <VDivider />
      <!-- Loading state -->
      <div v-if="loading" class="text-center pa-4">
        <VProgressCircular indeterminate />
        <p>Loading loan options...</p>
      </div>
      <!-- Error state -->
      <VAlert v-else-if="error" type="error" variant="tonal" class="ma-4">
        {{ error.message || "Failed to load loan options" }}
      </VAlert>
      <!-- Data table -->
      <VDataTable
        v-else
        :headers="headers"
        :items="loanOptions"
        class="text-no-wrap"
      >
        <!-- Name -->
        <template #item.name="{ item }">
          <div class="d-flex align-center gap-x-4">
            <VAvatar size="34" variant="tonal" color="primary">
              <span>{{ item.name?.charAt(0)?.toUpperCase() || "L" }}</span>
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
          <IconBtn v-if="hasPermission('loan_option.update')" @click="openEditLoanOption(item)">
            <VIcon icon="tabler-pencil" />
          </IconBtn>
          <IconBtn v-if="hasPermission('loan_option.delete')" @click="deleteLoanOption(item.id)">
            <VIcon icon="tabler-trash" />
          </IconBtn>
        </template>
      </VDataTable>
    </VCard>
    <!-- Add/Edit Loan Option Drawer -->
    <AddNewLoanOptionDrawer
      v-model:is-drawer-open="isDrawerVisible"
      :loan-option="currentLoanOption"
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
