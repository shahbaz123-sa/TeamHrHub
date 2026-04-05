<template>
  <VRow>
    <VCol cols="12">
      <!-- Top Personal Info Card -->
      <VCard class="mb-6 pa-4 personal-info-card">
        <VRow class="align-center">
          <!-- Avatar -->
          <VCol cols="12" md="2" class="d-flex align-center justify-center px-8">
            <VAvatar size="96" rounded="lg">
              <DocumentImageViewer :type="'avatar'" :src="employee.profile_picture || '/images/avatars/dummy.png'" :pdf-title="employee.name" avatarSize="96" />
            </VAvatar>
          </VCol>
          <vCol cols="12" md="10">
            <VRow class="align-center">
            <!-- Name and Contact -->
            <VCol cols="12" md="9">
              <h2 class="text-h5 mb-1 mt-6">{{ employee.name || 'N/A' }}</h2>
<!--              <p class="mb-1 text-medium-emphasis">Zarea Tech</p>-->
              <div class="d-flex align-center flex-wrap mt-2">
                <VIcon icon="tabler-mail" color="primary" size="16" class="me-2" />
                <span class="text-high-emphasis">{{ employee.official_email || employee.personal_email || 'N/A' }}</span>
                <VIcon icon="tabler-phone" color="primary" size="16" class="ms-10 me-2" />
                <span class="text-high-emphasis">{{ employee.phone || 'N/A' }}</span>
              </div>
            </VCol>
            <!-- Edit button -->
            <VCol cols="12" md="3" class="d-flex justify-center justify-md-center mt-md-0">
              <VBtn v-if="hasPermission('employee.update')" color="primary" size="small" @click="goToEdit">
                <VIcon start icon="tabler-edit" />
                Edit Details
              </VBtn>
            </VCol>
            <!-- Summary info -->
            <VCol cols="12">
            <VRow>
              <VCol cols="6" md="2">
                <div class="summary-item">
                  <span class="summary-label">Designation</span>
                  <span class="summary-value text-high-emphasis">{{ employee.designation?.title || 'N/A' }}</span>
                </div>
              </VCol>
              <VCol cols="6" md="2">
                <div class="summary-item">
                  <span class="summary-label">Department</span>
                  <span class="summary-value text-high-emphasis">{{ employee.department?.name || 'N/A' }}</span>
                </div>
              </VCol>
              <VCol cols="6" md="auto" class="pr-6">
                <div class="summary-item">
                  <span class="summary-label">Reporting to</span>
                  <span class="summary-value text-high-emphasis">{{ employee.reporting_to_name || employee.reporting_to?.name || 'N/A' }}</span>
                </div>
              </VCol>
              <VCol cols="6" md="2">
                <div class="summary-item">
                  <span class="summary-label">Employee Code</span>
                  <span class="summary-value text-high-emphasis">{{ employee.employee_code || 'N/A' }}</span>
                </div>
              </VCol>
              <VCol cols="6" md="2">
                <div class="summary-item">
                  <span class="summary-label">Status</span>
                  <span class="summary-value text-high-emphasis">{{ employee.employment_status?.name || 'N/A' }}</span>
                </div>
              </VCol>
            </VRow>
          </VCol>
            </VRow>
          </vCol>
        </VRow>
        <VCardText>
          <!-- Sticky Wizard Steps/Tabs -->
          <VTabs v-model="currentStep" class="sticky-tabs font-300" align-tabs="start">
            <VTab v-for="(step, index) in steps" :key="index" :value="index" @click="scrollToSection(step.id)">
              {{ step.title }}
            </VTab>
          </VTabs>

          <!-- Content Sections -->
          <div>
            <!-- Personal Details Section -->
            <div id="personal-details" class="form-section">
              <h3 class="text-h4 text-primary">Personal Details:</h3>
              <VRow >
                <!-- First Column -->
                <VCol cols="12" md="4">
                  <div class="detail-item">
                    <span class="stepper-title font-weight-medium mb-0">Father's Name:</span>
                    <span class="detail-value text-high-emphasis">{{ employee.father_name }}</span>
                  </div>
                  <div class="detail-item">
                    <span class="stepper-title font-weight-medium mb-0">Emergency Contact Number:</span>
                    <span class="detail-value text-high-emphasis">{{ employee.emergency_phone }}</span>
                  </div>
                  <div class="detail-item">
                    <span class="stepper-title font-weight-medium mb-0">Gender:</span>
                    <span class="detail-value text-high-emphasis">{{ ucFirst(employee.gender) }}</span>
                  </div>
                  <div class="detail-item">
                    <span class="stepper-title font-weight-medium mb-0">No of dependent:</span>
                    <span class="detail-value text-high-emphasis">{{ employee.dependents || '0' }}</span>
                  </div>
                </VCol>

                <!-- Second Column -->
                <VCol cols="12" md="4">
                  <div class="detail-item">
                    <span class="stepper-title font-weight-medium mb-0">Emergency Contact Name:</span>
                    <span class="detail-value text-high-emphasis">{{ employee.emergency_contact_name }}</span>
                  </div>
                  <div class="detail-item">
                    <span class="stepper-title font-weight-medium mb-0">Date of Birth:</span>
                    <span class="detail-value text-high-emphasis">{{ employee.dob }}</span>
                  </div>
                  <div class="detail-item">
                    <span class="stepper-title font-weight-medium mb-0">Blood Group:</span>
                    <span class="detail-value text-high-emphasis">{{ employee.blood_group }}</span>
                  </div>
                  <div class="detail-item">
                    <span class="stepper-title font-weight-medium mb-0">Personal Email:</span>
                    <span class="detail-value text-high-emphasis">{{ employee.personal_email }}</span>
                  </div>
                </VCol>

                <!-- Third Column -->
                <VCol cols="12" md="4">
                  <div class="detail-item">
                    <span class="stepper-title font-weight-medium mb-0">Emergency Contact Relation:</span>
                    <span class="detail-value text-high-emphasis">{{ employee.emergency_contact_relation }}</span>
                  </div>
                  <div class="detail-item">
                    <span class="stepper-title font-weight-medium mb-0">CNIC#:</span>
                    <span class="detail-value text-high-emphasis">{{ employee.cnic }}</span>
                  </div>
                  <div class="detail-item">
                    <span class="stepper-title font-weight-medium mb-0">Marital Status:</span>
                    <span class="detail-value text-high-emphasis">{{ ucFirst(employee.marital_status) }}</span>
                  </div>
                </VCol>

                <VCol cols="12" md="6">
                  <div class="detail-item">
                    <span class="stepper-title font-weight-medium mb-0">Address 1:</span>
                    <span class="detail-value text-high-emphasis">{{ employee.address1 }}</span>
                  </div>
                </VCol>
                <VCol cols="12" md="6">
                  <div class="detail-item">
                    <span class="stepper-title font-weight-medium mb-0">Address 2:</span>
                    <span class="detail-value text-high-emphasis">{{ employee.address2 }}</span>
                  </div>
                </VCol>
              </VRow>
            </div>

            <!-- Company Details Section -->
            <div id="company-details" class="form-section">
              <h3 class="text-h4 mb-2 text-primary">Company Details</h3>
