<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { PerfectScrollbar } from "vue3-perfect-scrollbar";

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true,
  },
  ticket: {
    type: Object,
    default: null,
  },
  loading: Boolean,
});

const emit = defineEmits(["update:isOpen", "submit"]);

// Form state
const isFormValid = ref(false);
const refForm = ref();
const isLoading = ref(false);
const accessToken = useCookie("accessToken");
const errors = ref({});

// Data lists
const employees = ref([]);
const departments = ref([]);
const categories = ref([]);
const statuses = ref([
  { title: "Open", value: "open" },
  { title: "Pending", value: "pending" },
  { title: "Resolved", value: "resolved" },
  { title: "Closed", value: "closed" },
]);

// Form fields
const form = ref({
  employee_id: null,
  department_id: null,
  poc_id: null,
  category_id: null,
  description: "",
  attachment: null,
  status: "open",
});

// Check if we're in edit mode
const isEditMode = computed(() => props.ticket !== null);

// Fetch data from API
const fetchEmployees = async () => {
  try {
    const { data } = await $api("/employees", {
      method: "GET",
      headers: {
        Authorization: `Bearer ${accessToken.value}`,
      },
    });

    employees.value = data.map((item) => ({
      id: item.id,
      name: item.name,
    }));
  } catch (error) {
    console.error("Error fetching employees:", error);
  }
};

const fetchDepartments = async () => {
  try {
    const { data } = await $api("/departments?context=filters", {
      method: "GET",
      headers: {
        Authorization: `Bearer ${accessToken.value}`,
      },
    });

    departments.value = data.map((item) => ({
      id: item.id,
      name: item.name,
    }));
  } catch (error) {
    console.error("Error fetching departments:", error);
  }
};

const fetchTicketCategories = async () => {
  try {
    const { data } = await $api("/ticket-categories", {
      method: "GET",
      headers: {
        Authorization: `Bearer ${accessToken.value}`,
      },
    });

    categories.value = data.map((item) => ({
      id: item.id,
      name: item.name,
    }));
  } catch (error) {
    console.error("Error fetching ticket categories:", error);
  }
};

// Fetch all required data
const fetchAllData = async () => {
  try {
    await Promise.all([
      fetchEmployees(),
      fetchDepartments(),
      fetchTicketCategories(),
    ]);
  } catch (error) {
    console.error("Error fetching data:", error);
  }
};

// Load data when component mounts
onMounted(() => {
  fetchAllData();
});

// Load data when drawer opens or ticket prop changes
watch(
  () => props.isOpen,
  (isOpen) => {
    if (isOpen) {
      if (isEditMode.value) {
        populateForm();
      } else {
        resetForm();
      }
    }
  }
);

// Watch for ticket prop changes
watch(
  () => props.ticket,
  (newTicket) => {
    if (newTicket && props.isOpen) {
      populateForm();
    }
  }
);

// Populate form with ticket data for editing
// Populate form with ticket data for editing
const populateForm = async () => {
  if (props.ticket) {
    try {
      // Fetch the full ticket details with relationships to get IDs
      const response = await $api(`/tickets/${props.ticket.id}`, {
        headers: {
          Authorization: `Bearer ${accessToken.value}`,
        },
      });

      const ticket = response.data;

      console.log("Fetched ticket for editing:", ticket);

      form.value = {
        employee_id: ticket.employee_id || null,
        department_id: ticket.department_id || null,
        poc_id: ticket.poc_id || null,
        category_id: ticket.category_id || null,
        description: ticket.description || "",
        attachment: null,
        status: ticket.status || "open",
      };

      errors.value = {};
    } catch (error) {
      console.error("Error fetching ticket details:", error);
      // Fallback: try to use the basic ticket data from props
      form.value = {
        employee_id: props.ticket.employee_id || null,
        department_id: props.ticket.department_id || null,
        poc_id: props.ticket.poc_id || null,
        category_id: props.ticket.category_id || null,
        description: props.ticket.description || "",
        attachment: null,
        status: props.ticket.status || "open",
      };
    }
  }
};

// Close drawer handler
const closeNavigationDrawer = () => {
  emit("update:isOpen", false);
};

