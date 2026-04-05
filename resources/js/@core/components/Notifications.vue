<script setup>
import { PerfectScrollbar } from 'vue3-perfect-scrollbar';
import axios from 'axios';
import DocumentImageViewer from "@/components/common/DocumentImageViewer.vue";
import { useRouter } from 'vue-router'

const authUser = useCookie('userData').value
const router = useRouter()

const props = defineProps({
  notifications: {
    type: Array,
    required: true,
  },
  badgeProps: {
    type: Object,
    required: false,
    default: undefined,
  },
  location: {
    type: null,
    required: false,
    default: 'bottom end',
  },
})

// 'read',
//   'unread',
const emit = defineEmits([
  'remove',
  'click:notification',
  'updated',
])

// Keep a local reactive copy so UI updates immediately without requiring the parent to refresh props
const localNotifications = ref(Array.isArray(props.notifications) ? [...props.notifications] : [])

watch(() => props.notifications, (val) => {
  localNotifications.value = Array.isArray(val) ? [...val] : []
}, { deep: true })

const onNewNotification = (newNotification) => {
  if (newNotification) {
    console.log(newNotification)
    localNotifications.value.unshift(newNotification);
  }
};

defineExpose({
  onNewNotification,
});
// true when ALL notifications are read (read_at !== null)
const allRead = computed(() => {
  if (!localNotifications.value || localNotifications.value.length === 0) return false
  return localNotifications.value.every(item => item.read_at)
})

const markAllReadOrUnread = async () => {
  const allNotificationsIds = localNotifications.value.map(item => item.id)
  const shouldMarkRead = !allRead.value
  // optimistic update
  const prev = [...localNotifications.value]
  localNotifications.value = localNotifications.value.map(n => ({ ...n, read_at: shouldMarkRead ? new Date().toISOString() : null }))
  try {
    if (shouldMarkRead) {
      await $api('/notifications/mark-read', { ids: allNotificationsIds, method: 'POST' })
      // emit('read', allNotificationsIds)
    } else {
      await $api('/notifications/mark-unread', { ids: allNotificationsIds, method: 'POST' })
      // emit('unread', allNotificationsIds)
    }
    emit('updated')
  } catch (error) {
    // revert optimistic update on error
    localNotifications.value = prev
    console.error('Failed to update notifications:', error)
  }
};

const totalUnseenNotifications = computed(() => {
  return localNotifications.value?.filter(item => !item.read_at).length || 0;
})

const toggleReadUnread = async (isSeen, Id) => {
  // optimistic update
  const prev = [...localNotifications.value]
  try {
    if (isSeen) {
      localNotifications.value = localNotifications.value.map(n => n.id === Id ? ({ ...n, read_at: null }) : n)
      await $api('/notifications/mark-unread', { ids: [Id], method: 'POST' })
      // emit('unread', [Id])
    } else {
      localNotifications.value = localNotifications.value.map(n => n.id === Id ? ({ ...n, read_at: new Date().toISOString() }) : n)
      await $api('/notifications/mark-read', { ids: [Id], method: 'POST' })
      // emit('read', [Id])
    }
    emit('updated')
  } catch (error) {
    // revert on error
    localNotifications.value = prev
    console.error('Failed to toggle notification status:', error)
  }
};
const handleNotificationClick = (notification) => {
  if (notification.url) {
    router.push(notification.url)
  }
  emit('click:notification', notification)
}

const removeNotification = async (Id) => {
  try {
    await axios.delete(`/api/notifications/${Id}`)
    // remove locally
    localNotifications.value = localNotifications.value.filter(n => n.id !== Id)
    emit('remove', Id)
    emit('updated')
  } catch (error) {
    console.error('Failed to remove notification:', error)
  }
};

function timeAgo(input) {
  if (!input) return ''

  const date = input instanceof Date ? input : new Date(input)
  if (isNaN(date.getTime())) return ''

  const diffMs = Date.now() - date.getTime()

  // future timestamps or same moment
  if (diffMs < 60 * 1000) return 'Now'

  const minutes = Math.floor(diffMs / (60 * 1000))
  if (minutes < 60) return `${minutes} min ago`

  const hours = Math.floor(diffMs / (60 * 60 * 1000))
  if (hours < 24) return `${hours} hour${hours === 1 ? '' : 's'} ago`

  const days = Math.floor(diffMs / (24 * 60 * 60 * 1000))
  if (days < 30) return `${days} day${days === 1 ? '' : 's'} ago`

  const months = Math.floor(days / 30)
  if (months < 12) return `${months} month${months === 1 ? '' : 's'} ago`

  const years = Math.floor(days / 365)
  return `${years} year${years === 1 ? '' : 's'} ago`
}

</script>

