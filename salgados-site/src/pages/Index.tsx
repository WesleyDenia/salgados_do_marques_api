import { Seo } from "@/components/Seo";
import { ADDRESS, CONTACT_PHONE, OG_IMAGES, SITE_NAME, SITE_URL } from "@/lib/site";
import { HeroSection } from "@/components/home/HeroSection";
import { EmergencySupportSection } from "@/components/home/EmergencySupportSection";
import { CTASection } from "@/components/home/CTASection";

const Index = () => {
  const localBusinessSchema = {
    "@context": "https://schema.org",
    "@type": "LocalBusiness",
    name: SITE_NAME,
    url: SITE_URL,
    image: OG_IMAGES.home,
    telephone: CONTACT_PHONE,
    address: {
      "@type": "PostalAddress",
      streetAddress: ADDRESS.street,
      postalCode: ADDRESS.postalCode,
      addressLocality: ADDRESS.city,
      addressRegion: ADDRESS.region,
      addressCountry: ADDRESS.country,
    },
    areaServed: [
      { "@type": "Country", name: "Portugal" },
      { "@type": "AdministrativeArea", name: "Leiria" },
      { "@type": "City", name: "Pombal" },
    ],
    inLanguage: "pt-PT",
  };

  const faqSchema = {
    "@context": "https://schema.org",
    "@type": "FAQPage",
    mainEntity: [
      {
        "@type": "Question",
        name: "Fazem encomendas de salgados em Portugal?",
        acceptedAnswer: {
          "@type": "Answer",
          text: "Sim. Atendemos encomendas de salgados para festas em Portugal, com foco em Pombal e região de Leiria.",
        },
      },
      {
        "@type": "Question",
        name: "Têm salgadinhos para festa em Portugal?",
        acceptedAnswer: {
          "@type": "Answer",
          text: "Temos mini salgados, salgados 70g, mini churros e pão de queijo para aniversários, eventos empresariais e celebrações.",
        },
      },
    ],
  };

  return (
    <main>
      <Seo
        title={`${SITE_NAME} | Salgados em Portugal e Salgadinhos para Festa`}
        description="Salgados em Portugal para festas e eventos: mini salgados, salgados 70g, mini churros e pão de queijo. Encomendas de salgadinhos para festa em Portugal, com atendimento em Pombal e região."
        canonical={`${SITE_URL}/`}
        ogImage={OG_IMAGES.home}
        schema={[
          localBusinessSchema,
          {
            "@context": "https://schema.org",
            "@type": "WebSite",
            name: SITE_NAME,
            url: SITE_URL,
            inLanguage: "pt-PT",
          },
          faqSchema,
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
            ],
          },
        ]}
      />
      <HeroSection />
      <EmergencySupportSection />
      <CTASection />
    </main>
  );
};

export default Index;
