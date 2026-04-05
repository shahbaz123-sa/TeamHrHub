<template>
  <div class="image-card">
    <div class="preview">
      <VImg
        v-if="url"
        :src="url"
      ></VImg>
    </div>

    <div class="info">
      <span class="name">{{ fileName }}</span>
    </div>
  </div>
</template>

<script>
export default {
  name: "PdfPreview",

  props: {
    url: {
      type: String,
      required: true
    }
  },

  computed: {
    fileName() {
      return this.url.split("/").pop();
    }
  },
};
</script>

<style scoped>
.image-card {
  background-color: black;
  cursor: pointer;
  inline-size: 200px;
  padding-block-start: 2px;
}

.preview {
  padding: 4px;
  block-size: 100px;
}

.preview img {
  position: unset;
  border: none;
  block-size: 200px;
  inline-size: 140px;
  pointer-events: none;
  transform: scale(1.4);
  transform-origin: top left;
}

.info {
  display: flex;
  align-items: center;
  gap: 6px;
  opacity: 0.7;
  padding-block: 8px;
  padding-inline: 10px;
}

.name {
  overflow: hidden;
  color: white;
  font-size: 13px;
}

</style>
