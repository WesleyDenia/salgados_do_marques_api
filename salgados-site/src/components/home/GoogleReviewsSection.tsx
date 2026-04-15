import { useQuery } from "@tanstack/react-query";
import { ExternalLink, Quote, Star } from "lucide-react";
import { formatDistanceToNow } from "date-fns";
import { pt } from "date-fns/locale";
import { Button } from "@/components/ui/button";
import {
  fetchGoogleReviews,
  shouldFetchGoogleReviews,
  type GoogleReview,
} from "@/lib/google-reviews";
import fundoParalax from "@/assets/fundo_paralax.jpg";

function renderStars(rating: number) {
  return Array.from({ length: 5 }, (_, index) => (
    <Star
      key={`${rating}-${index}`}
      className={
        index < rating
          ? "h-4 w-4 fill-primary text-primary"
          : "h-4 w-4 text-primary/20"
      }
    />
  ));
}

function reviewTimeLabel(review: GoogleReview) {
  if (review.relative_time_description) {
    return review.relative_time_description;
  }

  if (!review.published_at) {
    return "";
  }

  return formatDistanceToNow(new Date(review.published_at), {
    addSuffix: true,
    locale: pt,
  });
}

function truncateReview(text: string) {
  if (text.length <= 180) {
    return text;
  }

  return `${text.slice(0, 177).trimEnd()}...`;
}

export function GoogleReviewsSection() {
  const { data } = useQuery({
    queryKey: ["google-reviews"],
    queryFn: fetchGoogleReviews,
    staleTime: 1000 * 60 * 60,
    retry: 1,
    enabled: shouldFetchGoogleReviews(),
  });

  const reviews = data?.data ?? [];

  if (reviews.length === 0) {
    return null;
  }

  const primaryMapsUrl =
    reviews.find((review) => review.google_maps_url)?.google_maps_url ??
    "https://maps.google.com/";

  return (
    <section
      className="section-padding"
      style={{
        backgroundAttachment: "fixed",
        backgroundImage: `url(${fundoParalax})`,
        backgroundPosition: "center",
        backgroundRepeat: "no-repeat",
        backgroundSize: "cover",
      }}
    >
      <div className="section-container">
        <div className="brand-panel overflow-hidden bg-card/70 p-8 md:p-10">
          <div className="mb-10 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div className="max-w-3xl space-y-4">
              <span className="highlight-badge">
                <Quote className="h-4 w-4" />
                Avaliações no Google Maps
              </span>

              <h2 className="heading-section text-foreground">
                O que os clientes dizem sobre a experiência
              </h2>

              <p className="text-lg text-muted-foreground">
                Algumas das avaliações partilhadas por clientes no Google Maps,
                destacando a qualidade dos produtos e do atendimento.
              </p>
            </div>

            <Button variant="outline" size="lg" asChild>
              <a
                href={primaryMapsUrl}
                target="_blank"
                rel="noopener noreferrer"
                className="flex items-center gap-2"
              >
                Ver mais no Google Maps
                <ExternalLink className="h-4 w-4" />
              </a>
            </Button>
          </div>

          <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
            {reviews.map((review, index) => (
              <article
                key={`${review.author_name}-${review.published_at ?? index}`}
                className="card-elevated flex h-full flex-col p-6 animate-fade-up"
                style={{ animationDelay: `${index * 0.08}s` }}
              >
                <div className="mb-4 flex items-center justify-between gap-3">
                  <div className="min-w-0">
                    <p className="truncate text-base font-semibold text-foreground">
                      {review.author_name}
                    </p>
                    <p className="text-sm text-muted-foreground">
                      {reviewTimeLabel(review)}
                    </p>
                  </div>

                  <div className="flex items-center gap-1">
                    {renderStars(review.rating)}
                  </div>
                </div>

                <p className="flex-1 text-sm leading-7 text-muted-foreground">
                  {truncateReview(review.text)}
                </p>

                <div className="mt-6 border-t border-border/70 pt-4">
                  <a
                    href={review.google_maps_url ?? primaryMapsUrl}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="inline-flex items-center gap-2 text-sm font-semibold text-primary transition-colors hover:text-primary/80"
                  >
                    Ver avaliação no Google Maps
                    <ExternalLink className="h-4 w-4" />
                  </a>
                </div>
              </article>
            ))}
          </div>

          <p className="mt-6 text-sm text-muted-foreground">
            Fonte: Google Maps. A seleção e a ordem das avaliações dependem da
            disponibilidade da plataforma.
          </p>
        </div>
      </div>
    </section>
  );
}
