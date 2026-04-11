import { ArrowRight, BriefcaseBusiness, Store } from "lucide-react";
import { Link } from "react-router-dom";
import { Button } from "@/components/ui/button";

export function PartnersTeaserSection() {
  return (
    <section className="section-padding">
      <div className="section-container">
        <div className="brand-panel overflow-hidden p-8 md:p-10">
          <div className="grid gap-8 lg:grid-cols-[1fr_auto] lg:items-center">
            <div className="space-y-5">
              <span className="highlight-badge">
                <BriefcaseBusiness className="h-4 w-4" />
                Frente B2B
              </span>
              <h2 className="heading-section text-foreground">
                Também procuramos parceiros para festas, eventos e revenda
              </h2>
              <p className="max-w-3xl text-lg text-muted-foreground">
                Casas de festas, organizadores de eventos e negócios que precisem de oferta regular
                podem conhecer a proposta comercial numa página própria, sem competir com a proposta
                principal da home.
              </p>
              <div className="flex flex-wrap gap-3 text-sm text-muted-foreground">
                <span className="inline-flex items-center gap-2 rounded-full bg-secondary px-4 py-2">
                  <Store className="h-4 w-4 text-primary" />
                  Casas de festas
                </span>
                <span className="inline-flex items-center gap-2 rounded-full bg-secondary px-4 py-2">
                  <Store className="h-4 w-4 text-primary" />
                  Eventos corporativos
                </span>
                <span className="inline-flex items-center gap-2 rounded-full bg-secondary px-4 py-2">
                  <Store className="h-4 w-4 text-primary" />
                  Revenda selecionada
                </span>
              </div>
            </div>

            <div className="flex flex-col gap-3">
              <Button variant="outline" size="lg" asChild>
                <Link to="/parceiros" className="flex items-center gap-2">
                  Seja um parceiro
                  <ArrowRight className="h-4 w-4" />
                </Link>
              </Button>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
