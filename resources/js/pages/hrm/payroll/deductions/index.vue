<template>
  <section>
    <VCard>
      <VCardTitle>Payroll Deductions</VCardTitle>
      <VCardText>
        <VDataTableServer
          :headers="headers"
          :items="deductions"
          :items-length="totalDeductions"
          :loading="loading"
          loading-text="Loading data..."
          v-model:items-per-page="itemsPerPage"
          v-model:page="page"
          class="text-no-wrap"
        >
          <template #item.is_enabled="{ item }">
            <v-switch
              v-model="item.is_enabled"
              @change="toggleStatus(item)"
              :label="item.is_enabled ? 'Enabled' : 'Disabled'"
            />
          </template>
        </VDataTableServer>
      </VCardText>
    </VCard>
  </section>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const deductions = ref([])
const totalDeductions = ref(0)
const loading = ref(false)
const itemsPerPage = ref(10)
const page = ref(1)

const headers = [
  { title: 'Name', key: 'name' },
  { title: 'Description', key: 'description' },
  { title: 'Status', key: 'is_enabled', align: 'center' },
]

const fetchDeductions = async () => {
  loading.value = true
  try {
    const { data } = await $api(`/payroll-deductions?page=${page.value}&per_page=${itemsPerPage.value}`, {
      method: 'GET',
    });

    console.log('datas', data);
    deductions.value = data
    totalDeductions.value = data.value?.meta?.total
  } finally {
    loading.value = false
  }
}

const toggleStatus = async (deduction) => {
  await $api(`/payroll-deductions/${deduction.id}/status`, {
    method: 'PATCH',
  })
  await fetchDeductions()
}

onMounted(fetchDeductions)

// Watch for pagination changes
watch([itemsPerPage, page], fetchDeductions)
</script>

