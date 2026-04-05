<script setup>
import { isEmpty } from "@/@core/utils/helpers";
import { humanize } from "@/utils/helpers/str";
import { hasPermission } from "@/utils/permission";
import { onMounted, ref, watch } from "vue";

const props = defineProps({
  rolePermissions: {
    type: Object,
    required: false,
    default: () => ({
      name: "",
      permissions: [],
    }),
  },
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
});

const emit = defineEmits([
  "update:isDialogVisible",
  "role-created",
  "role-updated",
]);

onMounted(() => {
  fetchRoles();
})

const assignableRoles = ref([])
const allRoles = ref([])
const modules = ref([]);
const permissions = ref([]);
const role = ref("");
const refPermissionForm = ref();
const loading = ref(false);

const fetchRoles = async () => {
  try {
    const res = await $api(`/roles?per_page=-1`, {
      headers: {
        Authorization: `Bearer ${useCookie("accessToken").value}`,
      },
    });

    const roles = Array.isArray(res?.data) ? res.data : (Array.isArray(res) ? res : (res?.data?.data || res?.data || []));

    allRoles.value = roles.filter((r) => !props.rolePermissions?.id || r.id !== props.rolePermissions?.id);
  } catch (error) {
    console.error('Failed to fetch roles', error);
    allRoles.value = [];
  }
}

const fetchPermissions = async () => {
  try {
    const response = await $api("/permissions");

    const permissionList = Array.isArray(response?.data)
      ? response.data
      : [];

    console.log("response", response)

    permissions.value = transformPermissionsGrouped(
      permissionList,
      props.rolePermissions?.permissions || []
    );

    console.log("permissions.value", permissions.value)
    console.log("modules.value", modules.value)

    if (props.rolePermissions?.name) {
      role.value = props.rolePermissions.name;
    }
  } catch (error) {
    console.error("Error loading permissions:", error);
  }
};

const transformPermissionsGrouped = (apiPerms = [], rolePerms = []) => {
  rolePerms = rolePerms.map(p => p.name.toLowerCase());

  // Group apiPerms by module prefix
  const grouped = {};

  apiPerms.forEach(perm => {

    if(!isEmpty(perm?.module?.name) && !modules.value.includes(perm.module.name))
    {
      modules.value.push(perm.module.name)
    }

    const parts = perm.name.split('.');
    if (parts.length < 2) return; // skip invalid permissions

    const module = parts[0];      // e.g. 'employee'
    const action = parts[1];      // e.g. 'create'

    if (!grouped[module]) {
      grouped[module] = {
        id: perm.id,              // You can choose how to handle id here
        module: perm.module.name,
        name: module,
        read: false,
        update: false,
        create: false,
        delete: false,
      };
    }

    // Map your CRUD actions properly (update === update)
    if (action === 'read') grouped[module].read = hasPermission(`${module}.read`, rolePerms);
    if (action === 'update') grouped[module].update = hasPermission(`${module}.update`, rolePerms);
    if (action === 'create') grouped[module].create = hasPermission(`${module}.create`, rolePerms);
    if (action === 'delete') grouped[module].delete = hasPermission(`${module}.delete`, rolePerms);
  });

  // Return array of grouped permissions
  return Object.values(grouped);
};

watch(
  () => props.isDialogVisible,
  (visible) => {
    if (visible) {
      assignableRoles.value = props.rolePermissions.assignable_role_ids
      fetchRoles();
      fetchPermissions();
    }
  },
  { immediate: true }
);

const onSubmit = async () => {
  loading.value = true;
  try {

    let permissionArray = []

    permissions.value
      .filter(p => p.create || p.delete || p.read || p.update)
      .map(permission => {
        
        if(permission.read)
        {
          permissionArray.push({
            id: permission.id,
            name: permission.name + ".read",
          })
        }
        if(permission.update)
        {
          permissionArray.push({
            id: permission.id,
            name: permission.name + ".update",
          })
        }
        if(permission.delete)
        {
          permissionArray.push({
            id: permission.id,
            name: permission.name + ".delete",
          })
        }
        if(permission.create)
        {
          permissionArray.push({
            id: permission.id,
            name: permission.name + ".create",
          })
        }
      })

    const roleData = {
      name: role.value,
      guard_name: "web",
      permissions: permissionArray,
      allowed_role_ids: assignableRoles.value,
    };

    const isEdit = !!props.rolePermissions?.id;
    const url = isEdit ? `/roles/${props.rolePermissions.id}` : "/roles";
    const method = isEdit ? "PUT" : "POST";

    const res = await $api(url, {
      method,
      body: roleData,
    });

    if (res?.id || res?.data) {
      emit(isEdit ? "role-updated" : "role-created", res.data);
      onReset();
    } else {
      $toast.error("❌ Invalid role response:");
    }
  } catch (err) {
    let message = "Something went wrong!"
      
    if (err.response && err.response.status === 422) {
      message = Object.values(err.response?._data?.errors).slice(0, 2).join("\n")
    }

    $toast.error(message)
  } finally {
    loading.value = false;
  }
};

