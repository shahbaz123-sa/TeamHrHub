<template>
  <VForm ref="form" @submit.prevent="submitForm">
    <VCard>
      <!-- Title Section -->
      {{ formTitle }}

      <!-- Personal Information & Company Detail Sections Side-by-Side -->
      <VRow class="ma-0 pa-0">
        <!-- Left Column: Personal Information -->
        <VCol cols="12" md="6" class="border-e">
          <VCardText>
            <h3 class="text-h6 mb-4">Personal information</h3>

            <VRow>
              <!-- Name & Father Name -->
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="formData.name"
                  label="Name*"
                  placeholder="Enter full name"
                  :rules="[requiredRule]"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="formData.father_name"
                  label="Father Name*"
                  placeholder="Enter father's name"
                  :rules="[requiredRule]"
                />
              </VCol>

              <!-- Phone No & Emergency Contact Name -->
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="formData.phone"
                  label="Phone No*"
                  placeholder="Enter phone number"
                  :rules="[requiredRule, phoneRule]"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="formData.emergency_contact_name"
                  label="Emergency Contact Name*"
                  placeholder="Enter contact name"
                  :rules="[requiredRule]"
                />
              </VCol>

              <!-- Emergency Contact Number & Date of Birth -->
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="formData.emergency_phone"
                  label="Emergency Contact Number*"
                  placeholder="Enter contact number"
                  :rules="[requiredRule, phoneRule]"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppDateTimePicker
                  v-model="formData.dob"
                  label="Date of Birth*"
                  placeholder="DD-MM-YYYY"
                  :config="{ dateFormat: 'd-m-Y' }"
                  :rules="[requiredRule]"
                />
              </VCol>

              <!-- CNIC & Gender -->
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="formData.cnic"
                  label="CNIC*"
                  placeholder="Enter CNIC number"
                  :rules="[requiredRule, cnicRule]"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppSelect
                  v-model="formData.gender"
                  label="Gender*"
                  placeholder="Select Gender"
                  :items="['male', 'female', 'other']"
                  :rules="[requiredRule]"
                />
              </VCol>

              <!-- Blood Group & Marital Status -->
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="formData.blood_group"
                  label="Blood Group*"
                  placeholder="Enter blood group"
                  :rules="[requiredRule]"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppSelect
                  v-model="formData.marital_status"
                  label="Marital Status*"
                  placeholder="Select Status"
                  :items="['single', 'married', 'divorced', 'widowed']"
                  :rules="[requiredRule]"
                />
              </VCol>

              <!-- No of Dependents & Personal Email -->
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="formData.dependents"
                  label="No of Dependents"
                  placeholder="Enter number"
                  type="number"
                  min="0"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="formData.personal_email"
                  label="Personal Email*"
                  placeholder="email@example.com"
                  :rules="[requiredRule, emailRule]"
                />
              </VCol>

              <!-- Official Email & Password -->
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="formData.official_email"
                  label="Official Email"
                  placeholder="email@company.com"
                  :rules="[emailRule]"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="formData.official_email_password"
                  label="Password*"
                  placeholder="••••••••"
                  type="password"
                  :rules="[requiredRule, passwordRule]"
                />
              </VCol>

              <!-- Address (Full Width) -->
              <VCol cols="12">
                <AppTextarea
                  v-model="formData.address1"
                  label="Address*"
                  placeholder="Type your address"
                  rows="2"
                  :rules="[requiredRule]"
                />
              </VCol>
            </VRow>
          </VCardText>
        </VCol>

        <!-- Right Column: Company Detail -->
        <VCol cols="12" md="6">
          <VCardText>
            <h3 class="text-h6 mb-4">Company Detail</h3>

            <VRow>
              <!-- Employee ID & Designation -->
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="formData.employee_code"
                  label="Employee ID*"
                  placeholder="ZA-10012"
                  :rules="[requiredRule]"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppSelect
                  v-model="formData.designation_id"
                  label="Select Designation*"
                  placeholder="Select Designation"
                  :items="designations"
                  :rules="[requiredRule]"
                />
              </VCol>

              <!-- Department & Date of Joining -->
              <VCol cols="12" md="6">
                <AppSelect
                  v-model="formData.department_id"
                  label="Select Department*"
                  placeholder="Select"
                  :items="departments"
                  :rules="[requiredRule]"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppDateTimePicker
                  v-model="formData.date_of_joining"
                  label="Date of Joining*"
                  placeholder="Select date"
                  :rules="[requiredRule]"
                />
              </VCol>

              <!-- Reporting To & Bonus -->
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="formData.reporting_to"
                  label="Reporting To*"
                  placeholder="Enter name"
                  :rules="[requiredRule]"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="formData.bonus"
                  label="Bonus"
                  placeholder="Enter amount"
                  type="number"
                  min="0"
                />
              </VCol>

              <!-- Work Agreement & Add More Button -->
              <VCol cols="12" md="6">
                <AppSelect
                  v-model="formData.employment_type"
                  label="Employment Type*"
                  placeholder="Select"
                  :items="['full_time', 'part_time', 'contract', 'temporary']"
                  :rules="[requiredRule]"
                />
              </VCol>
              <VCol cols="12" md="6" class="d-flex align-end">
                <VBtn color="primary" class="mt-3" @click="addMore">
                  Add more
                </VBtn>
              </VCol>
            </VRow>
          </VCardText>

          <VCardText>
            <h3 class="text-h6 mb-4">Document</h3>
            <VRow>
              <VCol cols="12">
                <VFileInput
                  v-model="formData.photo"
                  label="Passport Size Photo*"
                  placeholder="Upload passport photo"
                  :rules="[requiredRule]"
                  prepend-icon="tabler-camera"
                  clearable
                  density="comfortable"
                  variant="outlined"
                />
              </VCol>
              <VCol cols="12">
                <VFileInput
                  v-model="formData.cnic_document"
                  label="CNIC Front*"
                  placeholder="Upload CNIC front"
                  :rules="[requiredRule]"
                  prepend-icon="tabler-id"
                  clearable
                  density="comfortable"
                  variant="outlined"
                />
              </VCol>
              <VCol cols="12">
                <VFileInput
                  v-model="formData.resume"
                  label="Resume*"
                  placeholder="Upload resume"
                  :rules="[requiredRule]"
                  prepend-icon="tabler-file"
                  clearable
                  density="comfortable"
                  variant="outlined"
                />
              </VCol>
            </VRow>
          </VCardText>
        </VCol>
      </VRow>

      <!-- Device Detail Section -->
      <VDivider />
      <VRow class="ma-0 pa-0">
        <!-- Left Column: Device Detail -->
        <VCol cols="12" md="6" class="border-e">
          <VCardText>
            <h3 class="text-h6 mb-4">Device Detail</h3>
            <VRow>
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="formData.machine_name"
                  label="Machine Name*"
                  placeholder="Enter device name"
                  :rules="[requiredRule]"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppSelect
                  v-model="formData.system_processor"
                  label="System Processor*"
                  placeholder="Select processor"
                  :items="processors"
                  :rules="[requiredRule]"
                />
              </VCol>

              <VCol cols="12" md="6">
                <AppTextField
                  v-model="formData.system_type"
                  label="System Type (64-bit OS)*"
                  placeholder="Enter system type"
                  :rules="[requiredRule]"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppSelect
                  v-model="formData.headphone"
                  label="Headphone*"
                  placeholder="Select option"
                  :items="['Yes', 'No']"
                  :rules="[requiredRule]"
                />
              </VCol>

              <VCol cols="12" md="6">
                <AppSelect
                  v-model="formData.machine_password"
                  label="Machine Password*"
                  placeholder="Select option"
                  :items="['Enabled', 'Disabled']"
                  :rules="[requiredRule]"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="formData.installed_ram"
                  label="Installed RAM (GB)*"
                  placeholder="Enter RAM size"
                  type="number"
                  :rules="[requiredRule]"
                />
              </VCol>

              <VCol cols="12" md="6">
                <AppSelect
                  v-model="formData.mouse"
                  label="Mouse*"
                  placeholder="Select option"
                  :items="['Provided', 'Not Provided']"
                  :rules="[requiredRule]"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppSelect
                  v-model="formData.laptop_charger"
                  label="Laptop Charger*"
                  placeholder="Select option"
                  :items="['Provided', 'Not Provided']"
                  :rules="[requiredRule]"
                />
              </VCol>
            </VRow>
          </VCardText>
        </VCol>

        <!-- Right Column: Account Detail -->
        <VCol cols="12" md="6">
          <VCardText>
            <h3 class="text-h6 mb-4">Account Detail</h3>
            <VRow>
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="formData.account_holder_name"
                  label="Account Holder Name*"
                  placeholder="Enter account holder name"
                  :rules="[requiredRule]"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="formData.account_number"
                  label="Account Number*"
                  placeholder="Enter account number"
                  :rules="[requiredRule]"
                />
              </VCol>

              <VCol cols="12" md="4">
                <AppSelect
                  v-model="formData.bank_name"
                  label="Bank Name*"
                  placeholder="Select bank"
                  :items="banks"
                  :rules="[requiredRule]"
                />
              </VCol>
              <VCol cols="12" md="4">
                <AppSelect
                  v-model="formData.branch_location"
                  label="Branch Location*"
                  placeholder="Select branch"
                  :items="locations"
                  :rules="[requiredRule]"
                />
              </VCol>
              <VCol cols="12" md="4">
                <AppSelect
                  v-model="formData.bank_identifier_code"
                  label="Bank Identifier Code*"
                  placeholder="Select code"
                  :items="codes"
                  :rules="[requiredRule]"
                />
              </VCol>
            </VRow>
          </VCardText>
        </VCol>
      </VRow>

      <!-- Form Actions -->
      <VCardActions class="justify-end mt-4">
        <VBtn type="submit" color="primary" :loading="isSubmitting">
          {{ isEditMode ? "Update" : "Create" }} Employee
        </VBtn>

        <VBtn
          color="secondary"
          variant="tonal"
          @click="router.push({ name: 'hrm-employees-list' })"
        >
          Cancel
        </VBtn>
      </VCardActions>

      <!-- Notification Snackbar -->
      <VSnackbar
        v-model="snackbar.visible"
        :timeout="3000"
        :color="snackbar.color"
        location="top right"
      >
        {{ snackbar.message }}
      </VSnackbar>
    </VCard>
  </VForm>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useToast } from "vue-toast-notification";

