<script setup>
import { hasPermission } from "@/utils/permission";
import AddNewAwardTypeDrawer from "@/views/apps/hrm/award-type/list/AddNewAwardTypeDrawer.vue";

const searchQuery = ref("");
const awardTypes = ref([]);
const loading = ref(false);
const error = ref(null);
const isDrawerVisible = ref(false);
const currentAwardType = ref(null);
const isSubmitting = ref(false);
const accessToken = useCookie("accessToken");

const headers = [
  { title: "Name", key: "name" },
  { title: "Description", key: "description" },
  { title: "Created At", key: "created_at" },
  { title: "Updated At", key: "updated_at" },
  { title: "Actions", key: "actions", sortable: false },
];

const fetchAwardTypes = async () => {
  loading.value = true;
  try {
    const response = await $api(`/api/award-types?q=${searchQuery.value}`);
    awardTypes.value = response.data || [];
  } catch (err) {
    error.value = err;
    $toast.error("Failed to fetch award types");
  } finally {
    loading.value = false;
  }
};

watch(
  searchQuery,
  () => {
    fetchAwardTypes();
  },
  { deep: true }
);

onMounted(() => {
  fetchAwardTypes();
});

const formatDate = (dateString) => {
  return dateString ? new Date(dateString).toISOString().split("T")[0] : "-";
};

const openAddAwardType = () => {
  currentAwardType.value = null;
  isDrawerVisible.value = true;
};

const openEditAwardType = (awardType) => {
  currentAwardType.value = { ...awardType };
  isDrawerVisible.value = true;
};

const handleSubmit = async (formData) => {
  isSubmitting.value = true;
  try {
    const method = formData.id ? "PUT" : "POST";
    const url = formData.id ? `/api/award-types/${formData.id}` : "/api/award-types";
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
        Authorization: `Bearer ${accessToken.value}`,
      },
    });
    $toast.success(`Award type ${formData.id ? "updated" : "created"} successfully`);
    await fetchAwardTypes();
    isDrawerVisible.value = false;
  } catch (err) {
    $toast.error(`Failed to ${formData.id ? "update" : "create"} award type`);
  } finally {
    isSubmitting.value = false;
  }
};

const deleteAwardType = async (id) => {
  try {
    await $api(`/api/award-types/${id}`, { method: "DELETE" });
    fetchAwardTypes();
    $toast.success("Award type deleted successfully!");
  } catch (err) {
    $toast.error("Failed to delete award type");
  }
};
</script>

<template>
  <section>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0 help-center-breadcrumbs"
      :items="[{ title: 'HR Administration' }, { title: 'Award Types' }]"
    />
    <VCard>
      <VCardText class="d-flex flex-wrap gap-4">
        <VSpacer />
        <div class="d-flex align-center flex-wrap gap-4">
          <!-- Search -->
          <AppTextField
            v-model="searchQuery"
            placeholder="Search award types"
            style="inline-size: 15.625rem;"
          />
          <VBtn v-if="hasPermission('award_type.create')" prepend-icon="tabler-plus" @click="openAddAwardType">
            Add New
          </VBtn>
        </div>
      </VCardText>
      <VDivider />
      <!-- Loading state -->
      <div v-if="loading" class="text-center pa-4">
        <VProgressCircular indeterminate />
        <p>Loading award types...</p>
      </div>
      <!-- Error state -->
      <VAlert v-else-if="error" type="error" variant="tonal" class="ma-4">
        {{ error.message || "Failed to load award types" }}
      </VAlert>
      <!-- Data table -->
      <VDataTable
        v-else
        :headers="headers"
        :items="awardTypes"
        class="text-no-wrap"
      >
        <!-- Name -->
        <template #item.name="{ item }">
          <div class="d-flex align-center gap-x-4">
            <VAvatar size="34" variant="tonal" color="primary">
              <span>{{ item.name?.charAt(0)?.toUpperCase() || "A" }}</span>
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
          <IconBtn v-if="hasPermission('award_type.update')" @click="openEditAwardType(item)">
            <VIcon icon="tabler-pencil" />
          </IconBtn>
          <IconBtn v-if="hasPermission('award_type.delete')" @click="deleteAwardType(item.id)">
            <VIcon icon="tabler-trash" />
          </IconBtn>
        </template>
      </VDataTable>
    </VCard>
    <!-- Add/Edit Award Type Drawer -->
    <AddNewAwardTypeDrawer
      v-model:is-open="isDrawerVisible"
      :award-type="currentAwardType"
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
