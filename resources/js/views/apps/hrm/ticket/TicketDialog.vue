<script setup>
import { PerfectScrollbar } from "vue3-perfect-scrollbar"

const props = defineProps({
  isOpen: Boolean,
  isFirst: Boolean,
  mode: String, // create | edit | view
  ticket: Object,
})

const emit = defineEmits(["update:isOpen", "update:isFirst", "submit", "close", "submission-complete"])

const isFormValid = ref(false)
const refForm = ref()
const isSubmitting = ref(false)

const form = ref({
  id: null,
  employee_id: null,
  department_id: null,
  poc_id: null,
  category_id: null,
  description: "",
  attachment: null,
  status: "Open",
})

const employees = ref([])
const departments = ref([])
const pocs = ref([])
const categories = ref([])

const accessToken = useCookie("accessToken")

const fetchEmployees = async () => {
  const res = await $api("/employees", {
    headers: { Authorization: `Bearer ${accessToken.value}` },
  })
  employees.value = res.data || []
}

const fetchDepartments = async () => {
  const res = await $api("/departments?context=filters", {
    headers: { Authorization: `Bearer ${accessToken.value}` },
  })
  departments.value = res.data || []
}

const fetchCategories = async () => {
  const res = await $api("/ticket-categories", {
    headers: { Authorization: `Bearer ${accessToken.value}` },
  })
  categories.value = res.data || []
}

const fetchPOCs = async (departmentId, selectPocId = 0) => {
  if (!departmentId) {
    pocs.value = []
    return
  }
  const res = await $api(`/employees/get-all?department_id=${departmentId}`, {
    headers: { Authorization: `Bearer ${accessToken.value}` },
  })
  pocs.value = res.data || []

  if(selectPocId && selectPocId > 0 && props.isFirst) {
    form.value.poc_id = selectPocId
    emit("update:isFirst", false)
  }
}

watch(
  () => form.value.department_id,
  (val) => {
    form.value.poc_id = null
    fetchPOCs(val, props.ticket?.poc?.id || null)
  }
)

watch(
  () => props.isOpen,
  (val) => {
    refForm.value?.reset()
    refForm.value?.resetValidation()
    isSubmitting.value = false
    if (val) {
      fetchEmployees()
      fetchDepartments()
      fetchCategories()
      if (props.ticket) {
        form.value = {
          id: props.ticket.id,
          employee_id: props.ticket.employee?.id,
          department_id: props.ticket.department?.id,
          poc_id: props.ticket.poc?.id,
          category_id: props.ticket.category?.id,
          description: props.ticket.description,
          attachment: props.ticket.attachment,
          status: props.ticket.status,
        }
        fetchPOCs(props.ticket.department?.id, props.ticket.poc?.id)
      } else {
        form.value = {
          id: null,
          employee_id: null,
          department_id: null,
          poc_id: null,
          category_id: null,
          description: "",
          attachment: null,
          status: "Open",
        }
        pocs.value = []
      }
    } else {
      refForm.value?.reset()
    }
  }
)

const statusOptions = [
  { title: "Open", value: "Open" },
  { title: "Pending", value: "Pending" },
  { title: "Resolved", value: "Resolved" },
  { title: "Closed", value: "Closed" },
]

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      isSubmitting.value = true
      emit("submit", form.value)
    }
  })
}

const closeDialog = () => {
  emit("update:isOpen", false)
  emit("close")
}

const resetLoadingState = () => {
  isSubmitting.value = false
}

// Expose the resetLoadingState method to parent component
defineExpose({
  resetLoadingState
})
</script>

<template>
  <VDialog
    v-model="props.isOpen"
    max-width="600"
  >
    <DialogCloseBtn @click="closeDialog" />
    <VCard>
      <VCardTitle>
        <span v-if="props.mode === 'create'">Create Ticket</span>
        <span v-else-if="props.mode === 'edit'">Update Ticket</span>
        <span v-else-if="props.mode === 'view'">View Ticket</span>
        <span v-else>Ticket</span>
      </VCardTitle>
      <VDivider />
      <PerfectScrollbar style="max-block-size: 70vh;">
        <VCardText>
          <VForm ref="refForm" v-model="isFormValid" @submit.prevent="onSubmit">
            <VRow>
              <VCol cols="12" sm="12">
