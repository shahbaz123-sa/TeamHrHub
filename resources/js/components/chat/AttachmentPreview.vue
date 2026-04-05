
<template>

  <DocumentImageViewer
    v-if="isPdf(props.url)"
    :src="props.url"
  >
    <template #content>
      <PdfPreview :url="props.url" />
    </template>
  </DocumentImageViewer>

  <DocumentImageViewer
    v-else
    :src="props.url"
  >
    <template #content>
      <ImagePreview :url="props.url" />
    </template>
  </DocumentImageViewer>

</template>
<script setup>
import { isPdf } from '@/utils/helpers/file';
import DocumentImageViewer from '../common/DocumentImageViewer.vue';
import PdfPreview from './PdfPreview.vue';
import ImagePreview from './ImagePreview.vue';

const props = defineProps({
  url: { type: String, required: true }
})
</script>
