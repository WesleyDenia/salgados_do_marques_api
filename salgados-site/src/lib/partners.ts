const DEFAULT_API_BASE_URL = "https://api.salgadosdomarques.pt/api/v1";

export interface Partner {
  id: number;
  name: string;
  slug: string;
  description: string;
  image_url?: string | null;
  active: boolean;
}

interface ApiListResponse<T> {
  data?: T[];
}

interface ApiObjectResponse<T> {
  data?: T | null;
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

function resolvePartnersEndpoint(path = "") {
  return `${resolveApiBaseUrl()}/public/partners${path}`;
}

export function resolvePartnerImageUrl(path?: string | null): string | undefined {
  if (!path) return undefined;

  const trimmed = path.trim();
  if (!trimmed) return undefined;

  if (/^https?:\/\//i.test(trimmed)) {
    return trimmed;
  }

  if (trimmed.startsWith("//")) {
    return `https:${trimmed}`;
  }

  const apiBaseUrl = resolveApiBaseUrl();
  const origin = apiBaseUrl.replace(/\/api\/v1$/, "");

  return `${origin}/${trimmed.replace(/^\/+/, "")}`;
}

export function buildPartnerPath(partner: Pick<Partner, "id" | "slug">) {
  return `/parceiros/${partner.id}-${partner.slug}`;
}

export function extractPartnerId(value?: string) {
  if (!value) return null;

  const [candidate] = value.split("-");
  const parsed = Number(candidate);

  return Number.isInteger(parsed) && parsed > 0 ? parsed : null;
}

export async function fetchPartners(): Promise<Partner[]> {
  const response = await fetch(resolvePartnersEndpoint(), {
    headers: {
      Accept: "application/json",
    },
  });

  if (!response.ok) {
    throw new Error("Não foi possível carregar os parceiros.");
  }

  const payload = (await response.json()) as ApiListResponse<Partner>;

  return (payload.data ?? []).sort((left, right) => left.name.localeCompare(right.name, "pt-PT"));
}

export async function fetchPartnerById(id: number): Promise<Partner | null> {
  const response = await fetch(resolvePartnersEndpoint(`/${id}`), {
    headers: {
      Accept: "application/json",
    },
  });

  if (response.status === 404) {
    return null;
  }

  if (!response.ok) {
    throw new Error("Não foi possível carregar o parceiro.");
  }

  const payload = (await response.json()) as ApiObjectResponse<Partner>;

  return payload.data ?? null;
}
