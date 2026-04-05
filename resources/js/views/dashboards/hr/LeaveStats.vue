<script setup>
import { convertNegativeToZero } from '@/utils/helpers/number'
import { computed } from 'vue'

const props = defineProps({
  dashboardStats: {
    type: Object,
    default: {}
  },
  desc: {
    type: String,
    default: ''
  }
})

const deliveryData = computed(() => {
  return [
    {
      title: 'Total Absents',
      value: props.dashboardStats?.total_absent,
      desc: props.desc,
      icon: 'tabler-clock-x',
      color: 'error',
    },
    {
      title: 'Full Leaves',
      value: props.dashboardStats?.total_leave,
      desc: props.desc,
      icon: 'tabler-calendar-event',
      color: 'info',
    },
    {
      title: 'Half Leaves',
      value: props.dashboardStats?.total_half_day_leaves,
      desc: props.desc,
      icon: 'tabler-calendar-event',
      color: 'info',
    },
    // {
    //   title: 'Late In’s (Deduction)',
    //   value: props.dashboardStats?.total_late,
    //   desc: props.desc,
    //   icon: 'tabler-clock-dollar',
    //   color: 'warning',
    // },
    {
      title: 'On Time Attendance',
      value: props.dashboardStats?.total_present,
      desc: props.desc,
      icon: 'tabler-clock',
      color: 'success',
    },
  ]
})

</script>

<template>
  <VCard>
    <VCardText>
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
