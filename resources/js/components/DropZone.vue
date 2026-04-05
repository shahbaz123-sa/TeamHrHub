<script setup>
import {
  useDropZone,
  useFileDialog,
  useObjectUrl,
} from '@vueuse/core'

defineOptions({
  name: 'DropZone',
  inheritAttrs: false,
})

const attrs = useAttrs()

const props = defineProps({
  existingImages: {
    type: Array,
    default: [],
  },
  max: {
    default: null
  }
})

const dropZoneRef = ref()
const fileData = ref([])

const { open, onChange } = useFileDialog({ accept: 'image/*' })

function canAddMore(newFilesCount = 1) {
  const total = props.existingImages.length + fileData.value.length + newFilesCount
  if (props.max && total > props.max) {
    $toast.error(`You can upload a maximum of ${props.max} images.`)
    return false
  }
  return true
}

function onDrop(DroppedFiles) {
  
  if (!canAddMore(selectedFiles.length)) return
  
  DroppedFiles?.forEach(file => {
    if (file.type.slice(0, 6) !== 'image/') {

      // eslint-disable-next-line no-alert
      alert('Only image files are allowed')
      
      return
    }
    fileData.value.push({
      file,
      url: useObjectUrl(file).value ?? '',
    })
  })
}

const emit = defineEmits(['removeExistingImage'])
function removeExistingImage(index, image) {
  emit('removeExistingImage', index, image)
}

onChange(selectedFiles => {
  if (!selectedFiles) return
  if (!canAddMore(selectedFiles.length)) return
  for (const file of selectedFiles) {
    fileData.value.push({
      file,
      url: useObjectUrl(file).value ?? '',
    })
  }
})

useDropZone(dropZoneRef, onDrop)

defineExpose({
  fileData,
})


const isReadonly = computed(() => attrs.readonly === '' || attrs.readonly === true)
const isDisabled = computed(() => attrs.disabled === '' || attrs.disabled === true)
</script>

<template>
  <div class="flex">
    <div class="w-full h-auto relative">
      <div
        ref="dropZoneRef"
        class="cursor-pointer"
        @click="isReadonly || isDisabled ? undefined : open()"
      >
        <div
          v-if="fileData.length === 0 && props.existingImages.length === 0 && !isReadonly && !isDisabled"
          class="d-flex flex-column justify-center align-center gap-y-2 pa-12 drop-zone rounded"
        >
          <IconBtn
            variant="tonal"
            class="rounded-sm"
          >
            <VIcon icon="tabler-upload" />
          </IconBtn>
          <h4 class="text-h4">
            Drag and drop your image here.
          </h4>
          <span class="text-disabled">or</span>

          <VBtn
            variant="tonal"
            size="small"
          >
            Browse Images
          </VBtn>
        </div>

        <div
          v-else
          class="d-flex justify-center align-center gap-3 pa-8 drop-zone flex-wrap"
        >
          <VRow class="match-height w-100">
            <template
              v-for="(item, index) in props.existingImages"
              :key="index"
            >
              <VCol
                cols="12"
                sm="4"
              >
                <VCard :ripple="false">
                  <VCardText
                    class="d-flex flex-column"
                    @click.stop
                  >
                    <VImg
                      :src="item?.src ?? item"
                      width="200px"
                      height="150px"
                      class="w-100 mx-auto"
                    />
                  </VCardText>
                  <VCardActions>
                    <VBtn
                      v-if="!isReadonly && !isDisabled"
                      variant="text"
                      block
                      @click.stop="removeExistingImage(index, item)"
                    >
                      Remove File
                    </VBtn>
                  </VCardActions>
                </VCard>
              </VCol>
            </template>
            <template
              v-for="(item, index) in fileData"
              :key="index"
            >
              <VCol
                cols="12"
                sm="4"
              >
                <VCard :ripple="false">
                  <VCardText
                    class="d-flex flex-column"
                    @click.stop
                  >
                    <VImg
                      :src="item.url"
                      width="200px"
                      height="150px"
                      class="w-100 mx-auto"
                    />
                    <div class="mt-2">
                      <span class="clamp-text text-wrap">
                        {{ item.file.name }}
                      </span>
                      <span>
                        {{ item.file.size / 1000 }} KB
                      </span>
                    </div>
                  </VCardText>
                  <VCardActions>
                    <VBtn
                      variant="text"
                      block
                      @click.stop="fileData.splice(index, 1)"
                    >
                      Remove File
                    </VBtn>
                  </VCardActions>
                </VCard>
              </VCol>
            </template>
          </VRow>
        </div>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.drop-zone {
  border: 1px dashed rgba(var(--v-theme-on-surface), var(--v-border-opacity));
}
</style>
