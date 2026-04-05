<script setup>
import { defineEmits, defineProps, ref } from "vue";
import { PerfectScrollbar } from "vue3-perfect-scrollbar";

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },
  branch: {
    type: Object,
    default: null,
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(["update:isDrawerOpen", "submit"]);
const accessToken = useCookie("accessToken");

// Form state
const isFormValid = ref(false);
const refForm = ref();
const isLoading = ref(false);

const form = ref({
  id: null,
  name: "",
  address: "",
  phone: "",
  email: "",
  grace_period: "",
  attendance_radius: "",
  latitude: "",
  longitude: "",
  office_start_time: "",
  office_end_time: "",
  allow_remote: false,
  time_deviations: [
    { day: 'Monday', check_in_deviation: 0, check_out_deviation: 0 },
    { day: 'Tuesday', check_in_deviation: 0, check_out_deviation: 0 },
    { day: 'Wednesday', check_in_deviation: 0, check_out_deviation: 0 },
    { day: 'Thursday', check_in_deviation: 0, check_out_deviation: 0 },
    { day: 'Friday', check_in_deviation: 0, check_out_deviation: 0 },
    { day: 'Saturday', check_in_deviation: 0, check_out_deviation: 0 },
    { day: 'Sunday', check_in_deviation: 0, check_out_deviation: 0 },
  ],
});

// Reset form when opening/closing drawer
watch(
  () => props.isDrawerOpen,
  (val) => {
    refForm.value?.reset();
    refForm.value?.resetValidation();
    form.value = props.branch
      ? {
          ...props.branch,
          time_deviations: props.branch.time_deviations || [
            { day: 'Monday', check_in_deviation: 0, check_out_deviation: 0 },
            { day: 'Tuesday', check_in_deviation: 0, check_out_deviation: 0 },
            { day: 'Wednesday', check_in_deviation: 0, check_out_deviation: 0 },
            { day: 'Thursday', check_in_deviation: 0, check_out_deviation: 0 },
            { day: 'Friday', check_in_deviation: 0, check_out_deviation: 0 },
            { day: 'Saturday', check_in_deviation: 0, check_out_deviation: 0 },
            { day: 'Sunday', check_in_deviation: 0, check_out_deviation: 0 },
          ],
        }
      : {
          id: null,
          name: "",
          address: "",
          phone: "",
          email: "",
          time_deviations: [
            { day: 'Monday', check_in_deviation: 0, check_out_deviation: 0 },
            { day: 'Tuesday', check_in_deviation: 0, check_out_deviation: 0 },
            { day: 'Wednesday', check_in_deviation: 0, check_out_deviation: 0 },
            { day: 'Thursday', check_in_deviation: 0, check_out_deviation: 0 },
            { day: 'Friday', check_in_deviation: 0, check_out_deviation: 0 },
            { day: 'Saturday', check_in_deviation: 0, check_out_deviation: 0 },
            { day: 'Sunday', check_in_deviation: 0, check_out_deviation: 0 },
          ],
        };
  }
);

const closeForm = () => {
  emit("update:isDrawerOpen", false);
  refForm.value?.reset();
  refForm.value?.resetValidation();
};

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      const formData = {...form.value};
      if (form.value.id) {
        formData.id = form.value.id;
      }
      emit("submit", formData);
    }
  });
};

const handleDrawerModelValueUpdate = (val) => {
  emit("update:isDrawerOpen", val);
};
</script>

<template>
  <VNavigationDrawer
    temporary
    :width="400"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <AppDrawerHeaderSection
      :title="form.id ? 'Update' : 'Add New'"
      @cancel="closeForm"
    />

    <VDivider />

    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat>
        <VCardText>
          <VForm ref="refForm" v-model="isFormValid" @submit.prevent="onSubmit">
            <VRow>
              <!-- Name Field -->
              <VCol cols="12">
                <AppTextField
                  v-model="form.name"
                  :rules="[requiredValidator]"
                  label="Branch Name"
                  placeholder="Enter branch name"
                />
              </VCol>

              <!-- Phone Field -->
              <VCol cols="12">
                <AppTextField
                  v-model="form.phone"
                  label="Phone Number"
                  placeholder="Enter phone number"
                  :rules="[
                    requiredValidator,
                    (v) => {
                      const pattern = /^[0-9\+\-\s]+$/;
                      return (
                        !v ||
                        pattern.test(v) ||
                        'Only numbers, +, - and spaces are allowed'
                      );
                    },
                  ]"
                />
              </VCol>

              <!-- Email Field -->
              <VCol cols="12">
                <AppTextField
                  v-model="form.email"
                  label="Email"
                  placeholder="Enter email address"
                  :rules="[
                    (v) => {
                      const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                      return (
                        !v ||
                        pattern.test(v) ||
                        'Please enter a valid email address'
                      );
                    },
                  ]"
                />
              </VCol>

              <VCol cols="12">
                <AppTextField
                  v-model="form.office_start_time"
                  label="Office Start Time"
                  placeholder="e.g. 09:00"
                  type="time"
                />
              </VCol>

              <VCol cols="12">
                <AppTextField
                  v-model="form.office_end_time"
                  label="Office End Time"
                  placeholder="e.g. 18:00"
                  type="time"
                />
              </VCol>

              <VCol cols="12">
                <AppTextField
                  v-model="form.grace_period"
                  label="Grace Period (minutes)"
                  placeholder="Enter grace period"
                  type="number"
                  step="any"
                />
              </VCol>

              <VCol cols="12">
                <VCheckbox
                  v-model="form.allow_remote"
                  label="Allow Remote Attendance"
                />
              </VCol>
              
              <VCol cols="12">
                <AppTextField
                  v-model="form.attendance_radius"
                  label="Attendance Radius (meters)"
                  placeholder="Enter attendance radius"
                  type="number"
                  step="any"
                />
              </VCol>
              
              <VCol cols="12">
                <AppTextField
                  v-model="form.latitude"
                  label="Latitude"
                  placeholder="Enter latitude"
                  type="number"
                  step="any"
                />
              </VCol>
              
              <VCol cols="12">
                <AppTextField
                  v-model="form.longitude"
                  label="Longitude"
                  placeholder="Enter longitude"
                  type="number"
                  step="any"
                />
              </VCol>

              <!-- Time Deviations -->
              <VCol cols="12">
                <VRow v-for="(deviation, index) in form.time_deviations" :key="index">
                  <VCol cols="12" class="pb-0">
                    <VDivider>
                      {{ deviation.day }}
                    </VDivider>
                  </VCol>
                  <VCol cols="6">
                    <AppTextField
                      v-model="deviation.check_in_deviation"
                      label="Check-in Deviation (mins)"
                      type="number"
                    />
                  </VCol>
                  <VCol cols="6">
                    <AppTextField
                      v-model="deviation.check_out_deviation"
                      label="Check-out Deviation (mins)"
                      type="number"
                    />
                  </VCol>
                </VRow>
              </VCol>

              <!-- Address Field -->
              <VCol cols="12">
                <AppTextarea
                  v-model="form.address"
                  label="Address"
                  placeholder="Enter branch address"
                  rows="2"
                />
              </VCol>             

              <!-- Submit and Cancel Buttons -->
              <VCol cols="12" class="d-flex justify-end gap-4 mt-6">
                <VBtn type="submit" :loading="isLoading || props.loading">
                  Save
                </VBtn>
                <VBtn
                  type="reset"
                  variant="tonal"
                  color="error"
                  @click="closeForm"
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
