import { Button } from "@/components/ui/button";
import { MessageCircle } from "lucide-react";
import salgados70gImage from "@/assets/salgados-70g.jpg";
import miniSalgadosImage from "@/assets/mini-salgados.jpg";
import miniChurrosImage from "@/assets/churros.webp";
import paoQueijoImage from "@/assets/pao-queijo.jpg";
import { Seo } from "@/components/Seo";
import { OG_IMAGES, SITE_NAME, SITE_URL } from "@/lib/site";

const products = [
  {
    id: "salgados-70g",
    name: "Salgados 70g",
    description:
      "Versão maior e mais substancial dos nossos salgados. Perfeitos para lanches ou refeições ligeiras.",
    uses: ["Lanche", "Refeição ligeira", "Eventos"],
    flavors: [
      "Coxinha de Frango",
      "Coxinha de Fiambre e Queijo",
      "Coxinha de Leitão",
      "Coxinha de Bacalhau",
      "Kibe",
      "Folheados (Consulte disponibilidade)",
    ],
    image: salgados70gImage,
  },
  {
    id: "mini-salgados",
    name: "Mini Salgados",
    description:
      "Tamanho ideal para festas e eventos. Variedade de sabores para agradar a todos os paladares.",
    uses: ["Festas", "Eventos empresariais", "Celebrações"],
    flavors: [
      "Coxinha de Frango",
      "Enroladinho de Salsicha",
      "Bolinha de Queijo",
      "Travesseirinho de Carne",
      "Pack Mix (Todos os sabores, exceto o kibe)",
      "Mini Kibe",
    ],
    image: miniSalgadosImage,
  },
  {
    id: "mini-churros",
    name: "Mini Churros",
    description:
      "Crocantes e cobertos com açúcar e canela. A sobremesa perfeita para fechar qualquer evento.",
    uses: ["Sobremesa", "Complemento doce", "Festas infantis"],
    flavors: ["Doce de Leite", "Creme de avelã"],
    image: miniChurrosImage,
  },
  {
    id: "pao-queijo",
    name: "Pão de Queijo",
    description:
      "Quentinho, macio por dentro e com crosta dourada. Um clássico que agrada sempre.",
    uses: ["Pequeno-almoço", "Lanche", "Acompanhamento"],
    flavors: ["Tradicional", "Recheado com chouriço (calabresa)"],
    image: paoQueijoImage,
  },
];

