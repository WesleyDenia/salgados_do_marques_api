const DEFAULT_API_BASE_URL = "https://api.salgadosdomarques.pt/api/v1";

export interface AppTesterPayload {
  name: string;
  email: string;
  phone: string;
  operating_system: "android" | "ios";
  consent: boolean;
  source_path?: string;
}

interface AppTesterResponse {
  message?: string;
  errors?: Record<string, string[]>;
  data?: {
    id: number;
    eligible_for_current_phase: boolean;
  };
}

function resolveApiBaseUrl() {
  const configuredBase =
    (import.meta.env.VITE_API_BASE_URL as string | undefined)?.replace(/\/$/, "") ??
    DEFAULT_API_BASE_URL;

  if (configuredBase.endsWith("/api/v1")) {
    return configuredBase;
  }

  return `${configuredBase}/api/v1`;
}

export async function submitAppTester(payload: AppTesterPayload) {
  const response = await fetch(`${resolveApiBaseUrl()}/testers`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
    },
    body: JSON.stringify(payload),
  });

  const data = (await response.json().catch(() => ({}))) as AppTesterResponse;

  if (!response.ok) {
    const firstError = data.errors
      ? Object.values(data.errors).flat().find(Boolean)
      : undefined;

    throw new Error(firstError ?? "Não foi possível registar o seu pedido neste momento.");
  }

  return data;
}
