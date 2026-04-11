import { Building2, CalendarHeart, CheckCircle, GlassWater, PartyPopper } from "lucide-react";

const solutions = [
  {
    icon: PartyPopper,
    title: "Aniversários",
    description: "Packs flexíveis para reunir família e amigos sem perder tempo na cozinha.",
  },
  {
    icon: Building2,
    title: "Eventos Empresariais",
    description: "Coffee breaks, reuniões e ativações com serviço simples de encomendar.",
  },
  {
    icon: CalendarHeart,
    title: "Casamentos & Celebrações",
    description: "Apoio para momentos especiais com variedade de salgados e complementos.",
  },
  {
    icon: GlassWater,
    title: "Eventos Informais",
    description: "Soluções práticas para convívios, open days e encontros com equipas ou clientes.",
  },
];

const benefits = [
  "Atendimento rápido via WhatsApp",
  "Opções para diferentes dimensões de evento",
  "Portefólio com salgados, pão de queijo e doces",
  "Produção local com foco em consistência",
  "Apoio para escolher quantidades",
  "Ponto de contacto único para encomenda",
];

export function PartySolutionsSection() {
  return (
    <section className="section-padding bg-secondary/30">
      <div className="section-container">
        <div className="text-center max-w-3xl mx-auto mb-16">
          <span className="highlight-badge mb-4 inline-block">Ocasiões de Uso</span>
          <h2 className="heading-section text-foreground mb-6">
            Salgados para festa adaptados ao tipo de evento
          </h2>
          <p className="text-lg text-muted-foreground">
            A homepage passa a explicar onde a marca se encaixa: eventos familiares,
            empresariais e celebrações que pedem variedade, praticidade e um contacto
            comercial simples.
          </p>
        </div>

        <div className="grid gap-8 md:grid-cols-2 xl:grid-cols-4 mb-16">
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

        <div className="bg-card rounded-2xl p-8 md:p-12 border border-border">
          <div className="grid md:grid-cols-2 gap-8 items-center">
            <div>
              <h3 className="heading-card text-foreground mb-4">
                Por que funciona bem para quem organiza?
              </h3>
              <p className="text-muted-foreground mb-6">
                A promessa aqui é comercial: facilidade para avançar, variedade para
                servir bem e clareza suficiente para decidir sem fricção.
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
