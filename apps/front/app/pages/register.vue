<script setup lang="ts">
useSeoMeta({
  title: 'Créer un compte',
  description: 'Rejoignez Aurora by Michelin — la plateforme qui optimise votre pression de pneu, vous récompense à chaque sortie et vous connecte à la communauté cycliste.',
  ogTitle: 'Créer un compte — Aurora by Michelin',
  ogDescription: 'Inscrivez-vous gratuitement et transformez chaque sortie vélo avec la technologie Michelin.',
  ogImage: '/icons/icon-512.png',
  twitterCard: 'summary',
})

const router = useRouter()
const { register } = useAuth()

const email = ref('')
const password = ref('')
const name = ref('')
const city = ref('')
const loading = ref(false)
const error = ref('')

async function submit() {
  error.value = ''
  loading.value = true
  try {
    await register({ email: email.value, password: password.value, name: name.value, city: city.value })
    router.push('/onboarding')
  } catch (e: any) {
    error.value = e?.data?.error ?? 'Inscription impossible.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="screen reg-screen">

    <!-- ── Mobile : layout colonne unique original ── -->
    <div class="reg-mobile">
      <AppHeader title="Créer mon compte" sub="Quelques infos pour démarrer votre profil cycliste." :on-back="() => router.push('/')" />
      <div class="screen-scroll pad" style="padding-top: 8px">
        <form class="rise" style="display: flex; flex-direction: column; gap: 14px" @submit.prevent="submit">
          <label class="small" style="display: flex; flex-direction: column; gap: 6px">
            Nom <input v-model="name" type="text" required autocomplete="name" class="input" placeholder="Votre nom">
          </label>
          <label class="small" style="display: flex; flex-direction: column; gap: 6px">
            Ville <input v-model="city" type="text" autocomplete="address-level2" class="input" placeholder="Clermont-Ferrand">
          </label>
          <label class="small" style="display: flex; flex-direction: column; gap: 6px">
            Email <input v-model="email" type="email" required autocomplete="email" class="input" placeholder="vous@exemple.com">
          </label>
          <label class="small" style="display: flex; flex-direction: column; gap: 6px">
            Mot de passe <input v-model="password" type="password" required minlength="8" autocomplete="new-password" class="input" placeholder="8 caractères minimum">
          </label>
          <div v-if="error" class="small" style="color: var(--red)">{{ error }}</div>
          <button class="btn btn-blue btn-block" type="submit" :disabled="loading">
            {{ loading ? 'Création…' : 'Créer mon profil cycliste' }} <Icon v-if="!loading" name="arrow" :size="20" />
          </button>
          <NuxtLink to="/login" class="small" style="text-align: center; color: var(--ink-3)">
            Déjà un compte ? <b style="color: var(--blue)">Se connecter</b>
          </NuxtLink>
        </form>
      </div>
    </div>

    <!-- ── Desktop : split brand / form ── -->
    <div class="reg-desktop">

      <!-- Panneau gauche : brand (accent lime) -->
      <div class="rb-panel">
        <div class="rb-top rise">
          <NuxtLink to="/" class="rb-back">
            <Icon name="chevL" :size="18" color="rgba(255,255,255,.7)" />
          </NuxtLink>
          <MichelinLogo :height="28" class="rb-logo" />
        </div>

        <div class="rb-center rise d2">
          <div class="rb-step">Étape 1 / 2</div>
          <div class="rb-wordmark">
            AUR<span class="rb-o">O</span>RA
          </div>
          <div class="eyebrow rb-sub">by Michelin · 2 Wheels</div>
          <p class="rb-tagline">
            Créez votre profil cycliste<br>et accédez à l'intelligence<br>Michelin sur chaque route.
          </p>

          <div class="rb-features">
            <div class="rb-feat">
              <div class="rb-feat-icon"><Icon name="gauge" :size="16" color="var(--yellow)" /></div>
              <span>Pression dynamique personnalisée</span>
            </div>
            <div class="rb-feat">
              <div class="rb-feat-icon"><Icon name="route" :size="16" color="var(--yellow)" /></div>
              <span>Itinéraires sur-mesure</span>
            </div>
            <div class="rb-feat">
              <div class="rb-feat-icon"><Icon name="gift" :size="16" color="var(--yellow)" /></div>
              <span>Récompenses à chaque kilomètre</span>
            </div>
          </div>
        </div>

        <div class="rb-bottom">
          <div class="rb-copy">© 2025 Michelin LB 2 Wheels</div>
        </div>

        <div class="rb-deco rb-deco-1" aria-hidden="true" />
        <div class="rb-deco rb-deco-2" aria-hidden="true" />
      </div>

      <!-- Panneau droit : formulaire -->
      <div class="rf-panel">
        <div class="rf-inner rise">
          <div class="rf-header">
            <div class="rf-title">Créer mon compte</div>
            <div class="small rf-sub">Quelques infos pour démarrer votre profil cycliste.</div>
          </div>

          <form style="display: flex; flex-direction: column; gap: 16px" @submit.prevent="submit">
            <div class="rf-row">
              <label class="small rf-label">
                Nom
                <input v-model="name" type="text" required autocomplete="name" class="input rf-input" placeholder="Votre nom">
              </label>
              <label class="small rf-label">
                Ville
                <input v-model="city" type="text" autocomplete="address-level2" class="input rf-input" placeholder="Clermont-Ferrand">
              </label>
            </div>
            <label class="small rf-label">
              Email
              <input v-model="email" type="email" required autocomplete="email" class="input rf-input" placeholder="vous@exemple.com">
            </label>
            <label class="small rf-label">
              Mot de passe
              <input v-model="password" type="password" required minlength="8" autocomplete="new-password" class="input rf-input" placeholder="8 caractères minimum">
            </label>

            <div v-if="error" class="small" style="color: var(--red); margin-top: -4px">{{ error }}</div>

            <button class="btn btn-blue btn-block rf-submit" type="submit" :disabled="loading">
              {{ loading ? 'Création…' : 'Créer mon profil cycliste' }}
              <Icon v-if="!loading" name="arrow" :size="20" />
            </button>
          </form>

          <div class="lf-divider">
            <span class="tiny">déjà inscrit ?</span>
          </div>

          <NuxtLink to="/login" class="btn btn-ghost btn-block rf-login">
            Se connecter
          </NuxtLink>

          <div class="tiny rf-legal">
            En créant votre compte, vous acceptez nos <b>conditions d'utilisation</b> et notre <b>politique de confidentialité</b>.
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<style scoped>
.reg-screen { min-height: 100vh; background: radial-gradient(140% 80% at 50% -10%, #D8E4F5 0%, #EEF2FA 40%, #E8EDF8 100%); }
.reg-mobile  { display: flex; flex-direction: column; min-height: 100vh; }
.reg-desktop { display: none; }

@media (min-width: 900px) {
  .reg-mobile  { display: none; }
  .reg-desktop {
    display: grid;
    grid-template-columns: 420px 1fr;
    height: 100vh;
    overflow: hidden;
  }

  /* ── Panneau gauche ── */
  .rb-panel {
    position: relative;
    display: flex;
    flex-direction: column;
    background:
      radial-gradient(120% 80% at 30% 10%, #2a5db0 0%, #00205B 45%, #000C34 100%);
    overflow: hidden;
    padding: 0 40px;
  }

  .rb-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: clamp(28px, 4vh, 48px);
    flex-shrink: 0;
    position: relative; z-index: 1;
  }

  .rb-back {
    display: flex; align-items: center; justify-content: center;
    width: 38px; height: 38px; border-radius: 50%;
    background: rgba(255,255,255,.10); border: 1px solid rgba(255,255,255,.18);
    transition: background .2s;
  }
  .rb-back:hover { background: rgba(255,255,255,.18); }
  .rb-logo { filter: brightness(0) invert(1); opacity: .85; }

  .rb-center {
    flex: 1; display: flex; flex-direction: column;
    justify-content: center; position: relative; z-index: 1;
  }

  .rb-step {
    font-family: var(--mono); font-size: 11px; font-weight: 600;
    letter-spacing: .18em; text-transform: uppercase;
    color: var(--yellow); margin-bottom: 16px;
  }

  .rb-wordmark {
    font-size: 44px; font-weight: 900;
    letter-spacing: -.03em; color: #fff; line-height: 1;
  }
  .rb-o { color: var(--yellow); -webkit-text-stroke: .6px #c4b000; }
  .rb-sub { margin-top: 8px; color: rgba(255,255,255,.5); }

  .rb-tagline {
    margin-top: 24px; font-size: 17px; line-height: 1.65;
    color: rgba(255,255,255,.72); font-weight: 400;
  }

  .rb-features {
    margin-top: 28px;
    display: flex; flex-direction: column; gap: 14px;
  }
  .rb-feat {
    display: flex; align-items: center; gap: 12px;
    font-size: 13px; color: rgba(255,255,255,.7); font-weight: 500;
  }
  .rb-feat-icon {
    width: 32px; height: 32px; border-radius: 9px; flex-shrink: 0;
    background: rgba(252,229,0,.12); border: 1px solid rgba(252,229,0,.22);
    display: flex; align-items: center; justify-content: center;
  }

  .rb-bottom {
    padding-bottom: clamp(28px, 4vh, 48px);
    flex-shrink: 0; position: relative; z-index: 1;
  }
  .rb-copy { font-size: 11px; color: rgba(255,255,255,.28); font-weight: 500; }

  .rb-deco {
    position: absolute; border-radius: 50%; pointer-events: none;
  }
  .rb-deco-1 {
    width: 420px; height: 420px;
    border: 1px solid rgba(255,255,255,.07);
    bottom: -140px; right: -160px;
  }
  .rb-deco-2 {
    width: 260px; height: 260px;
    border: 1px solid rgba(252,229,0,.10);
    bottom: -40px; right: -60px;
  }

  /* ── Panneau droit ── */
  .rf-panel {
    display: flex; align-items: center; justify-content: center;
    background: #fff; padding: 40px; overflow-y: auto;
  }

  .rf-inner { width: 100%; max-width: 440px; }
  .rf-header { margin-bottom: 32px; }
  .rf-title {
    font-size: 28px; font-weight: 800;
    letter-spacing: -.02em; color: var(--ink); line-height: 1;
  }
  .rf-sub { margin-top: 8px; color: var(--ink-3); }

  /* Nom + Ville côte à côte */
  .rf-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

  .rf-label {
    display: flex; flex-direction: column; gap: 7px;
    font-weight: 600; color: var(--ink-2);
  }
  .rf-input { height: 52px; font-size: 15px; border-radius: 14px; }
  .rf-submit { height: 56px; font-size: 16px; margin-top: 4px; border-radius: 14px; }

  .lf-divider {
    display: flex; align-items: center; gap: 12px;
    margin: 20px 0; color: var(--mute);
  }
  .lf-divider::before, .lf-divider::after {
    content: ''; flex: 1; height: 1px; background: var(--line);
  }

  .rf-login { height: 52px; font-size: 15px; border-radius: 14px; color: var(--ink-2); }

  .rf-legal {
    margin-top: 20px; text-align: center;
    color: var(--mute); line-height: 1.6;
  }
}

@media (display-mode: standalone) {
  .reg-mobile  { display: flex; }
  .reg-desktop { display: none; }
}
</style>
