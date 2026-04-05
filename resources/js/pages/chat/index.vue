<script setup>
import { themes } from '@/plugins/vuetify/theme'
import { isPdf } from '@/utils/helpers/file'
import { hasPermission } from '@/utils/permission'
import { useChat } from '@/views/apps/chat/useChat'
import ChatLeftSidebarContent from '@/views/chat/ChatLeftSidebarContent.vue'
import ChatLog from '@/views/chat/ChatLog.vue'
import { useChatStore } from '@/views/chat/useChatStore'
import pdf from '@images/icons/project-icons/pdf.png'
import { nextTick, watch } from 'vue'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import {
  useDisplay,
  useTheme,
} from 'vuetify'

definePage({ meta: { layoutWrapperClasses: 'layout-content-height-fixed' } })

// composables
const vuetifyDisplays = useDisplay()
const store = useChatStore()
const { isLeftSidebarOpen } = useResponsiveLeftSidebar(vuetifyDisplays.smAndDown)
const { resolveAvatarBadgeVariant } = useChat()

// Perfect scrollbar
const chatLogPS = ref()

// File Input
const refInputEl = ref()
const attachmentPreviewUrl = ref()
const handleFileUpload = (event) => {
  const file = event.target.files[0];

  if (!file) return;

  store.attachment = file

  event.target.value = null

  // console.log("File object:", store.attachment);
  // console.log("File name:", store.attachment.name);
  // console.log("File size:", store.attachment.size);
  // console.log("File type:", store.attachment.type);
  // console.log(URL.createObjectURL(file))
};
const removeFile = () => {
  nextTick(() => {
    store.attachment = null
    scrollToBottomInChatLog()
  })
}

const getAttachmentPreviewUrl = () => store.attachment ? URL.createObjectURL(store.attachment) : null

const scrollToBottomInChatLog = () => {
  const scrollEl = chatLogPS.value?.$el || chatLogPS.value
  scrollEl.scrollTop = scrollEl.scrollHeight
}

// Search query
const q = ref('')

// Open Sidebar in smAndDown when "start conversation" is clicked
const startConversation = () => {
  if (vuetifyDisplays.mdAndUp.value)
    return
  isLeftSidebarOpen.value = true
}

// Chat message
const msg = ref('')

const sendMessage = async () => {
  if (!msg.value) {
    $toast.error("Message is empty!")
    store.doingAction = false
    return;
  }
  await store.sendMsg(msg.value)

  // Reset message input
  msg.value = ''

  // Scroll to bottom
  nextTick(() => {
    scrollToBottomInChatLog()
  })
}

const openChatOfContact = async (userId, unseenMsgs = 0) => {
  await store.getChat(userId)

  // Reset message input
  msg.value = ''

  // Set unseenMsgs to 0
  const contact = store.chatsContacts.find(c => c.id === userId)
  if (contact) {
    contact.unseenMsgs = unseenMsgs

    setTimeout(() => {
      contact.unseenMsgs = 0
    }, 1000)
  }

  // if smAndDown =>  Close Chat & Contacts left sidebar
  if (vuetifyDisplays.smAndDown.value)
    isLeftSidebarOpen.value = false

  // Scroll to bottom
  nextTick(() => {
    scrollToBottomInChatLog()
  })
}

// User profile sidebar
const isUserProfileSidebarOpen = ref(false)

// Active chat user profile sidebar
const isActiveChatUserProfileSidebarOpen = ref(false)

// file input
const { name } = useTheme()

const chatContentContainerBg = computed(() => {
  let color = 'transparent'
  if (themes)
    color = themes?.[name.value].colors?.background
  
  return color
})

watch(q, async val => {
  await store.fetchChatsAndContacts(val)

  if(store.webhookPayload) {
    openChatOfContact(store.webhookPayload.senderId, 1)
    store.webhookPayload = null
  }
}, { immediate: true })

watch(
  [
    () => store.activeChat?.contact?.id,
    () => store.activeChat?.messages?.length,
  ],
  () => {
    nextTick(scrollToBottomInChatLog)
  }
)

watch(
  () => store.attachment,
  (val) => {
    attachmentPreviewUrl.value = getAttachmentPreviewUrl()
  }
)

