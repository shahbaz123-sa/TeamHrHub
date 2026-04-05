import { padString } from "@/utils/helpers/str.js";

export function convertIntoHoursMins(totalMinutes) {
  const hours = Math.floor(totalMinutes / 60);
  const minutes = totalMinutes % 60;

  const hourStr = hours > 0 ? padString(hours.toString(), 2, "0") : "00";
  const minStr = minutes > 0 ? padString(minutes.toString(), 2, "0") : "00";

  return [hourStr, minStr].filter(Boolean).join(":");
}

export function convertTo12HourFormat(time24) {
  if (!time24) return "";

  const [hourStr, minuteStr] = time24.split(":");
  let hour = parseInt(hourStr, 10);
  const minute = minuteStr.padStart(2, "0");

  const ampm = hour >= 12 ? "PM" : "AM";
  hour = hour % 12 || 12; // Convert 0 -> 12 and 13+ -> 1+

  return `${hour}:${minute} ${ampm}`;
}
