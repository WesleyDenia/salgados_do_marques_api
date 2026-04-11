import { ArrowRight, MessageCircle, Store } from "lucide-react";
import { Link } from "react-router-dom";
import { Button } from "@/components/ui/button";
import heroImage from "@/assets/image_home.png";

export function HeroSection() {
  return (
    <section className="relative overflow-hidden pt-10 pb-14 sm:pt-14 sm:pb-20">
      <div className="absolute inset-0 bg-[linear-gradient(135deg,hsl(var(--surface-strong))_0%,hsl(var(--accent))_42%,hsl(var(--primary))_100%)]" />
      <div className="absolute inset-0 bg-black/20" />
      <div className="absolute -top-10 left-8 h-40 w-40 rounded-full bg-white/10 blur-3xl" />
      <div className="absolute bottom-0 right-8 h-52 w-52 rounded-full bg-primary/20 blur-3xl" />

      <div className="section-container relative z-10">
        <div className="grid items-center gap-10 lg:grid-cols-[0.95fr_1.05fr]">
          <div className="space-y-8 animate-fade-up">
            <div className="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm font-medium text-white/90 backdrop-blur-sm">
              <Store className="h-4 w-4" />
              Packs de salgados para festas, eventos e celebrações
            </div>

            <div className="max-w-2xl space-y-5">
              <h1 className="heading-display text-balance text-white">
                Packs de salgados para festa feitos para abrir o apetite
              </h1>

              <p className="text-base leading-relaxed text-white/80 sm:text-lg">
                Mini salgados, salgados 70g, pão de queijo e mini churros para
                aniversários, reuniões, convívios e celebrações. Mais variedade
                para partilhar bem, com encomenda simples e atendimento rápido.
              </p>
            </div>

            <div className="flex flex-col gap-4 sm:flex-row">
              <Button variant="hero" size="lg" asChild>
                <a
                  href="https://wa.me/351939197110"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="flex items-center gap-2"
                >
                  <MessageCircle className="h-5 w-5" />
                  Falar no WhatsApp
                </a>
              </Button>

              <Button variant="hero-secondary" size="lg" asChild>
                <Link to="/produtos" className="flex items-center gap-2">
                  Ver produtos
                  <ArrowRight className="h-5 w-5" />
                </Link>
              </Button>
            </div>

            <div className="grid gap-3 sm:grid-cols-3">
              {[
                "Mais quantidade para partilhar",
                "Sabores pensados para diferentes ocasiões",
                "Encomenda rápida e prática",
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

          <div
            className="animate-fade-up lg:pl-4"
            style={{ animationDelay: "0.1s" }}
          >
            <div className="relative overflow-hidden rounded-[32px] border border-white/10 shadow-2xl">
              <img
                src={heroImage}
                alt="Seleção de salgados para festa"
                className="h-[420px] w-full object-cover md:h-[520px]"
              />

              <div className="absolute inset-0 bg-gradient-to-t from-black/50 via-black/10 to-transparent" />

              <div className="absolute bottom-0 left-0 right-0 p-6 md:p-8">
                <div className="inline-flex rounded-full border border-white/20 bg-black/25 px-4 py-2 text-sm font-medium text-white backdrop-blur-sm">
                  Sabor, variedade e partilha
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}