<script setup>
import { convertTo12HourFormat } from "@/utils/helpers/date";
import dayGridPlugin from "@fullcalendar/daygrid";
import interactionPlugin from "@fullcalendar/interaction";
import timeGridPlugin from "@fullcalendar/timegrid";
import FullCalendar from "@fullcalendar/vue3";
import axios from "axios";
import { computed, onMounted, onUnmounted, ref } from "vue";
import { useRouter } from 'vue-router';

const router = useRouter();

definePage({
  meta: {
    layout: "default",
    requiresAuth: true,
  },
});

// Data
const userData = useCookie("userData");
// const employeeName = computed(() => userData.value?.name || "Employee");
const currentDateTime = ref("");
const attendance = ref(null);
const officeHours = ref("");
const isLoading = ref(true);
const canCheckin = ref(false);
const accessToken = useCookie("accessToken");
const checkingIn = ref(false);
const checkingOut = ref(false);

// Attendance statistics data
const attendanceStats = ref({
  totalEarlyCheckIn: 0,
  totalLateCheckIn: 0,
  totalEarlyCheckOut: 0,
  totalLateCheckOut: 0,
  totalPresents: 0,
  totalLeaves: 0,
  totalAbsent: 0,
});

// Filter options
const selectedMonth = ref(new Date().getMonth() + 1); // Current month (1-12)
const selectedYear = ref(new Date().getFullYear()); // Current year
const monthOptions = ref([]);
const yearOptions = ref([]);

// Location modal
const isLocationModalOpen = ref(false);
const locationInput = ref("");
const pendingAction = ref(""); // 'checkin' or 'checkout'

// Current week attendance table
const currentWeekAttendances = ref([]);
const attendanceTableLoading = ref(false);
const attendanceTableOptions = ref({
  itemsPerPage: 7,
  selectedRows: [],
  page: 1,
});
const attendanceTableHeaders = [
  { title: "Date", key: "date" },
  { title: "Check In", key: "check_in" },
  { title: "Check Out", key: "check_out" },
  { title: "Status", key: "status" },
  { title: "Late", key: "late_minutes" },
  { title: "Early Leaving", key: "early_leaving_minutes" },
  { title: "Overtime", key: "overtime_minutes" },
];
const calendarOptions = ref({
  plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
  initialView: "dayGridMonth",
  headerToolbar: {
    left: "prev,next today",
    center: "title",
    right: "dayGridMonth,timeGridWeek,timeGridDay",
  },
  events: [],
  nowIndicator: true,
  editable: false,
  selectable: false,
  height: "auto",
  aspectRatio: 1.35,
  dayMaxEvents: 2,
  moreLinkClick: "popover",
  eventDisplay: "block",
  dayHeaderFormat: { weekday: "short" },
  buttonText: {
    today: "Today",
    month: "Month",
    week: "Week",
    day: "Day",
  },
  // Add event handlers for calendar navigation
  datesSet: (dateInfo) => {
    handleCalendarDateChange(dateInfo);
  },
  viewDidMount: (viewInfo) => {
    handleCalendarViewChange(viewInfo);
  },
});

// Methods
const updateDateTime = () => {
  const now = new Date();
  currentDateTime.value = now.toLocaleString("en-US", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
    hour12: true,
  });
};

// const fetchTodayAttendance = async () => {
//   try {
//     // date: new Date().toISOString().split("T")[0],
//     const response = await axios.get("/api/attendance/my-attendance", {
//       params: {
//          today: 1,
//       },
//       headers: {
//         Authorization: `Bearer ${accessToken.value}`,
//       },
//     });
//     if (response.data.data.length > 0) {
//       attendance.value = response.data.data[0];
//     }
//   } catch (error) {
//     // Handle error silently
//   }
// };

// Fetch attendance events for calendar (independent from dropdown filters)
const fetchAttendanceEvents = async (startDate = null, endDate = null) => {
  try {
    // Use provided dates or calculate from selected month/year
    let start, end;
    if (startDate && endDate) {
      start = new Date(startDate);
      end = new Date(endDate);
    } else {
      // Fallback to current month if no dates provided
      const currentDate = new Date();
      const month = currentDate.getMonth() + 1;
      const year = currentDate.getFullYear();

      start = new Date(year, month - 1, 1);
      end = new Date(year, month, 0);
    }

    const response = await axios.get("/api/attendance/my-attendance", {
      params: {
        per_page: 100,
        start_date: start.toISOString().split("T")[0],
        end_date: end.toISOString().split("T")[0],
      },
      headers: {
        Authorization: `Bearer ${accessToken.value}`,
      },
    });
    calendarOptions.value.events = response.data.data.map((att) => ({
      title: formatStatus(att.status),
      start: att.date,
      allDay: true,
      color: getStatusColorCode(att.status),
    }));
  } catch (error) {
    // Handle error silently
  }
};

const formatStatus = (status) =>
  status
    .replace(/-/g, " ")
    .replace(/\b\w/g, (c) => c.toUpperCase());

// Fetch attendance statistics based on dropdown filters (independent from calendar)
const fetchAttendanceStats = async () => {
  try {
    // Use dropdown filter values only
    const month = selectedMonth.value || new Date().getMonth() + 1;
    const year = selectedYear.value || new Date().getFullYear();
    // Use GET and pass month/year as query params
    const response = await $api(`employee/dashboard/stats?month=${month}&year=${year}`, {
      method: "GET"
    });

    // The $api helper wraps the response in a 'data' property
    const data = response.data;

    // Update attendance stats with API data
    attendanceStats.value = {
      totalEarlyCheckIn: data.attendance_stats.total_early_check_in || 0,
      totalLateCheckIn: data.attendance_stats.total_late_check_in || 0,
      totalEarlyCheckOut: data.attendance_stats.total_early_check_out || 0,
      totalLateCheckOut: data.attendance_stats.total_late_check_out || 0,
      totalPresents: data.attendance_stats.total_presents || 0,
      totalLeaves: data.attendance_stats.total_leaves || 0,
      totalAbsent: data.attendance_stats.total_absent || 0,
    };

    canCheckin.value = data.can_checkin;
  } catch (error) {
    if (error.response?.status === 401) {
      $toast.error("Authentication failed. Please login again.");
    } else if (error.response?.status === 404) {
      $toast.error("Employee record not found.");
    } else {
      $toast.error("Failed to fetch attendance statistics");
    }
  }
};

