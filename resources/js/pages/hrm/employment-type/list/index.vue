<script setup>
import { hasPermission } from "@/utils/permission";
import AddNewEmploymentTypeDrawer from "@/views/apps/hrm/employment-type/list/AddNewEmploymentTypeDrawer.vue";

const searchQuery = ref("");
const employmentTypes = ref([]);
const loading = ref(false);
const error = ref(null);
const isFormOpen = ref(false);
const currentEmploymentType = ref(null);
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

const fetchEmploymentTypes = async () => {
  loading.value = true;
  try {
    const response = await $api("/employment-types");
    employmentTypes.value = response.data || [];
  } catch (err) {
    error.value = err;
    console.error("Failed to fetch employment types:", err);
  } finally {
    loading.value = false;
  }
};

watch(searchQuery, fetchEmploymentTypes, { deep: true });
onMounted(fetchEmploymentTypes);

const formatDate = (dateString) => {
  return dateString ? new Date(dateString).toISOString().split("T")[0] : "-";
};

const handleSubmit = async (formData) => {
  isSubmitting.value = true;
  try {
    const method = formData.id ? "PUT" : "POST";
    const url = formData.id
      ? `/employment-types/${formData.id}`
      : "/employment-types";
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
    await fetchEmploymentTypes();
    isFormOpen.value = false;
    $toast.success("Employment type saved successfully!");
  } catch (err) {
    $toast.error("Failed to save employment type");
    if (err.response) {
      console.error("Response error:", err.response.data);
    }
  } finally {
    isSubmitting.value = false;
  }
};

const editEmploymentType = (employmentType) => {
  currentEmploymentType.value = { ...employmentType };
  isFormOpen.value = true;
};

const deleteEmploymentType = async (id) => {
  try {
    isDeleting.value = true;
    await $api(`/employment-types/${id}`, { method: "DELETE" });
    await fetchEmploymentTypes();
    $toast.success("Employment type deleted successfully!");
  } catch (err) {
    $toast.error("Failed to delete employment type");
  } finally {
    isDeleting.value = false;
  }
};

const newEmploymentType = () => {
  currentEmploymentType.value = null;
  isFormOpen.value = true;
};
</script>

<template>
  <section>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0 help-center-breadcrumbs"
      :items="[{ title: 'HR Administration' }, { title: 'Employment Type' }]"
    />
    <VCard>
      <VCardText class="d-flex flex-wrap gap-4">
        <VSpacer />
        <div class="d-flex align-center flex-wrap gap-4">
          <AppTextField
            v-model="searchQuery"
            placeholder="Search employment types"
            style="inline-size: 15.625rem;"
          />
          <VBtn v-if="hasPermission('employment_type.create')" prepend-icon="tabler-plus" @click="newEmploymentType">
            Add New
          </VBtn>
        </div>
      </VCardText>
      <VDivider />
      <div v-if="loading" class="text-center pa-4">
        <VProgressCircular indeterminate />
        <p>Loading employment types...</p>
      </div>
      <VAlert v-else-if="error" type="error" variant="tonal" class="ma-4">
        {{ error.message || "Failed to load employment types" }}
      </VAlert>
      <VDataTable
        v-else
        :headers="headers"
        :items="employmentTypes"
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
          <IconBtn v-if="hasPermission('employment_type.update')" @click="editEmploymentType(item)">
            <VIcon icon="tabler-pencil" />
          </IconBtn>
          <IconBtn
            v-if="hasPermission('employment_type.delete')"
            @click="deleteEmploymentType(item.id)"
            :loading="isDeleting"
          >
            <VIcon icon="tabler-trash" />
          </IconBtn>
        </template>
      </VDataTable>
    </VCard>
    <AddNewEmploymentTypeDrawer
      v-model:is-open="isFormOpen"
      :employment-type="currentEmploymentType"
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
