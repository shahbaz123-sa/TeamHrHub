<template>
  <div>
    <VCard v-if="formLoaded">
      <VForm
        ref="form"
        @submit.prevent="submitForm"
      >
        <!-- Top Personal Info Card -->
        <VCard class="mb-6 pa-4 personal-info-card">
          <VRow class="align-center">
            <!-- Avatar -->
            <VCol
              cols="12"
              md="2"
              class="d-flex align-center justify-center px-8"
            >
              <VAvatar
                size="96"
                rounded="lg"
              >
                <DocumentImageViewer
                  :type="'avatar'"
                  :src="formData.photo ? getObjectUrl(formData.photo) : '/images/avatars/dummy.png'"
                  :pdf-title="formData.name"
                  avatar-size="96"
                />
              </VAvatar>
            </VCol>
            <VCol
              cols="12"
              md="10"
            >
              <VRow class="align-center">
                <!-- Name and Contact -->
                <VCol
                  cols="12"
                >
                  <VRow>
                    <VCol cols="12" md="3">
                      <AppTextField
                        v-model="formData.name"
                        label="Employee Name*"
                        placeholder="Enter full name"
                        :rules="[requiredRule, nameRule]"
                        :error-messages="errors.name"
                      />
                    </VCol>
                    <VCol cols="6" md="3">
                      <AppTextField
                        v-model="formData.employee_code"
                        label="Employee Code*"
                        :rules="[requiredRule, employeeCodeRule]"
                        :error-messages="errors.employee_code"
                      />
                    </VCol>
                    <VCol cols="12" md="3">
                      <AppTextField
                        v-model="formData.official_email"
                        label="Official Email*"
                        placeholder="email@company.com"
                        :rules="[requiredRule, emailRule]"
                        :error-messages="errors.official_email"
                      />
                    </VCol>
                    <VCol cols="12" md="3">
                      <AppTextField
                        v-model="formData.phone"
                        label="Phone*"
                        placeholder="Enter phone number"
                        :rules="[requiredRule, phoneRule]"
                      />
                    </VCol>
                  </VRow>
                </VCol>
                <!-- Summary info -->
                <VCol cols="12">
                  <VRow>
                    <VCol
                      cols="6"
                      md="3"
                    >
                      <AppSelect
                        v-model="formData.department_id"
                        label="Department*"
                        :items="departments"
                        placeholder="Select department"
                        :rules="[requiredRule]"
                      />
                    </VCol>
                    <VCol
                      cols="6"
                      md="3"
                    >
                      <AppSelect
                        v-model="formData.designation_id"
                        label="Designation*"
                        :items="designations"
                        placeholder="Select designation"
                        :rules="[requiredRule]"
                      />
                    </VCol>
                    <VCol
                      cols="6"
                      md="3"
                    >
                      <AppSelect
                        v-model="formData.reporting_to"
                        label="Reporting to"
                        :items="reportingTos"
                        placeholder="Select manager"
                      />
                    </VCol>
                    <VCol
                      cols="6"
                      md="3"
                    >
                      <AppSelect
                        v-model="formData.employment_status_id"
                        label="Employee Status*"
                        :items="employmentStatuses"
                        placeholder="Select status"
                        :rules="[requiredRule]"
                      />
                    </VCol>
                  </VRow>
                </VCol>
              </VRow>
            </VCol>
          </VRow>
        </VCard>
        <VTabs
        v-model="currentStep"
        class="sticky-tabs font-300"
        align-tabs="start"
      >
        <VTab
          v-for="(step, index) in steps"
          :key="index"
          :value="index"
          @click="scrollToSection(step.id)"
        >
          {{ step.title }}
        </VTab>
      </VTabs>
        <VCardText>
          <!-- 👉 Personal Details -->
          <div
            id="personal-details"
            class="form-section"
          >
            <h3 class="text-h4 text-primary">
              Personal Details
            </h3>
            <VRow>
              <!-- Father Name -->
              <VCol
                cols="12"
                md="4"
              >
                <AppTextField
                  v-model="formData.father_name"
                  label="Father Name*"
                  placeholder="Enter father's name"
                  :rules="[requiredRule, nameRule]"
                  :error-messages="errors.father_name"
                />
              </VCol>
              <!-- Emergency Contact Name -->
              <VCol
                cols="12"
                md="4"
              >
                <AppTextField
                  v-model="formData.emergency_contact_name"
                  label="Emergency Contact Name*"
                  placeholder="Enter contact name"
                  :rules="[requiredRule, nameRule]"
                  :error-messages="errors.emergency_contact_name"
                />
              </VCol>
              <!-- Emergency Contact Relation -->
              <VCol
                cols="12"
                md="4"
              >
                <AppTextField
                  v-model="formData.emergency_contact_relation"
                  label="Emergency Contact Relation"
                  placeholder="Enter relation"
                />
              </VCol>

              <!-- Emergency Contact Number & Date of Birth -->
              <VCol
                cols="12"
                md="4"
              >
                <AppTextField
                  v-model="formData.emergency_phone"
                  label="Emergency Contact Number*"
                  placeholder="Enter contact number"
                  :rules="[requiredRule, phoneRule]"
                  :error-messages="errors.emergency_phone"
                />
              </VCol>

              <!-- :config="{ dateFormat: 'Y-m-d' }" -->
              <VCol
                cols="12"
                md="4"
              >
                <AppDateTimePicker
                  v-model="formData.dob"
                  label="Date of Birth*"
                  placeholder="Select date"
                  :rules="[requiredRule, dateRule, dobRule]"
                  :error-messages="errors.dob"
                  :config="{ maxDate: maxDob }"
                />
              </VCol>

              <!-- CNIC & Gender -->
              <VCol
                cols="12"
                md="4"
              >
                <AppTextField
                  v-model="formData.cnic"
                  label="CNIC#*"
                  placeholder="Enter CNIC number"
                  :rules="[requiredRule, cnicRule]"
                  :error-messages="errors.cnic"
                />
              </VCol>
              <VCol
                cols="12"
                md="4"
              >
                <AppSelect
                  v-model="formData.gender"
                  label="Gender*"
                  placeholder="Select Gender"
                  :items="genders"
                  :rules="[requiredRule]"
                  :error-messages="errors.gender"
                />
              </VCol>

              <!-- Blood Group & Marital Status -->
              <VCol
                cols="12"
                md="4"
              >
                <AppSelect
                  v-model="formData.blood_group"
                  label="Blood Group*"
                  placeholder="Select blood group"
                  :items="bloodGroups"
                  :rules="[requiredRule]"
                  :error-messages="errors.blood_group"
                />
              </VCol>
              <VCol
                cols="12"
                md="4"
              >
                <AppSelect
                  v-model="formData.marital_status"
                  label="Marital Status*"
                  placeholder="Select Status"
                  :items="maritalStatuses"
                  :rules="[requiredRule]"
                  :error-messages="errors.marital_status"
                />
              </VCol>

              <!-- No of Dependents & Personal Email -->
              <VCol
                cols="12"
                md="4"
              >
                <AppTextField
                  v-model="formData.dependents"
                  label="No of Dependents"
                  placeholder="Enter number"
                  type="number"
                  min="0"
                  :rules="[numberRule]"
                  :error-messages="errors.dependents"
                />
              </VCol>
              <VCol
                cols="12"
                md="4"
              >
                <AppTextField
                  v-model="formData.personal_email"
                  label="Personal Email*"
                  placeholder="email@example.com"
                  :rules="[requiredRule, emailRule]"
                  :error-messages="errors.personal_email"
                />
              </VCol>
              <VCol
                cols="12"
                md="4"
              >
                <AppTextField
                  v-model="formData.official_email_password"
                  label="Password*"
                  placeholder="••••••••"
                  :rules="[requiredRule, passwordRule]"
                  :error-messages="errors.official_email_password"
                />
              </VCol>
              <VCol
                cols="12"
                md="8"
              />
              <!-- Address (Full Width) -->
              <VCol
                cols="12"
                md="6"
              >
                <AppTextarea
                  v-model="formData.address1"
                  label="Address 1*"
                  placeholder="Type your address"
                  rows="2"
                  :rules="[requiredRule]"
                  :error-messages="errors.address1"
                />
              </VCol>

              <!-- Address 2 -->
              <VCol
                cols="12"
                md="6"
              >
                <AppTextarea
                  v-model="formData.address2"
                  label="Address 2"
                  placeholder="Type your address"
                  rows="2"
                />
              </VCol>
            </VRow>
          </div>

          <!-- 👉 Company Details -->
          <div
            id="company-details"
            class="form-section"
          >
            <h3 class="text-h4 text-primary">
              Company Details
            </h3>
            <VRow>
              <VCol
                cols="12"
                md="4"
              >
                <AppSelect
                  v-model="formData.branch_id"
                  label="Branch*"
                  :items="branches"
                  placeholder="Select branch"
                />
              </VCol>
              <VCol
                cols="12"
                md="4"
              >
                <AppDateTimePicker
                  v-model="formData.date_of_joining"
                  label="Date of Joining*"
                  placeholder="Select date"
                  :rules="[requiredRule, dateRule, dojRule]"
                  :error-messages="errors.date_of_joining"
                  :config="{ maxDate: new Date() }"
                />
              </VCol>
              <VCol
                cols="12"
                md="4"
              >
                <AppTextField
                  v-model="formData.bonus"
                  label="Bonus"
                  placeholder="Enter amount"
                  type="number"
                  min="0"
                  :rules="[numberRule]"
                  :error-messages="errors.bonus"
                />
              </VCol>
              <VCol
                cols="12"
                md="4"
              >
                <AppSelect
                  v-model="formData.employment_type_id"
                  label="Employment Type*"
                  placeholder="Select"
                  :items="employmentTypes"
                  :rules="[requiredRule]"
                  :error-messages="errors.employment_type_id"
                />
              </VCol>
              <VCol
                cols="12"
                md="4"
              >
                <AppSelect
                  v-model="formData.employment_status_id"
                  label="Employment Status*"
                  :items="employmentStatuses"
                  placeholder="Select status"
                />
              </VCol>
              <VCol
                cols="12"
                md="4"
              >
                <AppSelect
                  v-model="formData.status"
                  label="User Status*"
                  :items="[{ value: true, title: 'Active' }, { value: false, title: 'Inactive' }]"
                  placeholder="Select"
                />
              </VCol>
              <VCol
                cols="12"
                md="4"
              >
                <AppSelect
                  v-model="formData.termination_type_id"
                  label="End of Employment Type"
                  :items="terminationTypes"
                  placeholder="Select end of employement type"
                  disabled
                  clearable
                />
              </VCol>
              <VCol
                cols="12"
                md="4"
              >
                <AppDateTimePicker
                  v-model="formData.termination_date"
                  label="End of Employment Date*"
                  placeholder="Select date"
                  :disabled="!formData.termination_type_id"
                  :rules="formData.termination_type_id ? [requiredRule] : []"
                />
              </VCol>
              <VCol
                cols="12"
                md="4"
              >
                <AppDateTimePicker
                  v-model="formData.termination_effective_date"
                  label="Effective Date (Last working day)"
                  placeholder="Select date"
                  :disabled="!formData.termination_type_id"
                  :rules="formData.termination_type_id ? [requiredRule] : []"
                />
              </VCol>
              <VCol cols="12">
                <AppTextField
                  v-model="formData.termination_reason"
                  :label="`End of Employement Reason${formData.termination_type_id ? ' *' : ''}`"
                  placeholder="Enter end of employement reason"
                  :disabled="!formData.termination_type_id"
                  :rules="formData.termination_type_id ? [requiredRule] : []"
                />
              </VCol>
            </VRow>
          </div>

          <!-- 👉 Documents -->
          <div
            id="documents-details"
            class="form-section"
          >
            <h3 class="text-h4 text-primary">
              Documents
            </h3>
            <VRow>
              <VCol
                cols="12"
                md="4"
              >
                <VFileInput
                  v-model="formData.offer_letter"
                  label="Offer Letter"
                  placeholder="Upload offer letter (PDF - Max 10MB)"
                  :rules="[pdfFileRule]"
                  prepend-icon="tabler-file-text"
                  clearable
                  density="comfortable"
                  variant="outlined"
                  :error-messages="errors.offer_letter"
                />
              </VCol>
              <VCol
                cols="12"
                md="4"
              >
                <VFileInput
                  v-model="formData.cnic_document"
                  :label="'CNIC Front'"
                  :placeholder="'Upload CNIC front (PNG, JPEG, JPG - Max 10MB)'"
                  :rules="[imageFileRule]"
                  prepend-icon="tabler-photo"
                  clearable
                  density="comfortable"
                  variant="outlined"
                  :error-messages="errors.cnic_document"
                />
              </VCol>
              <VCol
                cols="12"
                md="4"
              >
                <VFileInput
                  v-model="formData.resume"
                  :label="'Resume'"
                  :placeholder="'Upload resume (PDF - Max 10MB)'"
                  :rules="[pdfFileRule]"
                  prepend-icon="tabler-file-text"
                  clearable
                  density="comfortable"
                  variant="outlined"
                  :error-messages="errors.resume"
                />
              </VCol>
              <VCol
                cols="12"
                md="4"
              >
                <VFileInput
                  v-model="formData.photo"
                  :label="'Employee Photo'"
                  :placeholder="'Upload passport photo (PNG, JPEG, JPG - Max 10MB)'"
                  :rules="[imageFileRule]"
                  prepend-icon="tabler-photo"
                  clearable
                  density="comfortable"
                  variant="outlined"
                  :error-messages="errors.photo"
                />
              </VCol>
              <VCol
                cols="12"
                md="4"
              >
                <VFileInput
                  v-model="formData.experience_letter"
                  :label="'Experience Letter'"
                  :placeholder="'Upload experience letter (PDF - Max 10MB)'"
                  :rules="[pdfFileRule]"
                  prepend-icon="tabler-file-text"
                  clearable
                  density="comfortable"
                  variant="outlined"
                  :error-messages="errors.experience_letter"
                />
              </VCol>
              <VCol
                cols="12"
                md="4"
              >
                <VFileInput
                  v-model="formData.contract"
                  label="Contract"
                  placeholder="Upload contract (PDF - Max 10MB)"
                  :rules="[pdfFileRule]"
                  prepend-icon="tabler-file-text"
                  clearable
                  density="comfortable"
                  variant="outlined"
                  :error-messages="errors.contract"
                />
              </VCol>
              <!-- Default Dynamic Documents (pre-populated from document types) -->
              <VCol
                v-for="(document, index) in formData.dynamic_documents?.filter(d => d.is_default)"
                :key="index"
                cols="12"
                md="4"
              >
                <VFileInput
                  v-model="document.file"
                  :label="document.document_type_name || document.title || 'Upload Document'"
                  placeholder="Upload document (PDF, JPG, PNG - Max 10MB)"
                  :rules="[dynamicDocumentFileRule]"
                  prepend-icon="tabler-file-text"
                  clearable
                  density="comfortable"
                  variant="outlined"
                />
              </VCol>

              <!-- Dynamic Documents Section -->
              <VCol cols="12">
                <VDivider class="my-4" />
                <h4 class="text-h6 mb-3">
                  Additional Documents
                </h4>

                <!-- Existing Dynamic Documents -->
                <div
                  v-for="(document, index) in formData.dynamic_documents?.filter(d => !d.is_default)"
                  :key="index"
                  class="dynamic-document-item mb-4"
                >
                  <VCard
                    variant="outlined"
                    class="pa-4"
                  >
                    <VRow class="align-end">
                      <VCol
                        cols="12"
                        md="4"
                      >
                        <AppSelect
                          v-model="document.document_type_id"
                          placeholder="Select document type"
                          :items="documentTypes?.filter(d => !d.is_default)"
                          :rules="[requiredRule]"
                          :error-messages="errors[`dynamic_documents.${index}.document_type_id`]"
                        />
                      </VCol>
                      <VCol
                        cols="12"
                        md="6"
                      >
                        <VFileInput
                          v-model="document.file"
                          :label="`Upload Document`"
                          placeholder="Upload document (PDF, JPG, PNG - Max 10MB)"
                          :rules="[requiredRule, dynamicDocumentFileRule]"
                          prepend-icon="tabler-file"
                          clearable
                          density="comfortable"
                          variant="outlined"
                          :error-messages="errors[`dynamic_documents.${index}.file`]"
                        />
                      </VCol>
                      <VCol
                        cols="12"
                        md="1"
                        class="d-flex align-center justify-center"
                      >
                        <VBtn
                          icon
                          size="small"
                          color="error"
                          variant="text"
                          @click="removeDynamicDocument(index)"
                          :disabled="isSubmitting"
                          class="mt-2"
                        >
                          <VIcon>tabler-trash</VIcon>
                        </VBtn>
                      </VCol>
                    </VRow>
                  </VCard>
                </div>

                <!-- Add More Button -->
                <div class="mt-2">
                  <button
                    type="button"
                    @click="addDynamicDocument"
                    :disabled="isSubmitting"
                    class="add-more-btn"
                  >
                    + Add more
                  </button>
                </div>
              </VCol>
            </VRow>
          </div>

          <!-- 👉 Bank Details -->
          <div
            id="bank-details"
            class="form-section"
          >
            <h3 class="text-h4 text-primary">
              Bank Account Details
            </h3>
            <VRow>
              <VCol
                cols="12"
                md="4"
              >
                <AppTextField
                  v-model="formData.bank_name"
                  label="Bank Name*"
                  placeholder="Enter bank"
                  :rules="[requiredRule]"
                  :error-messages="errors.bank_name"
                />
              </VCol>
              <VCol
                cols="12"
                md="4"
              >
                <AppTextField
                  v-model="formData.branch_location"
                  label="Branch Location*"
                  placeholder="Enter location"
                  :rules="[requiredRule]"
                  :error-messages="errors.branch_location"
                />
              </VCol>
              <VCol
                cols="12"
                md="4"
              >
                <AppTextField
                  v-model="formData.account_holder_name"
                  label="Account Holder Name*"
                  placeholder="Enter account holder name"
                  :rules="[requiredRule]"
                  :error-messages="errors.account_holder_name"
                />
              </VCol>
              <VCol
                cols="12"
                md="4"
              >
                <AppTextField
                  v-model="formData.account_number"
                  label="Account Number*"
                  placeholder="Enter account number"
                  :rules="[requiredRule]"
                  :error-messages="errors.account_number"
                />
              </VCol>
              <!-- IBAN -->
              <VCol
                cols="12"
                md="4"
              >
                <AppTextField
                  v-model="formData.iban"
                  label="IBAN"
                  placeholder="Enter IBAN"
                />
              </VCol>
            </VRow>
          </div>

          <!-- 👉 Device Details -->
          <div
            id="device-details"
            class="form-section"
          >
            <h3 class="text-h4 text-primary">
              Assigned Devices
            </h3>
            <!-- Assigned Devices List -->
            <VRow v-if="assignedDevices.length > 0">
              <VCol
                v-for="(device, index) in assignedDevices"
                :key="index"
                cols="12"
                md="12"
              >
                <VCard
                  variant="outlined"
                  class="pa-4"
                >
                  <div
                    class="d-flex"
                    style="justify-content: end;"
                  >
                    <VBtn
                      icon="tabler-x"
                      size="small"
                      variant="text"
                      color="error"
                      @click="removeAssignedDevice(index)"
                    />
                  </div>

                  <VRow>
                    <VCol
                      cols="12"
                      md="6"
                    >
                      <AppSelect
                        v-model="device.asset_id"
                        label="Select Device*"
                        :items="filteredAssetsPerDevice[index]"
                        placeholder="Choose a device"
                        :rules="[requiredRule]"
                      />
                    </VCol>
                    <VCol
                      cols="12"
                      md="6"
                    >
                      <AppDateTimePicker
                        v-model="device.assigned_date"
                        label="Assigned Date*"
                        placeholder="Select date"
                        :rules="[requiredRule]"
                        :config="{ maxDate: new Date() }"
                      />
                    </VCol>
                  </VRow>
                </VCard>
              </VCol>
            </VRow>
            <!-- Add Device Button -->
            <div class="mt-4">
              <VBtn
                prepend-icon="tabler-plus"
                variant="outlined"
                @click="addAssignedDevice"
                :disabled="isSubmitting || !canAddDevice"
              >
                Add Assigned Device
              </VBtn>
            </div>
          </div>

          <!-- 👉 Attendance -->
          <div
            id="attendance-details"
            class="form-section"
          >
            <h3 class="text-h4 text-primary">
              Attendance Settings
            </h3>
            <VTable class="mb-4">
              <thead>
                <tr>
                  <th>Day</th>
                  <th class="text-center">
                    Working Day
                  </th>
                  <th class="text-center">
                    Inside Office
                  </th>
                  <th class="text-center">
                    Outside Office
                  </th>
                  <th class="text-center">
                    Checkin Time
                  </th>
                  <th class="text-center">
                    Checkout Time
                  </th>
                  <th class="text-center">
                    late Checkin
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(setting, idx) in formData.attendance_days"
                  :key="setting.day"
                >
                  <td class="font-weight-medium">
                    {{ setting.day }}
                  </td>
                  <td
                    class="text-center"
                    style="justify-items: center;"
                  >
                    <VCheckbox
                      v-model="setting.is_working_day"
                      :label="''"
                      hide-details
                      density="compact"
                      color="primary"
                      @change="handleOfficeTypeChange(setting)"
                    />
                  </td>
                  <td
                    class="text-center"
                    style="justify-items: center;"
                  >
                    <VCheckbox
                      v-model="setting.inside_office"
                      @change="handleOfficeTypeChange(setting)"
                      :label="''"
                      hide-details
                      :disabled="!setting.is_working_day"
                      density="compact"
                    />
                  </td>
                  <td
                    class="text-center"
                    style="justify-items: center;"
                  >
                    <VCheckbox
                      v-model="setting.outside_office"
                      @change="handleOfficeTypeChange(setting)"
                      :label="''"
                      hide-details
                      :disabled="!setting.is_working_day"
                      density="compact"
                    />
                  </td>
                  <td
                    class="text-center"
                    style="justify-items: center; min-width: 110%"
                  >
                    <VTextField
                      v-model="setting.checkin_time"
                      type="time"
                      step="60"
                      inputmode="numeric"
                      label=""
                      variant="outlined"
                      hide-details
                      density="compact"
                      :disabled="!setting.is_working_day || (!setting.inside_office && !setting.outside_office)"
                    />
                  </td>
                  <td
                    class="text-center"
                    style="justify-items: center;"
                  >
                    <VTextField
                      v-model="setting.checkout_time"
                      type="time"
                      step="60"
                      inputmode="numeric"
                      label=""
                      variant="outlined"
                      hide-details
                      density="compact"
                      :disabled="!setting.is_working_day || (!setting.inside_office && !setting.outside_office)"
                    />
                  </td>
                  <td
                    class="text-center"
                    style="justify-items: center;"
                  >
                    <VCheckbox
                      v-model="setting.allow_late_checkin"
                      :label="''"
                      :disabled="!setting.is_working_day || (!setting.inside_office && !setting.outside_office)"
                      hide-details
                      density="compact"
                    />
                  </td>
                </tr>
              </tbody>
            </VTable>
          </div>

          <!-- 👉 Salary & Allowances -->
          <div
            id="salary-allowances"
            class="form-section"
          >
            <h3 class="text-h4 text-primary">
              Salary & Allowances
            </h3>
            <EmployeeSalaryAllowancesTab :employee-id="null" />
          </div>

          <!-- Form Actions -->
          <VCardActions class="justify-end px-6 pb-6">
            <VBtn
              color="primary"
              type="submit"
              :loading="isSubmitting"
            >
              Submit
            </VBtn>
            <VBtn
              color="secondary"
              variant="tonal"
              @click="resetForm"
              :disabled="isSubmitting"
            >
              Cancel
            </VBtn>
          </VCardActions>
        </VCardText>
      </VForm>
    </VCard>
    <div
      v-else
      class="d-flex justify-center align-center"
      style="block-size: 400px;"
    >
      <VProgressCircular
        indeterminate
        size="64"
      />
    </div>
    <!-- Notification Snackbar -->
    <VSnackbar
      v-model="snackbar.visible"
      :timeout="3000"
      :color="snackbar.color"
      location="top right"
    >
      {{ snackbar.message }}
    </VSnackbar>
  </div>
