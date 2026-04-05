<script setup>
import { useRouter } from 'vue-router'
const router = useRouter()

const props = defineProps({
  dashboardStats: {
    type: Object,
    default: {}
  }
})

const goToEmployeeAtt = (employeeCode) => {
  router.push({
    path: '/hrm/attendance/list',
    query: { q: [employeeCode] }
  })
}

</script>

<template>
  <VCard :height="322">
    <VCardItem class="pb-3 border-b-sm">
      <h3>Today's Not Clock In ({{ props.dashboardStats.notMarked_employees.length }})</h3>
    </VCardItem>
    <VCardText class="pl-0 pr-0 pt-1">
      <!-- Scrollable tbody container -->
      <div class="table-body-scroll" style="max-block-size: 250px;">
        <VTable dense>
          <tbody>
            <tr v-for="(employee, index) in props.dashboardStats.notMarked_employees"
                :key="index"
                @click="goToEmployeeAtt(employee.employee?.employee_code)"
                class="cursor-pointer hover-row"
            >
              <td>
                <div class="d-flex align-center gap-x-4 pl-2">
                  <VAvatar size="34" :color="!employee.employee?.user?.avatar_url ? 'primary' : undefined"
                           :variant="!employee.employee?.user?.avatar_url ? 'tonal' : undefined">
                    <VImg
                      v-if="employee.employee?.user?.avatar_url"
                      :src="employee.employee?.user?.avatar_url"
                      cover
                    />
                    <span v-else>{{ employee.employee?.name.charAt(0) }}</span>
                  </VAvatar>
                  <div class="d-flex flex-column">
                    <h6 class="text-base">
                      {{ employee.employee?.name }}
                    </h6>
                    <div class="text-sm">
                      {{ employee.employee?.official_email || employee.employee?.personal_email }}
                    </div>
                  </div>
                </div>
              </td>
              <td :width="150">{{ employee.employee?.department?.name }}</td>
            </tr>
          </tbody>
        </VTable>
      </div>
    </VCardText>
  </VCard>
</template>
<style scoped>

.table-body-scroll {
  max-block-size: 200px; /* Adjust based on remaining space in card */
  overflow-y: auto;
}

.scrollable-table thead th {
  position: sticky;
  z-index: 1;
}
</style>
