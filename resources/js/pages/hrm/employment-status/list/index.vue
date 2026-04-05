<script setup>
import { hasPermission } from "@/utils/permission";
import AddNewEmploymentStatusDrawer from "@/views/apps/hrm/employment-status/list/AddNewEmploymentStatusDrawer.vue";

const searchQuery = ref("");
const employmentStatuses = ref([]);
const loading = ref(false);
const error = ref(null);
const isFormOpen = ref(false);
const currentEmploymentStatus = ref(null);
const isSubmitting = ref(false);
const isDeleting = ref(false);
const accessToken = useCookie("accessToken");

const headers = [
  { title: "Name", key: "name" },
  { title: "Description", key: "description" },
  { title: "Created At", key: "created_at" },
  { title: "Updated At", key: "updated_at" },
  { title: "Actions", key: "actions", sortable: false },
];

const fetchEmploymentStatuses = async () => {
  loading.value = true;
  try {
    const response = await $api("/employment-statuses");
    employmentStatuses.value = response.data || [];
  } catch (err) {
    error.value = err;
    console.error("Failed to fetch employment statuses:", err);
  } finally {
    loading.value = false;
  }
};

watch(searchQuery, fetchEmploymentStatuses, { deep: true });
onMounted(fetchEmploymentStatuses);

const formatDate = (dateString) => {
  return dateString ? new Date(dateString).toISOString().split("T")[0] : "-";
};

const handleSubmit = async (formData) => {
  isSubmitting.value = true;
  try {
    const method = formData.id ? "PUT" : "POST";
    const url = formData.id
      ? `/employment-statuses/${formData.id}`
      : "/employment-statuses";
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
    await fetchEmploymentStatuses();
    isFormOpen.value = false;
    $toast.success("Employment status saved successfully!");
  } catch (err) {
    $toast.error("Failed to save employment status");
    if (err.response) {
      console.error("Response error:", err.response.data);
    }
  } finally {
    isSubmitting.value = false;
  }
};

const editEmploymentStatus = (employmentStatus) => {
  currentEmploymentStatus.value = { ...employmentStatus };
  isFormOpen.value = true;
};

const deleteEmploymentStatus = async (id) => {
  try {
    isDeleting.value = true;
    await $api(`/employment-statuses/${id}`, { method: "DELETE" });
    await fetchEmploymentStatuses();
    $toast.success("Employment status deleted successfully!");
  } catch (err) {
    $toast.error("Failed to delete employment status");
  } finally {
    isDeleting.value = false;
  }
};

const newEmploymentStatus = () => {
  currentEmploymentStatus.value = null;
  isFormOpen.value = true;
};
</script>

<template>
  <section>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0 help-center-breadcrumbs"
      :items="[{ title: 'HR Administration' }, { title: 'Employment Status' }]"
    />
    <VCard>
      <VCardText class="d-flex flex-wrap gap-4">
        <VSpacer />
        <div class="d-flex align-center flex-wrap gap-4">
          <AppTextField
            v-model="searchQuery"
            placeholder="Search employment statuses"
            style="inline-size: 15.625rem;"
          />
          <VBtn v-if="hasPermission('employment_status.create')" prepend-icon="tabler-plus" @click="newEmploymentStatus">
            Add New
          </VBtn>
        </div>
      </VCardText>
      <VDivider />
      <div v-if="loading" class="text-center pa-4">
        <VProgressCircular indeterminate />
        <p>Loading employment statuses...</p>
      </div>
      <VAlert v-else-if="error" type="error" variant="tonal" class="ma-4">
        {{ error.message || "Failed to load employment statuses" }}
      </VAlert>
      <VDataTable
        v-else
        :headers="headers"
        :items="employmentStatuses"
        :search="searchQuery"
        class="text-no-wrap"
      >
        <template #item.name="{ item }">
          <div class="d-flex align-center gap-x-4">
            <VAvatar size="34" variant="tonal" color="primary">
              <span>{{ item.name?.charAt(0)?.toUpperCase() || "S" }}</span>
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
          <IconBtn v-if="hasPermission('employment_status.update')"
          @click="editEmploymentStatus(item)">
            <VIcon icon="tabler-pencil" />
          </IconBtn>
          <IconBtn
            v-if="hasPermission('employment_status.delete')"
            @click="deleteEmploymentStatus(item.id)"
            :loading="isDeleting"
          >
            <VIcon icon="tabler-trash" />
          </IconBtn>
        </template>
      </VDataTable>
    </VCard>
    <AddNewEmploymentStatusDrawer
      v-model:is-open="isFormOpen"
      :employment-status="currentEmploymentStatus"
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
