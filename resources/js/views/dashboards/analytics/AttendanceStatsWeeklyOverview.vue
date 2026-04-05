<script setup>
import { useTheme } from 'vuetify'
import { hexToRgb } from '@layouts/utils'
import { useRouter } from 'vue-router'
const router = useRouter()

const goToReport = link => {
  router.push(link)
}
const vuetifyTheme = useTheme()

const props = defineProps({
  dashboardStats: {
    type: Object,
    default: () => ({
      employeeCounts: {},
      weekDays: [],
      dayWiseCounts: [],
      increaseInHiring: 0,
    }),
  },
  startDate: {
    type: String,
    default: ''
  },
})

const series = []

const totalEmployees = computed(
  () => props.dashboardStats?.employeeCounts?.total_employees ?? 0
)
const increaseInHiring = computed(
  () => props.dashboardStats?.increaseInHiring ?? 0
)
const presentEmployees = computed(
  () => props.dashboardStats?.employeeCounts?.total_present ?? 0
)
const totalAttendance = computed(
  () => props.dashboardStats?.employeeCounts?.total_attendance ?? 0
)
const onLeaveEmployees = computed(
  () => props.dashboardStats?.employeeCounts?.total_leave ?? 0
)
const progressPresent = computed(() =>
  totalAttendance.value > 0
    ? Math.round((presentEmployees.value / totalAttendance.value) * 100)
    : 0
)
const progressAbsent = computed(() =>
  Math.round((100 - progressLeave.value - progressPresent.value))
)
const progressLeave = computed(() =>
  totalAttendance.value > 0
    ? Math.round((onLeaveEmployees.value / totalAttendance.value) * 100)
    : 0
)

const chartOptions = computed(() => {
  const currentTheme = vuetifyTheme.current.value.colors
  const variableTheme = vuetifyTheme.current.value.variables
  
  return {
    chart: {
      parentHeightOffset: 0,
      type: 'bar',
      toolbar: { show: false },
    },
    series: [{
      name: 'Present',
      data: props.dashboardStats.dayWiseCounts,
    }],
    plotOptions: {
      bar: {
        barHeight: '60%',
        columnWidth: '38%',
        startingShape: 'rounded',
        endingShape: 'rounded',
        borderRadius: 4,
        distributed: true,
      },
    },
    grid: {
      show: false,
      padding: {
        top: -30,
        bottom: 0,
        left: -10,
        right: -10,
      },
    },
    colors: [
      `rgba(${ hexToRgb(currentTheme.primary) },${ variableTheme['dragged-opacity'] })`,
      `rgba(${ hexToRgb(currentTheme.primary) },${ variableTheme['dragged-opacity'] })`,
      `rgba(${ hexToRgb(currentTheme.primary) },${ variableTheme['dragged-opacity'] })`,
      `rgba(${ hexToRgb(currentTheme.primary) },${ variableTheme['dragged-opacity'] })`,
      `rgba(${ hexToRgb(currentTheme.primary) },${ variableTheme['dragged-opacity'] })`,
      `rgba(${ hexToRgb(currentTheme.primary) },${ variableTheme['dragged-opacity'] })`,
      `rgba(${ hexToRgb(currentTheme.primary) }, 1)`,
    ],
    dataLabels: { enabled: false },
    legend: { show: false },
    xaxis: {
      categories: props.dashboardStats.weekDays,
      axisBorder: { show: false },
      axisTicks: { show: false },
      labels: {
        style: {
          colors: `rgba(${ hexToRgb(currentTheme['on-surface']) },${ variableTheme['disabled-opacity'] })`,
          fontSize: '13px',
          fontFamily: 'Public Sans',
        },
      },
    },
    yaxis: { labels: { show: false } },
    tooltip: { enabled: false },
    responsive: [{
      breakpoint: 1025,
      options: { chart: { height: 199 } },
    }],
  }
})

const earningsReports = computed(() => [
  {
    color: 'primary',
    icon: 'tabler-currency-dollar',
    title: 'Present',
    amount: progressPresent.value + '%',
    progress: progressPresent.value,
    link: `/hrm/attendance/list?status=present${props.startDate ? `&start_date=${props.startDate}` : ''}`
  },
  {
    color: 'error',
    icon: 'tabler-chart-pie-2',
    title: 'Absent',
    amount: progressAbsent.value + '%',
    progress: progressAbsent.value,
    link: `/hrm/attendance/list?status=absent${props.startDate ? `&start_date=${props.startDate}` : ''}`
  },
  {
    color: 'info',
    icon: 'tabler-brand-paypal',
    title: 'On leave',
    amount: progressLeave.value + '%',
    progress: progressLeave.value,
    link: `/hrm/attendance/list?status=leave${props.startDate ? `&start_date=${props.startDate}` : ''}`
  },
])

const moreList = [
  {
    title: 'View More',
    value: 'View More',
  },
  {
    title: 'Delete',
    value: 'Delete',
  },
]
</script>

<template>
  <VCard>
    <VCardItem class="pb-sm-0">
      <VCardTitle>Attendance Stats for the Week</VCardTitle>
      <VCardSubtitle>Last 7 Days</VCardSubtitle>
    </VCardItem>

    <VCardText>
      <VRow>
          <VCol
            cols="12"
            sm="5"
            lg="6"
            class="d-flex flex-column align-self-center hover-row"
            @click="$router.push('/hrm/employee/list')"
          >
            <div class="d-flex align-center gap-2 mb-3 flex-wrap">
              <h2 class="text-h2">
                {{ totalEmployees }}
              </h2>
              <VChip
                label
                size="small"
                color="success"
              >
                +{{ increaseInHiring }}%
              </VChip>
            </div>

            <span class="text-sm text-medium-emphasis">
              All Employees
            </span>
          </VCol>
        <VCol
          cols="12"
          sm="7"
          lg="6"
        >
          <VueApexCharts
            :options="chartOptions"
            :series="series"
            height="161"
          />
        </VCol>
      </VRow>

      <div class="border rounded mt-5 pa-5">
        <VRow>
          <VCol
            v-for="report in earningsReports"
            :key="report.title"
            cols="12"
            sm="4"
            class="hover-row"
            @click="goToReport(report.link)"
          >
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
          </VCol>
        </VRow>
      </div>
    </VCardText>
  </VCard>
</template>

<style>
  .hover-row:hover {
    background-color: rgba(0, 0, 0, 0.05);
    cursor: pointer;
  }
</style>
