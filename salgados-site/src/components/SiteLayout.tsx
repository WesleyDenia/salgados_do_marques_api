import { Outlet } from "react-router-dom";
import { Header } from "@/components/Header";
import { Footer } from "@/components/Footer";
import { CookieConsentProvider } from "@/components/CookieConsentProvider";

export function SiteLayout() {
  return (
    <CookieConsentProvider>
      <div className="min-h-screen bg-background">
        <Header />
        <Outlet />
        <Footer />
      </div>
    </CookieConsentProvider>
  );
}
