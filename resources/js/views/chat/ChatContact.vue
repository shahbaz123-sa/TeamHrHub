<script setup>
import { isEmpty } from '@/utils/helpers/str'
import { useChat } from '@/views/apps/chat/useChat'
import { useChatStore } from '@/views/chat/useChatStore'

const props = defineProps({
  isChatContact: {
    type: Boolean,
    required: false,
    default: false,
  },
  user: {
    type: null,
    required: true,
  },
})

const { resolveAvatarBadgeVariant } = useChat()
const store = useChatStore()

const isChatContactActive = computed(() => {
  const isActive = store.activeChat?.contact?.id === props.user?.id
  if (!props.isChatContact)
    return !store.activeChat?.chat && isActive
  
  return isActive
})
</script>

<template>
  <li
    :key="store.chatsContacts.length"
    class="chat-contact cursor-pointer d-flex align-center"
    :class="{ 'chat-contact-active': isChatContactActive }"
  >
    <VBadge
      dot
      location="bottom right"
      offset-x="3"
      offset-y="0"
      :color="resolveAvatarBadgeVariant(props.user?.status)"
      bordered
      :model-value="props.isChatContact"
    >
      <VAvatar
        size="40"
        :variant="!props.user?.avatar ? 'tonal' : undefined"
        :color="!props.user?.avatar ? resolveAvatarBadgeVariant(props.user?.status) : undefined"
      >
        <VImg
          v-if="props.user?.avatar"
          :src="props.user?.avatar"
          alt="John Doe"
        />
        <span v-else>{{ avatarText(props.user?.full_name) }}</span>
      </VAvatar>
    </VBadge>

    <div class="flex-grow-1 ms-4 overflow-hidden">
      <p class="text-base text-high-emphasis mb-0">
        {{ props.user?.full_name }}
      </p>
      <p class="mb-0 text-truncate text-body-2">
        {{ props.isChatContact && 'latest_message' in props.user ? props.user?.latest_message?.message : props.user?.email }}
      </p>
    </div>
    <div
      v-if="props.isChatContact && 'latest_message' in props.user"
      class="d-flex flex-column align-self-start"
    >
      <div class="text-body-2 text-disabled whitespace-no-wrap">
        {{ isEmpty(props.user?.latest_message?.updated_at)
          ? ""
          : formatDateToMonthShort(props.user?.latest_message?.updated_at) 
        }}
      </div>
      <VBadge
        v-if="props.user?.unseenMsgs"
        color="error"
        inline
        :content="props.user?.unseenMsgs"
        class="ms-auto"
      />
    </div>
  </li>
</template>

<style lang="scss">
@use "@core-scss/template/mixins" as templateMixins;
@use "@styles/variables/vuetify";
@use "@core-scss/base/mixins";
@use "vuetify/lib/styles/tools/states" as vuetifyStates;

.chat-contact {
  border-radius: vuetify.$border-radius-root;
  padding-block: 8px;
  padding-inline: 12px;

  @include mixins.before-pseudo;
  @include vuetifyStates.states($active: false);

  &.chat-contact-active {
    @include templateMixins.custom-elevation(var(--v-theme-primary), "sm");

    background: rgb(var(--v-theme-primary));
    color: #fff;

    --v-theme-on-background: #fff;
  }

  .v-badge--bordered .v-badge__badge::after {
    color: #fff;
  }
}
</style>