<!--              <VDivider class="mb-5" />-->
              <VRow>
                <VCol cols="12" sm="4">
                  <div class="detail-item">
                    <span class="stepper-title font-weight-medium mb-0">Office Branch:</span>
                    <span class="detail-value text-high-emphasis">{{ employee.branch?.name || 'N/A' }}</span>
                  </div>
                  <div class="detail-item">
                    <span class="stepper-title font-weight-medium mb-0">Employment Type:</span>
                    <span class="detail-value text-high-emphasis">{{ employee.employment_type?.name || 'N/A' }}</span>
                  </div>
                  <div class="detail-item">
                    <span class="stepper-title font-weight-medium mb-0">End of Employment Type:</span>
                    <span class="detail-value text-high-emphasis">{{ employee.termination_type?.name || 'N/A' }}</span>
                  </div>
                </VCol>
                <VCol cols="12" sm="4">
                  <div class="detail-item">
                    <span class="stepper-title font-weight-medium mb-0">Bonus:</span>
                    <span class="detail-value text-high-emphasis">{{ Number(employee.bonus) || 'N/A' }}</span>
                  </div>
                  <div class="detail-item">
                    <span class="stepper-title font-weight-medium mb-0">Employee Status:</span>
                    <span class="detail-value text-high-emphasis">{{ employee.employment_status?.name || 'N/A' }}</span>
                  </div>
                  <div class="detail-item">
                    <span class="stepper-title font-weight-medium mb-0">End of Employment Date:</span>
                    <span class="detail-value text-high-emphasis">{{ employee.termination_date || 'N/A' }}</span>
                  </div>
                </VCol>
                <VCol cols="12" sm="4">
                  <div class="detail-item">
                    <span class="stepper-title font-weight-medium mb-0">Date of Joining:</span>
                    <span class="detail-value text-high-emphasis">{{ employee.date_of_joining }}</span>
                  </div>
                  <div class="detail-item">
                    <span class="stepper-title font-weight-medium mb-0">User Status:</span>
                    <span class="detail-value text-high-emphasis">{{ employee.status ? 'Active' : 'In-active' }}</span>
                  </div>
                  <div class="detail-item">
                    <span class="stepper-title font-weight-medium mb-0">Effective Date (Last working day):</span>
                    <span class="detail-value text-high-emphasis">{{ employee.termination_effective_date || 'N/A' }}</span>
                  </div>
                </VCol>
                <VCol cols="12">
                  <div class="detail-item">
                    <span class="stepper-title font-weight-medium mb-0">End of Employement Reason:</span>
                    <span class="detail-value text-high-emphasis">{{ employee.termination_reason || 'N/A' }}</span>
                  </div>
                </VCol>
              </VRow>
            </div>

            <!-- Documents Section -->
            <div id="documents-details" class="form-section">
              <h3 class="text-h4 mb-2 text-primary">Documents</h3>
