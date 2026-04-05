<script setup>
import { useApi } from "@/composables/useApi";
import { ref } from "vue";
import { VForm } from "vuetify/components/VForm";

const { execute: fetchDepartments } = useApi("/departments?context=filters");
const { execute: fetchDesignations } = useApi("/designations");
const { execute: fetchBranches } = useApi("/branches");

const currentStep = ref(0);
const isCurrentStepValid = ref(true);
const refPersonalForm = ref();
const refCompanyForm = ref();
const refBankForm = ref();
const refDocumentsForm = ref();

const departments = ref([]);
const designations = ref([]);
const branches = ref([]);

const employeeForm = ref({
  // Personal Details
  name: "",
  father_name: "",
  phone: "",
  emergency_contact_name: "",
  emergency_contact_relation: "",
  emergency_phone: "",
  date_of_birth: "",
  gender: "",
  cnic: "",
  blood_group: "",
  marital_status: "",
  number_of_dependents: 0,
  personal_email: "",
  official_email: "",
  official_email_password: "",
  skype_profile_id: "",
  skype_profile_password: "",
  active_status: true,
  address_1: "",
  address_2: "",

  // Company Details
  branch_id: null,
  department_id: null,
  designation_id: null,
  employment_type: "full_time",
  employment_status: "active",
  date_of_joining: "",

  // Bank Details
  account_holder_name: "",
  bank_name: "",
  account_number: "",
  bank_identifier_code: "",
  iban: "",
  branch_location: "",
});

const loadOptions = async () => {
  try {
    const [deptRes, desgRes, branchRes] = await Promise.all([
      fetchDepartments(),
      fetchDesignations(),
      fetchBranches(),
    ]);

    departments.value = deptRes.data;
    designations.value = desgRes.data;
    branches.value = branchRes.data;
  } catch (error) {
    console.error("Error loading options:", error);
  }
};

const validatePersonalForm = () => {
  refPersonalForm.value?.validate().then((valid) => {
    if (valid.valid) {
      currentStep.value++;
      isCurrentStepValid.value = true;
    } else {
      isCurrentStepValid.value = false;
    }
  });
};

const validateCompanyForm = () => {
  refCompanyForm.value?.validate().then((valid) => {
    if (valid.valid) {
      currentStep.value++;
      isCurrentStepValid.value = true;
    } else {
      isCurrentStepValid.value = false;
    }
  });
};

const validateBankForm = () => {
  refBankForm.value?.validate().then((valid) => {
    if (valid.valid) {
      currentStep.value++;
      isCurrentStepValid.value = true;
    } else {
      isCurrentStepValid.value = false;
    }
  });
};

const submitForm = () => {
  // Handle form submission
  console.log("Form submitted:", employeeForm.value);
};

// Load options when component mounts
onMounted(loadOptions);
</script>

