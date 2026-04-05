<script setup>
import { defineEmits, defineProps, nextTick, ref } from "vue";
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
});

const emit = defineEmits(["update:isDrawerOpen", "userData"]);

// Form state
const isFormValid = ref(false);
const refForm = ref();
const isLoading = ref(false);

// Form fields
const fullName = ref("");
const email = ref("");
const password = ref("");
const confirmPassword = ref("");
const company = ref("");
const country = ref("");
const phone = ref("");
const role = ref("");
const plan = ref("");
const status = ref("active"); // Default to active
const billingEmail = ref("");
const taxId = ref("");
const address = ref("");
const city = ref("");
const state = ref("");
const postalCode = ref("");

// Dropdown options data - moved to static data
const dropdownOptions = ref({
  countries: [
    { title: "United States", value: "US" },
    { title: "United Kingdom", value: "UK" },
    { title: "Canada", value: "CA" },
    { title: "Australia", value: "AU" },
    { title: "Germany", value: "DE" },
  ],
  roles: [
    { title: "Admin", value: "admin" },
    { title: "Author", value: "author" },
    { title: "Editor", value: "editor" },
    { title: "Maintainer", value: "maintainer" },
    { title: "Subscriber", value: "subscriber" },
  ],
  plans: [
    { title: "Basic", value: "basic" },
    { title: "Company", value: "company" },
    { title: "Enterprise", value: "enterprise" },
    { title: "Team", value: "team" },
  ],
  statuses: [
    { title: "Active", value: "active" },
    { title: "Inactive", value: "inactive" },
    { title: "Pending", value: "pending" },
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

// Form submission
const onSubmit = async () => {
  const { valid } = await refForm.value.validate();

  if (valid) {
    isLoading.value = true;

    try {
      const userData = {
        name: fullName.value,
        email: email.value,
        password: password.value,
        password_confirmation: confirmPassword.value,
        company: company.value,
        country: country.value,
        phone: phone.value,
        role: role.value,
        plan: plan.value,
        status: status.value,
        billing_email: billingEmail.value,
        tax_id: taxId.value,
        address: address.value,
        city: city.value,
        state: state.value,
        postal_code: postalCode.value,
      };

      emit("userData", userData);
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
</script>

<template>
  <VNavigationDrawer
    temporary
    :width="600"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <!-- 👉 Title -->
    <AppDrawerHeaderSection
      title="Add New User"
      @cancel="closeNavigationDrawer"
    />

    <VDivider />

    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat>
        <VCardText>
          <!-- 👉 Form -->
          <VForm ref="refForm" v-model="isFormValid" @submit.prevent="onSubmit">
            <VRow>
              <!-- 👉 Basic Information Section -->
              <VCol cols="12">
                <h6 class="text-h6 mb-3">Basic Information</h6>
              </VCol>

              <!-- 👉 Full name -->
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="fullName"
                  :rules="[requiredValidator]"
                  label="Full Name"
                  placeholder="John Doe"
                />
              </VCol>

              <!-- 👉 Email -->
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="email"
                  :rules="[requiredValidator, emailValidator]"
                  label="Email"
                  placeholder="johndoe@example.com"
                />
              </VCol>

              <!-- 👉 Password -->
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="password"
                  :rules="[requiredValidator, passwordValidator]"
                  label="Password"
                  type="password"
                  placeholder="············"
                />
              </VCol>

              <!-- 👉 Confirm Password -->
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="confirmPassword"
                  :rules="[
                    requiredValidator,
                    confirmedValidator(confirmPassword, password),
                  ]"
                  label="Confirm Password"
                  type="password"
                  placeholder="············"
                />
              </VCol>

              <!-- 👉 Company Information Section -->
              <VCol cols="12">
                <h6 class="text-h6 mb-3">Company Information</h6>
              </VCol>

              <!-- 👉 Company -->
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="company"
                  label="Company"
                  placeholder="Company Name"
                />
              </VCol>

              <!-- 👉 Tax ID -->
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="taxId"
                  label="Tax ID"
                  placeholder="Tax Identification Number"
                />
              </VCol>

              <!-- 👉 Billing Email -->
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="billingEmail"
                  :rules="[emailValidator]"
                  label="Billing Email"
                  placeholder="billing@example.com"
                />
              </VCol>

              <!-- 👉 Contact Information Section -->
              <VCol cols="12">
                <h6 class="text-h6 mb-3">Contact Information</h6>
              </VCol>

              <!-- 👉 Phone -->
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="phone"
                  :rules="[requiredValidator]"
                  label="Phone"
                  placeholder="+1 (123) 456-7890"
                />
              </VCol>

              <!-- 👉 Country -->
              <VCol cols="12" md="6">
                <AppSelect
                  v-model="country"
                  label="Country"
                  placeholder="Select Country"
                  :rules="[requiredValidator]"
                  :items="dropdownOptions.countries"
                />
              </VCol>

              <!-- 👉 Address -->
              <VCol cols="12">
                <AppTextField
                  v-model="address"
                  label="Address"
                  placeholder="Street Address"
                />
              </VCol>

              <!-- 👉 City -->
              <VCol cols="12" md="4">
                <AppTextField v-model="city" label="City" placeholder="City" />
              </VCol>

              <!-- 👉 State -->
              <VCol cols="12" md="4">
                <AppTextField
                  v-model="state"
                  label="State/Province"
                  placeholder="State"
                />
              </VCol>

              <!-- 👉 Postal Code -->
              <VCol cols="12" md="4">
                <AppTextField
                  v-model="postalCode"
                  label="Postal Code"
                  placeholder="ZIP/Postal Code"
                />
              </VCol>

              <!-- 👉 Account Settings Section -->
              <VCol cols="12">
                <h6 class="text-h6 mb-3">Account Settings</h6>
              </VCol>

              <!-- 👉 Role -->
              <VCol cols="12" md="6">
                <AppSelect
                  v-model="role"
                  label="Role"
                  placeholder="Select Role"
                  :rules="[requiredValidator]"
                  :items="dropdownOptions.roles"
                />
              </VCol>

              <!-- 👉 Plan -->
              <VCol cols="12" md="6">
                <AppSelect
                  v-model="plan"
                  label="Plan"
                  placeholder="Select Plan"
                  :rules="[requiredValidator]"
                  :items="dropdownOptions.plans"
                />
              </VCol>

              <!-- 👉 Status -->
              <VCol cols="12" md="6">
                <AppSelect
                  v-model="status"
                  label="Status"
                  placeholder="Select Status"
                  :rules="[requiredValidator]"
                  :items="dropdownOptions.statuses"
                />
              </VCol>

              <!-- 👉 Submit and Cancel -->
              <VCol cols="12" class="d-flex justify-end gap-4 mt-6">
                <VBtn
                  type="submit"
                  :loading="isLoading || props.loading"
                  :disabled="isLoading || props.loading"
                >
                  Create User
                </VBtn>
                <VBtn
                  type="reset"
                  variant="tonal"
                  color="error"
                  :disabled="isLoading || props.loading"
                  @click="closeNavigationDrawer"
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
