import { useMemo, useState, type FormEvent } from "react";
import {
  ArrowRight,
  Check,
  CircleHelp,
  Gift,
  LockKeyhole,
  MapPin,
  MessageCircle,
  Search,
  ShieldCheck,
  Sparkles,
  Trophy,
  X,
} from "lucide-react";
import { Seo } from "@/components/Seo";
import { Button } from "@/components/ui/button";
import { OG_IMAGES, SITE_NAME, SITE_URL } from "@/lib/site";
import quizBackground from "@/assets/campanha-urbana-perguntas.png";

type CampaignType = "kibe" | "carne" | "salsicha" | "queijo" | "coxinha";

type QuizOption = {
  id: string;
  label: string;
};

type CampaignChallenge = {
  type: CampaignType;
  secretNumber: number;
  collectionLabel: string;
  eyebrow: string;
  riddle: string[];
  options: QuizOption[];
  correctOptionId: string;
  rewardWhenCorrect: number;
  rewardWhenWrong: number;
};

type MockClaim = {
  phone: string;
  couponCode: string;
};

const MOCK_CHALLENGES: Record<CampaignType, CampaignChallenge> = {
  kibe: {
    type: "kibe",
    secretNumber: 1,
    collectionLabel: "Kibe",
    eyebrow: "Uma pista de forma comprida",
    riddle: [
      "Por fora sou dourado e estaladiço.",
      "Por dentro, carne bem temperada.",
      "Tenho raízes no Médio Oriente e desapareço num instante.",
      "Quem sou eu?",
    ],
    options: [
      { id: "kibe", label: "Kibe" },
      { id: "coxinha", label: "Coxinha de Frango" },
      { id: "queijo", label: "Bolinha de Queijo" },
      { id: "carne", label: "Travesseirinho de Carne" },
    ],
    correctOptionId: "kibe",
    rewardWhenCorrect: 10,
    rewardWhenWrong: 5,
  },
  carne: {
    type: "carne",
    secretNumber: 2,
    collectionLabel: "Travesseirinho de Carne",
    eyebrow: "Uma pista macia por dentro",
    riddle: [
      "Tenho nome de coisa que ajuda a descansar.",
      "Mas ninguém me leva para a cama.",
      "Sou pequeno, recheado de carne e feito para partilhar.",
      "Quem sou eu?",
    ],
    options: [
      { id: "carne", label: "Travesseirinho de Carne" },
      { id: "salsicha", label: "Enroladinho de Salsicha" },
      { id: "kibe", label: "Kibe" },
      { id: "queijo", label: "Bolinha de Queijo" },
    ],
    correctOptionId: "carne",
    rewardWhenCorrect: 15,
    rewardWhenWrong: 10,
  },
  salsicha: {
    type: "salsicha",
    secretNumber: 3,
    collectionLabel: "Enroladinho de Salsicha",
    eyebrow: "Uma pista bem enrolada",
    riddle: [
      "Levo o recheio escondido num abraço dourado.",
      "Sou comprido, divertido e desapareço antes da festa começar.",
      "Quem sou eu?",
    ],
    options: [
      { id: "queijo", label: "Bolinha de Queijo" },
      { id: "salsicha", label: "Enroladinho de Salsicha" },
      { id: "coxinha", label: "Coxinha de Frango" },
      { id: "carne", label: "Travesseirinho de Carne" },
    ],
    correctOptionId: "salsicha",
    rewardWhenCorrect: 20,
    rewardWhenWrong: 10,
  },
  queijo: {
    type: "queijo",
    secretNumber: 4,
    collectionLabel: "Bolinha de Queijo",
    eyebrow: "Uma pista redonda e irresistível",
    riddle: [
      "Sou pequena, redonda e dourada.",
      "Quando me abrem, o meu coração pode esticar.",
      "Quem sou eu?",
    ],
    options: [
      { id: "kibe", label: "Kibe" },
      { id: "carne", label: "Travesseirinho de Carne" },
      { id: "queijo", label: "Bolinha de Queijo" },
      { id: "salsicha", label: "Enroladinho de Salsicha" },
    ],
    correctOptionId: "queijo",
    rewardWhenCorrect: 25,
    rewardWhenWrong: 15,
  },
  coxinha: {
    type: "coxinha",
    secretNumber: 5,
    collectionLabel: "Coxinha de Frango",
    eyebrow: "Talvez tenhas encontrado o lendário",
    riddle: [
      "Tenho uma armadura dourada,",
      "um coração cremoso",
      "e desapareço rapidamente quando chego à mesa.",
      "Quem sou eu?",
    ],
    options: [
      { id: "coxinha", label: "Coxinha de Frango" },
      { id: "kibe", label: "Kibe" },
      { id: "queijo", label: "Bolinha de Queijo" },
      { id: "carne", label: "Travesseirinho de Carne" },
    ],
    correctOptionId: "coxinha",
    rewardWhenCorrect: 50,
    rewardWhenWrong: 25,
  },
};

