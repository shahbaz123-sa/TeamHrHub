<script setup>
import { hasPermission } from "@/utils/permission";
import AddNewCompetencyDrawer from "@/views/apps/hrm/competency/list/AddNewCompetencyDrawer.vue";

const searchQuery = ref("");
const competencies = ref([]);
const loading = ref(false);
const error = ref(null);
const isFormOpen = ref(false);
const currentCompetency = ref(null);
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

// Fetch competencies
const fetchCompetencies = async () => {
  loading.value = true;
  try {
    const response = await $api("/competencies");
    competencies.value = response.data || [];
  } catch (err) {
    error.value = err;
    console.error("Failed to fetch competencies:", err);
  } finally {
    loading.value = false;
  }
};

// Watch for search changes
watch(searchQuery, fetchCompetencies, { deep: true });

// Initial fetch
onMounted(fetchCompetencies);

const formatDate = (dateString) => {
  return dateString ? new Date(dateString).toISOString().split("T")[0] : "-";
};

// Handle form submission
const handleSubmit = async (formData) => {
  isSubmitting.value = true;
  try {
    const method = formData.id ? "PUT" : "POST";
    const url = formData.id ? `/competencies/${formData.id}` : "/competencies";

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

    await fetchCompetencies();
    isFormOpen.value = false;
    $toast.success("Competency saved successfully!");
  } catch (err) {
    $toast.error("Failed to save competency");
  } finally {
    isSubmitting.value = false;
  }
};

// Edit competency
const editCompetency = (competency) => {
  currentCompetency.value = { ...competency };
  isFormOpen.value = true;
};

// Delete competency
const deleteCompetency = async (id) => {
  try {
    isDeleting.value = true;
    await $api(`/competencies/${id}`, { method: "DELETE" });
    await fetchCompetencies();
    $toast.success("Competency deleted successfully!");
  } catch (err) {
    $toast.error("Failed to delete competency");
  } finally {
    isDeleting.value = false;
  }
};

// Open form for new competency
const newCompetency = () => {
  currentCompetency.value = null;
  isFormOpen.value = true;
};
</script>

<template>
  <section>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0 help-center-breadcrumbs"
      :items="[{ title: 'HR Administration' }, { title: 'Competencies' }]"
    />
    <VCard>
      <VCardText class="d-flex flex-wrap gap-4">
        <VSpacer />
        <div class="d-flex align-center flex-wrap gap-4">
          <AppTextField
            v-model="searchQuery"
            placeholder="Search competencies"
            style="inline-size: 15.625rem;"
          />
          <VBtn v-if="hasPermission('competency.create')" prepend-icon="tabler-plus" @click="newCompetency">
            Add New
          </VBtn>
        </div>
      </VCardText>

      <VDivider />

      <div v-if="loading" class="text-center pa-4">
        <VProgressCircular indeterminate />
        <p>Loading competencies...</p>
      </div>

      <VAlert v-else-if="error" type="error" variant="tonal" class="ma-4">
        {{ error.message || "Failed to load competencies" }}
      </VAlert>

      <VDataTable
        v-else
        :headers="headers"
        :items="competencies"
        :search="searchQuery"
        class="text-no-wrap"
      >
        <template #item.name="{ item }">
          <div class="d-flex align-center gap-x-4">
            <VAvatar size="34" variant="tonal" color="primary">
              <span>{{ item.name?.charAt(0)?.toUpperCase() || "C" }}</span>
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
          <IconBtn v-if="hasPermission('competency.update')" @click="editCompetency(item)">
            <VIcon icon="tabler-pencil" />
          </IconBtn>

          <IconBtn v-if="hasPermission('competency.delete')" @click="deleteCompetency(item.id)" :loading="isDeleting">
            <VIcon icon="tabler-trash" />
          </IconBtn>
        </template>
      </VDataTable>
    </VCard>

    <AddNewCompetencyDrawer
      v-model:is-open="isFormOpen"
      :competency="currentCompetency"
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
