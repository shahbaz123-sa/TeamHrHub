<template>
  <div>
    <department-add-edit
      :is-add-new="isAddNew"
      :department="department"
      @close="closeModal"
    />

    <b-card no-body>
      <b-card-header>
        <b-button variant="primary" @click="addNew">Add Department</b-button>
      </b-card-header>
      <b-table :items="departments" :fields="fields" responsive>
        <template #cell(status)="data">
          <b-badge :variant="data.item.status ? 'success' : 'danger'">
            {{ data.item.status ? "Active" : "Inactive" }}
          </b-badge>
        </template>
        <template #cell(actions)="data">
          <b-button variant="info" @click="edit(data.item)">Edit</b-button>
          <b-button variant="danger" @click="confirmDelete(data.item.id)"
            >Delete</b-button
          >
        </template>
      </b-table>
    </b-card>
  </div>
</template>

<script>
import useDepartments from "@/composables/useDepartments";
import { onMounted } from "@vue/composition-api";
import DepartmentAddEdit from "./DepartmentAddEdit.vue";

export default {
  components: { DepartmentAddEdit },
  setup() {
    const {
      departments,
      fields,
      fetchDepartments,
      department,
      isAddNew,
      addNew,
      edit,
      closeModal,
      confirmDelete,
    } = useDepartments();

    onMounted(fetchDepartments);

    return {
      departments,
      fields,
      department,
      isAddNew,
      addNew,
      edit,
      closeModal,
      confirmDelete,
    };
  },
};
</script>
