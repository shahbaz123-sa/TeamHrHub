<script setup>
import { hasPermission } from "@/utils/permission";
import AddNewTerminationTypeDrawer from "@/views/apps/hrm/termination-type/list/AddNewTerminationTypeDrawer.vue";

const searchQuery = ref("");
const terminationTypes = ref([]);
const loading = ref(false);
const error = ref(null);
const isFormOpen = ref(false);
const currentTerminationType = ref(null);
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

// Fetch termination types
const fetchTerminationTypes = async () => {
  loading.value = true;
  try {
    const response = await $api("/termination-types");
    terminationTypes.value = response.data || [];
  } catch (err) {
    error.value = err;
    console.error("Failed to fetch termination types:", err);
  } finally {
    loading.value = false;
  }
};

// Watch for search changes
watch(searchQuery, fetchTerminationTypes, { deep: true });

// Initial fetch
onMounted(fetchTerminationTypes);

const formatDate = (dateString) => {
  return dateString ? new Date(dateString).toISOString().split("T")[0] : "-";
};

// Handle form submission
const handleSubmit = async (formData) => {
  isSubmitting.value = true;
  try {
    const method = formData.id ? "PUT" : "POST";
    const url = formData.id
      ? `/termination-types/${formData.id}`
      : "/termination-types";

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

    await fetchTerminationTypes();
    isFormOpen.value = false;
    $toast.success("Termination type saved successfully!");
  } catch (err) {
    $toast.error("Failed to save termination type");

    if (err.response) {
      console.error("Response error:", err.response.data);
    }
  } finally {
    isSubmitting.value = false;
  }
};

// Edit termination type
const editTerminationType = (terminationType) => {
  currentTerminationType.value = { ...terminationType };
  isFormOpen.value = true;
};

// Delete termination type
const deleteTerminationType = async (id) => {
  try {
    isDeleting.value = true;
    await $api(`/termination-types/${id}`, { method: "DELETE" });
    await fetchTerminationTypes();
    $toast.success("Termination type deleted successfully!");
  } catch (err) {
    $toast.error("Failed to delete termination type");
  } finally {
    isDeleting.value = false;
  }
};

// Open form for new termination type
const newTerminationType = () => {
  currentTerminationType.value = null;
  isFormOpen.value = true;
};
</script>

<template>
  <section>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0 help-center-breadcrumbs"
      :items="[{ title: 'HR Administration' }, { title: 'Termination Type' }]"
    />
    <VCard>
      <VCardText class="d-flex flex-wrap gap-4">
        <VSpacer />
        <div class="d-flex align-center flex-wrap gap-4">
          <AppTextField
            v-model="searchQuery"
            placeholder="Search termination types"
            style="inline-size: 15.625rem;"
          />
          <VBtn v-if="hasPermission('termination_type.create')" prepend-icon="tabler-plus" @click="newTerminationType">
            Add New
          </VBtn>
        </div>
      </VCardText>

      <VDivider />

      <div v-if="loading" class="text-center pa-4">
        <VProgressCircular indeterminate />
        <p>Loading termination types...</p>
      </div>

      <VAlert v-else-if="error" type="error" variant="tonal" class="ma-4">
        {{ error.message || "Failed to load termination types" }}
      </VAlert>

      <VDataTable
        v-else
        :headers="headers"
        :items="terminationTypes"
        :search="searchQuery"
        class="text-no-wrap"
      >
        <template #item.name="{ item }">
          <div class="d-flex align-center gap-x-4">
            <VAvatar size="34" variant="tonal" color="primary">
              <span>{{ item.name?.charAt(0)?.toUpperCase() || "T" }}</span>
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
          <IconBtn v-if="hasPermission('termination_type.update')" @click="editTerminationType(item)">
            <VIcon icon="tabler-pencil" />
          </IconBtn>

          <IconBtn
            v-if="hasPermission('termination_type.delete')"
            @click="deleteTerminationType(item.id)"
            :loading="isDeleting"
          >
            <VIcon icon="tabler-trash" />
          </IconBtn>
        </template>
      </VDataTable>
    </VCard>

    <AddNewTerminationTypeDrawer
      v-model:is-open="isFormOpen"
      :termination-type="currentTerminationType"
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
