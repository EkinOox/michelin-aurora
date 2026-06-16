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
