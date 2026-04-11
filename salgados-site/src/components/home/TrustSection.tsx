import { Clock3, MapPin, MessageCircleMore, ShieldCheck } from "lucide-react";

const trustPoints = [
  {
    icon: ShieldCheck,
    title: "Produção consistente",
    description: "Oferta organizada em torno de produtos já fortes na marca e fáceis de recomendar.",
  },
  {
    icon: MessageCircleMore,
    title: "Encomenda sem fricção",
    description: "Canal principal concentrado no WhatsApp para dúvidas, ajuste de quantidades e fecho comercial.",
  },
  {
    icon: MapPin,
    title: "Base local em Pombal",
    description: "Presença clara em Pombal, Leiria, com comunicação orientada a quem pesquisa salgados em Portugal.",
  },
  {
    icon: Clock3,
    title: "Processo simples",
    description: "Explicação direta do que pedir, para que ocasião serve e qual a próxima ação para avançar.",
  },
];

export function TrustSection() {
  return (
    <section className="section-padding bg-secondary/25">
      <div className="section-container">
        <div className="grid gap-10 lg:grid-cols-[0.9fr_1.1fr] lg:items-start">
          <div className="space-y-5">
            <span className="highlight-badge">Confiança Comercial</span>
            <h2 className="heading-section text-foreground">
              Sinais claros para ajudar o visitante a decidir
            </h2>
            <p className="text-lg text-muted-foreground">
              Em vez de números inventados, a secção reforça o que é verificável:
              foco comercial, resposta rápida, localização e um processo de encomenda direto.
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
