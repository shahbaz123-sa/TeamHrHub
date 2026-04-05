<script setup>
import axios from "axios";
import { defineEmits, defineProps, nextTick, onMounted, ref } from "vue";
import { PerfectScrollbar } from "vue3-perfect-scrollbar";
const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  saveError: {
    // Add this prop
    type: String,
    default: null,
  },
});

const emit = defineEmits(["update:isDrawerOpen", "userData", "closeDrawer"]);

// Form state
const isFormValid = ref(false);
const refForm = ref();
const isLoading = ref(false);
const accessToken = useCookie("accessToken");

// Dropdown data
const branches = ref([]);
const departments = ref([]);
const designations = ref([]);
const isFetchingDropdowns = ref(false);

// Form fields - Updated to match Employee model
const name = ref("");
const fatherName = ref("");
const phone = ref("");
const emergencyContactName = ref("");
const emergencyContactRelation = ref("");
const emergencyPhone = ref("");
const dob = ref("");
const gender = ref("");
const cnic = ref("");
const bloodGroup = ref("");
const maritalStatus = ref("");
const employmentType = ref("");
const employmentStatus = ref("active");
const personalEmail = ref("");
const officialEmail = ref("");
const officialEmailPassword = ref("");
const status = ref(true);
const address1 = ref("");
const address2 = ref("");
const branchId = ref("");
const departmentId = ref("");
const designationId = ref("");
const dateOfJoining = ref("");
const accountHolderName = ref("");
const bankName = ref("");
const accountNumber = ref("");
const bankIdentifierCode = ref("");
const iban = ref("");
const branchLocation = ref("");

// File upload refs
const resume = ref(null);
const experienceLetter = ref(null);
const salarySlip = ref(null);
const photo = ref(null);
const cnicDocument = ref(null);
const offerLetter = ref(null);
const contract = ref(null);

// Dropdown options
const dropdownOptions = ref({
  genders: [
    { title: "Male", value: "male" },
    { title: "Female", value: "female" },
    { title: "Other", value: "other" },
  ],
  bloodGroups: [
    { title: "A+", value: "A+" },
    { title: "A-", value: "A-" },
    { title: "B+", value: "B+" },
    { title: "B-", value: "B-" },
    { title: "AB+", value: "AB+" },
    { title: "AB-", value: "AB-" },
    { title: "O+", value: "O+" },
    { title: "O-", value: "O-" },
  ],
  maritalStatuses: [
    { title: "Single", value: "single" },
    { title: "Married", value: "married" },
    { title: "Divorced", value: "divorced" },
    { title: "Widowed", value: "widowed" },
  ],
  employmentTypes: [
    { title: "Full Time", value: "full_time" },
    { title: "Part Time", value: "part_time" },
    { title: "Contract", value: "contract" },
    { title: "Temporary", value: "temporary" },
  ],
  employmentStatuses: [
    { title: "Active", value: "active" },
    { title: "On Leave", value: "on_leave" },
    { title: "Suspended", value: "suspended" },
    { title: "Terminated", value: "terminated" },
  ],
});

// Close drawer handler
const closeNavigationDrawer = () => {
  emit("update:isDrawerOpen", false);
  nextTick(() => {
    refForm.value?.reset();
    refForm.value?.resetValidation();
  });
};

// Handle file upload
const handleFileUpload = (file, field) => {
  field.value = file;
};

