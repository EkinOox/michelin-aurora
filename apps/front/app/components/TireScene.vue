<script setup lang="ts">
import * as THREE from 'three'

const PRODUCTS = [
  { name: 'Power Road',       cat: 'Route',  dot: '#FCE500', text: '#0a0a0a', desc: 'Asphalte · Aéro · Racing' },
  { name: 'Power Gravel TLR', cat: 'Gravel', dot: '#84BD00', text: '#0a0a0a', desc: 'Mixte · TLR · Polyvalent' },
  { name: 'Wild Enduro',      cat: 'VTT',    dot: '#d1d5db', text: '#111',    desc: 'Single Track · Grip Total' },
  { name: 'E-Wild',           cat: 'VAE',    dot: '#27509B', text: '#fff',    desc: 'Assistance · Tout-terrain' },
]

const activeIdx = ref(0)
const labelKey  = ref(0)

const SHOW_S  = 5.5
const CROSS_S = 0.55

// ─────────────────────────────────────────────
// Helpers géométrie
// ─────────────────────────────────────────────

function std(hex: number, rough: number, metal: number, em = 0, ei = 0) {
  return new THREE.MeshStandardMaterial({ color: hex, roughness: rough, metalness: metal, emissive: em, emissiveIntensity: ei })
}

/** Hub complet : corps + brides + trous de rayon + bouchons + alésage */
function buildHub(T: typeof THREE, S: number, bodyHex: number, accentHex: number) {
  const g = new T.Group()
  const silver  = std(0xcdd2cc, 0.18, 0.96)
  const body    = std(bodyHex, 0.25, 0.68)
  const darkHex = Math.max(0, bodyHex - 0x0c0c0c)

  // Corps principal (légèrement bombé)
  ;[
    { r1: 0.148, r2: 0.128, h: 0.44 },
    { r1: 0.168, r2: 0.148, h: 0.08 },  // épaulement avant/arrière
  ].forEach(({ r1, r2, h }, i) => {
    const m = new T.Mesh(new T.CylinderGeometry(r1, r2, h, 18), body)
    m.rotation.x = Math.PI / 2
    m.position.z = i === 0 ? 0 : 0
    g.add(m)
  })

  // Brides (flanges) avec trous de rayon
  const fR = 0.295, fZ = 0.225, fThick = 0.046
  ;[-fZ, fZ].forEach(z => {
    // Flasque principale
    const flange = new T.Mesh(new T.CylinderGeometry(fR, fR, fThick, 22), silver)
    flange.rotation.x = Math.PI / 2
    flange.position.z = z
    g.add(flange)

    // Anneau de transition corps → bride
    const tr = new T.Mesh(new T.CylinderGeometry(0.178, 0.178, 0.032, 16), std(darkHex, 0.22, 0.72))
    tr.rotation.x = Math.PI / 2
    tr.position.z = z * 0.80
    g.add(tr)

    // Trous de rayon (S/2 par bride)
    const nH = Math.ceil(S / 2)
    const holeMat = std(0x040404, 0.9, 0.0)
    for (let i = 0; i < nH; i++) {
      const a    = (i / nH) * Math.PI * 2
      const hole = new T.Mesh(new T.CylinderGeometry(0.016, 0.016, fThick + 0.01, 5), holeMat)
      hole.rotation.x = Math.PI / 2
      hole.position.set(Math.cos(a) * fR * 0.70, Math.sin(a) * fR * 0.70, z)
      g.add(hole)
    }
  })

  // Bouchons d'axe (endcaps saillants)
  ;[-0.435, 0.435].forEach(z => {
    const cap = new T.Mesh(new T.CylinderGeometry(0.050, 0.050, 0.072, 8), std(0xc6cac6, 0.15, 0.96))
    cap.rotation.x = Math.PI / 2
    cap.position.z = z + (z > 0 ? 0.032 : -0.032)
    g.add(cap)
    // Bague de verrouillage
    const lock = new T.Mesh(new T.CylinderGeometry(0.066, 0.066, 0.018, 8), silver)
    lock.rotation.x = Math.PI / 2
    lock.position.z = z
    g.add(lock)
  })

  // Alésage central (canal sombre)
  const bore = new T.Mesh(new T.CylinderGeometry(0.036, 0.036, 0.56, 7), std(0x030303, 0.95, 0.0))
  bore.rotation.x = Math.PI / 2
  g.add(bore)

  // Liseré d'accent coloré sur le corps
  if (accentHex) {
    const ring = new T.Mesh(
      new T.CylinderGeometry(0.135, 0.135, 0.055, 16, 1, true),
      std(accentHex, 0.40, 0.12, accentHex, 0.12),
    )
    ring.rotation.x = Math.PI / 2
    g.add(ring)
  }

  return g
}