</template>

<script setup>
import DocumentImageViewer from "@/components/common/DocumentImageViewer.vue"
import EmployeeSalaryAllowancesTab from "@/components/hrm/employee/EmployeeSalaryAllowancesTab.vue"
import {
  cnicRule,
  dateRule,
  emailRule,
  employeeCodeRule,
  imageFileRule,
  nameRule,
  numberRule,
  passwordRule,
  pdfFileRule,
  phoneRule,
  requiredRule,
} from "@/utils/form/validation"
import { computed, onMounted, onUnmounted, ref } from "vue"
import { useRoute, useRouter } from "vue-router"

const form = ref(null)
const isSubmitting = ref(false)
const accessToken = useCookie("accessToken")
const isLoading = ref(false)
const errors = ref({})
const employeeId = ref(null)
const formLoaded = ref(false)

// Notification system
const snackbar = ref({
  visible: false,
  message: "",
  color: "success",
})

const currentStep = ref(0)

const steps = [
  { title: "Personal Details", id: "personal-details" },
  { title: "Company Details", id: "company-details" },
  { title: "Documents", id: "documents-details" },
  { title: "Bank Details", id: "bank-details" },
  { title: "Device Details", id: "device-details" },
  { title: "Attendance", id: "attendance-details" },
  { title: "Salary & Allowances", id: "salary-allowances" },
]