<!--              <VDivider class="mb-5" />-->
              <VRow>
                <!--Offer letter-->
                <VCol lg="2" md="2" sm="4" xs="7" cols="8">
                  <span class="stepper-title font-weight-medium mb-0">Offer Letter:</span>
                </VCol>
                <VCol lg="2" md="2" sm="2" xs="5" cols="4">
                  <DocumentImageViewer :src="employee.offer_letter_url" :pdf-title="'Offer Letter'" />
                </VCol>

                <!--CNIC-->
                <VCol lg="2" md="2" sm="4" xs="7" cols="8">
                  <span class="stepper-title font-weight-medium mb-0">CNIC Document:</span>
                </VCol>
                <VCol lg="2" md="2" sm="2" xs="5" cols="4">
                  <DocumentImageViewer :src="employee.cnic_document_url" :pdf-title="'Employee CNIC'" />
                </VCol>

                <!--Resume-->
                <VCol lg="2" md="2" sm="4" xs="7" cols="8">
                  <span class="stepper-title font-weight-medium mb-0">Resume:</span>
                </VCol>
                <VCol lg="2" md="2" sm="2" xs="5" cols="4">
                  <DocumentImageViewer :src="employee.resume_url" :pdf-title="'Resume'" />
                </VCol>

                <!--Photo-->
                <VCol lg="2" md="2" sm="4" xs="7" cols="8">
                  <span class="stepper-title font-weight-medium mb-0">Photo:</span>
                </VCol>
                <VCol lg="2" md="2" sm="2" xs="5" cols="4">
                  <DocumentImageViewer :src="employee.photo_url" :pdf-title="'Employee Photo'" />
                </VCol>

                <!--Experiance Letter-->
                <VCol lg="2" md="2" sm="4" xs="7" cols="8">
                  <span class="stepper-title font-weight-medium mb-0">Experience Letter:</span>
                </VCol>
                <VCol lg="2" md="2" sm="2" xs="5" cols="4">
                  <DocumentImageViewer :src="employee.experience_letter_url" :pdf-title="'Experience Letter'" />
                </VCol>

                <!--Contract-->
                <VCol lg="2" md="2" sm="4" xs="7" cols="8">
                  <span class="stepper-title font-weight-medium mb-0">Contract:</span>
                </VCol>
                <VCol lg="2" md="2" sm="2" xs="5" cols="4">
                  <DocumentImageViewer :src="employee.contract_url" :pdf-title="'Contract'" />
                </VCol>

                <template
                v-for="document in employee.documents?.filter(d => d.is_default)"
                :key="document.id"
                >
                  <VCol lg="2" md="2" sm="4" xs="7" cols="8">
                    <span class="stepper-title font-weight-medium mb-0">{{ document.document_type_name }}:</span>
                  </VCol>
                  <VCol lg="2" md="2" sm="2" xs="5" cols="4">
                    <DocumentImageViewer :src="document.file_url" :pdf-title="document.document_type_name" />
                  </VCol>
                </template>
              </VRow>
              <div v-if="employee.documents && employee.documents?.filter(d => !d.is_default).length > 0">
                <VDivider class="my-5">
                  <span class="text-primary font-weight-medium">
                    Additional Documents
                  </span>
                </VDivider>
                <VRow>
                  <template
                    v-for="document in employee.documents?.filter(d => !d.is_default)"
                    :key="document.id"
                  >
                    <VCol lg="2" md="2" sm="4" xs="7" cols="8">
                      <span class="stepper-title font-weight-medium mb-0">{{ document.document_type_name }}:</span>
                    </VCol>
                    <VCol lg="2" md="2" sm="2" xs="5" cols="4">
                      <DocumentImageViewer :src="document.file_url" :pdf-title="document.document_type_name" />
                    </VCol>
                  </template>
                </VRow>
              </div>
            </div>

            <!-- Bank Details Section -->
            <div id="bank-details" class="form-section">
              <h3 class="text-h4 mb-2 text-primary">Bank Account Details</h3>
