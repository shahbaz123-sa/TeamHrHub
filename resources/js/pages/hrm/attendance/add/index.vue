<template>
  <section>
    <VCard>
      <VCardTitle class="d-flex justify-space-between align-center attendance-header">
        <span class="attendance-title">Attendance</span>
        <div class="current-time d-none d-md-block">
          {{ currentDateTime }}
        </div>
        <div class="current-time-mobile d-md-none">
          {{ currentDateTime }}
        </div>
      </VCardTitle>

      <VCardText class="text-center">
        <div class="attendance-actions d-flex flex-column align-center gap-4">
          <div class="office-time mb-4">
            <h3 class="office-time-title">My Office Time: {{ officeHours }}</h3>
          </div>

          <div class="d-flex gap-4 attendance-buttons">
            <VBtn
              v-if="!attendance?.check_in"
              color="primary"
              :size="$vuetify.display.mobile ? 'large' : 'x-large'"
              @click="checkIn"
              :loading="isCheckingInOut"
              class="attendance-btn"
            >
              <VIcon start icon="tabler-login" />
              Check In
            </VBtn>

            <VBtn
              v-if="attendance?.check_in && !attendance?.check_out"
              color="error"
              :size="$vuetify.display.mobile ? 'large' : 'x-large'"
              @click="checkOut"
              :loading="isCheckingInOut"
              class="attendance-btn"
            >
              <VIcon start icon="tabler-logout" />
              Check Out
            </VBtn>
          </div>

          <div v-if="attendance?.check_in" class="check-in-time">
            <VChip 
              color="success" 
              :size="$vuetify.display.mobile ? 'default' : 'large'"
              class="status-chip"
            >
            <span class="chip-text">Checked in at: {{ formatTime(attendance.check_in) }}</span>
            </VChip>
          </div>

          <div v-if="attendance?.check_out" class="check-out-time">
            <VChip 
              color="error" 
              :size="$vuetify.display.mobile ? 'default' : 'large'"
              class="status-chip"
            >
              <span class="chip-text">Checked out at: {{ formatTime(attendance.check_out) }}</span>
            </VChip>
          </div>
        </div>

        <VDivider class="my-6" />

        <div class="calendar-section">
          <FullCalendar :options="calendarOptions" />
        </div>
      </VCardText>
    </VCard>
  </section>
</template>

<script setup>
import { convertTo12HourFormat } from "@/utils/helpers/date";
import dayGridPlugin from "@fullcalendar/daygrid";
import interactionPlugin from "@fullcalendar/interaction";
import timeGridPlugin from "@fullcalendar/timegrid";
import FullCalendar from "@fullcalendar/vue3";
import axios from "axios";
import { computed, onMounted, onUnmounted, ref } from "vue";

// Data
const currentDateTime = ref("");
const attendance = ref(null);
const officeHours = ref('');
const isCheckingInOut = ref(false);

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
});
const accessToken = useCookie("accessToken").value;
const userData = useCookie("userData").value;

// Computed
const formattedDate = computed(() => {
  return new Date().toLocaleDateString("en-US", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  });
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

const fetchTodayAttendance = async () => {
  try {
    const response = await axios.get("/api/attendance/my-attendance", {
      params: {
        date: new Date().toISOString().split("T")[0],
      },
      headers: {
        Authorization: `Bearer ${accessToken}`,
      },
    });
    if (response.data.data.length > 0) {
      attendance.value = response.data.data[0];
    }
  } catch (error) {
    console.error("Error fetching attendance:", error);
  }
};

const fetchAttendanceEvents = async () => {
  try {
    const response = await axios.get("/api/attendance/my-attendance", {
      params: {
        per_page: 100,
      },
      headers: {
        Authorization: `Bearer ${accessToken}`,
      },
    });
    calendarOptions.value.events = response.data.data.map((att) => ({
      title: att.check_in ? "Present" : "Absent",
      start: att.date,
      allDay: true,
      color: att.check_in ? "#28a745" : "#dc3545",
    }));
  } catch (error) {
    console.error("Error fetching attendance events:", error);
  }
};

const getCurrentPosition = () => {
  return new Promise((resolve, reject) => {
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(resolve, reject, { enableHighAccuracy: true });
    } else {
      reject(new Error("Geolocation is not supported by this browser."));
    }
  });
};

const checkIn = async () => {
  isCheckingInOut.value = true
  try {
    const position = await getCurrentPosition();
    const device = navigator.userAgent;
    
    // console.log(position)
    // console.log(device)

    const response = await $api("/api/attendance/check-in", {
      method: "POST",
      body: {
        latitude: position.coords.latitude,
        longitude: position.coords.longitude,
        accuracy: position.coords.accuracy,
        device
      },
      headers: {
        Authorization: `Bearer ${accessToken}`,
      },
    });
    attendance.value = response.data;
    fetchAttendanceEvents();
  } catch (error) {
    let message = error.response?.data?.message || "Something went wrong while checking in";
    if (error.response && error.response.status === 422) {
      message = Object.values(error.response?._data?.errors).slice(0, 2).join("\n")
    }

    $toast.error(message)
  } finally {
    isCheckingInOut.value = false
  }
};

const checkOut = async () => {
  isCheckingInOut.value = true
  try {
    const position = await getCurrentPosition();
    const response = await $api("/api/attendance/check-out", {
      method: "POST",
      body: {
        latitude: position.coords.latitude,
        longitude: position.coords.longitude,
      },
      headers: {
        Authorization: `Bearer ${accessToken}`,
      },
    });
    attendance.value = response.data;
    fetchAttendanceEvents();
  } catch (error) {
    $toast.error(error.response?.data?.message || "Something went wrong while checking out")
  } finally {
    isCheckingInOut.value = false
  }
};

