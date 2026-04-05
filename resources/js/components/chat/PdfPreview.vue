<template>
  <div class="pdf-card">
    <div class="preview">
      <iframe
        v-if="url"
        :src="iframeSrc"
        frameborder="0"
        scrolling="no"
        allowfullscreen="false"
      ></iframe>
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
    iframeSrc() {
      return this.url + "#page=1&zoom=page-width";
    },

    fileName() {
      return this.url.split("/").pop();
    }
  },
};
</script>

<style scoped>
.pdf-card {
  overflow: hidden;

  /* border: 1px solid #ddd; */

  /* background: white; */
  background-color: black;

  /* block-size: 100px; */
  cursor: pointer;
  inline-size: 200px;
  padding-block-start: 2px;
}

.preview {
  position: relative;
  overflow: hidden;
  block-size: 100px;
  inline-size: 200px;
}

.preview iframe {
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

  /* text-overflow: ellipsis;
  white-space: nowrap; */
}

</style>