/** Bande de roulement selon le type de pneu */
function buildTread(
  T: typeof THREE, g: THREE.Group,
  R: number, tireT: number,
  type: 'smooth' | 'gravel' | 'mtb' | 'urban',
  SEG: number,
) {
  if (type === 'smooth') {
    return  // pneu route lisse — aucun crampon, aucun anneau supplémentaire
  }

  if (type === 'gravel') {
    // Crampons d'épaule — 2 rangées de 28 knobs, décalés (pas d'anneau torus)
    const kGeo = new T.BoxGeometry(0.056, 0.050, 0.066)
    const kMat = std(0x161208, 0.72, 0.03)
    const theta = Math.PI / 3
    const rS    = R + tireT * Math.cos(theta)
    const zS    = tireT * Math.sin(theta) * 0.88
    ;[zS, -zS].forEach((z, si) => {
      for (let i = 0; i < 28; i++) {
        const a  = ((i + si * 0.5) / 28) * Math.PI * 2
        const kn = new T.Mesh(kGeo, kMat)
        kn.position.set((rS + 0.025) * Math.cos(a), (rS + 0.025) * Math.sin(a), z)
        kn.rotation.z = a
        g.add(kn)
      }
    })
    return
  }

  if (type === 'mtb') {
    const kMat = std(0x141414, 0.80, 0.02)

    // Centre : 18 crampons hauts et larges
    const cGeo = new T.BoxGeometry(0.060, 0.065, 0.080)
    for (let i = 0; i < 18; i++) {
      const a  = (i / 18) * Math.PI * 2
      const kn = new T.Mesh(cGeo, kMat)
      kn.position.set((R + tireT + 0.018) * Math.cos(a), (R + tireT + 0.018) * Math.sin(a), 0)
      kn.rotation.z = a
      g.add(kn)
    }
    // Transition : 24 crampons moyens alternés
    const tGeo = new T.BoxGeometry(0.050, 0.048, 0.062)
    const th1  = Math.PI / 4
    const rT   = R + tireT * Math.cos(th1)
    const zT   = tireT * Math.sin(th1) * 0.90
    ;[zT, -zT].forEach((z, si) => {
      for (let i = 0; i < 24; i++) {
        const a  = ((i + si * 0.5) / 24) * Math.PI * 2
        const kn = new T.Mesh(tGeo, kMat)
        kn.position.set((rT + 0.023) * Math.cos(a), (rT + 0.023) * Math.sin(a), z)
        kn.rotation.z = a
        g.add(kn)
      }
    })
    // Épaule : 20 crampons larges ramped
    const sGeo = new T.BoxGeometry(0.078, 0.040, 0.056)
    const th2  = Math.PI * 0.42
    const rSh  = R + tireT * Math.cos(th2)
    const zSh  = tireT * Math.sin(th2) * 0.84
    ;[zSh, -zSh].forEach((z, si) => {
      for (let i = 0; i < 20; i++) {
        const a  = ((i + si * 0.25) / 20) * Math.PI * 2
        const kn = new T.Mesh(sGeo, kMat)
        kn.position.set((rSh + 0.018) * Math.cos(a), (rSh + 0.018) * Math.sin(a), z)
        kn.rotation.z = a
        g.add(kn)
      }
    })
    return
  }

  if (type === 'urban') {
    // VAE : damier dense (2 rangées alternées + bandes de protection latérales)
    const kMat = std(0x0e0d1a, 0.66, 0.05)
    const kA   = new T.BoxGeometry(0.040, 0.034, 0.058)
    for (let i = 0; i < 38; i++) {
      const a  = (i / 38) * Math.PI * 2
      const kn = new T.Mesh(kA, kMat)
      kn.position.set((R + tireT * 0.70 + 0.016) * Math.cos(a), (R + tireT * 0.70 + 0.016) * Math.sin(a), 0)
      kn.rotation.z = a
      g.add(kn)
    }
    const kB = new T.BoxGeometry(0.036, 0.028, 0.052)
    ;[-0.028, 0.028].forEach(zoff => {
      for (let i = 0; i < 38; i++) {
        const a  = ((i + 0.5) / 38) * Math.PI * 2
        const kn = new T.Mesh(kB, kMat)
        kn.position.set((R + tireT * 0.64 + 0.013) * Math.cos(a), (R + tireT * 0.64 + 0.013) * Math.sin(a), zoff)
        kn.rotation.z = a
        g.add(kn)
      }
    })
  }
}

