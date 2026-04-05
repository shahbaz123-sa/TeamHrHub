import { hasPermission } from "@/utils/permission";

const chatMenu = [];

if (hasPermission("chat.read")) {
  chatMenu.push({
    title: "Chat",
    icon: { icon: "tabler-message" },
    to: { name: "chat" },
  });
}

export default chatMenu;
