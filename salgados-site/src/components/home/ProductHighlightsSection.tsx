import { ArrowRight, MessageCircle } from "lucide-react";
import { Link } from "react-router-dom";
import { Button } from "@/components/ui/button";
import miniSalgadosImage from "@/assets/mini-salgados.jpg";
import salgados70gImage from "@/assets/salgados-70g.jpg";
import paoQueijoImage from "@/assets/pao-queijo.jpg";
import miniChurrosImage from "@/assets/mini-churros.jpg";

const highlights = [
  {
    title: "Mini salgados",
    description: "A escolha ideal para festas, aniversários e eventos com partilha prática.",
    image: miniSalgadosImage,
  },
  {
    title: "Salgados 70g",
    description: "Opções mais generosas para lanches, pausas de trabalho e momentos com mais apetite.",
    image: salgados70gImage,
  },
  {
    title: "Pão de queijo",
    description: "Um clássico irresistível, perfeito para complementar encomendas e servir quente.",
    image: paoQueijoImage,
  },
  {
    title: "Mini churros",
    description: "O toque doce ideal para completar festas, packs variados e momentos especiais.",
    image: miniChurrosImage,
  },
];

export function ProductHighlightsSection() {
  return (
    <section className="section-padding">
      <div className="section-container space-y-12">
        <div className="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
          <div className="max-w-3xl">
            <span className="highlight-badge mb-4 inline-flex">Produtos em destaque</span>
            <h2 className="heading-section mb-4 text-foreground">
              Sabores pensados para festas, encomendas e momentos de partilha
            </h2>
            <p className="text-lg text-muted-foreground">
              Descubra algumas das opções mais procuradas da nossa loja, preparadas para
              diferentes ocasiões, desde convívios em família até eventos e celebrações.
            </p>
          </div>

          <div className="flex flex-col gap-3 sm:flex-row">
            <Button variant="whatsapp" asChild>
              <a
                href="https://wa.me/351939197110"
                target="_blank"
                rel="noopener noreferrer"
                className="flex items-center gap-2"
              >
                <MessageCircle className="h-4 w-4" />
                Falar no WhatsApp
              </a>
            </Button>
            <Button variant="outline" asChild>
              <Link to="/produtos" className="flex items-center gap-2">
                Ver produtos
                <ArrowRight className="h-4 w-4" />
              </Link>
            </Button>
          </div>
        </div>

        <div className="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
          {highlights.map((item, index) => (
            <article
              key={item.title}
              className="card-elevated overflow-hidden animate-fade-up"
              style={{ animationDelay: `${index * 0.08}s` }}
            >
              <img src={item.image} alt={item.title} className="h-56 w-full object-cover" />
              <div className="space-y-3 p-6">
                <h3 className="heading-card text-foreground">{item.title}</h3>
                <p className="text-sm leading-relaxed text-muted-foreground">{item.description}</p>
              </div>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}