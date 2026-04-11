import { ArrowRight, MessageCircle, Store } from "lucide-react";
import { Link } from "react-router-dom";
import { Button } from "@/components/ui/button";
import heroImage from "@/assets/image_home.png";

export function HeroSection() {
  return (
    <section className="relative flex min-h-[78vh] items-center overflow-hidden">
      <div
        className="absolute inset-0 bg-cover bg-center"
        style={{
          backgroundImage: `url(${heroImage})`,
          backgroundPosition: "center center",
        }}
      />

      <div className="absolute inset-0 bg-black/26" />
      <div className="absolute inset-0 bg-[linear-gradient(90deg,rgba(0,0,0,0.58)_0%,rgba(0,0,0,0.38)_34%,rgba(0,0,0,0.14)_64%,rgba(0,0,0,0.04)_100%)]" />
      <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(255,255,255,0.08),transparent_30%)]" />

      <div className="section-container relative z-10 w-full py-16 sm:py-24">
        <div className="max-w-2xl space-y-8 animate-fade-up">
          <div className="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm font-medium text-white/90 backdrop-blur-sm">
            <Store className="h-4 w-4" />
            Packs de salgados para festas, eventos e celebrações
          </div>

          <div className="space-y-5">
            <h1 className="heading-display text-balance text-white">
              Packs de salgados para festa feitos para abrir o apetite
            </h1>

            <p className="max-w-xl text-base leading-relaxed text-white/85 sm:text-lg">
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
                className="rounded-2xl border border-white/15 bg-white/10 px-4 py-4 text-sm text-white/85 backdrop-blur-sm"
              >
                {item}
              </div>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}