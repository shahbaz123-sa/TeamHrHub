<script setup>
import Calendar from "@/views/dashboards/hr/Calendar.vue";
import DailyAttendanceChart from "@/views/dashboards/hr/DailyAttendanceChart.vue";
import DashboardCard from "@/views/dashboards/hr/DashboardCard.vue";
import GeoLocationCard from "@/views/dashboards/hr/GeoLocationCard.vue";
import AttendanceStats from "@/views/dashboards/hr/AttendanceStats.vue";
import NotClockInList from "@/views/dashboards/hr/NotClockInList.vue";
import { onMounted } from "vue";

const dashboardStats = ref({});
const isLoading = ref(true)

const fetchDashboardStats = async () => {
  try {
    const response = await $api("hr/dashboard/stats", {
      method: "POST",
      headers: {
        Authorization: `Bearer ${useCookie('accessToken').value}`
      }
    })

    dashboardStats.value = response.data
  } catch (error) {
    $toast.error("Failed to load dashboard data")
  }
}

const now = new Date();
const startOfMonth = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-01`;

onMounted(async () => {
  isLoading.value = true
  await fetchDashboardStats()
  isLoading.value = false
})

</script>
<template>
  <section>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0 help-center-breadcrumbs"
      :items="[{ title: 'HR Dashboard' }]"
    />
    <div v-if="isLoading" class="d-flex justify-center align-center" style="block-size: 400px;">
      <VProgressCircular indeterminate size="64" />
    </div>
    <template v-else>
      <VRow class="match-height">
        <VCol cols="12" md="8">
          <VRow>
            <VCol cols="12" sm="4">
              <DashboardCard :title="'Total Employees'" :total="dashboardStats.today_stats?.total_employees" link="/hrm/employee/list" />
            </VCol>
            <VCol cols="12" sm="4">
              <DashboardCard :title="'Total Presents'" :color="'success'" :total="dashboardStats.today_stats?.total_present" query="present" />
            </VCol>
            <VCol cols="12" sm="4">
              <DashboardCard :title="'Total Late'" :color="'warning'" :total="dashboardStats.today_stats?.total_late" query="late" />
            </VCol>
          </VRow>
          <VRow>
            <VCol cols="12" sm="4">
              <DashboardCard :title="'Total Leaves'" :color="'info'" :total="dashboardStats.today_stats?.total_leave" query="leave" />
            </VCol>
            <VCol cols="12" sm="4">
              <DashboardCard :title="'Not Marked'" :color="'primary'" :total="dashboardStats.today_stats?.total_notMarked" query="not-marked" />
            </VCol>
            <VCol cols="12" sm="4">
              <DashboardCard :title="'Total Absents'" :color="'error'" :total="dashboardStats.today_stats?.total_absent"  query="absent" />
            </VCol>
          </VRow>
        </VCol>
        <VCol cols="12" md="4">
          <DailyAttendanceChart :dashboard-stats="dashboardStats.today_stats" />
        </VCol>
      </VRow>

      <VRow>
        <VCol cols="12" md="6">
          <AttendanceStats
            :dashboard-stats="dashboardStats.today_stats"
            title="Today's Attendance Stats"
          />
        </VCol>
        <VCol cols="12" md="6">
          <AttendanceStats
            :dashboard-stats="dashboardStats.this_month_stats"
            title="This Month Attendance Stats"
            :startDate="startOfMonth"
          />
        </VCol>
      </VRow>

      <VRow>
        <VCol cols="12" lg="5">
          <GeoLocationCard />
          <VSpacer class="mt-5" />
          <NotClockInList :dashboard-stats="dashboardStats.today_stats" />
        </VCol>
        <VCol cols="12" lg="7">
          <Calendar />
        </VCol>
      </VRow>
    </template>
  </section>
</template>

<style scoped>
/* Mobile responsive adjustments */
@media (max-width: 600px) {
  .match-height {
    margin-inline: -0.5rem;
  }

  /* Reduce spacing on mobile */
  .v-row {
    margin-inline: 0;
  }

  .v-col {
    padding-inline: 0.5rem;
  }
}

/* Tablet adjustments */
@media (max-width: 960px) {
  .match-height {
    margin-inline: -0.75rem;
  }

  .v-col {
    padding-inline: 0.75rem;
  }
}

/* Ensure proper spacing between sections */
.v-row + .v-row {
  margin-block-start: 1.5rem;
}

@media (max-width: 600px) {
  .v-row + .v-row {
    margin-block-start: 1rem;
  }
}
</style>
