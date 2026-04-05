<script setup>
import B2CCustomerList from '@/views/crm/customer/list/B2CCustomerList.vue';
import B2BCustomerList from '@/views/crm/customer/list/B2BCustomerList.vue';

const activeTab = ref('b2b')

const customerTypeTabs = ref([
  { title: 'Business to Business', value: 'b2b' },
  { title: 'Business to Consumer', value: 'b2c' },
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
    <B2CCustomerList
      v-if="activeTab === 'b2c'"
      v-show="activeTab === 'b2c'"
      :key="`b2c-${activeTab}`"
    />

    <!-- 👉 B2B Customer List -->
    <B2BCustomerList
      v-if="activeTab === 'b2b'"
      v-show="activeTab === 'b2b'"
      :key="`b2b-${activeTab}`"
    />
  </div>
</template>
