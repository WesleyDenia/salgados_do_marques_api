import { Seo } from "@/components/Seo";
import { OG_IMAGES, SITE_URL } from "@/lib/site";

const Termos = () => {
  return (
    <main>
      <Seo
        title="Termos e Condições | Salgados do Marquês"
        description="Termos e condições de utilização do site e encomendas da Salgados do Marquês."
        canonical={`${SITE_URL}/termos`}
        ogImage={OG_IMAGES.termos}
        schema={[
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
                name: "Termos e Condições",
                item: `${SITE_URL}/termos`,
              },
            ],
          },
        ]}
      />
      <section className="section-padding bg-gradient-to-b from-secondary/50 to-background">
          <div className="section-container text-center">
            <span className="highlight-badge mb-4 inline-block">
              Termos e Condições
            </span>
            <h1 className="heading-display text-foreground mb-6">
              Termos e Condições
            </h1>
            <p className="text-lg text-muted-foreground max-w-2xl mx-auto">
              Leia atentamente estes termos antes de utilizar o nosso site e
              solicitar encomendas.
            </p>
          </div>
        </section>

        <section className="section-padding">
          <div className="section-container max-w-3xl space-y-10 text-muted-foreground">
            <div className="space-y-3">
              <h2 className="heading-card text-foreground">1. Aceitacao</h2>
              <p>
                Ao aceder ao site Salgados do Marquês, o utilizador concorda com
                os presentes termos. Caso não concorde, não deve utilizar o
                site.
              </p>
            </div>

            <div className="space-y-3">
              <h2 className="heading-card text-foreground">2. Informacoes</h2>
              <p>
                As informações apresentadas no site podem ser alteradas sem
                aviso prévio. As imagens são ilustrativas.
              </p>
            </div>

            <div className="space-y-3">
              <h2 className="heading-card text-foreground">3. Encomendas</h2>
              <p>
                Os pedidos efetuados através dos nossos canais estão sujeitos a
                confirmação. Prazos e valores podem variar conforme a
                disponibilidade e o tipo de evento.
              </p>
            </div>

            <div className="space-y-3">
              <h2 className="heading-card text-foreground">
                4. Responsabilidade
              </h2>
              <p>
                O Salgados do Marquês não se responsabiliza por falhas de
                ligação, indisponibilidade temporária do site ou danos
                decorrentes do uso do mesmo.
              </p>
            </div>

            <div className="space-y-3">
              <h2 className="heading-card text-foreground">
                5. Contacto
              </h2>
              <p>
                Para esclarecimentos, contacte-nos via e-mail ou WhatsApp.
              </p>
            </div>
          </div>
        </section>
    </main>
  );
};

export default Termos;
