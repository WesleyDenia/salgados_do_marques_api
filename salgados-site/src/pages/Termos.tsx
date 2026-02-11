const Termos = () => {
  return (
    <main>
        <section className="section-padding bg-gradient-to-b from-secondary/50 to-background">
          <div className="section-container text-center">
            <span className="highlight-badge mb-4 inline-block">
              Termos e Condicoes
            </span>
            <h1 className="heading-display text-foreground mb-6">
              Termos e Condicoes
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
                Ao aceder ao site Salgados do Marques, o utilizador concorda com
                os presentes termos. Caso nao concorde, nao deve utilizar o
                site.
              </p>
            </div>

            <div className="space-y-3">
              <h2 className="heading-card text-foreground">2. Informacoes</h2>
              <p>
                As informacoes apresentadas no site podem ser alteradas sem
                aviso previo. As imagens sao ilustrativas.
              </p>
            </div>

            <div className="space-y-3">
              <h2 className="heading-card text-foreground">3. Encomendas</h2>
              <p>
                Os pedidos efetuados atraves dos nossos canais estao sujeitos a
                confirmacao. Prazos e valores podem variar conforme a
                disponibilidade e o tipo de evento.
              </p>
            </div>

            <div className="space-y-3">
              <h2 className="heading-card text-foreground">
                4. Responsabilidade
              </h2>
              <p>
                O Salgados do Marques nao se responsabiliza por falhas de
                ligacao, indisponibilidade temporaria do site ou danos
                decorrentes do uso do mesmo.
              </p>
            </div>

            <div className="space-y-3">
              <h2 className="heading-card text-foreground">
                5. Contacto
              </h2>
              <p>
                Para esclarecimentos, contacte-nos via email ou WhatsApp.
              </p>
            </div>
          </div>
        </section>
    </main>
  );
};

export default Termos;
