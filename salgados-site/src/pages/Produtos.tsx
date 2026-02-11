import { Button } from "@/components/ui/button";
import { MessageCircle } from "lucide-react";
import salgados70gImage from "@/assets/salgados-70g.jpg";
import miniSalgadosImage from "@/assets/mini-salgados.jpg";
import miniChurrosImage from "@/assets/churros.webp";
import paoQueijoImage from "@/assets/pao-queijo.jpg";

const products = [
  {
    id: "salgados-70g",
    name: "Salgados 70g",
    description:
      "Versão maior e mais substancial dos nossos salgados. Perfeitos para lanches ou refeições ligeiras.",
    uses: ["Lanche", "Refeição ligeira", "Eventos"],
    image: salgados70gImage,
  },
  {
    id: "mini-salgados",
    name: "Mini Salgados",
    description:
      "Tamanho ideal para festas e eventos. Variedade de sabores para agradar a todos os paladares.",
    uses: ["Festas", "Eventos empresariais", "Celebrações"],
    image: miniSalgadosImage,
  },
  {
    id: "mini-churros",
    name: "Mini Churros",
    description:
      "Crocantes e cobertos com açúcar e canela. A sobremesa perfeita para fechar qualquer evento.",
    uses: ["Sobremesa", "Complemento doce", "Festas infantis"],
    image: miniChurrosImage,
  },
  {
    id: "pao-queijo",
    name: "Pão de Queijo",
    description:
      "Quentinho, macio por dentro e com crosta dourada. Um clássico que agrada sempre.",
    uses: ["Pequeno-almoço", "Lanche", "Acompanhamento"],
    image: paoQueijoImage,
  },
];

const Produtos = () => {
  return (
    <main>
        {/* Hero */}
        <section className="section-padding bg-gradient-to-b from-secondary/50 to-background">
          <div className="section-container text-center">
            <span className="highlight-badge mb-4 inline-block">
              Os Nossos Produtos
            </span>
            <h1 className="heading-display text-foreground mb-6 max-w-3xl mx-auto">
              Qualidade e sabor em{" "}
              <span className="gradient-text">cada mordida</span>
            </h1>
            <p className="text-lg text-muted-foreground max-w-2xl mx-auto">
              Produção própria com ingredientes selecionados. Cada produto é 
              preparado com cuidado para garantir a melhor experiência.
            </p>
          </div>
        </section>

        {/* Products Grid */}
        <section className="section-padding">
          <div className="section-container">
            <div className="space-y-24">
              {products.map((product, index) => (
                <div
                  key={product.id}
                  className={`grid lg:grid-cols-2 gap-12 items-center ${
                    index % 2 === 1 ? "lg:flex-row-reverse" : ""
                  }`}
                >
                  <div
                    className={`animate-fade-up ${
                      index % 2 === 1 ? "lg:order-2" : ""
                    }`}
                  >
                    <div className="rounded-2xl overflow-hidden shadow-xl">
                      <img
                        src={product.image}
                        alt={product.name}
                        className="w-full h-auto object-cover aspect-square"
                      />
                    </div>
                  </div>
                  <div
                    className={`space-y-6 animate-fade-up ${
                      index % 2 === 1 ? "lg:order-1" : ""
                    }`}
                    style={{ animationDelay: "0.1s" }}
                  >
                    <h2 className="heading-section text-foreground">
                      {product.name}
                    </h2>
                    <p className="text-lg text-muted-foreground leading-relaxed">
                      {product.description}
                    </p>
                    <div>
                      <p className="text-sm font-medium text-foreground mb-3">
                        Sugestões de uso:
                      </p>
                      <div className="flex flex-wrap gap-2">
                        {product.uses.map((use) => (
                          <span
                            key={use}
                            className="px-3 py-1 bg-secondary text-secondary-foreground text-sm rounded-full"
                          >
                            {use}
                          </span>
                        ))}
                      </div>
                    </div>
                    <Button variant="cta" size="lg" asChild>
                      <a
                        href="https://wa.me/351939197110"
                        target="_blank"
                        rel="noopener noreferrer"
                        className="flex items-center gap-2"
                      >
                        <MessageCircle className="w-5 h-5" />
                        Pedir Orçamento
                      </a>
                    </Button>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </section>

        {/* Note */}
        <section className="section-padding bg-secondary/30">
          <div className="section-container">
            <div className="max-w-2xl mx-auto text-center">
              <h3 className="heading-card text-foreground mb-4">
                Preços sob consulta
              </h3>
              <p className="text-muted-foreground mb-6">
                Os valores variam de acordo com a quantidade, tipo de evento e 
                personalização pretendida. Contacte-nos para receber um 
                orçamento detalhado e sem compromisso.
              </p>
              <Button variant="hero" size="lg" asChild>
                <a
                  href="https://wa.me/351939197110"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="flex items-center gap-2"
                >
                  <MessageCircle className="w-5 h-5" />
                  Solicitar Orçamento
                </a>
              </Button>
            </div>
          </div>
        </section>
    </main>
  );
};

export default Produtos;
