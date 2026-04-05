<script setup>
import { hasPermission } from "@/utils/permission";
import AddNewDocumentTypeDrawer from "@/views/apps/hrm/document-type/list/AddNewDocumentTypeDrawer.vue";

const searchQuery = ref("");
const documentTypes = ref([]);
const loading = ref(false);
const error = ref(null);
const isDrawerVisible = ref(false);
const currentDocumentType = ref(null);
const isSubmitting = ref(false);

const headers = [
  { title: "Name", key: "name" },
  { title: "Description", key: "description" },
  { title: "Is Default", key: "is_default", align: "center" },
  { title: "Order", key: "order", align: "center" },
  { title: "Created At", key: "created_at" },
  { title: "Updated At", key: "updated_at" },
  { title: "Actions", key: "actions", sortable: false },
];

const fetchDocumentTypes = async () => {
  loading.value = true;
  try {
    const response = await $api(`/document-types?q=${searchQuery.value}`);
    documentTypes.value = response.data || [];
  } catch (err) {
    error.value = err;
    $toast.error("Failed to fetch document types");
  } finally {
    loading.value = false;
  }
};

watch(
  searchQuery,
  () => {
    fetchDocumentTypes();
  },
  { deep: true }
);

onMounted(() => {
  fetchDocumentTypes();
});

const formatDate = (dateString) => {
  return dateString ? new Date(dateString).toISOString().split("T")[0] : "-";
};

const openAddDocumentType = () => {
  currentDocumentType.value = null;
  isDrawerVisible.value = true;
};

const openEditDocumentType = (documentType) => {
  currentDocumentType.value = { ...documentType };
  isDrawerVisible.value = true;
};

const handleSubmit = async (formData) => {
  isSubmitting.value = true;
  try {
    const method = formData.id ? "PUT" : "POST";
    const url = formData.id ? `/document-types/${formData.id}` : "/document-types";
    const payload = {
      name: formData.name,
      description: formData.description,
      is_default: formData.is_default,
      order: formData.order,
    };
    await $api(url, {
      method,
      body: JSON.stringify(payload),
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
    });
    $toast.success(`Document type ${formData.id ? "updated" : "created"} successfully`);
    await fetchDocumentTypes();
    isDrawerVisible.value = false;
  } catch (err) {
    $toast.error(`Failed to ${formData.id ? "update" : "create"} document type`);
  } finally {
    isSubmitting.value = false;
  }
};

const deleteDocumentType = async (id) => {
  try {
    await $api(`/document-types/${id}`, { method: "DELETE" });
    fetchDocumentTypes();
    $toast.success("Document type deleted successfully!");
  } catch (err) {
    $toast.error("Failed to delete document type");
  }
};
</script>

<template>
  <section>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0 help-center-breadcrumbs"
      :items="[{ title: 'HR Administration' }, { title: 'Document Types' }]"
    />
    <VCard>
      <VCardText class="d-flex flex-wrap gap-4">
        <VSpacer />
        <div class="d-flex align-center flex-wrap gap-4">
          <!-- Search -->
          <AppTextField
            v-model="searchQuery"
            placeholder="Search document types"
            style="inline-size: 15.625rem;"
          />
          <VBtn v-if="hasPermission('document_type.create')" prepend-icon="tabler-plus" @click="openAddDocumentType">
            Add New
          </VBtn>
        </div>
      </VCardText>
      <VDivider />
      <!-- Loading state -->
      <div v-if="loading" class="text-center pa-4">
        <VProgressCircular indeterminate />
        <p>Loading document types...</p>
      </div>
      <!-- Error state -->
      <VAlert v-else-if="error" type="error" variant="tonal" class="ma-4">
        {{ error.message || "Failed to load document types" }}
      </VAlert>
      <!-- Data table -->
      <VDataTable
        v-else
        :headers="headers"
        :items="documentTypes"
        class="text-no-wrap"
      >
        <!-- Name -->
        <template #item.name="{ item }">
          <div class="d-flex align-center gap-x-4">
            <VAvatar size="34" variant="tonal" color="primary">
              <span>{{ item.name?.charAt(0)?.toUpperCase() || "D" }}</span>
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
        <!-- Default -->
        <template #item.is_default="{ item }">
          <div class="text-high-emphasis text-body-1">
            {{ item.is_default ? 'Yes' : 'No' || "-" }}
          </div>
        </template>
        <!-- Order -->
        <template #item.order="{ item }">
          <div class="text-high-emphasis text-body-1">
            {{ item.order !== 0 ? item.order : '-' }}
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
          <IconBtn v-if="hasPermission('document_type.update')" @click="openEditDocumentType(item)">
            <VIcon icon="tabler-pencil" />
          </IconBtn>
          <IconBtn v-if="hasPermission('document_type.delete')" @click="deleteDocumentType(item.id)">
            <VIcon icon="tabler-trash" />
          </IconBtn>
        </template>
      </VDataTable>
    </VCard>
    <!-- Add/Edit Document Type Drawer -->
    <AddNewDocumentTypeDrawer
      v-model:is-drawer-open="isDrawerVisible"
      :document-type="currentDocumentType"
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
