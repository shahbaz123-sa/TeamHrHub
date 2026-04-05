<script setup>
import { onMounted } from "vue";
import AttStats from "@/views/dashboards/ceo/AttendanceStats.vue";
import DepWiseEmployees from "@/views/dashboards/ceo/DepWiseEmployees.vue";
import TicketStatistics from "@/views/dashboards/analytics/TicketStatistics.vue";
import AttendanceStatsWeeklyOverview
  from "@/views/dashboards/analytics/AttendanceStatsWeeklyOverview.vue";
import CardAssetsStats from "@/views/pages/cards/card-statistics/CardAssetsStats.vue";
import TodayAttendanceCheckinSummery from "@/views/dashboards/ceo/TodayAttendanceCheckinSummery.vue";

const router = useRouter()
const totalLeaves = ref(0)
const loading = ref(false)
const leaves = ref([])
const searchQuery = ref("")
const itemsPerPage = ref(5)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()
const selectedRows = ref([])
const now = new Date()
const leavesData = ref({ data: [], meta: { total: 0 } })

const sixDaysBefore = new Date(now)
sixDaysBefore.setDate(now.getDate() - 6)

const weekBefore = sixDaysBefore.toISOString().slice(0, 10)
const startOfMonth = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-01`;

const dashboardStats = ref({});

const headers = [
  { title: "Employee", key: "employee" },
  { title: "Days", key: "days" },
  { title: "Type", key: "leave_type.name" },
  { title: "Applied On", key: "leave.applied_on" },
  { title: "Start Date", key: "leave.start_date" },
  { title: "End Date", key: "leave.end_date" },
  { title: "Manager", key: "manager_status" },
  { title: "HR", key: "hr_status", sortable: false },
]

const adjustedPresent = computed(() => {
  const p = dashboardStats.value?.employeeCounts?.total_present ?? 0
  const l = dashboardStats.value?.employeeCounts?.total_late ?? 0
  return p - l
})


const assetsCounts = [
  { title: 'Total Assets', value: 753, color: 'dark' },
  { title: 'Assigned', value: 694, color: 'success' },
  { title: 'Available', value: 58, color: 'error' },
];

const deliveryData = computed(() => {
  const monthlyStats = dashboardStats.value?.monthlyAttStats;
  return [
    {
      title: 'Present',
      value: (monthlyStats?.total_present / monthlyStats?.total_attendance).toFixed(1) + '%',
      desc: null,
      icon: 'tabler-clock-x',
      color: 'success',
      perColor: 'success',
      link: `/hrm/attendance/list?status=present&start_date=${startOfMonth}`
    },
    {
      title: 'On-Time',
      value: (adjustedPresent.value / monthlyStats?.total_attendance).toFixed(1) + '%',
      desc: null,
      icon: 'tabler-calendar-event',
      color: 'info',
      perColor: 'success',
      link: `/hrm/attendance/list?status=present&start_date=${startOfMonth}`
    },
    {
      title: 'Late',
      value: (monthlyStats?.total_late / monthlyStats?.total_attendance).toFixed(1) + '%',
      desc: null,
      icon: 'tabler-calendar-event',
      color: 'primary',
      perColor: 'error',
      link: `/hrm/attendance/list?status=late&start_date=${startOfMonth}`
    },
    {
      title: 'Absent',
      value: (monthlyStats?.total_absent / monthlyStats?.total_attendance).toFixed(1) + '%',
      desc: null,
      icon: 'tabler-clock',
      color: 'error',
      perColor: 'error',
      link: `/hrm/attendance/list?status=absent&start_date=${startOfMonth}`
    },
  ]
})

const fetchDashboardStats = async () => {
  try {
    const response = await $api("ceo/dashboard/stats", {
      method: "POST",
    })
    dashboardStats.value = response;
  } catch (error) {
    $toast.error("Failed to load dashboard data")
  }
}

const formatDays = (days) => {
  if (!days) return '0';

  // Format decimal days nicely
  const formatted = parseFloat(days).toFixed(2);

  // Remove trailing zeros for whole numbers
  return formatted.endsWith('.00') ? formatted.slice(0, -3) : formatted;
}

const fetchLeaves = async () => {
  loading.value = true
  const { data, error } = await useApi(
    createUrl("/leaves", {
      query: {
        q: searchQuery.value,
        status: '',
        leave_type_id: '',
        per_page: itemsPerPage.value,
        page: page.value,
        sortBy: sortBy.value,
        orderBy: orderBy.value,
      },
    }),
  )

  if (!error.value) {
    leavesData.value = data.value
    leaves.value = leavesData.value?.data || []
    totalLeaves.value = leavesData.value?.meta?.total || 0
  }
  loading.value = false
}

const getRowProps = ({ item }) => {
  return {
    class: 'hover-row',
    onClick: () => goToLeaves(item),
  }
}

const goToLeaves = (item) => {
  router.push({
    path: '/hrm/leave/list', // ✅ direct URL
    query: {
      employee_code: item.employee?.employee_code,
    },
  })
}


onMounted(() => {
  fetchDashboardStats();
  fetchLeaves();
});


watch([searchQuery], fetchLeaves, { deep: true })

</script>

<template>
  <section>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0 help-center-breadcrumbs"
      :items="[{ title: 'CEO Dashboard' }]"
    />
    <VRow class="match-height">
      <!-- 👉 Earning Reports Weekly Overview -->
      <VCol
        cols="12"
        md="6"
      >
        <AttendanceStatsWeeklyOverview :dashboardStats="dashboardStats" :startDate="weekBefore" />
      </VCol>

      <!-- 👉 Support Tracker -->
      <VCol
        cols="12"
        md="6"
      >
        <TicketStatistics :stats="dashboardStats.tickets" />
      </VCol>
    </VRow>

    <VRow class="match-height">
      <!-- 👉 Earning Reports Weekly Overview -->
      <VCol cols="12">
        <TodayAttendanceCheckinSummery :dashboardStats="dashboardStats?.todayAttStats" />
      </VCol>
    </VRow>

    <VRow>
      <VCol cols="12" md="4" sm="12">
        <AttStats :deliveryData="deliveryData"  :desc="'Today\'s'" />
      </VCol>
      <VCol cols="12" md="4" sm="12">
        <DepWiseEmployees :departments="dashboardStats.departments" :totalEmployees="dashboardStats?.employeeCounts?.total_employees" :percentage="'+5.2%'" />
      </VCol>
      <VCol cols="12" sm="12" md="4">
        <CardAssetsStats :counts="assetsCounts" :assets="dashboardStats?.assets" />
      </VCol>
    </VRow>

    <VRow class="mt-6">
      <VCol cols="12">
        <VCard class="attendance-table-card"
               title="Leave Requests"
               subtitle="Recent Leave Application"
        >
          <template #append>
            <div class="mt-n4 me-n2">
              <IconBtn>
                <VIcon
                  :size="iconSize"
                  icon="tabler-dots-vertical"
                />

                <VMenu
                  activator="parent"
                >
                  <VList
                    :items="[{
                    title: 'View All',
                    value: 'View All',
                  },]"
                    @click="router.push('/hrm/leave/list')"
                  />
                </VMenu>
              </IconBtn>
            </div>
          </template>
          <VDivider />

          <VCardText class="pa-0">
            <VDataTableServer v-model:items-per-page="itemsPerPage" v-model:model-value="selectedRows" v-model:page="page"
                              :headers="headers" :items="leaves" :items-length="totalLeaves" class="text-no-wrap"
                              :loading="loading" loading-text="Loading data..." :row-props="getRowProps">
              <!--            <template #item.employee.name="{ item }">-->
              <!--              <div class="text-high-emphasis text-body-1">-->
              <!--                {{ item.employee?.name }} Name-->
              <!--              </div>-->
              <!--            </template>-->

              <template #item.employee="{ item }">
                <div class="d-flex align-center gap-x-4">
                  <VAvatar size="34" :color="!item.employee?.profile_picture ? 'primary' : undefined"
                           :variant="!item.employee?.profile_picture ? 'tonal' : undefined">
                    <VImg
                      v-if="item.employee?.profile_picture"
                      :src="item.employee?.profile_picture"
                      cover
                    />
                    <span v-else>{{ item.employee?.name.charAt(0) }}</span>
                  </VAvatar>
                  <div class="d-flex flex-column">
                    <h6 class="text-base">
                      {{ item.employee?.name }}
                    </h6>
                    <div class="text-sm">
                      {{ item.employee?.official_email || item.employee?.personal_email }}
                    </div>
                  </div>
                </div>
              </template>

              <template #item.days="{ item }">
                <div class="text-high-emphasis text-body-1">{{ formatDays(item.days) }}</div>
              </template>
              <template #item.leave_type.name="{ item }">
                <div class="text-high-emphasis text-body-1">{{ item.leave_type?.name || '—' }}</div>
              </template>
              <template #item.leave.applied_on="{ item }">
                <div class="text-high-emphasis text-body-1">{{ item.applied_on }}</div>
              </template>
              <template #item.leave.start_date="{ item }">
                <div class="text-high-emphasis text-body-1">{{ item.start_date }}</div>
              </template>
              <template #item.leave.end_date="{ item }">
                <div class="text-high-emphasis text-body-1">{{ item.end_date }}</div>
              </template>
              <template #item.manager_status="{ item }">
                <VChip
                  :color="item.manager_status === 'approved' ? 'success' : item.manager_status === 'rejected' ? 'error' : 'warning'"
                  size="small" label class="text-capitalize">{{ item.manager_status }}</VChip>
              </template>
              <template #item.hr_status="{ item }">
                <VChip
                  :color="item.hr_status === 'approved' ? 'success' : item.hr_status === 'rejected' ? 'error' : 'warning'"
                  size="small" label class="text-capitalize">{{ item.hr_status }}</VChip>
              </template>
              <template #item.actions="{ item }">
                <VMenu location="bottom end">
                  <template #activator="{ props }">
                    <VBtn v-bind="props" icon="tabler-dots-vertical" variant="text" color="default" />
                  </template>
                  <VList density="compact">
                    <VListItem v-if="canEdit(item)" @click="openEditModal(item)">
                      <template #prepend>
                        <VIcon icon="tabler-edit" />
                      </template>
                      <VListItemTitle>Edit</VListItemTitle>
                    </VListItem>
                    <VListItem v-if="canDelete(item)" @click="askDelete(item.id)">
                      <template #prepend>
                        <VIcon icon="tabler-trash" />
                      </template>
                      <VListItemTitle>Delete</VListItemTitle>
                    </VListItem>
                    <VDivider class="my-1" />
                    <VListItem v-if="canManagerApproveReject(item)" @click="openManagerDecisionModal(item)">
                      <template #prepend>
                        <VIcon icon="tabler-circle-check" />
                      </template>
                      <VListItemTitle>Manager Approve/Reject</VListItemTitle>
                    </VListItem>
                    <VListItem v-if="canHrApproveReject(item)" @click="openDecisionModal(item)">
                      <template #prepend>
                        <VIcon icon="tabler-circle-check" />
                      </template>
                      <VListItemTitle>Hr Approve/Reject</VListItemTitle>
                    </VListItem>
                  </VList>
                </VMenu>
              </template>
              <template #bottom>
                <!--              <TablePagination v-model:page="page" :items-per-page="itemsPerPage" :total-items="totalLeaves" />-->
              </template>
            </VDataTableServer>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </section>
</template>

