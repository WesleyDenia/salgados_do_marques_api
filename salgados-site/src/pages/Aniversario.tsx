import {
  ArrowRight,
  CalendarClock,
  CalendarDays,
  CheckCircle2,
  Gift,
  MessageCircle,
  PartyPopper,
  ShoppingBag,
  Sparkles,
  Users,
} from "lucide-react";
import heroImage from "@/assets/hero-salgados.jpg";
import { Seo } from "@/components/Seo";
import { Button } from "@/components/ui/button";
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "@/components/ui/accordion";
import { ADDRESS, CONTACT_PHONE, OG_IMAGES, SITE_NAME, SITE_URL } from "@/lib/site";

const ANNIVERSARY_WHATSAPP_NUMBER = CONTACT_PHONE.replace(/\D/g, "");
const ANNIVERSARY_WHATSAPP_TEXT =
  "Olá, quero aproveitar a campanha de aniversário dos Salgados do Marquês";
const ANNIVERSARY_WHATSAPP_URL = `https://wa.me/${ANNIVERSARY_WHATSAPP_NUMBER}?text=${encodeURIComponent(
  ANNIVERSARY_WHATSAPP_TEXT,
)}`;

const campaignPacks = [
  {
    units: 25,
    previousPrice: "9€",
    promotionalPrice: "4,50€",
    savings: "4,50€",
    highlight: "Até 50% OFF",
    description: "A escolha certa para provar, partilhar ou garantir um lanche especial.",
    featured: true,
  },
  {
    units: 50,
    previousPrice: "16,80€",
    promotionalPrice: "9€",
    savings: "7,80€",
    highlight: "Poupa 7,80€",
    description: "Ideal para encontros pequenos, mesas de apoio e convívios em casa.",
    featured: false,
  },
  {
    units: 75,
    previousPrice: "25,20€",
    promotionalPrice: "13,50€",
    savings: "11,70€",
    highlight: "Poupa 11,70€",
    description: "Uma opção equilibrada para receber bem sem complicações.",
    featured: false,
  },
  {
    units: 100,
    previousPrice: "30€",
    promotionalPrice: "18€",
    savings: "12€",
    highlight: "Melhor para famílias, festas e eventos",
    description: "Perfeito para quem já tem datas, convidados e ocasiões planeadas até ao fim do ano.",
    featured: false,
    emphasized: true,
  },
];

const campaignSteps = [
  {
    title: "Escolha o pack",
    description: "Veja os packs promocionais e selecione a quantidade mais adequada para a sua festa ou evento.",
    icon: ShoppingBag,
  },
  {
    title: "Encomende e pague entre 25 e 28 de Junho de 2026",
    description: "Garanta já os seus salgados com preço especial de aniversário durante os quatro dias da campanha.",
    icon: CalendarDays,
  },
  {
    title: "Levante até 31 de Dezembro de 2026",
    description: "Compre agora, levante depois. O levantamento é feito mediante agendamento e disponibilidade.",
    icon: CalendarClock,
  },
];

const idealForItems = [
  { title: "Festas", description: "Tenha a mesa pronta para receber mais convidados com menos custo.", icon: PartyPopper },
  {
    title: "Reuniões de família",
    description: "Uma solução prática para quem quer servir bem sem cozinhar no dia.",
    icon: Users,
  },
  {
    title: "Eventos de empresa",
    description: "Reserve com antecedência e organize coffee breaks, reuniões ou ações internas.",
    icon: Sparkles,
  },
  {
    title: "Aniversários",
    description: "Garanta o pack ideal para a festa e deixe tudo pago dentro do prazo promocional.",
    icon: Gift,
  },
  {
    title: "Lanches e convívios",
    description: "Perfeito para encontros já planeados até ao fim do ano, com levantamento na data certa.",
    icon: CheckCircle2,
  },
];

const faqItems = [
  {
    question: "Posso comprar agora e levantar depois?",
    answer:
      "Sim. A campanha foi pensada exatamente para isso: compra e pagamento entre 25 e 28 de Junho de 2026, com levantamento posterior mediante agendamento e disponibilidade.",
  },
  {
    question: "Até quando posso levantar?",
    answer:
      "Pode levantar a sua encomenda até 31 de Dezembro de 2026, desde que a data seja combinada previamente e exista disponibilidade.",
  },
  {
    question: "Tenho de pagar no ato da encomenda?",
    answer:
      "Sim. Para garantir o preço promocional de aniversário, a encomenda deve ficar paga durante o período da campanha, entre 25 e 28 de Junho de 2026.",
  },
  {
    question: "Posso escolher a data de levantamento?",
    answer:
      "Sim. A data de levantamento é agendada consigo, de acordo com a sua necessidade e com a disponibilidade da loja.",
  },
  {
    question: "A campanha vale para todos os packs?",
    answer:
      "Sim. A campanha aplica-se aos quatro packs promocionais apresentados nesta página, com descontos até 50%, válidos apenas durante os dias 25, 26, 27 e 28 de Junho de 2026.",
  },
];