// Fetch current week attendance data
const fetchCurrentWeekAttendance = async () => {
  attendanceTableLoading.value = true;
  try {
    // Get current date and time in local timezone
    const now = new Date();
    
    // Get day of week in local timezone (0 = Sunday, 1 = Monday, etc.)
    const dayOfWeek = now.getDay();
    
    // Calculate start of current week (Monday) in local timezone
    // Convert JavaScript day to Monday-based week (Monday = 0, Tuesday = 1, ..., Sunday = 6)
    let mondayBasedDay;
    if (dayOfWeek === 0) { // Sunday
      mondayBasedDay = 6; // Sunday is day 6 in Monday-based week
    } else {
      mondayBasedDay = dayOfWeek - 1; // Monday = 0, Tuesday = 1, etc.
    }
    
    // Create start of week date (Monday 00:00:00)
    const startOfWeek = new Date(now);
    startOfWeek.setDate(now.getDate() - mondayBasedDay);
    startOfWeek.setHours(0, 0, 0, 0);
    
    // Create end of week date (Sunday 23:59:59)
    const endOfWeek = new Date(startOfWeek);
    endOfWeek.setDate(startOfWeek.getDate() + 6);
    endOfWeek.setHours(23, 59, 59, 999);
    
    // Get local date strings to avoid timezone conversion issues
    const startDate = startOfWeek.getFullYear() + '-' + 
      String(startOfWeek.getMonth() + 1).padStart(2, '0') + '-' + 
      String(startOfWeek.getDate()).padStart(2, '0');
    
    const endDate = endOfWeek.getFullYear() + '-' + 
      String(endOfWeek.getMonth() + 1).padStart(2, '0') + '-' + 
      String(endOfWeek.getDate()).padStart(2, '0');
    
    const response = await axios.get("/api/attendance/my-attendance", {
      params: {
        start_date: startDate,
        end_date: endDate,
        per_page: 7,
      },
      headers: {
        Authorization: `Bearer ${accessToken.value}`,
      },
    });

    if (response.data.data.length > 0) {
      attendance.value = response.data.data[0];
    }
    currentWeekAttendances.value = response.data.data || [];
  } catch (error) {
    console.error("Error fetching current week attendance:", error);
    currentWeekAttendances.value = [];
  } finally {
    attendanceTableLoading.value = false;
  }
};

const getCurrentPosition = () => {
  return new Promise((resolve, reject) => {
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(resolve, reject);
    } else {
      reject(new Error("Geolocation is not supported by this browser."));
    }
  });
};

const hasGoodNetwork = () => {
  const connection =
    navigator.connection ||
    navigator.mozConnection ||
    navigator.webkitConnection
  if (!connection) return true
  const badTypes = ['slow-2g', '2g']
  if (badTypes.includes(connection.effectiveType)) return false
  if (connection.downlink && connection.downlink < 0.3) return false
  return true
}

const checkIn = async (fromOffice = true, location = null) => {
  if (!hasGoodNetwork()) {
    $toast.error('Network is too weak. Please check your internet connection and try again.');
    return
  }
  checkingIn.value = true;
  try {
    const position = await getCurrentPosition();
    const device = navigator.userAgent;

    const requestData = {
      latitude: position.coords.latitude,
      longitude: position.coords.longitude,
      accuracy: position.coords.accuracy,
      device: device,
      from_office: fromOffice,
    };

    // Only add location if it's provided
    if (location) {
      requestData.location = location;
    }
    const response = await $api("/attendance/check-in", {
      method: "POST",
      body: requestData,
    });
    attendance.value = response.data;
    fetchAttendanceEvents();
    fetchAttendanceStats();
    fetchCurrentWeekAttendance();
    $toast.success("Checked in successfully!");
  } catch (error) {
    const data = error?._data || error?.response?.data;

    if (data?.errors?.message) {
      $toast.error(data.errors.message[0]);
    } else if (data?.errors?.location) {
      $toast.error(data.errors.location[0]);
    } else if (data?.message) {
      $toast.error(data.message);
    } else if (error.code === 1) {
      $toast.error("Location permission denied. Please allow location access.");
    } else if (error.code) {
      $toast.error(error.message);
    } else {
      $toast.error("Something went wrong. Please try again.");
    }
  } finally {
    checkingIn.value = false;
  }
};

const checkInFromOffice = () => {
  checkIn(true, null);
};

const checkInFromOtherLocation = () => {
  pendingAction.value = "checkin";
  locationInput.value = "";
  isLocationModalOpen.value = true;
};

const checkOut = async (fromOffice = true, location = null) => {
  checkingOut.value = true;
  try {
    const position = await getCurrentPosition();

    const requestData = {
      latitude: position.coords.latitude,
      longitude: position.coords.longitude,
      from_office: fromOffice,
    };

    // Only add location if it's provided
    if (location) {
      requestData.location = location;
    }

    const response = await axios.post(
      "/api/attendance/check-out",
      requestData,
      {
        headers: {
          Authorization: `Bearer ${accessToken.value}`,
        },
      }
    );
    attendance.value = response.data.data;
    fetchAttendanceEvents();
    fetchAttendanceStats();
    fetchCurrentWeekAttendance();
    $toast.success("Checked out successfully!");
  } catch (error) {
    if (error.code === 1) {
      $toast.error("Location permission denied. Please allow location access.");
    } else if (error.code) {
      $toast.error(error.message);
    } else if(error.response?.data?.message === 'You cannot checkout at this time'){
      router.go(0);
    } else{
      $toast.error(
        error.response?.data?.message || "Something went wrong while checking out"
      );
    }
  } finally {
    checkingOut.value = false;
  }
};

const checkOutFromOffice = () => {
  checkOut(true, null);
};

const checkOutFromOtherLocation = () => {
  pendingAction.value = "checkout";
  locationInput.value = "";
  isLocationModalOpen.value = true;
};

const handleLocationSave = () => {
  if (!locationInput.value.trim()) {
    $toast.error("Please enter a location");
    return;
  }

  if (pendingAction.value === "checkin") {
    checkIn(false, locationInput.value);
  } else if (pendingAction.value === "checkout") {
    checkOut(false, locationInput.value);
  }

  isLocationModalOpen.value = false;
  locationInput.value = "";
  pendingAction.value = "";
};

const handleLocationCancel = () => {
  isLocationModalOpen.value = false;
  locationInput.value = "";
  pendingAction.value = "";
};


const formatTime = (timeString) => {
  if (!timeString) return "";
  const [hours, minutes] = timeString.split(":");
  const hour = parseInt(hours, 10);
  const ampm = hour >= 12 ? "PM" : "AM";
  const displayHour = hour % 12 || 12;
  return `${displayHour}:${minutes} ${ampm}`;
};

// Helper function to convert minutes to hours:minutes format
const convertIntoHoursMins = (minutes) => {
  if (!minutes || minutes === 0) return "0h 0m";
  const hours = Math.floor(minutes / 60);
  const mins = minutes % 60;
  return `${hours}h ${mins}m`;
};

// Helper function to format date
const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
};