const typing = ref(false)
watch(msg, (newVal) => {
  if(typing.value && !newVal) {
    typing.value = false
    useSocketEmit('stop-typing', {
      userId: store.profileUser?.employee_id,
      userType: "MANAGER",
      sessionId: store.activeChat?.contact?.latest_message?.sessionId ??
        store.activeChat?.contact?.latest_message?.session_id,
    })
    return
  } else if (!typing.value) {
    typing.value = true
    useSocketEmit('typing', {
      userId: store.profileUser?.employee_id,
      userType: "MANAGER",
      sessionId: store.activeChat?.contact?.latest_message?.sessionId ??
        store.activeChat?.contact?.latest_message?.session_id,
    })
  }
})
</script>

<template>
  <VLayout
    class="chat-app-layout"
    style="z-index: 0;"
  >
    <!-- 👉 Left sidebar   -->
    <VNavigationDrawer
      v-model="isLeftSidebarOpen"
      data-allow-mismatch
      absolute
      touchless
      location="start"
      width="370"
      :temporary="$vuetify.display.smAndDown"
      class="chat-list-sidebar"
      :permanent="$vuetify.display.mdAndUp"
    >
      <ChatLeftSidebarContent
        v-model:is-drawer-open="isLeftSidebarOpen"
        v-model:search="q"
        @open-chat-of-contact="openChatOfContact"
        @show-user-profile="isUserProfileSidebarOpen = true"
        @close="isLeftSidebarOpen = false"
      />

    </VNavigationDrawer>

    <!-- 👉 Chat content -->
    <VMain class="chat-content-container">
      <!-- 👉 Right content: Active Chat -->
      <div
        v-if="store.activeChat"
        class="d-flex flex-column h-100"
      >
        <!-- 👉 Active chat header -->
        <div class="active-chat-header d-flex align-center text-medium-emphasis bg-surface">
          <!-- Sidebar toggler -->
          <IconBtn
            class="d-md-none me-3"
            @click="isLeftSidebarOpen = true"
          >
            <VIcon icon="tabler-menu-2" />
          </IconBtn>

          <!-- avatar -->
          <div
            class="d-flex align-center cursor-pointer"
            @click="isActiveChatUserProfileSidebarOpen = true"
          >
            <VBadge
              dot
              location="bottom right"
              offset-x="3"
              offset-y="0"
              :color="resolveAvatarBadgeVariant(store.activeChat?.contact?.status)"
              bordered
            >
              <VAvatar
                size="40"
                :variant="!store.activeChat?.contact?.avatar ? 'tonal' : undefined"
                :color="!store.activeChat?.contact?.avatar ? resolveAvatarBadgeVariant(store.activeChat?.contact?.status) : undefined"
                class="cursor-pointer"
              >
                <VImg
                  v-if="store.activeChat?.contact?.avatar"
                  :src="store.activeChat?.contact?.avatar"
                  :alt="store.activeChat?.contact?.full_name"
                />
                <span v-else>{{ avatarText(store.activeChat?.contact?.full_name) }}</span>
              </VAvatar>
            </VBadge>

            <div class="flex-grow-1 ms-4 overflow-hidden">
              <div class="text-h6 mb-0 font-weight-regular">
                {{ store.activeChat?.contact?.full_name }}
              </div>
              <p class="text-truncate mb-0 text-body-2">
                {{ store.activeChat?.contact?.email }}
              </p>
            </div>
          </div>

          <VSpacer />
        </div>

        <VDivider />

        <!-- Chat log -->
        <PerfectScrollbar
          :key="store.activeChat?.contact?.id"
          ref="chatLogPS"
          tag="ul"
          :options="{ wheelPropagation: false }"
          class="flex-grow-1"
        >
          <ChatLog />
        </PerfectScrollbar>

        <!-- Message form -->
        <VForm
          class="chat-log-message-form mb-5 mx-5"
          @submit.prevent="sendMessage"
          v-if="hasPermission('chat.create')"
        >
          <div
            v-if="attachmentPreviewUrl"
            class="msg-attachment mb-3"
          >
            
            <VImg
              :src="isPdf(store.attachment.name) ? pdf : attachmentPreviewUrl"
              :width="isPdf(store.attachment.name) ? 100 : 150"
              max-height="150"
              rounded
            >
              <template #default>
                <div
                  :width="isPdf(store.attachment.name) ? 100 : 150"
                  class="text-center"
                >
                  <VBtn
                    size="x-small"
                    class="mt-2"
                    variant="elevated"
                    color="danger"
                    @click="removeFile"
                  >
                    X
                  </VBtn>
                </div>
              </template>
            </VImg>
          </div>
        
          <VTextField
            :key="store.activeChat?.contact?.id"
            v-model="msg"
            variant="solo"
            density="default"
            class="chat-message-input"
            placeholder="Type your message..."
            autofocus
          >
            <template #append-inner>
              <div class="d-flex gap-1">
                <IconBtn @click="refInputEl?.click()">
                  <VIcon
                    icon="tabler-paperclip"
                    size="22"
                  />
                </IconBtn>

                <div class="d-none d-md-block">
                  <VBtn
                    append-icon="tabler-send"
                    @click="sendMessage"
                    :loading="store.doingAction"
                  >
                    Send
                  </VBtn>
                </div>
              </div>
            </template>
          </VTextField>

          <input
            ref="refInputEl"
            type="file"
            name="file"
            hidden
            @change="handleFileUpload"
          >
        </VForm>
      </div>

      <!-- 👉 Start conversation -->
      <div
        v-else
        class="d-flex h-100 align-center justify-center flex-column"
      >
        <VAvatar
          size="98"
          variant="tonal"
          color="primary"
          class="mb-4"
        >
          <VIcon
            size="50"
            class="rounded-0"
            icon="tabler-message-2"
          />
        </VAvatar>
        <VBtn
          v-if="$vuetify.display.smAndDown"
          rounded="pill"
          @click="startConversation"
        >
          Start Conversation
        </VBtn>

        <p
          v-else
          style="max-inline-size: 40ch; text-wrap: balance;"
          class="text-center text-disabled"
        >
          Start connecting with the people by selecting one of the contact on left
        </p>
      </div>
    </VMain>
  </VLayout>