<template>
  <IconBtn id="notification-btn" :key="totalUnseenNotifications">
    <VBadge
      v-bind="props.badgeProps"
      :model-value="totalUnseenNotifications"
      :color="allRead ? 'success' : 'primary'"
      dot
      offset-x="2"
      offset-y="3"
    >
      <VIcon icon="tabler-bell" />
    </VBadge>

    <VMenu
      activator="parent"
      width="380px"
      :location="props.location"
      offset="12px"
      :close-on-content-click="false"
    >
      <VCard class="d-flex flex-column">
        <!-- 👉 Header -->
        <VCardItem class="notification-section">
          <VCardTitle class="text-h6">
            Notifications
          </VCardTitle>

          <template #append>
            <VChip
              v-show="localNotifications && localNotifications.length > 0"
              size="small"
              color="primary"
              class="me-2"
            >
              {{ totalUnseenNotifications }} New
            </VChip>
            <IconBtn
              v-show="localNotifications && localNotifications.length > 0"
              size="34"
              @click="markAllReadOrUnread"
            >
              <VIcon
                size="20"
                color="high-emphasis"
                :icon="!allRead ? 'tabler-mail' : 'tabler-mail-opened' "
              />

              <VTooltip
                activator="parent"
                location="start"
              >
                {{ allRead ? 'Mark all as unread' : 'Mark all as read' }}
              </VTooltip>
            </IconBtn>
          </template>
        </VCardItem>

        <VDivider />

        <!-- 👉 Notifications list -->
        <PerfectScrollbar
          :options="{ wheelPropagation: false }"
          style="max-block-size: 23.75rem;"
        >
          <VList class="notification-list rounded-0 py-0">
            <template
              v-for="(notification, index) in localNotifications"
              :key="notification.id || index"
            >
              <VDivider v-if="index > 0" />
              <VListItem
                link
                lines="one"
                min-height="66px"
                class="list-item-hover-class"
                @click="handleNotificationClick(notification)"
              >
<!--                @click="$emit('click:read', notification)"-->
                <!-- Slot: Prepend -->
                <!-- Handles Avatar: Image, Icon, Text -->
                <div class="d-flex align-start gap-3">
                  <VAvatar
                    size="34"
                    :color="!notification.sender?.avatar_url ? 'primary' : undefined"
                    :variant="!notification.sender?.avatar_url ? 'tonal' : undefined"
                  >
                    <DocumentImageViewer v-if="notification.sender?.avatar_url" :type="'avatar'" :src="notification.sender?.avatar_url" :pdf-title="notification.sender?.name" />
                    <span v-else>{{ notification.employee?.name ? notification.employee.name.charAt(0) : '—' }}</span>
                  </VAvatar>

                  <div>
                    <p class="text-sm font-weight-medium mb-1">
                      {{ notification.title }}
<!--                      {{localNotifications}}-->
                    </p>
                    <p
                      class="text-body-2 mb-2"
                      style=" letter-spacing: 0.4px !important; line-height: 18px;"
                    >
                      <span v-if="authUser.employee_id === notification.receiver_id">You</span> {{ notification.data }}
                    </p>
                    <p
                      class="text-sm text-disabled mb-0"
                      style=" letter-spacing: 0.4px !important; line-height: 18px;"
                    >
                      {{ timeAgo(notification.created_at) }}
                    </p>
                  </div>
                  <VSpacer />

                  <div class="d-flex flex-column align-end">
                    <VIcon
                      v-if="!notification.read_at"
                      size="10"
                      icon="tabler-circle-filled"
                      color="primary"
                      class="mb-2"
                      @click.stop="toggleReadUnread(!!notification.read_at, notification.id)"
                    />

<!--                    <VIcon-->
<!--                      size="20"-->
<!--                      icon="tabler-x"-->
<!--                      class="visible-in-hover"-->
<!--                      @click="removeNotification(notification.id)"-->
<!--                    />-->
                  </div>
                </div>
              </VListItem>
            </template>

            <VListItem
              v-show="!localNotifications || localNotifications.length === 0"
              class="text-center text-medium-emphasis"
              style="block-size: 56px;"
            >
              <VListItemTitle>No Notification Found!</VListItemTitle>
            </VListItem>
          </VList>
        </PerfectScrollbar>

      </VCard>
    </VMenu>
  </IconBtn>
</template>

<style lang="scss">
.notification-section {
  padding-block: 0.75rem;
  padding-inline: 1rem;
}

.list-item-hover-class {
  .visible-in-hover {
    display: none;
  }

  &:hover {
    .visible-in-hover {
      display: block;
    }
  }
}

.notification-list.v-list {
  .v-list-item {
    border-radius: 0 !important;
    margin: 0 !important;
    padding-block: 0.75rem !important;
  }
}

// Badge Style Override for Notification Badge
.notification-badge {
  .v-badge__badge {
    /* stylelint-disable-next-line liberty/use-logical-spec */
    min-width: 18px;
    padding: 0;
    block-size: 18px;
  }
}
</style>
