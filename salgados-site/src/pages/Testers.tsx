import { FormEvent, useMemo, useState } from "react";
import {
  BadgePercent,
  ChevronRight,
  CircleCheckBig,
  Coins,
  Crown,
  Gift,
  Lock,
  ShieldCheck,
  Sparkles,
  Smartphone,
  Users,
} from "lucide-react";
import heroSalgados from "@/assets/hero-salgados.jpg";
import miniSalgados from "@/assets/mini-salgados.jpg";
import paoQueijo from "@/assets/pao-queijo.jpg";
import churros from "@/assets/churros.webp";
import { Seo } from "@/components/Seo";
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/components/ui/accordion";
import { Button } from "@/components/ui/button";
import { Card } from "@/components/ui/card";
import { Checkbox } from "@/components/ui/checkbox";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group";
import { useToast } from "@/hooks/use-toast";
import { CONTACT_PHONE, OG_IMAGES, SITE_NAME, SITE_URL } from "@/lib/site";
import { submitAppTester } from "@/lib/app-testers";

const benefits = [
  {
    icon: BadgePercent,
    title: "15% OFF durante 20 dias",
    description: "Uma vantagem real para fazer as suas encomendas no app com mais conveniência e melhor preço.",
  },
  {
    icon: Coins,
    title: "200 Pontos de oferta",
    description:
      "Entre já com saldo promocional que pode ser trocado por produtos e aproveite a nova experiência com recompensa desde o primeiro acesso.",
  },
  {
    icon: Crown,
    title: "Acesso antecipado ao novo app",
    description: "Receba o convite primeiro, descubra novidades antes do público geral e faça parte do grupo VIP.",
  },
];

const steps = [
  {
    icon: Users,
    title: "Preencha os seus dados",
    description: "Deixe o nome, email e contacto em menos de um minuto.",
  },
  {
    icon: Smartphone,
    title: "Receba o convite oficial do Google",
    description: "Enviamos o convite para o email indicado, sem passos complicados.",
  },
  {
    icon: Gift,
    title: "Instale e comece com vantagens",
    description: "Ative o app, use o desconto e entre com pontos promocionais para trocar por produtos.",
  },
];

const faqItems = [
  {
    question: "Quem pode participar?",
    answer:
      "Qualquer cliente da Salgados do Marquês pode pedir acesso. Nesta primeira fase, as vagas prioritárias são para utilizadores Android.",
  },
  {
    question: "Tenho de pagar alguma coisa?",
    answer: "Não. A participação é gratuita e as vantagens da campanha são reais.",
  },
  {
    question: "Como recebo o convite?",
    answer: "Depois do registo, enviamos para o seu email o convite oficial do Google Play para instalar o app.",
  },
  {
    question: "Quando começam os benefícios?",
    answer: "Assim que entrar no teste e concluir a instalação, os benefícios ficam disponíveis dentro da campanha.",
  },
  {
    question: "Posso sair depois?",
    answer: "Sim. Pode deixar de participar quando quiser, sem custos nem fidelização.",
  },
];

const socialHighlights = [
  "Convite oficial via Google Play",
  "Grupo limitado a 50 clientes",
  "Oferta real e acesso antecipado",
];

type FormState = {
  name: string;
  email: string;
  phone: string;
  operatingSystem: "android" | "ios";
  consent: boolean;
};

const initialState: FormState = {
  name: "",
  email: "",
  phone: "",
  operatingSystem: "android",
  consent: false,
};

