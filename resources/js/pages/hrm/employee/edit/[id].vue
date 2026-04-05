<template>
  <section>
    <VBreadcrumbs class="px-0 pb-2 pt-0" :items="[{ title: 'Employee' }, { title: 'Edit' }]" />

    <VCard v-if="formLoaded">
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
                :src="formData.current_photo || '/images/avatars/dummy.png'"
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
              <VCol cols="12">
                <VRow>
                  <VCol cols="12" md="3">
                    <AppTextField
                      v-model="formData.name"
                      label="Name"
                      placeholder="Enter full name"
                      :rules="[requiredRule]"
                    />
                  </VCol>
                  <VCol
                    cols="6"
                    md="3"
                  >
                    <AppTextField
                      v-model="formData.employee_code"
                      label="Employee Code"
                      readonly
                    />
                  </VCol>
                  <VCol cols="12" md="3">
                    <AppTextField
                      v-model="formData.official_email"
                      label="Official Email"
                      placeholder="email@company.com"
                      :rules="[requiredRule, emailRule]"
                    />
                  </VCol>
                  <VCol cols="12" md="3">
                    <AppTextField
                      v-model="formData.phone"
                      label="Phone"
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
                      label="Department"
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
                      label="Designation"
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
                      label="Employee Status"
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
        :class="{'is-stuck': isStuck}"
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
        <VForm
          ref="form"
          @submit.prevent="submitForm"
        >
          <!-- 👉 Personal Details -->
          <div
            id="personal-details"
            ref="personalDetailsRef"
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
                  :rules="[requiredRule]"
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
                  :rules="[requiredRule]"
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
                />
              </VCol>
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
                  label="CNIC*"
                  placeholder="Enter CNIC number"
                  :rules="[requiredRule, cnicRule]"
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
                />
              </VCol>
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
            ref="companyDetailsRef"
            class="form-section"
          >
            <h3 class="text-h4 text-primary">
              Company Details
            </h3>
            <VRow>
              <!-- Employee ID & Designation -->
              <VCol
                cols="12"
                md="4"
              >
                <AppSelect
                  v-model="formData.branch_id"
                  label="Branch*"
                  :items="branches"
                  placeholder="Select branch"
                  :rules="[requiredRule]"
                />
              </VCol>
              <!-- Date of Joining & Bonus -->
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
                />
              </VCol>
              <!-- Employment Type & Status -->
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

              <!-- Status (Active/Inactive) & Termination Type -->
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
                  placeholder="Select end of employment type"
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
            ref="documentsRef"
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
                  placeholder="Upload offer letter"
                  prepend-icon="tabler-file-text"
                  clearable
                  density="comfortable"
                  variant="outlined"
                />
                <VSpacer class="mt-2" />
                <small
                  v-if="formData.offer_letter"
                  class="text-primary"
                >
                  Current:
                  <DocumentImageViewer
                    :src="formData.current_offer_letter"
                    :pdf-title="'Offer Letter'"
                  />
                </small>
              </VCol>
              <VCol
                cols="12"
                md="4"
              >
                <VFileInput
                  v-model="formData.cnic_document"
                  :label="'CNIC Front (Leave empty to keep current)'"
                  :placeholder="'Keep current CNIC'"
                  prepend-icon="tabler-photo"
                  clearable
                  density="comfortable"
                  variant="outlined"
                />
                <VSpacer class="mt-2" />
                <small
                  v-if="formData.cnic_document"
                  class="text-primary"
                >
                  Current:
                  <DocumentImageViewer
                    :src="formData.current_cnic_document"
                    :pdf-title="'Employee CNIC'"
                  />
                </small>
              </VCol>
              <VCol
                cols="12"
                md="4"
              >
                <VFileInput
                  v-model="formData.resume"
                  :label="'Resume (Leave empty to keep current)'"
                  :placeholder="'Keep current resume'"
                  prepend-icon="tabler-file-text"
                  clearable
                  density="comfortable"
                  variant="outlined"
                />
                <VSpacer class="mt-2" />
                <small
                  v-if="formData.resume"
                  class="text-primary"
                >
                  Current:
                  <DocumentImageViewer
                    :src="formData.current_resume"
                    :pdf-title="'Resume'"
                  />
                </small>
              </VCol>
              <VCol
                cols="12"
                md="4"
              >
                <VFileInput
                  v-model="formData.photo"
                  :label="'Employee Photo (Leave empty to keep current)'"
                  :placeholder="'Keep current photo'"
                  prepend-icon="tabler-photo"
                  clearable
                  density="comfortable"
                  variant="outlined"
                />
                <VSpacer class="mt-2" />
                <small
                  v-if="formData.photo"
                  class="text-primary"
                >
                  Current:
                  <DocumentImageViewer
                    :src="formData.current_photo"
                    :pdf-title="'Employee Photo'"
                  />
                </small>
              </VCol>
              <!-- Add these document fields -->
              <VCol
                cols="12"
                md="4"
              >
                <VFileInput
                  v-model="formData.experience_letter"
                  label="Experience Letter"
                  placeholder="Upload experience letter"
                  prepend-icon="tabler-file-text"
                  clearable
                  density="comfortable"
                  variant="outlined"
                />
                <VSpacer class="mt-2" />
                <small
                  v-if="formData.experience_letter"
                  class="text-primary"
                >
                  Current:
                  <DocumentImageViewer
                    :src="formData.current_experience_letter"
                    :pdf-title="'Experience Letter'"
                  />
                </small>
              </VCol>
              <VCol
                cols="12"
                md="4"
              >
                <VFileInput
                  v-model="formData.contract"
                  label="Contract"
                  placeholder="Upload contract"
                  prepend-icon="tabler-file-text"
                  clearable
                  density="comfortable"
                  variant="outlined"
                />
                <VSpacer class="mt-2" />
                <small
                  v-if="formData.contract"
                  class="text-primary"
                >
                  Current:
                  <DocumentImageViewer
                    :src="formData.current_contract"
                    :pdf-title="'Contract'"
                  />
                </small>
              </VCol>
              <VCol
                v-for="(document, index) in formData.dynamic_documents?.filter(d => d.is_default)"
                :key="index"
                cols="12"
                md="4"
              >
                <VFileInput
                  v-model="document.file"
                  :label="document.document_type_name || 'Upload Document'"
                  :placeholder="document.existing_file_url ? 'Leave empty to keep current document' : 'Upload document (PDF, JPG, PNG - Max 10MB)'"
                  prepend-icon="tabler-file-text"
                  clearable
                  density="comfortable"
                  variant="outlined"
                  :rules="[dynamicDocumentFileRule]"
                />
                <VSpacer class="mt-2" />
                <small
                  v-if="document.existing_file_url"
                  class="text-primary"
                >
                  Current:
                  <DocumentImageViewer
                    :src="document.existing_file_url"
                    :pdf-title="document.document_type_name || 'Document'"
                  />
                </small>
              </VCol>

              <!-- Dynamic Documents Section -->
              <VCol cols="12">
                <VDivider class="my-4" />
                <h4 class="text-h6 mb-3">
                  Additional Documents
                </h4>

                <div
                  v-for="(document, index) in formData.dynamic_documents?.filter(d => !d.is_default)"
                  :key="index"
                  class="dynamic-document-item mb-4"
                >
                  <VCard
                    variant="outlined"
                    class="pa-4"
                  >
                    <VRow>
                      <VCol
                        cols="12"
                        md="4"
                      >
                        <AppSelect
                          v-model="document.document_type_id"
                          placeholder="Select document type"
                          :items="availableDocumentTypes(index)"
                          :rules="[requiredRule]"
                          :disabled="document.is_existing"
                        />
                      </VCol>
                      <VCol
                        cols="12"
                        md="6"
                      >
                        <div class="d-flex">
                          <!-- :label="document.is_existing ? `Update Document ${index + 1}` : `Upload Document ${index + 1}*`" -->

                          <VFileInput
                            v-model="document.file"
                            :label="`Upload Document`"
                            :placeholder="document.is_existing ? 'Leave empty to keep current document' : 'Upload document (PDF, JPG, PNG - Max 10MB)'"
                            prepend-icon="tabler-file"
                            clearable
                            density="comfortable"
                            variant="outlined"
                            :rules="[dynamicDocumentFileRule]"
                          />

                          <VBtn
                            icon
                            size="small"
                            color="error"
                            variant="text"
                            @click="removeDynamicDocument(index)"
                            :disabled="isSubmitting"
                          >
                            <VIcon>tabler-trash</VIcon>
                          </VBtn>
                        </div>
                      </VCol>
                      <VCol
                        cols="12"
                        md="2"
                        class="d-flex align-end justify-center"
                      >
                        <!-- Show existing document if available -->
                        <div
                          v-if="document.existing_file_url && !document.file"
                          class="mt-2"
                        >
                          <small class="text-primary">Current:</small>
                          <DocumentImageViewer
                            :src="document.existing_file_url"
                            :pdf-title="document.document_type_name || 'Document'"
                          />
                        </div>
                      </VCol>
                    </VRow>
                  </VCard>
                </div>

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
            ref="bankDetailsRef"
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
                />
              </VCol>
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
            ref="deviceDetailsRef"
            class="form-section"
          >
            <h3 class="text-h4 text-primary">
              Assigned Devices
            </h3>
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
                      />
                    </VCol>
                  </VRow>
                </VCard>
              </VCol>
            </VRow>
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
            ref="attendanceRef"
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
                      @change="handleOfficeTypeChange(setting)"
                      :label="''"
                      hide-details
                      density="compact"
                      color="primary"
                    />
                  </td>
                  <td
                    class="text-center"
                    style="justify-items: center;"
                  >
                    <VCheckbox
                      v-model="setting.inside_office"
                      :label="''"
                      hide-details
                      density="compact"
                      @change="handleOfficeTypeChange(setting)"
                      :disabled="!setting.is_working_day"
                    />
                  </td>
                  <td
                    class="text-center"
                    style="justify-items: center;"
                  >
                    <VCheckbox
                      v-model="setting.outside_office"
                      :label="''"
                      hide-details
                      density="compact"
                      @change="handleOfficeTypeChange(setting)"
                      :disabled="!setting.is_working_day"
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
                      hide-details
                      density="compact"
                      :disabled="!setting.is_working_day || (!setting.inside_office && !setting.outside_office)"
                    />
                  </td>
                </tr>
              </tbody>
            </VTable>
          </div>

          <!-- 👉 Salary & Allowances -->
          <div
            id="salary-allowances"
            ref="salaryAllowancesRef"
            class="form-section"
          >
            <h3 class="text-h4 text-primary">
              Salary & Allowances
            </h3>
            <EmployeeSalaryAllowancesTab :employee-id="route.params.id" />
          </div>

          <!-- Form Actions -->
          <VCardActions class="justify-end mt-4">
            <VBtn
              type="submit"
              color="primary"
              :loading="isSubmitting"
            >
              Update Employee
            </VBtn>
            <VBtn
              color="secondary"
              variant="tonal"
              @click="router.push({ name: 'hrm-employee-list' })"
            >
              Cancel
            </VBtn>
          </VCardActions>
        </VForm>
      </VCardText>
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
  </section>
