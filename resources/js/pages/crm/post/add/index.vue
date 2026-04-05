<script setup>
import PostContentEditor from '@/components/crm/PostContentEditor.vue'
import DropZone from '@/components/DropZone.vue'
import { usePostApi } from '@/composables/usePostApi'
import { router } from '@/plugins/1.router'
import { onMounted, ref, watch } from 'vue'

const { createPost } = usePostApi()

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
  subtitle: '',
  header_image: null,
  thumbnail: null,
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

const savingPost = ref(false)
const savePost = async () => {
  savingPost.value = true
  try {
    const fd = new FormData()
    fd.append('title', form.value.title)
    fd.append('subtitle', form.value.subtitle)
    fd.append('header_image', form.value.header_image?.file)
    fd.append('thumbnail', form.value.thumbnail?.file)
    fd.append('content', form.value.content)
    fd.append('post_type', form.value.post_type)
    fd.append('press_release_link', form.value.press_release_link)
    fd.append('read_time', form.value.read_time)
    fd.append('publish_date', form.value.publish_date)
    fd.append('status', form.value.status)
    
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

    await createPost(fd)

    $toast.success('Post created successfully')
    router.push({ name: 'crm-post-list' })
  } catch (error) {
    let message = `Failed to add post`

    if (error && error.status === 201) {
      $toast.success("Post created successfully")
      router.push({ name: "crm-post-list" })
      return
    }

    if (error && error.status === 422) {
      message = Object.values(error?._data?.errors).slice(0, 2).join("\n")
    }

    $toast.error(message)
  } finally {
    savingPost.value = false
  }
}

const addAdditionalImage = (file, url) => {
  form.value.additional_images.push({
    file, url
  })
}

const dropZoneRef = ref()
const dropZoneThumbRef = ref()
watch(
  () => dropZoneRef.value?.fileData,
  (newVal) => {
    form.value.header_image = newVal[0]
  },
  { deep: true }
)
watch(
  () => dropZoneThumbRef.value?.fileData,
  (newVal) => {
    form.value.thumbnail = newVal[0]
  },
  { deep: true }
)

onMounted(() => {
  fetchTags()
  fetchCategories()
})
</script>

<template>
  <div>
    <VCard>
      <VCardTitle>Create Post</VCardTitle>
      <VDivider />
      <VCardText>
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
                ref="dropZoneRef"
                :max="1" />
            </VCol>
            <VCol cols="12" md="6">
              <label class="text-label mb-2">Thumbnail</label>
              <DropZone
                ref="dropZoneThumbRef"
                :max="1" />
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
                :loading="savingPost"
              >
                Publish Post
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
    </VCard>
  </div>
</template>
