<script setup>
import { useChat } from '@/views/apps/chat/useChat'
import ChatContact from '@/views/chat/ChatContact.vue'
import { useChatStore } from '@/views/chat/useChatStore'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'

const props = defineProps({
  search: {
    type: String,
    required: true,
  },
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },
})

const emit = defineEmits([
  'openChatOfContact',
  'showUserProfile',
  'close',
  'update:search',
])

const { resolveAvatarBadgeVariant } = useChat()
const search = useVModel(props, 'search', emit)
const store = useChatStore()
</script>

<template>
  <!-- 👉 Chat list header -->
  <div
    v-if="store.profileUser"
    class="chat-list-header"
  >
    <VBadge
      dot
      location="bottom right"
      offset-x="3"
      offset-y="3"
      :color="resolveAvatarBadgeVariant(store.profileUser.status)"
      bordered
    >
      <VAvatar
        size="40"
        class="cursor-pointer"
        @click="$emit('showUserProfile')"
      >
        <VImg
          :src="store.profileUser?.avatar"
          :alt="store.profileUser?.full_name"
        />
      </VAvatar>
    </VBadge>

    <AppTextField
      id="search"
      v-model="search"
      placeholder="Search..."
      prepend-inner-icon="tabler-search"
      class="ms-4 me-1 chat-list-search"
    />

    <IconBtn
      v-if="$vuetify.display.smAndDown"
      @click="$emit('close')"
    >
      <VIcon
        icon="tabler-x"
        class="text-medium-emphasis"
      />
    </IconBtn>
  </div>
  <VDivider />

  <PerfectScrollbar
    tag="ul"
    class="d-flex flex-column gap-y-1 chat-contacts-list px-3 py-2 list-none"
    :options="{ wheelPropagation: false }"
  >
    <li class="list-none">
      <h5 class="chat-contact-header text-primary text-h5">
        Chats
      </h5>
    </li>

    <ChatContact
      v-for="contact in store.chatsContacts"
      :key="`chat-${contact.id}`"
      :user="contact"
      is-chat-contact
      @click="$emit('openChatOfContact', contact.id)"
    />

    <span
      v-show="!store.chatsContacts.length"
      class="no-chat-items-text text-disabled"
    >No chats found</span>

  </PerfectScrollbar>
</template>

<style lang="scss">
.chat-contacts-list {
  --chat-content-spacing-x: 16px;

  padding-block-end: 0.75rem;

  .chat-contact-header {
    margin-block: 0.5rem 0.25rem;
  }

  .chat-contact-header,
  .no-chat-items-text {
    margin-inline: var(--chat-content-spacing-x);
  }
}

.chat-list-search {
  .v-field--focused {
    box-shadow: none !important;
  }
}
</style>
