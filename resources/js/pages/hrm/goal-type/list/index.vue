<script setup>
import { hasPermission } from "@/utils/permission";
import GoalTypeForm from "@/views/apps/hrm/goal-type/list/AddNewGoalTypeDrawer.vue";

const searchQuery = ref("");
const goalTypes = ref([]);
const loading = ref(false);
const error = ref(null);
const isFormOpen = ref(false);
const currentGoalType = ref(null);
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

// Fetch goal types
const fetchGoalTypes = async () => {
  loading.value = true;
  try {
    const response = await $api("/goal-types");
    goalTypes.value = response.data || [];
  } catch (err) {
    error.value = err;
    console.error("Failed to fetch goal types:", err);
  } finally {
    loading.value = false;
  }
};

// Watch for search changes
watch(searchQuery, fetchGoalTypes, { deep: true });

// Initial fetch
onMounted(fetchGoalTypes);

const formatDate = (dateString) => {
  return dateString ? new Date(dateString).toISOString().split("T")[0] : "-";
};

// Handle form submission
const handleSubmit = async (formData) => {
  isSubmitting.value = true;
  try {
    const method = formData.id ? "PUT" : "POST";
    const url = formData.id ? `/goal-types/${formData.id}` : "/goal-types";

    // Create the payload object
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

    await fetchGoalTypes();
    isFormOpen.value = false;

    $toast.success("Goal type saved successfully!");
  } catch (err) {
    $toast.error("Failed to save goal type");
  } finally {
    isSubmitting.value = false;
  }
};

// Edit goal type
const editGoalType = (goalType) => {
  currentGoalType.value = { ...goalType };
  isFormOpen.value = true;
};

// Delete goal type
const deleteGoalType = async (id) => {
  try {
    isDeleting.value = true;
    await $api(`/goal-types/${id}`, { method: "DELETE" });
    await fetchGoalTypes();
    $toast.success("Goal type deleted successfully!");
  } catch (err) {
    $toast.error("Failed to delete goal type");
  } finally {
    isDeleting.value = false;
  }
};

// Open form for new goal type
const newGoalType = () => {
  currentGoalType.value = null;
  isFormOpen.value = true;
};
</script>

<template>
  <section>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0 help-center-breadcrumbs"
      :items="[{ title: 'HR Administration' }, { title: 'Goal Types' }]"
    />
    <VCard>
      <VCardText class="d-flex flex-wrap gap-4">
        <VSpacer />
        <div class="d-flex align-center flex-wrap gap-4">
          <AppTextField
            v-model="searchQuery"
            placeholder="Search goal types"
            style="inline-size: 15.625rem;"
          />
          <VBtn v-if="hasPermission('goal_type.create')" prepend-icon="tabler-plus" @click="newGoalType">
            Add New
          </VBtn>
        </div>
      </VCardText>

      <VDivider />

      <div v-if="loading" class="text-center pa-4">
        <VProgressCircular indeterminate />
        <p>Loading goal types...</p>
      </div>

      <VAlert v-else-if="error" type="error" variant="tonal" class="ma-4">
        {{ error.message || "Failed to load goal types" }}
      </VAlert>

      <VDataTable
        v-else
        :headers="headers"
        :items="goalTypes"
        :search="searchQuery"
        class="text-no-wrap"
      >
        <template #item.name="{ item }">
          <div class="d-flex align-center gap-x-4">
            <VAvatar size="34" variant="tonal" color="primary">
              <span>{{ item.name?.charAt(0)?.toUpperCase() || "G" }}</span>
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
          <IconBtn v-if="hasPermission('goal_type.update')" @click="editGoalType(item)">
            <VIcon icon="tabler-pencil" />
          </IconBtn>

          <IconBtn v-if="hasPermission('goal_type.delete')" @click="deleteGoalType(item.id)" :loading="isDeleting">
            <VIcon icon="tabler-trash" />
          </IconBtn>
        </template>
      </VDataTable>
    </VCard>

    <GoalTypeForm
      v-model:is-open="isFormOpen"
      :goal-type="currentGoalType"
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
