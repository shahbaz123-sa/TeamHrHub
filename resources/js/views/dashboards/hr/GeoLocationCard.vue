<script setup>
import { onMounted, ref } from 'vue'

const isLoading = ref(false)
const allowRemote = ref(false)

// Fetch branch allow_remote status (replace with your actual API/logic)
const fetchAllowRemote = async () => {
  isLoading.value = true
  try {
    const response = await $api("/branches");
    allowRemote.value = response.data.some(branch => branch.allow_remote)
  } catch(error) {
    console.log(error)
    $toast.error("Something went wrong")
  } finally {
    isLoading.value = false
  }
}

const setAllowRemote = async (val) => {
  isLoading.value = true
  try {
    const url = val ? "branches/allow-remote" : "branches/disallow-remote"
    const response = await $api(url, {
      method: "POST",
      headers: {
        Authorization: `Bearer ${useCookie('accessToken').value}`
      }
    })

    $toast.success(response?.message || "Settings updated successfully")
    allowRemote.value = val
  } catch(error) {
    console.log(error)
    $toast.error("Something went wrong")
  } finally {
    isLoading.value = false
  }
}

onMounted(fetchAllowRemote)
</script>
<template>
  <VCard :height="180">
    <VCardItem class="pb-3 border-b-sm">
      <h3>Geo Location</h3>
    </VCardItem>
    <VSpacer class="mt-13" />
    <VCardText class="pt-1 d-flex align-center justify-space-between">
      <VBtn
        class="mb-4"
        size="small"
        color="primary"
        :disabled="allowRemote || isLoading"
        @click="setAllowRemote(true)"
      >On Location
      </VBtn>
      <VBtn
        class="mb-4"
        size="small"
        color="error"
        :disabled="!allowRemote || isLoading"
        @click="setAllowRemote(false)"
      >Off Location
      </VBtn>
    </VCardText>
  </VCard>
</template>
