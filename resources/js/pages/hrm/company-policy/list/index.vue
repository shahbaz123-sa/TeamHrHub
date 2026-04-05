<script setup>
import ConfirmationDialog from "@/components/common/ConfirmationDialog.vue";
import DocumentImageViewer from "@/components/common/DocumentImageViewer.vue";
import { hasPermission } from "@/utils/permission";
import AddCompanyPolicyDrawer from "@/views/apps/company-policy/list/AddCompanyPolicyDrawer.vue";

const loading = ref(false)
const searchQuery = ref("");
const itemsPerPage = ref(10);
const page = ref(1);
const sortBy = ref();
const orderBy = ref();
const selectedRows = ref([]);

const policiesData = ref({ data: [], meta: { total: 0 } });
const policies = ref([]);
const branches = ref([]);
const totalPolicies = ref(0);
const accessToken = useCookie("accessToken");

const headers = [
  { title: "Display Order", key: "display_order" },
  { title: "Branch", key: "branch" },
  { title: "Title", key: "title" },
  { title: "Attachment", key: "attachment_path" },
  { title: "Actions", key: "actions", sortable: false },
];

const updateOptions = (options) => {
  sortBy.value = options.sortBy[0]?.key;
  orderBy.value = options.sortBy[0]?.order;
  fetchPolicies();
}

const fetchBranches = async () => {
  try {
    const response = await $api("/branches", {
      headers: { Authorization: `Bearer ${accessToken.value}` },
    });
    branches.value = response.data || [];
  } catch (err) {
    console.error("Failed to fetch branches:", err);
  }
}

const fetchPolicies = async () => {
  loading.value = true
  const { data, error } = await useApi(
    createUrl("/company-policies", {
      query: {
        q: searchQuery.value,
        per_page: itemsPerPage.value,
        page: page.value,
        sortBy: sortBy.value,
        orderBy: orderBy.value,
      },
    }),
    { headers: { Authorization: `Bearer ${accessToken.value}` } }
  );

  if (!error.value) {
    policiesData.value = data.value;
    policies.value = policiesData.value?.data || [];
    totalPolicies.value = policiesData.value?.meta?.total || 0;
  }
  loading.value = false
};

watch([searchQuery], () => {
  fetchPolicies();
});

onMounted(() => {
  fetchPolicies()
  fetchBranches()
});

const isPolicyDrawerVisible = ref(false);
const editingPolicy = ref(null);
const isDeleteDialogOpen = ref(false);
const deleteSubmitting = ref(false);
const deleteTargetId = ref(null);

const openCreateDrawer = () => {
  editingPolicy.value = null;
  isPolicyDrawerVisible.value = true;
};

const openEditDrawer = (policy) => {
  editingPolicy.value = policy;
  isPolicyDrawerVisible.value = true;
};

const savePolicy = async (formData, id) => {
  try {
    if (id) {
        formData.append('_method', 'PUT');
      await $api(`/company-policies/${id}`, {
        method: "POST",
        headers: { Authorization: `Bearer ${accessToken.value}` },
        body: formData,
      });
      $toast.success("Policy updated successfully.");
    } else {
      await $api(`/company-policies`, {
        method: "POST",
        headers: { Authorization: `Bearer ${accessToken.value}` },
        body: formData,
      });
      $toast.success("Policy created successfully.");
    }
    isPolicyDrawerVisible.value = false
    fetchPolicies();
  } catch (err) {

    let message = "Something went wrong!"
    // Handle validation errors
    if (err.response && err.response.status === 201) {
        $toast.success('Leave created successfully')
        router.push({ name: 'hrm-leave-list' })
    }
    
    if (err.response && err.response.status === 422) {
        message = Object.values(err.response?._data?.errors).join("\n")
    }

    $toast.error(message)
    
    
  }
};

const askDelete = (id) => {
  deleteTargetId.value = id;
  isDeleteDialogOpen.value = true;
};