<!--                <AppSelect-->
<!--                  v-model="form.employee_id"-->
<!--                  :items="employees.map(e => ({ title: e.name, value: e.id }))"-->
<!--                  label="Assignor"-->
<!--                  :rules="[requiredValidator]"-->
<!--                  placeholder="Select Assignor"-->
<!--                  :readonly="props.mode === 'view'"-->
<!--                />-->
                <label class="v-label mb-1 text-body-2 font-weight-medium" style="line-height: 15px;">Assigned By</label>
                <VAutocomplete
                  v-model="form.employee_id"
                  :items="employees.map(e => ({ title: e.name + '_' + e.employee_code + '_' + e.department?.name, value: e.id }))"
                  :rules="[requiredValidator]"
                  label=""
                  item-title="title"
                  item-value="value"
                  placeholder="Select Assigned By"
                  clearable
                  no-data-text="No employee found"
                  :readonly="props.mode === 'view'"
                />
              </VCol>
              <VCol cols="12" sm="6">
                <label class="v-label mb-1 text-body-2 font-weight-medium" style="line-height: 15px;">For Department</label>
                <VAutocomplete
                  v-model="form.department_id"
                  :items="departments.map(d => ({ title: d.name, value: d.id }))"
                  :rules="[requiredValidator]"
                  label=""
                  item-title="title"
                  item-value="value"
                  placeholder="Select Department"
                  clearable
                  no-data-text="No department found"
                  :readonly="props.mode === 'view'"
                />
              </VCol>
              <VCol cols="12" sm="6">
                <label class="v-label mb-1 text-body-2 font-weight-medium" style="line-height: 15px;">
                  <span v-if="props.mode === 'view'">Assigned To</span>
                  <span v-else>Assign To</span>
                </label>
                <VAutocomplete
                  v-model="form.poc_id"
                  :items="pocs.map(e => ({ title: e.name, value: e.id }))"
                  :rules="[requiredValidator]"
                  :disabled="!form.department_id"
                  label=""
                  item-title="title"
                  item-value="value"
                  placeholder="Select Assigned To"
                  clearable
                  no-data-text="No employee found"
                  :readonly="props.mode === 'view'"
                />
              </VCol>
              <VCol cols="12" sm="12">
                <AppSelect
                  v-model="form.category_id"
                  :items="categories.map(c => ({ title: c.name, value: c.id }))"
                  label="Ticket Category"
                  :rules="[requiredValidator]"
                  placeholder="Select Category"
                  :readonly="props.mode === 'view'"
                />
              </VCol>
              <VCol cols="12">
                <AppTextarea
                  v-model="form.description"
                  label="Ticket Description"
                  placeholder="Enter description"
                  rows="3"
                  :rules="[requiredValidator]"
                  :readonly="props.mode === 'view'"
                />
              </VCol>
              <VCol cols="12">
                <AppSelect
                  v-model="form.status"
                  :items="statusOptions"
                  label="Status"
                  :rules="[requiredValidator]"
                  placeholder="Select Status"
                  :readonly="props.mode === 'view'"
                />
              </VCol>
              <VCol cols="12" v-if="!form.attachment">
                <label class="v-label mb-1 text-body-2 font-weight-medium" style="line-height: 15px;"></label>
                <VFileInput
                  v-model="form.attachment"
                  label="Attachment"
                  prepend-icon="tabler-file"
                  clearable
                  density="comfortable"
                  variant="outlined"
                  :disabled="props.mode === 'view'"
                />
              </VCol>
              <VCol cols="12" v-else>
                <VFileInput
                  v-model="form.attachment"
                  label="Attachment (Leave empty to keep current)"
                  placeholder="Keep current photo"
                  prepend-icon="tabler-camera"
                  clearable
                  density="comfortable"
                  variant="outlined"
                  :disabled="props.mode === 'view'"
                />
                <small class="text-primary">
                  Current:
                  <a :href="form.attachment" target="_blank">View</a>
                </small>
              </VCol>
              <VCol cols="12" class="d-flex justify-end gap-4 mt-6">
                <VBtn
                  v-if="props.mode !== 'view'"
                  type="submit"
                  color="primary"
                  :loading="isSubmitting"
                  :disabled="isSubmitting"
                >
                  Save
                </VBtn>
                <VBtn
                  variant="tonal"
                  color="error"
                  :disabled="isSubmitting"
                  @click="closeDialog"
                >
                  {{ props.mode === 'view' ? 'Close' : 'Cancel' }}
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </PerfectScrollbar>
    </VCard>
  </VDialog>
</template>