const scrollToSection = sectionId => {
  const element = document.getElementById(sectionId)
  if (element) {
    const headerOffset = 120 // Adjust this value based on your sticky header's height
    const elementPosition = element.getBoundingClientRect().top
    const offsetPosition = elementPosition + window.pageYOffset - headerOffset

    window.scrollTo({
      top: offsetPosition,
      behavior: "smooth",
    })
  }
}

const handleScroll = () => {
  const scrollPosition = window.scrollY + 150 // Offset to trigger active state earlier
  for (let i = steps.length - 1; i >= 0; i--) {
    const section = document.getElementById(steps[i].id)
    if (section && section.offsetTop <= scrollPosition) {
      currentStep.value = i
      break
    }
  }
}

// Dynamic document file validation rule
const dynamicDocumentFileRule = value => {
  if (!value) return true // Optional if not required

  const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png']
  const maxSize = 10 * 1024 * 1024 // 10MB

  if (!allowedTypes.includes(value.type)) {
    return 'Only PDF, JPG, JPEG, and PNG files are allowed'
  }

  if (value.size > maxSize) {
    return 'File size must not exceed 10MB'
  }

  return true
}

// Form data model
const formData = ref({
  employee_code: "",
  // Personal Information
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

  // Company Detail
  designation_id: "",
  department_id: "",
  date_of_joining: "",
  bonus: "",
  employment_type_id: "",
  reporting_to: "",

  // Document
  photo: null,
  cnic_document: null,
  resume: null,

  // Device Detail
  machine_name: "",
  system_processor: "",
  system_type: "",
  headphone: "",
  machine_password: "",
  installed_ram: "",
  mouse: "",
  laptop_charger: "",

  // Account Detail
  account_holder_name: "",
  bank_name: "",
  account_number: "",
  iban: "",
  branch_location: "",

  // Additional Information
  emergency_contact_relation: "",
  employment_status_id: "",
  status: true,
  branch_id: "",
  experience_letter: null,
  offer_letter: null,
  contract: null,

  // Job Details
  job_category_id: "",
  job_stage_id: "",
  termination_type_id: "",
  termination_reason:"",
  termination_date: "",
  termination_effective_date: "",

  // Dynamic Documents
  dynamic_documents: [],

  // Assigned Devices
  assigned_devices: [],

  // Salary & Allowances
  salary_allowances: [],

  attendance_days: [
    { day: 'Monday', is_working_day: false, inside_office: false, outside_office: false, allow_late_checkin: false },
    { day: 'Tuesday', is_working_day: false, inside_office: false, outside_office: false, allow_late_checkin: false },
    { day: 'Wednesday', is_working_day: false, inside_office: false, outside_office: false, allow_late_checkin: false },
    { day: 'Thursday', is_working_day: false, inside_office: false, outside_office: false, allow_late_checkin: false },
    { day: 'Friday', is_working_day: false, inside_office: false, outside_office: false, allow_late_checkin: false },
    { day: 'Saturday', is_working_day: false, inside_office: false, outside_office: false, allow_late_checkin: false },
    { day: 'Sunday', is_working_day: false, inside_office: false, outside_office: false, allow_late_checkin: false },
  ],
})