<template>
  <VCard>
    <VCardText>
      <!-- 👉 Stepper -->
      <AppStepper
        v-model:current-step="currentStep"
        :items="[
          { title: 'Personal Details' },
          { title: 'Company Details' },
          { title: 'Bank Details' },
          { title: 'Documents' },
          { title: 'Review & Submit' },
        ]"
        :is-active-step-valid="isCurrentStepValid"
      />
    </VCardText>

    <VDivider />

    <VCardText>
      <VWindow v-model="currentStep" class="disable-tab-transition">
        <!-- Personal Details -->
        <VWindowItem>
          <VForm ref="refPersonalForm" @submit.prevent="validatePersonalForm">
            <VRow>
              <VCol cols="12">
                <h6 class="text-h6 font-weight-medium mb-2">
                  Personal Information
                </h6>
                <p class="mb-6">Enter employee's personal details</p>
              </VCol>

              <VCol cols="12" md="6">
                <AppTextField
                  v-model="employeeForm.name"
                  label="Full Name"
                  :rules="[requiredValidator]"
                />
              </VCol>

              <VCol cols="12" md="6">
                <AppTextField
                  v-model="employeeForm.father_name"
                  label="Father's Name"
                />
              </VCol>

              <VCol cols="12" md="6">
                <AppTextField
                  v-model="employeeForm.phone"
                  label="Phone Number"
                  :rules="[requiredValidator, phoneValidator]"
                />
              </VCol>

              <VCol cols="12" md="6">
                <AppTextField
                  v-model="employeeForm.cnic"
                  label="CNIC"
                  :rules="[cnicValidator]"
                />
              </VCol>

              <VCol cols="12" md="6">
                <AppSelect
                  v-model="employeeForm.gender"
                  label="Gender"
                  :items="['Male', 'Female', 'Other']"
                />
              </VCol>

              <VCol cols="12" md="6">
                <AppTextField
                  v-model="employeeForm.date_of_birth"
                  label="Date of Birth"
                  type="date"
                />
              </VCol>

              <VCol cols="12" md="6">
                <AppSelect
                  v-model="employeeForm.blood_group"
                  label="Blood Group"
                  :items="['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']"
                />
              </VCol>

              <VCol cols="12" md="6">
                <AppSelect
                  v-model="employeeForm.marital_status"
                  label="Marital Status"
                  :items="['Single', 'Married', 'Divorced', 'Widowed']"
                />
              </VCol>

              <VCol cols="12" md="6">
                <AppTextField
                  v-model="employeeForm.personal_email"
                  label="Personal Email"
                  :rules="[emailValidator]"
                />
              </VCol>

              <VCol cols="12" md="6">
                <AppTextField
                  v-model="employeeForm.number_of_dependents"
                  label="Number of Dependents"
                  type="number"
                  min="0"
                />
              </VCol>

              <VCol cols="12">
                <AppTextField
                  v-model="employeeForm.address_1"
                  label="Address Line 1"
                />
              </VCol>

              <VCol cols="12">
                <AppTextField
                  v-model="employeeForm.address_2"
                  label="Address Line 2"
                />
              </VCol>

              <VCol cols="12">
                <div
                  class="d-flex flex-wrap gap-4 justify-sm-space-between justify-center mt-8"
                >
                  <VBtn color="secondary" variant="tonal" disabled>
                    <VIcon icon="tabler-arrow-left" start class="flip-in-rtl" />
                    Previous
                  </VBtn>

                  <VBtn type="submit">
                    Next
                    <VIcon icon="tabler-arrow-right" end class="flip-in-rtl" />
                  </VBtn>
                </div>
              </VCol>
            </VRow>
          </VForm>
        </VWindowItem>

        <!-- Company Details -->
        <VWindowItem>
          <VForm ref="refCompanyForm" @submit.prevent="validateCompanyForm">
            <VRow>
              <VCol cols="12">
                <h6 class="text-h6 font-weight-medium mb-2">
                  Company Information
                </h6>
                <p class="mb-6">Enter employee's company details</p>
              </VCol>

              <VCol cols="12" md="6">
                <AppSelect
                  v-model="employeeForm.department_id"
                  label="Department"
                  :items="departments"
                  item-title="name"
                  item-value="id"
                  :rules="[requiredValidator]"
                />
              </VCol>

              <VCol cols="12" md="6">
                <AppSelect
                  v-model="employeeForm.designation_id"
                  label="Designation"
                  :items="designations"
                  item-title="title"
                  item-value="id"
                  :rules="[requiredValidator]"
                />
              </VCol>

              <VCol cols="12" md="6">
                <AppSelect
                  v-model="employeeForm.branch_id"
                  label="Branch"
                  :items="branches"
                  item-title="name"
                  item-value="id"
                />
              </VCol>

              <VCol cols="12" md="6">
                <AppTextField
                  v-model="employeeForm.date_of_joining"
                  label="Date of Joining"
                  type="date"
                  :rules="[requiredValidator]"
                />
              </VCol>

              <VCol cols="12" md="6">
                <AppSelect
                  v-model="employeeForm.employment_type"
                  label="Employment Type"
                  :items="['Full Time', 'Part Time', 'Contract', 'Temporary']"
                  :rules="[requiredValidator]"
                />
              </VCol>

              <VCol cols="12" md="6">
                <AppSelect
                  v-model="employeeForm.employment_status"
                  label="Employment Status"
                  :items="['Active', 'On Leave', 'Terminated', 'Suspended']"
                  :rules="[requiredValidator]"
                />
              </VCol>

              <VCol cols="12" md="6">
                <AppTextField
                  v-model="employeeForm.official_email"
                  label="Official Email"
                  :rules="[requiredValidator, emailValidator]"
                />
              </VCol>

              <VCol cols="12" md="6">
                <AppTextField
                  v-model="employeeForm.official_email_password"
                  label="Email Password"
                />
              </VCol>

              <VCol cols="12" md="6">
                <AppTextField
                  v-model="employeeForm.skype_profile_id"
                  label="Skype ID"
                />
              </VCol>

              <VCol cols="12" md="6">
                <AppTextField
                  v-model="employeeForm.skype_profile_password"
                  label="Skype Password"
                />
              </VCol>

              <VCol cols="12">
                <div
                  class="d-flex flex-wrap gap-4 justify-sm-space-between justify-center mt-8"
                >
                  <VBtn
                    color="secondary"
                    variant="tonal"
                    @click="currentStep--"
                  >
                    <VIcon icon="tabler-arrow-left" start class="flip-in-rtl" />
                    Previous
                  </VBtn>

                  <VBtn type="submit">
                    Next
                    <VIcon icon="tabler-arrow-right" end class="flip-in-rtl" />
                  </VBtn>
                </div>
              </VCol>
            </VRow>
          </VForm>
        </VWindowItem>

        <!-- Bank Details -->
        <VWindowItem>
          <VForm ref="refBankForm" @submit.prevent="validateBankForm">
            <VRow>
              <VCol cols="12">
                <h6 class="text-h6 font-weight-medium mb-2">
                  Bank Information
                </h6>
                <p class="mb-6">Enter employee's bank details</p>
              </VCol>

              <VCol cols="12" md="6">
                <AppTextField
                  v-model="employeeForm.account_holder_name"
                  label="Account Holder Name"
                />
              </VCol>

              <VCol cols="12" md="6">
                <AppTextField
                  v-model="employeeForm.bank_name"
                  label="Bank Name"
                />
              </VCol>

              <VCol cols="12" md="6">
                <AppTextField
                  v-model="employeeForm.account_number"
                  label="Account Number"
                />
              </VCol>

              <VCol cols="12" md="6">
                <AppTextField
                  v-model="employeeForm.bank_identifier_code"
                  label="Bank Identifier Code"
                />
              </VCol>

              <VCol cols="12" md="6">
                <AppTextField v-model="employeeForm.iban" label="IBAN" />
              </VCol>

              <VCol cols="12" md="6">
                <AppTextField
                  v-model="employeeForm.branch_location"
                  label="Branch Location"
                />
              </VCol>

              <VCol cols="12">
                <div
                  class="d-flex flex-wrap gap-4 justify-sm-space-between justify-center mt-8"
                >
                  <VBtn
                    color="secondary"
                    variant="tonal"
                    @click="currentStep--"
                  >
                    <VIcon icon="tabler-arrow-left" start class="flip-in-rtl" />
                    Previous
                  </VBtn>

                  <VBtn type="submit">
                    Next
                    <VIcon icon="tabler-arrow-right" end class="flip-in-rtl" />
                  </VBtn>
                </div>
              </VCol>
            </VRow>
          </VForm>
        </VWindowItem>

        <!-- Documents -->
        <VWindowItem>
          <VForm ref="refDocumentsForm">
            <VRow>
              <VCol cols="12">
                <h6 class="text-h6 font-weight-medium mb-2">Documents</h6>
                <p class="mb-6">Upload employee's documents</p>
              </VCol>

              <VCol cols="12" md="6">
                <AppFileUploader label="Resume" />
              </VCol>

              <VCol cols="12" md="6">
                <AppFileUploader label="CNIC" />
              </VCol>

              <VCol cols="12" md="6">
                <AppFileUploader label="Photograph" />
              </VCol>

              <VCol cols="12" md="6">
                <AppFileUploader label="Offer Letter" />
              </VCol>

              <VCol cols="12" md="6">
                <AppFileUploader label="Experience Letter" />
              </VCol>

              <VCol cols="12" md="6">
                <AppFileUploader label="Contract" />
              </VCol>

              <VCol cols="12">
                <div
                  class="d-flex flex-wrap gap-4 justify-sm-space-between justify-center mt-8"
                >
                  <VBtn
                    color="secondary"
                    variant="tonal"
                    @click="currentStep--"
                  >
                    <VIcon icon="tabler-arrow-left" start class="flip-in-rtl" />
                    Previous
                  </VBtn>

                  <VBtn @click="currentStep++">
                    Next
                    <VIcon icon="tabler-arrow-right" end class="flip-in-rtl" />
                  </VBtn>
                </div>
              </VCol>
            </VRow>
          </VForm>
        </VWindowItem>

        <!-- Review & Submit -->
        <VWindowItem>
          <VRow>
            <VCol cols="12">
              <h6 class="text-h6 font-weight-medium mb-2">
                Review Information
              </h6>
              <p class="mb-6">Review all information before submission</p>
            </VCol>

            <VCol cols="12">
              <VCard variant="tonal">
                <VCardText>
                  <h6 class="text-h6 mb-4">Personal Details</h6>
                  <VTable>
                    <tbody>
                      <tr>
                        <td>Name</td>
                        <td>{{ employeeForm.name }}</td>
                      </tr>
                      <tr>
                        <td>Father's Name</td>
                        <td>{{ employeeForm.father_name }}</td>
                      </tr>
                      <tr>
                        <td>Phone</td>
                        <td>{{ employeeForm.phone }}</td>
                      </tr>
                      <tr>
                        <td>CNIC</td>
                        <td>{{ employeeForm.cnic }}</td>
                      </tr>
                      <tr>
                        <td>Date of Birth</td>
                        <td>{{ employeeForm.date_of_birth }}</td>
                      </tr>
                      <tr>
                        <td>Gender</td>
                        <td>{{ employeeForm.gender }}</td>
                      </tr>
                    </tbody>
                  </VTable>
                </VCardText>
              </VCard>
            </VCol>

            <VCol cols="12">
              <VCard variant="tonal">
                <VCardText>
                  <h6 class="text-h6 mb-4">Company Details</h6>
                  <VTable>
                    <tbody>
                      <tr>
                        <td>Department</td>
                        <td>
                          {{
                            departments.find(
                              (d) => d.id === employeeForm.department_id
                            )?.name
                          }}
                        </td>
                      </tr>
                      <tr>
                        <td>Designation</td>
                        <td>
                          {{
                            designations.find(
                              (d) => d.id === employeeForm.designation_id
                            )?.title
                          }}
                        </td>
                      </tr>
                      <tr>
                        <td>Branch</td>
                        <td>
                          {{
                            branches.find(
                              (b) => b.id === employeeForm.branch_id
                            )?.name
                          }}
                        </td>
                      </tr>
                      <tr>
                        <td>Date of Joining</td>
                        <td>{{ employeeForm.date_of_joining }}</td>
                      </tr>
                      <tr>
                        <td>Employment Type</td>
                        <td>{{ employeeForm.employment_type }}</td>
                      </tr>
                      <tr>
                        <td>Official Email</td>
                        <td>{{ employeeForm.official_email }}</td>
                      </tr>
                    </tbody>
                  </VTable>
                </VCardText>
              </VCard>
            </VCol>

            <VCol cols="12">
              <VCard variant="tonal">
                <VCardText>
                  <h6 class="text-h6 mb-4">Bank Details</h6>
                  <VTable>
                    <tbody>
                      <tr>
                        <td>Account Holder Name</td>
                        <td>{{ employeeForm.account_holder_name }}</td>
                      </tr>
                      <tr>
                        <td>Bank Name</td>
                        <td>{{ employeeForm.bank_name }}</td>
                      </tr>
                      <tr>
                        <td>Account Number</td>
                        <td>{{ employeeForm.account_number }}</td>
                      </tr>
                      <tr>
                        <td>IBAN</td>
                        <td>{{ employeeForm.iban }}</td>
                      </tr>
                    </tbody>
                  </VTable>
                </VCardText>
              </VCard>
            </VCol>

            <VCol cols="12">
              <div class="d-flex flex-wrap gap-4 justify-space-between mt-8">
                <VBtn color="secondary" variant="tonal" @click="currentStep--">
                  <VIcon icon="tabler-arrow-left" start class="flip-in-rtl" />
                  Previous
                </VBtn>

                <VBtn color="success" @click="submitForm"> Submit </VBtn>
              </div>
            </VCol>
          </VRow>
        </VWindowItem>
      </VWindow>
    </VCardText>
  </VCard>
</template>
