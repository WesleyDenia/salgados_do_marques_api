import { defineConfig } from "vite";
import react from "@vitejs/plugin-react-swc";
import path from "path";
import { componentTagger } from "lovable-tagger";
import { createRequire } from "module";

const require = createRequire(import.meta.url);
const prerender = require("vite-plugin-prerender");
const prerenderPlugin = prerender.default ?? prerender;
const jsdomRendererModule = require("@prerenderer/renderer-jsdom");
const JSDOMRenderer = jsdomRendererModule.default ?? jsdomRendererModule;

// https://vitejs.dev/config/
export default defineConfig(({ mode }) => ({
  build: {
    outDir: "dist",
    emptyOutDir: true,
  },
  server: {
    host: "::",
    port: 8080,
    hmr: {
      overlay: false,
    },
  },
  plugins: [
    react(),
    mode === "development" && componentTagger(),
    mode === "production" &&
      prerenderPlugin({
        staticDir: path.resolve(__dirname, "dist"),
        routes: ["/", "/festas", "/produtos", "/sobre", "/contactos", "/termos", "/privacidade"],
        renderer: new JSDOMRenderer(),
      }),
  ].filter(Boolean),
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "./src"),
    },
  },
}));
