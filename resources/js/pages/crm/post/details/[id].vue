<script setup>
import { usePostApi } from '@/composables/usePostApi'
import { router } from '@/plugins/1.router'
import { humanize } from '@/utils/helpers/str'
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'

const { getPost } = usePostApi()

const route = useRoute()

const loading = ref(false)
const post = ref(null)
const deleting = ref(false)

const loadPost = async () => {
  loading.value = true
  try {
    const postData = await getPost(route.params.id)
    post.value = postData
  } catch (error) {
    $toast.error('Failed to load post')
    router.push({ name: 'crm-post-list' })
  } finally {
    loading.value = false
  }
}

const resolveStatus = statusMsg => {
  const statusMap = {
    publish: { text: 'Publish', color: 'success' },
    draft: { text: 'Draft', color: 'warning' },
    inactive: { text: 'Inactive', color: 'error' },
  }
  return statusMap[statusMsg] || { text: statusMsg, color: 'secondary' }
}

onMounted(() => {
  loadPost()
})
</script>

<template>
  <div v-if="!loading">
    <!-- Header -->
    <VCard v-if="post">
      <VCardText>
        <VRow>
          <VCol cols="12" sm="8">
            <h1 class="text-h4 font-weight-bold mb-2">{{ post.title }}</h1>
            <p v-if="post.subtitle" class="text-subtitle1 text-grey">
              {{ post.subtitle }}
            </p>
          </VCol>
          <VCol cols="12" sm="4" class="text-right">
            <VChip label :color="resolveStatus(post.status).color">
              {{ resolveStatus(post.status).text }}
            </VChip>
          </VCol>
        </VRow>
        <div class="mb-10">
          <VImg :src="post.header_image" />
        </div>
        <div class="post-content prose" v-html="post.content" />
      </VCardText>
    </VCard>

    <VCard v-if="post">
      <VCardText>
        <VRow>
          <VCol cols="12" sm="6">
            <strong>Created At:</strong>
            <p>{{ post.published_at }}</p>
          </VCol>
          <VCol cols="12" sm="6">
            <strong>Written By:</strong>
            <p>{{ post.author_name || post?.author?.name }}</p>
          </VCol>
        </VRow>
        <VRow>
          <VCol cols="12" sm="6">
            <strong>Post Slug:</strong>
            <p>{{ post.slug }}</p>
          </VCol>
          <VCol cols="12" sm="6">
            <strong>Read Time:</strong>
            <p>{{ post.read_time || 'N/A' }}</p>
          </VCol>
        </VRow>
        <VRow>
          <VCol cols="12" sm="6">
            <strong>Post Type:</strong>
            <p>{{ humanize(post.post_type) }}</p>
          </VCol>
          <VCol cols="12" sm="6">
            <strong>Press Release Link:</strong>
            <p>{{ post.press_release_link || 'N/A' }}</p>
          </VCol>
        </VRow>
        <VRow>
          <VCol cols="12" sm="6">
            <strong>Publish Date: </strong>
            <p>{{ post?.published_at }}</p>
          </VCol>
          <VCol cols="12" sm="6">
            <strong>Thumbnail: </strong>
            <DocumentImageViewer v-if="post.thumbnail" :type="'avatar'" :src="post?.thumbnail" />
          </VCol>
        </VRow>
        <VRow v-if="post.tags && post.tags.length > 0" class="mt-4">
          <VCol cols="12">
            <strong>Tags:</strong>
            <div class="mt-2">
              <VChip
                v-for="tag in post.tags"
                :key="tag.id"
                label
                color="info"
                variant="outlined"
                class="me-2 mb-2"
              >
                {{ tag.title }}
              </VChip>
            </div>
          </VCol>
        </VRow>
        <VRow v-if="post.categories && post.categories.length > 0" class="mt-4">
          <VCol cols="12">
            <strong>Categories:</strong>
            <div class="mt-2">
              <VChip
                v-for="category in post.categories"
                :key="category.id"
                label
                color="secondary"
                variant="outlined"
                class="me-2 mb-2"
              >
                {{ category.title }}
              </VChip>
            </div>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>
  </div>

  <!-- Loading State -->
  <VCard v-else>
    <VCardText class="text-center py-10">
      <VProgressCircular indeterminate color="primary" />
    </VCardText>
  </VCard>
</template>

<style lang="scss">
@import "@styles/tiptap/table";
</style>
<style scoped>
.post-content {
  color: rgba(0, 0, 0, 87%);
  font-size: 1rem;
  line-height: 1.8;
}

.post-content ::v-deep(h1),
.post-content ::v-deep(h2),
.post-content ::v-deep(h3),
.post-content ::v-deep(h4),
.post-content ::v-deep(h5),
.post-content ::v-deep(h6) {
  font-weight: 600;
  margin-block: 1.5rem 0.5rem;
}

.post-content ::v-deep(p) {
  margin-block-end: 1rem;
}

.post-content ::v-deep(ul),
.post-content ::v-deep(ol) {
  margin-block-end: 1rem;
  margin-inline-start: 1.5rem;
}

.post-content ::v-deep(li) {
  margin-block-end: 0.5rem;
}

.post-content ::v-deep(blockquote) {
  border-inline-start: 4px solid #1976d2;
  color: rgba(0, 0, 0, 60%);
  font-style: italic;
  margin-block: 1rem;
  padding-inline-start: 1rem;
}

.post-content ::v-deep(code) {
  border-radius: 3px;
  background-color: #f5f5f5;
  font-family: "Courier New", monospace;
  padding-block: 0.2rem;
  padding-inline: 0.4rem;
}

.post-content ::v-deep(pre) {
  border-radius: 4px;
  background-color: #f5f5f5;
  margin-block-end: 1rem;
  overflow-x: auto;
  padding-block: 1rem;
  padding-inline: 1rem;
}

.post-content ::v-deep(img) {
  border-radius: 4px;
  block-size: auto;
  margin-block: 1rem;
  max-inline-size: 100%;
}
</style>
