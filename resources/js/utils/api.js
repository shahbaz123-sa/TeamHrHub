import { ofetch } from "ofetch";

const SERVICE_UNAVAILABLE_ROUTE = "/503";

const handleServiceUnavailable = () => {
  $toast.error("Service is temporarily unavailable. Please try again shortly.");
  if (typeof window !== "undefined" && window.location.pathname !== SERVICE_UNAVAILABLE_ROUTE) {
    window.location.href = SERVICE_UNAVAILABLE_ROUTE;
  }
};

export const $api = ofetch.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || "/api",
  async onRequest({ options }) {
    const accessToken = useCookie("accessToken").value;
    if (accessToken)
      options.headers.append("Authorization", `Bearer ${accessToken}`);
  },

  async onResponse({ response }) {
    if (response.status === 503) {
      handleServiceUnavailable();
      return;
    }

    if (response.status === 500) {
      const data = response?._data;
      const message = data?.message || "";

      const isAuthError =
        response.status === 401 ||
        (typeof data === 'string' && data.includes("Route [login] not defined")) ||
        (typeof message === 'string' && message.includes("Route [login] not defined"));

      if (isAuthError) {
        // Clear frontend cookies
        useCookie("userData").value = null;
        useCookie("accessToken").value = null;

        // Redirect to login page
        window.location.href = "/login";
      }
    }
  },
  async onResponseError({ response }) {
    if (response.status === 503) {
      handleServiceUnavailable();
      throw response;
    }

    // Don't show global toasts for these status codes - let components handle them
    const componentHandledErrors = [401, 403, 422, 500];
    
    if (componentHandledErrors.includes(response.status)) {
      // Re-throw the error so components can handle it
      throw response;
    }
    
    // Only handle unexpected errors globally
    if (response.status >= 500) {
      $toast.error("Server error. Please try again later.");
    } else {
      $toast.error("An unexpected error occurred.");
    }
  },
});
