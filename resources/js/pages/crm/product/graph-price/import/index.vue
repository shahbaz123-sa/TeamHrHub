<script setup>
import { ref } from 'vue'

const selectedFile = ref(null)
const fileInput = ref(null)
const uploading = ref(false)
const report = ref(null)
const errors = ref([])

const accessToken = useCookie('accessToken').value

const onChoose = () => fileInput.value && fileInput.value.click()

const handleFileChange = (e) => {
  const file = e.target.files && e.target.files[0]
  if (!file) return
  selectedFile.value = file
}

const uploadFile = async () => {
  if (!selectedFile.value) {
    $toast.error('Please choose a CSV file first')
    return
  }

  uploading.value = true
  errors.value = []
  report.value = null

  const formData = new FormData()
  formData.append('file', selectedFile.value)

  try {
    const res = await $api('/product/graph-prices/import', {
      method: 'POST',
      body: formData,
      headers: { Authorization: `Bearer ${accessToken}` }
    })
    report.value = res.report || { created: 0, skipped: 0, errors: [] }
    if (Array.isArray(report.value.errors)) {
      errors.value = report.value.errors
    }
    $toast.success(res.message || 'Import completed')
  } catch (err) {
    $toast.error('Import failed')
    if (err && err._data && err._data.message) {
      errors.value = [err._data.message]
    }
  } finally {
    uploading.value = false
    selectedFile.value = null
    if (fileInput.value) fileInput.value.value = null
  }
}
</script>

<template>
  <div>
    <VCard>
      <VCardTitle>Import Product Graph Prices</VCardTitle>
      <VCardText>
            <div class="d-grid gap-4">
              <div class="d-flex gap-4 align-center mb-1">
                <VBtn color="secondary" variant="outlined" @click="onChoose">Choose CSV</VBtn>
                <span v-if="selectedFile">{{ selectedFile.name }}</span>
                <VBtn :loading="uploading" color="primary" @click="uploadFile">Upload</VBtn>
                <VBtn :to="{ name: 'crm-product-graph-price-list' }" variant="tonal" color="secondary">
                  Price List 
                </VBtn>
                <input ref="fileInput" type="file" accept=".csv,text/csv" style="display: none;" @change="handleFileChange" />
              </div>
              <div>
                <small class="text--secondary">Expected CSV headers: DateTime*, Category*, Product*, Brand, Market*, Currency*, Price*, Unit*</small>
              </div>
            </div>
          </VCardText>
    </VCard>

  <div style="margin-block-start: 16px;">
      <VCard v-if="report">
        <VCardTitle>Import Report</VCardTitle>
        <VCardText>
          <div>Created: {{ report.created }}</div>
          <div>Skipped: {{ report.skipped }}</div>
          <div v-if="errors.length">
            <h4>Errors</h4>
            <ul>
              <li v-for="(err, idx) in errors" :key="idx">{{ err }}</li>
            </ul>
          </div>
        </VCardText>
      </VCard>

      <VCard v-else-if="errors.length">
        <VCardTitle>Errors</VCardTitle>
        <VCardText>
          <ul>
            <li v-for="(err, idx) in errors" :key="idx">{{ err }}</li>
          </ul>
        </VCardText>
      </VCard>
    </div>
  </div>
</template>
