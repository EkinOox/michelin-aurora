<script setup lang="ts">
import * as THREE from 'three'

const el = useThreeScene(({ THREE: T, scene, camera, renderer }) => {
  renderer.setClearColor(0x000000, 0)
  camera.fov = 52
  camera.updateProjectionMatrix()
  camera.position.set(0, 0, 8.5)
  camera.lookAt(0, 0, 0)
  addStandardLights(scene, { lime: true })

  const wheel = new T.Group()
  scene.add(wheel)

  const rubber = new T.MeshStandardMaterial({ color: 0x141414, roughness: 0.55, metalness: 0.12 })
  const carbon = new T.MeshStandardMaterial({ color: 0x16171a, roughness: 0.32, metalness: 0.55 })
  const silver = new T.MeshStandardMaterial({ color: 0xd2d6cf, roughness: 0.24, metalness: 0.96 })
  const steel  = new T.MeshStandardMaterial({ color: 0xbfc4bd, roughness: 0.3,  metalness: 0.9 })
  const dark   = new T.MeshStandardMaterial({ color: 0x0d0e10, roughness: 0.4,  metalness: 0.6 })
  const yellow = new T.MeshStandardMaterial({ color: 0xfce500, roughness: 0.4,  metalness: 0.2, emissive: 0x2e2a00, emissiveIntensity: 0.35 })

  // Segments réduits : 64 au lieu de 160 pour les tores (3x moins de triangles)
  const SEG = 64
  const TSEG = 10  // tube segments (était 22)

  const spokeGeo = new T.CylinderGeometry(0.018, 0.018, 1, 4) // 4 côtés au lieu de 6
  const up = new T.Vector3(0, 1, 0)
  const addSpoke = (from: THREE.Vector3, to: THREE.Vector3, mat: THREE.Material) => {
    const m = new T.Mesh(spokeGeo, mat)
    const dir = new T.Vector3().subVectors(to, from)
    const len = dir.length()
    m.scale.set(1, len, 1)
    m.position.copy(from).addScaledVector(dir, 0.5)
    m.quaternion.setFromUnitVectors(up, dir.clone().normalize())
    wheel.add(m)
  }

  const R = 2.45
  const tireT = 0.115
  const rimDepth = 0.34

  const tire = new T.Mesh(new T.TorusGeometry(R, tireT, TSEG, SEG), rubber)
  wheel.add(tire)
  const tread = new T.Mesh(new T.TorusGeometry(R + tireT * 0.55, 0.012, 6, SEG), dark)
  wheel.add(tread)
  const sideY = new T.Mesh(new T.TorusGeometry(R - 0.02, 0.022, 6, SEG), yellow)
  sideY.position.z = tireT * 0.7
  wheel.add(sideY)

  const rimOuter = new T.Mesh(new T.CylinderGeometry(R - tireT, R - tireT, rimDepth, 80, 1, true), carbon)
  rimOuter.rotation.x = Math.PI / 2
  wheel.add(rimOuter)
  const rimInner = new T.Mesh(new T.CylinderGeometry(R - tireT - rimDepth * 0.9, R - tireT - rimDepth * 0.9, rimDepth * 0.82, 80, 1, true), carbon)
  rimInner.rotation.x = Math.PI / 2
  wheel.add(rimInner)
  const rimBedF = new T.Mesh(new T.TorusGeometry(R - tireT - rimDepth * 0.45, 0.05, 6, SEG), carbon)
  rimBedF.position.z = rimDepth * 0.4
  wheel.add(rimBedF)
  const rimBedB = new T.Mesh(new T.TorusGeometry(R - tireT - rimDepth * 0.45, 0.05, 6, SEG), carbon)
  rimBedB.position.z = -rimDepth * 0.4
  wheel.add(rimBedB)

  const nippleRing = R - tireT - rimDepth * 0.92
  const ring = new T.Mesh(new T.TorusGeometry(nippleRing, 0.03, 6, SEG), steel)
  wheel.add(ring)

  const hubBody = new T.Mesh(new T.CylinderGeometry(0.16, 0.16, 0.66, 20), silver)
  hubBody.rotation.x = Math.PI / 2
  wheel.add(hubBody)
  const flangeR = 0.3
  const flangeZ = 0.26
  ;[flangeZ, -flangeZ].forEach((z) => {
    const fl = new T.Mesh(new T.CylinderGeometry(flangeR, flangeR, 0.04, 18), silver)
    fl.rotation.x = Math.PI / 2
    fl.position.z = z
    wheel.add(fl)
  })
  const endL = new T.Mesh(new T.CylinderGeometry(0.09, 0.09, 0.74, 14), dark)
  endL.rotation.x = Math.PI / 2
  wheel.add(endL)

  // 18 rayons au lieu de 28 — visuellement identique, 36% moins de géométrie
  const S = 18
  const cross = 0.42
  for (let i = 0; i < S; i++) {
    const a = (i / S) * Math.PI * 2
    const z = i % 2 ? flangeZ : -flangeZ
    const dirSign = i % 2 ? 1 : -1
    const hub = new T.Vector3(Math.cos(a) * flangeR, Math.sin(a) * flangeR, z)
    const rimA = a + cross * dirSign
    const rim = new T.Vector3(Math.cos(rimA) * nippleRing, Math.sin(rimA) * nippleRing, 0)
    addSpoke(hub, rim, steel)
  }

  wheel.rotation.x = -0.38
  wheel.rotation.y = 0.46

  return {
    update(t: number) {
      wheel.rotation.z = t * 1.0
    },
  }
})

const ready = ref(false)
onMounted(() => {
  // Laisse le navigateur peindre le placeholder avant de démarrer Three.js
  requestAnimationFrame(() => { ready.value = true })
})
</script>

<template>
  <div class="ts-wrap">
    <div v-if="!ready" class="ts-placeholder" aria-hidden="true">
      <div class="ts-ring ts-r1" />
      <div class="ts-ring ts-r2" />
      <div class="ts-ring ts-r3" />
    </div>
    <div ref="el" class="canvas-wrap" :style="{ opacity: ready ? 1 : 0 }" style="width: 100%; height: 100%; transition: opacity .4s ease" />
  </div>
</template>

<style scoped>
.ts-wrap { position: relative; width: 100%; height: 100%; }

/* Placeholder animé pendant le chargement de Three.js */
.ts-placeholder {
  position: absolute; inset: 0;
  display: flex; align-items: center; justify-content: center;
}
.ts-ring {
  position: absolute;
  border-radius: 50%;
  border: 2px solid rgba(132,189,0,.25);
  animation: ts-spin 3s linear infinite;
}
.ts-r1 { width: 200px; height: 200px; animation-duration: 4s; }
.ts-r2 { width: 130px; height: 130px; border-color: rgba(39,80,155,.22); animation-direction: reverse; animation-duration: 2.8s; }
.ts-r3 { width: 60px;  height: 60px;  border-color: rgba(252,229,0,.3); animation-duration: 1.8s; }

@keyframes ts-spin { to { transform: rotate(360deg); } }
</style>