// Helper function to get status color
const getStatusColor = (status) => {
  switch (status) {
    case "Present":
      return "success";
    case "Absent":
      return "error";
    case "Late":
      return "warning";
    case "Half-leave":
      return "info";
    case "Short-leave":
      return "info";
    case "Holiday":
      return "info";
    case "Leave":
      return "info";
    case "Not-marked":
      return "primary";
    default:
      return "secondary";
  }
};

const getStatusColorCode = (status) => {
  switch (status) {
    case "Present":
      return "#28c76f";
    case "Absent":
      return "#dc3545";
    case "Late":
      return "#FF9F43";
    case "Half-leave":
      return "#00BAD1";
    case "Short-leave":
      return "#00BAD1";
    case "Holiday":
      return "#808390";
    case "Shift-awaiting":
      return "#808390";
    case "Leave":
      return "#00BAD1";
    case "Not-marked":
      return "#D55D36";
    default:
      return "#dc3545";
  }
};

// Generate month options (1-12)
const generateMonthOptions = () => {
  const months = [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December",
  ];

  monthOptions.value = months.map((month, index) => ({
    title: month,
    value: index + 1, // 1-12
  }));
};

// Generate year options (from 2020 to current year + 1)
const generateYearOptions = () => {
  const currentYear = new Date().getFullYear();
  const startYear = 2024;
  const endYear = currentYear;

  const years = [];
  for (let year = endYear; year >= startYear; year--) {
    years.push({
      title: year.toString(),
      value: year,
    });
  }

  yearOptions.value = years;
};

// Handle month/year filter change (independent from calendar)
const onMonthYearChange = () => {
  if (selectedMonth.value && selectedYear.value) {
    // Only update statistics cards, don't affect calendar
    fetchAttendanceStats();

    // DON'T call fetchAttendanceEvents() here - keep calendar independent
  }
};

// Handle calendar date changes (when user navigates with prev/next buttons)
const handleCalendarDateChange = (dateInfo) => {
  // DON'T update the dropdown filters - keep calendar independent
  // Just fetch calendar events for the displayed month
  fetchAttendanceEvents(dateInfo.start, dateInfo.end);
};

// Handle calendar view changes (when user switches between month/week/day views)
const handleCalendarViewChange = (viewInfo) => {
  // Only fetch data for month view to avoid too many API calls
  if (viewInfo.view.type === "dayGridMonth") {
    // Fetch calendar events independently without affecting dropdown filters
    fetchAttendanceEvents(viewInfo.start, viewInfo.end);
  }
};

let dateTimeInterval = null;

// Lifecycle
onMounted(async () => {
  updateDateTime();
  dateTimeInterval = setInterval(updateDateTime, 1000);

  // Initialize month and year options
  generateMonthOptions();
  generateYearOptions();

  // Ensure month and year have valid values
  const currentDate = new Date();
  const currentMonth = currentDate.getMonth() + 1;
  const currentYear = currentDate.getFullYear();
  
  selectedMonth.value = currentMonth;
  selectedYear.value = currentYear;

  // Set dynamic office hours from user data
  const startTime = userData.value?.branch?.office_start_time || "09:00";
  const endTime = userData.value?.branch?.office_end_time || "18:00";
  officeHours.value =
    convertTo12HourFormat(startTime) + " to " + convertTo12HourFormat(endTime);

  // Only fetch data if we have access token
  if (accessToken.value) {
    // await fetchTodayAttendance();
    // Initialize calendar with current month (independent)
    // await fetchAttendanceEvents();
    // Initialize statistics with dropdown filter values (independent)
    await fetchAttendanceStats();
    // Fetch current week attendance data
    await fetchCurrentWeekAttendance();
  }
  else{
    window.location.href = "/login";
  }

  isLoading.value = false;
});

onUnmounted(() => {
  if (dateTimeInterval) clearInterval(dateTimeInterval);
});
</script>