// Dropdown options - will be populated from API
const genders = ref([
  { value: "male", title: "Male" },
  { value: "female", title: "Female" },
  { value: "other", title: "Other" },
])

const maritalStatuses = ref([
  { value: "single", title: "Single" },
  { value: "married", title: "Married" },
  { value: "divorced", title: "Divorced" },
  { value: "widowed", title: "Widowed" },
])

const employmentTypes = ref([])
const employmentStatuses = ref([])
const bloodGroups = ref(["A+", "A-", "B+", "B-", "AB+", "AB-", "O+", "O-"])
const designations = ref([])
const departments = ref([])
const reportingTos = ref([])
const branches = ref([])
const jobCategories = ref([])
const jobStages = ref([])
const terminationTypes = ref([])
const documentTypes = ref([])

// Assigned Devices
const assignedDevices = ref([])
const availableAssets = ref([])

const filteredAssetsPerDevice = computed(() => {
  const selectedAssetIds = assignedDevices.value.map(d => d.asset_id)

  return assignedDevices.value.map((device, index) => {
    // IDs selected in other dropdowns
    const otherSelectedIds = selectedAssetIds.filter((id, i) => i !== index && id)

    // Return assets that are not selected in other dropdowns
    return availableAssets.value.filter(asset => !otherSelectedIds.includes(asset.value))
  })
})

