<script setup>
import { convertNegativeToZero } from '@/utils/helpers/number'
import { computed } from 'vue'

const props = defineProps({
  desc: {
    type: String,
    default: ''
  },
  subtitle: {
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
      title: 'Engineering',
      value: 34.7 + '%',
      desc: '85 Employees',
      icon: 'tabler-clock-x',
      color: 'success',
    },
    {
      title: 'Engineering',
      value: 34.7 + '%',
      desc: '85 Employees',
      icon: 'tabler-clock-x',
      color: 'secondary',
    },
    {
      title: 'Engineering',
      value: 34.7 + '%',
      desc: '85 Employees',
      icon: 'tabler-clock-x',
      color: 'primary',
    },
  ]
})

</script>

<template>
  <VCard>
<!--    <small>{{ subtitle }}</small>-->
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