// Reset form
const resetForm = () => {
  form.value = {
    employee_id: null,
    department_id: null,
    poc_id: null,
    category_id: null,
    description: "",
    attachment: null,
    status: "open",
  };
  errors.value = {};

  // Reset file input
  const fileInput = document.querySelector('input[type="file"]');
  if (fileInput) {
    fileInput.value = "";
  }
};

// Handle file attachment
const handleAttachment = (event) => {
  if (event.target.files && event.target.files[0]) {
    form.value.attachment = event.target.files[0];
  }
};

// Remove attachment
const removeAttachment = () => {
  form.value.attachment = null;
  const fileInput = document.querySelector('input[type="file"]');
  if (fileInput) {
    fileInput.value = "";
  }
};

// Form submission
// Form submission
const onSubmit = async () => {
  try {
    const { valid } = await refForm.value.validate();

    if (!valid) {
      console.log("Form validation failed");
      return;
    }

    isLoading.value = true;
    errors.value = {};

    console.log(
      "Form data before submission:",
      JSON.parse(JSON.stringify(form.value))
    );

    const formDataToSend = new FormData();

    // Append all form fields
    Object.entries(form.value).forEach(([key, value]) => {
      if (key !== "attachment" && value !== null && value !== "") {
        console.log(`Appending ${key}:`, value);
        formDataToSend.append(key, value);
      }
    });

    // Append attachment if exists
    if (form.value.attachment) {
      console.log("Appending attachment:", form.value.attachment);
      formDataToSend.append("attachment", form.value.attachment);
    }

    // Debug: Log all FormData entries
    console.log("FormData entries:");
    for (let [key, value] of formDataToSend.entries()) {
      console.log(key, value);
    }

    let response;
    if (isEditMode.value) {
      console.log("Updating ticket:", props.ticket.id);
      response = await $api(`/tickets/${props.ticket.id}`, {
        method: "POST",
        body: formDataToSend,
        headers: {
          Authorization: `Bearer ${accessToken.value}`,
          "X-Requested-With": "XMLHttpRequest",
        },
      });
    } else {
      console.log("Creating new ticket");
      response = await $api("/tickets", {
        method: "POST",
        body: formDataToSend,
        headers: {
          Authorization: `Bearer ${accessToken.value}`,
          "X-Requested-With": "XMLHttpRequest",
        },
      });
    }

    // SUCCESS - clear errors and close drawer
    errors.value = {};
    emit("submit");
    resetForm();
    closeNavigationDrawer();
  } catch (error) {
    console.error("Submission error:", error);

    if (error.response?.status === 422) {
      errors.value = error.response.data.errors;
      console.error("Validation errors:", error.response.data.errors);
    } else {
      console.error("Unexpected error:", error);
    }
  } finally {
    isLoading.value = false;
  }
};

const handleDrawerModelValueUpdate = (val) => {
  emit("update:isOpen", val);
};
</script>

