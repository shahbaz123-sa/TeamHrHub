<script setup>
import PostContentEditor from '@/components/crm/PostContentEditor.vue'
import DropZone from '@/components/DropZone.vue'
import { usePostApi } from '@/composables/usePostApi'
import { router } from '@/plugins/1.router'
import { onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'

const { getPost, updatePost } = usePostApi()

const route = useRoute()

const statuses = ref([
  {
    title: 'Publish',
    value: 'publish',
  },
  {
    title: 'Draft',
    value: 'draft',
  },
  {
    title: 'Inactive',
    value: 'inactive',
  },
])

const tags = ref([])
const categories = ref([])
const loadingTags = ref(false)
const loadingCategories = ref(false)
const accessToken = useCookie('accessToken').value

const form = ref({
  title: '',
  slug: '',
  subtitle: '',
  header_image: null,
  existing_header_image: [],
  thumbnail: null,
  existing_thumbnail: [],
  additional_images: [],
  content: '',
  status: 'draft',
  post_type: 'news',
  press_release_link: '',
  read_time: '',
  publish_date: '',
  tags: [],
  categories: [],
})

const loading = ref(false)
const post = ref(null)

const fetchTags = async (search = '') => {
  loadingTags.value = true
  try {
    const { data } = await useApi(
      createUrl("/post/tags", {
        query: {
          q: search,
          per_page: -1,
          status: 1,
        }
      }),
      {
        headers: { Authorization: `Bearer ${accessToken}` }
      }
    )
    tags.value = (data?.value?.data ?? [])
  } catch (error) {
    console.error('Failed to fetch tags', error)
  } finally {
    loadingTags.value = false
  }
}

const fetchCategories = async (search = '') => {
  loadingCategories.value = true
  try {
    const { data } = await useApi(
      createUrl("/post/categories", {
        query: {
          q: search,
          per_page: -1,
          status: 1,
        }
      }),
      {
        headers: { Authorization: `Bearer ${accessToken}` }
      }
    )
    categories.value = (data?.value?.data ?? [])
  } catch (error) {
    console.error('Failed to fetch categories', error)
  } finally {
    loadingCategories.value = false
  }
}

const loadPost = async () => {
  loading.value = true
  try {
    const postData = await getPost(route.params.id)
    post.value = postData

    form.value = {
      title: postData.title,
      slug: postData.slug,
      subtitle: postData.subtitle,
      header_image: postData.header_image,
      existing_header_image: postData.header_image ? [postData.header_image] : [],
      thumbnail: postData.thumbnail,
      existing_thumbnail: postData.thumbnail ? [postData.thumbnail] : [],
      additional_images: [],
      content: postData.content,
      status: postData.status,
      post_type: postData.post_type || 'news',
      press_release_link: postData.press_release_link || '',
      read_time: postData.read_time || '',
      publish_date: postData?.publish_date,
      tags: (postData.tags || []).map((item) => item.id),
      categories: (postData.categories || []).map((item) => item.id),
    }
  } catch (error) {
    $toast.error('Failed to load post')
    router.push({ name: 'crm-post-list' })
  } finally {
    loading.value = false
  }
}

const updatingPost = ref(false)
const savePost = async () => {
  updatingPost.value = true
  try {
    const fd = new FormData();
    fd.append('title', form.value.title);
    fd.append('slug', form.value.slug);
    fd.append('subtitle', form.value.subtitle);
    fd.append('header_image', form.value.header_image?.file ? form.value.header_image?.file : form.value.header_image);
    fd.append('thumbnail', form.value.thumbnail?.file ? form.value.thumbnail?.file : form.value.thumbnail);
    fd.append('content', form.value.content);
    fd.append('post_type', form.value.post_type)
    fd.append('press_release_link', form.value.press_release_link)
    fd.append('read_time', form.value.read_time);
    fd.append('publish_date', form.value.publish_date);
    fd.append('status', form.value.status);
    
    // Add tags
    form.value.tags.forEach((tag, index) => {
      fd.append(`tags[${index}]`, tag.id || tag)
    })
    
    // Add categories
    form.value.categories.forEach((cat, index) => {
      fd.append(`categories[${index}]`, cat.id || cat)
    })

    form.value.additional_images.forEach((img, index) => {
      fd.append(`additional_images[${index}][file]`, img.file)
      fd.append(`additional_images[${index}][temp_url]`, img.url)
    })

    await updatePost(route.params.id, fd)

    $toast.success('Post updated successfully')
    router.push({ name: 'crm-post-list' })
  } catch (error) {
    console.error(error)
    let message = 'Failed to update post'

    if (error && error.status === 422) {
      message = Object.values(error?._data?.errors)
        .flat()
        .slice(0, 2)
        .join('\n')
    }

    $toast.error(message)
  } finally {
    updatingPost.value = false
  }
}

const removeExistingImage = (index, image) => {
  form.value.existing_header_image.splice(index, 1)
}

const removeExistingThumbnail = (index, image) => {
  form.value.existing_thumbnail.splice(index, 1)
}

const addAdditionalImage = (file, url) => {
  form.value.additional_images.push({
    file, url
  })
}

onMounted(async () => {
  await loadPost()
  fetchTags()
  fetchCategories()
})

const dropZoneRef = ref()
const dropZoneThumbRef = ref()
watch(
  () => dropZoneRef.value?.fileData,
  (newVal) => {
    if(newVal?.length) {
      form.value.header_image = newVal[0]
    }
  },
  { deep: true }
)
watch(
  () => dropZoneThumbRef.value?.fileData,
  (newVal) => {
    if(newVal?.length) {
      form.value.thumbnail = newVal[0]
    }
  },
  { deep: true }
)
</script>

<template>
  <div>
    <VCard>
      <VCardTitle>Edit Post</VCardTitle>
      <VDivider />
      <VCardText v-if="!loading">
        <VForm @submit.prevent="savePost">
          <VRow>
            <VCol cols="12" sm="12">
              <VTextField
                v-model="form.title"
                label="Title"
                placeholder="Enter post title"
                variant="outlined"
                :rules="[v => !!v || 'Title is required']"
                required
              />
            </VCol>
          </VRow>

          <VRow>
            <VCol cols="12">
              <VTextField
                v-model="form.subtitle"
                label="Subtitle"
                placeholder="Enter post subtitle"
                variant="outlined"
              />
            </VCol>
          </VRow>

          <VRow>
            <VCol cols="12" sm="6">
              <VTextField
                v-model="form.read_time"
                label="Read Time"
                placeholder="e.g., 5 min read"
                variant="outlined"
              />
            </VCol>
            <VCol cols="12" sm="6">
              <VSelect
                v-model="form.status"
                label="Status"
                :items="statuses"
                item-title="title"
                item-value="value"
                variant="outlined"
              />
            </VCol>
          </VRow>

          <VRow>
            <VCol cols="12" sm="6">
              <AppDateTimePicker
                v-model="form.publish_date"
                label="Publish Date"
              />
            </VCol>
          </VRow>

          <VRow>
            <VCol cols="12" sm="6">
              <VSelect
                v-model="form.post_type"
                label="Post Type"
                :items="[
                  { title: 'News', value: 'news' },
                  { title: 'Press release', value: 'press_release' }
                ]"
                item-title="title"
                item-value="value"
                variant="outlined"
              />
            </VCol>
            <VCol cols="12" sm="6">
              <VTextField
                v-model="form.press_release_link"
                label="Press Release Link"
                placeholder="https://..."
                variant="outlined"
              />
            </VCol>
          </VRow>

          <VRow>
            <VCol cols="12" md="6">
              <label class="text-label mb-2">Header Image</label>
              <DropZone
                :existing-images="form.existing_header_image"
                :max="1"
                ref="dropZoneRef"
                @removeExistingImage="removeExistingImage"
              />
            </VCol>
            <VCol cols="12" md="6">
              <label class="text-label mb-2">Thumbnail</label>
              <DropZone
                :existing-images="form.existing_thumbnail"
                :max="1"
                ref="dropZoneThumbRef"
                @removeExistingImage="removeExistingThumbnail"
              />
            </VCol>
          </VRow>

          <VRow>
            <VCol cols="12">
              <label class="text-label mb-2">Content</label>
              <PostContentEditor
                v-model="form.content"
                placeholder="Enter post content"
                class="border rounded"
                @addAdditionalImage="addAdditionalImage"
              />

            </VCol>
          </VRow>

          <VRow>
            <VCol cols="12" sm="6">
              <VAutocomplete
                v-model="form.tags"
                label="Tags"
                placeholder="Select tags"
                :items="tags"
                item-title="title"
                item-value="id"
                multiple
                chips
                clearable
                :loading="loadingTags"
                @update:search="fetchTags"
              />
            </VCol>
            <VCol cols="12" sm="6">
              <VAutocomplete
                v-model="form.categories"
                label="Categories"
                placeholder="Select categories"
                :items="categories"
                item-title="title"
                item-value="id"
                multiple
                chips
                clearable
                :loading="loadingCategories"
                @update:search="fetchCategories"
              />
            </VCol>
          </VRow>

          <VRow class="mt-5">
            <VCol cols="12">
              <VBtn
                type="submit"
                color="primary"
                :loading="updatingPost"
              >
                Update Post
              </VBtn>
              <VBtn
                variant="tonal"
                color="error"
                class="ms-3"
                @click="router.push({ name: 'crm-post-list' })"
              >
                Cancel
              </VBtn>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
      <VCardText v-else>
        <VProgressCircular
          indeterminate
          color="primary"
          class="d-block mx-auto"
        />
      </VCardText>
    </VCard>
  </div>
</template>