const Produtos = () => {
  const miniSalgadosFritos = [
    { quantity: "25 un", price: "9,00€" },
    { quantity: "50 un", price: "16,80€" },
    { quantity: "75 un", price: "25,20€" },
    { quantity: "100 un", price: "30,00€" },
  ];

  const miniSalgadosCongelados = [
    { quantity: "25 un", price: "7,50€" },
    { quantity: "50 un", price: "14,00€" },
    { quantity: "75 un", price: "21,50€" },
    { quantity: "100 un", price: "25,00€" },
  ];

  const miniChurrosPrices = [
    { quantity: "5 un", price: "1,80€" },
    { quantity: "10 un", price: "3,60€" },
    { quantity: "15 un", price: "5,40€" },
    { quantity: "20 un", price: "7,20€" },
    { quantity: "25 un", price: "9,00€" },
  ];

  const renderPricing = (productId: string) => {
    if (productId === "salgados-70g") {
      return (
        <div className="rounded-xl border border-border bg-secondary/20 p-4">
          <p className="text-sm font-medium text-foreground mb-1">Valor:</p>
          <p className="text-lg font-semibold text-foreground">1€ a unidade</p>
        </div>
      );
    }

    if (productId === "mini-salgados") {
      return (
        <div className="space-y-4">
          <p className="text-lg font-medium text-foreground">Mini Salgados - Tabela de preços</p>
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="rounded-xl border border-border overflow-hidden">
              <div className="bg-secondary/40 px-4 py-2 text-sm font-semibold text-foreground">Fritos</div>
              <table className="w-full text-sm">
                <tbody>
                  {miniSalgadosFritos.map((row) => (
                    <tr key={`fritos-${row.quantity}`} className="border-t border-border">
                      <td className="px-4 py-2 text-muted-foreground">{row.quantity}</td>
                      <td className="px-4 py-2 text-right font-medium text-foreground">{row.price}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            <div className="rounded-xl border border-border overflow-hidden">
              <div className="bg-secondary/40 px-4 py-2 text-sm font-semibold text-foreground">Congelados</div>
              <table className="w-full text-sm">
                <tbody>
                  {miniSalgadosCongelados.map((row) => (
                    <tr key={`congelados-${row.quantity}`} className="border-t border-border">
                      <td className="px-4 py-2 text-muted-foreground">{row.quantity}</td>
                      <td className="px-4 py-2 text-right font-medium text-foreground">{row.price}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      );
    }

    if (productId === "mini-churros") {
      return (
        <div className="space-y-3">
          <p className="text-lg font-medium text-foreground">Valores</p>
          <div className="rounded-xl border border-border overflow-hidden">
            <table className="w-full text-sm">
              <tbody>
                {miniChurrosPrices.map((row) => (
                  <tr key={`churros-${row.quantity}`} className="border-t border-border">
                    <td className="px-4 py-2 text-muted-foreground">{row.quantity}</td>
                    <td className="px-4 py-2 text-right font-medium text-foreground">{row.price}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      );
    }

    if (productId === "pao-queijo") {
      return (
        <div className="space-y-4">
          <p className="text-lg font-medium text-foreground">Tabela de preços</p>
          <div className="rounded-xl border border-border overflow-hidden">
            <table className="w-full text-sm">
              <thead>
                <tr className="bg-secondary/40">
                  <th className="px-4 py-2 text-left font-semibold text-foreground">Produto</th>
                  <th className="px-4 py-2 text-right font-semibold text-foreground">Congelados</th>
                  <th className="px-4 py-2 text-right font-semibold text-foreground">Assados</th>
                </tr>
              </thead>
              <tbody>
                <tr className="border-t border-border">
                  <td className="px-4 py-2 text-muted-foreground">Pão de Queijo Tradicional (25 un)</td>
                  <td className="px-4 py-2 text-right font-medium text-foreground">8,50€</td>
                  <td className="px-4 py-2 text-right font-medium text-foreground">10,00€</td>
                </tr>
                <tr className="border-t border-border">
                  <td className="px-4 py-2 text-muted-foreground">Pão de Queijo Recheado com Chouriço (25 un)</td>
                  <td className="px-4 py-2 text-right font-medium text-foreground">9,50€</td>
                  <td className="px-4 py-2 text-right font-medium text-foreground">11,00€</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      );
    }

    return null;
  };

  const productSchema = {
    "@context": "https://schema.org",
    "@type": "ItemList",
    itemListElement: products.map((product, index) => ({
      "@type": "ListItem",
      position: index + 1,
      item: {
        "@type": "Product",
        name: product.name,
        description: product.description,
      },
    })),
  };

  return (
    <main>
      <Seo
        title={`${SITE_NAME} | Produtos`}
        description="Conheça os nossos produtos: salgados 70g, mini salgados, mini churros e pão de queijo. Produção própria e qualidade consistente."
        canonical={`${SITE_URL}/produtos`}
        ogImage={OG_IMAGES.produtos}
        schema={[
          productSchema,
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
                name: "Produtos",
                item: `${SITE_URL}/produtos`,
              },
            ],
          },
        ]}
      />
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
                    <div>
                      <p className="text-lg font-medium text-foreground mb-3">
                        Sabores:
                      </p>
                      <ul className="grid gap-2 text-lg text-muted-foreground sm:grid-cols-2">
                        {product.flavors.map((flavor) => (
                          <li key={flavor} className="flex items-start gap-2">
                            <span className="mt-1 text-primary">•</span>
                            <span>{flavor}</span>
                          </li>
                        ))}
                      </ul>
                    </div>
                    {renderPricing(product.id)}
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
                Fale connosco para encomendas
              </h3>
              <p className="text-muted-foreground mb-6">
                Para quantidades maiores, personalização ou eventos, peça um
                orçamento rápido sem compromisso.
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
