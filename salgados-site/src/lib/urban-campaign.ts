const DEFAULT_API_BASE_URL = "https://api.salgadosdomarques.pt/api/v1";

export interface UrbanCampaignOption {
  id: number;
  response: string;
}

export interface UrbanCampaignQuestion {
  id: number;
  code_type: string;
  question: string;
  secret_number: number;
  collection_label: string;
  eyebrow?: string | null;
  riddle: string[];
  responses: UrbanCampaignOption[];
}

export interface UrbanCampaignChallenge {
  id: number;
  type: string;
  code: string;
  question: UrbanCampaignQuestion;
}

export interface UrbanCampaignAnswerResult {
  qr_code_id: number;
  type: string;
  question_id: number;
  response_id: number;
  is_correct: boolean;
  reward_percent: number;
  reward_type?: "money" | "percent";
  reward_amount?: number;
  collection_label: string;
}

export interface UrbanCampaignCouponClaimResult {
  id: number;
  phone: string;
  coupon_type: string;
  code: string | null;
  external_id: string | null;
  status: string;
  is_correct: boolean;
  discount_type: "money" | "percent";
  amount: number;
  generated_at?: string | null;
  expires_at?: string | null;
  erp_error?: string | null;
}

interface ApiObjectResponse<T> {
  data?: T | null;
  message?: string;
  errors?: Record<string, string[]>;
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

function firstApiError(payload: ApiObjectResponse<unknown>, fallback: string) {
  const firstError = payload.errors
    ? Object.values(payload.errors).flat().find(Boolean)
    : undefined;

  return firstError ?? payload.message ?? fallback;
}

export async function fetchUrbanCampaignChallenge(code: string): Promise<UrbanCampaignChallenge | null> {
  const response = await fetch(`${resolveApiBaseUrl()}/public/urban-campaign/qr-codes/${encodeURIComponent(code)}`, {
    headers: {
      Accept: "application/json",
    },
  });

  const payload = (await response.json().catch(() => ({}))) as ApiObjectResponse<UrbanCampaignChallenge>;

  if (response.status === 404 || response.status === 422) {
    return null;
  }

  if (!response.ok) {
    throw new Error(firstApiError(payload, "Não foi possível carregar esta pista."));
  }

  return payload.data ?? null;
}

export async function submitUrbanCampaignAnswer(payload: {
  code: string;
  question_id: number;
  response_id: number;
}): Promise<UrbanCampaignAnswerResult> {
  const response = await fetch(`${resolveApiBaseUrl()}/public/urban-campaign/answers`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
    },
    body: JSON.stringify(payload),
  });

  const data = (await response.json().catch(() => ({}))) as ApiObjectResponse<UrbanCampaignAnswerResult>;

  if (!response.ok) {
    throw new Error(firstApiError(data, "Não foi possível validar a resposta."));
  }

  if (!data.data) {
    throw new Error("Não foi possível validar a resposta.");
  }

  return data.data;
}

export async function claimUrbanCampaignCoupon(payload: {
  code: string;
  question_id: number;
  response_id: number;
  phone: string;
}): Promise<UrbanCampaignCouponClaimResult> {
  const response = await fetch(`${resolveApiBaseUrl()}/public/urban-campaign/claims`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
    },
    body: JSON.stringify(payload),
  });

  const data = (await response.json().catch(() => ({}))) as ApiObjectResponse<UrbanCampaignCouponClaimResult>;

  if (!response.ok) {
    throw new Error(firstApiError(data, "Não foi possível resgatar o cupão."));
  }

  if (!data.data) {
    throw new Error("Não foi possível resgatar o cupão.");
  }

  return data.data;
}
