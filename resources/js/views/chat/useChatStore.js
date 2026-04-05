import { useSocketEmit, useSocketOn } from "@/composables/useSocket";
import { isEmpty } from "@/utils/helpers/str";
import { hasPermission } from "@/utils/permission";
import { $toast } from "@/utils/toast";
import { useRouter } from "vue-router";

export const useChatStore = defineStore("chat", {
  // ℹ️ arrow function recommended for full type inference
  state: () => ({
    contacts: [],
    chatsContacts: [],
    profileUser: useCookie("userData").value,
    activeChat: null,
    activeChatContact: null,
    accessToken: useCookie("accessToken").value,
    doingAction: false,
    router: useRouter(),
    webhookPayload: null,
    attachment: null,
  }),
  actions: {
    async fetchChatsAndContacts(q) {
      const { data, meta } = await $api("/chats", {
        query: {
          q,
          page: 1,
          per_page: -1,
        },
        method: "GET",
        headers: { Authorization: `Bearer ${this.accessToken}` },
      });

      this.chatsContacts = (data ?? [])
        .sort((a, b) => {
          const aTime = a.latest_message?.created_at;
          const bTime = b.latest_message?.created_at;
          if (aTime && bTime) {
            return new Date(bTime) - new Date(aTime);
          }
          return 0;
        })
        .map((chat) => {
          if (
            hasPermission("chat.create") &&
            !isEmpty(chat?.latest_message?.room)
          ) {
            console.log(
              "Joining room for chat contact: ",
              chat.id,
              "Room:",
              chat?.latest_message?.room,
            );

            useSocketEmit("join-room", {
              roomId: chat?.latest_message?.room,
              sessionId:
                chat?.latest_message?.sessionId ??
                chat?.latest_message?.session_id,
              userType: "MANAGER",
              userId: this.profileUser?.employee_id,
            });
          }

          return {
            id: chat.id,
            email: chat.email,
            latest_message: chat.latest_message,
            unseenMsgs: 0,
            status: "online",
            full_name:
              chat.type === "B2B"
                ? chat.company?.company_name
                : chat.profile?.full_name,
            avatar:
              chat.type === "B2B"
                ? chat.company?.company_image
                : chat.profile?.profile_image,
          };
        });

      // this.contacts = contacts;
    },
    async getChat(userId) {
      const { data } = await $api("/chat/messages", {
        query: {
          user_id: userId,
          page: 1,
          per_page: -1,
        },
        method: "GET",
        headers: { Authorization: `Bearer ${this.accessToken}` },
      });

      this.activeChat = {
        messages: data,
        contact: this.chatsContacts.find((c) => c.id === userId),
      };
    },
    async sendMsg(message) {
      this.doingAction = true;

      const senderId = this.profileUser?.employee_id ?? 0;
      const sessionId =
        this.activeChat?.contact?.latest_message.sessionId ??
        this.activeChat?.contact?.latest_message.session_id;

      try {
        var attachmentUrl = "";
        if (this.attachment) {
          const formData = new FormData();
          formData.append("attachment", this.attachment);
          formData.append("session_id", sessionId);
          formData.append("sender_id", senderId);

          const response = await $api("/chat/messages/upload-attachment", {
            body: formData,
            method: "POST",
            headers: { Authorization: `Bearer ${this.accessToken}` },
          });

          if (response.url) {
            this.attachment = null;
            attachmentUrl = [
              {
                key: response.key,
                mimeType: response.mimeType,
                originalName: response.originalName,
              },
            ];
          }
        }

        useSocketEmit("chat-message", {
          sessionId,
          roomId: this.activeChat?.contact?.latest_message?.room,
          senderType: "MANAGER",
          senderId,
          message,
          receiverId: this.activeChat?.contact.id,
          attachments: attachmentUrl,
        });
      } catch (error) {
        $toast.error("Failed to send message.");
      } finally {
        this.doingAction = false;
      }
    },
    async handleMessageReceived(payload) {
      this.webhookPayload = payload;

      const contact = this.chatsContacts.find(
        (c) => c.latest_message?.room === payload?.roomId,
      );

      if (this.activeChat?.contact?.latest_message?.room === payload?.roomId) {
        await this.getChat(this.activeChat.contact.id);
      } else {
        const managers = await $api(`/employees/rfq-managers`, {
          method: "GET",
          headers: { Authorization: `Bearer ${this.accessToken}` },
        });

        if (
          (managers.data ?? []).filter(
            (mgr) => mgr.id === this.profileUser?.employee_id,
          ).length > 0
        ) {
          $toast.warning("New message received. Click to open chat.", {
            pauseOnHover: true,
            duration: 10000,
            onClick: () => {
              this.router.push({ name: "chat" });
            },
          });
        }

        if (contact) {
          contact.unseenMsgs++;
        }
      }

      if (contact) {
        contact.latest_message = {
          attachments: [],
          created_at: payload.createdAt,
          id: payload.id,
          message: payload.message,
          receiverId: payload.receiverId,
          room: payload.roomId,
          senderId: payload.senderId,
          senderType: payload.senderType,
          sessionId: payload.sessionId,
          updated_at: payload.createdAt,
        };
      }
    },
    async registerChatWebhooks() {
      if (!hasPermission("chat.create")) {
        return;
      }

      const userData = useCookie("userData");
      if (userData.value) {
        // useSocketOn("joined-room", (data) => {
        //   console.log("Joined room:", data);
        // });

        useSocketOn("new-room", async (data) => {
          console.log("new-room", data);
          await this.fetchChatsAndContacts();

          const contact = this.chatsContacts.find(
            (c) => c.latest_message?.room === data?.roomId,
          );

          if (contact) {
            contact.unseenMsgs++;
          }

          useSocketEmit("join-room", {
            roomId: data?.roomId,
            sessionId: data?.sessionId,
            userType: "MANAGER",
            userId: this.profileUser?.employee_id,
          });
        });

        useSocketOn("chat-message", async (data) => {
          console.log("Received new message:", data);

          if (
            !this.chatsContacts.find(
              (c) => c.latest_message?.room === data?.roomId,
            )
          ) {
            await this.fetchChatsAndContacts();
          }

          this.handleMessageReceived(data);
        });
      }
    },
  },
});
