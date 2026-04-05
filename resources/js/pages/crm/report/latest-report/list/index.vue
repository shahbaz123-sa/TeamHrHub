<script setup>
import AppAutocomplete from '@/components/AppAutocomplete.vue';
import ConfirmationDialog from '@/components/common/ConfirmationDialog.vue';
import DocumentImageViewer from '@/components/common/DocumentImageViewer.vue';
import { hasPermission } from '@/utils/permission';
import AddUpdateDrawer from '@/views/crm/report/latest-report/AddUpdateDrawer.vue';
import { onMounted, ref, watch } from 'vue';

const searchQuery = ref('')
const itemsPerPage = ref(10)
const page = ref(1)
const selectedStatus = ref()

const items = ref([])
const totalItems = ref(0)
const loading = ref(false)

const isDrawerVisible = ref(false)
const editingItem = ref(null)

const isDeleteDialogOpen = ref(false)
const deleteSubmitting = ref(false)
const deleteTargetId = ref(null)

const accessToken = useCookie('accessToken').value

const headers = [
	{ title: 'Title', key: 'title' },
	{ title: 'Attachment', key: 'attachment' },
	{ title: 'Report Date', key: 'report_date' },
	{ title: 'Active', key: 'is_active' },
	{ title: 'Actions', key: 'actions', sortable: false },
]

const fetchItems = async () => {
	loading.value = true
	const { data, meta } = await $api('/latest-reports', {
		query: {
			q: searchQuery.value,
			page: page.value,
			per_page: itemsPerPage.value,
			status: selectedStatus.value,
		},
		method: "GET",
		headers: { Authorization: `Bearer ${accessToken}` }
	})
	items.value = data
	totalItems.value = meta.total || 0
	loading.value = false
}

const openDrawer = (item = null) => {
	editingItem.value = item;
	isDrawerVisible.value = true
}

const saveItem = async (formData, id) => {
	loading.value = true
	try {
		if(id) {
			formData.append('_method', 'PUT')
			await $api(`/latest-reports/${id}`, {
				method: 'POST',
				body: formData,
				headers: { Authorization: `Bearer ${accessToken}` }
			})
		} else {
			await $api('/latest-reports', {
				method: 'POST',
				body: formData,
				headers: { Authorization: `Bearer ${accessToken}` }
			})
		}
		fetchItems()
		$toast.success('Report saved successfully')
		isDrawerVisible.value = false
	} catch (err) {
		let message = "Something went wrong!"
		if (err && err.status === 201) {
				fetchItems()
				$toast.success('Report saved successfully')
				isDrawerVisible.value = false
				return;
		}
		if (err && err.status === 422) {
				message = Object.values(err?._data?.errors).join("\n")
		}
		$toast.error(message)
	} finally {
		loading.value = false
	}
}

const askDelete = (id) => {
	deleteTargetId.value = id;
	isDeleteDialogOpen.value = true;
};

const confirmDelete = async () => {
	loading.value = true
	try {
		await $api(`/latest-reports/${deleteTargetId.value}`, {
			method: 'DELETE',
			headers: { Authorization: `Bearer ${accessToken}` }
		})
		fetchItems()
		$toast.success('Report deleted!')
	} catch (e) {
		$toast.error('Failed to delete')
	} finally {
		isDeleteDialogOpen.value = false;
		deleteSubmitting.value = false;
		loading.value = false
		deleteTargetId.value = null;
	}
}

watch([
	searchQuery,
	page,
	itemsPerPage,
	selectedStatus
], () => {
	fetchItems()
})

onMounted(fetchItems)
</script>

<template>
	<div>
		<VCard>
			<VCardText>
				<VRow>
					<VCol cols="3">
						<AppAutocomplete
							v-model="selectedStatus"
							:items="[{id: 1, name: 'Active'}, {id: 0, name: 'Inactive'}]"
							autocomplete
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
						placeholder="Search Report"
						style="max-inline-size: 280px; min-inline-size: 280px;"
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
							v-if="hasPermission('latest_report.create')"
							prepend-icon="tabler-plus"
							@click="openDrawer()"
						>
							Add Report
						</VBtn>
					</div>
				</div>
			</VCardText>

			<VDivider />
  
			<VDataTableServer
				v-model:items-per-page="itemsPerPage"
				v-model:page="page"
				:headers="headers"
				:items="items"
				:items-length="totalItems"
				:loading="loading"
				loading-text="Loading data..."
				class="text-no-wrap"
			>
				<template #item.attachment="{ item }">
					<div class="d-flex align-center gap-2">
            <DocumentImageViewer v-if="item.attachment" :src="item.attachment" :pdf-title="item.title">
              <template #icon></template>
            </DocumentImageViewer>
            <VBtn size="small" v-if="item.attachment" :href="item.attachment" icon download :title="'Download'">
              <VIcon icon="tabler-download" />
            </VBtn>
            <span v-if="!item.attachment">-</span>
          </div>
				</template>
				<template #item.report_date="{ item }">
					{{ item.report_date }}
				</template>
				<template #item.is_active="{ item }">
					<VChip :color="item.is_active ? 'success' : 'error'" size="small">
						{{ item.is_active ? 'Active' : 'Inactive' }}
					</VChip>
				</template>
				<template #item.actions="{ item }">
					<IconBtn v-if="hasPermission('latest_report.update')" @click="openDrawer(item)">
						<VIcon icon="tabler-edit" />
					</IconBtn>
					<IconBtn v-if="hasPermission('latest_report.delete')" @click="askDelete(item.id)">
						<VIcon icon="tabler-trash" />
					</IconBtn>
				</template>
				<template #bottom>
					<TablePagination v-model:page="page" :items-per-page="itemsPerPage" :total-items="totalItems" />
				</template>
			</VDataTableServer>
		</VCard>
  
		<AddUpdateDrawer
			v-model:is-drawer-open="isDrawerVisible"
			v-model:editing-latest-report="editingItem"
			@save="saveItem"
		/>

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