const heroHighlights = [
  "2 anos de Salgados do Marquês",
  "Campanha limitada",
  "Até 50% de desconto",
  "Levantamento até 31/12/2026",
];

const Aniversario = () => {
  const faqSchema = {
    "@context": "https://schema.org",
    "@type": "FAQPage",
    mainEntity: faqItems.map((item) => ({
      "@type": "Question",
      name: item.question,
      acceptedAnswer: {
        "@type": "Answer",
        text: item.answer,
      },
    })),
  };

  const offerCatalogSchema = {
    "@context": "https://schema.org",
    "@type": "OfferCatalog",
    name: "Campanha de aniversário Salgados do Marquês",
    itemListElement: campaignPacks.map((pack, index) => ({
      "@type": "ListItem",
      position: index + 1,
      item: {
        "@type": "Offer",
        name: `Pack ${pack.units} unidades`,
        priceCurrency: "EUR",
        price: pack.promotionalPrice.replace("€", "").replace(",", "."),
        priceValidUntil: "2026-06-28",
        availability: "https://schema.org/InStock",
        url: `${SITE_URL}/aniversario`,
        eligibleRegion: {
          "@type": "Country",
          name: "Portugal",
        },
      },
    })),
  };

  const saleEventSchema = {
    "@context": "https://schema.org",
    "@type": "SaleEvent",
    name: "Campanha de aniversário de 2 anos Salgados do Marquês",
    startDate: "2026-06-25",
    endDate: "2026-06-28",
    eventAttendanceMode: "https://schema.org/OfflineEventAttendanceMode",
    location: {
      "@type": "Place",
      name: SITE_NAME,
      address: {
        "@type": "PostalAddress",
        streetAddress: ADDRESS.street,
        postalCode: ADDRESS.postalCode,
        addressLocality: ADDRESS.city,
        addressRegion: ADDRESS.region,
        addressCountry: ADDRESS.country,
      },
    },
    url: `${SITE_URL}/aniversario`,
    description:
      "Campanha limitada de aniversário com descontos até 50% em packs de salgados. Compra e pagamento entre 25 e 28 de Junho de 2026, com levantamento até 31 de Dezembro de 2026 mediante agendamento e disponibilidade.",
  };

  return (
    <main className="overflow-hidden">
      <Seo
        title={`${SITE_NAME} | Aniversário: até 50% de desconto`}
        description="Campanha de aniversário Salgados do Marquês: compre entre 25 e 28 de Junho de 2026, deixe pago e levante quando quiser até 31 de Dezembro de 2026. Packs promocionais até 50% OFF."
        canonical={`${SITE_URL}/aniversario`}
        ogImage={OG_IMAGES.aniversario}
        schema={[
          saleEventSchema,
          offerCatalogSchema,
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
              {
                "@type": "ListItem",
                position: 2,
                name: "Aniversário",
                item: `${SITE_URL}/aniversario`,
              },
            ],
          },
        ]}
      />

      <section className="relative isolate overflow-hidden bg-[#5b0608] text-white">
        <div
          className="absolute inset-0 bg-cover bg-center opacity-20"
          style={{ backgroundImage: `url(${heroImage})` }}
        />
        <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(249,205,111,0.2),transparent_28%),radial-gradient(circle_at_bottom_left,rgba(255,255,255,0.08),transparent_28%),linear-gradient(135deg,rgba(71,5,8,0.96),rgba(120,14,18,0.94)_45%,rgba(58,4,6,0.98)_100%)]" />
        <div className="absolute -left-16 top-20 h-48 w-48 rounded-full bg-[#d9a441]/10 blur-3xl" />
        <div className="absolute right-0 top-8 h-64 w-64 rounded-full bg-[#f6d27a]/10 blur-3xl" />

        <div className="section-container relative z-10 py-14 md:py-20">
          <div className="grid items-center gap-12 lg:grid-cols-[1.1fr_0.9fr]">
            <div className="space-y-8 animate-fade-up">
              <span className="inline-flex items-center gap-2 rounded-full border border-[#f3d48d]/30 bg-[#f3d48d]/10 px-4 py-2 text-sm font-semibold text-[#f6dfaa] backdrop-blur-sm">
                <Gift className="h-4 w-4" />
                Campanha de aniversário | 25 a 28 de Junho de 2026
              </span>

              <div className="space-y-5">
                <h1 className="heading-display max-w-3xl text-balance text-white">
                  Aniversário Salgados do Marquês: até 50% de desconto
                </h1>
                <p className="max-w-2xl text-base leading-relaxed text-white/85 sm:text-lg">
                  Compre entre 25 e 28 de Junho, deixe pago e levante quando quiser até 31 de
                  Dezembro de 2026.
                </p>
                <p className="max-w-2xl text-sm leading-relaxed text-[#f6dfaa] sm:text-base">
                  Garanta já os seus salgados com preço especial de aniversário. Perfeito para quem já
                  tem festas, eventos ou encontros planeados até ao fim do ano.
                </p>
              </div>

              <div className="grid gap-3 sm:grid-cols-2">
                {heroHighlights.map((item) => (
                  <div
                    key={item}
                    className="rounded-2xl border border-white/10 bg-white/10 px-4 py-4 text-sm font-medium text-white/90 backdrop-blur-sm"
                  >
                    {item}
                  </div>
                ))}
              </div>

              <div className="flex flex-col gap-4 sm:flex-row">
                <Button variant="hero" size="lg" asChild>
                  <a
                    href={ANNIVERSARY_WHATSAPP_URL}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="flex items-center gap-2"
                  >
                    <MessageCircle className="h-5 w-5" />
                    Encomendar pelo WhatsApp
                  </a>
                </Button>

                <Button variant="hero-secondary" size="lg" asChild>
                  <a href="#packs" className="flex items-center gap-2">
                    Ver packs da campanha
                    <ArrowRight className="h-5 w-5" />
                  </a>
                </Button>
              </div>

              <p className="text-sm text-white/72">
                Campanha limitada aos dias 25, 26, 27 e 28 de Junho de 2026. Levantamento mediante
                agendamento e disponibilidade.
              </p>
            </div>

            <div className="relative mx-auto w-full max-w-[540px] animate-fade-up lg:justify-self-end">
              <div className="relative overflow-hidden rounded-[2rem] border border-[#f3d48d]/20 bg-[#3b0305]/40 p-3 shadow-[0_30px_80px_-28px_rgba(0,0,0,0.55)] backdrop-blur-sm">
                <div className="absolute left-4 top-4 z-10 rounded-full bg-[#f2cf7c] px-4 py-2 text-xs font-bold uppercase tracking-[0.18em] text-[#5b0608]">
                  Maior promoção do ano
                </div>
                <div className="overflow-hidden rounded-[1.5rem]">
                  <img
                    src={heroImage}
                    alt="Coxinhas e mini salgados preparados para festas"
                    className="aspect-[5/4] w-full object-cover"
                  />
                </div>
                <div className="absolute inset-x-6 bottom-6 rounded-[1.5rem] border border-white/10 bg-[linear-gradient(135deg,rgba(91,6,8,0.92),rgba(142,32,31,0.86))] p-5 shadow-lg backdrop-blur-sm">
                  <p className="text-xs font-semibold uppercase tracking-[0.2em] text-[#f6dfaa]">
                    Compre agora, levante depois
                  </p>
                  <p className="mt-2 text-lg font-semibold text-white">
                    Packs promocionais para garantir já a sua próxima ocasião.
                  </p>
                </div>
              </div>

              <div className="absolute -bottom-10 right-4 hidden w-48 overflow-hidden rounded-[1.5rem] border border-[#f3d48d]/25 bg-white/90 shadow-[0_24px_60px_-26px_rgba(0,0,0,0.55)] sm:block">
                <img
                  src="/aneversario_preco.jpeg"
                  alt="Tabela promocional de aniversário dos Salgados do Marquês"
                  className="w-full object-cover"
                  loading="lazy"
                />
              </div>
            </div>
          </div>
        </div>
      </section>

      <section className="bg-[#fff7ee] py-16 md:py-20">
        <div className="section-container">
          <div className="mx-auto max-w-3xl text-center">
            <span className="highlight-badge border border-primary/10 bg-primary/5">
              Como funciona a campanha
            </span>
            <h2 className="heading-section mt-4 text-[#4b1113]">
              Três passos simples para garantir o preço especial de aniversário
            </h2>
            <p className="mt-4 text-base leading-relaxed text-[#6d4c41]">
              Compre agora, levante depois. A campanha foi feita para quem quer organizar festas e
              eventos com antecedência e pagar menos.
            </p>
          </div>

          <div className="mt-12 grid gap-6 md:grid-cols-3">
            {campaignSteps.map((step, index) => {
              const Icon = step.icon;

              return (
                <div
                  key={step.title}
                  className="relative rounded-[1.75rem] border border-[#edd4be] bg-white p-7 shadow-[0_24px_60px_-34px_rgba(91,6,8,0.28)]"
                >
                  <div className="mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-[#5b0608] text-[#f6dfaa]">
                    <Icon className="h-6 w-6" />
                  </div>
                  <span className="text-xs font-bold uppercase tracking-[0.22em] text-[#b88839]">
                    Passo {index + 1}
                  </span>
                  <h3 className="mt-3 text-2xl font-semibold text-[#4b1113]">{step.title}</h3>
                  <p className="mt-3 text-sm leading-7 text-[#6d4c41]">{step.description}</p>
                </div>
              );
            })}
          </div>
        </div>
      </section>

      <section id="packs" className="bg-[#f9efe1] py-16 md:py-20">
        <div className="section-container">
          <div className="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div className="max-w-3xl">
              <span className="inline-flex items-center gap-2 rounded-full bg-[#f4ddb2] px-4 py-2 text-sm font-semibold text-[#6f0f12]">
                Packs promocionais
              </span>
              <h2 className="heading-section mt-4 text-[#4b1113]">
                Escolha o pack certo e encomende pelo WhatsApp
              </h2>
              <p className="mt-4 text-base leading-relaxed text-[#6d4c41]">
                Cada card mostra quantidade, valor anterior, preço de campanha e poupança. Os preços
                promocionais são válidos apenas para encomendas pagas de 25 a 28 de Junho de 2026.
              </p>
            </div>

            <a
              href={ANNIVERSARY_WHATSAPP_URL}
              target="_blank"
              rel="noopener noreferrer"
              className="inline-flex items-center gap-2 text-sm font-semibold text-[#6f0f12] hover:text-[#5b0608]"
            >
              Falar no WhatsApp para reservar
              <ArrowRight className="h-4 w-4" />
            </a>
          </div>

          <div className="mt-12 grid gap-6 xl:grid-cols-4 md:grid-cols-2">
            {campaignPacks.map((pack) => (
              <article
                key={pack.units}
                className={`relative flex h-full flex-col overflow-hidden rounded-[1.9rem] border ${
                  pack.featured
                    ? "border-[#f0c66c] bg-[linear-gradient(180deg,#6f0f12_0%,#4d0709_100%)] text-white shadow-[0_32px_70px_-32px_rgba(91,6,8,0.7)]"
                    : "border-[#ead1b8] bg-white text-[#4b1113] shadow-[0_24px_60px_-34px_rgba(91,6,8,0.28)]"
                }`}
              >
                <div className="p-7">
                  <div className="flex items-start justify-between gap-4">
                    <div>
                      <p className={`text-sm font-semibold uppercase tracking-[0.18em] ${pack.featured ? "text-[#f6dfaa]" : "text-[#b88839]"}`}>
                        Pack promocional
                      </p>
                      <h3 className="mt-3 text-4xl font-semibold">{pack.units} unidades</h3>
                    </div>
                    <span
                      className={`max-w-[11rem] rounded-full px-4 py-2 text-right text-[11px] font-bold uppercase leading-4 tracking-[0.16em] ${
                        pack.featured
                          ? "bg-[#f2cf7c] text-[#5b0608]"
                          : pack.emphasized
                            ? "bg-[#6f0f12] text-[#f6dfaa]"
                            : "bg-[#fdf3df] text-[#7a1014]"
                      }`}
                    >
                      {pack.highlight}
                    </span>
                  </div>

                  <div className="mt-8 space-y-3">
                    <p className={`text-sm ${pack.featured ? "text-white/70" : "text-[#8f6d62]"}`}>
                      Preço habitual
                    </p>
                    <p className={`text-2xl font-semibold line-through ${pack.featured ? "text-white/55" : "text-[#8f6d62]"}`}>
                      {pack.previousPrice}
                    </p>
                    <div
                      className={`rounded-[1.5rem] p-5 ${
                        pack.featured
                          ? "border border-white/10 bg-white/10 backdrop-blur-sm"
                          : "border border-[#f1dfcf] bg-[#fff7ef]"
                      }`}
                    >
                      <p className={`text-sm ${pack.featured ? "text-white/78" : "text-[#6d4c41]"}`}>
                        Preço de aniversário
                      </p>
                      <p className={`mt-2 text-5xl font-semibold ${pack.featured ? "text-[#f6dfaa]" : "text-[#6f0f12]"}`}>
                        {pack.promotionalPrice}
                      </p>
                    </div>
                  </div>

                  <p className={`mt-5 text-sm leading-7 ${pack.featured ? "text-white/82" : "text-[#6d4c41]"}`}>
                    {pack.description}
                  </p>

                  <div className={`mt-6 rounded-2xl px-4 py-3 text-sm font-medium ${pack.featured ? "bg-white/10 text-white/88" : "bg-[#fdf4ea] text-[#6f0f12]"}`}>
                    Poupa {pack.savings} nesta campanha.
                  </div>
                </div>

                <div className={`mt-auto border-t px-7 py-6 ${pack.featured ? "border-white/10 bg-black/10" : "border-[#f1dfcf] bg-[#fffaf4]"}`}>
                  <Button
                    variant={pack.featured ? "hero" : "cta"}
                    size="lg"
                    className="w-full"
                    asChild
                  >
                    <a
                      href={ANNIVERSARY_WHATSAPP_URL}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="flex items-center justify-center gap-2"
                    >
                      <MessageCircle className="h-5 w-5" />
                      Encomendar este pack
                    </a>
                  </Button>
                </div>
              </article>
            ))}
          </div>

          <p className="mt-8 text-center text-sm text-[#7f655c]">
            Compra e pagamento entre 25 e 28 de Junho de 2026. Levantamento até 31 de Dezembro de
            2026, mediante agendamento e disponibilidade.
          </p>
        </div>
      </section>

      <section className="bg-[#5b0608] py-16 text-white">
        <div className="section-container">
          <div className="grid gap-8 lg:grid-cols-[1fr_auto] lg:items-center">
            <div>
              <p className="text-sm font-semibold uppercase tracking-[0.22em] text-[#f6dfaa]">
                Só de 25 a 28 de Junho de 2026
              </p>
              <h2 className="mt-3 text-4xl font-semibold leading-tight">
                Depois desta data, os valores promocionais deixam de estar disponíveis
              </h2>
              <p className="mt-4 max-w-2xl text-base leading-relaxed text-white/80">
                Se já tem uma festa, um convívio ou um evento marcado para os próximos meses, esta é a
                altura certa para garantir o preço especial de aniversário e deixar a encomenda tratada.
              </p>
            </div>

            <div className="grid grid-cols-4 gap-3 sm:gap-4">
              {["25", "26", "27", "28"].map((day) => (
                <div
                  key={day}
                  className="rounded-2xl border border-[#f3d48d]/30 bg-white/10 px-4 py-5 text-center backdrop-blur-sm"
                >
                  <div className="text-3xl font-semibold text-[#f6dfaa]">{day}</div>
                  <div className="mt-1 text-xs font-semibold uppercase tracking-[0.16em] text-white/75">
                    Junho
                  </div>
                </div>
              ))}
            </div>
          </div>

          <div className="mt-8 flex flex-col gap-4 sm:flex-row sm:items-center">
            <Button variant="hero" size="lg" asChild>
              <a
                href={ANNIVERSARY_WHATSAPP_URL}
                target="_blank"
                rel="noopener noreferrer"
                className="flex items-center gap-2"
              >
                <MessageCircle className="h-5 w-5" />
                Garantir preço especial
              </a>
            </Button>
            <p className="text-sm text-white/72">
              Campanha limitada aos dias 25, 26, 27 e 28 de Junho. Sem pagamento dentro desse período,
              os valores promocionais não ficam reservados.
            </p>
          </div>
        </div>
      </section>

      <section className="bg-[#fff9f1] py-16 md:py-20">
        <div className="section-container">
          <div className="mx-auto max-w-3xl text-center">
            <span className="inline-flex items-center gap-2 rounded-full bg-[#f4ddb2] px-4 py-2 text-sm font-semibold text-[#6f0f12]">
              Ideal para
            </span>
            <h2 className="heading-section mt-4 text-[#4b1113]">
              Uma campanha pensada para quem quer organizar-se já
            </h2>
            <p className="mt-4 text-base leading-relaxed text-[#6d4c41]">
              Perfeito para quem já tem festas, eventos ou encontros planeados até ao fim do ano.
            </p>
          </div>

          <div className="mt-12 grid gap-6 md:grid-cols-2 xl:grid-cols-5">
            {idealForItems.map((item) => {
              const Icon = item.icon;

              return (
                <div
                  key={item.title}
                  className="rounded-[1.7rem] border border-[#ecd7c7] bg-white p-6 shadow-[0_22px_50px_-34px_rgba(91,6,8,0.32)]"
                >
                  <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#6f0f12] text-[#f6dfaa]">
                    <Icon className="h-5 w-5" />
                  </div>
                  <h3 className="mt-5 text-2xl font-semibold text-[#4b1113]">{item.title}</h3>
                  <p className="mt-3 text-sm leading-7 text-[#6d4c41]">{item.description}</p>
                </div>
              );
            })}
          </div>
        </div>
      </section>

      <section className="bg-[#f9efe1] py-16 md:py-20">
        <div className="section-container">
          <div className="grid gap-10 lg:grid-cols-[0.95fr_1.05fr] lg:items-start">
            <div className="space-y-5">
              <span className="inline-flex items-center gap-2 rounded-full bg-[#f4ddb2] px-4 py-2 text-sm font-semibold text-[#6f0f12]">
                Perguntas frequentes
              </span>
              <h2 className="heading-section text-[#4b1113]">
                Tudo o que precisa de saber antes de encomendar
              </h2>
              <p className="text-base leading-relaxed text-[#6d4c41]">
                Comunicação simples, processo claro e campanha com datas bem definidas. Se precisar de
                confirmar a melhor opção, o WhatsApp é o canal mais rápido.
              </p>
              <Button variant="cta" size="lg" asChild>
                <a
                  href={ANNIVERSARY_WHATSAPP_URL}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="flex items-center gap-2"
                >
                  <MessageCircle className="h-5 w-5" />
                  Tirar dúvidas no WhatsApp
                </a>
              </Button>
            </div>

            <div className="rounded-[1.9rem] border border-[#ead1b8] bg-white px-6 py-3 shadow-[0_24px_60px_-34px_rgba(91,6,8,0.28)] sm:px-8">
              <Accordion type="single" collapsible className="w-full">
                {faqItems.map((item, index) => (
                  <AccordionItem key={item.question} value={`faq-${index}`} className="border-[#ecd9c9]">
                    <AccordionTrigger className="py-5 text-left text-lg font-semibold text-[#4b1113] hover:no-underline">
                      {item.question}
                    </AccordionTrigger>
                    <AccordionContent className="pb-5 text-base leading-7 text-[#6d4c41]">
                      {item.answer}
                    </AccordionContent>
                  </AccordionItem>
                ))}
              </Accordion>
            </div>
          </div>
        </div>
      </section>

      <section className="bg-[linear-gradient(135deg,#6f0f12_0%,#4b0608_100%)] py-16 text-white">
        <div className="section-container">
          <div className="mx-auto max-w-4xl text-center">
            <p className="text-sm font-semibold uppercase tracking-[0.22em] text-[#f6dfaa]">
              Campanha final
            </p>
            <h2 className="mt-4 text-4xl font-semibold leading-tight md:text-5xl">
              Garanta já os seus salgados com preço especial de aniversário
            </h2>
            <p className="mx-auto mt-5 max-w-3xl text-base leading-relaxed text-white/82 md:text-lg">
              Compre agora, levante depois. Aproveite a campanha limitada aos dias 25, 26, 27 e 28 de
              Junho de 2026 e reserve já pelo WhatsApp.
            </p>

            <div className="mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row">
              <Button variant="hero" size="xl" asChild>
                <a
                  href={ANNIVERSARY_WHATSAPP_URL}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="flex items-center gap-2"
                >
                  <MessageCircle className="h-5 w-5" />
                  Encomendar pelo WhatsApp
                </a>
              </Button>

              <Button variant="hero-secondary" size="xl" asChild>
                <a href="#packs" className="flex items-center gap-2">
                  Rever packs da campanha
                  <ArrowRight className="h-5 w-5" />
                </a>
              </Button>
            </div>

            <p className="mt-6 text-sm text-white/72">
              Levantamento até 31 de Dezembro de 2026, mediante agendamento e disponibilidade.
            </p>
          </div>
        </div>
      </section>
    </main>
  );
};

export default Aniversario;
