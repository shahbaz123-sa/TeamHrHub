<script setup>

const props = defineProps({
  counts: {
    type: {},
  },
  assets: {
    type: Array,
  },
})

</script>

<template>
  <VCard class="h-100">
    <VCardItem>
      <VCardTitle>Assets Overview</VCardTitle>
      <VCardSubtitle>
        Company Resources
      </VCardSubtitle>
    </VCardItem>

<!--    <VCardText>-->
      <VRow class="px-6">
        <!--        Total -->
        <VCol cols="12" sm="4" lg="4" class="mb-3 hover-row"
           @click="$router.push({ path: '/hrm/asset/list'})"
        >
          <div class="align-left">
            <div>
              <h6 class="text-h7 text-medium-emphasis">
                Total Assets
              </h6>
              <h4 class="text-h5 text-dark">
                {{ assets?.total_assets }}
              </h4>
            </div>
          </div>
        </VCol>
        <!--        Assigned -->
        <VCol cols="12" sm="4" lg="4" class="mb-3 hover-row"
           @click="$router.push({ path: '/hrm/asset/list', query: { status: 1 } })"
        >
          <div class="align-left">
            <div>
              <h6 class="text-h7 text-medium-emphasis">
                Assigned
              </h6>
              <h4 class="text-h5 text-success">
                {{ assets?.assigned_assets }}
              </h4>
            </div>
          </div>
        </VCol>
        <!--        Available -->
        <VCol cols="12" sm="4" lg="4" class="mb-3 hover-row"
           @click="$router.push({ path: '/hrm/asset/list', query: { status: 2 } })"
        >
          <div class="align-left">
            <div>
              <h6 class="text-h7 text-medium-emphasis">
                Available
              </h6>
              <h4 class="text-h5 text-error">
                {{ assets?.unassigned_assets }}
              </h4>
            </div>
          </div>
        </VCol>
      </VRow>
      <VCardText class="py-0 pl-6"  style="height: 250px; overflow-y: auto">
        <VList class="card-list">
          <VListItem
            v-for="asset in assets?.all_assets"
            :key="asset.id"
            class="hover-row"
            :to="{
              path: '/hrm/asset/list',
              query: {
                asset_type: asset.id
              }
            }"
            link
          >

            <div class="py-1">
              <div class="d-flex justify-space-between mb-1">
                <h6 class="text-h6">{{ asset.name }}</h6>
                <span class="text-body-0">{{ asset.assets_count }} / {{ assets?.total_assets }}</span>
              </div>
              <VProgressLinear
                :model-value="(asset.assets_count / assets?.total_assets) * 100"
                color="primary"
                rounded
                height="6"
              />
            </div>
          </VListItem>
        </VList>
      </VCardText>
<!--    </VCardText>-->
  </VCard>
</template>

<style lang="scss" scoped>
.card-list {
  --v-card-list-gap: 16px;
}
</style>
