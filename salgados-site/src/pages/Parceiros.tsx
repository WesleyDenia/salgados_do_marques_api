import { ArrowRight, BriefcaseBusiness, CalendarRange, Handshake, MessageCircle, Store } from "lucide-react";
import { Seo } from "@/components/Seo";
import { Button } from "@/components/ui/button";
import { OG_IMAGES, SITE_NAME, SITE_URL } from "@/lib/site";

const partnerTypes = [
  "Casas de festas e espaços para eventos",
  "Organizadores de eventos e serviços de catering complementar",
  "Empresas com necessidades recorrentes para reuniões, ações e ativações",
  "Revenda selecionada com interesse em produtos prontos a recomendar",
];

const benefits = [
  "Oferta prática e ajustada a diferentes tipos de evento e operação",
  "Atendimento rápido para definir quantidades, mix de produtos e contexto de uso",
  "Processo simples para facilitar encomendas pontuais ou recorrentes",
  "Relação comercial pensada para continuidade, consistência e proximidade",
];

const workflow = [
  "Partilhe connosco o contexto do seu negócio, evento ou necessidade recorrente.",
  "Definimos em conjunto os produtos, quantidades e frequência mais adequados.",
  "Alinhamos os próximos passos pelo WhatsApp de forma rápida e direta.",
];

const Parceiros = () => {
  const partnerSchema = {
    "@context": "https://schema.org",
    "@type": "Service",
    name: "Parcerias comerciais para salgados e eventos",
    serviceType: "Parcerias para salgados, eventos e revenda",
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
                Parcerias
              </span>
              <h1 className="heading-display text-foreground">
                Soluções para eventos, negócios e revenda com atendimento direto
              </h1>
              <p className="text-lg leading-relaxed text-muted-foreground">
                Trabalhamos com parceiros que procuram uma oferta prática, consistente e fácil de integrar
                em eventos, operações recorrentes ou pontos de revenda. Uma forma simples de contar com
                produtos preparados para servir, recomendar e voltar a encomendar.
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
                    Falar sobre parceria
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
            <h2 className="heading-section mb-4 text-foreground">Para quem faz sentido</h2>
            <p className="text-lg text-muted-foreground">
              Esta solução é indicada para parceiros que valorizam rapidez no contacto,
              facilidade na encomenda e uma oferta adequada a diferentes ocasiões.
            </p>
          </div>
          <div className="grid gap-5 md:grid-cols-2">
            {partnerTypes.map((item, index) => (
              <article
                key={item}
                className="card-elevated flex gap-4 p-6 animate-fade-up"
                style={{ animationDelay: `${index * 0.08}s` }}
              >
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
              <h2 className="heading-card text-foreground">Vantagens da parceria</h2>
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
              <h2 className="heading-card text-foreground">Como funciona</h2>
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
          <h2 className="heading-section mb-5">Vamos conversar sobre a sua necessidade</h2>
          <p className="mx-auto mb-8 max-w-3xl text-lg text-background/75">
            Seja para eventos, encomendas recorrentes ou revenda, o contacto é simples e direto.
            Fale connosco pelo WhatsApp para apresentar o seu contexto e perceber a melhor forma
            de trabalharmos em conjunto.
          </p>
          <Button variant="whatsapp" size="xl" asChild>
            <a
              href="https://wa.me/351939197110"
              target="_blank"
              rel="noopener noreferrer"
              className="flex items-center gap-2"
            >
              <MessageCircle className="h-5 w-5" />
              Falar sobre parceria
            </a>
          </Button>
        </div>
      </section>
    </main>
  );
};

export default Parceiros;