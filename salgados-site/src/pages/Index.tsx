import { Seo } from "@/components/Seo";
import { ADDRESS, CONTACT_PHONE, OG_IMAGES, SITE_NAME, SITE_URL } from "@/lib/site";
import { HeroSection } from "@/components/home/HeroSection";
import { EmergencySupportSection } from "@/components/home/EmergencySupportSection";
import { CTASection } from "@/components/home/CTASection";

const Index = () => {
  return (
    <main>
      <Seo
        title={`${SITE_NAME} | Salgados e Doces em Pombal, Portugal`}
        description="Salgados e doces de qualidade para festas e eventos em Pombal e região. Coxinhas, mini salgados, mini churros e pão de queijo. Encomendas para aniversários, eventos empresariais e celebrações."
        canonical={`${SITE_URL}/`}
        ogImage={OG_IMAGES.home}
        schema={[
          {
            "@context": "https://schema.org",
            "@type": "Organization",
            name: SITE_NAME,
            url: SITE_URL,
            telephone: CONTACT_PHONE,
            address: {
              "@type": "PostalAddress",
              streetAddress: ADDRESS.street,
              postalCode: ADDRESS.postalCode,
              addressLocality: ADDRESS.city,
              addressRegion: ADDRESS.region,
              addressCountry: ADDRESS.country,
            },
          },
          {
            "@context": "https://schema.org",
            "@type": "WebSite",
            name: SITE_NAME,
            url: SITE_URL,
            inLanguage: "pt-PT",
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
