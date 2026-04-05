<script setup>
import DocumentImageViewer from "@/components/common/DocumentImageViewer.vue";
import { onMounted } from "vue";
import { useRoute } from "vue-router";

const route = useRoute();
const supplierId = route.params.id;

const supplier = ref(null);
const loading = ref(false);

const fetchSupplier = async () => {
  loading.value = true;
  try {
    const { data } = await useApi(createUrl(`/suppliers/${supplierId}`));
    supplier.value = data.value?.data || null;
  } catch (error) {
    console.error("Error fetching supplier:", error);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchSupplier();
});
</script>

<template>
  <div
    v-if="loading"
    class="d-flex justify-center align-center min-height-screen"
  >
    <VProgressCircular indeterminate />
  </div>

  <div v-else-if="supplier" class="space-y-6">
    <VRow>
      <VCol cols="8">
        <VCard title="Supplier Information">
          <VCardText>
            <VRow>
              <VCol cols="12" sm="6">
                <div>
                  <div class="text-subtitle-2 font-weight-bold mb-1">
                    Supplier Name
                  </div>
                  <div class="text-base">{{ supplier.name }}</div>
                </div>
              </VCol>

              <VCol cols="12" sm="6">
                <div>
                  <div class="text-subtitle-2 font-weight-bold mb-1">Email</div>
                  <div class="text-base">{{ supplier.email || "-" }}</div>
                </div>
              </VCol>

              <VCol cols="12" sm="6">
                <div>
                  <div class="text-subtitle-2 font-weight-bold mb-1">Phone Number</div>
                  <div class="text-base">{{ supplier.phone || "-" }}</div>
                </div>
              </VCol>

              <VCol cols="12" sm="6">
                <div>
                  <div class="text-subtitle-2 font-weight-bold mb-1">Supplier Type</div>
                  <div class="text-base">{{ supplier.type || "-" }}</div>
                </div>
              </VCol>

              <VCol cols="12" sm="6">
                <div>
                  <div class="text-subtitle-2 font-weight-bold mb-1">Brand</div>
                  <div class="text-base">{{ supplier.brand || "-" }}</div>
                </div>
              </VCol>

              <VCol cols="12" sm="6">
                <div>
                  <div class="text-subtitle-2 font-weight-bold mb-1">Commodity</div>
                  <div class="text-base">{{ supplier.product_category || "-" }}</div>
                </div>
              </VCol>

              <VCol cols="12" sm="12">
                <div>
                  <div class="text-subtitle-2 font-weight-bold mb-1">
                    Address
                  </div>
                  <div class="text-base">{{ supplier.address || "-" }}</div>
                </div>
              </VCol>

            </VRow>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="4">
        <VCard>
          <VCardTitle class="pt-7 pl-6 mb-3">Supporting Documents</VCardTitle>
          <VCardText>
            <VRow>
              <VCol cols="12">

                <div class="pa-2 border-sm rounded-lg">
                  <DocumentImageViewer pdf-icon="/images/icons/document.png" :src="supplier.incorporation_letter" v-if="supplier.incorporation_letter">
                    <template #content>
                      <div class="d-flex align-center">
                        <VAvatar size="40" :rounded="false">
                          <VImg src="/images/icons/document.png" :rounded="false" />
                        </VAvatar>
                        <div class="ml-4">
                          <p class="text-body-1 mb-0">Incorporation Letter</p>
                        </div>
                      </div>
                    </template>
                  </DocumentImageViewer>
                </div>

              </VCol>
              <VCol cols="12">

                <div class="pa-2 border-sm rounded-lg">
                  <DocumentImageViewer pdf-icon="/images/icons/document.png" :src="supplier.request_letterhead" v-if="supplier.request_letterhead">
                    <template #content>
                      <div class="d-flex align-center">
                        <VAvatar size="40" :rounded="false">
                          <VImg src="/images/icons/document.png" :rounded="false" />
                        </VAvatar>
                        <div class="ml-4">
                          <p class="text-body-1 mb-0">Request Letterhead</p>
                        </div>
                      </div>
                    </template>
                  </DocumentImageViewer>
                </div>

              </VCol>
            </VRow>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>

  <div v-else class="d-flex justify-center align-center min-height-screen">
    <VAlert type="warning"> Supplier not found </VAlert>
  </div>
</template>
