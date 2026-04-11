import { ArrowRight, MessageCircle, Store, Users } from "lucide-react";
import { Link } from "react-router-dom";
import { Button } from "@/components/ui/button";
import heroImage from "@/assets/hero-salgados.jpg";

export function HeroSection() {
  return (
    <section className="relative overflow-hidden pt-10 pb-14 sm:pt-14 sm:pb-20">
      <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_left,hsl(var(--brand-soft)/0.55),transparent_34%),linear-gradient(135deg,hsl(var(--surface-strong))_0%,hsl(var(--accent))_48%,hsl(var(--primary))_100%)]" />
      <div
        className="absolute inset-y-0 right-0 hidden w-1/2 bg-cover bg-center opacity-30 lg:block"
        style={{ backgroundImage: `url(${heroImage})` }}
      />
      <div className="absolute -top-10 left-8 h-40 w-40 rounded-full bg-white/10 blur-3xl" />
      <div className="absolute bottom-0 right-8 h-52 w-52 rounded-full bg-primary/25 blur-3xl" />

      <div className="section-container relative z-10">
        <div className="grid items-center gap-10 lg:grid-cols-[1.15fr_0.85fr]">
          <div className="space-y-8 animate-fade-up">
            <div className="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm font-medium text-white/90 backdrop-blur-sm">
              <Store className="h-4 w-4" />
              Salgados para festa, eventos e encomendas em Portugal
            </div>

            <h1 className="heading-display text-balance text-white">
              Salgados para festa com sabor brasileiro, produção local e contacto rápido por{" "}
              <span className="text-[#ffd7d7]">WhatsApp</span>
            </h1>

            <p className="max-w-2xl text-base leading-relaxed text-white/80 sm:text-lg">
              Mini salgados, salgados 70g, pão de queijo e mini churros para aniversários,
              casamentos, eventos empresariais e celebrações em Pombal, Leiria e região.
              Fale connosco, ajuste quantidades e avance com a sua encomenda sem complicação.
            </p>

            <div className="flex flex-col gap-4 sm:flex-row">
              <Button variant="hero" size="lg" asChild>
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
              <Button variant="hero-secondary" size="lg" asChild>
                <Link to="/parceiros" className="flex items-center gap-2">
                  Seja um parceiro
                  <ArrowRight className="w-5 h-5" />
                </Link>
              </Button>
            </div>

            <div className="grid gap-3 sm:grid-cols-3">
              {[
                "Resposta rápida para encomendas e dúvidas",
                "Opções para festas pequenas ou eventos maiores",
                "Frente B2C e B2B com atendimento no mesmo canal",
              ].map((item) => (
                <div
                  key={item}
                  className="rounded-2xl border border-white/15 bg-white/8 px-4 py-4 text-sm text-white/80 backdrop-blur-sm"
                >
                  {item}
                </div>
              ))}
            </div>
          </div>

          <div className="animate-fade-up" style={{ animationDelay: "0.1s" }}>
            <div className="brand-panel overflow-hidden border-white/10 bg-white/95">
              <img
                src={heroImage}
                alt="Seleção de salgados para festa"
                className="h-64 w-full object-cover md:h-72"
              />
              <div className="space-y-5 p-6 md:p-8">
                <div className="flex items-center gap-3 text-sm font-medium text-primary">
                  <Users className="h-4 w-4" />
                  Atendimento comercial focado em festas e eventos
                </div>
                <div className="grid gap-4 sm:grid-cols-2">
                  <div className="rounded-2xl bg-secondary/70 p-4">
                    <p className="text-sm text-muted-foreground">Produtos em destaque</p>
                    <p className="mt-2 font-display text-2xl text-foreground">Mini salgados e 70g</p>
                  </div>
                  <div className="rounded-2xl bg-secondary/70 p-4">
                    <p className="text-sm text-muted-foreground">Complementos</p>
                    <p className="mt-2 font-display text-2xl text-foreground">Pão de queijo e churros</p>
                  </div>
                </div>
                <p className="text-sm leading-relaxed text-muted-foreground">
                  Use a home para descobrir a oferta, validar confiança e seguir para o WhatsApp ou para a página de parceiros.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