/** Roue complète */
function buildWheel(T: typeof THREE, cfg: {
  R: number, tireT: number, rimDepth: number, S: number, spokesW: number
  rubber: number, rubberRough: number, rubberMetal: number
  rim: number, rimRough: number, rimMetal: number
  stripe: number
  hubColor: number, hubAccent: number
  disc: boolean, tread: 'smooth' | 'gravel' | 'mtb' | 'urban'
  bladed?: boolean
}) {
  const {
    R, tireT, rimDepth, S, spokesW,
    rubber: rubberHex, rubberRough, rubberMetal,
    rim: rimHex, rimRough, rimMetal,
    stripe: stripeHex,
    hubColor, hubAccent,
    disc, tread,
    bladed = false,
  } = cfg

  const SEG = 56, TSEG = 12
  const g = new T.Group()

  const rubberMat = std(rubberHex, rubberRough, rubberMetal)
  const rimMat    = std(rimHex, rimRough, rimMetal)
  const steelMat  = std(0xb8bcba, 0.22, 0.94)

  // ── 1. PNEU ──────────────────────────────────
  g.add(new T.Mesh(new T.TorusGeometry(R, tireT, TSEG, SEG), rubberMat))
  buildTread(T, g, R, tireT, tread, SEG)

  // ── 2. JANTE — un seul cylindre, rien d'autre ─
  const rimR = R - tireT
  // Les rayons s'attachent directement à la paroi extérieure de la jante
  const nipR = rimR
  const rimWall = new T.Mesh(
    new T.CylinderGeometry(rimR, rimR, rimDepth, SEG, 1, true),
    rimMat,
  )
  rimWall.rotation.x = Math.PI / 2
  g.add(rimWall)

  // ── 3. MOYEU + RAYONS ────────────────────────
  g.add(buildHub(T, S, hubColor, hubAccent))

  const up   = new T.Vector3(0, 1, 0)
  const sGeo = bladed
    ? new T.BoxGeometry(spokesW * 5.0, 1, spokesW * 0.85)
    : new T.CylinderGeometry(spokesW, spokesW, 1, 5)
  const sMat = bladed ? std(0xd2d6d2, 0.13, 0.96) : steelMat
  const fR = 0.295, fZ = 0.225
  for (let i = 0; i < S; i++) {
    const a    = (i / S) * Math.PI * 2
    const z    = i % 2 ? fZ : -fZ
    const sign = i % 2 ? 1 : -1
    const h    = new THREE.Vector3(Math.cos(a) * fR * 0.70, Math.sin(a) * fR * 0.70, z)
    const ra   = a + 0.36 * sign
    const r3   = new THREE.Vector3(Math.cos(ra) * nipR, Math.sin(ra) * nipR, 0)
    const dir  = new THREE.Vector3().subVectors(r3, h)
    const m    = new T.Mesh(sGeo, sMat)
    m.scale.y  = dir.length()
    m.position.copy(h).addScaledVector(dir, 0.5)
    m.quaternion.setFromUnitVectors(up, dir.clone().normalize())
    g.add(m)
  }

  return g
}