</template>

<script setup>
import DocumentImageViewer from "@/components/common/DocumentImageViewer.vue"
import EmployeeSalaryAllowancesTab from "@/components/hrm/employee/EmployeeSalaryAllowancesTab.vue"
import {cnicRule, dateRule, emailRule, phoneRule, requiredRule} from "@/utils/form/validation"
import {computed, onMounted, onUnmounted, ref, watch} from "vue"
import { useRoute, useRouter } from "vue-router"

// Custom validation rules
const dynamicDocumentFileRule = value => {
  if (!value) return true // Optional field
  const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png']
  const maxSize = 10 * 1024 * 1024 // 10MB

  if (!allowedTypes.includes(value.type)) {
    return 'File must be PDF, JPG, or PNG'
  }

  if (value.size > maxSize) {
    return 'File size must be less than 10MB'
  }

  return true
}

const route = useRoute()
const router = useRouter()
const accessToken = useCookie("accessToken")
const form = ref(null)
const isSubmitting = ref(false)
const formLoaded = ref(false)
const errors = ref({})

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

const isStuck = ref(false)

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

// Form data model
const formData = ref({
  // Personal Information
  name: "",
  father_name: "",
  phone: "",
  emergency_contact_name: "",
  emergency_phone: "",
  dob: "",
  cnic: "",
  gender: "",
  blood_group: "",
  marital_status: "",
  dependents: "",
  personal_email: "",
  official_email: "",
  // official_email_password: "",
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
  experience_letter: null,
  offer_letter: null,
  contract: null,

  // Document
  current_photo: null,
  current_cnic_document: null,
  current_resume: null,
  current_experience_letter: null,
  current_offer_letter: null,
  current_contract: null,

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
  status: "",
  branch_id: "",
  employee_code: "",

  // Job Details
  job_category_id: "",
  job_stage_id: "",
  termination_type_id: "",
  termination_reason:"",
  termination_date: "",
  termination_effective_date: "",

  // Assigned Devices
  assigned_devices: [],

  attendance_days: [
    { day: 'Monday', is_working_day: false, inside_office: false, outside_office: false, allow_late_checkin: false, checkin_time: '09:00 AM', checkout_time: '06:00 PM' },
    { day: 'Tuesday', is_working_day: false, inside_office: false, outside_office: false, allow_late_checkin: false, checkin_time: '09:00 AM', checkout_time: '06:00 PM' },
    { day: 'Wednesday', is_working_day: false, inside_office: false, outside_office: false, allow_late_checkin: false, checkin_time: '09:00 AM', checkout_time: '06:00 PM' },
    { day: 'Thursday', is_working_day: false, inside_office: false, outside_office: false, allow_late_checkin: false, checkin_time: '09:00 AM', checkout_time: '06:00 PM' },
    { day: 'Friday', is_working_day: false, inside_office: false, outside_office: false, allow_late_checkin: false, checkin_time: '09:00 AM', checkout_time: '06:00 PM' },
    { day: 'Saturday', is_working_day: false, inside_office: false, outside_office: false, allow_late_checkin: false, checkin_time: '09:00 AM', checkout_time: '06:00 PM' },
    { day: 'Sunday', is_working_day: false, inside_office: false, outside_office: false, allow_late_checkin: false, checkin_time: '09:00 AM', checkout_time: '06:00 PM' },
  ],

  // Dynamic Documents
  dynamic_documents: [],
})

