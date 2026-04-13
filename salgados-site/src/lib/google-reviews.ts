const DEFAULT_API_BASE_URL = "https://api.salgadosdomarques.pt/api/v1";

function resolveReviewsEndpoint() {
  const configuredBase =
    (import.meta.env.VITE_API_BASE_URL as string | undefined)?.replace(/\/$/, "") ??
    DEFAULT_API_BASE_URL;

  if (configuredBase.endsWith("/api/v1")) {
    return `${configuredBase}/google-reviews`;
  }

  return `${configuredBase}/api/v1/google-reviews`;
}
export interface GoogleReview {
  author_name: string;
  author_url?: string | null;
  author_photo_url?: string | null;
  rating: number;
  text: string;
  published_at?: string | null;
  relative_time_description?: string;
  google_maps_url?: string | null;
}

export interface GoogleReviewsResponse {
  data: GoogleReview[];
  meta: {
    source: string;
    ordered_by: string;
    minimum_rating: number;
  };
}

export function shouldFetchGoogleReviews() {
  return !(
    typeof navigator !== "undefined" &&
    navigator.userAgent.toLowerCase().includes("jsdom")
  );
}

export async function fetchGoogleReviews(): Promise<GoogleReviewsResponse> {
  const response = await fetch(resolveReviewsEndpoint(), {
    headers: {
      Accept: "application/json",
    },
  });

  if (!response.ok) {
    throw new Error(`Failed to fetch Google reviews: ${response.status}`);
  }

  return response.json() as Promise<GoogleReviewsResponse>;
}
