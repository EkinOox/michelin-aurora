<script setup lang="ts">
definePageMeta({ tabbar: false })

const route  = useRoute()
const router = useRouter()
const retailerSheet = useRetailerSheet()

const wheel    = computed(() => (route.query.wheel as string) ?? 'arrière')
const pressure = computed(() => parseFloat((route.query.pressure as string) ?? '0'))

function fmt(n: number) { return n.toFixed(2).replace('.', ',') }
</script>

<template>
  <div class="alert-screen">
    <!-- Fond pulsé -->
    <div class="pulse-ring r1" />
    <div class="pulse-ring r2" />
    <div class="pulse-ring r3" />

    <div class="content">
      <!-- Icône centrale -->
      <div class="alert-icon-wrap">
        <svg width="52" height="52" viewBox="0 0 52 52" fill="none">
          <path d="M26 8C16.06 8 8 16.06 8 26C8 35.94 16.06 44 26 44C35.94 44 44 35.94 44 26C44 16.06 35.94 8 26 8Z" fill="rgba(200,16,46,0.2)" stroke="#FF4560" stroke-width="2"/>
          <path d="M26 16V28" stroke="#FF4560" stroke-width="3" stroke-linecap="round"/>
          <circle cx="26" cy="35" r="2" fill="#FF4560"/>
        </svg>
      </div>

      <!-- Titre -->
      <div class="alert-label">Alerte pneu</div>
      <div class="alert-title">Crevaison détectée</div>
      <div class="alert-sub">Roue <strong>{{ wheel }}</strong></div>

      <!-- Pression actuelle -->
      <div class="pressure-card">
        <div class="pressure-row">
          <span class="pressure-label">Pression actuelle</span>
          <span class="pressure-value">{{ fmt(pressure) }} <span class="pressure-unit">bar</span></span>
        </div>
        <div class="pressure-bar-track">
          <div
            class="pressure-bar-fill"
            :style="{ width: `${Math.min(100, (pressure / 9) * 100)}%` }"
          />
        </div>
        <div class="pressure-hint">Seuil critique atteint — pneu à risque</div>
      </div>

      <!-- Consignes de sécurité -->
      <div class="instructions">
        <div class="instruction-item">
          <span class="step">1</span>
          <span>Ralentissez <strong>progressivement</strong> sans freiner brusquement</span>
        </div>
        <div class="instruction-item">
          <span class="step">2</span>
          <span>Rejoignez le <strong>bord de la route</strong> en toute sécurité</span>
        </div>
        <div class="instruction-item">
          <span class="step">3</span>
          <span>Coupez le moteur et allumez vos <strong>feux de détresse</strong></span>
        </div>
      </div>

      <!-- CTA -->
      <div class="cta-stack">
        <button
          class="btn-retailer"
          @click="retailerSheet.open({ name: 'Réparation · revendeur Michelin' })"
        >
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
          </svg>
          Trouver un revendeur Michelin
        </button>
        <button class="btn-back" @click="router.push('/ride')">
          Continuer la session
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.alert-screen {
  position: fixed;
  inset: 0;
  background: #0D0608;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  z-index: 500;
}

/* ── Anneaux pulsés ── */
.pulse-ring {
  position: absolute;
  border-radius: 50%;
  border: 1.5px solid rgba(200, 16, 46, 0.25);
  animation: pulse 3s ease-out infinite;
}
.r1 { width: 320px; height: 320px; animation-delay: 0s; }
.r2 { width: 520px; height: 520px; animation-delay: .8s; }
.r3 { width: 720px; height: 720px; animation-delay: 1.6s; }

@keyframes pulse {
  0%   { transform: scale(.85); opacity: .7; }
  100% { transform: scale(1.1); opacity: 0; }
}

/* ── Contenu ── */
.content {
  position: relative;
  width: 100%;
  max-width: 420px;
  padding: 0 24px 48px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.alert-icon-wrap {
  width: 96px;
  height: 96px;
  border-radius: 50%;
  background: rgba(200, 16, 46, 0.12);
  border: 1.5px solid rgba(200, 16, 46, 0.35);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 20px;
  animation: icon-beat 1.6s ease-in-out infinite;
}
@keyframes icon-beat {
  0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(200,16,46,.4); }
  50%       { transform: scale(1.04); box-shadow: 0 0 0 16px rgba(200,16,46,0); }
}

.alert-label {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: .1em;
  text-transform: uppercase;
  color: #FF4560;
  margin-bottom: 6px;
}
.alert-title {
  font-size: 34px;
  font-weight: 900;
  letter-spacing: -.03em;
  color: #fff;
  line-height: 1;
  margin-bottom: 8px;
}
.alert-sub {
  font-size: 15px;
  color: rgba(255,255,255,.55);
  margin-bottom: 28px;
}
.alert-sub strong { color: rgba(255,255,255,.85); }

/* ── Carte pression ── */
.pressure-card {
  width: 100%;
  background: rgba(200,16,46,.1);
  border: 1px solid rgba(200,16,46,.3);
  border-radius: 18px;
  padding: 16px 18px;
  margin-bottom: 24px;
  text-align: left;
}
.pressure-row {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  margin-bottom: 10px;
}
.pressure-label { font-size: 12px; color: rgba(255,255,255,.5); }
.pressure-value { font-size: 28px; font-weight: 900; color: #FF4560; letter-spacing: -.03em; }
.pressure-unit  { font-size: 14px; font-weight: 500; }
.pressure-bar-track {
  height: 5px;
  background: rgba(255,255,255,.1);
  border-radius: 99px;
  overflow: hidden;
  margin-bottom: 8px;
}
.pressure-bar-fill {
  height: 100%;
  background: #FF4560;
  border-radius: 99px;
  transition: width .4s ease;
}
.pressure-hint { font-size: 11px; color: rgba(255,70,96,.75); }

/* ── Instructions ── */
.instructions {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-bottom: 32px;
  text-align: left;
}
.instruction-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  font-size: 13px;
  color: rgba(255,255,255,.65);
  line-height: 1.4;
}
.instruction-item strong { color: rgba(255,255,255,.9); }
.step {
  flex-shrink: 0;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: rgba(255,255,255,.08);
  border: 1px solid rgba(255,255,255,.15);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 700;
  color: rgba(255,255,255,.6);
}

/* ── Boutons ── */
.cta-stack {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.btn-retailer {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  width: 100%;
  height: 52px;
  border-radius: 14px;
  background: #C8102E;
  color: #fff;
  font-size: 15px;
  font-weight: 700;
  border: none;
  cursor: pointer;
  transition: opacity .15s;
}
.btn-retailer:active { opacity: .85; }
.btn-back {
  width: 100%;
  height: 46px;
  border-radius: 14px;
  background: transparent;
  border: 1px solid rgba(255,255,255,.12);
  color: rgba(255,255,255,.4);
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: opacity .15s;
}
.btn-back:active { opacity: .7; }
</style>
