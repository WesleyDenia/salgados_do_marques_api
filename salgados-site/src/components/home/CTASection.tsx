import { ArrowRight, MessageCircle } from "lucide-react";
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
            Pronto para fechar a próxima encomenda?
          </h2>
          <p className="text-lg text-background/70 mb-10 max-w-2xl mx-auto">
            A ação principal do site passa a ser sempre a mesma: falar connosco no WhatsApp
            para pedir quantidades, esclarecer disponibilidade e avançar com salgados para festa.
          </p>

          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <Button variant="whatsapp" size="xl" asChild>
              <a
                href="https://wa.me/351939197110"
                target="_blank"
                rel="noopener noreferrer"
                className="flex items-center gap-2"
              >
                <MessageCircle className="w-5 h-5" />
                Falar no WhatsApp
              </a>
            </Button>
            <Button
              variant="outline"
              size="xl"
              className="border-background/30 text-background hover:bg-background hover:text-foreground"
              asChild
            >
              <Link to="/parceiros" className="flex items-center gap-2">
                Seja um parceiro
                <ArrowRight className="w-5 h-5" />
              </Link>
            </Button>
          </div>

          <p className="mt-8 text-sm text-background/50">
            Atendimento comercial para clientes finais e parceiros, com resposta centralizada no mesmo canal.
          </p>
        </div>
      </div>
    </section>
  );
}
