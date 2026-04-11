import { ArrowRight, BriefcaseBusiness, CalendarRange, Handshake, MessageCircle, Store } from "lucide-react";
import { Seo } from "@/components/Seo";
import { Button } from "@/components/ui/button";
import { OG_IMAGES, SITE_NAME, SITE_URL } from "@/lib/site";

const partnerTypes = [
  "Casas de festas e espaços para eventos",
  "Organizadores de eventos e catering complementar",
  "Empresas com necessidade recorrente para reuniões e ativações",
  "Revenda selecionada com procura por salgados prontos a recomendar",
];

const benefits = [
  "Oferta alinhada com produtos já fortes na marca",
  "Canal rápido para ajustar volumes e contexto de uso",
  "Comunicação simples para acelerar a decisão comercial",
  "Possibilidade de relação recorrente para eventos e encomendas",
];

const workflow = [
  "Partilha do contexto do parceiro e do tipo de evento ou operação.",
  "Definição do mix de produtos, quantidades e frequência pretendida.",
  "Validação comercial via WhatsApp e alinhamento dos próximos passos.",
];

const Parceiros = () => {
  const partnerSchema = {
    "@context": "https://schema.org",
    "@type": "Service",
    name: "Parcerias comerciais para salgados e eventos",
    serviceType: "Parcerias B2B para salgados, eventos e revenda",
    areaServed: "Portugal",
    provider: {
      "@type": "Organization",
      name: SITE_NAME,
      url: SITE_URL,
    },
  };

  return (
    <main>
      <Seo
        title={`${SITE_NAME} | Parceiros para Eventos e Revenda`}
        description="Página para parceiros do Salgados do Marquês: casas de festas, eventos empresariais e revenda. Conheça benefícios, forma de trabalho e fale connosco no WhatsApp."
        canonical={`${SITE_URL}/parceiros`}
        ogImage={OG_IMAGES.parceiros}
        schema={[
          partnerSchema,
          {
            "@context": "https://schema.org",
            "@type": "BreadcrumbList",
            itemListElement: [
              {
                "@type": "ListItem",
                position: 1,
                name: "Início",
                item: `${SITE_URL}/`,
              },
              {
                "@type": "ListItem",
                position: 2,
                name: "Parceiros",
                item: `${SITE_URL}/parceiros`,
              },
            ],
          },
        ]}
      />

      <section className="section-padding">
        <div className="section-container">
          <div className="brand-panel overflow-hidden bg-gradient-to-br from-card via-card to-secondary/60 p-8 md:p-12">
            <div className="max-w-3xl space-y-6">
              <span className="highlight-badge">
                <BriefcaseBusiness className="h-4 w-4" />
                Parcerias comerciais
              </span>
              <h1 className="heading-display text-foreground">
                Uma página dedicada para quem quer trabalhar connosco em eventos, festas e revenda
              </h1>
              <p className="text-lg leading-relaxed text-muted-foreground">
                Se gere uma casa de festas, organiza eventos ou precisa de um parceiro de confiança
                para encomendas recorrentes, esta frente B2B foi desenhada para explicar a proposta
                com clareza e encaminhar o contacto comercial certo.
              </p>
              <div className="flex flex-col gap-3 sm:flex-row">
                <Button variant="whatsapp" size="lg" asChild>
                  <a
                    href="https://wa.me/351939197110"
                    target="_blank"
                    rel="noopener noreferrer"
                    className="flex items-center gap-2"
                  >
                    <MessageCircle className="h-5 w-5" />
                    Seja um parceiro
                  </a>
                </Button>
                <Button variant="outline" size="lg" asChild>
                  <a href="#como-funciona" className="flex items-center gap-2">
                    Como funciona
                    <ArrowRight className="h-4 w-4" />
                  </a>
                </Button>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section className="section-padding bg-secondary/25">
        <div className="section-container">
          <div className="mb-10 max-w-3xl">
            <h2 className="heading-section mb-4 text-foreground">Tipos de parceiros atendidos</h2>
            <p className="text-lg text-muted-foreground">
              A página existe para mostrar que a marca consegue responder a relações comerciais
              regulares sem tirar foco do cliente final.
            </p>
          </div>
          <div className="grid gap-5 md:grid-cols-2">
            {partnerTypes.map((item, index) => (
              <article key={item} className="card-elevated flex gap-4 p-6 animate-fade-up" style={{ animationDelay: `${index * 0.08}s` }}>
                <div className="mt-1 flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10">
                  <Store className="h-6 w-6 text-primary" />
                </div>
                <p className="text-base leading-relaxed text-foreground">{item}</p>
              </article>
            ))}
          </div>
        </div>
      </section>

      <section className="section-padding">
        <div className="section-container grid gap-8 lg:grid-cols-2">
          <div className="card-elevated p-8">
            <div className="mb-5 flex items-center gap-3">
              <Handshake className="h-6 w-6 text-primary" />
              <h2 className="heading-card text-foreground">Benefícios da parceria</h2>
            </div>
            <ul className="space-y-4">
              {benefits.map((item) => (
                <li key={item} className="flex gap-3 text-muted-foreground">
                  <span className="mt-2 h-2 w-2 rounded-full bg-primary" />
                  <span>{item}</span>
                </li>
              ))}
            </ul>
          </div>

          <div id="como-funciona" className="card-elevated p-8">
            <div className="mb-5 flex items-center gap-3">
              <CalendarRange className="h-6 w-6 text-primary" />
              <h2 className="heading-card text-foreground">Modo de trabalho</h2>
            </div>
            <ol className="space-y-4">
              {workflow.map((item, index) => (
                <li key={item} className="flex gap-4 text-muted-foreground">
                  <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-semibold text-primary-foreground">
                    {index + 1}
                  </span>
                  <span>{item}</span>
                </li>
              ))}
            </ol>
          </div>
        </div>
      </section>

      <section className="section-padding bg-foreground text-background">
        <div className="section-container max-w-4xl text-center">
          <h2 className="heading-section mb-5">Confiança institucional e contacto direto</h2>
          <p className="mx-auto mb-8 max-w-3xl text-lg text-background/75">
            O objetivo desta página não é complicar o processo com formulários. É criar uma ponte
            clara entre a marca e parceiros com potencial recorrente, usando o mesmo canal principal
            de comunicação do resto do site.
          </p>
          <Button variant="whatsapp" size="xl" asChild>
            <a
              href="https://wa.me/351939197110"
              target="_blank"
              rel="noopener noreferrer"
              className="flex items-center gap-2"
            >
              <MessageCircle className="h-5 w-5" />
              Seja um parceiro
            </a>
          </Button>
        </div>
      </section>
    </main>
  );
};

export default Parceiros;
