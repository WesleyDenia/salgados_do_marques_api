import { useQuery } from "@tanstack/react-query";
import { ArrowRight, BriefcaseBusiness, MessageCircle } from "lucide-react";
import { Link } from "react-router-dom";
import { Seo } from "@/components/Seo";
import { Button } from "@/components/ui/button";
import { buildPartnerPath, fetchPartners, resolvePartnerImageUrl } from "@/lib/partners";
import { OG_IMAGES, SITE_NAME, SITE_URL } from "@/lib/site";

const Parceiros = () => {
  const { data: partners = [], isLoading, isError } = useQuery({
    queryKey: ["partners"],
    queryFn: fetchPartners,
  });

  const partnerSchema = {
    "@context": "https://schema.org",
    "@type": "CollectionPage",
    name: "Parceiros do Salgados do Marquês",
    url: `${SITE_URL}/parceiros`,
    isPartOf: {
      "@type": "WebSite",
      name: SITE_NAME,
      url: SITE_URL,
    },
  };

  return (
    <main>
      <Seo
        title={`${SITE_NAME} | Parceiros`}
        description="Conheça os parceiros do Salgados do Marquês e veja os detalhes de cada parceria ativa."
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
                Parceiros
              </span>
              <h1 className="heading-display text-foreground">Parcerias ativas no Salgados do Marquês</h1>
              <p className="text-lg leading-relaxed text-muted-foreground">
                Veja os parceiros já presentes no nosso ecossistema. Cada página reúne a imagem,
                o nome e a descrição da parceria para facilitar a consulta no site.
              </p>
              <div className="flex flex-col gap-3 sm:flex-row">
                <Button variant="outline" size="lg" asChild>
                  <a href="#lista-parceiros" className="flex items-center gap-2">
                    Ver parceiros
                    <ArrowRight className="h-4 w-4" />
                  </a>
                </Button>
                <Button variant="whatsapp" size="lg" asChild>
                  <a
                    href="https://wa.me/351939197110"
                    target="_blank"
                    rel="noopener noreferrer"
                    className="flex items-center gap-2"
                  >
                    <MessageCircle className="h-5 w-5" />
                    Falar no WhatsApp
                  </a>
                </Button>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section id="lista-parceiros" className="section-padding bg-secondary/25">
        <div className="section-container">
          <div className="mb-10 max-w-3xl">
            <h2 className="heading-section mb-4 text-foreground">Parceiros disponíveis</h2>
            <p className="text-lg text-muted-foreground">
              Selecione um parceiro para abrir a página interna e consultar a descrição completa.
            </p>
          </div>

          {isLoading ? (
            <div className="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
              {Array.from({ length: 6 }).map((_, index) => (
                <div
                  key={index}
                  className="card-elevated overflow-hidden bg-card/80 animate-pulse"
                >
                  <div className="h-56 bg-muted" />
                  <div className="space-y-3 p-6">
                    <div className="h-6 w-3/4 rounded bg-muted" />
                    <div className="h-4 w-full rounded bg-muted" />
                    <div className="h-4 w-2/3 rounded bg-muted" />
                  </div>
                </div>
              ))}
            </div>
          ) : null}

          {!isLoading && isError ? (
            <div className="card-elevated p-8 text-center">
              <p className="text-lg font-medium text-foreground">Não foi possível carregar os parceiros.</p>
              <p className="mt-3 text-muted-foreground">Tente novamente dentro de instantes.</p>
            </div>
          ) : null}

          {!isLoading && !isError && partners.length === 0 ? (
            <div className="card-elevated p-8 text-center">
              <p className="text-lg font-medium text-foreground">Nenhum parceiro disponível no momento.</p>
            </div>
          ) : null}

          {!isLoading && !isError && partners.length > 0 ? (
            <div className="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
              {partners.map((partner, index) => {
                const imageUrl = resolvePartnerImageUrl(partner.image_url);

                return (
                  <Link
                    key={partner.id}
                    to={buildPartnerPath(partner)}
                    className="group card-elevated overflow-hidden bg-card animate-fade-up"
                    style={{ animationDelay: `${index * 0.06}s` }}
                  >
                    {imageUrl ? (
                      <img
                        src={imageUrl}
                        alt={partner.name}
                        className="h-56 w-full object-cover transition-transform duration-500 group-hover:scale-105"
                        loading="lazy"
                      />
                    ) : (
                      <div className="flex h-56 items-center justify-center bg-muted px-6 text-center">
                        <span className="heading-card text-foreground">{partner.name}</span>
                      </div>
                    )}

                    <div className="space-y-3 p-6">
                      <h3 className="heading-card text-foreground">{partner.name}</h3>
                      <p
                        className="text-sm leading-6 text-muted-foreground"
                        style={{
                          display: "-webkit-box",
                          WebkitBoxOrient: "vertical",
                          WebkitLineClamp: 3,
                          overflow: "hidden",
                        }}
                      >
                        {partner.description}
                      </p>
                      <span className="inline-flex items-center gap-2 text-sm font-semibold text-primary">
                        Ver parceiro
                        <ArrowRight className="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" />
                      </span>
                    </div>
                  </Link>
                );
              })}
            </div>
          ) : null}
        </div>
      </section>
    </main>
  );
};

export default Parceiros;
