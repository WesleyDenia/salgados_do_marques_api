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
    window.localStorage.clear();
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

      if (url.includes("/public/urban-campaign/claims") && init?.method === "POST") {
        return {
          ok: true,
          status: 200,
          json: async () => ({
            data: {
              id: 500,
              phone: "351912345678",
              coupon_type: "kibe",
              code: "VD-URBANA-10",
              external_id: "123",
              status: "synced",
              is_correct: true,
              discount_type: "percent",
              amount: 10,
              expires_at: "2026-09-15T23:59:59+00:00",
              erp_error: null,
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

    fireEvent.change(screen.getByLabelText(/número de telemóvel/i), {
      target: { value: "912 345 678" },
    });
    fireEvent.click(screen.getByRole("button", { name: /resgatar cupão/i }));

    await waitFor(() => {
      expect(screen.getByText("VD-URBANA-10")).toBeInTheDocument();
    });

    expect(fetchMock).toHaveBeenCalledWith(
      expect.stringContaining("/public/urban-campaign/claims"),
      expect.objectContaining({
        body: JSON.stringify({
          code: "qrxpto",
          question_id: 10,
          response_id: 100,
          phone: "351912345678",
        }),
      }),
    );
  });

  it("loads the stored answer after refresh and skips the answer options", async () => {
    window.localStorage.setItem(
      "urban-campaign-answer:QRXPTO:10",
      JSON.stringify({
        qr_code_id: 1,
        type: "kibe",
        question_id: 10,
        response_id: 100,
        is_correct: true,
        reward_percent: 10,
        collection_label: "Kibe",
      }),
    );

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

      throw new Error(`Unexpected request: ${url}`);
    });

    renderPage();

    expect(await screen.findByText("10%")).toBeInTheDocument();
    expect(screen.getByText(/encontraste: kibe/i)).toBeInTheDocument();
    expect(screen.queryByRole("button", { name: /revelar o segredo/i })).not.toBeInTheDocument();
    expect(screen.queryByText("Coxinha de Frango")).not.toBeInTheDocument();
    expect(fetchMock).not.toHaveBeenCalledWith(
      expect.stringContaining("/public/urban-campaign/answers"),
      expect.anything(),
    );
  });
});
