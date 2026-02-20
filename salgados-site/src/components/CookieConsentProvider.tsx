import { createContext, useContext, useEffect, useMemo, useState, type ReactNode } from "react";
import { Link } from "react-router-dom";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Checkbox } from "@/components/ui/checkbox";
import {
  COOKIE_CONSENT_EVENT,
  CookieConsent,
  defaultPreferences,
  readCookieConsent,
  writeCookieConsent,
} from "@/lib/cookie-consent";

type CookieConsentContextValue = {
  consent: CookieConsent | null;
  hasFunctionalConsent: boolean;
  openPreferences: () => void;
  acceptAll: () => void;
  rejectOptional: () => void;
  saveCustom: (preferences: {
    functional: boolean;
    analytics: boolean;
    marketing: boolean;
  }) => void;
};

const CookieConsentContext = createContext<CookieConsentContextValue | null>(null);

function createConsent(
  status: CookieConsent["status"],
  preferences: {
    functional: boolean;
    analytics: boolean;
    marketing: boolean;
  },
): CookieConsent {
  return {
    version: 1,
    status,
    updatedAt: new Date().toISOString(),
    preferences: {
      necessary: true,
      ...preferences,
    },
  };
}

export function CookieConsentProvider({ children }: { children: ReactNode }) {
  const [consent, setConsent] = useState<CookieConsent | null>(null);
  const [ready, setReady] = useState(false);
  const [preferencesOpen, setPreferencesOpen] = useState(false);
  const [functional, setFunctional] = useState(false);
  const [analytics, setAnalytics] = useState(false);
  const [marketing, setMarketing] = useState(false);

  useEffect(() => {
    const loaded = readCookieConsent();
    setConsent(loaded);
    if (loaded) {
      setFunctional(loaded.preferences.functional);
      setAnalytics(loaded.preferences.analytics);
      setMarketing(loaded.preferences.marketing);
    }
    setReady(true);
  }, []);

  useEffect(() => {
    const onUpdate = () => {
      const loaded = readCookieConsent();
      setConsent(loaded);
      if (loaded) {
        setFunctional(loaded.preferences.functional);
        setAnalytics(loaded.preferences.analytics);
        setMarketing(loaded.preferences.marketing);
      }
    };

    window.addEventListener(COOKIE_CONSENT_EVENT, onUpdate);
    return () => window.removeEventListener(COOKIE_CONSENT_EVENT, onUpdate);
  }, []);

  const acceptAll = () => {
    const next = createConsent("accepted_all", {
      functional: true,
      analytics: true,
      marketing: true,
    });
    writeCookieConsent(next);
    setConsent(next);
  };

  const rejectOptional = () => {
    const next = createConsent("rejected_all", {
      functional: false,
      analytics: false,
      marketing: false,
    });
    writeCookieConsent(next);
    setConsent(next);
  };

  const saveCustom = (preferences: {
    functional: boolean;
    analytics: boolean;
    marketing: boolean;
  }) => {
    const next = createConsent("custom", preferences);
    writeCookieConsent(next);
    setConsent(next);
    setPreferencesOpen(false);
  };

  const openPreferences = () => {
    const loaded = consent?.preferences ?? defaultPreferences;
    setFunctional(loaded.functional);
    setAnalytics(loaded.analytics);
    setMarketing(loaded.marketing);
    setPreferencesOpen(true);
  };

  const showBanner = ready && !consent;

  const value = useMemo<CookieConsentContextValue>(
    () => ({
      consent,
      hasFunctionalConsent: Boolean(consent?.preferences.functional),
      openPreferences,
      acceptAll,
      rejectOptional,
      saveCustom,
    }),
    [consent],
  );

  return (
    <CookieConsentContext.Provider value={value}>
      {children}

      {showBanner && (
        <div className="fixed bottom-0 inset-x-0 z-[60] border-t border-border bg-background/95 backdrop-blur">
          <div className="section-container py-4">
            <p className="text-sm text-foreground">
              Utilizamos cookies necessários e, com o seu consentimento, cookies opcionais para funcionalidades
              adicionais e análise. Leia os{" "}
              <Link to="/termos" className="text-primary hover:underline">
                Termos e Condições
              </Link>{" "}
              e a{" "}
              <Link to="/privacidade" className="text-primary hover:underline">
                Política de Privacidade
              </Link>
              .
            </p>
            <div className="mt-3 flex flex-wrap gap-2">
              <Button size="sm" onClick={acceptAll}>
                Aceitar tudo
              </Button>
              <Button size="sm" variant="outline" onClick={rejectOptional}>
                Recusar opcionais
              </Button>
              <Button size="sm" variant="secondary" onClick={openPreferences}>
                Preferências
              </Button>
            </div>
          </div>
        </div>
      )}

      <Dialog open={preferencesOpen} onOpenChange={setPreferencesOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Preferências de Cookies</DialogTitle>
            <DialogDescription>
              Pode gerir os cookies opcionais a qualquer momento. Os cookies necessários permanecem sempre ativos.
            </DialogDescription>
          </DialogHeader>

          <div className="space-y-4">
            <label className="flex items-start gap-3">
              <Checkbox checked disabled />
              <span className="text-sm">
                <span className="font-medium text-foreground">Necessários</span>
                <span className="block text-muted-foreground">Essenciais para funcionamento e segurança do site.</span>
              </span>
            </label>

            <label className="flex items-start gap-3 cursor-pointer">
              <Checkbox checked={functional} onCheckedChange={(value) => setFunctional(value === true)} />
              <span className="text-sm">
                <span className="font-medium text-foreground">Funcionais</span>
                <span className="block text-muted-foreground">
                  Permitem conteúdos externos e recursos adicionais, como mapa incorporado.
                </span>
              </span>
            </label>

            <label className="flex items-start gap-3 cursor-pointer">
              <Checkbox checked={analytics} onCheckedChange={(value) => setAnalytics(value === true)} />
              <span className="text-sm">
                <span className="font-medium text-foreground">Analíticos</span>
                <span className="block text-muted-foreground">
                  Ajudam a medir desempenho e melhorar a experiência do utilizador.
                </span>
              </span>
            </label>

            <label className="flex items-start gap-3 cursor-pointer">
              <Checkbox checked={marketing} onCheckedChange={(value) => setMarketing(value === true)} />
              <span className="text-sm">
                <span className="font-medium text-foreground">Marketing</span>
                <span className="block text-muted-foreground">Permitem campanhas e personalização de conteúdos.</span>
              </span>
            </label>
          </div>

          <DialogFooter>
            <Button
              variant="outline"
              onClick={() => {
                setFunctional(false);
                setAnalytics(false);
                setMarketing(false);
                rejectOptional();
                setPreferencesOpen(false);
              }}
            >
              Recusar opcionais
            </Button>
            <Button
              onClick={() =>
                saveCustom({
                  functional,
                  analytics,
                  marketing,
                })
              }
            >
              Guardar preferências
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </CookieConsentContext.Provider>
  );
}

export function useCookieConsent() {
  const context = useContext(CookieConsentContext);
  if (!context) {
    throw new Error("useCookieConsent must be used within CookieConsentProvider");
  }
  return context;
}