const confirmDelete = async () => {
  if (!deleteTargetId.value) {
    isDeleteDialogOpen.value = false;
    return;
  }
  deleteSubmitting.value = true;
  try {
    await $api(`/company-policies/${deleteTargetId.value}`, {
      method: "DELETE",
      headers: { Authorization: `Bearer ${accessToken.value}` },
    });
    isDeleteDialogOpen.value = false;
    deleteTargetId.value = null;
    await fetchPolicies();
    $toast.success("Policy deleted successfully.");
  } catch (err) {
    $toast.error("Failed to delete policy.");
  } finally {
    deleteSubmitting.value = false;
  }
};
</script>

<template>
  <section>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0 help-center-breadcrumbs"
      :items="[{ title: 'About Zarea' }]"
    />
    <VCard class="mb-6">
      <VCardText>
        <VRow>
          <VCol cols="12" sm="9">
          </VCol>
          <VCol cols="12" sm="3" class="text-right">
            <VBtn v-if="hasPermission('company_policy.create')" prepend-icon="tabler-plus" @click="openCreateDrawer">Create Policy</VBtn>
          </VCol>
        </VRow>
      </VCardText>

      <VDivider />

      <VRow class="ma-6 align-center" no-gutters>
        <VCol cols="12" sm="6" class="d-flex align-center mb-2 mb-sm-0">
          <AppTextField
            v-model="searchQuery"
            placeholder="Search Policy"
            style="inline-size: 200px;"
            class="me-3"
          />
        </VCol>
        <!-- <VSpacer /> -->

        <VCol
          cols="12"
          sm="6"
          class="d-flex align-center"
          :class="$vuetify.display.smAndDown ? 'justify-start' : 'justify-end'"
        >
          <div :class="$vuetify.display.smAndDown ? '' : 'ms-auto'">
            <AppSelect
              v-model="itemsPerPage"
              :items="[
                { value: 5, title: '5' },
                { value: 10, title: '10' },
                { value: 20, title: '20' },
                { value: 50, title: '50' },
                { value: -1, title: 'All' },
              ]"
              style="inline-size: 7rem;"
            />
          </div>
        </VCol>
      </VRow>

      <VDivider class="mt-4" />

      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:model-value="selectedRows"
        v-model:page="page"
        :headers="headers"
        :items="policies"
        :items-length="totalPolicies"
        class="text-no-wrap"
        @update:options="updateOptions"
        :loading="loading"
        loading-text="Loading data..."
      >
      <template #item.branch="{ item }">
          <div class="text-high-emphasis text-body-1">{{ item.branch?.name || '—' }}</div>
        </template>
        <template #item.attachment_path="{ item }">
          <div class="d-flex align-center gap-2">
            <DocumentImageViewer v-if="item.attachment_url" :src="item.attachment_url" :pdf-title="item.title">
              <template #icon></template>
            </DocumentImageViewer>
            <VBtn size="small" v-if="item.attachment_url" :href="item.attachment_url" icon download :title="'Download'">
              <VIcon icon="tabler-download" />
            </VBtn>
            <span v-if="!item.attachment_url">-</span>
          </div>
        </template>

        <template #item.actions="{ item }">
          <IconBtn v-if="hasPermission('company_policy.update')" @click="openEditDrawer(item)">
            <VIcon icon="tabler-edit" />
          </IconBtn>
          <IconBtn v-if="hasPermission('company_policy.delete')" @click="askDelete(item.id)">
            <VIcon icon="tabler-trash" />
          </IconBtn>
        </template>
        <template #bottom>
          <TablePagination v-model:page="page" :items-per-page="itemsPerPage" :total-items="totalPolicies" />
        </template>
      </VDataTableServer>
    </VCard>

    <AddCompanyPolicyDrawer
      v-model:is-drawer-open="isPolicyDrawerVisible"
      :editing-policy="editingPolicy"
      :branches="branches"
      @save="savePolicy"
    />

    <ConfirmationDialog
      v-model="isDeleteDialogOpen"
      title="Are you sure"
      description="This action can not be undone. Do you want to continue?"
      cancel-text="No"
      confirm-text="Yes"
      :loading="deleteSubmitting"
      @confirm="confirmDelete"
    />
  </section>
</template>


