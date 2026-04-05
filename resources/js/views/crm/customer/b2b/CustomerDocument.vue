<script setup>
import DocumentImageViewer from '@/components/common/DocumentImageViewer.vue'
import { strReplace } from '@/utils/helpers/str'


const props = defineProps({
  customerData: {
    type: null,
    required: true,
  },
})

const emit = defineEmits(['refresh'])

const accessToken = useCookie('accessToken').value

const selectedFile = ref(null)
const uploading = ref(false)
const deleting = ref(null)

const documents = computed(() => (props.customerData?.company?.documents ?? []))

const isUploadDialogOpen = ref(false)
const uploadDocType = ref('')

const openUploadDialog = () => {
  uploadDocType.value = ''
  selectedFile.value = null
  isUploadDialogOpen.value = true
}

const uploadDocument = async () => {
  if (!selectedFile.value) {
    $toast.warning('Please choose a file')
    return
  }

  if (!uploadDocType.value) {
    $toast.warning('Please enter document type')
    return
  }

  const fd = new FormData()
  fd.append('document', selectedFile.value)
  fd.append('document_type', uploadDocType.value)

  uploading.value = true
  try {
    await $api(`/customers/${props.customerData.id}/company/documents`, {
      method: 'POST',
      body: fd,
      headers: { Authorization: `Bearer ${accessToken}` },
    })
    $toast.success('Document uploaded')
    emit('refresh')
    isUploadDialogOpen.value = false
  } catch (e) {
    $toast.error('Failed to upload document')
  } finally {
    uploading.value = false
    selectedFile.value = null
  }
}

const deleteDocument = async (docId) => {

  deleting.value = docId
  try {
    await $api(`/customers/${props.customerData.id}/company/documents/${docId}`, {
      method: 'DELETE',
      headers: { Authorization: `Bearer ${accessToken}` },
    })
    $toast.success('Document deleted')
    emit('refresh')
  } catch (e) {
    $toast.error('Failed to delete document')
  } finally {
    deleting.value = null
  }
}

</script>

<template>

  <!-- 👉 Address Book -->
  <VCard class="mb-6">
    <VCardText>
      <div class="d-flex justify-space-between mb-6 flex-wrap align-center gap-y-4 gap-x-6">
        <h5 class="text-h5">
          Documents
        </h5>
        <div>
          <VBtn
            class="float-right"
            prepend-icon="tabler-plus"
            :loading="uploading"
            @click="openUploadDialog"
          >
            Upload
          </VBtn>
        </div>
      </div>
      
      <!-- Upload Dialog -->
      <VDialog v-model="isUploadDialogOpen" max-width="400">
        <VCard>
          <h3 class="text-center mt-10">Add New Document</h3>
          <VCardText>
            <VRow>
              <VCol cols="12">
                <VLabel text="Select Document" />
                <AppTextField v-model="uploadDocType" />
              </VCol>
              <VCol cols="12">
                <VLabel text="Attach Document" />
                <VFileInput
                  v-model="selectedFile"
                  prepend-icon="tabler-paperclip"
                  clearable
                  density="comfortable"
                  variant="outlined"
                />
              </VCol>
            </VRow>
          </VCardText>
          <VCardActions class="mt-5 mb-5 text-center">
            <VBtn :loading="uploading" variant="elevated" @click="uploadDocument">Upload</VBtn>
            <VBtn variant="text" @click="isUploadDialogOpen = false">Cancel</VBtn>
          </VCardActions>
        </VCard>
      </VDialog>
      <template
        v-for="(document, index) in documents"
        :key="document.id || index"
      >
        <div>
          <div class="d-flex justify-space-between py-3 gap-y-2 flex-wrap align-center">
            <div class="d-flex align-center gap-x-4">
              <div>
                <DocumentImageViewer
                  :src="document.document_url"
                  :type="'button'"
                  :pdf-title="document.document_type"
                  :pdf-icon="'/images/docs.png'"
                  :avatar-size="68"
                />
              </div>
              <div>
                <h4>{{ strReplace(document.document_type, "_", " ") }}</h4>
              </div>
            </div>
            <div>
              <VBtn
                color="error"
                variant="tonal"
                icon="tabler-trash"
                :rounded="false"
                :loading="deleting === document.id"
                @click="deleteDocument(document.id)" />
            </div>
          </div>
        </div>
      </template>
    </VCardText>
  </VCard>
</template>
