import { Header } from "@/components/Header";
import { Footer } from "@/components/Footer";
import { Button } from "@/components/ui/button";
import { MessageCircle, Package, Sparkles, Users, ArrowRight } from "lucide-react";
import festasImage from "@/assets/festas-catering.png";
import packsQuantidadeImage from "@/assets/mini-salgados.jpg";
import packsSaboresImage from "@/assets/hero-salgados.jpg";

const Festas = () => {
  return (
    <div className="min-h-screen bg-background">
      <Header />
      
      <main className="pt-20">
        {/* Hero */}
        <section className="section-padding bg-gradient-to-b from-secondary/50 to-background">
          <div className="section-container">
            <div className="grid lg:grid-cols-2 gap-12 items-center">
              <div className="space-y-6 animate-fade-up">
                <span className="highlight-badge">
                  <Package className="w-4 h-4" />
                  Festas & Encomendas
                </span>
                <h1 className="heading-display text-foreground">
                  A solução ideal para{" "}
                  <span className="gradient-text">festas e eventos</span>
                </h1>
                <p className="text-lg text-muted-foreground leading-relaxed">
                  Organização impecável, produção consistente e facilidade de 
                  encomenda. Concentre-se nos seus convidados enquanto nós 
                  tratamos da comida.
                </p>
                <Button variant="hero" size="lg" asChild>
                  <a
                    href="https://wa.me/351939197110"
                    target="_blank"
                    rel="noopener noreferrer"
                    className="flex items-center gap-2"
                  >
                    <MessageCircle className="w-5 h-5" />
                    Solicitar Orçamento
                  </a>
                </Button>
              </div>
              <div className="animate-fade-up" style={{ animationDelay: "0.1s" }}>
                <div className="rounded-2xl overflow-hidden shadow-xl">
                  <img
                    src={festasImage}
                    alt="Catering para festas"
                    className="w-full h-auto object-cover aspect-[4/3]"
                  />
                </div>
              </div>
            </div>
          </div>
        </section>

        {/* Packs */}
        <section className="section-padding">
          <div className="section-container">
            <div className="text-center max-w-2xl mx-auto mb-16">
              <h2 className="heading-section text-foreground mb-4">
                Packs para Festas
              </h2>
              <p className="text-muted-foreground">
                Escolha a quantidade ideal e os sabores disponíveis para a sua festa.
              </p>
            </div>

            <div className="grid md:grid-cols-2 gap-8">
              <div
                className="card-elevated p-8 relative animate-fade-up ring-2 ring-primary"
                style={{ animationDelay: "0s" }}
              >
                <div className="absolute -top-3 left-1/2 -translate-x-1/2">
                  <span className="bg-primary text-primary-foreground text-xs font-semibold px-3 py-1 rounded-full">
                    Quantidade
                  </span>
                </div>
                <div className="rounded-xl overflow-hidden mb-6">
                  <img
                    src={packsQuantidadeImage}
                    alt="Packs por quantidade"
                    className="w-full h-32 object-cover"
                  />
                </div>
                <ul className="space-y-3">
                  <li className="flex items-start gap-2 text-md">
                    <span className="text-primary mt-1">•</span>
                    <div className="flex-1 flex items-center justify-between gap-4">
                      <span className="text-foreground">Pack 25 unidades</span>
                      <span className="font-semibold text-primary">9€</span>
                    </div>
                  </li>
                  <li className="flex items-start gap-2 text-md">
                    <span className="text-primary mt-1">•</span>
                    <div className="flex-1 flex items-center justify-between gap-4">
                      <span className="text-foreground">Pack 50 unidades</span>
                      <span className="font-semibold text-primary">16.80€</span>
                    </div>
                  </li>
                  <li className="flex items-start gap-2 text-md">
                    <span className="text-primary mt-1">•</span>
                    <div className="flex-1 flex items-center justify-between gap-4">
                      <span className="text-foreground">Pack 75 unidades</span>
                      <span className="font-semibold text-primary">25.20€</span>
                    </div>
                  </li>
                  <li className="flex items-start gap-2 text-md">
                    <span className="text-primary mt-1">•</span>
                    <div className="flex-1 flex items-center justify-between gap-4">
                      <span className="text-foreground">Pack 100 unidades</span>
                      <span className="font-semibold text-primary">100€</span>
                    </div>
                  </li>
                </ul>
              </div>

              <div
                className="card-elevated p-8 relative animate-fade-up ring-2 ring-primary"
                style={{ animationDelay: "0.1s" }}
              >
                <div className="absolute -top-3 left-1/2 -translate-x-1/2">
                  <span className="bg-primary text-primary-foreground text-xs font-semibold px-3 py-1 rounded-full">
                    Sabores
                  </span>
                </div>
                <div className="rounded-xl overflow-hidden mb-6">
                  <img
                    src={packsSaboresImage}
                    alt="Sabores disponíveis"
                    className="w-full h-32 object-cover"
                  />
                </div>
                <ul className="grid grid-cols-2 gap-x-6 gap-y-3">
                  <li className="flex items-start gap-2 text-md">
                    <span className="text-primary mt-1">•</span>
                    <span className="text-foreground">Coxinha de Frango</span>
                  </li>
                  <li className="flex items-start gap-2 text-md">
                    <span className="text-primary mt-1">•</span>
                    <span className="text-foreground">Enroladinhos de Salsicha</span>
                  </li>
                  <li className="flex items-start gap-2 text-md">
                    <span className="text-primary mt-1">•</span>
                    <span className="text-foreground">Travesseirinho de Carne</span>
                  </li>
                  <li className="flex items-start gap-2 text-md">
                    <span className="text-primary mt-1">•</span>
                    <span className="text-foreground">Bolinhas de Queijo</span>
                  </li>
                  <li className="flex items-start gap-2 text-md">
                    <span className="text-primary mt-1">•</span>
                    <span className="text-foreground">Pack Mix*</span>
                  </li>
                  <li className="flex items-start gap-2 text-md">
                    <span className="text-primary mt-1">•</span>
                    <span className="text-foreground">Kibe</span>
                  </li>
                  <li className="flex items-start gap-2 text-md">
                    <span className="text-primary mt-1">•</span>
                    <span className="text-foreground">Pão de queijo tradicional**</span>
                  </li>
                  <li className="flex items-start gap-2 text-md">
                    <span className="text-primary mt-1">•</span>
                    <span className="text-foreground">Pão de queijo recheado**</span>
                  </li>
                  <li className="flex items-start gap-2 text-md">
                    <span className="text-primary mt-1">•</span>
                    <span className="text-foreground">Churros de doce de leite**</span>
                  </li>
                  <li className="flex items-start gap-2 text-md">
                    <span className="text-primary mt-1">•</span>
                    <span className="text-foreground">Churros de Creme de Avelã**</span>
                  </li>
                </ul>
              </div>
            </div>

            <p className="text-md text-muted-foreground mt-6 text-center">
              * O Pack Mix por todos os sabores exceto o kibe<br />
              ** Entre em contato para consultar valores.
            </p>
          </div>
        </section>

        {/* Extras */}
        <section className="section-padding bg-secondary/30">
          <div className="section-container">
            <div className="grid lg:grid-cols-2 gap-12 items-center">
              <div className="space-y-6">
                <span className="highlight-badge">
                  <Sparkles className="w-4 h-4" />
                  Extras Opcionais
                </span>
                <h2 className="heading-section text-foreground">
                  Complete a sua encomenda
                </h2>
                <p className="text-muted-foreground">
                  Adicione extras irresistíveis para tornar o seu evento 
                  ainda mais especial.
                </p>
                <div className="space-y-4">
                  <div className="bg-card p-4 rounded-lg border border-border">
                    <h4 className="font-semibold text-foreground">Mini Churros</h4>
                    <p className="text-sm text-muted-foreground">
                      A sobremesa perfeita que surpreende todos os convidados.
                    </p>
                  </div>
                  <div className="bg-card p-4 rounded-lg border border-border">
                    <h4 className="font-semibold text-foreground">Pão de Queijo</h4>
                    <p className="text-sm text-muted-foreground">
                      Quentinho e irresistível, complemento ideal.
                    </p>
                  </div>
                  <div className="bg-card p-4 rounded-lg border border-border">
                    <h4 className="font-semibold text-foreground">Salgados 70g</h4>
                    <p className="text-sm text-muted-foreground">
                      Versão maior para quem quer mais substância.
                    </p>
                  </div>
                </div>
              </div>
              <div className="bg-card p-8 rounded-2xl border border-border">
                <div className="flex items-center gap-4 mb-6">
                  <div className="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center">
                    <Users className="w-6 h-6 text-primary" />
                  </div>
                  <div>
                    <h3 className="font-semibold text-foreground">Encomenda Personalizada</h3>
                    <p className="text-sm text-muted-foreground">Montamos de acordo com as suas necessidades</p>
                  </div>
                </div>
                <p className="text-muted-foreground mb-6">
                  Não encontrou o pack ideal? Criamos combinações personalizadas 
                  com base no tipo de evento, número de convidados e preferências.
                </p>
                <Button variant="cta" className="w-full" asChild>
                  <a
                    href="https://wa.me/351939197110"
                    target="_blank"
                    rel="noopener noreferrer"
                    className="flex items-center justify-center gap-2"
                  >
                    Falar Connosco
                    <ArrowRight className="w-4 h-4" />
                  </a>
                </Button>
              </div>
            </div>
          </div>
        </section>
      </main>

      <Footer />
    </div>
  );
};

export default Festas;