const onReset = () => {
  role.value = "";
  permissions.value = [];
  emit("update:isDialogVisible", false);
  if (refPermissionForm.value) {
    refPermissionForm.value.reset();
  }
};
</script>


<template>
  <VDialog
    :width="$vuetify.display.smAndDown ? 'auto' : 900"
    :model-value="props.isDialogVisible"
    @update:model-value="onReset"
  >
    <DialogCloseBtn @click="onReset" />

    <VCard class="pa-sm-10 pa-2">
      <VCardText>
        <h4 class="text-h4 text-center mb-2">
          {{ props.rolePermissions.name ? "Edit" : "Add New" }} Role
        </h4>
        <p class="text-body-1 text-center mb-6">Set Role Permissions</p>

        <VForm ref="refPermissionForm" @submit.prevent="onSubmit">
          <AppTextField
            v-model="role"
            label="Role Name"
            placeholder="Enter Role Name"
            :rules="[(v) => !!v || 'Role name is required']"
          />

          <AppSelect
            v-model="assignableRoles"
            :items="allRoles"
            label="Assignable Roles"
            multiple
            item-title="name"
            item-value="id"
            placeholder="Select roles this role can assign"
            clearable
          />

          <h5 class="text-h5 my-6">Role Permissions</h5>

          <div class="mb-5" v-if="permissions.length > 0">
            <VExpansionPanels multiple>
              <VExpansionPanel
                v-for="module in modules"
                :key="module"
                class="mb-2"
              >
                <VExpansionPanelTitle>
                  <h5 class="text-h5">{{ module }}</h5>
                </VExpansionPanelTitle>
                <VExpansionPanelText>
                  <VTable class="permission-table text-no-wrap mb-6">
                    <tr>
                      <td><h6 class="text-h6">Access</h6></td>
                      <td><div class="d-flex justify-center ml-3">Read</div></td>
                      <td><div class="d-flex justify-center ml-3">Create</div></td>
                      <td><div class="d-flex justify-center ml-3">Update</div></td>
                      <td><div class="d-flex justify-center ml-3">Delete</div></td>
                    </tr>
                    <template v-for="permission in permissions.filter(permission => permission.module === module)" :key="permission.id">
                      <tr>
                        <td>
                          <h6 class="text-h6" :data-read="permission.read">{{ humanize(permission.name) }}</h6>
                        </td>
                        <td>
                          <div class="d-flex justify-end">
                            <VCheckbox v-model="permission.read" />
                          </div>
                        </td>
                        <td>
                          <div class="d-flex justify-end">
                            <VCheckbox v-model="permission.create" />
                          </div>
                        </td>
                        <td>
                          <div class="d-flex justify-end">
                            <VCheckbox v-model="permission.update" />
                          </div>
                        </td>
                        <td>
                          <div class="d-flex justify-end">
                            <VCheckbox v-model="permission.delete" />
                          </div>
                        </td>
                      </tr>
                    </template>
                  </VTable>
                </VExpansionPanelText>
              </VExpansionPanel>
            </VExpansionPanels>
          </div>

          <!-- <VTable
            v-if="permissions.length > 0"
            class="permission-table text-no-wrap mb-6"
          >
            <tr>
              <td><h6 class="text-h6">Access</h6></td>
              <td><div class="d-flex justify-center ml-3">Read</div></td>
              <td><div class="d-flex justify-center ml-3">Create</div></td>
              <td><div class="d-flex justify-center ml-3">Update</div></td>
              <td><div class="d-flex justify-center ml-3">Delete</div></td>
            </tr>

            <template v-for="permission in permissions" :key="permission.id">
              <tr>
                <td>
                  <h6 class="text-h6" :data-read="permission.read">{{ humanize(permission.name) }}</h6>
                </td>
                <td>
                  <div class="d-flex justify-end">
                    <VCheckbox v-model="permission.read" />
                  </div>
                </td>
                <td>
                  <div class="d-flex justify-end">
                    <VCheckbox v-model="permission.create" />
                  </div>
                </td>
                <td>
                  <div class="d-flex justify-end">
                    <VCheckbox v-model="permission.update" />
                  </div>
                </td>
                <td>
                  <div class="d-flex justify-end">
                    <VCheckbox v-model="permission.delete" />
                  </div>
                </td>
              </tr>
            </template>
          </VTable> -->

          <VAlert v-else type="info" variant="tonal" class="mb-6">
            Loading permissions...
          </VAlert>

          <div class="d-flex align-center justify-center gap-4">
            <VBtn type="submit" :loading="loading">Submit</VBtn>
            <VBtn
              color="secondary"
              variant="tonal"
              @click="onReset"
              :disabled="loading"
            >
              Cancel
            </VBtn>
          </div>
        </VForm>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style lang="scss">
.permission-table {
  td {
    border-block-end:
      1px solid
      rgba(var(--v-border-color), var(--v-border-opacity));
    padding-block: 0.5rem;

    .v-checkbox {
      min-inline-size: 4.75rem;
    }

    &:not(:first-child) {
      padding-inline: 0.5rem;
    }

    .v-label {
      white-space: nowrap;
    }
  }
}
</style>
