import axios from "axios";

export function getApiErrorMessage(
  error: any,
  fallback = "Falha ao processar sua requisição."
): string {
  if (axios.isAxiosError(error)) {
    const data: any = error.response?.data;

    // Laravel validation: { message, errors: { field: [msg] } }
    if (data?.errors && typeof data.errors === "object") {
      const firstKey = Object.keys(data.errors)[0];
      const firstMessage = data.errors[firstKey]?.[0];
      if (typeof firstMessage === "string") return firstMessage;
    }

    if (data?.message && typeof data.message === "string") {
      return data.message;
    }
  }

  if (typeof error?.message === "string") return error.message;
  return fallback;
}