<template>
  <section>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0 help-center-breadcrumbs"
      :items="[{ title: 'Employee Dashboard' }]"
    />
    <div
      v-if="isLoading"
      class="d-flex justify-center align-center"
      style="block-size: 400px;"
    >
      <VProgressCircular indeterminate size="64" />
    </div>
    <div v-else>
      <VRow>
        <!-- Total Early Check-In Card -->
        <VCol cols="6" lg="3">
          <VCard class="attendance-stat-card early-checkin-card" elevation="2">
            <VCardText class="pa-4">
              <div class="d-flex align-center mb-3">
                <div class="icon-container early-checkin-icon">
                  <VIcon icon="tabler-truck-delivery" size="24" color="white" />
                </div>
                <div class="stat-number text-h4 ml-5">
                  {{ attendanceStats.totalEarlyCheckIn }}
                </div>
              </div>
              <div class="stat-label text-body-2 text-medium-emphasis">
                Early Check-Ins
              </div>
            </VCardText>
          </VCard>
        </VCol>

        <!-- Total Late Check-In Card -->
        <VCol cols="6" lg="3">
          <VCard class="attendance-stat-card late-checkin-card" elevation="2">
            <VCardText class="pa-4">
              <div class="d-flex align-center mb-3">
                <div class="icon-container late-checkin-icon">
                  <VIcon icon="tabler-alert-triangle" size="24" color="white" />
                </div>
                <div class="stat-number text-h4 ml-5">
                  {{ attendanceStats.totalLateCheckIn }}
                </div>
              </div>
              <div class="stat-label text-body-2 text-medium-emphasis">
                Late Check-Ins
              </div>
            </VCardText>
          </VCard>
        </VCol>

        <!-- Total Early Check-Out Card -->
        <VCol cols="6" lg="3">
          <VCard class="attendance-stat-card early-checkout-card" elevation="2">
            <VCardText class="pa-4">
              <div class="d-flex align-center mb-3">
                <div class="icon-container early-checkout-icon">
                  <VIcon icon="tabler-git-branch" size="24" color="white" />
                </div>
                <div class="stat-number text-h4 ml-5">
                  {{ attendanceStats.totalEarlyCheckOut }}
                </div>
              </div>
              <div class="stat-label text-body-2 text-medium-emphasis">
                Early Check-Outs
              </div>
            </VCardText>
          </VCard>
        </VCol>

        <!-- Total Late Check-Out Card -->
        <VCol cols="6" lg="3">
          <VCard class="attendance-stat-card late-checkout-card" elevation="2">
            <VCardText class="pa-4">
              <div class="d-flex align-center mb-3">
                <div class="icon-container late-checkout-icon">
                  <VIcon icon="tabler-clock" size="24" color="white" />
                </div>
                <div class="stat-number text-h4 ml-5">
                  {{ attendanceStats.totalLateCheckOut }}
                </div>
              </div>
              <div class="stat-label text-body-2 text-medium-emphasis">
                Late Check-Outs
              </div>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <VRow>
        <!-- Calendar Section (Left) -->
        <VCol cols="12" lg="8" class="order-2 order-lg-1">
          <VCard class="calendar-card">
            <VCardTitle class="calendar-title d-none d-md-flex">
              <span>Attendance Calendar</span>
            </VCardTitle>
            <VCardText class="calendar-content">
              <div class="calendar-section">
                <FullCalendar :options="calendarOptions" />
              </div>
            </VCardText>
          </VCard>
        </VCol>

        <!-- Right Side Section -->
        <VCol cols="12" lg="4" class="order-1 order-lg-2">
          <VRow>
            <!-- Mark Attendance Card -->
            <VCol cols="12">
              <VCard class="attendance-card">
                <VCardTitle class="attendance-title">
                  <VIcon icon="tabler-clock" class="me-2" />
                  Mark Attendance
                </VCardTitle>
                <VDivider />
                <VCardText class="attendance-content">
                  <div class="office-time pb-4 pt-5">
                    <p class="office-time-text">
                      <VIcon icon="tabler-clock-24" size="16" class="me-1" />
                      My Office Time: {{ officeHours }}
                    </p>
                  </div>

                  <h4>From Office</h4>
                  <div class="attendance-actions pt-2 pb-4">
                    <VRow>
                      <VCol cols="6" class="pr-1">
                        <!-- OFFICE CLOCK IN Button -->
                        <VBtn
                          class="clock-in-btn text-sm w-100"
                          size="large"
                          :disabled="!canCheckin"
                          @click="checkInFromOffice"
                          :loading="checkingIn"
                        >
                          <VIcon icon="tabler-login-2" class="me-2" />
                          <template
                            v-if="
                            attendance?.check_in && attendance?.check_in_from === '1'
                          "
                          >
                            {{ formatTime(attendance.check_in) }}
                          </template>
                          <template v-else> Check In </template>
                        </VBtn>
                      </VCol>
                      <VCol cols="6" class="pl-1">
                        <VBtn
                          class="clock-out-btn text-sm w-100"
                          size="large"
                          :disabled="!attendance?.check_in || attendance?.check_out"
                          @click="checkOutFromOffice"
                          :loading="checkingOut"
                        >
                          <VIcon icon="tabler-logout" class="me-2" />
                          <template
                            v-if="
                            attendance?.check_out &&
                            attendance?.check_out_from === '1'
                          "
                          >
                            {{ formatTime(attendance.check_out) }}
                          </template>
                          <template v-else> Check Out </template>
                        </VBtn>
                      </VCol>
                    </VRow>
                  </div>

                  <h4>From Other Location</h4>
                  <div class="attendance-actions pt-2 pb-5">
                    <VRow>
                      <VCol cols="6" class="pr-1">
                        <!-- OTHER LOCATION CLOCK IN Button -->
                        <VBtn
                          class="clock-in-btn text-sm w-100"
                          size="large"
                          :disabled="!canCheckin"
                          @click="checkInFromOtherLocation"
                          :loading="checkingIn"
                        >
                          <VIcon icon="tabler-map-pin-check" class="me-2" />
                          <template
                            v-if="
                            attendance?.check_in &&
                            attendance?.check_in_from === '2'
                          "
                          >
                            {{ formatTime(attendance.check_in) }}
                          </template>
                          <template v-else> Check In </template>
                        </VBtn>
                      </VCol>
                      <VCol cols="6" class="pl-1">
                        <!-- OTHER LOCATION CLOCK OUT Button -->
                        <VBtn
                          class="clock-out-btn text-sm w-100"
                          size="large"
                          :disabled="!attendance?.check_in || attendance?.check_out"
                          @click="checkOutFromOtherLocation"
                          :loading="checkingOut"
                        >
                          <VIcon icon="tabler-map-pin-x" class="me-2" />
                          <template
                            v-if="
                            attendance?.check_out &&
                            attendance?.check_out_from === '2'
                          "
                          >
                            {{ formatTime(attendance.check_out) }}
                          </template>
                          <template v-else> Check Out </template>
                        </VBtn>
                      </VCol>
                    </VRow>
                  </div>

                  <!-- Today's Status Display -->
                  <div class="todays-status-section d-md-none">
                    <VCard variant="outlined" class="todays-status-card">
                      <div class="todays-status-header">
                        <VIcon
                          icon="tabler-clock-check"
                          size="20"
                          class="todays-status-icon"
                        />
                        <span class="todays-status-title">Today's Status</span>
                        <VChip
                          :color="getStatusColor(attendance?.status || 'Not-marked')"
                          size="small"
                          class="todays-status-chip"
                        >
                          {{ attendance?.status || 'Not Marked' }}
                        </VChip>
                      </div>

                      <div v-if="attendance?.check_in" class="todays-status-content">
                        <div class="todays-status-item">
                          <div class="todays-status-item-info">
                            <VIcon
                              icon="tabler-login-2"
                              size="16"
                              color="success"
                              class="todays-status-item-icon"
                            />
                            <span class="todays-status-item-label">Check In</span>
                          </div>
                          <span class="todays-status-item-time">{{
                              formatTime(attendance.check_in)
                            }}</span>
                        </div>

                        <div
                          v-if="attendance?.check_out"
                          class="todays-status-item"
                        >
                          <div class="todays-status-item-info">
                            <VIcon
                              icon="tabler-logout"
                              size="16"
                              color="info"
                              class="todays-status-item-icon"
                            />
                            <span class="todays-status-item-label">Check Out</span>
                          </div>
                          <span class="todays-status-item-time">{{
                              formatTime(attendance.check_out)
                            }}</span>
                        </div>
                      </div>
                    </VCard>
                  </div>
                </VCardText>
              </VCard>
            </VCol>

            <VCol cols="12">
              <VCard class="leaves-breakdown-card">
                <VCardTitle class="pt-4"> Leaves Break-Down </VCardTitle>
                <VCardText class="leaves-breakdown-content">
                  <div class="leaves-breakdown-item">
                    <div class="leaves-breakdown-info">
                      <VIcon
                        icon="tabler-users"
                        size="24"
                        color="success"
                        class="leaves-breakdown-icon"
                      />
                      <span class="leaves-breakdown-label">Total Present</span>
                    </div>
                    <span class="leaves-breakdown-value">{{ attendanceStats.totalPresents }}</span>
                  </div>

                  <div class="leaves-breakdown-item">
                    <div class="leaves-breakdown-info">
                      <VIcon
                        icon="tabler-x"
                        size="24"
                        color="error"
                        class="leaves-breakdown-icon"
                      />
                      <span class="leaves-breakdown-label">Total Absent</span>
                    </div>
                    <span class="leaves-breakdown-value">{{ attendanceStats.totalAbsent }}</span>
                  </div>

                  <div class="leaves-breakdown-item">
                    <div class="leaves-breakdown-info">
                      <VIcon
                        icon="tabler-calendar-event"
                        size="24"
                        color="info"
                        class="leaves-breakdown-icon"
                      />
                      <span class="leaves-breakdown-label">Total Leaves</span>
                    </div>
                    <span class="leaves-breakdown-value">{{ attendanceStats.totalLeaves }}</span>
                  </div>

                </VCardText>
              </VCard>
            </VCol>

            <!-- Quick Stats Card -->
            <!-- <VCol cols="12">
              <VCard>
                <VCardTitle>
                  <VIcon icon="tabler-chart-bar" class="me-2" />
                  Quick Stats
                </VCardTitle>
                <VCardText>
                  <VRow>
                    <VCol cols="6">
                      <div class="text-center">
                        <VIcon icon="tabler-calendar-check" size="32" color="success" class="mb-2" />
                        <h6 class="text-h6">Present</h6>
                        <p class="text-body-2 text-medium-emphasis">This Month</p>
                      </div>
                    </VCol>
                    <VCol cols="6">
                      <div class="text-center">
                        <VIcon icon="tabler-calendar-x" size="32" color="error" class="mb-2" />
                        <h6 class="text-h6">Absent</h6>
                        <p class="text-body-2 text-medium-emphasis">This Month</p>
                      </div>
                    </VCol>
                  </VRow>
                </VCardText>
              </VCard>
            </VCol> -->
          </VRow>
        </VCol>
      </VRow>

      <!-- Current Week Attendance Table -->
      <VRow class="mt-6">
        <VCol cols="12">
          <VCard class="attendance-table-card">
            <VCardTitle class="d-flex align-center">
              <VIcon icon="tabler-calendar-week" class="me-2" />
              <span>This Week's Attendance</span>
            </VCardTitle>
            <VDivider />
            <VCardText class="pa-0">
              <VDataTableServer
                v-model:items-per-page="attendanceTableOptions.itemsPerPage"
                v-model:model-value="attendanceTableOptions.selectedRows"
                v-model:page="attendanceTableOptions.page"
                :headers="attendanceTableHeaders"
                :items="currentWeekAttendances"
                :items-length="currentWeekAttendances.length"
                :loading="attendanceTableLoading"
                loading-text="Loading attendance data..."
                class="text-no-wrap"
                hide-default-footer
              >
                <template #item.date="{ item }">
                  {{ formatDate(item.date) }}
                </template>

                <template #item.check_in="{ item }">
                  <VTooltip
                    v-if="item.check_in && item.latitude_in && item.longitude_in"
                    :text="item.address_in || 'View on maps'"
                    location="top">
                    <template #activator="{ props }">
                      <a
                        :href="`https://www.google.com/maps?q=${item.latitude_in},${item.longitude_in}`"
                        target="_blank"
                        rel="noopener noreferrer"
                        v-bind="props"
                      >
                        <i class="tabler tabler-map-pin"></i>
                      </a>
                    </template>
                  </VTooltip>
                  <VTooltip
                    v-if="item.check_in && (!item.latitude_in || !item.longitude_in)"
                    text="Location not available"
                    location="top">
                    <template #activator="{ props }">
                      <i v-bind="props" class="tabler tabler-map-pin-off"></i>
                    </template>
                  </VTooltip>
                  {{ convertTo12HourFormat(item.check_in) }}
                </template>

                <template #item.check_out="{ item }">
                  <VTooltip
                    v-if="item.check_out && item.latitude_out && item.longitude_out"
                    :text="item.address_out || 'View on maps'"
                    location="top">
                    <template #activator="{ props }">
                      <a
                        :href="`https://www.google.com/maps?q=${item.latitude_out},${item.longitude_out}`"
                        target="_blank"
                        rel="noopener noreferrer"
                        v-bind="props"
                      >
                        <i class="tabler tabler-map-pin"></i>
                      </a>
                    </template>
                  </VTooltip>
                  <VTooltip
                    v-if="item.check_out && (!item.latitude_out || !item.longitude_out)"
                    text="Location not available"
                    location="top">
                    <template #activator="{ props }">
                      <i v-bind="props" class="tabler tabler-map-pin-off"></i>
                    </template>
                  </VTooltip>
                  {{ convertTo12HourFormat(item.check_out) }}
                </template>

                <template #item.status="{ item }">
                  <VChip
                    :color="getStatusColor(item.status)"
                    size="small"
                    class="status-chip"
                  >
                    {{ item.status.replace('-', ' ').replace(/\b\w/g, (c) => c.toUpperCase()) }}
                  </VChip>
                </template>

                <template #item.late_minutes="{ item }">
                  <span class="time-cell">{{ convertIntoHoursMins(item.late_minutes) }}</span>
                </template>

                <template #item.early_leaving_minutes="{ item }">
                  <span class="time-cell">{{ convertIntoHoursMins(item.early_leaving_minutes) }}</span>
                </template>

                <template #item.overtime_minutes="{ item }">
                  <span class="time-cell">{{ convertIntoHoursMins(item.overtime_minutes) }}</span>
                </template>
              </VDataTableServer>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Location Modal -->
      <VDialog v-model="isLocationModalOpen" max-width="400" persistent>
        <VCard>
          <VCardTitle class="text-h6 pa-4"> Add Your Location </VCardTitle>

          <VDivider />

          <VCardText class="pa-4">
            <VTextField
              v-model="locationInput"
              label="Location"
              placeholder="Enter your location"
              variant="outlined"
              autofocus
              @keyup.enter="handleLocationSave"
            />
          </VCardText>

          <VCardActions class="pa-4 pt-0">
            <VSpacer />
            <VBtn color="grey" variant="outlined" @click="handleLocationCancel">
              Cancel
            </VBtn>
            <VBtn color="primary" @click="handleLocationSave">
              Save Changes
            </VBtn>
          </VCardActions>
        </VCard>
      </VDialog>
    </div>
  </section>
