# Base Debian (glibc) plutôt qu'Alpine (musl) : requis pour les navigateurs Playwright.
FROM node:22-bookworm-slim AS base
WORKDIR /app

FROM base AS deps
COPY package.json ./
RUN npm install

FROM base AS dev
COPY --from=deps /app/node_modules ./node_modules
# Navigateur Chromium + dépendances système pour les tests e2e Playwright.
RUN npx playwright install --with-deps chromium
COPY . .
EXPOSE 3000
CMD ["npm", "run", "dev"]

# ── Build de production : génère .output/ (serveur Nitro autonome) ─────────────
FROM base AS build
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# ── Image de production : node minimal, sans Playwright ───────────────────────
FROM node:22-bookworm-slim AS prod
WORKDIR /app
ENV NODE_ENV=production
ENV HOST=0.0.0.0
ENV PORT=3000
# .output est autonome (embarque ses propres node_modules runtime).
COPY --from=build /app/.output ./.output
EXPOSE 3000
CMD ["node", ".output/server/index.mjs"]
