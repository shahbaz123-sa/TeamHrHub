import { isEmpty as _isEmpty } from "@/@core/utils/helpers";

export function ucFirst(string) {
  if (!string) return "";
  return string.charAt(0).toUpperCase() + string.slice(1);
}

export function strReplace(string, search, replace) {
  if (!string || !search || !replace) return string;
  return string.split(search).join(replace);
}

export function humanize(str) {
  if (!str) return "";

  return str
    .replace(/[_-]+/g, " ") // replace _ or - with space
    .toLowerCase() // convert everything to lowercase
    .replace(/\b\w/g, (char) => char.toUpperCase()); // capitalize first letter of each word
}

export function padString(string, length, padWith) {
  return string.padStart(length, padWith);
}

export function formatLongText(text, maxLength = 50) {
  if (isEmpty(text) || text.length <= maxLength) return text;
  return text.substring(0, maxLength - 3) + "...";
}

export function isEmpty(string) {
  return !string || _isEmpty(string) || string === "null";
}

export function stripHtml(html) {
  const temp = document.createElement("div");
  temp.innerHTML = html;
  return temp.textContent || temp.innerText || "";
}