const route = useRoute();
const router = useRouter();
const toast = useToast();
const accessToken = useCookie("accessToken");
console.log("imran erp routes", router.getRoutes());

// Form mode (add/edit)
const isEditMode = computed(() => !!route.params.id);
const formTitle = computed(() =>
  isEditMode.value ? "Edit Employee" : "Add New Employee"
);

// Form data model
const formData = ref({
  name: "",
  father_name: "",
  phone: "",
  emergency_contact_name: "",
  emergency_phone: "",
  dob: "",
  cnic: "",
  gender: "male",
  blood_group: "",
  marital_status: "single",
  dependents: "",
  personal_email: "",
  official_email: "",
  official_email_password: "",
  address1: "",
  address2: "",
  employee_code: "",
  designation_id: "",
  department_id: "",
  date_of_joining: "",
  reporting_to: "",
  bonus: "",
  employment_type: "full_time",
  employment_status: "active",
  // ... other fields
});

// Load employee data for edit
const loadEmployeeData = async () => {
  if (!isEditMode.value) return;

  try {
    const { data } = await $api(`/employees/${route.params.id}`, {
      headers: {
        Authorization: `Bearer ${accessToken.value}`,
      },
    });
    formData.value = data;
  } catch (error) {
    console.error("Error loading employee data:", error);
    toast.error("Failed to load employee data");
  }
};

