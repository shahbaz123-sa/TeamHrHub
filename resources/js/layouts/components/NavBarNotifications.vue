<script setup>
import { inject, onMounted, onUnmounted, ref } from "vue";

const emit = defineEmits(['new-notification']);
const notifications = ref([]);
const echo = inject('socket');
const notificationsRef = ref(null);

const removeNotification = async (notificationId) => {
  try {
    await $api(`/notifications/${notificationId}`, { method: 'DELETE' });
    notifications.value = notifications.value.filter(n => n.id !== notificationId);
  } catch (error) {
    console.error('Failed to delete notification:', error);
  }
};

const handleNotificationClick = (notification) => {
  if (!notification.is_read) {
    // markRead(notification.id);
  }
};

const fetchNotifications = async () => {
  try {
    const response = await $api('/notifications');
    notifications.value = response;
    // console.log('response', response);
  } catch (error) {
    console.error('Failed to fetch notifications:', error);
  }
};

onMounted(() => {
  fetchNotifications();

  const user = useCookie('userData').value;
  if (user && user.id) {
    const channelName = `user.${user.id}`;
    const channel = echo.private(channelName);

    channel
      .listen('.notification.created', (data) => {
        const newNotification = data.notification || data;
        if (newNotification) {
          console.log('newNotification', newNotification);
          notifications.value.unshift(newNotification);
          if (notificationsRef.value) {
            notificationsRef.value.onNewNotification(newNotification);
          }
          $toast.success(newNotification.data);
        }
      })
      .error((error) => {
        console.error(`Subscription error on channel ${channelName}:`, error);
      });
  } else {
    console.warn('User not found for socket subscription.');
  }
});

onUnmounted(() => {
  const user = useCookie('userData').value;
  if (user && user.id) {
    const channelName = `user.${user.id}`;
    echo.leave(channelName);
  }
});
</script>

<template>
  <Notifications
    ref="notificationsRef"
    :notifications="notifications"
    @remove="removeNotification"
    @click:notification="handleNotificationClick"
  />
</template>

<style>
.dropdown {
  position: absolute;
  background: white;
  border: 1px solid #ccc;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}
</style>
