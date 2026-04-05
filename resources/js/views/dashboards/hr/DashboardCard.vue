<script setup>

const props = defineProps({
  title: {
    type: String,
    default: ""
  },
  total: {
    type: Number,
    default: 0
  },
  color: {
    type: String,
    default: 'primary'
  },
  link: {          // new prop for route
    type: String,
    default: ''
  },
  query: {          // new prop for route
    type: String,
    default: ''
  }
})

const router = useRouter()

const goToLink = () => {
  if (props.link) {
    router.push(props.link)  // go to fixed link
  } else if (props.query) {
    router.push({
      path: '/hrm/attendance/list',
      query: { status: [props.query] }
    })
  }
}

</script>

<template>
  <VCard :height="154">
    <VCardItem class="pb-3">
      <h3>{{ props.title }}</h3>
    </VCardItem>

    <VCardText class="pt-1" style="position: absolute; inset-block-end: 0;">
      <VBtn
        class="mb-4"
        size="small"
        :color="props.color"
        @click="goToLink(props.title)"
      >{{ props.total }}
      </VBtn>
    </VCardText>
  </VCard>
</template>
