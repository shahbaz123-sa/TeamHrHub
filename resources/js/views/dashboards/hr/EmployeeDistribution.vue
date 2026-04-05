<script setup>
import { convertNegativeToZero } from '@/utils/helpers/number'
import { computed } from 'vue'

const props = defineProps({
  desc: {
    type: String,
    default: ''
  },
  title: {
    type: String,
    default: ''
  },
  subtitle: {
    type: String,
    default: ''
  },
  percentage: {
    type: String,
    default: ''
  }
})

const deliveryData = computed(() => {
  return [
    {
      title: 'Engineering',
      value: 34.7 + '%',
      desc: '85 Employees',
      icon: 'tabler-clock-x',
      color: 'info',
    },
    {
      title: 'Sales & marketing',
      value: 18.4 + '%',
      desc: '45 Employees',
      icon: 'tabler-clock-x',
      color: 'success',
    },
    {
      title: 'HR & Admin',
      value: 11.4 + '%',
      desc: '28 Employees',
      icon: 'tabler-clock-x',
      color: 'secondary',
    },
    {
      title: 'Finance',
      value: 15.5 + '%',
      desc: '35 Employees',
      icon: 'tabler-clock-x',
      color: 'primary',
    },
    {
      title: 'Operations',
      value: 15.5 + '%',
      desc: '35 Employees',
      icon: 'tabler-clock-x',
      color: 'error',
    },
    {
      title: 'Others',
      value: 15.5 + '%',
      desc: '35 Employees',
      icon: 'tabler-clock-x',
      color: 'primary',
    },
  ]
})

</script>

<template>
  <VCard>
    <VCardItem>
      <VCardTitle>{{ title }}</VCardTitle>
      <VCardSubtitle>
        {{ subtitle }}
      </VCardSubtitle>
      <template #append>
        <div class="mt-n4 me-n2">
<!--          <MoreBtn :menu-list="moreList" />-->
          {{ percentage }}
        </div>
      </template>
    </VCardItem>
    <VCardText>
      <div class="mb-">
        <small class="text-medium-emphasis">{{ subtitle }}</small>
      </div>
      <VList class="card-list">
        <VListItem
          v-for="(data, index) in deliveryData"
          :key="index"
        >
          <template #prepend>
            <VAvatar
              :color="data.color"
              variant="tonal"
              rounded
              size="38"
              class="me-1"
            >
              <VIcon
                :icon="data.icon"
                size="26"
              />
            </VAvatar>
          </template>

          <VListItemTitle class="me-2">
            {{ data.title }}
          </VListItemTitle>

          <VListItemSubtitle>
            <div class="d-flex align-center gap-x-1">
              <div>{{ data.desc }}</div>
            </div>
          </VListItemSubtitle>

          <template #append>
            <span class="text-body-1 font-weight-medium">
              {{ data.value }}
            </span>
          </template>
        </VListItem>
      </VList>
    </VCardText>
  </VCard>
</template>

<style lang="scss" scoped>
.card-list {
  --v-card-list-gap: 1.5rem;
}
</style>
