<script setup>
import ExistingClient from '@/views/crm/credit-application/list/ExistingClient.vue';
import NonExistingClient from '@/views/crm/credit-application/list/NonExistingClient.vue';


const activeTab = ref('existing-client')

const customerTypeTabs = ref([
  { title: 'Existing Client', value: 'existing-client' },
  { title: 'Non Existing Client', value: 'non-existing-client' },
])
</script>

<template>
  <div>
    <!-- 👉 Customer Type Tabs -->
    <div class="mb-6 d-flex gap-3 align-center">
      <VTabs
        v-model="activeTab"
        background-color="transparent"
      >
        <VTab
          v-for="tab in customerTypeTabs"
          :key="tab.value"
          :value="tab.value"
        >
          {{ tab.title }}
        </VTab>
      </VTabs>
    </div>

    <!-- 👉 B2C Customer List -->
    <NonExistingClient
      v-if="activeTab === 'non-existing-client'"
      v-show="activeTab === 'non-existing-client'"
      :key="`non-existing-client-${activeTab}`"
    />

    <!-- 👉 B2B Customer List -->
    <ExistingClient
      v-if="activeTab === 'existing-client'"
      v-show="activeTab === 'existing-client'"
      :key="`existing-client-${activeTab}`"
    />
  </div>
</template>