const canAddDevice = computed(() => {
  const selectedAssetIds = new Set(assignedDevices.value.map(d => d.asset_id).filter(Boolean))
  return availableAssets.value.length > selectedAssetIds.size
})

const getObjectUrl = file => {
  if (file instanceof File) {
    return URL.createObjectURL(file)
  }

  return null
}

const fetchAvailableAssets = async () => {
  try {
    const { data } = await $api("/assets/unassigned/list", { method: "GET" })
    availableAssets.value = data.map(asset => ({
      value: asset.id,
      title: `${asset.name} (${asset.serial_no})`,
    }))
  } catch (error) {
    $toast.error("Failed to load available assets")
  }
}

const addAssignedDevice = () => {
  assignedDevices.value.push({
    asset_id: "",
    assigned_date: "",
  })
}

const removeAssignedDevice = index => {
  assignedDevices.value.splice(index, 1)
}

const fetchEmploymentTypes = async () => {
  try {
    const { data } = await $api("/employment-types", {
      method: "GET",
      headers: {
        Authorization: `Bearer ${accessToken.value}`,
      },
    })
    employmentTypes.value = data.map(item => ({
      value: item.id,
      title: item.name,
    }))
  } catch (error) {
    $toast.error("Failed to load employment types")
  }
}

const fetchEmploymentStatuses = async () => {
  try {
    const { data } = await $api("/employment-statuses", {
      method: "GET",
      headers: {
        Authorization: `Bearer ${accessToken.value}`,
      },
    })
    employmentStatuses.value = data.map(item => ({
      value: item.id,
      title: item.name,
    }))
  } catch (error) {
    $toast.error("Failed to load employment statuses")
  }
}