</template>

<style scoped>
.current-time {
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
  font-size: 1.1rem;
  font-weight: 500;
}

/* Mark Attendance Card Styles */
.attendance-card {
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 10%);
}

.attendance-title {
  font-size: 1.1rem;
  font-weight: 500;
  padding-block: 16px 8px;
  padding-inline: 16px;
}

.attendance-content {
  padding-block: 8px 16px;
  padding-inline: 16px;
}

.office-time-text {
  display: flex;
  align-items: center;
  margin: 0;
  /* color: #666; */
  font-size: 0.9rem;
  line-height: 1.4;
}

.attendance-actions {
  display: flex;
  gap: 12px;
}

.clock-in-btn {
  flex: 1;
  border-radius: 8px;
  background-color: #d45b35 !important;
  block-size: 48px;
  box-shadow: 0 2px 4px rgba(212, 91, 53, 30%);
  color: white !important;
  font-weight: 600;
  letter-spacing: 0.5px;
  text-transform: uppercase;
}

.clock-in-btn:disabled {
  background-color: #d45b35 !important;
  box-shadow: none !important;
  color: white !important;
  cursor: not-allowed !important;
}

.clock-in-btn:hover:not(:disabled) {
  background-color: #b8492a !important;
  box-shadow: 0 4px 8px rgba(212, 91, 53, 40%);
}

