import { Sparkles, ArrowRight } from "lucide-react";
import { Link } from "react-router-dom";
import { Button } from "@/components/ui/button";
import churrosImage from "@/assets/mini-churros.jpg";

export function ChurrosHighlight() {
  return (
    <section className="section-padding overflow-hidden">
      <div className="section-container">
        <div className="grid lg:grid-cols-2 gap-12 lg:gap-20 items-center">
          {/* Image */}
          <div className="relative order-2 lg:order-1 animate-fade-up">
            <div className="relative rounded-2xl overflow-hidden shadow-xl">
              <img
                src={churrosImage}
                alt="Mini Churros deliciosos"
                className="w-full h-auto object-cover aspect-square"
              />
              <div className="absolute inset-0 bg-gradient-to-t from-foreground/30 to-transparent" />
            </div>
            
            {/* Decorative badge */}
            <div className="absolute -top-4 -right-4 bg-accent text-accent-foreground rounded-full p-4 shadow-lg animate-float">
              <Sparkles className="w-6 h-6" />
            </div>
          </div>

          {/* Content */}
          <div className="order-1 lg:order-2 space-y-6 animate-fade-up" style={{ animationDelay: "0.1s" }}>
            <span className="highlight-badge">
              <Sparkles className="w-4 h-4" />
              Destaque Doce
            </span>

            <h2 className="heading-section text-foreground">
              O toque doce que{" "}
              <span className="gradient-text">surpreende os convidados</span>
            </h2>

            <p className="text-lg text-muted-foreground leading-relaxed">
              Os nossos mini churros são a sobremesa perfeita para completar 
              a sua encomenda. Crocantes por fora, macios por dentro, e 
              cobertos com açúcar e canela.
            </p>

            <div className="space-y-4">
              <div className="flex items-start gap-3">
                <div className="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center flex-shrink-0">
                  <span className="text-sm">✓</span>
                </div>
                <div>
                  <p className="font-medium text-foreground">Perfeitos para fechar a encomenda</p>
                  <p className="text-sm text-muted-foreground">
                    Adicione como extra às suas festas
                  </p>
                </div>
              </div>
              <div className="flex items-start gap-3">
                <div className="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center flex-shrink-0">
                  <span className="text-sm">✓</span>
                </div>
                <div>
                  <p className="font-medium text-foreground">Práticos de servir</p>
                  <p className="text-sm text-muted-foreground">
                    Formato mini ideal para eventos
                  </p>
                </div>
              </div>
              <div className="flex items-start gap-3">
                <div className="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center flex-shrink-0">
                  <span className="text-sm">✓</span>
                </div>
                <div>
                  <p className="font-medium text-foreground">Sucesso garantido</p>
                  <p className="text-sm text-muted-foreground">
                    Agradam adultos e crianças
                  </p>
                </div>
              </div>
            </div>

            <Button variant="cta" size="lg" asChild>
              <Link to="/produtos" className="flex items-center gap-2">
                Ver Todos os Produtos
                <ArrowRight className="w-5 h-5" />
              </Link>
            </Button>
          </div>
        </div>
      </div>
    </section>
  );
}
