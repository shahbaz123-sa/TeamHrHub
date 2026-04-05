<script setup>
import { useGenerateImageVariant } from '@core/composable/useGenerateImageVariant'
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'
import authV2ForgotPasswordIllustrationDark from '@images/pages/auth-v2-forgot-password-illustration-dark.png'
import authV2ForgotPasswordIllustrationLight from '@images/pages/auth-v2-forgot-password-illustration-light.png'
import authV2MaskDark from '@images/pages/misc-mask-dark.png'
import authV2MaskLight from '@images/pages/misc-mask-light.png'

const router = useRouter()
const route = useRoute()
const isPasswordVisible = ref(false)
const isConfirmPasswordVisible = ref(false)
const loading = ref(false)
const message = ref('')
const messageType = ref('')
const tokenValid = ref(true)

const form = ref({
  password: '',
  password_confirmation: '',
})

const authThemeImg = useGenerateImageVariant(authV2ForgotPasswordIllustrationLight, authV2ForgotPasswordIllustrationDark)
const authThemeMask = useGenerateImageVariant(authV2MaskLight, authV2MaskDark)

definePage({
  meta: {
    layout: 'blank',
    unauthenticatedOnly: true,
  },
})

onMounted(() => {
  // Verify token is present
  if (!route.query.token || !route.query.email) {
    tokenValid.value = false
    message.value = 'This password reset link is invalid or has expired. Please request a new one.'
    messageType.value = 'error'
  }
})

const handleResetPassword = async () => {
  if (!form.value.password) {
    message.value = 'Please enter a new password'
    messageType.value = 'error'
    return
  }

  if (form.value.password.length < 8) {
    message.value = 'Password must be at least 8 characters'
    messageType.value = 'error'
    return
  }

  if (form.value.password !== form.value.password_confirmation) {
    message.value = 'Passwords do not match'
    messageType.value = 'error'
    return
  }

  loading.value = true
  message.value = ''
  messageType.value = ''

  try {
    const response = await $api('/api/user/reset-password', {
      method: 'POST',
      body: {
        token: route.query.token,
        email: route.query.email,
        password: form.value.password,
        password_confirmation: form.value.password_confirmation,
      },
    })

    message.value = response.message || 'Your password has been reset successfully'
    messageType.value = 'success'

    // Reset form
    form.value = {
      password: '',
      password_confirmation: '',
    }

    // Redirect to login after 2 seconds
    setTimeout(() => {
      router.push({ name: 'login' })
    }, 2000)
  } catch (error) {

    if(!isNullOrUndefined(error?._data?.errors)) {
      const validationErrors = Object.values(error._data.errors).flat().join("\n")
      message.value = validationErrors
      messageType.value = 'error'
      loading.value = false
      return; 
    }

    const errorMessage = error?.data?.message || error?.message || 'Failed to reset password. Please try again or request a new reset link.'
    message.value = errorMessage
    messageType.value = 'error'
    tokenValid.value = false
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <RouterLink to="/">
    <div class="auth-logo d-flex align-center gap-x-3">
      <VNodeRenderer :nodes="themeConfig.app.logo" />
    </div>
  </RouterLink>

  <VRow
    class="auth-wrapper bg-surface"
    no-gutters
  >
    <VCol
      md="8"
      class="d-none d-md-flex"
    >
      <div class="position-relative bg-background w-100 me-0">
        <div
          class="d-flex align-center justify-center w-100 h-100"
          style="padding-inline: 150px;"
        >
          <VImg
            max-width="468"
            :src="authThemeImg"
            class="auth-illustration mt-16 mb-2"
          />
        </div>

        <img
          class="auth-footer-mask"
          :src="authThemeMask"
          alt="auth-footer-mask"
          height="280"
          width="100"
        >
      </div>
    </VCol>

    <VCol
      cols="12"
      md="4"
      class="d-flex align-center justify-center"
    >
      <VCard
        flat
        :max-width="500"
        class="mt-12 mt-sm-0 pa-4"
      >
        <VCardText>
          <h4 class="text-h4 mb-1">
            Reset Password 🔑
          </h4>
          <p class="mb-0">
            Enter your new password below
          </p>
        </VCardText>

        <VCardText>
          <!-- Alert Messages -->
          <VAlert
            v-if="message"
            :type="messageType"
            class="mb-4"
            closable
          >
            {{ message }}
          </VAlert>

          <VForm v-if="tokenValid" @submit.prevent="handleResetPassword">
            <VRow>
              <!-- New Password -->
              <VCol cols="12">
                <AppTextField
                  v-model="form.password"
                  label="New Password"
                  placeholder="Enter your new password"
                  :type="isPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  @click:append-inner="isPasswordVisible = !isPasswordVisible"
                  :disabled="loading"
                />
              </VCol>

              <!-- Confirm Password -->
              <VCol cols="12">
                <AppTextField
                  v-model="form.password_confirmation"
                  label="Confirm Password"
                  placeholder="Confirm your new password"
                  :type="isConfirmPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isConfirmPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  @click:append-inner="isConfirmPasswordVisible = !isConfirmPasswordVisible"
                  :disabled="loading"
                />
              </VCol>

              <!-- Reset Password Button -->
              <VCol cols="12">
                <VBtn
                  block
                  type="submit"
                  :loading="loading"
                  :disabled="loading"
                >
                  Reset Password
                </VBtn>
              </VCol>

              <!-- back to login -->
              <VCol cols="12">
                <RouterLink
                  class="d-flex align-center justify-center"
                  :to="{ name: 'login' }"
                >
                  <VIcon
                    icon="tabler-chevron-left"
                    size="20"
                    class="me-1 flip-in-rtl"
                  />
                  <span>Back to login</span>
                </RouterLink>
              </VCol>
            </VRow>
          </VForm>

          <!-- Invalid Token - Show Back to Login -->
          <div v-else>
            <VBtn
              block
              color="primary"
              :to="{ name: 'login' }"
            >
              Back to Login
            </VBtn>
          </div>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<style lang="scss">
@use "@core-scss/template/pages/page-auth";
</style>
