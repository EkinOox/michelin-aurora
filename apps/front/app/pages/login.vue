<script setup lang="ts">
useSeoMeta({
  title: 'Connexion',
  description: 'Connectez-vous à Aurora by Michelin pour accéder à votre plateforme cycliste personnalisée.',
  robots: 'noindex',
})

const router = useRouter()
const { login } = useAuth()

const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref('')

async function submit() {
  error.value = ''
  loading.value = true
  try {
    await login({ email: email.value, password: password.value })
    router.push('/home')
  } catch (e: any) {
    error.value = e?.data?.error ?? 'Identifiants invalides.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="screen login-screen">

    <!-- ── Mobile : layout colonne unique original ── -->
    <div class="login-mobile">
      <AppHeader title="Connexion" sub="Accédez à votre plateforme Aurora." :on-back="() => router.push('/')" />
      <div class="screen-scroll pad" style="padding-top: 8px">
        <form class="rise" style="display: flex; flex-direction: column; gap: 14px" @submit.prevent="submit">
          <label class="small" style="display: flex; flex-direction: column; gap: 6px">
            Email
            <input v-model="email" type="email" required autocomplete="email" class="input" placeholder="vous@exemple.com">
          </label>
          <label class="small" style="display: flex; flex-direction: column; gap: 6px">
            Mot de passe
            <input v-model="password" type="password" required autocomplete="current-password" class="input" placeholder="••••••••">
          </label>
          <div v-if="error" class="small" style="color: var(--red)">{{ error }}</div>
          <button class="btn btn-blue btn-block" type="submit" :disabled="loading">
            {{ loading ? 'Connexion…' : 'Se connecter' }} <Icon v-if="!loading" name="arrow" :size="20" />
          </button>
          <NuxtLink to="/register" class="small" style="text-align: center; color: var(--ink-3)">
            Pas encore de compte ? <b style="color: var(--blue)">Créer mon profil</b>
          </NuxtLink>
        </form>
      </div>
    </div>

    <!-- ── Desktop : split brand / form ── -->
    <div class="login-desktop">

      <!-- Panneau gauche : brand -->
      <div class="login-brand">
        <div class="lb-top rise">
          <NuxtLink to="/" class="lb-back">
            <Icon name="chevL" :size="18" color="rgba(255,255,255,.7)" />
          </NuxtLink>
          <MichelinLogo :height="28" class="lb-logo" />
        </div>

        <div class="lb-center rise d2">
          <div class="lb-wordmark">
            AUR<span class="lb-o">O</span>RA
          </div>
          <div class="eyebrow lb-sub">by Michelin · 2 Wheels</div>
          <p class="lb-tagline">
            Itinéraires sur-mesure,<br>pression dynamique,<br>télémétrie temps réel.
          </p>
        </div>

        <div class="lb-bottom">
          <div class="lb-badges">
            <span class="lb-badge">Route</span>
            <span class="lb-badge">Gravel</span>
            <span class="lb-badge">VTT</span>
            <span class="lb-badge">VAE</span>
          </div>
          <div class="lb-copy">© 2025 Michelin LB 2 Wheels</div>
        </div>

        <!-- Cercles décoratifs -->
        <div class="lb-deco lb-deco-1" aria-hidden="true" />
        <div class="lb-deco lb-deco-2" aria-hidden="true" />
      </div>

      <!-- Panneau droit : formulaire -->
      <div class="login-form-panel">
        <div class="lf-inner rise">
          <div class="lf-header">
            <div class="lf-title">Connexion</div>
            <div class="small lf-sub">Accédez à votre plateforme Aurora.</div>
          </div>

          <form style="display: flex; flex-direction: column; gap: 18px" @submit.prevent="submit">
            <label class="small lf-label">
              Email
              <input v-model="email" type="email" required autocomplete="email" class="input lf-input" placeholder="vous@exemple.com">
            </label>
            <label class="small lf-label">
              Mot de passe
              <input v-model="password" type="password" required autocomplete="current-password" class="input lf-input" placeholder="••••••••">
            </label>

            <div v-if="error" class="small" style="color: var(--red); margin-top: -4px">{{ error }}</div>

            <button class="btn btn-blue btn-block lf-submit" type="submit" :disabled="loading">
              {{ loading ? 'Connexion…' : 'Se connecter' }}
              <Icon v-if="!loading" name="arrow" :size="20" />
            </button>
          </form>

          <div class="lf-divider">
            <span class="tiny">ou</span>
          </div>

          <NuxtLink to="/register" class="btn btn-ghost btn-block lf-register">
            Créer mon profil cycliste
          </NuxtLink>

          <div class="tiny lf-legal">
            En vous connectant, vous acceptez nos <b>conditions d'utilisation</b> et notre <b>politique de confidentialité</b>.
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<style scoped>
/* ── Commun ── */
.login-screen {
  min-height: 100vh;
  background: radial-gradient(140% 80% at 50% -10%, #D8E4F5 0%, #EEF2FA 40%, #E8EDF8 100%);
}

/* ── Mobile visible, desktop masqué ── */
.login-mobile  { display: flex; flex-direction: column; min-height: 100vh; }
.login-desktop { display: none; }

/* ── Desktop ≥ 900px ── */
@media (min-width: 900px) {
  .login-mobile  { display: none; }
  .login-desktop {
    display: grid;
    grid-template-columns: 420px 1fr;
    height: 100vh;
    overflow: hidden;
  }

  /* ── Panneau gauche brand ── */
  .login-brand {
    position: relative;
    display: flex;
    flex-direction: column;
    background:
      radial-gradient(120% 80% at 30% 10%, #2a5db0 0%, #00205B 45%, #000C34 100%);
    overflow: hidden;
    padding: 0 40px;
  }

  .lb-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: clamp(28px, 4vh, 48px);
    flex-shrink: 0;
    position: relative;
    z-index: 1;
  }

  .lb-back {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 38px; height: 38px;
    border-radius: 50%;
    background: rgba(255,255,255,.10);
    border: 1px solid rgba(255,255,255,.18);
    transition: background .2s;
  }
  .lb-back:hover { background: rgba(255,255,255,.18); }

  .lb-logo { filter: brightness(0) invert(1); opacity: .85; }

  .lb-center {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    position: relative;
    z-index: 1;
  }

  .lb-wordmark {
    font-size: 44px;
    font-weight: 900;
    letter-spacing: -.03em;
    color: #fff;
    line-height: 1;
  }

  .lb-o {
    color: var(--yellow);
    -webkit-text-stroke: .6px #c4b000;
  }

  .lb-sub {
    margin-top: 8px;
    color: rgba(255,255,255,.5);
  }

  .lb-tagline {
    margin-top: 28px;
    font-size: 18px;
    line-height: 1.65;
    color: rgba(255,255,255,.75);
    font-weight: 400;
  }

  .lb-bottom {
    padding-bottom: clamp(28px, 4vh, 48px);
    flex-shrink: 0;
    position: relative;
    z-index: 1;
  }

  .lb-badges {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 16px;
  }

  .lb-badge {
    display: inline-flex;
    align-items: center;
    height: 26px;
    padding: 0 12px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: .04em;
    background: rgba(255,255,255,.10);
    border: 1px solid rgba(255,255,255,.18);
    color: rgba(255,255,255,.7);
  }

  .lb-copy {
    font-size: 11px;
    color: rgba(255,255,255,.28);
    font-weight: 500;
  }

  /* Cercles décoratifs */
  .lb-deco {
    position: absolute;
    border-radius: 50%;
    pointer-events: none;
  }
  .lb-deco-1 {
    width: 420px; height: 420px;
    border: 1px solid rgba(255,255,255,.07);
    bottom: -140px; right: -160px;
  }
  .lb-deco-2 {
    width: 260px; height: 260px;
    border: 1px solid rgba(252,229,0,.10);
    bottom: -40px; right: -60px;
  }

  /* ── Panneau droit formulaire ── */
  .login-form-panel {
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    padding: 40px;
    overflow-y: auto;
  }

  .lf-inner {
    width: 100%;
    max-width: 400px;
  }

  .lf-header { margin-bottom: 36px; }

  .lf-title {
    font-size: 28px;
    font-weight: 800;
    letter-spacing: -.02em;
    color: var(--ink);
    line-height: 1;
  }

  .lf-sub {
    margin-top: 8px;
    color: var(--ink-3);
  }

  .lf-label {
    display: flex;
    flex-direction: column;
    gap: 7px;
    font-weight: 600;
    color: var(--ink-2);
  }

  .lf-input {
    height: 52px;
    font-size: 15px;
    border-radius: 14px;
  }

  .lf-submit {
    height: 56px;
    font-size: 16px;
    margin-top: 4px;
    border-radius: 14px;
  }

  .lf-divider {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 20px 0;
    color: var(--mute);
  }
  .lf-divider::before,
  .lf-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--line);
  }

  .lf-register {
    height: 52px;
    font-size: 15px;
    border-radius: 14px;
    color: var(--ink-2);
  }

  .lf-legal {
    margin-top: 20px;
    text-align: center;
    color: var(--mute);
    line-height: 1.6;
  }
}

/* ── PWA standalone : toujours mobile ── */
@media (display-mode: standalone) {
  .login-mobile  { display: flex; }
  .login-desktop { display: none; }
}
</style>
