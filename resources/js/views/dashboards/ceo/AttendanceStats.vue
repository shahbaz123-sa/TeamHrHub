<script setup>

const props = defineProps({
  deliveryData: {
    type: Object,
    default: {}
  },
  desc: {
    type: String,
    default: ''
  }
})

</script>

<template>
  <VCard class="h-100">
    <VCardItem class="mb-1">
      <VCardTitle>Monthly Attendance Stats</VCardTitle>
      <VCardSubtitle>
        Attendance Stats
      </VCardSubtitle>
    </VCardItem>
    <VCardText>
      <VList class="card-list">
        <VListItem
          v-for="(data, index) in deliveryData"
          :key="index"
          class="mb-3 hover-row"
          :to="data.link"
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
            <div class="d-flex align-center">
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
