export type CookiePreferences = {
  necessary: true;
  functional: boolean;
  analytics: boolean;
  marketing: boolean;
};

export type CookieConsent = {
  version: number;
  status: "accepted_all" | "rejected_all" | "custom";
  updatedAt: string;
  preferences: CookiePreferences;
};

export const COOKIE_CONSENT_STORAGE_KEY = "salgados_cookie_consent_v1";
export const COOKIE_CONSENT_EVENT = "salgados-cookie-consent-updated";

export const defaultPreferences: CookiePreferences = {
  necessary: true,
  functional: false,
  analytics: false,
  marketing: false,
};

export function parseCookieConsent(raw: string | null): CookieConsent | null {
  if (!raw) {
    return null;
  }

  try {
    const parsed = JSON.parse(raw) as CookieConsent;
    if (!parsed || typeof parsed !== "object") {
      return null;
    }
    if (!parsed.preferences || typeof parsed.preferences !== "object") {
      return null;
    }

    return {
      version: Number(parsed.version) || 1,
      status: parsed.status ?? "custom",
      updatedAt: parsed.updatedAt ?? new Date().toISOString(),
      preferences: {
        necessary: true,
        functional: Boolean(parsed.preferences.functional),
        analytics: Boolean(parsed.preferences.analytics),
        marketing: Boolean(parsed.preferences.marketing),
      },
    };
  } catch {
    return null;
  }
}

export function readCookieConsent(): CookieConsent | null {
  if (typeof window === "undefined") {
    return null;
  }
  return parseCookieConsent(window.localStorage.getItem(COOKIE_CONSENT_STORAGE_KEY));
}

export function writeCookieConsent(consent: CookieConsent): void {
  if (typeof window === "undefined") {
    return;
  }
  window.localStorage.setItem(COOKIE_CONSENT_STORAGE_KEY, JSON.stringify(consent));
  window.dispatchEvent(new Event(COOKIE_CONSENT_EVENT));
}
