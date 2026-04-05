<script setup>
import { computed } from 'vue'

const props = defineProps({
  desc: {
    type: String,
    default: ''
  },
  departments: {
    type: Array,
  },
  totalEmployees: {
    type: Number,
    required: true
  },
  percentage: {
    type: String,
    default: ''
  }
});

const availableColors = ['primary', 'secondary', 'success', 'warning', 'error', 'info'];
const availableIcons = ['tabler-building', 'tabler-users', 'tabler-clock', 'tabler-briefcase', 'tabler-flag'];

// Compute department data dynamically
const departmentsData = computed(() => {
  return props.departments.map(() => {
    // Pick a random color and icon
    const color = availableColors[Math.floor(Math.random() * availableColors.length)];
    const icon = availableIcons[Math.floor(Math.random() * availableIcons.length)];
    return { color, icon };
  });
});

</script>

<template>
  <VCard class="h-100">
    <VCardItem class="mb-1">
      <VCardTitle>Departments</VCardTitle>
      <VCardSubtitle>
        Employee Distribution
      </VCardSubtitle>
    </VCardItem>
    <VCardText style="height: 300px; overflow-y: auto">
      <VList class="card-list" >
        <VListItem
          v-for="(data, index) in departments"
          :key="index"
          class="mb-1 mt-0 hover-row"
          :to="{
            path: '/hrm/employee/list',
            query: {
              department: data.id
            }
          }"
          link
        >
          <template #prepend>
            <VAvatar
              :color="departmentsData[index].color"
              variant="tonal"
              rounded
              size="20"
              class="me-1"
            >
              <VIcon
                :icon="departmentsData[index].icon"
                size="16"
              />
            </VAvatar>
          </template>

          <VListItemTitle class="me-2">
            {{ data.name }}
          </VListItemTitle>

          <VListItemSubtitle>
            <div class="d-flex align-center gap-x-1">
              <div>{{ data.employees }} Employees</div>
            </div>
          </VListItemSubtitle>

          <template #append>
            <span class="text-body-1 font-weight-medium">
              {{ ((data.employees / totalEmployees) * 100).toFixed(1) }}%
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
