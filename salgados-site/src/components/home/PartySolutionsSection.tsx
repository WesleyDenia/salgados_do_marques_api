import { Building2, CalendarHeart, CheckCircle, GlassWater, PartyPopper } from "lucide-react";

const solutions = [
  {
    icon: PartyPopper,
    title: "Aniversários",
    description: "Packs práticos para reunir família e amigos com mais sabor e menos preocupação.",
  },
  {
    icon: Building2,
    title: "Eventos Empresariais",
    description: "Opções práticas para coffee breaks, reuniões e momentos de equipa.",
  },
  {
    icon: CalendarHeart,
    title: "Casamentos & Celebrações",
    description: "Variedade de salgados e complementos para celebrar com mais praticidade.",
  },
  {
    icon: GlassWater,
    title: "Eventos Informais",
    description: "Uma forma simples de servir bem em convívios, encontros e eventos mais descontraídos.",
  },
];

const benefits = [
  "Atendimento rápido via WhatsApp",
  "Opções para eventos de diferentes dimensões",
  "Mini salgados, salgados 70g e complementos",
  "Apoio na escolha das quantidades",
  "Encomenda simples e sem complicação",
  "Variedade para servir diferentes convidados",
];

export function PartySolutionsSection() {
  return (
    <section className="section-padding bg-secondary/30">
      <div className="section-container">
        <div className="mx-auto mb-16 max-w-3xl text-center">
          <span className="highlight-badge mb-4 inline-block">Ocasiões de Uso</span>
          <h2 className="heading-section mb-6 text-foreground">
            Salgados para festa adaptados ao tipo de evento
          </h2>
          <p className="text-lg text-muted-foreground">
            Soluções pensadas para festas, reuniões e celebrações que pedem variedade,
            praticidade e uma encomenda simples.
          </p>
        </div>

        <div className="mb-16 grid gap-8 md:grid-cols-2 xl:grid-cols-4">
          {solutions.map((solution, index) => (
            <div
              key={solution.title}
              className="card-elevated p-8 text-center animate-fade-up"
              style={{ animationDelay: `${index * 0.1}s` }}
            >
              <div className="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-2xl bg-primary/10">
                <solution.icon className="h-8 w-8 text-primary" />
              </div>
              <h3 className="heading-card mb-3 text-foreground">{solution.title}</h3>
              <p className="text-muted-foreground">{solution.description}</p>
            </div>
          ))}
        </div>

        <div className="rounded-2xl border border-border bg-card p-8 md:p-12">
          <div className="grid items-center gap-8 md:grid-cols-2">
            <div>
              <h3 className="heading-card mb-4 text-foreground">
                Por que funciona bem para quem organiza?
              </h3>
              <p className="mb-6 text-muted-foreground">
                Mais praticidade para organizar a encomenda, escolher as quantidades
                e servir diferentes convidados com confiança.
              </p>
            </div>

            <div className="grid grid-cols-2 gap-4">
              {benefits.map((benefit) => (
                <div key={benefit} className="flex items-center gap-2">
                  <CheckCircle className="h-5 w-5 flex-shrink-0 text-primary" />
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