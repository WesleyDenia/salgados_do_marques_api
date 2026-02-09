import { Header } from "@/components/Header";
import { Footer } from "@/components/Footer";
import { HeroSection } from "@/components/home/HeroSection";
import { EmergencySupportSection } from "@/components/home/EmergencySupportSection";
import { CTASection } from "@/components/home/CTASection";

const Index = () => {
  return (
    <div className="min-h-screen bg-background">
      <Header />
      <main>
        <HeroSection />
        <EmergencySupportSection />
        <CTASection />
      </main>
      <Footer />
    </div>
  );
};

export default Index;
