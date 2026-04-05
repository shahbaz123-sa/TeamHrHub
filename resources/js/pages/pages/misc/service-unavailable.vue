<script setup>
import { useGenerateImageVariant } from '@core/composable/useGenerateImageVariant'
import miscMaskDark from '@images/pages/misc-mask-dark.png'
import miscMaskLight from '@images/pages/misc-mask-light.png'
import miscServiceUnavailable from '@images/pages/misc-under-maintenance.png'

const themeMask = useGenerateImageVariant(miscMaskLight, miscMaskDark)

const retryRequest = () => {
  if (typeof window !== 'undefined') {
    window.location.reload()
  }
}

definePage({
  meta: {
    layout: 'blank',
    public: true,
  },
})
</script>

<template>
  <div class="misc-wrapper">
    <div class="text-center mb-15">
      <h4 class="text-h4 font-weight-medium mb-2">
        503 | Service Unavailable
      </h4>
      <p class="text-body-1 mb-6">
        We are scaling infrastructure right now. Your workspace will be back in just a moment.
      </p>

      <div class="d-flex flex-wrap justify-center gap-4">
        <VBtn color="primary" @click="retryRequest">
          Retry Now
        </VBtn>
        <VBtn variant="tonal" to="/">
          Back to Home
        </VBtn>
      </div>
    </div>

    <div class="misc-avatar w-100 text-center">
      <VImg
        :src="miscServiceUnavailable"
        alt="Service Unavailable"
        :max-width="550"
        :min-height="300"
        class="mx-auto"
      />
    </div>

    <img
      class="misc-footer-img d-none d-md-block"
      :src="themeMask"
      alt="misc-footer-mask"
      height="320"
    >
  </div>
</template>

<style lang="scss" scoped>
@use "@core-scss/template/pages/misc.scss";
</style>