<template>
  <VNavigationDrawer
    temporary
    :width="500"
    location="end"
    class="scrollable-content"
    :model-value="props.isOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <!-- Title -->
    <div class="d-flex justify-space-between align-center pa-6">
      <h3 class="text-h4">{{ isEditMode ? "Edit" : "Create New" }} Ticket</h3>
      <VBtn
        icon
        variant="text"
        color="default"
        size="small"
        @click="closeNavigationDrawer"
      >
        <VIcon icon="tabler-x" size="24" />
      </VBtn>
    </div>

    <VDivider />

    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat>
        <VCardText class="pa-6">
          <VForm ref="refForm" v-model="isFormValid" @submit.prevent="onSubmit">
            <VRow>
              <!-- Ticket for Employee -->
              <VCol cols="12">
                <label class="text-body-1 text-high-emphasis mb-2 d-block"
                  >Ticket for Employee *</label
                >
                <AppSelect
                  v-model="form.employee_id"
                  :items="employees"
                  item-title="name"
                  item-value="id"
                  placeholder="Select Employee"
                  :rules="[requiredValidator]"
                  :error-messages="errors.employee_id"
                />
              </VCol>

              <!-- Row with 2 columns -->
              <VCol cols="12" md="6">
                <label class="text-body-1 text-high-emphasis mb-2 d-block"
                  >Department *</label
                >
                <AppSelect
                  v-model="form.department_id"
                  :items="departments"
                  item-title="name"
                  item-value="id"
                  placeholder="Select Department"
                  :rules="[requiredValidator]"
                  :error-messages="errors.department_id"
                />
              </VCol>
              <VCol cols="12" md="6">
                <label class="text-body-1 text-high-emphasis mb-2 d-block"
                  >POC *</label
                >
                <AppSelect
                  v-model="form.poc_id"
                  :items="employees"
                  item-title="name"
                  item-value="id"
                  placeholder="Select POC"
                  :rules="[requiredValidator]"
                  :error-messages="errors.poc_id"
                />
              </VCol>

              <!-- Category -->
              <VCol cols="12">
                <label class="text-body-1 text-high-emphasis mb-2 d-block"
                  >Category *</label
                >
                <AppSelect
                  v-model="form.category_id"
                  :items="categories"
                  item-title="name"
                  item-value="id"
                  placeholder="Select Category"
                  :rules="[requiredValidator]"
                  :error-messages="errors.category_id"
                />
              </VCol>

              <!-- Description -->
              <VCol cols="12">
                <label class="text-body-1 text-high-emphasis mb-2 d-block"
                  >Description *</label
                >
                <AppTextarea
                  v-model="form.description"
                  placeholder="Ticket Description"
                  rows="3"
                  :rules="[requiredValidator]"
                  :error-messages="errors.description"
                />
              </VCol>

              <!-- Attachment -->
              <VCol cols="12">
                <label class="text-body-1 text-high-emphasis mb-2 d-block"
                  >Ticket Attachment</label
                >
                <div class="d-flex align-center">
                  <VBtn
                    color="primary"
                    variant="outlined"
                    class="mr-4"
                    @click="$refs.fileInput.click()"
                  >
                    <VIcon icon="tabler-upload" class="mr-2" />
                    Choose File
                  </VBtn>
                  <div class="d-flex flex-column">
                    <span class="text-body-1">
                      {{
                        form.attachment
                          ? form.attachment.name
                          : "No file chosen"
                      }}
                    </span>
                    <span
                      v-if="form.attachment"
                      class="text-caption text-disabled"
                    >
                      {{ form.attachment.size | fileSize }}
                    </span>
                  </div>
                  <input
                    ref="fileInput"
                    type="file"
                    hidden
                    @change="handleAttachment"
                  />
                </div>
                <div
                  v-if="errors.attachment"
                  class="text-error mt-1 text-caption"
                >
                  {{ errors.attachment[0] }}
                </div>
                <VBtn
                  v-if="form.attachment"
                  size="small"
                  variant="text"
                  color="error"
                  class="mt-2"
                  @click="removeAttachment"
                >
                  <VIcon icon="tabler-x" size="16" class="mr-1" />
                  Remove File
                </VBtn>
              </VCol>

              <!-- Status -->
              <VCol cols="12">
                <label class="text-body-1 text-high-emphasis mb-2 d-block"
                  >Status *</label
                >
                <AppSelect
                  v-model="form.status"
                  :items="statuses"
                  item-title="title"
                  item-value="value"
                  :rules="[requiredValidator]"
                  :error-messages="errors.status"
                />
              </VCol>

              <!-- Submit and Cancel -->
              <VCol cols="12" class="d-flex justify-end gap-4 mt-6">
                <VBtn
                  type="submit"
                  :loading="isLoading || props.loading"
                  :disabled="isLoading || props.loading"
                  prepend-icon="tabler-check"
                  color="primary"
                >
                  <template v-if="isLoading || props.loading">
                    {{ isEditMode ? "Updating..." : "Creating..." }}
                  </template>
                  <template v-else>
                    {{ isEditMode ? "Update Ticket" : "Create Ticket" }}
                  </template>
                </VBtn>
                <VBtn
                  variant="tonal"
                  color="error"
                  prepend-icon="tabler-x"
                  @click="closeNavigationDrawer"
                  :disabled="isLoading || props.loading"
                >
                  Cancel
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </PerfectScrollbar>
  </VNavigationDrawer>
</template>

<style scoped>
.scrollable-content {
  block-size: 100%;
  overflow-y: auto;
}

.v-card-text {
  padding: 1.5rem !important;
}
</style>
