<script setup>
import { useTheme } from 'vuetify'
import { hexToRgb } from '@layouts/utils'

const vuetifyTheme = useTheme()

const props = defineProps({
  dashboardStats: {
    type: Object,
    default: () => ({
      total_employees: 0,
      total_attendance: 0,
      total_present: 0,
      total_notMarked: 0,
      total_absent: 0,
      total_late: 0,
      total_leave: 0,
    }),
  },
})

const progressPresent = computed(() =>
  Math.round(
    ((props.dashboardStats?.total_present + props.dashboardStats?.total_late) / props.dashboardStats?.total_employees) * 100)
)

const progressLate = computed(() =>
  Math.round((props.dashboardStats?.total_late / props.dashboardStats?.total_employees) * 100)
)

const progressNotCheckin = computed(() =>
  Math.round((props.dashboardStats?.total_notMarked / props.dashboardStats?.total_employees) * 100)
)

const progressCheckout = computed(() =>
  Math.round((props.dashboardStats?.total_checkout / props.dashboardStats?.total_employees) * 100)
)

const earningsReports = computed(() => [
  {
    color: 'success',
    icon: 'tabler-currency-dollar',
    title: 'Total Check In',
    amount: props.dashboardStats.total_present + props.dashboardStats.total_late,
    progress: progressPresent.value,
    link: `/hrm/attendance/list?status=present`
  },
  {
    color: 'warning',
    icon: 'tabler-chart-pie-2',
    title: 'Late Check In',
    amount: props.dashboardStats.total_late,
    progress: progressLate.value,
    link: `/hrm/attendance/list?status=late`
  },
  {
    color: 'error',
    icon: 'tabler-brand-paypal',
    title: 'Not Marked',
    amount: props.dashboardStats.total_notMarked,
    progress: progressNotCheckin.value,
    link: `/hrm/attendance/list?status=not-marked`
  },
  {
    color: 'info',
    icon: 'tabler-brand-paypal',
    title: 'Total Check Out',
    amount: props.dashboardStats.total_checkout,
    progress: progressCheckout.value,
    link: `/hrm/attendance/list?status=checkout`
  },
])

</script>

<template>
  <VCard>
    <VCardItem class="pb-sm-0">
      <VCardTitle>Today's Check-In Summary</VCardTitle>
      <small class="text-medium-emphasis">Real-time attendance tracking</small>
    </VCardItem>

    <VCardText>
      <VRow class="mt-2">
        <VCol
          v-for="report in earningsReports"
          :key="report.title"
          cols="12"
          sm="6"
          md="3"
          class="pa-2"
        >
          <!-- Card Wrapper -->
          <div class="border rounded pa-3 hover-row" @click="$router.push(report.link)">
            <div class="d-flex align-center">
              <VAvatar
                rounded
                size="26"
                :color="report.color"
                variant="tonal"
                class="me-2"
              >
                <VIcon
                  size="18"
                  :icon="report.icon"
                />
              </VAvatar>

              <h6 class="text-base font-weight-regular">
                {{ report.title }}
              </h6>
            </div>

            <h6 class="text-h4 my-2">
              {{ report.amount }}
            </h6>

            <VProgressLinear
              :model-value="report.progress"
              :color="report.color"
              height="4"
              rounded
              rounded-bar
            />
          </div>
        </VCol>
      </VRow>

    </VCardText>
  </VCard>
</template>
