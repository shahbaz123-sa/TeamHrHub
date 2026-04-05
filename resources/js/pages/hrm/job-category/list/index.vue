<script setup>
import { hasPermission } from "@/utils/permission";
import AddNewJobCategoryDrawer from "@/views/apps/hrm/job-category/list/AddNewJobCategoryDrawer.vue";

const searchQuery = ref("");
const jobCategories = ref([]);
const loading = ref(false);
const error = ref(null);
const isFormOpen = ref(false);
const currentJobCategory = ref(null);
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

// Fetch job categories
const fetchJobCategories = async () => {
  loading.value = true;
  try {
    const response = await $api("/job-categories");
    jobCategories.value = response.data || [];
  } catch (err) {
    error.value = err;
    console.error("Failed to fetch job categories:", err);
  } finally {
    loading.value = false;
  }
};

// Watch for search changes
watch(searchQuery, fetchJobCategories, { deep: true });

// Initial fetch
onMounted(fetchJobCategories);

const formatDate = (dateString) => {
  return dateString ? new Date(dateString).toISOString().split("T")[0] : "-";
};

// Handle form submission
const handleSubmit = async (formData) => {
  isSubmitting.value = true;
  try {
    const method = formData.id ? "PUT" : "POST";
    const url = formData.id
      ? `/job-categories/${formData.id}`
      : "/job-categories";

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

    await fetchJobCategories();
    isFormOpen.value = false;
    $toast.success("Job category saved successfully!");
  } catch (err) {
    $toast.error("Failed to save job category");
  } finally {
    isSubmitting.value = false;
  }
};

// Edit job category
const editJobCategory = (jobCategory) => {
  currentJobCategory.value = { ...jobCategory };
  isFormOpen.value = true;
};

// Delete job category
const deleteJobCategory = async (id) => {
  try {
    isDeleting.value = true;
    await $api(`/job-categories/${id}`, { method: "DELETE" });
    await fetchJobCategories();
    $toast.success("Job category deleted successfully!");
  } catch (err) {
    $toast.error("Failed to delete job category");
  } finally {
    isDeleting.value = false;
  }
};

// Open form for new job category
const newJobCategory = () => {
  currentJobCategory.value = null;
  isFormOpen.value = true;
};
</script>

<template>
  <section>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0 help-center-breadcrumbs"
      :items="[{ title: 'HR Administration' }, { title: 'Job Categories' }]"
    />
    <VCard>
      <VCardText class="d-flex flex-wrap gap-4">
        <VSpacer />
        <div class="d-flex align-center flex-wrap gap-4">
          <AppTextField
            v-model="searchQuery"
            placeholder="Search job categories"
            style="inline-size: 15.625rem;"
          />
          <VBtn v-if="hasPermission('job_category.create')" prepend-icon="tabler-plus" @click="newJobCategory">
            Add New
          </VBtn>
        </div>
      </VCardText>

      <VDivider />

      <div v-if="loading" class="text-center pa-4">
        <VProgressCircular indeterminate />
        <p>Loading job categories...</p>
      </div>

      <VAlert v-else-if="error" type="error" variant="tonal" class="ma-4">
        {{ error.message || "Failed to load job categories" }}
      </VAlert>

      <VDataTable
        v-else
        :headers="headers"
        :items="jobCategories"
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
          <IconBtn v-if="hasPermission('job_category.update')" @click="editJobCategory(item)">
            <VIcon icon="tabler-pencil" />
          </IconBtn>

          <IconBtn v-if="hasPermission('job_category.delete')" @click="deleteJobCategory(item.id)" :loading="isDeleting">
            <VIcon icon="tabler-trash" />
          </IconBtn>
        </template>
      </VDataTable>
    </VCard>

    <AddNewJobCategoryDrawer
      v-model:is-open="isFormOpen"
      :job-category="currentJobCategory"
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
