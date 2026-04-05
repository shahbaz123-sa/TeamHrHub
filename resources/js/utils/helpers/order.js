const allowedTransitions = {
  pending: ["awaiting_payment", "cancelled"],
  awaiting_payment: ["processing", "refunded", "cancelled"],
  processing: ["completed", "cancelled", "refunded"],
};

export function getAllowed(status, paymentMethod) {
  const allowed = allowedTransitions[status] || [];
  const isCod = paymentMethod && String(paymentMethod).toLowerCase() === "cod";

  // Work on a copy so we don't mutate the source
  let result = [...allowed];

  // For COD orders, allow direct pending -> processing and remove awaiting_payment
  if (status === "pending" && isCod) {
    result = result.filter(function (s) {
      return s !== "awaiting_payment";
    });
    if (!result.includes("processing")) {
      result = ["processing", ...result];
    }
  }

  return result;
}

export function getPaymentMethods() {
  return [
    {
      title: "Cash on Delivery (COD)",
      value: "COD",
    },
    {
      title: "Bank Transfer",
      value: "bank",
    },
    {
      title: "Credit/Debit Card",
      value: "card",
    },
    {
      title: "Wallet",
      value: "wallet",
    },
  ];
}

export function getPaymentStatuses() {
  return [
    {
      title: "Paid",
      value: "paid",
    },
    {
      title: "Unpaid",
      value: "unpaid",
    },
    {
      title: "Failed",
      value: "failed",
    },
    {
      title: "Refunded",
      value: "refunded",
    },
  ];
}

export function getStatuses() {
  return [
    {
      title: "Pending",
      value: "pending",
    },
    {
      title: "Awaiting Payment",
      value: "awaiting_payment",
    },
    {
      title: "Processing",
      value: "processing",
    },
    {
      title: "Completed",
      value: "completed",
      description: "",
    },
    {
      title: "Cancelled",
      value: "cancelled",
      description: "",
    },
    {
      title: "Refunded",
      value: "refunded",
      description: "",
    },
  ];
}

export function resolvePaymentStatus(status) {
  if (status === "paid")
    return {
      text: "Paid",
      color: "success",
    };
  if (status === "unpaid")
    return {
      text: "Unpaid",
      color: "error",
    };
  if (status === "refunded")
    return {
      text: "Refunded",
      color: "secondary",
    };
  if (status === "failed")
    return {
      text: "Failed",
      color: "error",
    };
}

export function resolveStatus(status) {
  if (status === "pending")
    return {
      text: "Pending",
      color: "warning",
    };
  if (status === "awaiting_payment")
    return {
      text: "Awaiting Payment",
      color: "info",
    };
  if (status === "processing")
    return {
      text: "Processing",
      color: "info",
    };
  if (status === "completed")
    return {
      text: "Completed",
      color: "success",
    };
  if (status === "cancelled")
    return {
      text: "Cancelled",
      color: "primary",
    };
  if (status === "refunded")
    return {
      text: "Refunded",
      color: "warning",
    };
}