// Fetch designations and departments from API
const fetchDesignations = async () => {
  try {
    const { data } = await $api("/designations", {
      method: "GET",
      headers: {
        Authorization: `Bearer ${accessToken.value}`,
      },
    })

    designations.value = data.map(item => ({
      value: item.id,
      title: item.title,
    }))
  } catch (error) {
    $toast.error("Failed to load designations")
  }
}

const fetchManagers = async () => {
  try {
    const { data } = await $api("/employees?only_managers=true", {
      method: "GET",
      headers: {
        Authorization: `Bearer ${accessToken.value}`,
      },
    })

    reportingTos.value = data.map(item => ({
      value: item.id,
      title: item.name,
    }))
  } catch (error) {
    $toast.error("Failed to load managers")
  }
}

const fetchDepartments = async () => {
  try {
    const { data } = await $api("/departments?context=filters", {
      method: "GET",
      headers: {
        Authorization: `Bearer ${accessToken.value}`,
      },
    })

    departments.value = data.map(item => ({
      value: item.id,
      title: item.name,
    }))
  } catch (error) {
    $toast.error("Failed to load departments")
  }
}

const fetchBranches = async () => {
  try {
    const { data } = await $api("/branches", {
      method: "GET",
      headers: {
        Authorization: `Bearer ${accessToken.value}`,
      },
    })

    branches.value = data.map(item => ({
      value: item.id,
      title: item.name,
    }))
  } catch (error) {
    $toast.error("Failed to load branches")
  }
}

