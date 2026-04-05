<!-- ❗Errors in the form are set on line 60 -->
<script setup>
import { useSocket } from "@/plugins/socket";
import { VNodeRenderer } from "@layouts/components/VNodeRenderer";
import { themeConfig } from "@themeConfig";
import { onMounted } from "vue";
import { VForm } from "vuetify/components/VForm";

definePage({
  meta: {
    layout: "blank",
    unauthenticatedOnly: true,
  },
});

const isPasswordVisible = ref(false);
const route = useRoute();
const ability = useAbility();

const loading = ref(false);

const errors = ref({
  email: undefined,
  password: undefined,
});

const refVForm = ref();

const credentials = ref({
  email: "",
  password: "",
  latitude: null,
  longitude: null,
  remember: false,
});

const login = async () => {
  loading.value = true;

  // Get user's location
  if (navigator.geolocation) {
    try {
      const position = await new Promise((resolve, reject) => {
        navigator.geolocation.getCurrentPosition(resolve, reject);
      });
      credentials.value.latitude = position.coords.latitude;
      credentials.value.longitude = position.coords.longitude;
    } catch (error) {
      console.error("Error getting location:", error);
    }
  }

  try {
    const res = await $api("/user/login", {
      method: "POST",
      body: {
        email: credentials.value.email,
        password: credentials.value.password,
        latitude: credentials.value.latitude,
        longitude: credentials.value.longitude,
      },
      onResponseError({ response }) {
        $toast.error(response._data.message, {
          position: 'bottom-left'
        })
        loading.value = false;
        return
      },
    });

    const { accessToken, userData, userAbilityRules } = res;

    if (credentials.value.remember) {
      useCookie("rememberMe").value = {
        email: credentials.value.email,
        password: credentials.value.password,
      };
    } else {
      const rememberMeCookie = useCookie("rememberMe");
      if (rememberMeCookie.value) {
        rememberMeCookie.value = null;
      }
    }

    useCookie("userAbilityRules").value = userAbilityRules;
    ability.update(userAbilityRules);
    useCookie("userData").value = userData;
    useCookie("accessToken").value = accessToken;

    const permissions = await $api("/api/users/logged-in/permissions", {
      method: "GET",
      headers: {
        Authorization: `Bearer ${accessToken}`
      }
    });

    localStorage.setItem("loggedInUserPermission", JSON.stringify(permissions?.data))

    await nextTick()

    // Redirect based on user role
    let defaultDashboard = '/dashboards/hrm'; // Default for admin/manager roles

    // Check if user has only Employee role
    const hasEmployeeDashboardAccess = permissions?.data.includes('employee_dashboard.read');
    const hasHrDashboardAccess = permissions?.data.includes('hr_dashboard.read');
    const hasCeoDashboardAccess = permissions?.data.includes('ceo_dashboard.read');

    if(hasCeoDashboardAccess) {
      defaultDashboard = '/dashboards/hrm'
    }
    else if(hasHrDashboardAccess) {
      defaultDashboard = '/dashboards/hr'
    }
    else if (hasEmployeeDashboardAccess) {
      defaultDashboard = '/dashboards/employee'
    }

    const target = route.query.to ? String(route.query.to) : defaultDashboard;
    window.location.href = target;
  } catch (error) {
    loading.value = false;
    console.error("Login error:", error);
  }
};

const onSubmit = async () => {
  const { valid } = await refVForm.value.validate();
  if (!valid) return;
  login();
};

onMounted(() => {
  useSocket().disconnect();
  // console.log("Socket disconnected.");
  const rememberMeCookie = useCookie("rememberMe");
  if (rememberMeCookie.value) {
    credentials.value.email = rememberMeCookie.value.email;
    credentials.value.password = rememberMeCookie.value.password;
    credentials.value.remember = true;
  }
});
</script>

<template>
  <RouterLink to="/">
    <div class="auth-logo d-flex align-center gap-x-3">
      <VNodeRenderer :nodes="!$vuetify.theme.current.dark ? themeConfig.app.logo : themeConfig.app.logoDark" />
    </div>
  </RouterLink>
  <VRow no-gutters class="auth-wrapper bg-surface">

    <VCol
      cols="12"
      md="6"
      class="auth-card-v2 d-flex align-center justify-center"
    >
      <VCard flat :width="400" class="mt-12 mt-sm-0 pa-4">
        <VCardText>
          <h4 class="text-h4 mb-1">
            Welcome to <span class="text-capitalize">{{ themeConfig.app.title }}</span>! 👋🏻
          </h4>
          <p class="mb-0">
            Access your HRMS account by signing in below.
          </p>
        </VCardText>
        <VCardText>
          <VForm ref="refVForm" @submit.prevent="onSubmit">
            <VRow>
              <!-- email -->
              <VCol cols="12">
                <AppTextField
                  v-model="credentials.email"
                  label="Email"
                  placeholder="johndoe@email.com"
                  type="email"
                  autofocus
                  :rules="[requiredValidator, emailValidator]"
                  :error-messages="errors.email"
                />
              </VCol>

              <!-- password -->
              <VCol cols="12">
                <AppTextField
                  v-model="credentials.password"
                  label="Password"
                  placeholder="********"
                  :rules="[requiredValidator]"
                  :type="isPasswordVisible ? 'text' : 'password'"
                  autocomplete="password"
                  :error-messages="errors.password"
                  :append-inner-icon="
                    isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'
                  "
                  @click:append-inner="isPasswordVisible = !isPasswordVisible"
                />

                <div class="d-flex align-center flex-wrap justify-space-between mb-6">
                  <VCheckbox
                    v-model="credentials.remember"
                    label="Remember me"
                  />
                  <RouterLink
                    class="text-primary mb-1"
                    :to="{ name: 'forgot-password' }"
                  >
                    <small>Forgot Password?</small>
                  </RouterLink>
                </div>
                <VBtn
                  :loading="loading"
                  :disabled="loading"
                  color="primary"
                  type="submit"
                  class="w-100"
                >
                  Login
                  <template #loader>
                    <span class="custom-loader">
                      <VIcon icon="tabler-refresh" />
                    </span>
                  </template>
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>

    <VCol md="6" class="d-none d-md-flex pa-2">
      <div class="position-relative auth-right-background w-100 me-0">
        <div
          class="d-flex align-center justify-center w-100 h-100"
          style="padding-inline: 6.25rem;"
        >
          <VImg
            width="400"
            height="auto"
            src="/images/login/auth.png"
            class="auth-illustration mt-16 mb-2"
          />
        </div>
      </div>
    </VCol>
  </VRow>
</template>

<style lang="scss">
@use "@core-scss/template/pages/page-auth";

.copywrite-text {
  position: absolute;
  inset-block-end: 0;
  inset-inline-start: 14%;
  margin-block-end: 20px;
}

.auth-right-background {
  border-radius: 24px;
  margin: 15px;
  background: url("/images/login/background.png");
  background-position: center;
  background-repeat: no-repeat;
  background-size: cover;
}
</style>
<style scoped>
.custom-loader {
  display: flex;
  animation: loader 1s infinite;
}

@keyframes loader {
  from {
    transform: rotate(0deg);
  }

  to {
    transform: rotate(-360deg);
  }
}
</style>
