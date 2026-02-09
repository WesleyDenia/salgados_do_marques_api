import { MessageCircle, Phone } from "lucide-react";
import { Link } from "react-router-dom";
import { Button } from "@/components/ui/button";

export function CTASection() {
  return (
    <section className="section-padding bg-foreground text-background relative overflow-hidden">
      {/* Decorative elements */}
      <div className="absolute top-0 left-0 w-96 h-96 bg-primary/10 rounded-full blur-3xl -translate-x-1/2 -translate-y-1/2" />
      <div className="absolute bottom-0 right-0 w-96 h-96 bg-accent/10 rounded-full blur-3xl translate-x-1/2 translate-y-1/2" />

      <div className="section-container relative z-10">
        <div className="max-w-3xl mx-auto text-center">
          <h2 className="heading-section mb-6">
            Precisa de apoio agora?
          </h2>
          <p className="text-lg text-background/70 mb-10 max-w-2xl mx-auto">
            A nossa equipa está a apoiar a comunidade de Pombal com arca
            congeladora, cowork solidário e carregamento de eletrónicos.
            Atendimento mediante agendamento.
          </p>

          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <Button
              variant="whatsapp"
              size="xl"
              asChild
            >
              <a
                href="https://wa.me/351939197110"
                target="_blank"
                rel="noopener noreferrer"
                className="flex items-center gap-2"
              >
                  <MessageCircle className="w-5 h-5" />
                  Agendar Apoio via WhatsApp
                </a>
              </Button>
            <Button
              variant="outline"
              size="xl"
              className="border-background/30 text-background hover:bg-background hover:text-foreground"
              asChild
            >
              <Link to="/contactos" className="flex items-center gap-2">
                <Phone className="w-5 h-5" />
                Ver Mais Contactos
              </Link>
            </Button>
          </div>

          <p className="mt-8 text-sm text-background/50">
            Atendimento: segunda a sábado, das 10h às 20h
          </p>
        </div>
      </div>
    </section>
  );
}
