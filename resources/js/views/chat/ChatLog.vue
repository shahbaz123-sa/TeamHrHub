<script setup>
import AttachmentPreview from '@/components/chat/AttachmentPreview.vue';
import { useChatStore } from '@/views/chat/useChatStore';

const store = useChatStore()

const contact = computed(() => ({
  id: store.activeChat?.contact.id,
  avatar: store.activeChat?.contact.avatar,
}))

const showOnLeft = senderId => senderId === contact.value.id || senderId !== store.profileUser?.employee_id

const getAvatar = msgGroup => {
  if (msgGroup.senderId === contact.value.id)
    return contact.value.avatar
  else if(msgGroup.senderId === store.profileUser?.id)
    return store.profileUser?.avatar
  else
    return msgGroup?.avatar
}

const resolveFeedbackIcon = feedback => {
  if (feedback.isSeen)
    return {
      icon: 'tabler-checks',
      color: 'success',
    }
  else if (feedback.isDelivered)
    return {
      icon: 'tabler-checks',
      color: undefined,
    }
  else
    return {
      icon: 'tabler-check',
      color: undefined,
    }
}

const msgGroups = computed(() => {
  let messages = []
  const _msgGroups = []
  if (store.activeChat.messages && store.activeChat.messages.length > 0) {
    messages = store.activeChat.messages
    let msgSenderId = messages[0].sender_id
    let msgGroup = {
      senderId: msgSenderId,
      messages: [],
    }
    messages.forEach((msg, index) => {
      if (msgSenderId === msg.sender_id) {
        msgGroup.avatar = msg.sender_manager?.avatar
        msgGroup.messages.push({
          message: msg.message,
          sender: msg.sender_manager,
          time: msg.created_at,
          feedback: "",
          attachment_urls: msg.attachment_urls,
        })
      } else {
        msgSenderId = msg.sender_id
        _msgGroups.push(msgGroup)
        msgGroup = {
          senderId: msg.sender_id,
          avatar: msg.sender_manager?.avatar,
          messages: [{
            message: msg.message,
            sender: msg.sender_manager,
            time: msg.created_at,
            feedback: "",
            attachment_urls: msg.attachment_urls,
          }],
        }
      }
      if (index === messages.length - 1)
        _msgGroups.push(msgGroup)
    })
  }
  
  return _msgGroups
})
</script>

<template>
  <div class="chat-log pa-6">
    <div
      v-for="(msgGrp, index) in msgGroups"
      :key="msgGrp.senderId + String(index)"
      class="chat-group d-flex align-start"
      :class="[{
        'flex-row-reverse': !showOnLeft(msgGrp.senderId),
        'mb-6': msgGroups.length - 1 !== index,
      }]"
    >
      <div
        class="chat-avatar"
        :class="showOnLeft(msgGrp.senderId) ? 'me-4' : 'ms-4'"
      >
        <VAvatar size="32">
          <VImg :src="getAvatar(msgGrp)" />
        </VAvatar>
      </div>
      <div
        class="chat-body d-inline-flex flex-column"
        :class="showOnLeft(msgGrp.senderId) ? 'align-start' : 'align-end'"
      >
        <div
          v-for="(msgData, msgIndex) in msgGrp.messages"
          :key="msgData.time"
          :class="msgGrp.messages.length - 1 !== msgIndex ? 'mb-2' : 'mb-1'"
        >
          <div
            class="chat-content py-2 px-4 elevation-2"
            style="background-color: rgb(var(--v-theme-surface));"
            :class="[
              showOnLeft(msgGrp.senderId) ? 'chat-left' : 'bg-primary text-white chat-right',
            ]"
          >
          <div
            v-for="(attachment) in msgData.attachment_urls"
            :key="msgData.id"
          >
            <AttachmentPreview :url="attachment" />

            <VDivider class="mt-1 mb-1" color="warning"/>
          </div>

            <p class="mb-0 text-base">
              {{ msgData.message }}
            </p>
          </div>
          <span v-if="showOnLeft(msgGrp.senderId) && msgData.sender?.name" class="text-sm ms-2 text-disabled">Sent By: {{ msgData.sender?.name }}</span>
        </div>
        <div :class="{ 'text-right': !showOnLeft(msgGrp.senderId) }">
          <VIcon
            v-if="!showOnLeft(msgGrp.senderId)"
            size="16"
            :color="resolveFeedbackIcon(msgGrp.messages[msgGrp.messages.length - 1].feedback).color"
          >
            {{ resolveFeedbackIcon(msgGrp.messages[msgGrp.messages.length - 1].feedback).icon }}
          </VIcon>
          <span class="text-sm ms-2 text-disabled">{{ formatDate(msgGrp.messages[msgGrp.messages.length - 1].time, { hour: 'numeric', minute: 'numeric' }) }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<style lang=scss>
.chat-log {
  .chat-body {
    max-inline-size: calc(100% - 6.75rem);

    .chat-content {
      border-end-end-radius: 6px;
      border-end-start-radius: 6px;

      p {
        overflow-wrap: anywhere;
      }

      &.chat-left {
        border-start-end-radius: 6px;
      }

      &.chat-right {
        border-start-start-radius: 6px;
      }
    }
  }
}
</style>
