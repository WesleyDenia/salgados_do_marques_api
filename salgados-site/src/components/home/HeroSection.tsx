import { ArrowRight, MessageCircle } from "lucide-react";
import { Button } from "@/components/ui/button";
import heroImage from "@/assets/hero-salgados.jpg";

export function HeroSection() {
  return (
    <section className="relative min-h-[85vh] sm:min-h-screen flex items-center pt-20 pb-10 sm:pb-0">
      {/* Background Image */}
      <div 
        className="absolute inset-0 bg-cover bg-center bg-no-repeat"
        style={{ backgroundImage: `url(${heroImage})` }}
      />
      
      {/* Dark overlay for text readability */}
      <div className="absolute inset-0 bg-gradient-to-r from-foreground/85 via-foreground/70 to-foreground/40" />
      
      {/* Decorative elements */}
      <div className="absolute top-40 right-10 w-64 h-64 bg-primary/10 rounded-full blur-3xl" />
      <div className="absolute bottom-20 left-10 w-96 h-96 bg-accent/10 rounded-full blur-3xl" />

      <div className="section-container relative z-10">
        <div className="max-w-2xl">
          {/* Content */}
          <div className="space-y-8 animate-fade-up">
            <div className="inline-flex items-center gap-2 px-4 py-2 bg-primary/20 text-primary-foreground font-medium rounded-full text-sm backdrop-blur-sm border border-primary/30">
              <span className="w-2 h-2 bg-primary rounded-full animate-pulse" />
              Campanha de apoio à comunidade
            </div>

            <h1 className="heading-display text-white text-balance">
              Apoio emergencial para{" "}
              <span className="text-primary">Pombal e região</span>
            </h1>

            <p className="text-base sm:text-lg text-white/80 leading-relaxed max-w-xl">
              Diante da tempestade severa, estamos a disponibilizar apoio com 
              arca congeladora, cowork solidário e carregamento de eletrónicos.
              Atendimento mediante agendamento, de segunda a sábado, 10h às 20h.
            </p>

            <div className="flex flex-col sm:flex-row gap-4">
              <Button variant="hero" size="lg" asChild>
                <a
                  href="https://wa.me/+351939197110"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="flex items-center gap-2"
                >
                  <MessageCircle className="w-5 h-5" />
                  Agendar Apoio
                </a>
              </Button>
              <Button variant="hero-secondary" size="lg" asChild>
                <a href="#apoio" className="flex items-center gap-2">
                  Ver Apoio Disponível
                  <ArrowRight className="w-5 h-5" />
                </a>
              </Button>
            </div>

            {/* Trust indicators */}
            <div className="flex flex-col sm:flex-row sm:items-center gap-6 sm:gap-8 pt-4">
              <div className="text-center">
                <p className="text-2xl font-display font-bold text-white">10h–20h</p>
                <p className="text-sm text-white/70">Seg a Sáb</p>
              </div>
              <div className="hidden sm:block w-px h-12 bg-white/20" />
              <div className="text-center">
                <p className="text-2xl font-display font-bold text-white">Agendamento</p>
                <p className="text-sm text-white/70">Obrigatório</p>
              </div>
              <div className="hidden sm:block w-px h-12 bg-white/20" />
              <div className="text-center">
                <p className="text-2xl font-display font-bold text-white">Disponível</p>
                <p className="text-sm text-white/70">Conforme capacidade</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
