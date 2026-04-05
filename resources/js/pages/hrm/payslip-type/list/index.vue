<script setup>
import { hasPermission } from "@/utils/permission";
import AddNewPayslipDrawer from "@/views/apps/hrm/payslip-type/list/AddNewPayslipDrawer.vue";

const searchQuery = ref("");
const payslipTypes = ref([]);
const loading = ref(false);
const error = ref(null);
const isDrawerVisible = ref(false);
const currentPayslipType = ref(null);
const isSubmitting = ref(false);

const headers = [
  { title: "Name", key: "name" },
  { title: "Description", key: "description" },
  { title: "Created At", key: "created_at" },
  { title: "Updated At", key: "updated_at" },
  { title: "Actions", key: "actions", sortable: false },
];

const fetchPayslipTypes = async () => {
  loading.value = true;
  try {
    const response = await $api(`/payslip-types?q=${searchQuery.value}`);
    payslipTypes.value = response.data || [];
  } catch (err) {
    error.value = err;
    $toast.error("Failed to fetch payslip types");
  } finally {
    loading.value = false;
  }
};

watch(
  searchQuery,
  () => {
    fetchPayslipTypes();
  },
  { deep: true }
);

onMounted(() => {
  fetchPayslipTypes();
});

const formatDate = (dateString) => {
  return dateString ? new Date(dateString).toISOString().split("T")[0] : "-";
};

const openAddPayslipType = () => {
  currentPayslipType.value = null;
  isDrawerVisible.value = true;
};

const openEditPayslipType = (payslipType) => {
  currentPayslipType.value = { ...payslipType };
  isDrawerVisible.value = true;
};

const handleSubmit = async (formData) => {
  isSubmitting.value = true;
  try {
    const method = formData.id ? "PUT" : "POST";
    const url = formData.id ? `/payslip-types/${formData.id}` : "/payslip-types";
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
      },
    });
    $toast.success(`Payslip type ${formData.id ? "updated" : "created"} successfully`);
    await fetchPayslipTypes();
    isDrawerVisible.value = false;
  } catch (err) {
    $toast.error(`Failed to ${formData.id ? "update" : "create"} payslip type`);
  } finally {
    isSubmitting.value = false;
  }
};

const deletePayslipType = async (id) => {
  try {
    await $api(`payslip-types/${id}`, { method: "DELETE" });
    fetchPayslipTypes();
    $toast.success("Payslip type deleted successfully!");
  } catch (err) {
    $toast.error("Failed to delete payslip type");
  }
};
</script>

<template>
  <section>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0 help-center-breadcrumbs"
      :items="[{ title: 'HR Administration' }, { title: 'Payslip Types' }]"
    />
    <VCard>
      <VCardText class="d-flex flex-wrap gap-4">
        <VSpacer />
        <div class="d-flex align-center flex-wrap gap-4">
          <!-- Search -->
          <AppTextField
            v-model="searchQuery"
            placeholder="Search payslip types"
            style="inline-size: 15.625rem;"
          />
          <VBtn v-if="hasPermission('payslip_type.create')" prepend-icon="tabler-plus" @click="openAddPayslipType">
            Add New
          </VBtn>
        </div>
      </VCardText>
      <VDivider />
      <!-- Loading state -->
      <div v-if="loading" class="text-center pa-4">
        <VProgressCircular indeterminate />
        <p>Loading payslip types...</p>
      </div>
      <!-- Error state -->
      <VAlert v-else-if="error" type="error" variant="tonal" class="ma-4">
        {{ error.message || "Failed to load payslip types" }}
      </VAlert>
      <!-- Data table -->
      <VDataTable
        v-else
        :headers="headers"
        :items="payslipTypes"
        class="text-no-wrap"
      >
        <!-- Name -->
        <template #item.name="{ item }">
          <div class="d-flex align-center gap-x-4">
            <VAvatar size="34" variant="tonal" color="primary">
              <span>{{ item.name?.charAt(0)?.toUpperCase() || "P" }}</span>
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
          <IconBtn v-if="hasPermission('payslip_type.update')" @click="openEditPayslipType(item)">
            <VIcon icon="tabler-pencil" />
          </IconBtn>
          <IconBtn v-if="hasPermission('payslip_type.delete')" @click="deletePayslipType(item.id)">
            <VIcon icon="tabler-trash" />
          </IconBtn>
        </template>
      </VDataTable>
    </VCard>
    <!-- Add/Edit Payslip Type Drawer -->
    <AddNewPayslipDrawer
      v-model:is-drawer-open="isDrawerVisible"
      :payslip-type="currentPayslipType"
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