// Form submission
const onSubmit = async () => {
  const { valid } = await refForm.value.validate();

  if (valid) {
    isLoading.value = true;

    try {
      const formData = new FormData();

      // Append all fields to formData
      formData.append("name", name.value);
      formData.append("father_name", fatherName.value);
      formData.append("phone", phone.value);
      formData.append("emergency_contact_name", emergencyContactName.value);
      formData.append(
        "emergency_contact_relation",
        emergencyContactRelation.value
      );
      formData.append("emergency_phone", emergencyPhone.value);
      formData.append("dob", dob.value);
      formData.append("gender", gender.value);
      formData.append("cnic", cnic.value);
      formData.append("blood_group", bloodGroup.value);
      formData.append("marital_status", maritalStatus.value);
      formData.append("employment_type", employmentType.value);
      formData.append("employment_status", employmentStatus.value);
      formData.append("personal_email", personalEmail.value);
      formData.append("official_email", officialEmail.value);
      formData.append("official_email_password", officialEmailPassword.value);
      formData.append("status", status.value);
      formData.append("address1", address1.value);
      formData.append("address2", address2.value);
      formData.append("branch_id", branchId.value);
      formData.append("department_id", departmentId.value);
      formData.append("designation_id", designationId.value);
      formData.append("date_of_joining", dateOfJoining.value);
      formData.append("account_holder_name", accountHolderName.value);
      formData.append("bank_name", bankName.value);
      formData.append("account_number", accountNumber.value);
      formData.append("bank_identifier_code", bankIdentifierCode.value);
      formData.append("iban", iban.value);
      formData.append("branch_location", branchLocation.value);

      // Append files if they exist
      if (resume.value) formData.append("resume", resume.value);
      if (experienceLetter.value)
        formData.append("experience_letter", experienceLetter.value);
      if (salarySlip.value) formData.append("salary_slip", salarySlip.value);
      if (photo.value) formData.append("photo", photo.value);
      if (cnicDocument.value)
        formData.append("cnic_document", cnicDocument.value);
      if (offerLetter.value) formData.append("offer_letter", offerLetter.value);
      if (contract.value) formData.append("contract", contract.value);

      emit("userData", formData);
      closeNavigationDrawer();
    } catch (error) {
      console.error("Error submitting form:", error);
    } finally {
      isLoading.value = false;
    }
  }
};

const handleDrawerModelValueUpdate = (val) => {
  emit("update:isDrawerOpen", val);
};

onMounted(async () => {
  isFetchingDropdowns.value = true;
  try {
    const [branchesRes, departmentsRes, designationsRes] = await Promise.all([
      axios.get("/api/branches", {
        headers: {
          Authorization: `Bearer ${accessToken.value}`,
        },
      }),
      axios.get("/api/departments?context=filters", {
        headers: {
          Authorization: `Bearer ${accessToken.value}`,
        },
      }),
      axios.get("/api/designations", {
        headers: {
          Authorization: `Bearer ${accessToken.value}`,
        },
      }),
    ]);

    // Access the data property from the response
    branches.value = branchesRes.data.data || [];
    departments.value = departmentsRes.data.data || [];
    designations.value = designationsRes.data.data || [];
  } catch (error) {
    console.error("Error fetching dropdown data:", error);
  } finally {
    isFetchingDropdowns.value = false;
  }
});

// Add this to handle manual close
const handleClose = () => {
  if (!isLoading.value) {
    emit("closeDrawer");
  }
};
</script>

