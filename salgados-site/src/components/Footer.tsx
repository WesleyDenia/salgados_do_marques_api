import { Link } from "react-router-dom";
import { Clock, Mail, MapPin, MessageCircle, Phone } from "lucide-react";
import { useCookieConsent } from "@/components/CookieConsentProvider";

export function Footer() {
  const { openPreferences } = useCookieConsent();

  return (
    <footer className="bg-[hsl(var(--footer-background))] text-background">
      <div className="section-container py-16">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12">
          {/* Brand */}
          <div className="space-y-4">
            <h3 className="sr-only">Salgados do Marquês</h3>
            <Link
              to="/"
              aria-label="Salgados do Marquês"
              className="inline-flex items-center"
            >
              <img
                src="/logo_site.png"
                alt="Logo Salgados do Marquês"
                className="h-16 w-auto"
                loading="lazy"
                decoding="async"
              />
            </Link>
            <p className="text-background/70 text-sm leading-relaxed">
              Salgados para festa, eventos e encomendas com atendimento comercial
              direto por WhatsApp e base local em Pombal.
            </p>
            <a
              href="https://wa.me/351939197110"
              target="_blank"
              rel="noopener noreferrer"
              className="inline-flex items-center gap-2 text-sm font-semibold text-white"
            >
              <MessageCircle className="h-4 w-4" />
              Falar no WhatsApp
            </a>
          </div>

          {/* Quick Links */}
          <div className="space-y-4">
            <h4 className="font-display text-lg font-semibold">Navegação</h4>
            <nav className="flex flex-col gap-2">
              <Link to="/" className="text-background/70 hover:text-background transition-colors text-sm">
                Início
              </Link>
              <Link to="/festas" className="text-background/70 hover:text-background transition-colors text-sm">
                Festas
              </Link>
              <Link to="/produtos" className="text-background/70 hover:text-background transition-colors text-sm">
                Produtos
              </Link>
              <Link to="/parceiros" className="text-background/70 hover:text-background transition-colors text-sm">
                Parceiros
              </Link>
              <Link to="/sobre" className="text-background/70 hover:text-background transition-colors text-sm">
                Sobre Nós
              </Link>
              <Link to="/contactos" className="text-background/70 hover:text-background transition-colors text-sm">
                Contactos
              </Link>
            </nav>
          </div>

          {/* Contact Info */}
          <div className="space-y-4">
            <h4 className="font-display text-lg font-semibold">Contactos</h4>
            <div className="space-y-3">
              <a
                href="https://wa.me/351939197110"
                className="flex items-center gap-3 text-background/70 hover:text-background transition-colors text-sm"
              >
                <Phone className="w-4 h-4 flex-shrink-0" />
                <span>+351 939 197 110</span>
              </a>
              <a
                href="mailto:info@salgadosdomarques.pt"
                className="flex items-center gap-3 text-background/70 hover:text-background transition-colors text-sm"
              >
                <Mail className="w-4 h-4 flex-shrink-0" />
                <span>info@salgadosdomarques.pt</span>
              </a>
              <div className="flex items-start gap-3 text-background/70 text-sm">
                <MapPin className="w-4 h-4 flex-shrink-0 mt-0.5" />
                <div>
                  <p>Rua Filarmónica Artística Pombalense, 17</p>
                  <p>3100-430 Pombal, Leiria</p>
                </div>
              </div>
            </div>
          </div>

          {/* Hours */}
          <div className="space-y-4">
            <h4 className="font-display text-lg font-semibold">Horário</h4>
            <div className="space-y-3">
              <div className="flex items-start gap-3 text-background/70 text-sm">
                <Clock className="w-4 h-4 flex-shrink-0 mt-0.5" />
                <div>
                  <p>Terça a Sábado: 12h - 20h</p>
                  <p>Canal principal: WhatsApp</p>
                  <p>Página dedicada para parceiros disponível no site</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Bottom Bar */}
        <div className="border-t border-background/20 mt-12 pt-8">
          <div className="flex flex-col md:flex-row justify-between items-center gap-4">
            <p className="text-background/50 text-sm">
              © {new Date().getFullYear()} Salgados do Marquês. Todos os direitos reservados.
            </p>
            <div className="flex items-center gap-6">
              <Link to="/termos" className="text-background/50 hover:text-background transition-colors text-sm">
                Termos e Condições
              </Link>
              <Link to="/privacidade" className="text-background/50 hover:text-background transition-colors text-sm">
                Privacidade e LGPD
              </Link>
              <button
                type="button"
                onClick={openPreferences}
                className="text-background/50 hover:text-background transition-colors text-sm"
              >
                Preferências de Cookies
              </button>
            </div>
          </div>
        </div>
      </div>
    </footer>
  );
}