const Testers = () => {
  const { toast } = useToast();
  const [form, setForm] = useState<FormState>(initialState);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submitMessage, setSubmitMessage] = useState<string | null>(null);
  const [fieldError, setFieldError] = useState<string | null>(null);

  const faqSchema = useMemo(
    () => ({
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
    }),
    [],
  );

  const offerSchema = {
    "@context": "https://schema.org",
    "@type": "Offer",
    name: "Campanha VIP Testers do App",
    price: "0",
    priceCurrency: "EUR",
    availability: "https://schema.org/LimitedAvailability",
    eligibleCustomerType: "https://schema.org/BusinessAudience",
    description:
      "Acesso antecipado ao novo app com 15% OFF durante 20 dias e 200 pontos promocionais para os primeiros 50 clientes.",
  };

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setFieldError(null);
    setSubmitMessage(null);
    setIsSubmitting(true);

    try {
      const response = await submitAppTester({
        name: form.name,
        email: form.email,
        phone: form.phone,
        operating_system: form.operatingSystem,
        consent: form.consent,
        source_path: "/testers",
      });

      setSubmitMessage(
        response.message ??
          "Pedido registado com sucesso. Em breve vamos seguir com os próximos passos no email indicado.",
      );
      setForm((current) => ({
        ...initialState,
        operatingSystem: current.operatingSystem === "ios" ? "ios" : "android",
      }));

      toast({
        title: "Pedido registado",
        description: response.message ?? "Recebemos o seu pedido para entrar no Clube VIP.",
      });
    } catch (error) {
      const message =
        error instanceof Error
          ? error.message
          : "Não foi possível registar o seu pedido neste momento.";

      setFieldError(message);
      toast({
        title: "Não foi possível concluir",
        description: message,
        variant: "destructive",
      });
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <main className="overflow-hidden">
      <Seo
        title={`Clube VIP do App | ${SITE_NAME}`}
        description="Junte-se ao Clube VIP do Salgados do Marquês e garanta acesso antecipado ao novo app com 15% OFF durante 20 dias e 200 pontos promocionais. Apenas 50 vagas."
        canonical={`${SITE_URL}/testers`}
        ogImage={OG_IMAGES.home}
        schema={[faqSchema, offerSchema]}
      />

      <section className="section-padding relative">
        <div className="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top_right,rgba(145,2,2,0.16),transparent_30%),radial-gradient(circle_at_left,rgba(233,184,92,0.22),transparent_24%)]" />
        <div className="section-container">
          <div className="grid items-center gap-10 lg:grid-cols-[1.15fr_0.85fr]">
            <div className="space-y-8">
              <div className="inline-flex items-center gap-2 rounded-full border border-primary/15 bg-white/80 px-4 py-2 text-sm font-medium text-primary shadow-sm backdrop-blur">
                <Sparkles className="h-4 w-4" />
                Campanha limitada para os primeiros 50 clientes
              </div>

              <div className="space-y-5">
                <h1 className="heading-display max-w-3xl text-balance text-foreground">
                  Junta-te ao <span className="gradient-text">Clube VIP</span> do novo app do Salgados do
                  Marquês
                </h1>
                <p className="max-w-2xl text-lg leading-8 text-muted-foreground md:text-xl">
                  Estamos a abrir uma fase fechada para clientes selecionados que querem encomendar com mais
                  rapidez, vantagens reais e acesso antecipado ao que vem aí. Se entrar agora, recebe o convite
                  oficial do Google e começa com benefícios exclusivos.
                </p>
              </div>

              <div className="grid gap-4 sm:grid-cols-3">
                <Card className="rounded-[1.5rem] border-primary/10 bg-white/90 p-5 shadow-[0_24px_60px_-36px_rgba(109,23,23,0.45)]">
                  <div className="text-sm font-semibold uppercase tracking-[0.16em] text-primary/70">Oferta</div>
                  <div className="mt-2 text-3xl font-semibold text-foreground">15% OFF</div>
                  <p className="mt-2 text-sm leading-6 text-muted-foreground">Durante 20 dias para usar no app.</p>
                </Card>
                <Card className="rounded-[1.5rem] border-amber-200/60 bg-[#fff8ec] p-5 shadow-[0_24px_60px_-36px_rgba(109,23,23,0.45)]">
                  <div className="text-sm font-semibold uppercase tracking-[0.16em] text-amber-700">Bónus</div>
                  <div className="mt-2 text-3xl font-semibold text-foreground">200 Pontos</div>
                  <p className="mt-2 text-sm leading-6 text-muted-foreground">Saldo promocional para trocar por produtos no novo app.</p>
                </Card>
                <Card className="rounded-[1.5rem] border-primary/10 bg-[linear-gradient(160deg,#910202,#5d0101)] p-5 text-white shadow-[0_24px_60px_-36px_rgba(109,23,23,0.6)]">
                  <div className="text-sm font-semibold uppercase tracking-[0.16em] text-white/70">Vagas</div>
                  <div className="mt-2 text-3xl font-semibold">50 lugares</div>
                  <p className="mt-2 text-sm leading-6 text-white/75">Quando fechar, o acesso antecipado termina.</p>
                </Card>
              </div>

              <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
                <Button asChild size="xl" className="rounded-full px-8">
                  <a href="#tester-form">
                    Quero garantir a minha vaga
                    <ChevronRight className="h-4 w-4" />
                  </a>
                </Button>
                <div className="flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
                  {socialHighlights.map((item) => (
                    <span
                      key={item}
                      className="inline-flex items-center gap-2 rounded-full border border-border/80 bg-white/70 px-3 py-2"
                    >
                      <CircleCheckBig className="h-4 w-4 text-primary" />
                      {item}
                    </span>
                  ))}
                </div>
              </div>
            </div>

            <Card className="relative overflow-hidden rounded-[2rem] border-primary/10 bg-white/92 p-0 shadow-[0_30px_90px_-36px_rgba(109,23,23,0.55)]">
              <div className="absolute inset-0 bg-[linear-gradient(180deg,rgba(255,248,236,0.06),rgba(145,2,2,0.06))]" />
              <div className="grid gap-0 md:grid-cols-2 lg:grid-cols-1">
                <div className="relative min-h-[280px] overflow-hidden">
                  <img src={heroSalgados} alt="Seleção de salgados da marca" className="h-full w-full object-cover" />
                  <div className="absolute inset-0 bg-gradient-to-t from-[rgba(68,10,10,0.82)] via-[rgba(68,10,10,0.18)] to-transparent" />
                  <div className="absolute inset-x-0 bottom-0 p-6 text-white">
                    <div className="inline-flex items-center gap-2 rounded-full bg-white/14 px-3 py-2 text-xs font-semibold uppercase tracking-[0.18em] backdrop-blur">
                      <Lock className="h-4 w-4" />
                      Acesso antecipado
                    </div>
                    <p className="mt-4 text-lg font-semibold leading-7">
                      Uma experiência pensada para clientes que querem pedir mais depressa, com mais vantagens e sem perder tempo.
                    </p>
                  </div>
                </div>
                <div className="space-y-5 p-6">
                  <div>
                    <p className="text-sm font-semibold uppercase tracking-[0.18em] text-primary/70">Convite VIP</p>
                    <h2 className="mt-2 text-2xl font-semibold text-foreground">Oferta simples, valor imediato</h2>
                  </div>
                  <div className="space-y-3">
                    {benefits.map((benefit) => {
                      const Icon = benefit.icon;
                      return (
                        <div
                          key={benefit.title}
                          className="flex items-start gap-4 rounded-2xl border border-border/80 bg-[#fffdf9] p-4"
                        >
                          <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                            <Icon className="h-5 w-5" />
                          </div>
                          <div>
                            <h3 className="text-base font-semibold text-foreground">{benefit.title}</h3>
                            <p className="mt-1 text-sm leading-6 text-muted-foreground">{benefit.description}</p>
                          </div>
                        </div>
                      );
                    })}
                  </div>
                </div>
              </div>
            </Card>
          </div>
        </div>
      </section>

      <section className="pb-8">
        <div className="section-container">
          <div className="grid gap-6 md:grid-cols-3">
            {benefits.map((benefit) => {
              const Icon = benefit.icon;
              return (
                <Card key={benefit.title} className="card-elevated rounded-[1.75rem] border-primary/10 p-7">
                  <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                    <Icon className="h-6 w-6" />
                  </div>
                  <h2 className="mt-6 text-2xl text-foreground">{benefit.title}</h2>
                  <p className="mt-3 text-base leading-7 text-muted-foreground">{benefit.description}</p>
                </Card>
              );
            })}
          </div>
        </div>
      </section>

      <section className="section-padding pt-10">
        <div className="section-container">
          <div className="rounded-[2rem] bg-[linear-gradient(135deg,#5c0606_0%,#910202_52%,#b12323_100%)] px-7 py-8 text-white shadow-[0_30px_90px_-40px_rgba(109,23,23,0.75)] md:px-10 md:py-10">
            <div className="grid gap-6 lg:grid-cols-[1fr_auto] lg:items-center">
              <div>
                <p className="text-sm font-semibold uppercase tracking-[0.18em] text-white/65">Escassez real</p>
                <h2 className="mt-2 text-3xl md:text-4xl">Só vamos abrir 50 vagas nesta fase fechada.</h2>
                <p className="mt-4 max-w-3xl text-base leading-7 text-white/78 md:text-lg">
                  Este acesso antecipado é limitado. Quando as vagas forem preenchidas, fechamos a entrada para
                  garantir uma experiência cuidada e acompanhar de perto os primeiros clientes.
                </p>
              </div>
              <div className="rounded-[1.5rem] border border-white/15 bg-white/10 px-8 py-6 text-center backdrop-blur">
                <div className="text-sm uppercase tracking-[0.16em] text-white/70">Fase atual</div>
                <div className="mt-2 text-5xl font-semibold">50</div>
                <div className="mt-2 text-sm text-white/78">lugares disponíveis para o grupo VIP</div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section className="section-padding pt-6">
        <div className="section-container">
          <div className="grid gap-8 lg:grid-cols-[0.88fr_1.12fr] lg:items-center">
            <div className="space-y-5">
              <div className="highlight-badge">
                <ShieldCheck className="h-4 w-4" />
                Processo simples e sem atrito
              </div>
              <h2 className="heading-section max-w-xl text-balance">Como funciona</h2>
              <p className="max-w-xl text-lg leading-8 text-muted-foreground">
                Entrar no grupo VIP foi pensado para ser rápido. Deixa o teu contacto, recebe o convite oficial do
                Google e começa a usar o app sem complicações.
              </p>
            </div>

            <div className="grid gap-4">
              {steps.map((step, index) => {
                const Icon = step.icon;
                return (
                  <Card key={step.title} className="rounded-[1.5rem] border-border/80 p-6">
                    <div className="flex gap-5">
                      <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-[#fff1f1] text-primary">
                        <Icon className="h-6 w-6" />
                      </div>
                      <div>
                        <div className="text-sm font-semibold uppercase tracking-[0.16em] text-primary/65">
                          Passo {index + 1}
                        </div>
                        <h3 className="mt-2 text-xl text-foreground">{step.title}</h3>
                        <p className="mt-2 text-base leading-7 text-muted-foreground">{step.description}</p>
                      </div>
                    </div>
                  </Card>
                );
              })}
            </div>
          </div>
        </div>
      </section>

      <section className="section-padding pt-0">
        <div className="section-container">
          <div className="grid gap-8 lg:grid-cols-[1fr_1fr] lg:items-stretch">
            <Card className="relative overflow-hidden rounded-[2rem] border-primary/10 p-0">
              <div className="grid h-full md:grid-cols-3">
                {[miniSalgados, paoQueijo, churros].map((image, index) => (
                  <div key={image} className="relative min-h-[180px] md:min-h-full">
                    <img
                      src={image}
                      alt={index === 0 ? "Mini salgados para festas" : index === 1 ? "Pão de queijo" : "Churros da marca"}
                      className="h-full w-full object-cover"
                    />
                    <div className="absolute inset-0 bg-gradient-to-t from-[rgba(37,7,7,0.4)] to-transparent" />
                  </div>
                ))}
              </div>
            </Card>

            <Card className="rounded-[2rem] border-primary/10 bg-[linear-gradient(180deg,#fffaf1_0%,#ffffff_100%)] p-8">
              <div className="highlight-badge">
                <Sparkles className="h-4 w-4" />
                Porque estamos a convidar clientes VIP
              </div>
              <h2 className="mt-5 heading-section max-w-xl text-balance">
                Queremos lançar um app que fique à altura de quem já confia na marca
              </h2>
              <div className="mt-5 space-y-4 text-base leading-8 text-muted-foreground">
                <p>
                  Antes de abrir o acesso ao público, queremos ouvir clientes reais, perceber o que é mais útil e
                  refinar a experiência com base em quem já conhece a qualidade da Salgados do Marquês.
                </p>
                <p>
                  Os primeiros utilizadores ajudam-nos a moldar o app e, em troca, recebem vantagens exclusivas,
                  acesso antecipado e uma experiência mais próxima da marca.
                </p>
                <p>
                  Não é um registo técnico nem um processo complicado. É um convite limitado para quem gosta de
                  conveniência, benefícios reais e atendimento mais rápido.
                </p>
              </div>
            </Card>
          </div>
        </div>
      </section>

      <section id="tester-form" className="section-padding pt-4">
        <div className="section-container">
          <div className="grid gap-8 lg:grid-cols-[0.82fr_1.18fr]">
            <div className="space-y-6">
              <div className="highlight-badge">
                <Crown className="h-4 w-4" />
                Pedido de acesso
              </div>
              <h2 className="heading-section max-w-xl text-balance">Reserva a tua vaga em menos de um minuto</h2>
              <p className="max-w-xl text-lg leading-8 text-muted-foreground">
                Deixa o teu email para receberes o convite oficial do Google. O resto é simples: instalar, entrar e
                começar a usar com vantagens.
              </p>

              <div className="space-y-3">
                <div className="flex items-start gap-3 rounded-2xl border border-border/80 bg-white/80 p-4">
                  <ShieldCheck className="mt-0.5 h-5 w-5 text-primary" />
                  <p className="text-sm leading-6 text-muted-foreground">
                    Os seus dados são usados apenas para gerir o convite, o contacto desta campanha e a entrada na fase
                    fechada do app.
                  </p>
                </div>
                <div className="flex items-start gap-3 rounded-2xl border border-border/80 bg-white/80 p-4">
                  <CircleCheckBig className="mt-0.5 h-5 w-5 text-primary" />
                  <p className="text-sm leading-6 text-muted-foreground">
                    Sem pagamento, sem compromisso e com benefícios reais assim que o seu acesso for ativado.
                  </p>
                </div>
              </div>
            </div>

            <Card className="rounded-[2rem] border-primary/10 bg-white/95 p-6 shadow-[0_30px_90px_-42px_rgba(109,23,23,0.55)] md:p-8">
              <div className="flex flex-col gap-2">
                <p className="text-sm font-semibold uppercase tracking-[0.18em] text-primary/70">Clube VIP do app</p>
                <h3 className="text-3xl text-foreground">Quero receber o meu convite</h3>
                <p className="text-base leading-7 text-muted-foreground">
                  Preenche o formulário para entrar na seleção. Se fores dos primeiros, recebes o acesso e as ofertas
                  desta fase.
                </p>
              </div>

              <form className="mt-8 space-y-5" onSubmit={handleSubmit}>
                <div className="grid gap-5 md:grid-cols-2">
                  <div className="space-y-2 md:col-span-2">
                    <Label htmlFor="name">Nome</Label>
                    <Input
                      id="name"
                      placeholder="Como se chama?"
                      value={form.name}
                      onChange={(event) => setForm((current) => ({ ...current, name: event.target.value }))}
                      required
                    />
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="email">Email</Label>
                    <Input
                      id="email"
                      type="email"
                      placeholder="nome@email.com"
                      value={form.email}
                      onChange={(event) => setForm((current) => ({ ...current, email: event.target.value }))}
                      required
                    />
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="phone">Telefone / WhatsApp</Label>
                    <Input
                      id="phone"
                      type="tel"
                      placeholder="+351 ..."
                      value={form.phone}
                      onChange={(event) => setForm((current) => ({ ...current, phone: event.target.value }))}
                      required
                    />
                  </div>
                </div>

                <div className="space-y-3">
                  <Label>Sistema Operativo</Label>
                  <RadioGroup
                    value={form.operatingSystem}
                    onValueChange={(value: "android" | "ios") =>
                      setForm((current) => ({ ...current, operatingSystem: value }))
                    }
                    className="grid gap-3 md:grid-cols-2"
                  >
                    <Label
                      htmlFor="os-android"
                      className="flex cursor-pointer items-center gap-3 rounded-2xl border border-border bg-[#fffdf9] px-4 py-4 transition-colors hover:border-primary/40"
                    >
                      <RadioGroupItem value="android" id="os-android" />
                      <div>
                        <div className="font-semibold text-foreground">Android</div>
                        <div className="text-sm text-muted-foreground">Elegível para esta fase do convite</div>
                      </div>
                    </Label>
                    <Label
                      htmlFor="os-ios"
                      className="flex cursor-pointer items-center gap-3 rounded-2xl border border-border bg-[#fffdf9] px-4 py-4 transition-colors hover:border-primary/40"
                    >
                      <RadioGroupItem value="ios" id="os-ios" />
                      <div>
                        <div className="font-semibold text-foreground">iPhone</div>
                        <div className="text-sm text-muted-foreground">Guardamos o contacto para a próxima abertura</div>
                      </div>
                    </Label>
                  </RadioGroup>

                  {form.operatingSystem === "ios" ? (
                    <div className="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-900">
                      Nesta primeira fase estamos a abrir o acesso prioritário para Android. Se estiver em iPhone,
                      pode deixar já o seu contacto e será dos primeiros a saber quando abrirmos novas vagas.
                    </div>
                  ) : null}
                </div>

                <div className="rounded-2xl border border-border/80 bg-[#fffaf3] px-4 py-4">
                  <div className="flex items-start gap-3">
                    <Checkbox
                      id="consent"
                      checked={form.consent}
                      onCheckedChange={(checked) =>
                        setForm((current) => ({ ...current, consent: checked === true }))
                      }
                    />
                    <div className="space-y-1">
                      <Label htmlFor="consent" className="cursor-pointer text-sm leading-6">
                        Autorizo o uso dos meus dados para receber o convite do Google, comunicações desta campanha e
                        contacto relacionado com a fase fechada do app.
                      </Label>
                      <p className="text-sm leading-6 text-muted-foreground">
                        Pode pedir a remoção dos seus dados a qualquer momento através do WhatsApp {CONTACT_PHONE}.
                      </p>
                    </div>
                  </div>
                </div>

                {fieldError ? (
                  <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {fieldError}
                  </div>
                ) : null}

                {submitMessage ? (
                  <div className="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm leading-6 text-emerald-800">
                    {submitMessage}
                  </div>
                ) : null}

                <Button type="submit" size="xl" className="w-full rounded-full" disabled={isSubmitting}>
                  {isSubmitting ? "A registar o seu pedido..." : "Quero entrar no Clube VIP"}
                </Button>

                <p className="text-center text-sm leading-6 text-muted-foreground">
                  Grupo limitado, benefícios reais e processo simples. Se houver vaga nesta fase, seguimos consigo por
                  email com o convite oficial.
                </p>
              </form>
            </Card>
          </div>
        </div>
      </section>

      <section className="section-padding pt-0">
        <div className="section-container">
          <div className="grid gap-8 lg:grid-cols-[0.95fr_1.05fr]">
            <div className="space-y-5">
              <div className="highlight-badge">
                <Users className="h-4 w-4" />
                Dúvidas rápidas
              </div>
              <h2 className="heading-section">Perguntas frequentes</h2>
              <p className="max-w-xl text-lg leading-8 text-muted-foreground">
                Reunimos o essencial para que perceba rapidamente como funciona o convite e o que recebe ao entrar.
              </p>
            </div>

            <Card className="rounded-[2rem] border-primary/10 p-6 md:p-8">
              <Accordion type="single" collapsible className="w-full">
                {faqItems.map((item, index) => (
                  <AccordionItem key={item.question} value={`item-${index}`} className="border-border/70">
                    <AccordionTrigger className="text-left text-base font-semibold text-foreground hover:no-underline">
                      {item.question}
                    </AccordionTrigger>
                    <AccordionContent className="text-base leading-7 text-muted-foreground">
                      {item.answer}
                    </AccordionContent>
                  </AccordionItem>
                ))}
              </Accordion>
            </Card>
          </div>
        </div>
      </section>

      <section className="section-padding pt-0">
        <div className="section-container">
          <div className="rounded-[2.25rem] border border-primary/10 bg-[linear-gradient(135deg,#fff7ee_0%,#ffffff_45%,#fff1f1_100%)] px-7 py-10 shadow-[0_30px_90px_-42px_rgba(109,23,23,0.35)] md:px-10">
            <div className="grid gap-6 lg:grid-cols-[1fr_auto] lg:items-center">
              <div>
                <p className="text-sm font-semibold uppercase tracking-[0.18em] text-primary/70">Última chamada para esta fase</p>
                <h2 className="mt-2 text-3xl md:text-4xl">
                  Entra agora no grupo limitado e começa com <span className="gradient-text">15% OFF + 200 Pontos</span>
                </h2>
                <p className="mt-4 max-w-3xl text-base leading-7 text-muted-foreground md:text-lg">
                  Se gosta de encomendar com rapidez, benefícios reais e acesso antecipado, esta é a altura certa para
                  pedir a sua vaga.
                </p>
              </div>
              <Button asChild size="xl" className="rounded-full px-10">
                <a href="#tester-form">
                  Garantir 15% OFF + 200 Pontos
                  <ChevronRight className="h-4 w-4" />
                </a>
              </Button>
            </div>
          </div>
        </div>
      </section>
    </main>
  );
};

export default Testers;
