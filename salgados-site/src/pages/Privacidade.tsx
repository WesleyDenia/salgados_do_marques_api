import { Seo } from "@/components/Seo";
import { OG_IMAGES, SITE_URL } from "@/lib/site";

const Privacidade = () => {
  return (
    <main>
      <Seo
        title="Politica de Privacidade | Salgados do Marques"
        description="Saiba como a Salgados do Marques trata os seus dados pessoais e privacidade."
        canonical={`${SITE_URL}/privacidade`}
        ogImage={OG_IMAGES.privacidade}
        schema={[
          {
            "@context": "https://schema.org",
            "@type": "BreadcrumbList",
            itemListElement: [
              {
                "@type": "ListItem",
                position: 1,
                name: "Inicio",
                item: `${SITE_URL}/`,
              },
              {
                "@type": "ListItem",
                position: 2,
                name: "Politica de Privacidade",
                item: `${SITE_URL}/privacidade`,
              },
            ],
          },
        ]}
      />
      <section className="section-padding bg-gradient-to-b from-secondary/50 to-background">
          <div className="section-container text-center">
            <span className="highlight-badge mb-4 inline-block">
              Politica de Privacidade
            </span>
            <h1 className="heading-display text-foreground mb-6">
              Politica de Privacidade
            </h1>
            <p className="text-lg text-muted-foreground max-w-2xl mx-auto">
              Esta politica explica como tratamos os seus dados pessoais ao
              utilizar o nosso site e canais de contacto.
            </p>
          </div>
        </section>

        <section className="section-padding">
          <div className="section-container max-w-3xl space-y-10 text-muted-foreground">
            <div className="space-y-3">
              <h2 className="heading-card text-foreground">1. Dados recolhidos</h2>
              <p>
                Podemos recolher dados de contacto fornecidos por si, como nome,
                telefone e email, quando entra em contacto ou solicita
                orcamento.
              </p>
            </div>

            <div className="space-y-3">
              <h2 className="heading-card text-foreground">2. Finalidade</h2>
              <p>
                Utilizamos os dados apenas para responder a pedidos, preparar
                orcamentos e melhorar o atendimento.
              </p>
            </div>

            <div className="space-y-3">
              <h2 className="heading-card text-foreground">3. Partilha</h2>
              <p>
                Nao partilhamos dados pessoais com terceiros, exceto quando
                exigido por lei.
              </p>
            </div>

            <div className="space-y-3">
              <h2 className="heading-card text-foreground">4. Conservacao</h2>
              <p>
                Guardamos os dados apenas pelo tempo necessario para cumprir a
                finalidade do contacto.
              </p>
            </div>

            <div className="space-y-3">
              <h2 className="heading-card text-foreground">5. Direitos</h2>
              <p>
                Pode solicitar acesso, correcao ou eliminacao dos seus dados
                pessoais a qualquer momento.
              </p>
            </div>

            <div className="space-y-3">
              <h2 className="heading-card text-foreground">6. Contacto</h2>
              <p>
                Para questoes relacionadas com privacidade, contacte-nos pelos
                canais oficiais.
              </p>
            </div>
          </div>
        </section>
    </main>
  );
};

export default Privacidade;
