<script setup>
import { isNullOrUndefined } from '@/@core/utils/helpers'
import { useGenerateImageVariant } from '@core/composable/useGenerateImageVariant'
import authV2ForgotPasswordIllustrationDark from '@images/pages/auth-v2-forgot-password-illustration-dark.png'
import authV2ForgotPasswordIllustrationLight from '@images/pages/auth-v2-forgot-password-illustration-light.png'
import authV2MaskDark from '@images/pages/misc-mask-dark.png'
import authV2MaskLight from '@images/pages/misc-mask-light.png'
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'

const router = useRouter()
const email = ref('')
const loading = ref(false)
const message = ref('')
const messageType = ref('') // 'success' or 'error'
const authThemeImg = useGenerateImageVariant(authV2ForgotPasswordIllustrationLight, authV2ForgotPasswordIllustrationDark)
const authThemeMask = useGenerateImageVariant(authV2MaskLight, authV2MaskDark)

definePage({
  meta: {
    layout: 'blank',
    unauthenticatedOnly: true,
  },
})

const handleForgotPassword = async () => {
  if (!email.value) {
    message.value = 'Please enter your email address'
    messageType.value = 'error'
    return
  }

  loading.value = true
  message.value = ''
  messageType.value = ''

  try {
    const response = await $api('/api/user/forgot-password', {
      method: 'POST',
      body: {
        email: email.value,
      },
    })

    message.value = response.message || 'Password reset link has been sent to your email'
    messageType.value = 'success'
    
    // Clear email field
    email.value = ''
    
    // Redirect to login after 3 seconds
    setTimeout(() => {
      router.push({ name: 'login' })
    }, 3000)
  } catch (error) {

    if(!isNullOrUndefined(error?._data?.errors)) {
      const validationErrors = Object.values(error._data.errors).flat().join("\n")
      message.value = validationErrors
      messageType.value = 'error'
      loading.value = false
      return; 
    }

    const errorMessage = error?.data?.message || error?.message || 'Failed to send reset link. Please try again.'
    message.value = errorMessage
    messageType.value = 'error'
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
            Forgot Password? 🔒
          </h4>
          <p class="mb-0">
            Enter your email and we'll send you instructions to reset your password
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

          <VForm @submit.prevent="handleForgotPassword">
            <VRow>
              <!-- email -->
              <VCol cols="12">
                <AppTextField
                  v-model="email"
                  autofocus
                  label="Email"
                  type="email"
                  placeholder="johndoe@email.com"
                  :disabled="loading"
                />
              </VCol>

              <!-- Reset link -->
              <VCol cols="12">
                <VBtn
                  block
                  type="submit"
                  :loading="loading"
                  :disabled="loading"
                >
                  Send Reset Link
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
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<style lang="scss">
@use "@core-scss/template/pages/page-auth";
</style>
