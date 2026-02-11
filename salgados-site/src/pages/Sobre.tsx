import { Shield, Award, Leaf, Clock } from "lucide-react";

const values = [
  {
    icon: Shield,
    title: "Qualidade Constante",
    description:
      "Processos rigorosos de produção que garantem o mesmo padrão de excelência em cada encomenda.",
  },
  {
    icon: Award,
    title: "Ingredientes Selecionados",
    description:
      "Utilizamos apenas ingredientes de qualidade comprovada, sem compromissos.",
  },
  {
    icon: Leaf,
    title: "Higiene e Segurança",
    description:
      "Instalações e procedimentos que cumprem todas as normas de segurança alimentar.",
  },
  {
    icon: Clock,
    title: "Pontualidade",
    description:
      "Respeitamos os prazos acordados para que o seu evento decorra sem preocupações.",
  },
];

const Sobre = () => {
  return (
    <main>
        {/* Hero */}
        <section className="section-padding bg-gradient-to-b from-secondary/50 to-background">
          <div className="section-container">
            <div className="max-w-3xl mx-auto text-center">
              <span className="highlight-badge mb-4 inline-block">
                Sobre Nós
              </span>
              <h1 className="heading-display text-foreground mb-6">
                Qualidade e organização{" "}
                <span className="gradient-text">em cada detalhe</span>
              </h1>
              <p className="text-lg text-muted-foreground leading-relaxed">
                A Salgados do Marquês nasceu com uma missão clara: oferecer 
                salgados e doces de qualidade para festas e eventos, com a 
                organização e consistência que os nossos clientes merecem.
              </p>
            </div>
          </div>
        </section>

        {/* Our Story */}
        <section className="section-padding">
          <div className="section-container">
            <div className="grid lg:grid-cols-2 gap-16 items-center">
              <div className="space-y-6 animate-fade-up">
                <h2 className="heading-section text-foreground">
                  A nossa abordagem
                </h2>
                <div className="space-y-4 text-muted-foreground leading-relaxed">
                  <p>
                    Acreditamos que a comida de qualidade deve ser acessível e 
                    prática. Por isso, focamo-nos em criar produtos saborosos 
                    e consistentes, preparados com processos bem definidos.
                  </p>
                  <p>
                    A nossa fábrica opera com padrões rigorosos de higiene e 
                    segurança alimentar. Cada produto passa por controlo de 
                    qualidade antes de sair das nossas instalações.
                  </p>
                  <p>
                    Trabalhamos com festas e eventos de todas as dimensões, 
                    desde pequenas reuniões familiares a grandes celebrações 
                    empresariais. O nosso objetivo é sempre o mesmo: entregar 
                    um produto que supere expectativas.
                  </p>
                </div>
              </div>
              <div className="animate-fade-up" style={{ animationDelay: "0.1s" }}>
                <div className="bg-secondary/50 rounded-2xl p-8 space-y-6">
                  <h3 className="heading-card text-foreground">
                    O que nos define
                  </h3>
                  <div className="space-y-4">
                    <div className="flex items-start gap-4">
                      <div className="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center flex-shrink-0">
                        <span className="text-primary font-bold">1</span>
                      </div>
                      <div>
                        <p className="font-medium text-foreground">Produção própria</p>
                        <p className="text-sm text-muted-foreground">
                          Controlo total sobre a qualidade do início ao fim.
                        </p>
                      </div>
                    </div>
                    <div className="flex items-start gap-4">
                      <div className="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center flex-shrink-0">
                        <span className="text-primary font-bold">2</span>
                      </div>
                      <div>
                        <p className="font-medium text-foreground">Processos definidos</p>
                        <p className="text-sm text-muted-foreground">
                          Metodologia que garante consistência em cada lote.
                        </p>
                      </div>
                    </div>
                    <div className="flex items-start gap-4">
                      <div className="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center flex-shrink-0">
                        <span className="text-primary font-bold">3</span>
                      </div>
                      <div>
                        <p className="font-medium text-foreground">Foco no cliente</p>
                        <p className="text-sm text-muted-foreground">
                          Atendimento personalizado para cada necessidade.
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        {/* Values */}
        <section className="section-padding bg-secondary/30">
          <div className="section-container">
            <div className="text-center max-w-2xl mx-auto mb-16">
              <h2 className="heading-section text-foreground mb-4">
                Os nossos valores
              </h2>
              <p className="text-muted-foreground">
                Princípios que orientam cada decisão e cada produto que sai 
                das nossas instalações.
              </p>
            </div>

            <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
              {values.map((value, index) => (
                <div
                  key={value.title}
                  className="text-center animate-fade-up"
                  style={{ animationDelay: `${index * 0.1}s` }}
                >
                  <div className="w-16 h-16 bg-card rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-md">
                    <value.icon className="w-8 h-8 text-primary" />
                  </div>
                  <h3 className="font-display text-lg font-semibold text-foreground mb-2">
                    {value.title}
                  </h3>
                  <p className="text-sm text-muted-foreground">
                    {value.description}
                  </p>
                </div>
              ))}
            </div>
          </div>
        </section>
    </main>
  );
};

export default Sobre;
