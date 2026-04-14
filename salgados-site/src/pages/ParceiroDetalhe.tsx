import { useQuery } from "@tanstack/react-query";
import { ArrowLeft, MessageCircle } from "lucide-react";
import { Link, Navigate, useParams } from "react-router-dom";
import { Seo } from "@/components/Seo";
import { Button } from "@/components/ui/button";
import { extractPartnerId, fetchPartnerById, resolvePartnerImageUrl } from "@/lib/partners";
import { OG_IMAGES, SITE_NAME, SITE_URL } from "@/lib/site";

const ParceiroDetalhe = () => {
  const { partnerId } = useParams<{ partnerId: string }>();
  const parsedId = extractPartnerId(partnerId);

  const { data: partner, isLoading, isError } = useQuery({
    queryKey: ["partner", parsedId],
    queryFn: () => fetchPartnerById(parsedId as number),
    enabled: parsedId !== null,
  });

  if (parsedId === null) {
    return <Navigate to="/parceiros" replace />;
  }

  const imageUrl = resolvePartnerImageUrl(partner?.image_url);
  const canonicalPath = partner ? `/parceiros/${partner.id}-${partner.slug}` : `/parceiros/${partnerId}`;

  return (
    <main>
      <Seo
        title={partner ? `${SITE_NAME} | ${partner.name}` : `${SITE_NAME} | Parceiro`}
        description={
          partner?.description
            ? partner.description.slice(0, 160)
            : "Conheça os detalhes desta parceria ativa do Salgados do Marquês."
        }
        canonical={`${SITE_URL}${canonicalPath}`}
        ogImage={imageUrl ?? OG_IMAGES.parceiros}
        schema={
          partner
            ? [
                {
                  "@context": "https://schema.org",
                  "@type": "Organization",
                  name: partner.name,
                  description: partner.description,
                  image: imageUrl,
                  url: `${SITE_URL}${canonicalPath}`,
                },
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
                    {
                      "@type": "ListItem",
                      position: 3,
                      name: partner.name,
                      item: `${SITE_URL}${canonicalPath}`,
                    },
                  ],
                },
              ]
            : undefined
        }
      />

      <section className="section-padding">
        <div className="section-container">
          <div className="mb-8">
            <Button variant="outline" asChild>
              <Link to="/parceiros" className="inline-flex items-center gap-2">
                <ArrowLeft className="h-4 w-4" />
                Voltar para parceiros
              </Link>
            </Button>
          </div>

          {isLoading ? (
            <div className="card-elevated overflow-hidden animate-pulse">
              <div className="h-[320px] bg-muted md:h-[420px]" />
              <div className="space-y-4 p-8">
                <div className="h-10 w-2/3 rounded bg-muted" />
                <div className="h-5 w-full rounded bg-muted" />
                <div className="h-5 w-full rounded bg-muted" />
                <div className="h-5 w-5/6 rounded bg-muted" />
              </div>
            </div>
          ) : null}

          {!isLoading && (isError || !partner) ? (
            <div className="card-elevated p-10 text-center">
              <h1 className="heading-card text-foreground">Parceiro não encontrado</h1>
              <p className="mt-4 text-muted-foreground">
                Não foi possível localizar os dados deste parceiro.
              </p>
            </div>
          ) : null}

          {!isLoading && partner ? (
            <article className="card-elevated overflow-hidden">
              {imageUrl ? (
                <img
                  src={imageUrl}
                  alt={partner.name}
                  className="h-[320px] w-full object-cover md:h-[440px]"
                />
              ) : (
                <div className="flex h-[320px] items-center justify-center bg-muted px-8 text-center md:h-[440px]">
                  <span className="heading-section text-foreground">{partner.name}</span>
                </div>
              )}

              <div className="grid gap-8 p-8 md:grid-cols-[minmax(0,1fr)_280px] md:p-10">
                <div>
                  <h1 className="heading-section mb-6 text-foreground">{partner.name}</h1>
                  <div className="max-w-none text-base leading-8 text-muted-foreground">
                    <p>{partner.description}</p>
                  </div>
                </div>

                <aside className="rounded-3xl bg-secondary/60 p-6">
                  <h2 className="mb-3 text-xl font-semibold text-foreground">Falar sobre parceria</h2>
                  <p className="mb-6 text-sm leading-6 text-muted-foreground">
                    Quer saber mais ou discutir uma nova parceria? O contacto é direto pelo WhatsApp.
                  </p>
                  <Button variant="whatsapp" className="w-full" asChild>
                    <a
                      href="https://wa.me/351939197110"
                      target="_blank"
                      rel="noopener noreferrer"
                      className="flex items-center justify-center gap-2"
                    >
                      <MessageCircle className="h-5 w-5" />
                      Falar no WhatsApp
                    </a>
                  </Button>
                </aside>
              </div>
            </article>
          ) : null}
        </div>
      </section>
    </main>
  );
};

export default ParceiroDetalhe;