<!--              <VDivider class="mb-5" />-->
              <VRow>
                <VCol cols="12" sm="4">
                  <div class="detail-item">
                    <span class="stepper-title font-weight-medium mb-0">Account Holder Name</span>
                    {{ employee.account_holder_name }}
                  </div>
                  <div class="detail-item">
                    <span class="stepper-title font-weight-medium mb-0">Account Number</span>
                    <div class="d-flex align-center">
                      <span class="me-2">{{ employee.account_number }}</span>
                      <!-- Tabler Copy Icon -->
                      <VIcon
                        size="x-small"
                        class="cursor-pointer"
                        icon="tabler-copy"
                        color="primary"
                        @click="copyToClipboard(employee.account_number)"
                        title="Copy Account Number"
                      />
                    </div>
                  </div>
                </VCol>
                <VCol cols="12" sm="4">
                  <div class="detail-item">
                    <span class="stepper-title font-weight-medium mb-0">Bank Name</span>
                    {{ employee.bank_name }}
                  </div>
                  <div class="detail-item">
                    <span class="stepper-title font-weight-medium mb-0">IBAN</span>
                    {{ employee.iban }}
                  </div>
                </VCol>
                <VCol cols="12" sm="4">
                  <div class="detail-item">
                    <span class="stepper-title font-weight-medium mb-0">Branch Location</span>
                    {{ employee.branch_location }}
                  </div>
                </VCol>
              </VRow>
            </div>

            <!-- Device Details Section -->
            <div id="device-details" class="form-section">
              <h3 class="text-h4 mb-2 text-primary">Assigned Devices</h3>