.clock-out-btn {
  flex: 1;
  border-radius: 8px;
  background-color: #DC3545 !important;
  block-size: 48px;
  box-shadow: 0 2px 4px rgb(220, 53, 69);
  color: white !important;
  font-weight: 600;
  letter-spacing: 0.5px;
  text-transform: uppercase;
}

.clock-out-btn:disabled {
  background-color: #bdbdbd !important;
  box-shadow: none !important;
  color: #757575 !important;
  cursor: not-allowed;
}

.clock-out-btn:hover:not(:disabled) {
  background-color: #e53e3e !important;
  box-shadow: 0 4px 8px rgba(255, 76, 81, 40%);
}

/* Calendar Card Styles */
.calendar-card {
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 10%);
}

.calendar-title {
  font-size: 1.1rem;
  font-weight: 500;
  padding-block: 16px 8px;
  padding-inline: 16px;
}

.calendar-content {
  padding-block: 8px 16px;
  padding-inline: 16px;
}

.calendar-section {
  margin-block-start: 1rem;
  min-block-size: 400px;
  overflow-x: auto;
}

/* Mobile Status Display */
.attendance-status {
  border-radius: 8px;
}

.attendance-status .v-card {
  border-radius: 8px;
  background-color: rgba(var(--v-theme-surface), 0.5);
}

:deep(.fc .fc-button .fc-icon) {
  vertical-align: unset !important;
}

:deep(.fc) {
  /* FullCalendar styles */
  font-family: inherit;
}

:deep(.fc-header-toolbar) {
  flex-wrap: wrap;
  gap: 0.5rem;
}

:deep(.fc-toolbar-title) {
  font-size: 1.25rem;
}

/* General button improvements */
:deep(.fc-button) {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 1px solid transparent;
  cursor: pointer;
  text-decoration: none;
  transition: all 0.2s ease;
}

:deep(.fc-button:not(:disabled)) {
  border-color: rgb(var(--v-theme-primary));
  background-color: rgb(var(--v-theme-primary));
  color: white;
}

:deep(.fc-button:not(:disabled):hover) {
  border-color: rgb(var(--v-theme-primary));
  background-color: rgb(var(--v-theme-primary));
  opacity: 0.9;
}

:deep(.fc-button:not(:disabled).fc-button-active) {
  border-color: rgb(var(--v-theme-primary));
  background-color: rgb(var(--v-theme-primary));
  box-shadow: 0 2px 4px rgba(var(--v-theme-primary), 0.3);
  color: white;
  opacity: 0.8;
}

:deep(.fc-button:not(:disabled).fc-button-active:hover) {
  border-color: rgb(var(--v-theme-primary));
  background-color: rgb(var(--v-theme-primary));
  box-shadow: 0 4px 8px rgba(var(--v-theme-primary), 0.4);
  opacity: 0.7;
}

:deep(.fc-button:disabled) {
  border-color: #e0e0e0;
  background-color: #e0e0e0;
  color: #9e9e9e;
}

:deep(.fc-button:focus) {
  outline: 2px solid rgb(var(--v-theme-primary));
  outline-offset: 2px;
}

:deep(.fc-button:active) {
  transform: translateY(1px);
}

/* Today's Status Styles */
.todays-status-section {
  margin-block-start: 16px;
}

/* Hide on desktop, show on mobile and tablet */
@media (min-width: 960px) {
  .todays-status-section {
    display: none !important;
  }
}

.todays-status-card {
  padding: 16px;
  border: 1px solid rgba(var(--v-theme-outline), 0.12);
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 8%);
}

.todays-status-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-block-end: 12px;
}

.todays-status-icon {
  color: rgba(var(--v-theme-primary), 0.8);
  margin-inline-end: 8px;
}

.todays-status-title {
  flex: 1;
  color: rgba(var(--v-theme-on-surface), 0.87);
  font-size: 0.95rem;
  font-weight: 600;
}

.todays-status-chip {
  font-size: 0.8rem;
  font-weight: 500;
}

.todays-status-content {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.todays-status-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px;
  border: 1px solid rgba(var(--v-theme-outline), 0.08);
  border-radius: 8px;
  background-color: rgba(var(--v-theme-surface), 0.5);
}

.todays-status-item-info {
  display: flex;
  flex: 1;
  align-items: center;
  min-inline-size: 0;
}

.todays-status-item-icon {
  flex-shrink: 0;
  margin-inline-end: 8px;
}

.todays-status-item-label {
  color: rgba(var(--v-theme-on-surface), 0.87);
  font-size: 0.9rem;
  font-weight: 500;
}

.todays-status-item-time {
  flex-shrink: 0;
  border-radius: 6px;
  background-color: rgba(var(--v-theme-primary), 0.1);
  color: rgba(var(--v-theme-primary), 0.9);
  font-size: 0.9rem;
  font-weight: 600;
  padding-block: 4px;
  padding-inline: 8px;
}

/* Leaves Break-Down Styles */
.leaves-breakdown-card {
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 10%);
}

.leaves-breakdown-content {
  padding: 16px !important;
}

.leaves-breakdown-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-block-end: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  padding-block: 16px;
  padding-inline: 0;
}

.leaves-breakdown-item:last-child {
  border-block-end: none;
  padding-block-end: 0;
}

.leaves-breakdown-info {
  display: flex;
  flex: 1;
  align-items: center;
  min-inline-size: 0;
}

.leaves-breakdown-icon {
  flex-shrink: 0;
  margin-inline-end: 12px;
}