const fetchJobCategories = async () => {
  try {
    const { data } = await $api("/job-categories", {
      method: "GET",
      headers: {
        Authorization: `Bearer ${accessToken.value}`,
      },
    })

    jobCategories.value = data.map(item => ({
      value: item.id,
      title: item.name,
    }))
  } catch (error) {
    $toast.error("Failed to load job categories")
  }
}

const fetchJobStages = async () => {
  try {
    const { data } = await $api("/job-stages", {
      method: "GET",
      headers: {
        Authorization: `Bearer ${accessToken.value}`,
      },
    })

    jobStages.value = data.map(item => ({
      value: item.id,
      title: item.name,
    }))
  } catch (error) {
    $toast.error("Failed to load job stages")
  }
}

const fetchTerminationTypes = async () => {
  try {
    const { data } = await $api("/termination-types", {
      method: "GET",
      headers: {
        Authorization: `Bearer ${accessToken.value}`,
      },
    })

    terminationTypes.value = data.map(item => ({
      value: item.id,
      title: item.name,
    }))
  } catch (error) {
    $toast.error("Failed to load termination types")
  }
}

// Fetch document types from API
const fetchDocumentTypes = async () => {
  try {
    const { data } = await $api("/document-types", {
      method: "GET",
      headers: {
        Authorization: `Bearer ${accessToken.value}`,
      },
    })

    documentTypes.value = data.map(item => ({
      value: item.id,
      title: item.name,
      is_default: item.is_default || false,
      order: item.order || 0,
    }))

    // Pre-populate default dynamic documents so v-model binds to formData
    const defaults = documentTypes.value.filter(dt => dt.is_default)
    const existingTypeIds = new Set(formData.value.dynamic_documents.map(d => d.document_type_id))

    defaults.forEach(dt => {
      if (!existingTypeIds.has(dt.value)) {
        formData.value.dynamic_documents.push({
          document_type_id: dt.value,
          document_type_name: dt.title,
          title: dt.title,
          is_default: true,
          order: dt.order || 0,
          file: null,
        })
      }
    })
  } catch (error) {
    $toast.error("Failed to load document types")
  }
}

// Dynamic document management functions
const addDynamicDocument = () => {
  formData.value.dynamic_documents.push({
    document_type_id: "",
    file: null,
  })
}

const removeDynamicDocument = index => {
  formData.value.dynamic_documents.splice(index, 1)
}

const handleOfficeTypeChange = setting => {
  const allUnhecked = !setting.is_working_day || (!setting.inside_office && !setting.outside_office)

  if (allUnhecked) {
    setting.checkin_time = ""
    setting.checkout_time = ""
  } else {
    if (!setting.checkin_time) setting.checkin_time = "09:00"
    if (!setting.checkout_time) setting.checkout_time = "18:00"
  }
}

// Initialize component
onMounted(async () => {
  employeeId.value = useRoute().params.id || null

  isLoading.value = true
  try {
    await Promise.all([
      fetchDesignations(),
      fetchDepartments(),
      fetchManagers(),
      fetchBranches(),
      fetchJobCategories(),
      fetchJobStages(),
      fetchTerminationTypes(),
      fetchEmploymentTypes(),
      fetchEmploymentStatuses(),
      fetchDocumentTypes(),
      fetchAvailableAssets(),
    ])
  } finally {
    isLoading.value = false
    formLoaded.value = true
  }
  window.addEventListener("scroll", handleScroll)
})

onUnmounted(() => {
  window.removeEventListener("scroll", handleScroll)
})

// Custom validation function for attendance days
const validateAttendanceDays = () => {
  // Only check if at least one attendance option is selected (regardless of working days)
  const hasValidDay = formData.value.attendance_days.some(day =>
    day.inside_office || day.outside_office,
  )

  if (!hasValidDay) {
    $toast.error("Please select at least one attendance option (Inside Office or Outside Office)")

    return false
  }

  return true
}


const maxDob = computed(() => {
  const referenceDate = formData.value.date_of_joining ? new Date(formData.value.date_of_joining) : new Date()
  referenceDate.setFullYear(referenceDate.getFullYear() - 18)
  return referenceDate.toISOString().split('T')[0]
})

