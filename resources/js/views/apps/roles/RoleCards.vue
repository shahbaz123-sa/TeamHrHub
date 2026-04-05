<script setup>
import { hasPermission } from "@/utils/permission";
import { onMounted, ref, watch } from "vue";

const roles = ref([]);
const isRoleDialogVisible = ref(false);
const isAddRoleDialogVisible = ref(false);
const isDeleting = ref(false);
const loading = ref(false);
const roleDetail = ref(null);
const searchQuery = ref("");
const page = ref(1);
const itemsPerPage = ref(10);
const totalRoles = ref(0)
const selectedRows = ref([])
const sortBy = ref()
const orderBy = ref()

const headers = [
  { title: "Role", key: "role" },
  { title: "Permissions", key: "permission_string" },
  { title: "Created At", key: "created_at" },
  { title: "Updated At", key: "updated_at" },
  { title: "Actions", key: "actions" },
];

const updateOptions = (options) => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
    fetchRoles()
}

// Format role object
const transformRoleData = (role) => {
  if (!role) return null;

  return {
    role: role.name || "Unnamed Role",
    permission_string: role.permissionNames.join(', '),
    assignable_role_ids: role.assignable_role_ids || [],
    created_at: role.created_at,
    updated_at: role.updated_at,
    details: {
      id: role.id,
      name: role.name,
      permissions: role.permissions || [],
      created_at: role.created_at,
      updated_at: role.updated_at,
    },
  };
};

// Fetch all roles
const fetchRoles = async () => {
  loading.value = true;
  try {
    const res = await $api("/roles", {
      method: "GET",
      params: {
        q: searchQuery.value,
        per_page: itemsPerPage.value,
        page: page.value,
        sortBy: sortBy.value,
        orderBy: orderBy.value,
      },
    });

    if (res?.data) {
      roles.value = res.data.map(transformRoleData).filter(Boolean);
      totalRoles.value = res?.meta?.total || 0;
    }
  } catch (err) {
    roles.value = []
    totalRoles.value = 0
  } finally {
    loading.value = false;
  }
};

// When a role is created
const handleRoleCreated = async (newRole) => {
  fetchRoles();
  isAddRoleDialogVisible.value = false;
  $toast.success("Role created successfully")
  // const role = transformRoleData(newRole);
  // if (role) {
  //   roles.value.unshift(role);
  // }
};

// When a role is updated
const handleRoleUpdated = async (updatedRole) => {
  fetchRoles();
  isRoleDialogVisible.value = false;
  $toast.success("Role updated successfully")
  // const index = roles.value.findIndex((r) => r.details.id === updatedRole.id);
  // if (index !== -1) {
  //   roles.value[index] = transformRoleData(updatedRole);
  //   roles.value = [...roles.value];
  // }
};

// Open dialog for edit
const editPermission = (role) => {
  roleDetail.value = {
    id: role.details.id,
    name: role.details.name,
    permissions: role.details.permissions || [],
    assignable_role_ids: role.assignable_role_ids || [],
  };
  isRoleDialogVisible.value = true;
};

// Delete a role
const deleteRole = async (id) => {
  isDeleting.value = true
  try {
    await $api(`/roles/${id}`, { method: "DELETE" });
    roles.value = roles.value.filter((role) => role.details.id !== id);
  } catch (err) {
    console.error("Error deleting role:", err);
  } finally {
    isDeleting.value = false
  }
};

// Watch search or filters
// watch([searchQuery], () => {
//   fetchRoles();
// });

onMounted(() => {
  fetchRoles();
});
</script>

<template>
  <section>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0 help-center-breadcrumbs"
      :items="[{ title: 'Manage Roles' }, { title: 'Create Roles' }]"
    />
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardText class="d-flex flex-wrap gap-4">
          <VSpacer />
          <div class="d-flex align-center flex-wrap gap-4">
            <VBtn v-if="hasPermission('role.create')" prepend-icon="tabler-plus" @click="isAddRoleDialogVisible = true">
              Add Role
            </VBtn>
          </div>
        </VCardText>

        <VDivider />
        <VRow class="ma-6 align-center" no-gutters>
          <VCol cols="12" md="6" class="mb-2 mb-md-0">
            <AppTextField v-model="searchQuery" placeholder="Search roles" style="inline-size: 15.625rem;" />
          </VCol>
          <VCol cols="12" sm="6" class="d-flex align-center"
            :class="$vuetify.display.smAndDown ? 'justify-start mt-2' : 'justify-end'">
            <div :class="$vuetify.display.smAndDown ? '' : 'ms-auto'">
              <AppSelect v-model="itemsPerPage"
                :items="[{ value: 5, title: '5' }, { value: 10, title: '10' }, { value: 20, title: '20' }, { value: 50, title: '50' }, { value: -1, title: 'All' }]"
                style="inline-size: 7rem;" />
            </div>
          </VCol>
        </VRow>

        <VDivider class="mt-4" />

        <VDataTableServer
          v-model:items-per-page="itemsPerPage"
          v-model:page="page"
          v-model:model-value="selectedRows"
          :loading="loading"
          :headers="headers"
          :items="roles"
          :items-length="totalRoles"
          :search="searchQuery"
          :loading-text="'Loading data...'"
          @update:options="updateOptions"
        >

          <template #item.role="{ item }">
            <span class="text-no-wrap">{{ item.role }}</span>
          </template>

          <template #item.created_at="{ item }">
            <span class="text-no-wrap">{{ item.created_at }}</span>
          </template>

          <template #item.updated_at="{ item }">
            <span class="text-no-wrap">{{ item.updated_at }}</span>
          </template>

          <template #item.actions="{ item }">
            <IconBtn v-if="hasPermission('reports.update')" @click="editPermission(item)">
              <VIcon icon="tabler-pencil" />
            </IconBtn>

            <IconBtn v-if="hasPermission('role.delete')" @click="deleteRole(item.details.id)" :loading="isDeleting">
              <VIcon icon="tabler-trash" />
            </IconBtn>
          </template>

          <template #bottom>
            <TablePagination v-model:page="page" :items-per-page="itemsPerPage" :total-items="totalRoles" />
          </template>
        </VDataTableServer>
      </VCard>
    </VCol>
  </VRow>
  <!-- Dialogs -->
  <AddEditRoleDialog v-model:is-dialog-visible="isAddRoleDialogVisible" @role-created="handleRoleCreated" />
  <AddEditRoleDialog v-model:is-dialog-visible="isRoleDialogVisible" :role-permissions="roleDetail"
    @role-updated="handleRoleUpdated" />
  </section>
</template>
