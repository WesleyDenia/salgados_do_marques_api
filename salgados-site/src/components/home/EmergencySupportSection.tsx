import { Snowflake, Laptop, BatteryCharging, MessageCircle } from "lucide-react";
import { Button } from "@/components/ui/button";

const supportOptions = [
  {
    icon: Snowflake,
    title: "Arca Congeladora Solidária",
    description:
      "Guarde perecíveis para não perder alimentos. Sujeito a disponibilidade.",
  },
  {
    icon: Laptop,
    title: "Cowork Solidário",
    description:
      "Espaço da loja para trabalho e estudo quando precisar. Sujeito a disponibilidade.",
  },
  {
    icon: BatteryCharging,
    title: "Carregamento de Eletrónicos",
    description:
      "Carregue telemóveis e equipamentos essenciais para manter a comunicação.",
  },
];

export function EmergencySupportSection() {
  return (
    <section id="apoio" className="section-padding bg-secondary/30">
      <div className="section-container">
        <div className="text-center max-w-3xl mx-auto mb-16">
          <span className="highlight-badge mb-4 inline-block">
            Apoio Emergencial
          </span>
          <h2 className="heading-section text-foreground mb-6">
            Estamos juntos com a comunidade
          </h2>
          <p className="text-lg text-muted-foreground">
            Atendimento mediante agendamento. Horário de terça a sábado, das
            12h às 20h.
          </p>
        </div>

        <div className="grid md:grid-cols-3 gap-8 mb-12">
          {supportOptions.map((option, index) => (
            <div
              key={option.title}
              className="card-elevated p-8 text-center animate-fade-up"
              style={{ animationDelay: `${index * 0.1}s` }}
            >
              <div className="w-16 h-16 bg-primary/10 rounded-2xl flex items-center justify-center mx-auto mb-6">
                <option.icon className="w-8 h-8 text-primary" />
              </div>
              <h3 className="heading-card text-foreground mb-3">
                {option.title}
              </h3>
              <p className="text-muted-foreground">{option.description}</p>
            </div>
          ))}
        </div>

        <div className="bg-card rounded-2xl p-8 md:p-10 border border-border text-center">
          <p className="text-muted-foreground mb-6">
            Para garantir organização e segurança, o apoio é feito apenas com
            agendamento.
          </p>
          <Button variant="cta" size="lg" asChild>
            <a
              href="https://wa.me/351939197110"
              target="_blank"
              rel="noopener noreferrer"
              className="flex items-center justify-center gap-2"
            >
              <MessageCircle className="w-5 h-5" />
              Agendar Apoio via WhatsApp
            </a>
          </Button>
        </div>
      </div>
    </section>
  );
}
