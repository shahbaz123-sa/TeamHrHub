<script setup>

import { computed } from 'vue'

const props = defineProps({
  dashboardStats: {
    type: Object,
    default: {}
  },
  title: {
    type: String,
    default: ''
  },
  startDate: {
    type: String,
    default: ''
  }
})

const deliveryData = computed(() => {
  return [
    {
      title: 'Total Absents',
      value: props.dashboardStats?.total_absent,
      icon: 'tabler-clock-x',
      color: 'error',
      link: `/hrm/attendance/list?status=absent${props.startDate ? `&start_date=${props.startDate}` : ''}`
    },
    {
      title: 'Full Leaves',
      value: props.dashboardStats?.total_leave,
      icon: 'tabler-calendar-event',
      color: 'info',
      link: `/hrm/attendance/list?status=leave${props.startDate ? `&start_date=${props.startDate}` : ''}`
    },
    {
      title: 'Half Leaves',
      value: props.dashboardStats?.total_half_day_leaves,
      icon: 'tabler-calendar-event',
      color: 'info',
      link: `/hrm/attendance/list?status=leave${props.startDate ? `&start_date=${props.startDate}` : ''}`
    },
    // {
    //   title: 'Late In’s (Deduction)',
    //   value: props.dashboardStats?.total_late,
    //   icon: 'tabler-clock-dollar',
    //   color: 'warning',
    // },
    {
      title: 'On Time Attendance',
      value: props.dashboardStats?.total_present,
      icon: 'tabler-clock',
      color: 'success',
      link: `/hrm/attendance/list?status=present${props.startDate ? `&start_date=${props.startDate}` : ''}`
    },
  ]
})

</script>

<template>
  <VCard :title="props.title">
    <VCardText>
      <VListItem
          v-for="(data, index) in deliveryData"
          :key="index"
          :to="data.link"
          link
          class="hover-row py-2"
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

          <template #append>
            <span class="text-body-1 font-weight-medium">
              {{ data.value }}
            </span>
          </template>
        </VListItem>
    </VCardText>
  </VCard>
</template>

<style lang="scss" scoped>
.card-list {
  --v-card-list-gap: 1.5rem;
}
</style>
