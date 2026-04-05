<script setup>
import dayGridPlugin from "@fullcalendar/daygrid";
import interactionPlugin from "@fullcalendar/interaction";
import timeGridPlugin from "@fullcalendar/timegrid";
import FullCalendar from "@fullcalendar/vue3";
import { ref } from "vue";

const calendarOptions = ref({
  plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
  initialView: "dayGridMonth",
  headerToolbar: {
    left: "prev,next",
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
    month: "Month",
    week: "Week",
    day: "Day",
  },
  // Mobile responsive configuration
  responsive: true,
  mobileBreakpoint: 768,
});
</script>
<template>
  <VCard :height="523">
    <div class="calendar-section pa-5">
      <FullCalendar :options="calendarOptions"/>
    </div>
  </VCard>
</template>
<style scoped>
.calendar-section {
  margin-block-start: 1rem;
  min-block-size: 400px;
  overflow-x: auto;
}

.calendar-section :deep(a) {
  color: #000 !important;
}

.calendar-section :deep(.fc .fc-button .fc-icon) {
  margin-block-start: 1px;
  vertical-align: unset;
}

.calendar-section :deep(.fc .fc-button-group) {
  max-block-size: 36px;
}

/* General button improvements - matching employee dashboard */
.calendar-section :deep(.fc-button) {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 1px solid transparent;
  cursor: pointer;
  transition: all 0.2s ease;
}

.calendar-section :deep(.fc-button:not(:disabled)) {
  border-color: rgb(var(--v-theme-primary));
  background-color: rgb(var(--v-theme-primary));
  color: white;
}

.calendar-section :deep(.fc-button:not(:disabled):hover) {
  border-color: rgb(var(--v-theme-primary));
  background-color: rgb(var(--v-theme-primary));
  opacity: 0.9;
}

.calendar-section :deep(.fc-button:not(:disabled).fc-button-active) {
  border-color: rgb(var(--v-theme-primary));
  background-color: rgb(var(--v-theme-primary));
  box-shadow: 0 2px 4px rgba(var(--v-theme-primary), 0.3);
  color: white;
  opacity: 0.8;
}

.calendar-section :deep(.fc-button:not(:disabled).fc-button-active:hover) {
  border-color: rgb(var(--v-theme-primary));
  background-color: rgb(var(--v-theme-primary));
  box-shadow: 0 4px 8px rgba(var(--v-theme-primary), 0.4);
  opacity: 0.7;
}

.calendar-section :deep(.fc-button:disabled) {
  border-color: #e0e0e0;
  background-color: #e0e0e0;
  color: #9e9e9e;
}

.calendar-section :deep(.fc-button:focus) {
  outline: 2px solid rgb(var(--v-theme-primary));
  outline-offset: 2px;
}

.calendar-section :deep(.fc-button:active) {
  transform: translateY(1px);
}

/* Mobile Responsive Styles - matching employee dashboard */
@media (max-width: 600px) {
  .calendar-section {
    margin-block-start: 0.5rem;
    min-block-size: 280px;
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

/* FullCalendar Mobile Optimizations - matching employee dashboard */
@media (max-width: 768px) {
  .calendar-section :deep(.fc-header-toolbar) {
    flex-direction: column;
    align-items: center;
    padding: 0.5rem;
    gap: 0.75rem;
  }

  .calendar-section :deep(.fc-toolbar-title) {
    font-size: 1.1rem;
    margin-block: 0.5rem;
    text-align: center;
  }

  .calendar-section :deep(.fc-toolbar-chunk) {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
  }

  .calendar-section :deep(.fc-today-button) {
    margin-block: 0.5rem 0;
  }

  .calendar-section :deep(.fc-button-group) {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.5rem;
    inline-size: 100%;
  }

  .calendar-section :deep(.fc-button) {
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 500;
    min-inline-size: 60px;
    padding-block: 0.5rem;
    padding-inline: 0.75rem;
  }

  .calendar-section :deep(.fc-button:not(:disabled)) {
    border-color: rgb(var(--v-theme-primary));
    background-color: rgb(var(--v-theme-primary));
    color: white;
  }

  .calendar-section :deep(.fc-button:not(:disabled):hover) {
    border-color: rgb(var(--v-theme-primary));
    background-color: rgb(var(--v-theme-primary));
    opacity: 0.9;
  }

  .calendar-section :deep(.fc-button:disabled) {
    border-color: #e0e0e0;
    background-color: #e0e0e0;
    color: #9e9e9e;
  }

  .calendar-section :deep(.fc-daygrid-day) {
    min-block-size: 40px;
  }

  .calendar-section :deep(.fc-daygrid-day-number) {
    padding: 0.25rem;
    font-size: 0.9rem;
  }

  .calendar-section :deep(.fc-event-title) {
    font-size: 0.75rem;
  }
}

/* Very Small Screens - matching employee dashboard */
@media (max-width: 480px) {
  .calendar-section {
    min-block-size: 250px;
  }

  /* Extra small screen calendar optimizations */
  .calendar-section :deep(.fc-header-toolbar) {
    padding: 0.25rem;
    gap: 0.5rem;
  }

  .calendar-section :deep(.fc-toolbar-title) {
    font-size: 1rem;
  }

  .calendar-section :deep(.fc-button) {
    font-size: 0.7rem;
    min-inline-size: 50px;
    padding-block: 0.4rem;
    padding-inline: 0.5rem;
  }

  .calendar-section :deep(.fc-button-group) {
    gap: 0.25rem;
  }

  .calendar-section :deep(.fc-daygrid-day) {
    min-block-size: 35px;
  }

  .calendar-section :deep(.fc-daygrid-day-number) {
    padding: 0.2rem;
    font-size: 0.8rem;
  }

  .calendar-section :deep(.fc-event-title) {
    font-size: 0.7rem;
  }
}
</style>
