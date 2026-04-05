<script setup>
import AppAutocomplete from '@/components/AppAutocomplete.vue'
import { onMounted } from 'vue'

const props = defineProps({
  rfqId: {
    type: [String, Number],
    required: true,
  },
  assignedTo: {
    type: Number,
  },
  rfqStatus: {
    type: String
  }
})

const emit = defineEmits(['refresh'])

const accessToken = useCookie('accessToken').value
const assignedTo = ref('')
const isAssigning = ref(false)

const assignOptions = ref([])

const fetchManagers = async () => {
  try {
    const response = await $api(`/employees/rfq-managers?assigned=${props.assignedTo ?? 0}`, {
      method: 'GET',
      headers: { Authorization: `Bearer ${accessToken}` },
    })

    assignOptions.value = await (response.data ?? []).map(item => ({
      name: item.name,
      id: item.id
    }));

    assignedTo.value = props.assignedTo

  } catch (e) {
    $toast.error('Failed to fetch managers')
  }
}

const handleAssign = async () => {
  if (!assignedTo.value) {
    $toast.warning('Please select a user')
    return
  }

  isAssigning.value = true
  try {
    await $api(`/rfqs/${props.rfqId}/assign-manager`, {
      method: 'PATCH',
      body: { assigned_to: assignedTo.value },
      headers: { Authorization: `Bearer ${accessToken}` },
    })
    $toast.success('RFQ assigned successfully')
    emit('refresh')
  } catch (e) {
    $toast.error('Failed to assign RFQ')
  } finally {
    isAssigning.value = false
  }
}

onMounted(() => {
  fetchManagers()
})
</script>

<template>
  <VCard class="mb-6">
    <VCardTitle>Assign Manager</VCardTitle>
    <VCardText class="pb-3">
      <VRow>
        <VCol cols="12">
          <AppAutocomplete
            v-model="assignedTo"
            :items="assignOptions"
            autocomplete
            label="Assign Manager"
            clearable
          />
        </VCol>
      </VRow>
    </VCardText>
    <VCardActions class="pl-6">
      <VBtn
        v-if="props.rfqStatus == 'pending'"
        :loading="isAssigning"
        color="primary"
        variant="elevated"
        @click="handleAssign"
      >
        Assign
      </VBtn>
    </VCardActions>
  </VCard>
</template>
