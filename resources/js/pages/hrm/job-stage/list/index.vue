<script setup>
import { hasPermission } from "@/utils/permission";
import AddNewJobStageDrawer from "@/views/apps/hrm/job-stage/list/AddNewJobStageDrawer.vue";

const searchQuery = ref("");
const jobStages = ref([]);
const loading = ref(false);
const error = ref(null);
const isFormOpen = ref(false);
const currentJobStage = ref(null);
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

// Fetch job stages
const fetchJobStages = async () => {
  loading.value = true;
  try {
    const response = await $api("/job-stages");
    jobStages.value = response.data || [];
  } catch (err) {
    error.value = err;
    console.error("Failed to fetch job stages:", err);
  } finally {
    loading.value = false;
  }
};

// Watch for search changes
watch(searchQuery, fetchJobStages, { deep: true });

// Initial fetch
onMounted(fetchJobStages);

const formatDate = (dateString) => {
  return dateString ? new Date(dateString).toISOString().split("T")[0] : "-";
};

// Handle form submission
const handleSubmit = async (formData) => {
  isSubmitting.value = true;
  try {
    const method = formData.id ? "PUT" : "POST";
    const url = formData.id ? `/job-stages/${formData.id}` : "/job-stages";

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

    await fetchJobStages();
    isFormOpen.value = false;
    $toast.success("Job stage saved successfully!");
  } catch (err) {
    $toast.error("Failed to save job stage");
  } finally {
    isSubmitting.value = false;
  }
};

// Edit job stage
const editJobStage = (jobStage) => {
  currentJobStage.value = { ...jobStage };
  isFormOpen.value = true;
};

// Delete job stage
const deleteJobStage = async (id) => {
  try {
    isDeleting.value = true;
    await $api(`/job-stages/${id}`, { method: "DELETE" });
    await fetchJobStages();
    $toast.success("Job stage deleted successfully!");
  } catch (err) {
    $toast.error("Failed to delete job stage");
  } finally {
    isDeleting.value = false;
  }
};

// Open form for new job stage
const newJobStage = () => {
  currentJobStage.value = null;
  isFormOpen.value = true;
};
</script>

<template>
  <section>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0 help-center-breadcrumbs"
      :items="[{ title: 'HR Administration' }, { title: 'Job Stages' }]"
    />
    <VCard>
      <VCardText class="d-flex flex-wrap gap-4">
        <VSpacer />
        <div class="d-flex align-center flex-wrap gap-4">
          <AppTextField
            v-model="searchQuery"
            placeholder="Search job stages"
            style="inline-size: 15.625rem;"
          />
          <VBtn v-if="hasPermission('job_stage.create')" prepend-icon="tabler-plus" @click="newJobStage"> Add New </VBtn>
        </div>
      </VCardText>

      <VDivider />

      <div v-if="loading" class="text-center pa-4">
        <VProgressCircular indeterminate />
        <p>Loading job stages...</p>
      </div>

      <VAlert v-else-if="error" type="error" variant="tonal" class="ma-4">
        {{ error.message || "Failed to load job stages" }}
      </VAlert>

      <VDataTable
        v-else
        :headers="headers"
        :items="jobStages"
        :search="searchQuery"
        class="text-no-wrap"
      >
        <template #item.name="{ item }">
          <div class="d-flex align-center gap-x-4">
            <VAvatar size="34" variant="tonal" color="primary">
              <span>{{ item.name?.charAt(0)?.toUpperCase() || "J" }}</span>
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
          <IconBtn v-if="hasPermission('job_stage.update')" @click="editJobStage(item)">
            <VIcon icon="tabler-pencil" />
          </IconBtn>

          <IconBtn v-if="hasPermission('job_stage.delete')" @click="deleteJobStage(item.id)" :loading="isDeleting">
            <VIcon icon="tabler-trash" />
          </IconBtn>
        </template>
      </VDataTable>
    </VCard>

    <AddNewJobStageDrawer
      v-model:is-open="isFormOpen"
      :job-stage="currentJobStage"
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