</template>

<style lang="scss">
@use "@styles/variables/vuetify";
@use "@core-scss/base/mixins";
@use "@layouts/styles/mixins" as layoutsMixins;

// Variables
$chat-app-header-height: 76px;

// Placeholders
%chat-header {
  display: flex;
  align-items: center;
  min-block-size: $chat-app-header-height;
  padding-inline: 1.5rem;
}

.chat-start-conversation-btn {
  cursor: default;
}

.chat-app-layout {
  border-radius: vuetify.$card-border-radius;

  @include mixins.elevation(vuetify.$card-elevation);

  $sel-chat-app-layout: &;

  @at-root {
    .skin--bordered {
      @include mixins.bordered-skin($sel-chat-app-layout);
    }
  }

  .active-chat-user-profile-sidebar,
  .user-profile-sidebar {
    .v-navigation-drawer__content {
      display: flex;
      flex-direction: column;
    }
  }

  .chat-list-header,
  .active-chat-header {
    @extend %chat-header;
  }

  .chat-list-sidebar {
    .v-navigation-drawer__content {
      display: flex;
      flex-direction: column;
    }
  }
}

.chat-content-container {
  /* stylelint-disable-next-line value-keyword-case */
  background-color: v-bind(chatContentContainerBg);

  // Adjust the padding so text field height stays 48px
  .chat-message-input {
    .v-field__input {
      font-size: 0.9375rem !important;
      line-height: 1.375rem !important;
      padding-block: 0.6rem 0.5rem;
    }

    .v-field__append-inner {
      align-items: center;
      padding-block-start: 0;
    }

    .v-field--appended {
      padding-inline-end: 8px;
    }
  }
}

.chat-user-profile-badge {
  .v-badge__badge {
    /* stylelint-disable liberty/use-logical-spec */
    min-width: 12px !important;
    height: 0.75rem;
    /* stylelint-enable liberty/use-logical-spec */
  }
}
</style>
