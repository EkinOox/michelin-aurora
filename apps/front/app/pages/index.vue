<script setup lang="ts">
definePageMeta({ layout: 'default' })

useSeoMeta({
  title: 'Aurora — Cycling Intelligence Platform',
  description: 'La plateforme Michelin qui optimise votre pression de pneu en temps réel selon la météo, le terrain et votre profil cycliste. Téléchargez la PWA gratuite.',
  ogTitle: 'Aurora — Cycling Intelligence Platform',
  ogDescription: 'Pression dynamique, télémétrie live, itinéraires Michelin et récompenses. L\'app cycliste qui transforme chaque sortie.',
  ogImage: '/icons/icon-512.png',
  twitterCard: 'summary',
})

// Chargement différé de Three.js — ne bloque pas le premier paint
const TireSceneLazy = defineAsyncComponent(() => import('~/components/TireScene.vue'))
</script>

<template>
  <div class="screen lp-screen">
    <!-- ── Mobile : colonne unique ── -->
    <div class="screen-scroll lp-mobile">

      <!-- Header -->
      <div class="pad lp-m-header safe-top rise">
        <div class="between">
          <div class="lp-m-brand">
            <div class="lp-m-wordmark">AUR<span class="lp-m-o">O</span>RA</div>
            <div class="eyebrow lp-m-sub">by Michelin · 2 Wheels</div>
          </div>
          <MichelinLogo :height="26" />
        </div>
      </div>

      <!-- Scène 3D -->
      <div class="lp-m-scene rise d1">
        <TireSceneLazy />
      </div>

      <!-- Texte -->
      <div class="pad rise d2" style="margin-top: 8px">
        <div class="lp-m-eyebrow">Cycling Intelligence Platform</div>
        <div class="lp-m-headline">
          La liaison au sol,<br>devenue intelligence.
        </div>
        <p class="lp-m-body">
          Itinéraires sur-mesure, pression dynamique, télémétrie temps réel et récompenses.
        </p>
      </div>

      <!-- CTAs -->
      <div class="pad rise d3 lp-m-ctas">
        <NuxtLink to="/register" class="btn btn-blue btn-block lp-m-btn">
          Créer mon profil cycliste <Icon name="arrow" :size="20" />
        </NuxtLink>
        <NuxtLink to="/login" class="btn btn-tertiary btn-block lp-m-btn">
          J'ai déjà un compte
        </NuxtLink>
        <div class="lp-m-footer-note">
          Michelin LB 2 Wheels · Route · Gravel · VTT · VAE
        </div>
      </div>

    </div>

    <!-- ── Desktop : grille deux colonnes ── -->
    <div class="lp-desktop" aria-hidden="false">
      <!-- Colonne gauche -->
      <div class="lp-left">
        <!-- Header -->
        <header class="lp-header rise">
          <div class="between">
            <div style="line-height: 1">
              <div class="lp-wordmark">
                AUR<span class="lp-o">O</span>RA
              </div>
              <div class="eyebrow lp-sub">by Michelin · 2 Wheels</div>
            </div>
            <MichelinLogo :height="28" />
          </div>
        </header>

        <!-- Copy + CTAs groupés au centre -->
        <div class="lp-body-group rise d2">
          <div class="eyebrow lp-eyebrow">Cycling Intelligence Platform</div>
          <h1 class="lp-headline">
            La liaison au sol,<br>devenue intelligence.
          </h1>
          <p class="lp-desc">
            Itinéraires sur-mesure, pression dynamique,<br>
            télémétrie temps réel et récompenses à chaque kilomètre.
          </p>

          <div class="lp-ctas">
            <NuxtLink to="/register" class="btn btn-blue lp-btn">
              Créer mon profil cycliste <Icon name="arrow" :size="20" />
            </NuxtLink>
            <NuxtLink to="/login" class="btn btn-tertiary lp-btn">
              J'ai déjà un compte
            </NuxtLink>
          </div>
        </div>

        <!-- Footer -->
        <footer class="lp-footer tiny">
          Michelin LB 2 Wheels · Route · Gravel · VTT · VAE
        </footer>
      </div>

      <!-- Colonne droite : roue 3D -->
      <div class="lp-right">
        <!-- Cercles décoratifs (visibles même avant chargement Three.js) -->
        <div class="lp-ring lp-ring-1" aria-hidden="true" />
        <div class="lp-ring lp-ring-2" aria-hidden="true" />
        <div class="lp-ring lp-ring-3" aria-hidden="true" />
        <div class="lp-ring lp-ring-4" aria-hidden="true" />
        <!-- Wrapper absolu pour que le canvas Three.js hérite de 100% width/height -->
        <div class="lp-tire-wrap">
          <TireSceneLazy />
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* ── Fond global ── */
.lp-screen {
  background: radial-gradient(140% 70% at 50% -5%, #D8E4F5 0%, #EEF2FA 45%, #E4EBF7 100%);
  min-height: 100vh;
  /* empêche le scroll horizontal sur mobile */
  overflow-x: hidden;
  max-width: 100vw;
}
@media (min-width: 900px) {
  .lp-screen { height: 100vh; overflow: hidden; }
}

/* ── Mobile : visible, desktop : masqué ── */
.lp-mobile  { display: flex; flex-direction: column; overflow-x: hidden; width: 100%; }
.lp-desktop { display: none; }

/* ── Styles mobile ── */
.lp-m-header { padding-top: 52px; }

.lp-m-wordmark {
  font-size: 32px; font-weight: 900; letter-spacing: -.03em;
  color: var(--ink); line-height: 1;
}
.lp-m-o { color: var(--yellow); -webkit-text-stroke: .6px #d9c400; }
.lp-m-sub { margin-top: 5px; color: var(--ink-3); }

.lp-m-scene {
  height: 320px;
  margin-top: 4px;
  position: relative;
}

.lp-m-eyebrow {
  display: flex; align-items: center; gap: 10px;
  color: var(--lime-600);
  font-family: var(--mono); font-size: 11px; font-weight: 600;
  letter-spacing: .18em; text-transform: uppercase;
}
.lp-m-eyebrow::before {
  content: ''; display: block; width: 22px; height: 2px;
  background: var(--lime-600); border-radius: 2px; flex-shrink: 0;
}

.lp-m-headline {
  margin: 12px 0 0;
  font-size: clamp(30px, 8vw, 40px);
  line-height: .97; font-weight: 900; letter-spacing: -.025em;
  color: var(--ink);
}

.lp-m-body {
  margin: 14px 0 0;
  font-size: 15px; line-height: 1.6;
  color: var(--ink-2); font-weight: 400;
}

.lp-m-ctas {
  margin-top: 24px;
  display: flex; flex-direction: column; gap: 12px;
  padding-bottom: max(40px, env(safe-area-inset-bottom, 40px));
}
.lp-m-btn { height: 56px; font-size: 16px; }

.lp-m-footer-note {
  font-size: 11px;
  text-align: center;
  color: var(--mute);
  margin-top: 6px;
  white-space: normal;
  overflow-wrap: break-word;
  word-break: break-word;
  padding: 0 4px;
}

/* ── Desktop ≥ 900px ── */
@media (min-width: 900px) {
  .lp-mobile  { display: none; }
  .lp-desktop {
    display: grid;
    grid-template-columns: 1fr 1fr;
    /* height (pas min-height) pour que les grid items puissent hériter 100% */
    height: 100vh;
  }

  /* Colonne gauche : flex column, espace entre header / content / footer */
  .lp-left {
    display: flex;
    flex-direction: column;
    height: 100%;
    padding: 0 clamp(40px, 6vw, 80px);
    overflow: hidden;
    background: radial-gradient(130% 70% at 20% 110%, rgba(39,80,155,.09) 0%, transparent 55%),
                radial-gradient(100% 40% at 80% 0%, #EEF4FF 0%, transparent 60%),
                #F8FAFF;
  }

  .lp-header {
    padding-top: clamp(32px, 5vh, 56px);
    padding-bottom: 0;
    flex-shrink: 0;
  }

  .lp-wordmark {
    font-size: 32px;
    font-weight: 900;
    letter-spacing: -.03em;
    color: var(--ink);
    line-height: 1;
  }

  .lp-o {
    color: var(--yellow);
    -webkit-text-stroke: .6px #d9c400;
  }

  .lp-sub {
    margin-top: 6px;
    color: var(--ink-3);
  }

  /* Copy + CTAs centrés verticalement */
  .lp-body-group {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding-top: 40px;
    padding-bottom: 40px;
  }

  .lp-eyebrow {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--lime-600);
    font-family: var(--mono);
    font-size: 11px;
    font-weight: 600;
    letter-spacing: .18em;
    text-transform: uppercase;
  }
  .lp-eyebrow::before {
    content: '';
    display: block;
    width: 28px;
    height: 2px;
    background: var(--lime-600);
    border-radius: 2px;
    flex-shrink: 0;
  }

  .lp-headline {
    margin: 16px 0 0;
    font-size: clamp(42px, 5vw, 66px);
    line-height: .98;
    font-weight: 900;
    letter-spacing: -.03em;
    color: var(--ink);
    max-width: 480px;
  }

  .lp-desc {
    margin: 22px 0 0;
    font-size: 17px;
    line-height: 1.65;
    color: var(--ink-2);
    font-weight: 400;
    max-width: 400px;
  }

  .lp-ctas {
    margin-top: 40px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    max-width: 340px;
  }

  .lp-btn {
    width: 100%;
    height: 56px;
    font-size: 16px;
  }

  /* Footer fixé en bas */
  .lp-footer {
    flex-shrink: 0;
    padding-bottom: clamp(24px, 4vh, 48px);
    color: var(--mute);
    text-align: left;
  }

  /* Colonne droite : roue 3D plein écran */
  .lp-right {
    position: relative;
    background:
      radial-gradient(70% 60% at 50% 48%, #C8D8F0 0%, #D8E8F8 40%, #B8CEEC 100%);
    overflow: hidden;
  }

  /* Anneaux décoratifs qui évoquent une roue de vélo */
  .lp-ring {
    position: absolute;
    border-radius: 50%;
    border: 1px solid rgba(0,0,0,.06);
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    pointer-events: none;
  }
  .lp-ring-1 { width: 520px; height: 520px; border-color: rgba(0,0,0,.07); animation: lp-spin 60s linear infinite; }
  .lp-ring-2 { width: 380px; height: 380px; border-color: rgba(39,80,155,.10); border-width: 2px; animation: lp-spin 45s linear infinite reverse; }
  .lp-ring-3 { width: 240px; height: 240px; border-color: rgba(0,0,0,.06); animation: lp-spin 30s linear infinite; }
  .lp-ring-4 { width: 100px; height: 100px; border-color: rgba(39,80,155,.14); border-width: 3px; }

  @keyframes lp-spin {
    to { transform: translate(-50%, -50%) rotate(360deg); }
  }

  /* Wrapper absolu : le canvas Three.js hérite de width/height correctement */
  .lp-tire-wrap {
    position: absolute;
    inset: 0;
    z-index: 1;
  }
}

/* ── PWA standalone : forcer le layout mobile même sur grand écran ── */
@media (display-mode: standalone) {
  .lp-mobile  { display: flex; }
  .lp-desktop { display: none; }
}
</style>