<!--              <VDivider class="mb-3" />-->
              <VDataTable v-if="employee.assets && employee.assets.length > 0" :headers="deviceHeaders"
                          :items="employee.assets" :items-per-page="10" class="devices-table" hide-default-footer elevation="1">
                <template #item.name="{ item }">
                  <div class="d-flex align-center">
                    <div>
                      <h6 class="text-sm font-weight-semibold mb-1">{{ item.name }}</h6>
                      <p class="text-xs text-medium-emphasis mb-0">{{ item.serial_no }}</p>
                    </div>
                  </div>
                </template>
                <template #item.asset_type="{ item }">
                  <VChip size="small"  variant="tonal">
                    {{ item.asset_type?.name || 'Unknown Type' }}
                  </VChip>
                </template>
                <template #item.serial_no="{ item }">
                  <span class="text-sm font-weight-medium">{{ item.serial_no }}</span>
                </template>
                <template #item.assigned_date="{ item }">
                  <div class="d-flex align-center">
                    <VIcon icon="tabler-calendar-event" size="16" class="me-2 text-medium-emphasis" />
                    <span class="text-sm">{{ formatDate(item.pivot?.assigned_date) }}</span>
                  </div>
                </template>
              </VDataTable>
              <div v-else class="text-center py-8 w-100">
                <VAvatar size="64" color="grey-lighten-2" variant="tonal" class="mb-4">
                  <VIcon icon="tabler-device-laptop" size="32" />
                </VAvatar>
                <h6 class="text-lg font-weight-medium mb-2">No Devices Assigned</h6>
                <p class="text-medium-emphasis">This employee doesn't have any devices assigned yet.</p>
              </div>
            </div>

            <!-- Attendance Section -->
            <div id="attendance-details" class="form-section">
                <h3 class="text-h4 mb-2 text-primary">Attendance Days</h3>
<!--                <VDivider class="mb-3" />-->
                <VTable class="mb-4 d-none d-md-table">
                    <thead>
                    <tr>
                    <th>Day</th>
                    <th class="text-center">Working Day</th>
                    <th class="text-center">Inside Office</th>
                    <th class="text-center">Outside Office</th>
                    <th class="text-center">Checkin Time</th>
                    <th class="text-center">Checkout Time</th>
                    <th class="text-center">Allow late checkin</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="setting in employee.attendance_days" :key="setting.day">
                    <td>{{ setting.day }}</td>
                    <td class="text-center">
                        <VIcon v-if="setting.is_working_day" color="success">tabler-check</VIcon>
                        <VIcon v-else color="error">tabler-x</VIcon>
                    </td>
                    <td class="text-center">
                        <VIcon v-if="setting.inside_office" color="success">tabler-check</VIcon>
                        <VIcon v-else color="error">tabler-x</VIcon>
                    </td>
                    <td class="text-center">
                        <VIcon v-if="setting.outside_office" color="success">tabler-check</VIcon>
                        <VIcon v-else color="error">tabler-x</VIcon>
                    </td>
                    <td class="text-center">
                        <VTextField
                        v-model="setting.checkin_time"
                        type="time"
                        inputmode="numeric"
                        label=""
                        variant="outlined"
                        hide-details
                        density="compact"
                        readonly
                        />
                    </td>
                    <td class="text-center">
                        <VTextField
                        v-model="setting.checkout_time"
                        type="time"
                        inputmode="numeric"
                        label=""
                        variant="outlined"
                        hide-details
                        density="compact"
                        readonly
                        />
                    </td>
                    <td class="text-center">
                        <VIcon v-if="setting.allow_late_checkin" color="success">tabler-check</VIcon>
                        <VIcon v-else color="error">tabler-x</VIcon>
                    </td>
                    </tr>
                    </tbody>
                </VTable>
                <div class="d-md-none">
                    <VCard v-for="setting in employee.attendance_days" :key="setting.day" variant="outlined" class="mb-3">
                    <VCardText class="pa-4">
                        <div class="d-flex justify-space-between align-center mb-2">
                        <h4 class="text-h6 ma-0">{{ setting.day }}</h4>
                        </div>
                        <VRow>
                        <VCol cols="6">
                            <div class="d-flex align-center">
                            <VIcon v-if="setting.inside_office" color="success" class="me-2">tabler-check</VIcon>
                            <VIcon v-else color="error" class="me-2">tabler-x</VIcon>
                            <span class="text-sm">Inside Office</span>
                            </div>
                        </VCol>
                        <VCol cols="6">
                            <div class="d-flex align-center">
                            <VIcon v-if="setting.outside_office" color="success" class="me-2">tabler-check</VIcon>
                            <VIcon v-else color="error" class="me-2">tabler-x</VIcon>
                            <span class="text-sm">Outside Office</span>
                            </div>
                        </VCol>
                        </VRow>
                    </VCardText>
                    </VCard>
                </div>
            </div>

            <!-- Salary & Allowances Section -->
            <div id="salary-allowances" class="form-section">
              <h3 class="text-h4 mb-2 text-primary">Salary & Allowances</h3>
              <EmployeeSalaryAllowancesTab :employee-id="route.params.id" :readonly="true" />
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<script setup>
import DocumentImageViewer from "@/components/common/DocumentImageViewer.vue";
import EmployeeSalaryAllowancesTab from "@/components/hrm/employee/EmployeeSalaryAllowancesTab.vue";
import { ucFirst } from "@/utils/helpers/str";
import { onMounted, ref, onUnmounted } from "vue";
import { useRoute, useRouter } from "vue-router";

