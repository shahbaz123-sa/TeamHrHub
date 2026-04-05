<script setup>
import AppAutocomplete from '@/components/AppAutocomplete.vue'
import ConfirmationDialog from '@/components/common/ConfirmationDialog.vue'
import { usePostApi } from '@/composables/usePostApi'
import { router } from '@/plugins/1.router'
import { formatLongText, humanize } from '@/utils/helpers/str'
import { hasPermission } from '@/utils/permission'
import { onMounted, ref, watch } from 'vue'

const { fetchPosts, deletePost } = usePostApi()

const headers = [
  {
    title: 'Title',
    key: 'title',
  },
  {
    title: 'Type',
    key: 'post_type',
  },
  {
    title: 'Status',
    key: 'status',
  },
  {
    title: 'Read Time',
    key: 'read_time',
  },
  {
    title: 'Author',
    key: 'author.name',
  },
  {
    title: 'Publish Date',
    key: 'published_at',
  },
  {
    title: 'Actions',
    key: 'actions',
    sortable: false,
  },
]

const itemsPerPage = ref(10)
const page = ref(1)
const searchQuery = ref('')
const selectedStatus = ref(null)

const posts = ref([])
const totalPosts = ref(0)
const loading = ref(false)

const isDeleteDialogOpen = ref(false)
const deleteTargetId = ref(null)
const deleteSubmitting = ref(false)

const statuses = ref([
  { id: 'publish', name: 'Publish' },
  { id: 'draft', name: 'Draft' },
  { id: 'inactive', name: 'Inactive' },
])

const resolveStatus = statusMsg => {
  const statusMap = {
    publish: { text: 'Publish', color: 'success' },
    draft: { text: 'Draft', color: 'warning' },
    inactive: { text: 'Inactive', color: 'error' },
  }
  return statusMap[statusMsg] || { text: statusMsg, color: 'secondary' }
}

const fetchPostsData = async () => {
  loading.value = true
  try {
    const data = await fetchPosts({
      q: searchQuery.value,
      status: selectedStatus.value,
      page: page.value,
      per_page: itemsPerPage.value,
    })

    posts.value = data?.data ?? []
    totalPosts.value = data?.meta?.total ?? 0
  } catch (error) {
    $toast.error('Failed to load posts')
  } finally {
    loading.value = false
  }
}

const askDelete = (id) => {
  deleteTargetId.value = id
  isDeleteDialogOpen.value = true
}

const confirmDelete = async () => {
  deleteSubmitting.value = true
  try {
    await deletePost(deleteTargetId.value)
    $toast.success('Post deleted successfully')
    fetchPostsData()
  } catch (error) {
    $toast.error('Failed to delete post')
  } finally {
    isDeleteDialogOpen.value = false
    deleteTargetId.value = null
    deleteSubmitting.value = false
  }
}

watch(
  [searchQuery, page, itemsPerPage, selectedStatus],
  () => {
    fetchPostsData()
  },
  { deep: true }
)

onMounted(() => {
  fetchPostsData()
})
</script>

<template>
  <div>
    <VCard>
      <VCardText>
        <VRow>
          <VCol cols="12" md="3" sm="12">
            <AppAutocomplete
              v-model="selectedStatus"
              :items="statuses"
              item-title="name"
              item-value="id"
              placeholder="Choose Status"
              clearable
            />
          </VCol>
        </VRow>
      </VCardText>

      <VDivider />

      <VCardText>
        <div class="d-flex justify-sm-space-between flex-wrap gap-y-4 gap-x-6 justify-start">
          <AppTextField
            v-model="searchQuery"
            placeholder="Search Post"
            style="max-inline-size: 290px; min-inline-size: 290px;"
            clearable
          />

          <div class="d-flex align-center flex-wrap gap-4">
            <AppSelect
              v-model="itemsPerPage"
              :items="[
                { value: 5, title: '5' },
                { value: 10, title: '10' },
                { value: 20, title: '20' },
                { value: 50, title: '50' },
                { value: -1, title: 'All' },
              ]"
              style="max-inline-size: 100px; min-inline-size: 100px;"
            />
            <VBtn
              v-if="hasPermission('post.create')"
              prepend-icon="tabler-plus"
              @click="router.push({ name: 'crm-post-add' })"
            >
              Add Post
            </VBtn>
          </div>
        </div>
      </VCardText>

      <VDivider />

      <div class="post-table">
        <VDataTableServer
          v-model:items-per-page="itemsPerPage"
          v-model:page="page"
          :headers="headers"
          :items="posts"
          :items-length="totalPosts"
          :search="searchQuery"
          :loading="loading"
          loading-text="Loading posts..."
          class="text-no-wrap"
        >
          <template #item.title="{ item }">
            <div class="d-flex gap-x-3 align-center">
              <DocumentImageViewer v-if="item.thumbnail" :type="'avatar'" :src="item?.thumbnail" />
              <div>
                <h6 class="text-h6">
                  {{ item.title }}
                </h6>
                <div class="text-body-2">
                  {{ formatLongText(item.subtitle || 'No subtitle') }}
                </div>
              </div>
            </div>
          </template>

          <template #item.post_type="{ item }">
            <span>{{ humanize(item.post_type) }}</span>
          </template>

          <template #item.status="{ item }">
            <VChip :color="resolveStatus(item.status).color" size="small">
              {{ resolveStatus(item.status).text }}
            </VChip>
          </template>

          <template #item.created_at="{ item }">
            {{ formatDate(item.created_at) }}
          </template>

          <template #item.actions="{ item }">
            <IconBtn v-if="hasPermission('post.read')" @click="router.push({ name: 'crm-post-details-id', params: { id: item.id } })">
              <VIcon icon="tabler-eye" />
            </IconBtn>
            <IconBtn v-if="hasPermission('post.update')" @click="router.push({ name: 'crm-post-edit-id', params: { id: item.id } })">
              <VIcon icon="tabler-edit" />
            </IconBtn>
            <IconBtn v-if="hasPermission('post.delete')" @click="askDelete(item.id)">
              <VIcon icon="tabler-trash" />
            </IconBtn>
          </template>

          <template #bottom>
            <TablePagination
              v-model:page="page"
              :items-per-page="itemsPerPage"
              :total-items="totalPosts"
            />
          </template>
        </VDataTableServer>
      </div>
    </VCard>

    <ConfirmationDialog
      v-model="isDeleteDialogOpen"
      title="Are you sure"
      description="This action can not be undone. Do you want to continue?"
      cancel-text="No"
      confirm-text="Yes"
      :loading="deleteSubmitting"
      @confirm="confirmDelete"
    />
  </div>
</template>
