export function requiredRule(value, message = "This field is required") {
  return !!value || message;
}

export function emailRule(value, message = "Invalid email format") {
  if (!value) return true; // Allow empty if not required
  return /.+@.+\..+/.test(value) || message;
}

export function phoneRule(
  value,
  message = "Invalid phone number (11-15 digits)"
) {
  if (!value) return true; // Allow empty if not required
  return /^[0-9]{11,15}$/.test(value) || message;
}

export function cnicRule(value, message = "CNIC must be exactly 13 digits") {
  if (!value) return true; // Allow empty if not required
  return /^[0-9]{13}$/.test(value) || message;
}

export function passwordRule(
  value,
  message = "Password must be at least 8 characters"
) {
  if (!value) return true; // Allow empty if not required
  return (value && value.length >= 8) || message;
}

export function employeeCodeRule(
  value,
  message = "Only letters, numbers, and dashes are allowed"
) {
  if (!value) return true; // Allow empty if not required
  return /^[A-Za-z0-9-]+$/.test(value) || message;
}

export function nameRule(
  value,
  message = "Name must contain only letters and spaces"
) {
  if (!value) return true; // Allow empty if not required
  return /^[A-Za-z\s]+$/.test(value) || message;
}

export function numberRule(value, message = "Must be a valid number") {
  if (!value) return true; // Allow empty if not required
  return (!isNaN(value) && value >= 0) || message;
}

export function dateRule(value, message = "Please select a valid date") {
  if (!value) return true; // Allow empty if not required
  return value instanceof Date || !isNaN(Date.parse(value)) || message;
}

export function fileRule(value, message = "Please select a file") {
  if (!value) return true; // Allow empty if not required
  return value instanceof File || message;
}

export function slugRule(
  value,
  message = "Only letters, numbers, and dashes are allowed"
) {
  if (!value) return true; // Allow empty if not required
  return /^[A-Za-z0-9-_]+$/.test(value) || message;
}

export function attendanceDaysRule(
  value,
  message = "At least one work day must be selected"
) {
  if (!value || !Array.isArray(value)) return message;

  const hasWorkDay = value.some(
    (day) => day.inside_office || day.outside_office
  );

  return hasWorkDay || message;
}

export function imageFileRule(
  value,
  message = "Please select a valid image file (PNG, JPEG, JPG - Max 10MB)"
) {
  if (!value) return true; // Allow empty if not required

  if (!(value instanceof File)) return message;

  // Check file type
  const allowedTypes = ["image/png", "image/jpeg", "image/jpg"];
  if (!allowedTypes.includes(value.type)) {
    return "Only PNG, JPEG, and JPG files are allowed";
  }

  // Check file size (10MB = 10 * 1024 * 1024 bytes)
  const maxSize = 10 * 1024 * 1024;
  if (value.size > maxSize) {
    return "File size must not exceed 10MB";
  }

  return true;
}

export function pdfFileRule(
  value,
  message = "Please select a valid PDF file (Max 10MB)"
) {
  if (!value) return true; // Allow empty if not required

  if (!(value instanceof File)) return message;

  // Check file type
  if (value.type !== "application/pdf") {
    return "Only PDF files are allowed";
  }

  // Check file size (10MB = 10 * 1024 * 1024 bytes)
  const maxSize = 10 * 1024 * 1024;
  if (value.size > maxSize) {
    return "File size must not exceed 10MB";
  }

  return true;
}

export function leaveDateRule(
  startDate,
  endDate,
  durationType,
  message = "Invalid date range for selected duration type"
) {
  if (!startDate || !endDate || !durationType) return true; // Allow empty if not required

  const start = new Date(startDate);
  const end = new Date(endDate);

  // Check if dates are valid
  if (isNaN(start.getTime()) || isNaN(end.getTime())) {
    return "Please select valid dates";
  }

  // Check if start date is before or equal to end date
  if (start > end) {
    return "Start date must be before or equal to end date";
  }

  // Calculate difference in days
  const diffTime = Math.abs(end - start);
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; // +1 to include both dates

  // Apply duration type rules
  switch (durationType) {
    case 1: // Full Day - Can span multiple dates
      return true; // No restriction
    case 2: // Half Day - Can only apply for one date
      return (
        diffDays === 1 || "Half Day leave can only be applied for one date"
      );
    case 3: // Short Leave - Can only apply for one date
      return diffDays === 1 || "Short Leave can only be applied for one date";
    default:
      return true;
  }
}

export function endDateRule(
  value,
  startDate,
  durationType,
  message = "Invalid end date for selected duration type"
) {
  if (!value || !startDate || !durationType) return true; // Allow empty if not required

  return leaveDateRule(startDate, value, durationType, message);
}