const route = useRoute();
const router = useRouter();

const currentStep = ref(0);
const steps = [
  { title: "Personal Details", id: "personal-details" },
  { title: "Company Details", id: "company-details" },
  { title: "Documents", id: "documents-details" },
  { title: "Bank Details", id: "bank-details" },
  { title: "Device Details", id: "device-details" },
  { title: "Attendance", id: "attendance-details" },
  { title: "Salary & Allowances", id: "salary-allowances" },
];

const employee = ref({});

const goToEdit = () => {
  router.push({ name: 'hrm-employee-edit-id', params: { id: route.params.id } });
};

// Device table headers
const deviceHeaders = [
  { title: 'Device', key: 'name', sortable: false },
  { title: 'Type', key: 'asset_type', sortable: false },
  { title: 'Serial Number', key: 'serial_no', sortable: false },
  { title: 'Assigned Date', key: 'assigned_date', sortable: false }
];

const copyToClipboard = (text) => {
  navigator.clipboard.writeText(text).then(() => {
    $toast.info("Account Number Copied!");
  });
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

const fetchEmployee = async () => {
  try {
    const { data } = await $api(`/employees/${route.params.id}`, {
      headers: {
        Authorization: `Bearer ${useCookie("accessToken").value}`,
      },
    });
    employee.value = data;
  } catch (error) {
    console.error("Failed to fetch employee details:", error);
  }
};

const scrollToSection = (sectionId) => {
  const element = document.getElementById(sectionId);
  if (element) {
    const headerOffset = 120; // Adjust this value based on your sticky header's height
    const elementPosition = element.getBoundingClientRect().top;
    const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

    window.scrollTo({
      top: offsetPosition,
      behavior: "smooth",
    });
  }
};

const handleScroll = () => {
  const scrollPosition = window.scrollY + 150; // Offset to trigger active state earlier
  for (let i = steps.length - 1; i >= 0; i--) {
    const section = document.getElementById(steps[i].id);
    if (section && section.offsetTop <= scrollPosition) {
      currentStep.value = i;
      break;
    }
  }
};

onMounted(() => {
  window.addEventListener("scroll", handleScroll);
  fetchEmployee();
});

onUnmounted(() => {
  window.removeEventListener("scroll", handleScroll);
});
</script>

<style scoped>
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

.detail-item {
  display: flex;
  flex-direction: column;
  padding-block: 1rem;
  padding-bottom: 0.5rem;
}

.detail-value {
  font-size: 0.9rem;
  font-weight: 450;
  margin-top: 0.25rem;
  min-height: 1.5rem; /* Ensures consistent height */
}

.document-item {
  border-block-end: 1px solid rgba(var(--v-border-color), 0.12);
  margin-block-end: 16px;
  padding-block-end: 12px;
}

.document-item:last-child {
  border-block-end: none;
  margin-block-end: 0;
}

.sticky-tabs {
  position: sticky;
  background-color: rgb(var(--v-theme-surface));
  z-index: 10;
  border-bottom: 1px solid rgba(var(--v-border-color), 0.12);
}

.form-section {
  padding-top: 1.2rem;
}

</style>