.leaves-breakdown-label {
  overflow: hidden;
  color: rgba(var(--v-theme-on-surface), 0.87);
  font-size: 0.95rem;
  font-weight: 500;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* Mobile Responsive Styles */
@media (max-width: 600px) {
  /* Dashboard Header Mobile Adjustments */
  .d-flex.justify-space-between {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }

  .month-select {
    max-inline-size: 160px;
    min-inline-size: 140px;
  }

  .year-select {
    max-inline-size: 120px;
    min-inline-size: 100px;
  }

  /* Mobile Layout Adjustments */
  .attendance-actions {
    flex-direction: column;
    gap: 12px;
  }

  .clock-in-btn,
  .clock-out-btn {
    flex: none;
    block-size: 52px;
    font-size: 0.9rem;
    inline-size: 100%;
  }

  .calendar-section {
    margin-block-start: 0.5rem;
    min-block-size: 280px;
  }

  .attendance-title {
    font-size: 1rem;
    padding-block: 12px 6px;
    padding-inline: 12px;
  }

  .attendance-content {
    padding-block: 6px 12px;
    padding-inline: 12px;
  }

  .office-time-text {
    font-size: 0.85rem;
  }

  /* Mobile Status Display */
  .attendance-status {
    margin-block-start: 16px;
  }

  .attendance-status .v-card {
    padding-block: 12px !important;
    padding-inline: 12px !important;
  }

  /* Mobile Today's Status Styles */
  .todays-status-section {
    margin-block-start: 16px;
  }

  .todays-status-card {
    padding: 16px;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 10%);
  }

  .todays-status-header {
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    border-block-end: 1px solid rgba(var(--v-theme-outline), 0.12);
    margin-block-end: 20px;
    padding-block-end: 12px;
  }

  .todays-status-title {
    color: rgba(var(--v-theme-on-surface), 0.95);
    font-size: 1rem;
    font-weight: 700;
  }

  .todays-status-chip {
    font-size: 0.8rem;
    font-weight: 600;
    padding-block: 6px;
    padding-inline: 12px;
  }

  .todays-status-content {
    gap: 16px;
  }

  .todays-status-item {
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    border: 1px solid rgba(var(--v-theme-outline), 0.12);
    border-radius: 10px;
    background-color: rgba(var(--v-theme-surface), 0.8);
    box-shadow: 0 1px 4px rgba(0, 0, 0, 5%);
  }

  .todays-status-item-info {
    display: flex;
    flex: 1;
    align-items: center;
    min-inline-size: 0;
  }

  .todays-status-item-icon {
    flex-shrink: 0;
    font-size: 18px !important;
    margin-inline-end: 12px;
  }

  .todays-status-item-label {
    color: rgba(var(--v-theme-on-surface), 0.9);
    font-size: 0.95rem;
    font-weight: 600;
  }

  .todays-status-item-time {
    flex-shrink: 0;
    border: 1px solid rgba(var(--v-theme-primary), 0.2);
    border-radius: 8px;
    background-color: rgba(var(--v-theme-primary), 0.15);
    color: rgba(var(--v-theme-primary), 0.95);
    font-size: 0.95rem;
    font-weight: 700;
    min-inline-size: 80px;
    padding-block: 8px;
    padding-inline: 12px;
    text-align: center;
  }

  /* Mobile Leaves Break-Down Styles */
  .leaves-breakdown-content {
    padding: 16px !important;
  }

  .leaves-breakdown-item {
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    border: 1px solid rgba(var(--v-theme-outline), 0.12);
    border-radius: 10px;
    background-color: rgba(var(--v-theme-surface), 0.8);
    box-shadow: 0 1px 4px rgba(0, 0, 0, 5%);
    margin-block-end: 12px;
  }

  .leaves-breakdown-item:last-child {
    margin-block-end: 0;
  }

  .leaves-breakdown-info {
    display: flex;
    flex: 1;
    align-items: center;
    min-inline-size: 0;
  }

  .leaves-breakdown-icon {
    flex-shrink: 0;
    font-size: 22px !important;
    margin-inline-end: 12px;
  }

  .leaves-breakdown-label {
    color: rgba(var(--v-theme-on-surface), 0.9);
    font-size: 0.95rem;
    font-weight: 600;
  }

  .leaves-breakdown-value {
    flex-shrink: 0;
    border: 1px solid rgba(var(--v-theme-primary), 0.2);
    border-radius: 12px;
    background-color: rgba(var(--v-theme-primary), 0.15);
    color: rgba(var(--v-theme-primary), 0.95);
    font-size: 1.1rem;
    font-weight: 700;
    min-inline-size: 50px;
    padding-block: 8px;
    padding-inline: 14px;
    text-align: center;
  }
}

/* Tablet Responsive Styles */
@media (min-width: 601px) and (max-width: 959px) {
  .calendar-section {
    min-block-size: 350px;
  }

  .attendance-actions {
    gap: 10px;
  }

  .clock-in-btn,
  .clock-out-btn {
    block-size: 50px;
    font-size: 0.9rem;
  }

  /* Tablet Today's Status Styles */
  .todays-status-card {
    padding: 18px;
    border-radius: 14px;
  }

  .todays-status-header {
    gap: 16px;
    margin-block-end: 24px;
  }

  .todays-status-title {
    font-size: 1.1rem;
    font-weight: 700;
  }

  .todays-status-chip {
    font-size: 0.85rem;
    padding-block: 8px;
    padding-inline: 14px;
  }

  .todays-status-content {
    gap: 18px;
  }

  .todays-status-item {
    padding: 18px;
    border-radius: 12px;
  }

  .todays-status-item-icon {
    font-size: 20px !important;
    margin-inline-end: 14px;
  }

  .todays-status-item-label {
    font-size: 1rem;
    font-weight: 600;
  }

  .todays-status-item-time {
    font-size: 1rem;
    font-weight: 700;
    min-inline-size: 90px;
    padding-block: 10px;
    padding-inline: 14px;
  }

  /* Tablet Leaves Break-Down Styles */
  .leaves-breakdown-content {
    padding: 18px !important;
  }

  .leaves-breakdown-item {
    padding: 18px;
    border-radius: 12px;
    margin-block-end: 14px;
  }

  .leaves-breakdown-item:last-child {
    margin-block-end: 0;
  }

  .leaves-breakdown-icon {
    font-size: 24px !important;
    margin-inline-end: 14px;
  }

  .leaves-breakdown-label {
    font-size: 1rem;
    font-weight: 600;
  }

  .leaves-breakdown-value {
    font-size: 1.2rem;
    font-weight: 700;
    min-inline-size: 60px;
    padding-block: 10px;
    padding-inline: 16px;
  }
}

/* Desktop Responsive Styles */
@media (min-width: 961px) {
  .calendar-section {
    min-block-size: 400px;
  }

  /* Desktop Leaves Break-Down Styles */
  .leaves-breakdown-content {
    padding: 12px !important;
  }

  .leaves-breakdown-item {
    padding-block: 18px;
    padding-inline: 0;
  }

  .leaves-breakdown-icon {
    font-size: 24px !important;
    margin-inline-end: 12px;
  }

  .leaves-breakdown-label {
    font-size: 1rem;
  }

  .leaves-breakdown-value {
    font-size: 1.3rem;
  }
}