// ─────────────────────────────────────────────
// Configurations par gamme
// ─────────────────────────────────────────────
const CONFIGS = [
  {
    // Route — pneu étroit, jante carbone aéro, 20 rayons plats
    R: 2.40, tireT: 0.062, rimDepth: 0.52, S: 20, spokesW: 0.008,
    rubber: 0x0b0c1e, rubberRough: 0.36, rubberMetal: 0.14,
    rim: 0x080a16, rimRough: 0.12, rimMetal: 0.82,
    stripe: 0xfce500, hubColor: 0x18192a, hubAccent: 0xfce500,
    disc: false, bladed: true, tread: 'smooth' as const,
  },
  {
    // Gravel — medium, rubber chaud, jante alloy mat
    R: 2.35, tireT: 0.138, rimDepth: 0.28, S: 12, spokesW: 0.013,
    rubber: 0x130f08, rubberRough: 0.66, rubberMetal: 0.04,
    rim: 0x100d07, rimRough: 0.34, rimMetal: 0.52,
    stripe: 0x84bd00, hubColor: 0x1c1508, hubAccent: 0x84bd00,
    disc: false, tread: 'gravel' as const,
  },
  {
    // VTT — fat, très mat, crampons agressifs, moyeu large
    R: 2.20, tireT: 0.218, rimDepth: 0.18, S: 10, spokesW: 0.017,
    rubber: 0x181818, rubberRough: 0.80, rubberMetal: 0.02,
    rim: 0x121212, rimRough: 0.42, rimMetal: 0.48,
    stripe: 0xb4b8bf, hubColor: 0x181818, hubAccent: 0xb4b8bf,
    disc: false, tread: 'mtb' as const,
  },
  {
    // VAE — medium, renforcé, damier dense, liseré bleu Michelin
    R: 2.35, tireT: 0.108, rimDepth: 0.38, S: 14, spokesW: 0.013,
    rubber: 0x0c0b1e, rubberRough: 0.56, rubberMetal: 0.07,
    rim: 0x090818, rimRough: 0.22, rimMetal: 0.70,
    stripe: 0x27509B, hubColor: 0x0c0b1e, hubAccent: 0x27509B,
    disc: false, tread: 'urban' as const,
  },
]

// ─────────────────────────────────────────────
// Scène Three.js
// ─────────────────────────────────────────────
const el = useThreeScene(({ THREE: T, scene, camera }) => {
  camera.fov = 52
  camera.updateProjectionMatrix()
  camera.position.set(0, 1.2, 8.6)
  camera.lookAt(0, 0, 0)

  // Éclairage studio — met en valeur les matériaux et les détails
  scene.add(new T.AmbientLight(0xffffff, 0.28))            // bas pour garder le contraste

  const key = new T.DirectionalLight(0xffffff, 2.6)       // clé principale
  key.position.set(3, 7, 8)
  scene.add(key)

  const fill = new T.DirectionalLight(0xc8d8ff, 0.40)     // remplissage froid
  fill.position.set(-7, 1, 4)
  scene.add(fill)

  const accent = new T.PointLight(0xffd060, 1.10, 20)     // warm sur les métaux
  accent.position.set(2, -5, 5)
  scene.add(accent)

  const rim = new T.PointLight(0x5588ee, 0.90, 32)        // halo bleu arrière (séparation)
  rim.position.set(-4, 2, -6)
  scene.add(rim)

  const top = new T.PointLight(0xffffff, 0.55, 28)        // top light
  top.position.set(0, 9, 2)
  scene.add(top)

  // Construction des 4 roues (toutes à l'origine, une seule visible à la fois)
  const wheels: THREE.Group[] = CONFIGS.map(cfg => {
    const w = buildWheel(T, cfg)
    w.rotation.x = -0.72   // ~41° — profil du pneu visible (épaisseur lisible)
    w.rotation.y =  0.26   // légère torsion pour révéler la profondeur de jante
    w.visible = false
    w.scale.setScalar(0)
    scene.add(w)
    return w
  })

  let startT    = -1
  let prevLabel = -1
  let rollAcc   = 0
  let lastT     = -1

  return {
    update(t: number) {
      if (startT < 0) { startT = t; lastT = t }
      const dt      = t - lastT; lastT = t
      const elapsed = t - startT

      rollAcc += dt * 0.88

      const CYCLE  = SHOW_S + CROSS_S
      const phase  = elapsed % (CYCLE * 4)
      const ti     = Math.floor(phase / CYCLE) % 4
      const ni     = (ti + 1) % 4
      const phaseC = phase % CYCLE

      if (ti !== prevLabel) { prevLabel = ti; activeIdx.value = ti; labelKey.value++ }

      wheels.forEach((w, i) => {
        let s = 0
        if (phaseC < SHOW_S) {
          s = (i === ti) ? 1 : 0
        } else {
          const ct = (phaseC - SHOW_S) / CROSS_S
          if (ct < 0.5) {
            const t01 = ct / 0.5
            s = (i === ti) ? (1 - t01) * (1 - t01) : 0
          } else {
            const t01 = (ct - 0.5) / 0.5
            s = (i === ni) ? t01 * t01 : 0
          }
        }
        if (s > 0.001) { w.visible = true; w.scale.setScalar(s); w.rotation.z = rollAcc }
        else           { w.visible = false; w.scale.setScalar(0) }
      })
    },
  }
})

const ready = ref(false)
onMounted(() => { requestAnimationFrame(() => { ready.value = true }) })
</script>