// Simula o inventário que, na versão final, será consultado no backend.
// O código é a única entrada pública e determina internamente o desafio.
const MOCK_QR_INVENTORY: Record<string, CampaignType> = {
  qrxpto: "kibe",
  "qr-carne": "carne",
  "qr-salsicha": "salsicha",
  "qr-queijo": "queijo",
  "qr-lendario": "coxinha",
};

const MOCK_COLLECTION: CampaignType[] = [];
const VALID_CODE_PATTERN = /^[a-zA-Z0-9-]{4,64}$/;

function readCampaignParams() {
  if (typeof window === "undefined") {
    return { code: "" };
  }

  const params = new URLSearchParams(window.location.search);

  return {
    code: params.get("code")?.trim() ?? "",
  };
}

function getMockChallengeByCode(code: string) {
  const campaignType = MOCK_QR_INVENTORY[code.toLowerCase()];

  if (!campaignType) {
    return null;
  }

  return MOCK_CHALLENGES[campaignType];
}

function normalisePortugueseMobile(value: string) {
  const digits = value.replace(/\D/g, "");

  if (/^9\d{8}$/.test(digits)) {
    return `351${digits}`;
  }

  if (/^3519\d{8}$/.test(digits)) {
    return digits;
  }

  return null;
}

const CampanhaUrbana = () => {
  const [{ code }] = useState(readCampaignParams);
  const [selectedOptionId, setSelectedOptionId] = useState<string | null>(null);
  const [hasAnswered, setHasAnswered] = useState(false);
  const [phone, setPhone] = useState("");
  const [phoneError, setPhoneError] = useState("");
  const [mockClaim, setMockClaim] = useState<MockClaim | null>(null);

  const hasValidCode = VALID_CODE_PATTERN.test(code);
  const challenge = hasValidCode ? getMockChallengeByCode(code) : null;
  const isValidCampaignLink = Boolean(challenge);

  const isCorrect = Boolean(
    challenge && selectedOptionId === challenge.correctOptionId,
  );

  const reward = useMemo(() => {
    if (!challenge || !hasAnswered) {
      return null;
    }

    return isCorrect ? challenge.rewardWhenCorrect : challenge.rewardWhenWrong;
  }, [challenge, hasAnswered, isCorrect]);

  const collection = useMemo(() => {
    if (!challenge || !hasAnswered) {
      return MOCK_COLLECTION;
    }

    return Array.from(new Set([...MOCK_COLLECTION, challenge.type]));
  }, [challenge, hasAnswered]);

  const submitAnswer = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();

    if (!selectedOptionId || hasAnswered) {
      return;
    }

    setHasAnswered(true);
  };

  const claimCoupon = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setPhoneError("");

    const normalisedPhone = normalisePortugueseMobile(phone);

    if (!normalisedPhone) {
      setPhoneError("Introduz um número de telemóvel português válido.");
      return;
    }

    setMockClaim({
      phone: normalisedPhone,
      couponCode: `SEG-${code.slice(0, 4).toUpperCase()}-${reward ?? 0}`,
    });
  };

  if (!isValidCampaignLink || !challenge) {
    return (
      <main className="min-h-screen bg-[#390305] text-white">
        <Seo
          title={`Ligação inválida | ${SITE_NAME}`}
          description="Esta pista não está disponível."
          canonical={`${SITE_URL}/campanha-urbana`}
          ogImage={OG_IMAGES.aniversario}
        />

        <section className="flex min-h-screen items-center justify-center px-5 py-12">
          <div className="w-full max-w-xl rounded-[2rem] border border-white/10 bg-white/[0.07] p-7 text-center shadow-2xl backdrop-blur-sm sm:p-10">
            <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-[#f2cf7c] text-[#5b0608]">
              <Search className="h-7 w-7" />
            </div>
            <p className="mt-7 text-sm font-bold uppercase tracking-[0.22em] text-[#f6dfaa]">
              Pombal tem um segredo
            </p>
            <h1 className="mt-3 text-3xl font-semibold sm:text-4xl">
              Esta pista não foi encontrada
            </h1>
            <p className="mx-auto mt-4 max-w-md leading-7 text-white/72">
              Confirma se o QR Code foi lido corretamente. Cada pista possui uma ligação única.
            </p>
          </div>
        </section>
      </main>
    );
  }

  return (
    <main className="min-h-screen overflow-hidden bg-[#fff8ee] text-[#401013]">
      <Seo
        title={`Segredo #${challenge.secretNumber} | Pombal tem um segredo`}
        description="Encontraste uma das pistas espalhadas por Pombal. Resolve a adivinha e descobre a tua recompensa."
        canonical={`${SITE_URL}/campanha-urbana`}
        ogImage={OG_IMAGES.aniversario}
      />

      <section className="relative isolate min-h-screen overflow-hidden bg-[#4a0507] text-white">
        <div
          className="absolute inset-0 bg-cover bg-center opacity-80"
          style={{ backgroundImage: `url(${quizBackground})` }}
          aria-hidden="true"
        />
        <div
          className="absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(53,2,4,0.34),rgba(34,1,3,0.72)_78%),linear-gradient(145deg,rgba(57,3,5,0.28),rgba(91,6,8,0.42))]"
          aria-hidden="true"
        />

        <div className="section-container relative z-10 flex min-h-screen flex-col py-6 sm:py-10">
          <header className="flex items-center justify-between gap-4">
            <div className="flex items-center gap-3">
              <div className="flex h-10 w-10 items-center justify-center rounded-xl border border-[#f2cf7c]/30 bg-[#f2cf7c]/10 text-[#f6dfaa]">
                <MapPin className="h-5 w-5" />
              </div>
              <div>
                <p className="text-xs font-bold uppercase tracking-[0.24em] text-[#f6dfaa]">
                  Pombal
                </p>
                <p className="text-sm text-white/68">Tem um segredo</p>
              </div>
            </div>

            <div className="rounded-full border border-white/10 bg-white/[0.07] px-4 py-2 text-xs font-semibold text-white/72 backdrop-blur-sm">
              Pista {challenge.secretNumber} de 5
            </div>
          </header>

          <div className="mx-auto grid w-full max-w-6xl flex-1 items-center gap-8 py-10 lg:grid-cols-[0.82fr_1.18fr] lg:gap-14">
            <div className="hidden space-y-6 lg:block">
              <div className="flex h-16 w-16 items-center justify-center rounded-2xl border border-[#f2cf7c]/30 bg-[#f2cf7c]/10 text-[#f6dfaa]">
                <LockKeyhole className="h-7 w-7" />
              </div>
              <div>
                <p className="text-sm font-bold uppercase tracking-[0.24em] text-[#f6dfaa]">
                  Encontraste uma pista
                </p>
                <h1 className="mt-4 max-w-md text-5xl font-semibold leading-[1.06] text-balance">
                  Mas o prémio ainda está escondido.
                </h1>
                <p className="mt-5 max-w-md text-lg leading-8 text-white/72">
                  Resolve a adivinha. Acertes ou não, há uma recompensa à tua espera.
                </p>
              </div>

              <div className="grid max-w-md grid-cols-5 gap-2">
                {(["kibe", "carne", "salsicha", "queijo", "coxinha"] as CampaignType[]).map(
                  (item, index) => {
                    const found = collection.includes(item);

                    return (
                      <div
                        key={item}
                        className={`flex aspect-square items-center justify-center rounded-xl border text-sm font-bold ${
                          found
                            ? "border-[#f2cf7c] bg-[#f2cf7c] text-[#5b0608]"
                            : "border-white/12 bg-white/[0.06] text-white/40"
                        }`}
                        aria-label={found ? `Segredo ${index + 1} descoberto` : `Segredo ${index + 1} por descobrir`}
                      >
                        {found ? <Check className="h-5 w-5" /> : index + 1}
                      </div>
                    );
                  },
                )}
              </div>
            </div>

            <div className="mx-auto w-full max-w-2xl">
              <div className="overflow-hidden rounded-[2rem] border border-[#f2cf7c]/20 bg-[#fffaf2] text-[#401013] shadow-[0_32px_100px_-30px_rgba(0,0,0,0.72)]">
                {!hasAnswered ? (
                  <form onSubmit={submitAnswer}>
                    <div className="border-b border-[#ead8c3] bg-[linear-gradient(135deg,#fffaf2,#f7e9d5)] px-6 py-7 sm:px-9 sm:py-9">
                      <div className="flex items-center gap-3 text-[#761014]">
                        <CircleHelp className="h-5 w-5" />
                        <p className="text-sm font-bold uppercase tracking-[0.2em]">
                          Segredo #{challenge.secretNumber}
                        </p>
                      </div>
                      <p className="mt-3 text-sm font-semibold text-[#a06b26]">
                        {challenge.eyebrow}
                      </p>
                    </div>

                    <div className="px-6 py-7 sm:px-9 sm:py-9">
                      <div className="space-y-1 text-xl font-semibold leading-8 sm:text-2xl sm:leading-9">
                        {challenge.riddle.map((line) => (
                          <p key={line}>{line}</p>
                        ))}
                      </div>

                      <fieldset className="mt-8 space-y-3">
                        <legend className="sr-only">Escolhe uma resposta</legend>
                        {challenge.options.map((option) => {
                          const selected = selectedOptionId === option.id;

                          return (
                            <label
                              key={option.id}
                              className={`flex cursor-pointer items-center gap-4 rounded-2xl border px-4 py-4 transition sm:px-5 ${
                                selected
                                  ? "border-[#8f1519] bg-[#8f1519] text-white shadow-lg"
                                  : "border-[#e5d2bd] bg-white hover:border-[#bc8b48] hover:bg-[#fff8ef]"
                              }`}
                            >
                              <input
                                type="radio"
                                name="quiz-answer"
                                value={option.id}
                                checked={selected}
                                onChange={() => setSelectedOptionId(option.id)}
                                className="sr-only"
                              />
                              <span
                                className={`flex h-7 w-7 shrink-0 items-center justify-center rounded-full border-2 ${
                                  selected ? "border-[#f5d58d] bg-[#f5d58d] text-[#6f0f12]" : "border-[#c8aa86]"
                                }`}
                              >
                                {selected && <Check className="h-4 w-4" />}
                              </span>
                              <span className="text-base font-semibold sm:text-lg">{option.label}</span>
                            </label>
                          );
                        })}
                      </fieldset>

                      <Button
                        type="submit"
                        size="xl"
                        disabled={!selectedOptionId}
                        className="mt-7 w-full bg-[#761014] text-white hover:bg-[#5b090c] disabled:cursor-not-allowed disabled:opacity-45"
                      >
                        Revelar o segredo
                        <ArrowRight className="ml-2 h-5 w-5" />
                      </Button>

                      <p className="mt-4 text-center text-xs leading-5 text-[#816d61]">
                        Há uma recompensa mesmo que não acertes.
                      </p>
                    </div>
                  </form>
                ) : (
                  <div aria-live="polite">
                    <div
                      className={`px-6 py-7 text-center sm:px-9 sm:py-9 ${
                        isCorrect
                          ? "bg-[linear-gradient(135deg,#f5d68e,#e9b94e)]"
                          : "bg-[linear-gradient(135deg,#f1dfc5,#dec39e)]"
                      }`}
                    >
                      <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-[#5b0608] text-[#f6dfaa] shadow-lg">
                        {isCorrect ? <Trophy className="h-8 w-8" /> : <Gift className="h-8 w-8" />}
                      </div>
                      <p className="mt-5 text-sm font-bold uppercase tracking-[0.22em] text-[#741014]">
                        {isCorrect ? "Acertaste!" : "Não foi desta — mas ganhas na mesma"}
                      </p>
                      <h2 className="mt-2 text-5xl font-bold text-[#4b080b] sm:text-6xl">
                        {reward}%
                      </h2>
                      <p className="mt-2 text-lg font-semibold text-[#5c1719]">de desconto</p>
                    </div>

                    <div className="px-6 py-7 sm:px-9 sm:py-9">
                      <div className="text-center">
                        <div className="inline-flex items-center gap-2 rounded-full bg-[#761014]/8 px-4 py-2 text-sm font-semibold text-[#761014]">
                          <Sparkles className="h-4 w-4" />
                          Encontraste: {challenge.collectionLabel}
                        </div>
                        <h2 className="mt-5 text-3xl font-semibold">O segredo é nosso.</h2>
                        <p className="mt-3 text-base leading-7 text-[#755e52]">
                          Salgados do Marquês, no Shopping de Pombal. Guarda o teu prémio e continua à procura dos outros segredos.
                        </p>
                      </div>

                      {!mockClaim ? (
                        <form onSubmit={claimCoupon} className="mt-8">
                          <label htmlFor="campaign-phone" className="text-sm font-bold text-[#4b1113]">
                            Número de telemóvel
                          </label>
                          <p className="mt-1 text-sm leading-6 text-[#816d61]">
                            Enviaremos o teu cupão por WhatsApp. Não é necessário criar conta.
                          </p>
                          <div className="mt-3 flex rounded-2xl border border-[#d8c1a7] bg-white focus-within:border-[#8f1519] focus-within:ring-2 focus-within:ring-[#8f1519]/15">
                            <span className="flex items-center border-r border-[#e4d2be] px-4 text-sm font-semibold text-[#6d4c41]">
                              +351
                            </span>
                            <input
                              id="campaign-phone"
                              type="tel"
                              inputMode="tel"
                              autoComplete="tel"
                              value={phone}
                              onChange={(event) => setPhone(event.target.value)}
                              placeholder="912 345 678"
                              className="min-w-0 flex-1 rounded-r-2xl bg-transparent px-4 py-4 text-base outline-none placeholder:text-[#aa978d]"
                              aria-describedby={phoneError ? "campaign-phone-error" : undefined}
                            />
                          </div>
                          {phoneError && (
                            <p id="campaign-phone-error" className="mt-2 flex items-center gap-2 text-sm font-medium text-[#a41116]">
                              <X className="h-4 w-4" />
                              {phoneError}
                            </p>
                          )}

                          <Button
                            type="submit"
                            size="xl"
                            className="mt-5 w-full bg-[#761014] text-white hover:bg-[#5b090c]"
                          >
                            <MessageCircle className="mr-2 h-5 w-5" />
                            Resgatar cupão
                          </Button>

                          <p className="mt-4 flex items-start justify-center gap-2 text-center text-xs leading-5 text-[#816d61]">
                            <ShieldCheck className="mt-0.5 h-4 w-4 shrink-0" />
                            Ao continuar, aceitas receber esta recompensa através do WhatsApp.
                          </p>
                        </form>
                      ) : (
                        <div className="mt-8 rounded-2xl border border-[#b9d5bd] bg-[#edf8ef] p-5 text-center">
                          <Check className="mx-auto h-7 w-7 text-[#247336]" />
                          <h3 className="mt-3 text-xl font-bold text-[#174f25]">Cupão simulado criado</h3>
                          <p className="mt-2 text-sm leading-6 text-[#356440]">
                            Código <strong>{mockClaim.couponCode}</strong> preparado para o número terminado em {mockClaim.phone.slice(-3)}.
                          </p>
                          <p className="mt-2 text-xs text-[#52775a]">
                            Nesta primeira versão, nenhum cupão real será enviado.
                          </p>
                        </div>
                      )}

                      <div className="mt-7 rounded-2xl border border-[#ead8c3] bg-[#fff8ef] p-5">
                        <div className="flex items-center justify-between gap-4">
                          <div>
                            <p className="text-sm font-bold text-[#4b1113]">Os teus segredos</p>
                            <p className="mt-1 text-sm text-[#816d61]">{collection.length} de 5 descobertos</p>
                          </div>
                          <div className="flex gap-1.5">
                            {(["kibe", "carne", "salsicha", "queijo", "coxinha"] as CampaignType[]).map(
                              (item) => (
                                <span
                                  key={item}
                                  className={`h-3 w-3 rounded-full ${
                                    collection.includes(item) ? "bg-[#761014]" : "bg-[#ddcdbc]"
                                  }`}
                                />
                              ),
                            )}
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                )}
              </div>

              <p className="mt-5 text-center text-xs leading-5 text-white/52">
                Protótipo com dados simulados · QR {code}
              </p>
            </div>
          </div>
        </div>
      </section>
    </main>
  );
};

export default CampanhaUrbana;
