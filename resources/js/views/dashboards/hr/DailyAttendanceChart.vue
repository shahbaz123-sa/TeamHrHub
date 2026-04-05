<script setup>

const props = defineProps({
  dashboardStats: {
    type: Object,
    default: {}
  }
})

const chartColors = {
  donut: {
    series1: '#28c76f',
    series2: '#FF9F43',
    series3: '#00BAD1',
    series4: '#dc3545',
    series5: '#D55D36',
  },
}

const headingColor = 'rgba(var(--v-theme-on-background), var(--v-high-emphasis-opacity))'
const labelColor = 'rgba(var(--v-theme-on-background), var(--v-medium-emphasis-opacity))'

const chartSeries = computed(() => {
  return [
    props.dashboardStats.total_present ?? 0,
    props.dashboardStats.total_late ?? 0,
    props.dashboardStats.total_leave ?? 0,
    props.dashboardStats.total_absent ?? 0,
    props.dashboardStats.total_notMarked ?? 0,
  ]
})

const chartConfig = {
  labels: [
    'Present',
    'Late',
    'Leave',
    'Absent',
    'Not Marked'
  ],
  colors: [
    chartColors.donut.series1,
    chartColors.donut.series2,
    chartColors.donut.series3,
    chartColors.donut.series4,
    chartColors.donut.series5,
  ],
  stroke: { width: 0 },
  dataLabels: {
    enabled: true,
    style: {
        fontSize: '12px',
        fontFamily: 'Helvetica, Arial, sans-serif',
        fontWeight: 'normal',
        colors: ['#000']
    },
    dropShadow: {
      enabled: false,
    },
    formatter(val) {
      return `${ Number.parseInt(val) }%`
    },
  },
  legend: {
    show: true,
    position: 'bottom',
    offsetY: 10,
    markers: {
      width: 8,
      height: 8,
      offsetX: -3,
    },
    itemMargin: {
      horizontal: 5,
      vertical: 5,
    },
    fontSize: '13px',
    fontWeight: 400,
    labels: {
      colors: headingColor,
      useSeriesColors: false,
    },
  },
  tooltip: { theme: false },
  plotOptions: {
    pie: {
      donut: {
        size: '50%',
        labels: {
          show: true,
          value: {
            fontSize: '15px',
            color: headingColor,
            fontWeight: 500,
            offsetY: 5,
            formatter(val) {
              return `${ Number.parseInt(val) }%`
            },
          },
          name: { offsetY: 0 },
          total: {
            show: true,
            fontSize: '0.9375rem',
            fontWeight: 400,
            label: 'Total',
            color: labelColor,
            formatter() {
              return props.dashboardStats.total_employees
            },
          },
        },
      },
    },
  },
  responsive: [{
    breakpoint: 420,
    options: { chart: { height: 190 } },
  }],
}
</script>

<template>
  <VCard>
    <VCardItem>
      <h3>Daily Attendance</h3>
    </VCardItem>
    <VCardText>
      <VueApexCharts
        type="donut"
        height="230"
        :options="chartConfig"
        :series="chartSeries"
      />
    </VCardText>
  </VCard>
</template>
