import { hasPermission } from "@/utils/permission";

const routes = [
  {
    name: "crm-post-add",
    showInNav: false,
    canAccess: () => hasPermission("post.create"),
  },
  {
    name: "crm-post-edit-id",
    showInNav: false,
    canAccess: () => hasPermission("post.update"),
  },
  {
    name: "crm-post-details-id",
    showInNav: false,
    canAccess: () => hasPermission("post.read"),
  },
  {
    title: "Post List",
    name: "crm-post-list",
    to: { name: "crm-post-list" },
    canAccess: () => hasPermission("post.read"),
  },
  {
    title: "Post Tag",
    name: "crm-post-tag-list",
    to: { name: "crm-post-tag-list" },
    canAccess: () => hasPermission("post_tag.read"),
  },
  {
    title: "Post Category",
    name: "crm-post-category-list",
    to: { name: "crm-post-category-list" },
    canAccess: () => hasPermission("post_category.read"),
  },
];

export default routes;
