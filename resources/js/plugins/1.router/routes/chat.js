import { hasPermission } from "@/utils/permission";

const routes = [
  {
    name: "chat",
    showInNav: false,
    canAccess: () => hasPermission("chat.read"),
  },
];

export default routes;
