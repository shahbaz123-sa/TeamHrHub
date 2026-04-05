<script setup>
import { useTheme } from 'vuetify'
import { hexToRgb } from '@layouts/utils'

const vuetifyTheme = useTheme()

const router = useRouter()

const redirectToResolved = () => {
  router.push('/hrm/ticket/list?status=Resolved')
}

const props = defineProps({
  stats: {
    type: Object,
    default: () => ({
      total_ticket: 0,
      new_ticket: 0,
      incomplete_ticket: 0,
      completed_ticket: 0,
    }),
  },
})

const totalTicket = computed(
  () => props.stats.total_ticket ?? 0
)
const newTicket = computed(
  () => props.stats.new_ticket ?? 0
)
const completedTicket = computed(
  () => props.stats.completed_ticket ?? 0
)
const incompleteTicket = computed(
  () => props.stats.incomplete_ticket ?? 0
)

const chartOptions = computed(() => {
  const currentTheme = vuetifyTheme.current.value.colors
  const variableTheme = vuetifyTheme.current.value.variables
  
  return {
    labels: ['Completed Ticket'],
    chart: { type: 'radialBar' },
    plotOptions: {
      radialBar: {
        offsetY: 10,
        startAngle: -140,
        endAngle: 130,
        hollow: { size: '60%' },
        track: {
          background: '#FEFAF7',
          strokeWidth: '100%',
        },
        dataLabels: {
          name: {
            offsetY: -20,
            color: `rgba(${ hexToRgb(currentTheme['on-surface']) },${ variableTheme['disabled-opacity'] })`,
            fontSize: '13px',
            fontWeight: '400',
            fontFamily: 'Public Sans',
          },
          value: {
            offsetY: 10,
            color: `rgba(${ hexToRgb(currentTheme['on-background']) },${ variableTheme['high-emphasis-opacity'] })`,
            fontSize: '38px',
            fontWeight: '500',
            fontFamily: 'Public Sans',
          },
        },
      },
    },
    colors: [currentTheme.primary],
    fill: {
      type: 'gradient',
      gradient: {
        shade: 'dark',
        shadeIntensity: 0.5,
        gradientToColors: [currentTheme.primary],
        inverseColors: true,
        opacityFrom: 1,
        opacityTo: 0.6,
        stops: [
          30,
          70,
          100,
        ],
      },
    },
    stroke: { dashArray: 10 },
    grid: {
      padding: {
        top: -20,
        bottom: 5,
      },
    },
    states: {
      hover: { filter: { type: 'none' } },
      active: { filter: { type: 'none' } },
    },
    responsive: [{
      breakpoint: 960,
      options: { chart: { height: 280 } },
    }],
  }
})

const supportTicket = [
  {
    avatarColor: 'primary',
    avatarIcon: 'tabler-ticket',
    title: 'Open Tickets',
    subtitle: newTicket,
    link: `/hrm/ticket/list?status=Open`
  },
  {
    avatarColor: 'success',
    avatarIcon: 'tabler-check',
    title: 'Resolved Tickets',
    subtitle: completedTicket,
    link: `/hrm/ticket/list?status=Resolved`
  },
  {
    avatarColor: 'warning',
    avatarIcon: 'tabler-clock',
    title: 'Pending Tickets',
    subtitle: incompleteTicket,
    link: `/hrm/ticket/list?status=Pending`
  },
]

</script>

<template>
  <VCard>
    <VCardItem>
      <VCardTitle>Ticket Statistics</VCardTitle>
      <VCardSubtitle>All time</VCardSubtitle>

    </VCardItem>

    <VCardText>
      <VRow>
        <VCol
          cols="12"
          lg="4"
          md="4"
        >
          <div class="mb-lg-6 mb-4 mt-2 hover-row" @click="$router.push({ path: '/hrm/ticket/list'})">
            <h2 class="text-h2">
              {{ props.stats.total_ticket }}
            </h2>
            <p class="text-base mb-0">
              Total Tickets
            </p>
          </div>

          <VList class="card-list">
            <VListItem
              v-for="ticket in supportTicket"
              :key="ticket.title"
              :to="ticket.link"
              class="hover-row"
            >
              <VListItemTitle class="font-weight-medium">
                {{ ticket.title }}
              </VListItemTitle>
              <VListItemSubtitle>
                {{ ticket.subtitle }}
              </VListItemSubtitle>
              <template #prepend>
                <VAvatar
                  rounded
                  size="34"
                  :color="ticket.avatarColor"
                  variant="tonal"
                  class="me-1"
                >
                  <VIcon
                    size="22"
                    :icon="ticket.avatarIcon"
                  />
                </VAvatar>
              </template>
            </VListItem>
          </VList>
        </VCol>
        <VCol
          cols="12"
          lg="8"
          md="8"
        >
          <VueApexCharts
            :options="chartOptions"
            :series="[Math.ceil(((completedTicket / (totalTicket || 1)) * 100) || 100)]"
            height="360"
            class="hover-row"
            @click="redirectToResolved"
          />
        </VCol>
      </VRow>
    </VCardText>
  </VCard>
</template>

<style lang="scss" scoped>
.card-list {
  --v-card-list-gap: 16px;
}
</style>
