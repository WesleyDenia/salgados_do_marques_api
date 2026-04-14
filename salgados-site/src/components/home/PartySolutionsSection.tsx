import {
  Building2,
  CalendarHeart,
  CheckCircle,
  GlassWater,
  PartyPopper,
} from "lucide-react";
import bgCardBusiness from "@/assets/bg_card_busines.png";
import bgCardCamping from "@/assets/bg_card_camping.png";
import bgCardCasamento from "@/assets/bg_card_casamento.png";
import bgCardParty from "@/assets/bg_card_party.png";

const solutions = [
  {
    icon: PartyPopper,
    title: "Aniversários",
    backgroundImage: bgCardParty,
    description:
      "Packs práticos para partilhar com família e amigos sem complicar a organização.",
  },
  {
    icon: Building2,
    title: "Eventos Empresariais",
    backgroundImage: bgCardBusiness,
    description:
      "Opções simples para coffee breaks, reuniões e momentos de equipa.",
  },
  {
    icon: CalendarHeart,
    title: "Casamentos & Celebrações",
    backgroundImage: bgCardCasamento,
    description:
      "Variedade para servir convidados com praticidade em momentos especiais.",
  },
  {
    icon: GlassWater,
    title: "Eventos Informais",
    backgroundImage: bgCardCamping,
    description:
      "Uma solução descontraída e fácil para convívios, encontros e pequenas celebrações.",
  },
];

const benefits = [
  "Atendimento rápido via WhatsApp",
  "Ajuda na escolha das quantidades",
  "Variedade para diferentes convidados",
  "Encomenda simples e prática",
];

export function PartySolutionsSection() {
  return (
    <section className="section-padding bg-secondary/20">
      <div className="section-container">
        <div className="mx-auto mb-12 max-w-3xl text-center">
          <span className="highlight-badge mb-4 inline-block">Ocasiões de Uso</span>
          <h2 className="heading-section mb-4 text-foreground">
            Salgados certos para cada ocasião
          </h2>
          <p className="text-base md:text-lg text-muted-foreground">
            De aniversários a reuniões de equipa, ajudamos a servir bem com mais
            praticidade, variedade e uma encomenda sem complicações.
          </p>
        </div>

        <div className="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
          {solutions.map((solution, index) => (
            <div
              key={solution.title}
              className="group overflow-hidden rounded-3xl border border-border/60 bg-card shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl animate-fade-up"
              style={{ animationDelay: `${index * 0.08}s` }}
            >
              <div
                className="relative h-32 bg-cover bg-center bg-no-repeat"
                style={{ backgroundImage: `url(${solution.backgroundImage})` }}
              >
                <div className="absolute inset-0 bg-gradient-to-br from-foreground/30 via-foreground/10 to-transparent" />
              </div>
              <div className="-mt-8 px-6 pb-6">
                <div className="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-primary text-primary-foreground shadow-sm ring-4 ring-card">
                  <solution.icon className="h-6 w-6" />
                </div>

                <h3 className="mb-3 text-left font-display text-2xl text-foreground">
                  {solution.title}
                </h3>

                <p className="text-left text-sm leading-6 text-muted-foreground">
                  {solution.description}
                </p>
              </div>
            </div>
          ))}
        </div>

        <div className="mt-10 rounded-3xl border border-border/60 bg-card/80 p-6 md:p-8">
          <div className="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
            <div className="max-w-xl">
              <h3 className="heading-card mb-2 text-foreground">
                Encomendar para o seu evento pode ser mais simples
              </h3>
              <p className="text-sm md:text-base text-muted-foreground">
                Atendimento rápido, apoio nas quantidades e variedade para servir
                diferentes convidados com confiança.
              </p>
            </div>

            <div className="grid gap-3 sm:grid-cols-2">
              {benefits.map((benefit) => (
                <div key={benefit} className="flex items-center gap-2">
                  <CheckCircle className="h-4 w-4 flex-shrink-0 text-primary" />
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
