import { Clock3, MapPin, MessageCircleMore, ShieldCheck } from "lucide-react";

const trustPoints = [
  {
    icon: ShieldCheck,
    title: "Qualidade consistente",
    description: "Produção focada nos produtos mais procurados, com sabor e padrão que se repetem em cada encomenda.",
  },
  {
    icon: MessageCircleMore,
    title: "Atendimento direto",
    description: "Fale connosco pelo WhatsApp para esclarecer dúvidas, ajustar quantidades e finalizar o seu pedido com facilidade.",
  },
  {
    icon: MapPin,
    title: "Em Pombal, Leiria",
    description: "Produção local com atendimento próximo, ideal para quem procura soluções rápidas na região.",
  },
  {
    icon: Clock3,
    title: "Encomenda simples",
    description: "Escolha os produtos, indique a quantidade e combinamos tudo de forma rápida e sem complicações.",
  },
];

export function TrustSection() {
  return (
    <section className="section-padding bg-secondary/25">
      <div className="section-container">
        <div className="grid gap-10 lg:grid-cols-[0.9fr_1.1fr] lg:items-start">
          <div className="space-y-5">
            <span className="highlight-badge">Porquê escolher-nos</span>
            <h2 className="heading-section text-foreground">
              Uma forma simples e segura de encomendar para o seu evento
            </h2>
            <p className="text-lg text-muted-foreground">
              Trabalhamos com foco naquilo que realmente importa: qualidade,
              atendimento rápido e um processo de encomenda claro do início ao fim.
            </p>
          </div>

          <div className="grid gap-5 md:grid-cols-2">
            {trustPoints.map((point, index) => (
              <article
                key={point.title}
                className="card-elevated p-6 animate-fade-up"
                style={{ animationDelay: `${index * 0.08}s` }}
              >
                <div className="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10">
                  <point.icon className="h-6 w-6 text-primary" />
                </div>
                <h3 className="mb-2 text-xl font-semibold text-foreground">{point.title}</h3>
                <p className="text-sm leading-relaxed text-muted-foreground">{point.description}</p>
              </article>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}