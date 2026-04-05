<script setup>
import { hasPermission } from "@/utils/permission";
import AddNewLeaveTypeDrawer from "@/views/apps/hrm/leave-type/list/AddNewLeaveTypeDrawer.vue";
import { useToast } from "vue-toast-notification";
import "vue-toast-notification/dist/theme-sugar.css";

const $toast = useToast();
const searchQuery = ref("");
const leaveTypes = ref([]);
const loading = ref(false);
const error = ref(null);
const isFormOpen = ref(false);
const currentLeaveType = ref(null);
const isSubmitting = ref(false);
const isDeleting = ref(false);
const isReordering = ref(false);
const accessToken = useCookie("accessToken");

// Headers
const headers = [
  { title: "Name", key: "name" },
  { title: "Description", key: "description" },
  { title: "Quota", key: "quota" },
  { title: "Created At", key: "created_at" },
  { title: "Updated At", key: "updated_at" },
  { title: "Order", key: "sort_order", align: 'center'},
  { title: "Actions", key: "actions", sortable: false },
];

// Fetch leave types
const fetchLeaveTypes = async () => {
  loading.value = true;
  try {
    const response = await $api("/leave-types");
    leaveTypes.value = response.data || [];
  } catch (err) {
    error.value = err;
    console.error("Failed to fetch leave types:", err);
    $toast.error("Failed to load leave types");
  } finally {
    loading.value = false;
  }
};

// Watch for search changes
watch(searchQuery, fetchLeaveTypes, { deep: true });

// Initial fetch
onMounted(fetchLeaveTypes);

const formatDate = (dateString) => {
  return dateString ? new Date(dateString).toISOString().split("T")[0] : "-";
};

// Handle form submission
const handleSubmit = async (formData) => {
  isSubmitting.value = true;
  try {
    const method = formData.id ? "PUT" : "POST";
    const url = formData.id ? `/leave-types/${formData.id}` : "/leave-types";

    const payload = {
      name: formData.name,
      description: formData.description,
      quota: formData.quota,
    };

    await $api(url, {
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

    await fetchLeaveTypes();
    isFormOpen.value = false;
    $toast.success("Leave type saved successfully!");
  } catch (err) {
    console.error("Failed to save leave type:", err);
    $toast.error("Failed to save leave type");

    if (err.response) {
      console.error("Response error:", err.response.data);
    }
  } finally {
    isSubmitting.value = false;
  }
};

// Edit leave type
const editLeaveType = (leaveType) => {
  currentLeaveType.value = { ...leaveType };
  isFormOpen.value = true;
};

// Delete leave type
const deleteLeaveType = async (id) => {
  try {
    isDeleting.value = true;
    await $api(`/leave-types/${id}`, { method: "DELETE" });
    await fetchLeaveTypes();
    $toast.success("Leave type deleted successfully!");
  } catch (err) {
    console.error("Failed to delete leave type:", err);
    $toast.error("Failed to delete leave type");
  } finally {
    isDeleting.value = false;
  }
};

// Open form for new leave type
const newLeaveType = () => {
  currentLeaveType.value = null;
  isFormOpen.value = true;
};

const reorderLeaveType = async (leaveType, direction) => {
  if (!leaveType?.id) return;
  if (isReordering.value) return;

  // Avoid confusing behavior when the table is filtered.
  if (searchQuery.value) {
    $toast.error("Clear search to reorder leave types");
    return;
  }

  isReordering.value = true;
  try {
    await $api(`/leave-types/${leaveType.id}/reorder`, {
      method: "POST",
      body: JSON.stringify({ direction }),
      withCredentials: true,
    });

    await fetchLeaveTypes();
    $toast.success("Order updated");
  } catch (err) {
    console.error("Failed to reorder leave type:", err);
    $toast.error("Failed to update order");
  } finally {
    isReordering.value = false;
  }
};
</script>

<template>
  <section>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0 help-center-breadcrumbs"
      :items="[{ title: 'HR Administration' }, { title: 'Leave Types' }]"
    />
    <VCard>
      <VCardText class="d-flex flex-wrap gap-4">
        <VSpacer />
        <div class="d-flex align-center flex-wrap gap-4">
          <AppTextField
            v-model="searchQuery"
            placeholder="Search leave types"
            style="inline-size: 15.625rem;"
          />
          <VBtn v-if="hasPermission('leave_type.create')" prepend-icon="tabler-plus" @click="newLeaveType">
            Add New
          </VBtn>
        </div>
      </VCardText>

      <VDivider />

      <div v-if="loading" class="text-center pa-4">
        <VProgressCircular indeterminate />
        <p>Loading leave types...</p>
      </div>

      <VAlert v-else-if="error" type="error" variant="tonal" class="ma-4">
        {{ error.message || "Failed to load leave types" }}
      </VAlert>

      <VDataTable
        v-else
        :headers="headers"
        :items="leaveTypes"
        :search="searchQuery"
        class="text-no-wrap"
      >
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

        <template #item.description="{ item }">
          <div class="text-high-emphasis text-body-1">
            {{ item.description || "-" }}
          </div>
        </template>

        <template #item.quota="{ item }">
          <VChip color="primary" size="small" variant="tonal">
            {{ item.quota || 0 }} days
          </VChip>
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

        <template #item.sort_order="{ item, index }">
          <div class="d-flex align-center justify-center">
            <VChip color="primary" size="small" variant="outlined">
              {{ item.sort_order ?? '-' }}
            </VChip>

            <IconBtn
              v-if="hasPermission('leave_type.update')"
              :disabled="isReordering || index === 0 || !!searchQuery"
              @click="reorderLeaveType(item, 'up')"
            >
              <VIcon icon="tabler-arrow-up" />
            </IconBtn>

            <IconBtn
              v-if="hasPermission('leave_type.update')"
              class="order-arrow"
              :disabled="isReordering || index === leaveTypes.length - 1 || !!searchQuery"
              @click="reorderLeaveType(item, 'down')"
            >
              <VIcon icon="tabler-arrow-down" />
            </IconBtn>
          </div>
        </template>

        <template #item.actions="{ item }">
          <IconBtn v-if="hasPermission('leave_type.update')" @click="editLeaveType(item)">
            <VIcon icon="tabler-pencil" />
          </IconBtn>

          <IconBtn v-if="hasPermission('leave_type.delete')" @click="deleteLeaveType(item.id)" :loading="isDeleting">
            <VIcon icon="tabler-trash" />
          </IconBtn>
        </template>
      </VDataTable>
    </VCard>

    <AddNewLeaveTypeDrawer
      v-model:is-open="isFormOpen"
      :leave-type="currentLeaveType"
      :loading="isSubmitting"
      @submit="handleSubmit"
    />
  </section>
</template>

<style lang="scss">
.text-capitalize {
  text-transform: capitalize;
}

.order-arrow {
  width: 16px !important;
}

</style>
