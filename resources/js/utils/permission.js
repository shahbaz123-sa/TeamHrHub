const authUserPermissions = JSON.parse(
  localStorage.getItem("loggedInUserPermission") || "[]"
);

const userData = useCookie("userData");
const authUserRoles = userData.value?.roles?.map((role) => role.name) || [];

// check if auth user has specific role
export function hasRole(role, userRoles = null) {
  if (!userRoles) userRoles = authUserRoles;

  return userRoles.includes(role);
}

// check if auth user has specific role
export function hasOnlyRole(role, userRoles = null) {
  if (!userRoles) userRoles = authUserRoles;

  return userRoles.every((r) => r === role);
}

// check if auth user has specific permission
export function hasPermission(permission, userPermission = null) {
  if (!userPermission) userPermission = authUserPermissions;

  if (userPermission.includes(permission)) return true;

  permission = permission?.toLowerCase();
  const parts = permission?.split(".");
  if (parts?.length !== 2) return false;

  const wildcard = `${parts[0]}.*`;

  return userPermission.includes(wildcard);
}

export function isSuperAdmin() {
  return hasRole("Super Admin");
}
