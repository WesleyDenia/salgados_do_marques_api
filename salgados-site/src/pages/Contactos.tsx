import { Button } from "@/components/ui/button";
import { MessageCircle, Phone, Mail, MapPin, Clock } from "lucide-react";
import { Seo } from "@/components/Seo";
import { OG_IMAGES, SITE_NAME, SITE_URL } from "@/lib/site";

const Contactos = () => {
  return (
    <main>
      <Seo
        title={`${SITE_NAME} | Contactos`}
        description="Fale connosco pelo WhatsApp, telefone ou e-mail. Atendimento e encomendas em Pombal e região."
        canonical={`${SITE_URL}/contactos`}
        ogImage={OG_IMAGES.contactos}
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
                name: "Contactos",
                item: `${SITE_URL}/contactos`,
              },
            ],
          },
        ]}
      />
      {/* Hero */}
        <section className="section-padding bg-gradient-to-b from-secondary/50 to-background">
          <div className="section-container text-center">
            <span className="highlight-badge mb-4 inline-block">
              Contactos
            </span>
            <h1 className="heading-display text-foreground mb-6 max-w-3xl mx-auto">
              Vamos planear a sua{" "}
              <span className="gradient-text">encomenda</span>
            </h1>
            <p className="text-lg text-muted-foreground max-w-2xl mx-auto">
              Entre em contacto connosco para receber um orçamento personalizado 
              ou esclarecer qualquer dúvida.
            </p>
          </div>
        </section>

        {/* Contact Section */}
        <section className="section-padding">
          <div className="section-container">
            <div className="grid lg:grid-cols-2 gap-16">
              {/* Contact Info */}
              <div className="space-y-8 animate-fade-up">
                <div>
                  <h2 className="heading-section text-foreground mb-4">
                    Fale connosco
                  </h2>
                  <p className="text-muted-foreground">
                    A forma mais rápida de obter resposta é através do WhatsApp. 
                    Respondemos a todas as mensagens em até 24 horas úteis.
                  </p>
                </div>

                {/* WhatsApp CTA */}
                <div className="bg-[hsl(142_70%_95%)] border border-[hsl(142_70%_45%/0.3)] rounded-2xl p-6">
                  <div className="flex items-center gap-4 mb-4">
                    <div className="w-12 h-12 bg-[hsl(142_70%_45%)] rounded-full flex items-center justify-center">
                      <MessageCircle className="w-6 h-6 text-white" />
                    </div>
                    <div>
                      <h3 className="font-semibold text-foreground">WhatsApp</h3>
                      <p className="text-sm text-muted-foreground">Resposta mais rápida</p>
                    </div>
                  </div>
                  <Button variant="whatsapp" className="w-full" asChild>
                    <a
                      href="https://wa.me/351939197110"
                      target="_blank"
                      rel="noopener noreferrer"
                      className="flex items-center justify-center gap-2"
                    >
                      <MessageCircle className="w-5 h-5" />
                      Enviar Mensagem
                    </a>
                  </Button>
                </div>

                {/* Other Contacts */}
                <div className="space-y-4">
                  <div className="flex items-center gap-4 p-4 bg-card rounded-lg border border-border">
                    <div className="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center">
                      <Phone className="w-5 h-5 text-primary" />
                    </div>
                    <div>
                      <p className="text-sm text-muted-foreground">Telefone</p>
                      <a
                        href="tel:+351939197110"
                        className="font-medium text-foreground hover:text-primary transition-colors"
                      >
                        +351 939 197 110
                      </a>
                    </div>
                  </div>

                  <div className="flex items-center gap-4 p-4 bg-card rounded-lg border border-border">
                    <div className="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center">
                      <Mail className="w-5 h-5 text-primary" />
                    </div>
                    <div>
                      <p className="text-sm text-muted-foreground">E-mail</p>
                      <a
                        href="mailto:info@salgadosdomarques.pt"
                        className="font-medium text-foreground hover:text-primary transition-colors"
                      >
                        info@salgadosdomarques.pt
                      </a>
                    </div>
                  </div>

                  <div className="flex items-start gap-4 p-4 bg-card rounded-lg border border-border">
                    <div className="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center flex-shrink-0">
                      <MapPin className="w-5 h-5 text-primary" />
                    </div>
                    <div>
                      <p className="text-sm text-muted-foreground">Morada</p>
                      <p className="font-medium text-foreground">
                        Rua Filarmónica Artística Pombalense, 17
                      </p>
                      <p className="text-sm text-muted-foreground">
                        3100-430 Pombal, Leiria
                      </p>
                    </div>
                  </div>

                  <div className="flex items-start gap-4 p-4 bg-card rounded-lg border border-border">
                    <div className="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center flex-shrink-0">
                      <Clock className="w-5 h-5 text-primary" />
                    </div>
                    <div>
                      <p className="text-sm text-muted-foreground">Horário de funcionamento</p>
                      <div className="space-y-1">
                        <p className="font-medium text-foreground">
                          Terça a Sábado: 12h - 20h
                        </p>
                        <p className="text-sm text-muted-foreground">
                          Domingo: apenas retirada de encomendas
                        </p>
                        <p className="text-sm text-muted-foreground">
                          Segunda: Encerrado
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              {/* Google Maps */}
              <div className="animate-fade-up" style={{ animationDelay: "0.1s" }}>
                <div className="bg-card rounded-2xl p-4 border border-border h-full">
                  <h3 className="heading-card text-foreground mb-4">
                    A nossa localização
                  </h3>
                  <div className="rounded-xl overflow-hidden h-[500px]">
                    <iframe
                      src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3046.123456789!2d-8.6338712!3d39.9130005!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xd22676dff9e3169%3A0xcb6b0c464394f4ce!2sSalgados%20Do%20Marqu%C3%AAs!5e0!3m2!1spt-PT!2spt!4v1700000000000!5m2!1spt-PT!2spt"
                      width="100%"
                      height="100%"
                      style={{ border: 0 }}
                      allowFullScreen
                      loading="lazy"
                      referrerPolicy="no-referrer-when-downgrade"
                      title="Localização Salgados do Marquês"
                      className="w-full h-full"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>
    </main>
  );
};

export default Contactos;