// Form submission
const submitForm = async () => {
  try {
    if (isEditMode.value) {
      await $api.put(`/employees/${route.params.id}`, formData.value, {
        headers: {
          Authorization: `Bearer ${accessToken.value}`,
        },
      });
      toast.success("Employee updated successfully");
    } else {
      await $api.post("/employees", formData.value, {
        headers: {
          Authorization: `Bearer ${accessToken.value}`,
        },
      });
      toast.success("Employee created successfully");
    }

    // Redirect to employee list after successful submission
    router.push({ name: "hrm-employees-list" });
  } catch (error) {
    console.error("Form submission error:", error);
    toast.error("Failed to save employee data");
  }
};

// Load data when component mounts
onMounted(loadEmployeeData);

// Dropdown options (move to separate composable if reused)
const departments = ref([]);
const designations = ref([]);
const fetchDropdownData = async () => {
  try {
    const [deptRes, desigRes] = await Promise.all([
      $api("/departments?context=filters"),
      $api("/designations"),
    ]);

    departments.value = deptRes.data.data.map((d) => ({
      title: d.name,
      value: d.id,
    }));

    designations.value = desigRes.data.data.map((d) => ({
      title: d.title,
      value: d.id,
    }));
  } catch (error) {
    console.error("Error fetching dropdown data:", error);
  }
};

onMounted(fetchDropdownData);
onMounted(() => {
  router.push({ name: "hrm-employees-form" });
});
</script>

<style scoped>
.border-e {
  border-inline-end:
    1px solid
    rgba(var(--v-border-color), var(--v-border-opacity));
}

@media (max-width: 959px) {
  .border-e {
    border-block-end:
      1px solid
      rgba(var(--v-border-color), var(--v-border-opacity));
    border-inline-end: none;
    margin-block-end: 1.5rem;
    padding-block-end: 1.5rem;
  }
}

.v-divider {
  margin-block: 1.5rem;
  margin-inline: 0;
}
</style>
