import { describe, expect, it, vi, beforeEach } from "vitest";
import { render, screen, waitFor } from "@testing-library/react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { GoogleReviewsSection } from "@/components/home/GoogleReviewsSection";

describe("GoogleReviewsSection", () => {
  beforeEach(() => {
    vi.restoreAllMocks();
    Object.defineProperty(window.navigator, "userAgent", {
      configurable: true,
      value: "Mozilla/5.0 Chrome/123.0.0.0 Safari/537.36",
    });
  });

  it("renders available reviews returned by the backend", async () => {
    vi.spyOn(globalThis, "fetch").mockResolvedValue({
      ok: true,
      json: async () => ({
        data: [
          {
            author_name: "Ana",
            rating: 5,
            text: "Excelente atendimento e produtos muito saborosos.",
            relative_time_description: "3 semanas atrás",
            google_maps_url: "https://maps.google.com/review-1",
          },
          {
            author_name: "Bruno",
            rating: 4,
            text: "Muito bom para festas e encomendas maiores.",
            relative_time_description: "1 mês atrás",
            google_maps_url: "https://maps.google.com/review-2",
          },
        ],
        meta: {
          source: "Google Maps",
          ordered_by: "relevance",
          minimum_rating: 4,
        },
      }),
    } as Response);

    const queryClient = new QueryClient({
      defaultOptions: {
        queries: {
          retry: false,
        },
      },
    });

    render(
      <QueryClientProvider client={queryClient}>
        <GoogleReviewsSection />
      </QueryClientProvider>,
    );

    await waitFor(() => {
      expect(screen.getByText("Ana")).toBeInTheDocument();
      expect(screen.getByText("Bruno")).toBeInTheDocument();
    });

    expect(screen.getByText(/reviews no google maps/i)).toBeInTheDocument();
    expect(screen.getByRole("link", { name: /ver mais no google maps/i })).toHaveAttribute(
      "href",
      "https://maps.google.com/review-1",
    );
  });
});