// Dropdown data
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
const bloodGroups = ref(["A+", "A-", "B+", "B-", "AB+", "AB-", "O+", "O-"])

const reportingTos = ref([])
const departments = ref([])
const designations = ref([])
const departmentMap = ref({})
const branches = ref([])
const jobCategories = ref([])
const jobStages = ref([])
const terminationTypes = ref([])
const documentTypes = ref([])
// Keep a map of doc types to access title/is_default/order by id
const docTypesMapById = ref({})

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

const fetchAvailableAssets = async () => {
  try {
    const { data } = await $api("/assets/unassigned/list", { method: "GET" })
    availableAssets.value = data.map(item => ({
      value: item.id,
      title: `${item.name} (${item.serial_no})`,
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

// Fetch all departments
const fetchDepartments = async () => {
  try {
    const response = await $api("/departments?context=filters", {
      headers: {
        Authorization: `Bearer ${accessToken.value}`,
      },
    })

    departments.value = response.data.map(d => ({
      title: d.name,
      value: d.id,
    }))

    // Build department map
    departmentMap.value = {}
    response.data.forEach(dept => {
      departmentMap.value[dept.id] = dept.name
    })
  } catch (error) {
    $toast.error("Failed to load departments")
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
      is_default: !!item.is_default,
      order: item.order ?? 0,
    }))

    // Build lookup map
    docTypesMapById.value = {}
    data.forEach(item => {
      docTypesMapById.value[item.id] = {
        id: item.id,
        name: item.name,
        is_default: !!item.is_default,
        order: item.order ?? 0,
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
    is_existing: false,
    existing_document_id: null,
    existing_file_url: null,
  })
}

const removeDynamicDocument = nonDefaultIndex => {
  const nonDefaultDocs = formData.value.dynamic_documents
    .map((doc, index) => ({ doc, index }))
    .filter(item => item.doc.is_default === false)

  const target = nonDefaultDocs[nonDefaultIndex]

  if (target) {
    formData.value.dynamic_documents.splice(target.index, 1)
  }
}

const fetchManagers = async () => {
  try {
    const { data } = await $api(`/employees?only_managers=true&for_employee=${route.params.id}`, {
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

// Fetch all designations
const fetchDesignations = async () => {
  try {
    const response = await $api("/designations", {
      headers: {
        Authorization: `Bearer ${accessToken.value}`,
      },
    })

    designations.value = response.data.map(d => ({
      title: d.title,
      value: d.id,
    }))
  } catch (error) {
    $toast.error("Failed to load designations")
  }
}

const availableDocumentTypes = rowIndex => {
  const all = documentTypes.value.filter(d => !d.is_default) || []

  // Collect ids already chosen in other non-default rows
  const chosen = new Set(
    (formData.value.dynamic_documents.filter(d => !d.is_default) || [])
      .filter(d => !d.is_default)
      .map((d, idx) => (idx === rowIndex ? null : d.document_type_id))
      .filter(id => id !== null && id !== undefined && id !== '')
      .filter(d => !d.is_default)
      .map(id => String(id)),
  )

  return all.filter(dt => !chosen.has(String(dt.value)))
}

// Optional guard: if somehow a duplicate slips in (manual change, etc.),
// clear the later selection and warn
watch(
  () => formData.value.dynamic_documents,
  docs => {
    if (!Array.isArray(docs)) return

    const seen = new Set()

    docs
      .filter(d => !d.is_default)
      .forEach(d => {
        const id = d.document_type_id
        if (id === null || id === undefined || id === '') return
        const key = String(id)
        if (seen.has(key)) {
          d.document_type_id = ''
          $toast?.warning?.('This document type is already selected in another row')
        } else {
          seen.add(key)
        }
      })
  },
  { deep: true },
)

// Load employee data for edit
const loadEmployeeData = async () => {
  try {
    const { data } = await $api(`/employees/${route.params.id}?for-edit=true`, {
      headers: {
        Authorization: `Bearer ${accessToken.value}`,
      },
    })

    // Update form data
    Object.keys(formData.value).forEach(key => {
      if (data[key] !== undefined && formData.value[key] !== undefined) {
        formData.value[key] = data[key] ?? ''
      }
    })

    // Ensure attendance_days has the is_working_day field
    if (formData.value.attendance_days && formData.value.attendance_days.length > 0) {
      // Add is_working_day field if it doesn't exist
      formData.value.attendance_days = formData.value.attendance_days.map(day => ({
        ...day,
        is_working_day: day.is_working_day !== undefined ? day.is_working_day : false,
      }))
    }
    else {
      // Create default attendance days if none exist
      formData.value.attendance_days = [
        { day: 'Monday', is_working_day: false, inside_office: false, outside_office: false, allow_late_checkin: false, checkin_time: '09:00 AM', checkout_time: '06:00 PM' },
        { day: 'Tuesday', is_working_day: false, inside_office: false, outside_office: false, allow_late_checkin: false, checkin_time: '09:00 AM', checkout_time: '06:00 PM' },
        { day: 'Wednesday', is_working_day: false, inside_office: false, outside_office: false, allow_late_checkin: false, checkin_time: '09:00 AM', checkout_time: '06:00 PM' },
        { day: 'Thursday', is_working_day: false, inside_office: false, outside_office: false, allow_late_checkin: false, checkin_time: '09:00 AM', checkout_time: '06:00 PM' },
        { day: 'Friday', is_working_day: false, inside_office: false, outside_office: false, allow_late_checkin: false, checkin_time: '09:00 AM', checkout_time: '06:00 PM' },
        { day: 'Saturday', is_working_day: false, inside_office: false, outside_office: false, allow_late_checkin: false, checkin_time: '09:00 AM', checkout_time: '06:00 PM' },
        { day: 'Sunday', is_working_day: false, inside_office: false, outside_office: false, allow_late_checkin: false, checkin_time: '09:00 AM', checkout_time: '06:00 PM' },
      ]
    }

    formData.value.photo = data.photo_url
    formData.value.cnic_document = data.cnic_document_url
    formData.value.resume = data.resume_url
    formData.value.experience_letter = data.experience_letter_url
    formData.value.offer_letter = data.offer_letter_url
    formData.value.contract = data.contract_url

    formData.value.current_photo = data.photo_url
    formData.value.current_cnic_document = data.cnic_document_url
    formData.value.current_resume = data.resume_url
    formData.value.current_experience_letter = data.experience_letter_url
    formData.value.current_offer_letter = data.offer_letter_url
    formData.value.current_contract = data.contract_url

    // Load existing dynamic documents
    if (data.documents && data.documents.length > 0) {
      formData.value.dynamic_documents = data.documents.map(doc => ({
        document_type_id: doc.document_type_id,
        file: null, // No new file uploaded yet
        is_existing: true,
        existing_document_id: doc.id,
        is_default: doc.is_default,
        order: doc.order,
        existing_file_url: doc.file_url,
        document_type_name: doc.document_type_name,
      }))
    } else {
      formData.value.dynamic_documents = []
    }

    // Ensure all default document types exist for editing even if not previously uploaded
    const presentTypeIds = new Set((formData.value.dynamic_documents || []).map(d => d.document_type_id))

    documentTypes.value
      .filter(dt => dt.is_default)
      .forEach(dt => {
        if (!presentTypeIds.has(dt.value)) {
          formData.value.dynamic_documents.push({
            document_type_id: dt.value,
            file: null,
            is_existing: false,
            existing_document_id: null,
            existing_file_url: null,
            is_default: true,
            order: dt.order ?? 0,
            document_type_name: docTypesMapById.value[dt.value]?.name || dt.title,
          })
        }
      })

    // Load existing assigned devices
    if (data.assets && data.assets.length > 0) {
      assignedDevices.value = data.assets.map(asset => ({
        asset_id: asset.id,
        assigned_date: asset.pivot?.assigned_date || '',
        name: asset.name,
      }))

      // Add assigned assets back to the available list so they can be selected
      const assignedAssets = data.assets.map(item => ({
        value: item.id,
        title: `${item.name} (${item.serial_no})`,
      }))

      availableAssets.value = [...availableAssets.value, ...assignedAssets]
    }

  } catch (error) {
    console.log(error)
    $toast.error("Failed to load employee data")
  }
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
  isSubmitting.value = true

  try {
    const { valid } = await form.value.validate()
    if (!valid) {
      showNotification("Please fill all required fields", "warning")

      return
    }

    const formDataToSend = new FormData()

    // Append regular fields
    Object.entries(formData.value).forEach(([key, value]) => {
      if (value !== null && !Array.isArray(value)) {
        formDataToSend.append(key, value)
      }
    })

    // Append files if they exist
    if (formData.value.photo) {
      formDataToSend.append("photo", formData.value.photo)
    }
    if (formData.value.cnic_document) {
      formDataToSend.append("cnic_document", formData.value.cnic_document)
    }
    if (formData.value.resume) {
      formDataToSend.append("resume", formData.value.resume)
    }
    if (formData.value.experience_letter) {
      formDataToSend.append("experience_letter", formData.value.experience_letter)
    }
    if (formData.value.offer_letter) {
      formDataToSend.append("offer_letter", formData.value.offer_letter)
    }
    if (formData.value.contract) {
      formDataToSend.append("contract", formData.value.contract)
    }

    // Handle dynamic documents
    formData.value.dynamic_documents.forEach((document, index) => {
      if (document.document_type_id) {
        // Always send document type
        formDataToSend.append(`dynamic_documents[${index}][document_type_id]`, document.document_type_id)
        formDataToSend.append(`dynamic_documents[${index}][document_type_name]`, documentTypes.value.find(dt => dt.value == document.document_type_id)?.title || '')
        if (document.file) {
          formDataToSend.append(`dynamic_documents[${index}][file]`, document.file)
        }

        // Send existing document ID if this is an existing document
        if (document.existing_document_id) {
          formDataToSend.append(`dynamic_documents[${index}][existing_document_id]`, document.existing_document_id)
        }
      }
    })

    formDataToSend.append('attendance_days', JSON.stringify(formData.value.attendance_days))

    // Handle assigned devices
    if (assignedDevices.value.length > 0) {
      formDataToSend.append('assigned_devices', JSON.stringify(assignedDevices.value))
    }

    formDataToSend.append("_method", "PUT")

    // Submit data
    await $api(`/employees/${route.params.id}`, {
      method: "POST",
      body: formDataToSend,
      headers: {
        Authorization: `Bearer ${accessToken.value}`,
      },
    })
    $toast.success(`Employee updated successfully!`)
    router.push({ name: 'hrm-employee-list' })
  } catch (error) {
    let message = `Failed to update employee`
    if (error.response && error.response.status === 201) {
      $toast.success("Employee created successfully")
      setTimeout(() => {
        router.push({ name: 'hrm-employee-list' })
      }, 1500)

      return
    }

    if (error?.status === 422) {
      const errs = error._data?.errors || []
      errors.value = errs
      message = Array.isArray(errs) ? errs.join('<br>') : String(errs)
    }

    $toast.error(message)

  } finally {
    isSubmitting.value = false
  }
}

// Initialize form
const initForm = async () => {
  formLoaded.value = false
  await fetchDepartments()
  await fetchDesignations()
  await fetchBranches()
  await fetchManagers()
  await fetchJobCategories()
  await fetchJobStages()
  await fetchTerminationTypes()
  await fetchEmploymentTypes()
  await fetchEmploymentStatuses()
  await fetchDocumentTypes()
  await fetchAvailableAssets()
  await loadEmployeeData()
  formLoaded.value = true
}

onMounted(() => {
  window.addEventListener("scroll", handleScroll)
  initForm()
})

onUnmounted(() => {
  window.removeEventListener("scroll", handleScroll)
})
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
