import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import { HelmetProvider } from "react-helmet-async";
import { beforeEach, describe, expect, it, vi } from "vitest";
import CampanhaUrbana from "@/pages/CampanhaUrbana";

function renderPage() {
  const queryClient = new QueryClient({
    defaultOptions: {
      queries: {
        retry: false,
      },
    },
  });

  return render(
    <HelmetProvider>
      <QueryClientProvider client={queryClient}>
        <CampanhaUrbana />
      </QueryClientProvider>
    </HelmetProvider>,
  );
}

describe("CampanhaUrbana", () => {
  beforeEach(() => {
    vi.restoreAllMocks();
    window.history.replaceState({}, "", "/campanha-urbana?code=qrxpto");
  });

  it("loads a challenge from the backend and reveals the reward after submitting an answer", async () => {
    const fetchMock = vi.spyOn(globalThis, "fetch").mockImplementation(async (input, init) => {
      const url = String(input);

      if (url.includes("/public/urban-campaign/qr-codes/qrxpto")) {
        return {
          ok: true,
          status: 200,
          json: async () => ({
            data: {
              id: 1,
              type: "kibe",
              code: "QRXPTO",
              question: {
                id: 10,
                code_type: "kibe",
                question: "Por fora sou dourado.\nQuem sou eu?",
                secret_number: 1,
                collection_label: "Kibe",
                eyebrow: "Uma pista de forma comprida",
                riddle: ["Por fora sou dourado.", "Quem sou eu?"],
                responses: [
                  { id: 100, response: "Kibe" },
                  { id: 101, response: "Coxinha de Frango" },
                ],
              },
            },
          }),
        } as Response;
      }

      if (url.includes("/public/urban-campaign/answers") && init?.method === "POST") {
        return {
          ok: true,
          status: 200,
          json: async () => ({
            data: {
              qr_code_id: 1,
              type: "kibe",
              question_id: 10,
              response_id: 100,
              is_correct: true,
              reward_percent: 10,
              collection_label: "Kibe",
            },
          }),
        } as Response;
      }

      throw new Error(`Unexpected request: ${url}`);
    });

    renderPage();

    expect(await screen.findByText("Por fora sou dourado.")).toBeInTheDocument();
    expect(screen.queryByText("10%")).not.toBeInTheDocument();

    fireEvent.click(screen.getByText("Kibe"));
    fireEvent.click(screen.getByRole("button", { name: /revelar o segredo/i }));

    await waitFor(() => {
      expect(screen.getByText("10%")).toBeInTheDocument();
    });
    expect(screen.getByText(/encontraste: kibe/i)).toBeInTheDocument();

    expect(fetchMock).toHaveBeenCalledWith(
      expect.stringContaining("/public/urban-campaign/answers"),
      expect.objectContaining({
        body: JSON.stringify({
          code: "qrxpto",
          question_id: 10,
          response_id: 100,
        }),
      }),
    );
  });
});