<template>
  <VNavigationDrawer
    temporary
    :width="800"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
    persistent
  >
    <!-- 👉 Title -->
    <AppDrawerHeaderSection
      title="Add New Employee"
      @cancel="closeNavigationDrawer"
    />

    <VDivider />

    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat>
        <VCardText>
          <VAlert v-if="props.saveError" type="error" class="ma-4">
            {{ props.saveError }}
          </VAlert>
          <!-- 👉 Form -->
          <VForm ref="refForm" v-model="isFormValid" @submit.prevent="onSubmit">
            <VRow>
              <!-- 👉 Personal Information Section -->
              <VCol cols="12">
                <h6 class="text-h6 mb-3">Personal Information</h6>
              </VCol>

              <!-- 👉 Name -->
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="name"
                  :rules="[requiredValidator]"
                  label="Full Name"
                  placeholder="John Doe"
                />
              </VCol>

              <!-- 👉 Father's Name -->
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="fatherName"
                  label="Father's Name"
                  placeholder="Father's Name"
                />
              </VCol>

              <!-- 👉 Phone -->
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="phone"
                  :rules="[requiredValidator]"
                  label="Phone Number"
                  placeholder="+92234567890"
                />
              </VCol>

              <!-- 👉 CNIC -->
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="cnic"
                  label="CNIC"
                  placeholder="12345-6789012-3"
                />
              </VCol>

              <!-- 👉 Date of Birth -->
              <VCol cols="12" md="6">
                <AppDateTimePicker
                  v-model="dob"
                  label="Date of Birth"
                  placeholder="Select Date"
                  :config="{ dateFormat: 'Y-m-d' }"
                />
              </VCol>

              <!-- 👉 Gender -->
              <VCol cols="12" md="6">
                <AppSelect
                  v-model="gender"
                  label="Gender"
                  placeholder="Select Gender"
                  :items="dropdownOptions.genders"
                />
              </VCol>

              <!-- 👉 Blood Group -->
              <VCol cols="12" md="6">
                <AppSelect
                  v-model="bloodGroup"
                  label="Blood Group"
                  placeholder="Select Blood Group"
                  :items="dropdownOptions.bloodGroups"
                />
              </VCol>

              <!-- 👉 Marital Status -->
              <VCol cols="12" md="6">
                <AppSelect
                  v-model="maritalStatus"
                  label="Marital Status"
                  placeholder="Select Marital Status"
                  :items="dropdownOptions.maritalStatuses"
                />
              </VCol>

              <!-- 👉 Emergency Contact Section -->
              <VCol cols="12">
                <h6 class="text-h6 mb-3">Emergency Contact</h6>
              </VCol>

              <!-- 👉 Emergency Contact Name -->
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="emergencyContactName"
                  label="Emergency Contact Name"
                  placeholder="Contact Name"
                />
              </VCol>

              <!-- 👉 Emergency Contact Relation -->
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="emergencyContactRelation"
                  label="Relation"
                  placeholder="Relation to Employee"
                />
              </VCol>

              <!-- 👉 Emergency Phone -->
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="emergencyPhone"
                  label="Emergency Phone"
                  placeholder="+1234567890"
                />
              </VCol>

              <!-- 👉 Employment Information Section -->
              <VCol cols="12">
                <h6 class="text-h6 mb-3">Employment Information</h6>
              </VCol>

              <!-- 👉 Employment Type -->
              <VCol cols="12" md="6">
                <AppSelect
                  v-model="employmentType"
                  label="Employment Type"
                  placeholder="Select Employment Type"
                  :rules="[requiredValidator]"
                  :items="dropdownOptions.employmentTypes"
                />
              </VCol>

              <!-- 👉 Employment Status -->
              <VCol cols="12" md="6">
                <AppSelect
                  v-model="employmentStatus"
                  label="Employment Status"
                  placeholder="Select Employment Status"
                  :rules="[requiredValidator]"
                  :items="dropdownOptions.employmentStatuses"
                />
              </VCol>

              <!-- 👉 Department -->
              <!-- Department Select -->
              <VCol cols="12" md="6">
                <AppSelect
                  v-model="departmentId"
                  label="Department"
                  placeholder="Select Department"
                  :rules="[requiredValidator]"
                  :items="departments"
                  item-title="name"
                  item-value="id"
                />
              </VCol>

              <!-- 👉 Designation -->
              <VCol cols="12" md="6">
                <AppSelect
                  v-model="designationId"
                  label="Designation"
                  placeholder="Select Designation"
                  :rules="[requiredValidator]"
                  :items="designations"
                  :loading="isFetchingDropdowns"
                  item-title="title"
                  item-value="id"
                />
              </VCol>

              <!-- 👉 Branch -->
              <VCol cols="12" md="6">
                <AppSelect
                  v-model="branchId"
                  label="Branch"
                  placeholder="Select Branch"
                  :rules="[requiredValidator]"
                  :items="branches"
                  item-title="name"
                  item-value="id"
                />
              </VCol>

              <!-- 👉 Date of Joining -->
              <VCol cols="12" md="6">
                <AppDateTimePicker
                  v-model="dateOfJoining"
                  label="Date of Joining"
                  placeholder="Select Date"
                  :rules="[requiredValidator]"
                  :config="{ dateFormat: 'Y-m-d' }"
                />
              </VCol>

              <!-- 👉 Email Information Section -->
              <VCol cols="12">
                <h6 class="text-h6 mb-3">Email Information</h6>
              </VCol>

              <!-- 👉 Personal Email -->
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="personalEmail"
                  :rules="[emailValidator]"
                  label="Personal Email"
                  placeholder="personal@example.com"
                />
              </VCol>

              <!-- 👉 Official Email -->
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="officialEmail"
                  :rules="[requiredValidator, emailValidator]"
                  label="Official Email"
                  placeholder="official@company.com"
                />
              </VCol>

              <!-- 👉 Official Email Password -->
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="officialEmailPassword"
                  label="Official Email Password"
                  type="password"
                  placeholder="············"
                />
              </VCol>

              <!-- 👉 Address Information Section -->
              <VCol cols="12">
                <h6 class="text-h6 mb-3">Address Information</h6>
              </VCol>

              <!-- 👉 Address Line 1 -->
              <VCol cols="12">
                <AppTextField
                  v-model="address1"
                  label="Address Line 1"
                  placeholder="Street Address"
                />
              </VCol>

              <!-- 👉 Address Line 2 -->
              <VCol cols="12">
                <AppTextField
                  v-model="address2"
                  label="Address Line 2"
                  placeholder="Apartment, Suite, etc."
                />
              </VCol>

              <!-- 👉 Bank Information Section -->
              <VCol cols="12">
                <h6 class="text-h6 mb-3">Bank Information</h6>
              </VCol>

              <!-- 👉 Account Holder Name -->
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="accountHolderName"
                  label="Account Holder Name"
                  placeholder="Account Holder Name"
                />
              </VCol>

              <!-- 👉 Bank Name -->
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="bankName"
                  label="Bank Name"
                  placeholder="Bank Name"
                />
              </VCol>

              <!-- 👉 Account Number -->
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="accountNumber"
                  label="Account Number"
                  placeholder="Account Number"
                />
              </VCol>

              <!-- 👉 IBAN -->
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="iban"
                  label="IBAN"
                  placeholder="International Bank Account Number"
                />
              </VCol>

              <!-- 👉 Bank Identifier Code -->
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="bankIdentifierCode"
                  label="Bank Identifier Code"
                  placeholder="Bank Code"
                />
              </VCol>

              <!-- 👉 Branch Location -->
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="branchLocation"
                  label="Branch Location"
                  placeholder="Bank Branch Location"
                />
              </VCol>

              <!-- 👉 Documents Section -->
              <VCol cols="12">
                <h6 class="text-h6 mb-3">Documents</h6>
              </VCol>

              <!-- 👉 Photo -->
              <VCol cols="12" md="6">
                <VFileInput
                  v-model="photo"
                  label="Employee Photo"
                  prepend-icon="tabler-camera"
                  accept="image/*"
                />
              </VCol>

              <!-- 👉 CNIC Document -->
              <VCol cols="12" md="6">
                <VFileInput
                  v-model="cnicDocument"
                  label="CNIC Document"
                  prepend-icon="tabler-file"
                  accept=".pdf,.jpg,.jpeg,.png"
                />
              </VCol>

              <!-- 👉 Resume -->
              <VCol cols="12" md="6">
                <VFileInput
                  v-model="resume"
                  label="Resume/CV"
                  prepend-icon="tabler-file"
                  accept=".pdf,.doc,.docx"
                />
              </VCol>

              <!-- 👉 Experience Letter -->
              <VCol cols="12" md="6">
                <VFileInput
                  v-model="experienceLetter"
                  label="Experience Letter"
                  prepend-icon="tabler-file"
                  accept=".pdf,.jpg,.jpeg,.png"
                />
              </VCol>

              <!-- 👉 Salary Slip -->
              <VCol cols="12" md="6">
                <VFileInput
                  v-model="salarySlip"
                  label="Salary Slip"
                  prepend-icon="tabler-file"
                  accept=".pdf,.jpg,.jpeg,.png"
                />
              </VCol>

              <!-- 👉 Offer Letter -->
              <VCol cols="12" md="6">
                <VFileInput
                  v-model="offerLetter"
                  label="Offer Letter"
                  prepend-icon="tabler-file"
                  accept=".pdf,.jpg,.jpeg,.png"
                />
              </VCol>

              <!-- 👉 Contract -->
              <VCol cols="12" md="6">
                <VFileInput
                  v-model="contract"
                  label="Contract"
                  prepend-icon="tabler-file"
                  accept=".pdf,.doc,.docx"
                />
              </VCol>

              <!-- 👉 Submit and Cancel -->
              <VCol cols="12" class="d-flex justify-end gap-4 mt-6">
                <VBtn
                  type="submit"
                  :loading="isLoading || props.loading"
                  :disabled="isLoading || props.loading"
                >
                  Create Employee
                </VBtn>
                <VBtn
                  type="reset"
                  variant="tonal"
                  color="error"
                  :disabled="isLoading || props.loading"
                  @click="handleClose"
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
