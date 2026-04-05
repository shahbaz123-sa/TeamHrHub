<script setup>
import { hasPermission } from "@/utils/permission";
import AddNewExpenseTypeDrawer from "@/views/apps/hrm/expense-type/list/AddNewExpenseTypeDrawer.vue";

const searchQuery = ref("");
const expenseTypes = ref([]);
const loading = ref(false);
const error = ref(null);
const isFormOpen = ref(false);
const currentExpenseType = ref(null);
const isSubmitting = ref(false);
const isDeleting = ref(false);
const accessToken = useCookie("accessToken");

// Headers
const headers = [
  { title: "Name", key: "name" },
  { title: "Description", key: "description" },
  { title: "Created At", key: "created_at" },
  { title: "Updated At", key: "updated_at" },
  { title: "Actions", key: "actions", sortable: false },
];

// Fetch expense types
const fetchExpenseTypes = async () => {
  loading.value = true;
  try {
    const response = await $api("/expense-types");
    expenseTypes.value = response.data || [];
  } catch (err) {
    error.value = err;
    console.error("Failed to fetch expense types:", err);
  } finally {
    loading.value = false;
  }
};

// Watch for search changes
watch(searchQuery, fetchExpenseTypes, { deep: true });

// Initial fetch
onMounted(fetchExpenseTypes);

const formatDate = (dateString) => {
  return dateString ? new Date(dateString).toISOString().split("T")[0] : "-";
};

// Handle form submission
const handleSubmit = async (formData) => {
  isSubmitting.value = true;
  try {
    const method = formData.id ? "PUT" : "POST";
    const url = formData.id
      ? `/expense-types/${formData.id}`
      : "/expense-types";

    const payload = {
      name: formData.name,
      description: formData.description,
    };

    const response = await $api(url, {
      method,
      body: JSON.stringify(payload),
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",
        Authorization: `Bearer ${accessToken.value}`,
      },
      withCredentials: true,
    });

    await fetchExpenseTypes();
    isFormOpen.value = false;
    $toast.success("Expense type saved successfully!");
  } catch (err) {
    $toast.error("Failed to save expense type");
  } finally {
    isSubmitting.value = false;
  }
};

// Edit expense type
const editExpenseType = (expenseType) => {
  currentExpenseType.value = { ...expenseType };
  isFormOpen.value = true;
};

// Delete expense type
const deleteExpenseType = async (id) => {
  try {
    isDeleting.value = true;
    await $api(`/expense-types/${id}`, { method: "DELETE" });
    await fetchExpenseTypes();
    $toast.success("Expense type deleted successfully!");
  } catch (err) {
    $toast.error("Failed to delete expense type");
  } finally {
    isDeleting.value = false;
  }
};

// Open form for new expense type
const newExpenseType = () => {
  currentExpenseType.value = null;
  isFormOpen.value = true;
};
</script>

<template>
  <section>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0 help-center-breadcrumbs"
      :items="[{ title: 'HR Administration' }, { title: 'Expense Types' }]"
    />
    <VCard>
      <VCardText class="d-flex flex-wrap gap-4">
        <VSpacer />
        <div class="d-flex align-center flex-wrap gap-4">
          <AppTextField
            v-model="searchQuery"
            placeholder="Search expense types"
            style="inline-size: 15.625rem;"
          />
          <VBtn v-if="hasPermission('expense_type.create')" prepend-icon="tabler-plus" @click="newExpenseType">
            Add New
          </VBtn>
        </div>
      </VCardText>

      <VDivider />

      <div v-if="loading" class="text-center pa-4">
        <VProgressCircular indeterminate />
        <p>Loading expense types...</p>
      </div>

      <VAlert v-else-if="error" type="error" variant="tonal" class="ma-4">
        {{ error.message || "Failed to load expense types" }}
      </VAlert>

      <VDataTable
        v-else
        :headers="headers"
        :items="expenseTypes"
        :search="searchQuery"
        class="text-no-wrap"
      >
        <template #item.name="{ item }">
          <div class="d-flex align-center gap-x-4">
            <VAvatar size="34" variant="tonal" color="primary">
              <span>{{ item.name?.charAt(0)?.toUpperCase() || "E" }}</span>
            </VAvatar>
            <div class="d-flex flex-column">
              <h6 class="text-base font-weight-medium text-high-emphasis">
                {{ item.name }}
              </h6>
            </div>
          </div>
        </template>

        <template #item.description="{ item }">
          <div class="text-high-emphasis text-body-1">
            {{ item.description || "-" }}
          </div>
        </template>

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

        <template #item.actions="{ item }">
          <IconBtn v-if="hasPermission('expense_type.update')" @click="editExpenseType(item)">
            <VIcon icon="tabler-pencil" />
          </IconBtn>

          <IconBtn v-if="hasPermission('expense_type.delete')" @click="deleteExpenseType(item.id)" :loading="isDeleting">
            <VIcon icon="tabler-trash" />
          </IconBtn>
        </template>
      </VDataTable>
    </VCard>

    <AddNewExpenseTypeDrawer
      v-model:is-open="isFormOpen"
      :expense-type="currentExpenseType"
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
