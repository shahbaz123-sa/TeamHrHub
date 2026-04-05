<script setup>
import { $toast } from '@/utils/toast'
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const accessToken = useCookie('accessToken').value

const file = ref(null)
const priceDate = ref(new Date().toISOString().split('T')[0])
const uploading = ref(false)
const report = ref(null)
const errors = ref([])

const isAllowedFileType = (file) => {
  const allowedExtensions = ['csv', 'xlsx'];
  const extension = file.name.split('.').pop().toLowerCase();
  return allowedExtensions.includes(extension);
}

const handleFileChange = e => {
  const uploadedfile = e.target.files[0]
  if (!uploadedfile) return

  if (!isAllowedFileType(uploadedfile)) {
    $toast.error('Only CSV or XLSX files are allowed.');
    e.target.value = '';
    return;
  }

  file.value = uploadedfile
}

const handleUpload = async () => {

  if (!file.value) {
    $toast.error('Please choose a CSV file first')
    return
  }
  else if (!isAllowedFileType(file.value)) {
    $toast.error('Only CSV or XLSX files are allowed.');
    return;
  }

  uploading.value = true
  errors.value = []
  report.value = null

  try {
    const formData = new FormData()
    formData.append('file', file.value)
    formData.append('price_date', priceDate.value)
    
    const response = await $api('/product/daily-prices/import', {
      method: 'POST',
      body: formData,
      headers: { Authorization: `Bearer ${accessToken}` }
    })

    report.value = response.report || { created: 0, skipped: 0, errors: [] }
    if (Array.isArray(report.value.errors)) {
      errors.value = report.value.errors
    }
    $toast.success(response.message || 'Import completed')

    setTimeout(() => {
      router.push({ name: 'crm-product-daily-price-list' })
    }, 2000)
  } catch (err) {
    $toast.error('Import failed')
    if (err && err._data && err._data.message) {
      errors.value = [err._data.message]
    }
  } finally {
    uploading.value = false
    file.value = null
  }
}
</script>

<template>
  <div>
    <VCard class="mx-auto" max-width="600">
      <VCardTitle>Import Daily Prices</VCardTitle>
      <VCardText>
        <VLabel size="small" class="mb-1">
          Price Date
        </VLabel>
        <AppDateTimePicker
          v-model="priceDate"
          clearable
          clear-icon="tabler-x"
          class="mb-3"
          prepend-icon="tabler-calendar"
        />
        <VLabel size="small" class="mb-1">
          Choose File
        </VLabel>
        <VFileInput
          label="Select File"
          accept=".csv,.xlsx"
          v-model="file"
          @change="handleFileChange"
        />
        <div class="mt-4">
          <VBtn :disabled="!file" color="primary" @click="handleUpload" :loading="uploading">
            Upload
          </VBtn>
        </div>
      </VCardText>
    </VCard>
  </div>
</template>