// Date of Birth validation rule
const dobRule = value => {
  if (!value) {
    return true
  }
  const dob = new Date(value)
  const referenceDate = formData.value.date_of_joining ? new Date(formData.value.date_of_joining) : new Date()
  let age = referenceDate.getFullYear() - dob.getFullYear()
  const m = referenceDate.getMonth() - dob.getMonth()
  if (m < 0 || (m === 0 && referenceDate.getDate() < dob.getDate())) {
    age--
  }
  if (age < 18) {
    return 'Employee must be at least 18 years old at the time of joining'
  }
  return true
}
// Date of Joining validation rule
const dojRule = value => {
  if (!value) {
    return true
  }
  const referenceDate = formData.value.date_of_joining ? new Date(formData.value.date_of_joining) : new Date()
  const dob = formData.value.dob ? new Date(formData.value.dob) : maxDob.value
  let age = referenceDate.getFullYear() - dob.getFullYear()
  const m = referenceDate.getMonth() - dob.getMonth()
  if (m < 0 || (m === 0 && referenceDate.getDate() < dob.getDate())) {
    age--
  }
  if (age < 18) {
    return 'The employee must be 18 years or older on the selected joining date.'
  }
  return true
}

// Form submission
const submitForm = async () => {
  try {
    isSubmitting.value = true
    errors.value = {}

    const { valid } = await form.value.validate()
    if (!valid) {
      $toast.error("Please fill all required fields correctly")
      return
    }
    // Validate attendance days
    if (!validateAttendanceDays()) {
      isSubmitting.value = false
      return
    }

    const formDataToSend = new FormData()
    // Append all non-file fields (exclude dynamic_documents)
    Object.entries(formData.value).forEach(([key, value]) => {
      if (key !== 'dynamic_documents') {
        formDataToSend.append(key, value !== null ? value : "")
      }
    });

    formDataToSend.append('attendance_days', JSON.stringify(formData.value.attendance_days))
    // Add assigned devices to the main request
    if (assignedDevices.value.length > 0) {
      formDataToSend.append('assigned_devices', JSON.stringify(assignedDevices.value))
    }

    // Add dynamic documents (both default and additional)
    const dynamicDocuments = formData.value.dynamic_documents.filter(doc => doc.file && doc.document_type_id)

    dynamicDocuments.forEach((doc, index) => {
      formDataToSend.append(`dynamic_documents[${index}][file]`, doc.file)
      formDataToSend.append(`dynamic_documents[${index}][document_type_id]`, doc.document_type_id)
      formDataToSend.append(`dynamic_documents[${index}][document_type_name]`, doc.document_type_name || doc.title || '')
      formDataToSend.append(`dynamic_documents[${index}][is_default]`, doc.is_default ? '1' : '0')
      formDataToSend.append(`dynamic_documents[${index}][order]`, String(doc.order || 0))
    })

    const method = "POST"
    const url = "/employees"

    await $api(url, {
      method,
      body: formDataToSend,
      headers: {
        Authorization: `Bearer ${accessToken.value}`,
      },
    })

    $toast.success("Employee created successfully")
    useRouter().push({ name: "hrm-employee-list" })
  } catch (error) {
    if (error.response && error.response.status === 201) {
      $toast.success("Employee created successfully")
      useRouter().push({ name: "hrm-employee-list" })
      return
    }
    if (error?.status === 422) {
      let message = `Failed to add employee`
      const errs = error._data?.errors || []
      errors.value = errs
      message = Array.isArray(errs) ? errs.join('<br>') : String(errs)
      $toast.error(message)
    }
  } finally {
    isSubmitting.value = false
  }
}

// Reset form
const resetForm = () => {
  form.value.reset()
  Object.keys(formData.value).forEach(key => {
    if (key === 'dynamic_documents') {
      formData.value[key] = []
    } else if (Array.isArray(formData.value[key])) {
      formData.value[key] = []
    } else if (["photo", "cnic_document", "resume", "experience_letter", "offer_letter", "contract"].includes(key)) {
      formData.value[key] = null
    } else {
      formData.value[key] = ""
    }
  })
}
</script>

<style lang="scss" scoped>
.personal-info-card {
  border-radius: 12px;
}

.summary-item {
  display: flex;
  flex-direction: column;
}

.summary-label {
  font-size: 0.75rem;
  color: rgba(var(--v-theme-on-surface), 0.6);
}

.summary-value {
  font-size: 0.9rem;
  font-weight: 500;
}

.form-section {
  padding-top: 1.2rem;
}

.dynamic-document-item {
  position: relative;
}

.dynamic-document-item .v-card {
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 8px;
}

.dynamic-document-item .v-card:hover {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 10%);
}

.add-more-btn {
  border: none;
  background: none;
  color: #ff6b35; /* Orange/salmon color */
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  padding-block: 8px;
  padding-inline: 16px;
  text-decoration: underline;
  text-decoration-color: #ff6b35;
  transition: all 0.2s ease;
}

.add-more-btn:hover {
  color: #e55a2b; /* Darker orange on hover */
  text-decoration-color: #e55a2b;
}

.add-more-btn:disabled {
  color: #ccc;
  cursor: not-allowed;
  text-decoration-color: #ccc;
}

.sticky-tabs {
  position: sticky;
  background-color: rgb(var(--v-theme-surface));
  z-index: 10;
  border-bottom: 1px solid rgba(var(--v-border-color), 0.12);
}
</style>