<template>
  <div class="ts-wrap">
    <div v-if="!ready" class="ts-placeholder" aria-hidden="true">
      <div class="ts-ring ts-r1" /><div class="ts-ring ts-r2" /><div class="ts-ring ts-r3" />
    </div>

    <div
      ref="el"
      class="canvas-wrap"
      style="position:absolute;inset:0;transition:opacity .5s ease"
      :style="{ opacity: ready ? 1 : 0 }"
    />

    <div v-if="ready" class="ts-vignette" aria-hidden="true" />

    <transition name="ts-fade">
      <div v-if="ready" :key="labelKey" class="ts-label" aria-live="polite">
        <div class="ts-badge" :style="{ background: PRODUCTS[activeIdx].dot, color: PRODUCTS[activeIdx].text }">
          {{ PRODUCTS[activeIdx].cat }}
        </div>
        <div class="ts-name">Michelin {{ PRODUCTS[activeIdx].name }}</div>
        <div class="ts-desc">{{ PRODUCTS[activeIdx].desc }}</div>
        <div class="ts-dots" role="tablist">
          <span
            v-for="(p, i) in PRODUCTS" :key="i"
            role="tab" :aria-selected="i === activeIdx"
            class="ts-dot" :class="{ active: i === activeIdx }"
            :style="i === activeIdx ? { background: p.dot } : {}"
          />
        </div>
      </div>
    </transition>
  </div>
</template>

<style scoped>
.ts-wrap {
  position: relative; width: 100%; height: 100%; overflow: hidden;
  background: radial-gradient(130% 60% at 50% 20%, #4a7ec8 0%, #27509B 40%, #00205B 72%, #000C34 100%);
}
.canvas-wrap { position: absolute; inset: 0; }
.canvas-wrap canvas { display: block; width: 100%; height: 100%; }

.ts-vignette {
  position: absolute; left: 0; right: 0; bottom: 0; height: 38%;
  background: linear-gradient(to top, rgba(2,6,16,.70) 0%, transparent 100%);
  pointer-events: none;
}

.ts-placeholder {
  position: absolute; inset: 0;
  display: flex; align-items: center; justify-content: center;
}
.ts-ring { position: absolute; border-radius: 50%; animation: ts-spin 3s linear infinite; }
.ts-r1 { width: 200px; height: 200px; border: 2px solid rgba(255,255,255,.12); animation-duration: 4s; }
.ts-r2 { width: 130px; height: 130px; border: 2px solid rgba(255,255,255,.08); animation-direction: reverse; animation-duration: 2.8s; }
.ts-r3 { width: 60px;  height: 60px;  border: 2px solid rgba(252,229,0,.22); animation-duration: 1.8s; }
@keyframes ts-spin { to { transform: rotate(360deg); } }

.ts-label {
  position: absolute;
  bottom: clamp(16px, 4vh, 34px); left: 50%; transform: translateX(-50%);
  display: flex; flex-direction: column; align-items: center; gap: 4px;
  pointer-events: none; user-select: none; text-align: center; z-index: 2;
}
.ts-badge {
  display: inline-flex; align-items: center;
  height: 20px; padding: 0 11px; border-radius: 999px;
  font-size: 10px; font-weight: 700; letter-spacing: .10em; text-transform: uppercase;
}
.ts-name {
  font-size: clamp(16px, 2.4vw, 21px); font-weight: 800;
  letter-spacing: -.02em; line-height: 1.1; color: #fff; white-space: nowrap;
  text-shadow: 0 1px 10px rgba(0,0,0,.55), 0 4px 22px rgba(0,0,0,.42);
}
.ts-desc {
  font-size: 11px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase;
  color: rgba(255,255,255,.55); text-shadow: 0 1px 6px rgba(0,0,0,.4); margin-top: 1px;
}
.ts-dots { display: flex; gap: 7px; align-items: center; margin-top: 6px; }
.ts-dot {
  width: 5px; height: 5px; border-radius: 50%; background: rgba(255,255,255,.28);
  transition: background .35s ease, transform .35s ease;
}
.ts-dot.active { transform: scale(1.7); }

.ts-fade-enter-active { transition: opacity .42s ease, transform .42s ease; }
.ts-fade-leave-active { transition: opacity .25s ease, transform .25s ease; pointer-events: none; }
.ts-fade-enter-from   { opacity: 0; transform: translateX(-50%) translateY(8px); }
.ts-fade-leave-to     { opacity: 0; transform: translateX(-50%) translateY(-6px); }
</style>
