<script setup>
const deliveryData = [
  {
    title: 'Total Absents',
    value: '1k',
    desc: 'Today\'s',
    icon: 'tabler-box',
    color: 'primary',
  },
  {
    title: 'Social Networks',
    value: '5k',
    desc: 'Scoial Channels',
    icon: 'tabler-truck',
    color: 'info',
  },
  {
    title: 'Email Newsletters',
    value: '15k',
    desc: 'Mail Campaigns',
    icon: 'tabler-circle-check',
    color: 'success',
  },
  {
    title: 'Refferals',
    value: '95%',
    desc: 'Impact Radius Visits',
    icon: 'tabler-percentage',
    color: 'warning',
  },
  {
    title: 'ADVT',
    value: '2.5 Days',
    desc: 'Google ADVT',
    icon: 'tabler-clock',
    color: 'secondary',
  },
  {
    title: 'Others',
    value: '4.5/5',
    desc: 'Many Sources',
    icon: 'tabler-users',
    color: 'error',
  },
]
</script>

<template>
  <VCard>
    <VCardText>
      <VList class="card-list">
        <VListItem
          v-for="(data, index) in deliveryData"
          :key="index"
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
            <div class="d-flex align-center gap-x-1">
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
