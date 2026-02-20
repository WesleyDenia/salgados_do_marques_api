import { Seo } from "@/components/Seo";
import { CONTACT_PHONE, OG_IMAGES, SITE_NAME, SITE_URL } from "@/lib/site";

const Privacidade = () => {
  return (
    <main>
      <Seo
        title={`Política de Privacidade e LGPD | ${SITE_NAME}`}
        description="Conheça como tratamos dados pessoais, os seus direitos como titular e como exercer pedidos nos termos da LGPD."
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
                name: "Início",
                item: `${SITE_URL}/`,
              },
              {
                "@type": "ListItem",
                position: 2,
                name: "Política de Privacidade e LGPD",
                item: `${SITE_URL}/privacidade`,
              },
            ],
          },
        ]}
      />
        <section className="section-padding bg-gradient-to-b from-secondary/50 to-background">
          <div className="section-container text-center">
            <span className="highlight-badge mb-4 inline-block">
              Política de Privacidade e LGPD
            </span>
            <h1 className="heading-display text-foreground mb-6">
              Política de Privacidade e LGPD
            </h1>
            <p className="text-lg text-muted-foreground max-w-2xl mx-auto">
              Esta política explica como tratamos os seus dados pessoais no
              site e nos canais de contacto, em conformidade com a Lei Geral de
              Proteção de Dados (LGPD - Lei n.º 13.709/2018).
            </p>
            <p className="text-sm text-muted-foreground mt-4">
              Última atualização: 20 de fevereiro de 2026.
            </p>
          </div>
        </section>

        <section className="section-padding">
          <div className="section-container max-w-3xl space-y-10 text-muted-foreground">
            <div className="space-y-3">
              <h2 className="heading-card text-foreground">1. Controlador dos dados</h2>
              <p>
                O controlador dos dados pessoais é a empresa {SITE_NAME},
                responsável por definir as finalidades e os meios de tratamento
                dos dados recolhidos através deste site e dos canais oficiais.
              </p>
            </div>

            <div className="space-y-3">
              <h2 className="heading-card text-foreground">2. Dados pessoais que tratamos</h2>
              <p>
                Podemos tratar dados fornecidos por si, como nome, telefone,
                e-mail, conteúdo da mensagem e informações de encomenda. Também
                podem ser recolhidos dados técnicos de navegação, como endereço
                IP, tipo de dispositivo e páginas visitadas.
              </p>
            </div>

            <div className="space-y-3">
              <h2 className="heading-card text-foreground">3. Finalidades do tratamento</h2>
              <p>
                Os dados são utilizados para responder aos seus pedidos,
                elaborar e gerir orçamentos e encomendas, prestar suporte,
                cumprir obrigações legais e melhorar os serviços e a experiência
                no site.
              </p>
            </div>

            <div className="space-y-3">
              <h2 className="heading-card text-foreground">4. Bases legais (LGPD)</h2>
              <p>
                O tratamento é realizado com base em uma ou mais hipóteses
                legais da LGPD, incluindo execução de contrato ou de
                procedimentos preliminares, cumprimento de obrigação legal,
                legítimo interesse e, quando aplicável, consentimento.
              </p>
            </div>

            <div className="space-y-3">
              <h2 className="heading-card text-foreground">5. Partilha com terceiros</h2>
              <p>
                Os dados podem ser partilhados apenas com operadores e
                prestadores essenciais à operação do negócio (por exemplo,
                hospedagem e comunicação), sempre sob dever de confidencialidade
                e segurança. Não comercializamos dados pessoais.
              </p>
            </div>

            <div className="space-y-3">
              <h2 className="heading-card text-foreground">6. Conservação dos dados</h2>
              <p>
                Conservamos os dados apenas pelo período necessário para cumprir
                as finalidades informadas, respeitar prazos legais e resguardar
                direitos em processos administrativos, arbitrais ou judiciais.
              </p>
            </div>

            <div className="space-y-3">
              <h2 className="heading-card text-foreground">7. Cookies e tecnologias semelhantes</h2>
              <p>
                Podemos utilizar cookies e tecnologias equivalentes para
                funcionamento do site, segurança, medição de desempenho e
                melhoria de navegação. Pode gerir as preferências de cookies no
                banner de consentimento e na opção "Preferências de Cookies" no
                rodapé do site, além das configurações do seu navegador.
              </p>
            </div>

            <div className="space-y-3">
              <h2 className="heading-card text-foreground">8. Segurança da informação</h2>
              <p>
                Adotamos medidas técnicas e organizacionais razoáveis para
                proteger os dados pessoais contra acesso não autorizado, perda,
                alteração ou divulgação indevida.
              </p>
            </div>

            <div className="space-y-3">
              <h2 className="heading-card text-foreground">9. Direitos do titular</h2>
              <p>
                Nos termos da LGPD, pode solicitar confirmação do tratamento,
                acesso, correção, anonimização, bloqueio, eliminação,
                portabilidade, informação sobre partilhas, revogação de
                consentimento e oposição ao tratamento quando aplicável.
              </p>
            </div>

            <div className="space-y-3">
              <h2 className="heading-card text-foreground">10. Como exercer os seus direitos</h2>
              <p>
                Para pedidos relacionados com dados pessoais, contacte-nos por
                e-mail em{" "}
                <a
                  href="mailto:info@salgadosdomarques.pt"
                  className="text-primary hover:underline"
                >
                  info@salgadosdomarques.pt
                </a>{" "}
                ou pelo WhatsApp/telefone{" "}
                <a
                  href={`https://wa.me/${CONTACT_PHONE.replace("+", "")}`}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="text-primary hover:underline"
                >
                  {CONTACT_PHONE}
                </a>
                . Poderemos solicitar dados adicionais para confirmar a sua
                identidade.
              </p>
            </div>

            <div className="space-y-3">
              <h2 className="heading-card text-foreground">11. Alterações desta política</h2>
              <p>
                Esta política pode ser atualizada periodicamente para refletir
                alterações legais, técnicas ou operacionais. A versão vigente
                estará sempre disponível nesta página.
              </p>
            </div>
          </div>
        </section>
    </main>
  );
};

export default Privacidade;
