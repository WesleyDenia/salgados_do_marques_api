const API_BASE_URL = (import.meta.env.VITE_API_BASE_URL as string | undefined)?.replace(/\/$/, "") ?? "";
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
  const response = await fetch(`${API_BASE_URL}/api/v1/google-reviews`, {
    headers: {
      Accept: "application/json",
    },
  });

  if (!response.ok) {
    throw new Error(`Failed to fetch Google reviews: ${response.status}`);
  }

  return response.json() as Promise<GoogleReviewsResponse>;
}