const getDeviceType = () => {
  const ua = navigator.userAgent;
  if (/Mobi|Android|iPhone|iPad|iPod|Opera Mini|IEMobile|WPDesktop/i.test(ua)) {
    return 'mobile';
  }
  if (/Tablet|iPad/i.test(ua)) {
    return 'tablet';
  }
  return 'desktop/laptop';
};

const formatTime = (timeString) => {
  if (!timeString) return "";
  const [hours, minutes] = timeString.split(":");
  return `${hours}:${minutes}`;
};

let dateTimeInterval = null;

// Lifecycle
onMounted(() => {
  updateDateTime();
  dateTimeInterval = setInterval(updateDateTime, 1000);
  fetchTodayAttendance();
  fetchAttendanceEvents();

  const startTime = userData?.branch?.office_start_time || "09:00";
  const endTime = userData?.branch?.office_end_time || "18:00";
  officeHours.value = convertTo12HourFormat(startTime) + " to " + convertTo12HourFormat(endTime);
});

onUnmounted(() => {
  if(dateTimeInterval) clearInterval(dateTimeInterval)
})
</script>

<style scoped>
/* Header Styles */
.attendance-header {
  flex-wrap: wrap;
  gap: 0.5rem;
}

.attendance-title {
  font-size: 1.5rem;
  font-weight: 600;
}

.current-time {
  font-size: 1.1rem;
  font-weight: 500;
}

.current-time-mobile {
  font-size: 0.875rem;
  font-weight: 500;
  inline-size: 100%;
  margin-block-start: 0.5rem;
  text-align: center;
}

/* Office Time Styles */
.office-time-title {
  margin: 0;
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
  font-size: 1.2rem;
}

/* Attendance Actions */
.attendance-actions {
  padding-block: 1rem;
  padding-inline: 0;
}

.attendance-buttons {
  flex-wrap: wrap;
  justify-content: center;
}

.attendance-btn {
  min-inline-size: 140px;
}

/* Status Chips */
.status-chip {
  max-inline-size: 100%;
  text-align: center;
  white-space: normal;
}

.chip-text {
  word-break: break-word;
}

/* Calendar Section - Match Employee Dashboard */
.calendar-section {
  margin-block-start: 1rem;
  min-block-size: 400px;
  overflow-x: auto;
}

/* FullCalendar Styles */
:deep(.fc .fc-button .fc-icon) {
  vertical-align: unset !important;
}

:deep(.fc) {
  font-family: inherit;
}

:deep(.fc-header-toolbar) {
  flex-wrap: wrap;
  gap: 0.5rem;
}

:deep(.fc-toolbar-title) {
  font-size: 1.25rem;
}

/* FullCalendar Button Colors - Match Employee Dashboard */
:deep(.fc-button) {
  border-color: rgb(var(--v-theme-primary));
  background-color: rgb(var(--v-theme-primary));
  color: white;
}

:deep(.fc-button:hover) {
  border-color: rgb(var(--v-theme-primary));
  background-color: rgb(var(--v-theme-primary));
  opacity: 0.9;
}

:deep(.fc-button:focus) {
  border-color: rgb(var(--v-theme-primary));
  background-color: rgb(var(--v-theme-primary));
  box-shadow: 0 0 0 0.2rem rgba(var(--v-theme-primary), 0.25);
}

:deep(.fc-button:active) {
  border-color: rgb(var(--v-theme-primary));
  background-color: rgb(var(--v-theme-primary));
  opacity: 0.8;
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

/* Mobile Responsive Styles */
@media (max-width: 960px) {
  .attendance-title {
    font-size: 1.25rem;
  }

  .office-time-title {
    font-size: 1.1rem;
  }

  .attendance-actions {
    gap: 1rem;
  }

  .attendance-btn {
    min-inline-size: 120px;
  }
}

/* Tablet Responsive Styles */
@media (min-width: 601px) and (max-width: 960px) {
  .calendar-section {
    min-block-size: 350px;
  }
}

/* Desktop Responsive Styles */
@media (min-width: 961px) {
  .calendar-section {
    min-block-size: 400px;
  }
}

@media (max-width: 600px) {
  .attendance-header {
    flex-direction: column;
    align-items: center;
    text-align: center;
  }

  .attendance-title {
    font-size: 1.125rem;
  }

  .current-time-mobile {
    font-size: 0.75rem;
    margin-block-start: 0.25rem;
  }

  .office-time-title {
    font-size: 1rem;
    line-height: 1.4;
  }

  .attendance-actions {
    gap: 0.75rem;
    padding-block: 0.5rem;
  }

  .calendar-section {
    margin-block-start: 0.5rem;
    min-block-size: 280px;
  }

  .attendance-buttons {
    flex-direction: column;
    gap: 0.75rem;
    inline-size: 100%;
  }

  .attendance-btn {
    inline-size: 100%;
    min-inline-size: unset;
  }

  .status-chip {
    justify-content: center;
    inline-size: 100%;
  }

  .chip-text {
    font-size: 0.875rem;
  }

  /* FullCalendar Mobile Optimizations - Match Employee Dashboard */
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

@media (max-width: 400px) {
  .attendance-title {
    font-size: 1rem;
  }

  .office-time-title {
    font-size: 0.875rem;
  }

  .current-time-mobile {
    font-size: 0.6875rem;
  }

  .chip-text {
    font-size: 0.75rem;
  }

  .calendar-section {
    min-block-size: 250px;
  }

  /* Extra small screen calendar optimizations - Match Employee Dashboard */
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

  :deep(.fc-daygrid-day) {
    min-block-size: 35px;
  }

  :deep(.fc-daygrid-day-number) {
    font-size: 0.8rem;
  }

  :deep(.fc-event-title) {
    font-size: 0.7rem;
  }
}
</style>
