<template>
  <span v-if="props.src && isImage()">

    <template v-if="props.type === 'avatar'">
      <VAvatar
        :size="props.avatarSize"
        variant="tonal"
        rounded
        :image="props.src"
        class="cursor-pointer"
        @click="showLightbox = true"
      />
    </template>
    <template v-else>
      <VBtn
        size="small"
        color="primary"
        @click="showLightbox = true"
        v-if="$slots.button || (!$slots.button && !$slots.icon && !$slots.content)"
      >
        <slot name="button">
          View
          <VIcon icon="tabler-arrow-right" />
        </slot>
      </VBtn>

      <VBtn
        size="small"
        icon
        @click="showLightbox = true"
        v-if="$slots.icon"
      >
        <slot name="icon"><VIcon icon="tabler-eye" /></slot>
      </VBtn>

      <div @click="showLightbox = true" style="cursor: pointer;" v-if="$slots.content">
        <slot name="content"></slot>
      </div>

    </template>

  </span>
  <span v-else-if="props.src && isPdf()">
    
    <template v-if="props.type === 'avatar' && props.pdfIcon">
      <VAvatar
        :size="props.avatarSize"
        variant="tonal"
        rounded
        :image="props.pdfIcon"
        class="cursor-pointer"
        @click="showPdfViewer = true"
      />
    </template>
    <template v-else>
      <VBtn
        size="small"
        color="primary"
        @click="showPdfViewer = true"
        v-if="$slots.button || (!$slots.button && !$slots.icon && !$slots.content)"
      >
        <slot name="button">
          View
        <VIcon icon="tabler-arrow-right" />
        </slot>
      </VBtn>

      <VBtn
        size="small"
        icon
        @click="showPdfViewer = true"
        v-if="$slots.icon"
      >
        <slot name="icon"><VIcon icon="tabler-eye" /></slot>
      </VBtn>

      <div @click="showPdfViewer = true" style="cursor: pointer;" v-if="$slots.content">
        <slot name="content"></slot>
      </div>

    </template>
    
  </span>
  <span v-else-if="defaultName">
    <VAvatar
      :size="props.nameAvatarSize"
      variant="tonal"
      rounded
      class="d-flex align-center justify-center"
    >
      {{ defaultName.toUpperCase() }}
    </VAvatar>
  </span>
  <span v-if="!isImage() && !isPdf() && !defaultName">
    <div @click="showLightbox = true" style="cursor: pointer;" v-if="$slots.content">
      <slot name="content"></slot>
    </div>
  </span>

  <vue-easy-lightbox
    v-if="showLightbox"
    :visible="showLightbox"
    :imgs="[props.src]"
    :index="0"
    @hide="showLightbox = false"
    teleport="body"
  />

  <VDialog v-model="showPdfViewer" max-width="900px">
    <VCard>
      <VCardTitle class="d-flex justify-space-between align-center">
        <span>{{ props.pdfTitle }}</span>
        <VBtn icon @click="showPdfViewer = false">
          <VIcon icon="tabler-x" />
        </VBtn>
      </VCardTitle>

      <VCardText style=" padding: 0;block-size: 80vh;">
        <iframe
          v-if="props.src"
          :src="props.src"
          style=" border: none; block-size: 100%;inline-size: 100%;"
        ></iframe>
      </VCardText>
    </VCard>
  </VDialog>

</template>
<script setup>
import VueEasyLightbox from 'vue-easy-lightbox';

const props = defineProps({
  src: { type: String, default: null },
  type: { type: String, default: null },
  avatarSize: { default: 38 },
  pdfTitle: { type: String, default: 'PDF Preview'},
  pdfIcon: { type: String, default: null },
  defaultName: { type: String, default: null },
  nameAvatarSize: { default: 38 },
})

const showLightbox = ref(false)
const showPdfViewer = ref(false)

function isImage() {
  return /\.(jpg|jpeg|png|gif|webp|bmp|svg)$/i.test(props.src)
}

function isPdf() {
  return /\.pdf$/i.test(props.src)
}
</script>