/* Attendance Statistics Cards */
.attendance-stat-card {
  position: relative;
  overflow: hidden;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 10%);
  transition: all 0.3s ease;
}

.attendance-stat-card::after {
  position: absolute;
  border-radius: 0 0 12px 12px;
  block-size: 4px;
  content: "";
  inset-block-end: 0;
  inset-inline: 0;
}

/* Early Check-In Card */
.early-checkin-card::after {
  background-color: #ff9800;
}

.early-checkin-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 8px;
  border-radius: 8px;
  background-color: #fff3e0;
  block-size: 40px;
  inline-size: 40px;
}

.early-checkin-icon .v-icon {
  color: #ff9800 !important;
}

/* Late Check-In Card */
.late-checkin-card::after {
  background-color: #ffc107;
}

.late-checkin-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 8px;
  border-radius: 8px;
  background-color: #fff8e1;
  block-size: 40px;
  inline-size: 40px;
}

.late-checkin-icon .v-icon {
  color: #ffc107 !important;
}

/* Early Check-Out Card */
.early-checkout-card::after {
  background-color: #f44336;
}

.early-checkout-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 8px;
  border-radius: 8px;
  background-color: #ffebee;
  block-size: 40px;
  inline-size: 40px;
}

.early-checkout-icon .v-icon {
  color: #f44336 !important;
}

/* Late Check-Out Card */
.late-checkout-card::after {
  background-color: #9e9e9e;
}

.late-checkout-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 8px;
  border-radius: 8px;
  background-color: #f5f5f5;
  block-size: 40px;
  inline-size: 40px;
}

.late-checkout-icon .v-icon {
  color: #9e9e9e !important;
}

.stat-number {
  font-weight: 700;
  line-height: 1.2;
}

.stat-label {
  font-weight: 500;
  line-height: 1.3;
}

/* Month/Year Filter Styles */
.month-select {
  max-inline-size: 180px;
  min-inline-size: 160px;
}

.month-select :deep(.v-field__input) {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.month-select :deep(.v-list-item-title) {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.year-select {
  max-inline-size: 120px;
  min-inline-size: 100px;
}

/* Card Hover Effects (Desktop Only) */
@media (min-width: 768px) {
  .v-card {
    transition: all 0.3s ease;
  }

  .v-card:hover {
    box-shadow: 0 8px 25px rgba(0, 0, 0, 15%);
    transform: translateY(-2px);
  }
}

/* FullCalendar Mobile Optimizations */
@media (max-width: 768px) {
  :deep(.fc-header-toolbar) {
    flex-direction: column;
    align-items: center;
    padding: 0.5rem;
    gap: 0.75rem;
  }

  :deep(.fc-toolbar-title) {
    font-size: 1.1rem;
    margin-block: 0.5rem;
    text-align: center;
  }

  :deep(.fc-toolbar-chunk) {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
  }

  :deep(.fc-today-button) {
    margin-block: 0.5rem 0;
  }

  :deep(.fc-button-group) {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.5rem;
    inline-size: 100%;
  }

  :deep(.fc-button) {
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 500;
    min-inline-size: 60px;
    padding-block: 0.5rem;
    padding-inline: 0.75rem;
  }

  :deep(.fc-button:not(:disabled)) {
    border-color: rgb(var(--v-theme-primary));
    background-color: rgb(var(--v-theme-primary));
    color: white;
  }

  :deep(.fc-button:not(:disabled):hover) {
    border-color: rgb(var(--v-theme-primary));
    background-color: rgb(var(--v-theme-primary));
    opacity: 0.9;
  }

  :deep(.fc-button:disabled) {
    border-color: #e0e0e0;
    background-color: #e0e0e0;
    color: #9e9e9e;
  }

  :deep(.fc-daygrid-day) {
    min-block-size: 40px;
  }

  :deep(.fc-daygrid-day-number) {
    padding: 0.25rem;
    font-size: 0.9rem;
  }

  :deep(.fc-event-title) {
    font-size: 0.75rem;
  }
}

/* Very Small Screens */
@media (max-width: 480px) {
  .calendar-section {
    min-block-size: 250px;
  }

  .attendance-title {
    font-size: 0.95rem;
    padding-block: 10px 4px;
    padding-inline: 10px;
  }

  .attendance-content {
    padding-block: 4px 10px;
    padding-inline: 10px;
  }

  .clock-in-btn,
  .clock-out-btn {
    block-size: 48px;
    font-size: 0.85rem;
  }

  .office-time-text {
    font-size: 0.8rem;
  }

  /* Extra small screen calendar optimizations */
  :deep(.fc-header-toolbar) {
    padding: 0.25rem;
    gap: 0.5rem;
  }

  :deep(.fc-toolbar-title) {
    font-size: 1rem;
  }

  :deep(.fc-button) {
    font-size: 0.7rem;
    min-inline-size: 50px;
    padding-block: 0.4rem;
    padding-inline: 0.5rem;
  }

  :deep(.fc-button-group) {
    gap: 0.25rem;
  }

  :deep(.fc-daygrid-day) {
    min-block-size: 35px;
  }

  :deep(.fc-daygrid-day-number) {
    padding: 0.2rem;
    font-size: 0.8rem;
  }

  :deep(.fc-event-title) {
    font-size: 0.7rem;
  }
}

/* Attendance Table Styles */
.attendance-table-card {
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 10%);
}

.attendance-table-card .v-card-title {
  font-size: 1.1rem;
  font-weight: 500;
  padding-block: 16px 8px;
  padding-inline: 16px;
}

/* Time Cell Styles */
.time-cell {
  font-size: 0.875rem;
  font-weight: 500;
  white-space: nowrap;
}

/* Status Chip Styles */
.status-chip {
  font-weight: 500;
  white-space: nowrap;
}

/* Mobile Responsive Styles for Attendance Table */
@media (max-width: 960px) {
  .attendance-table-card :deep(.v-data-table-header) {
    font-size: 0.75rem;
  }

  .attendance-table-card :deep(.v-data-table__td) {
    padding-block: 8px;
    padding-inline: 4px;
  }

  .attendance-table-card :deep(.v-data-table__th) {
    padding-block: 8px;
    padding-inline: 4px;
  }

  .time-cell {
    font-size: 0.75rem;
  }

  .status-chip {
    font-size: 0.7rem;
  }
}

@media (max-width: 600px) {
  .attendance-table-card :deep(.v-data-table__td) {
    padding-block: 6px;
    padding-inline: 2px;
  }

  .attendance-table-card :deep(.v-data-table__th) {
    padding-block: 6px;
    padding-inline: 2px;
  }

  .time-cell {
    font-size: 0.7rem;
  }

  .status-chip {
    font-size: 0.65rem;
  }
}
</style>
