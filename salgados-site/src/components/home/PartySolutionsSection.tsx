import { PartyPopper, Users, Clock, CheckCircle } from "lucide-react";

const solutions = [
  {
    icon: PartyPopper,
    title: "Aniversários",
    description: "Salgados e doces para celebrar momentos especiais em família.",
  },
  {
    icon: Users,
    title: "Eventos Empresariais",
    description: "Reuniões, workshops e confraternizações com qualidade garantida.",
  },
  {
    icon: Clock,
    title: "Celebrações",
    description: "Batizados, comunhões e outras ocasiões importantes.",
  },
];

const benefits = [
  "Praticidade na organização",
  "Variedade de opções",
  "Quantidades para todos os grupos",
  "Produção consistente e de qualidade",
  "Entrega pontual",
  "Suporte dedicado",
];

export function PartySolutionsSection() {
  return (
    <section className="section-padding bg-secondary/30">
      <div className="section-container">
        {/* Header */}
        <div className="text-center max-w-3xl mx-auto mb-16">
          <span className="highlight-badge mb-4 inline-block">
            Soluções Completas
          </span>
          <h2 className="heading-section text-foreground mb-6">
            Tranquilidade para quem organiza
          </h2>
          <p className="text-lg text-muted-foreground">
            Deixe a comida por nossa conta. Focamo-nos em entregar qualidade e 
            consistência para que possa dedicar-se ao que realmente importa: 
            aproveitar o momento.
          </p>
        </div>

        {/* Solutions Grid */}
        <div className="grid md:grid-cols-3 gap-8 mb-16">
          {solutions.map((solution, index) => (
            <div
              key={solution.title}
              className="card-elevated p-8 text-center animate-fade-up"
              style={{ animationDelay: `${index * 0.1}s` }}
            >
              <div className="w-16 h-16 bg-primary/10 rounded-2xl flex items-center justify-center mx-auto mb-6">
                <solution.icon className="w-8 h-8 text-primary" />
              </div>
              <h3 className="heading-card text-foreground mb-3">{solution.title}</h3>
              <p className="text-muted-foreground">{solution.description}</p>
            </div>
          ))}
        </div>

        {/* Benefits */}
        <div className="bg-card rounded-2xl p-8 md:p-12 border border-border">
          <div className="grid md:grid-cols-2 gap-8 items-center">
            <div>
              <h3 className="heading-card text-foreground mb-4">
                Por que escolher Salgados do Marquês?
              </h3>
              <p className="text-muted-foreground mb-6">
                Organizamos cada encomenda com cuidado, garantindo que todos os 
                detalhes estejam alinhados com as suas necessidades.
              </p>
            </div>
            <div className="grid grid-cols-2 gap-4">
              {benefits.map((benefit) => (
                <div key={benefit} className="flex items-center gap-2">
                  <CheckCircle className="w-5 h-5 text-primary flex-shrink-0" />
                  <span className="text-sm text-foreground">{benefit}</span>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
