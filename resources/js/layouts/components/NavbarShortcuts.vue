<script setup>
const shortcuts = [
  // {
  //   icon: 'tabler-file-dollar',
  //   title: 'Invoice App',
  //   subtitle: 'Manage Accounts',
  //   to: { name: 'apps-invoice-list' },
  // },
  // {
  //   icon: 'tabler-user',
  //   title: 'Users',
  //   subtitle: 'Manage Users',
  //   to: { name: 'apps-user-list' },
  // },
  {
    icon: 'tabler-calendar',
    title: 'Attendance',
    subtitle: 'View attendance records',
    to: { name: 'hrm-attendance-list' },
  },
  {
    icon: 'tabler-users',
    title: 'Me & Reportees',
    subtitle: 'Employees reporting to you',
    to: { name: 'hrm-employee-list' },
  },
  {
    icon: 'tabler-device-desktop-analytics',
    title: 'Your Dashboard',
    subtitle: 'Analytics & overview',
    to: { name: 'dashboards-employee' },
  },
  {
    icon: 'tabler-settings',
    title: 'Settings',
    subtitle: 'Manage account settings',
    to: {
      name: 'my-profile',
    },
  },
]
</script>

<template>
  <Shortcuts :shortcuts="shortcuts" />
</template>
