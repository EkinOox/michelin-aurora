<script setup lang="ts">
import * as THREE from 'three'

const el = useThreeScene(({ THREE: T, scene, camera, renderer }) => {
  renderer.setClearColor(0x000000, 0)
  camera.position.set(0, 0.6, 8.2)
  camera.lookAt(0, 0, 0)
  addStandardLights(scene, { lime: true })

  const wheel = new T.Group()
  scene.add(wheel)

  const rubber = new T.MeshStandardMaterial({ color: 0x141414, roughness: 0.55, metalness: 0.12 })
  const carbon = new T.MeshStandardMaterial({ color: 0x16171a, roughness: 0.32, metalness: 0.55 })
  const silver = new T.MeshStandardMaterial({ color: 0xd2d6cf, roughness: 0.24, metalness: 0.96 })
  const steel = new T.MeshStandardMaterial({ color: 0xbfc4bd, roughness: 0.3, metalness: 0.9 })
  const dark = new T.MeshStandardMaterial({ color: 0x0d0e10, roughness: 0.4, metalness: 0.6 })
  const yellow = new T.MeshStandardMaterial({ color: 0xfce500, roughness: 0.4, metalness: 0.2, emissive: 0x2e2a00, emissiveIntensity: 0.35 })

  const spokeGeo = new T.CylinderGeometry(0.018, 0.018, 1, 6)
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

  const tire = new T.Mesh(new T.TorusGeometry(R, tireT, 22, 160), rubber)
  wheel.add(tire)
  const tread = new T.Mesh(new T.TorusGeometry(R + tireT * 0.55, 0.012, 8, 160), dark)
  wheel.add(tread)
  const sideY = new T.Mesh(new T.TorusGeometry(R - 0.02, 0.022, 10, 160), yellow)
  sideY.position.z = tireT * 0.7
  wheel.add(sideY)

  const rimOuter = new T.Mesh(new T.CylinderGeometry(R - tireT, R - tireT, rimDepth, 160, 1, true), carbon)
  rimOuter.rotation.x = Math.PI / 2
  wheel.add(rimOuter)
  const rimInner = new T.Mesh(new T.CylinderGeometry(R - tireT - rimDepth * 0.9, R - tireT - rimDepth * 0.9, rimDepth * 0.82, 160, 1, true), carbon)
  rimInner.rotation.x = Math.PI / 2
  wheel.add(rimInner)
  const rimBedF = new T.Mesh(new T.TorusGeometry(R - tireT - rimDepth * 0.45, 0.05, 10, 160), carbon)
  rimBedF.position.z = rimDepth * 0.4
  wheel.add(rimBedF)
  const rimBedB = new T.Mesh(new T.TorusGeometry(R - tireT - rimDepth * 0.45, 0.05, 10, 160), carbon)
  rimBedB.position.z = -rimDepth * 0.4
  wheel.add(rimBedB)

  const nippleRing = R - tireT - rimDepth * 0.92
  const ring = new T.Mesh(new T.TorusGeometry(nippleRing, 0.03, 8, 160), steel)
  wheel.add(ring)

  const hubBody = new T.Mesh(new T.CylinderGeometry(0.16, 0.16, 0.66, 28), silver)
  hubBody.rotation.x = Math.PI / 2
  wheel.add(hubBody)
  const flangeR = 0.3
  const flangeZ = 0.26
  ;[flangeZ, -flangeZ].forEach((z) => {
    const fl = new T.Mesh(new T.CylinderGeometry(flangeR, flangeR, 0.04, 24), silver)
    fl.rotation.x = Math.PI / 2
    fl.position.z = z
    wheel.add(fl)
  })
  const endL = new T.Mesh(new T.CylinderGeometry(0.09, 0.09, 0.74, 18), dark)
  endL.rotation.x = Math.PI / 2
  wheel.add(endL)

  const S = 28
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
</script>

<template>
  <div ref="el" class="canvas-wrap" style="width: 100%; height: 100%" />
</template>
