"""
Michelin Aurora — Simulateur IoT ESP32 v2
Simule capteurs pneu réalistes : pression (physique thermique), température,
vitesse (machine à états), altitude, batterie. HTTP POST vers l'API Aurora.

Usage:
    BIKE_TYPE=road    python3 simulator/esp32_sim.py
    BIKE_TYPE=gravel  python3 simulator/esp32_sim.py
    BIKE_TYPE=mtb     python3 simulator/esp32_sim.py
    API_BASE=http://localhost:8081 python3 simulator/esp32_sim.py
"""

import json
import math
import os
import random
import time
import urllib.error
import urllib.request
from dataclasses import dataclass, field
from enum import Enum

# ── Configuration ──────────────────────────────────────────────────────────────
API_BASE      = os.environ.get("API_BASE", "http://localhost:8081").rstrip("/")
TELEMETRY_URL = f"{API_BASE}/api/telemetry"
DEMO_RIDE_URL = f"{API_BASE}/api/rides/demo"
INTERVAL_S    = 2
BIKE_TYPE     = os.environ.get("BIKE_TYPE", "road").lower()
ROUTE_KM      = float(os.environ.get("ROUTE_KM", "0"))  # 0 = sortie libre

# Profils par type de vélo : (pression nominale, écart front/rear, seuil alerte bas, haut)
PROFILES = {
    "road":   {"p_front": 3.0,  "p_rear": 2.8,  "p_min": 1.5, "p_max": 4.5,
               "cruise": 28.0, "sprint": 52.0, "climb": 14.0, "descend": 58.0},
    "gravel": {"p_front": 3.0,  "p_rear": 2.8,  "p_min": 1.5, "p_max": 4.5,
               "cruise": 20.0, "sprint": 38.0, "climb": 10.0, "descend": 38.0},
    "mtb":    {"p_front": 3.0,  "p_rear": 2.8,  "p_min": 1.5, "p_max": 4.5,
               "cruise": 15.0, "sprint": 28.0, "climb":  7.0, "descend": 32.0},
}
PROFILE = PROFILES.get(BIKE_TYPE, PROFILES["road"])

# Température ambiante simulée (légère dérive pour la durée de la sortie)
AMBIENT_TEMP_C = random.uniform(14.0, 26.0)


# ── Machine à états de conduite ───────────────────────────────────────────────
class Phase(Enum):
    STOPPED  = "stopped"
    ACCEL    = "accelerating"
    CRUISE   = "cruising"
    CLIMB    = "climbing"
    DESCEND  = "descending"
    SPRINT   = "sprinting"
    BRAKING  = "braking"

# Transitions Markov : phase → liste de (phase_suivante, probabilité)
TRANSITIONS: dict[Phase, list[tuple[Phase, float]]] = {
    Phase.STOPPED: [(Phase.ACCEL,   0.65), (Phase.STOPPED, 0.35)],
    Phase.ACCEL:   [(Phase.CRUISE,  0.65), (Phase.SPRINT,  0.15), (Phase.CLIMB,   0.20)],
    Phase.CRUISE:  [(Phase.CRUISE,  0.50), (Phase.CLIMB,   0.15), (Phase.DESCEND, 0.15),
                    (Phase.SPRINT,  0.10), (Phase.BRAKING, 0.10)],
    Phase.CLIMB:   [(Phase.CLIMB,   0.60), (Phase.CRUISE,  0.25), (Phase.DESCEND, 0.15)],
    Phase.DESCEND: [(Phase.DESCEND, 0.50), (Phase.BRAKING, 0.30), (Phase.CRUISE,  0.20)],
    Phase.SPRINT:  [(Phase.BRAKING, 0.40), (Phase.CRUISE,  0.40), (Phase.SPRINT,  0.20)],
    Phase.BRAKING: [(Phase.STOPPED, 0.30), (Phase.CRUISE,  0.50), (Phase.BRAKING, 0.20)],
}

# Nombre minimum de ticks avant de pouvoir changer de phase
MIN_TICKS_IN_PHASE = {
    Phase.STOPPED:  3,
    Phase.ACCEL:    4,
    Phase.CRUISE:  10,
    Phase.CLIMB:    8,
    Phase.DESCEND:  6,
    Phase.SPRINT:   3,
    Phase.BRAKING:  3,
}


@dataclass
class SimState:
    # Vitesse
    speed_kmh: float    = 0.0
    target_speed: float = 0.0
    phase: Phase        = Phase.STOPPED
    ticks_in_phase: int = 0

    # Pressions (bar) — initialisées aux valeurs nominales + bruit
    p_front: float = field(default_factory=lambda: PROFILE["p_front"] + random.uniform(-0.1, 0.1))
    p_rear:  float = field(default_factory=lambda: PROFILE["p_rear"]  + random.uniform(-0.1, 0.1))

    # Températures pneu (°C)
    t_front: float = field(default_factory=lambda: AMBIENT_TEMP_C + random.uniform(-1, 1))
    t_rear:  float = field(default_factory=lambda: AMBIENT_TEMP_C + random.uniform(-1, 1))

    # Altitude GPS simulée (montée/descente cohérente avec la phase)
    altitude_m: float   = random.uniform(200.0, 800.0)

    # Batterie capteur (se vide très lentement)
    battery_pct: float  = random.uniform(85.0, 100.0)

    # Crevaison lente (déclenche périodiquement pour démontrer l'alerting)
    puncture_front: bool = False
    puncture_rear:  bool = False
    puncture_countdown: int = field(default_factory=lambda: random.randint(80, 200))

    # Distance parcourue (pour le vieillissement de pression)
    total_km: float = 0.0


def lerp(current: float, target: float, alpha: float) -> float:
    """Interpolation linéaire pour transitions douces."""
    return current + (target - current) * alpha


def next_phase(state: SimState) -> Phase:
    """Choisit la prochaine phase selon la matrice de transition Markov."""
    if state.ticks_in_phase < MIN_TICKS_IN_PHASE.get(state.phase, 3):
        return state.phase

    choices, weights = zip(*TRANSITIONS[state.phase])
    r = random.random()
    cumul = 0.0
    for phase, w in zip(choices, weights):
        cumul += w
        if r < cumul:
            return phase
    return choices[-1]


def target_speed_for_phase(phase: Phase) -> float:
    """Vitesse cible pour la phase en cours, avec bruit."""
    base = {
        Phase.STOPPED:  0.0,
        Phase.ACCEL:    PROFILE["cruise"] * 0.7,
        Phase.CRUISE:   PROFILE["cruise"],
        Phase.CLIMB:    PROFILE["climb"],
        Phase.DESCEND:  PROFILE["descend"],
        Phase.SPRINT:   PROFILE["sprint"],
        Phase.BRAKING:  PROFILE["cruise"] * 0.3,
    }.get(phase, PROFILE["cruise"])
    return max(0.0, base + random.gauss(0, base * 0.08))


def update_physics(state: SimState) -> None:
    """Met à jour tous les capteurs physiques selon l'état de conduite."""
    dt = INTERVAL_S  # secondes

    # ── Phase transition ─────────────────────────────────────────────────────
    new_phase = next_phase(state)
    if new_phase != state.phase:
        state.phase = new_phase
        state.ticks_in_phase = 0
        state.target_speed = target_speed_for_phase(new_phase)
    else:
        state.ticks_in_phase += 1
        # Légère dérive de la cible en cruise/climb/descend
        if state.phase in (Phase.CRUISE, Phase.CLIMB, Phase.DESCEND):
            state.target_speed += random.gauss(0, 0.4)
            state.target_speed = max(0.0, state.target_speed)

    # ── Vitesse (lerp vers cible + micro-bruit capteur) ──────────────────────
    accel_alpha = {
        Phase.STOPPED: 0.25, Phase.ACCEL: 0.12, Phase.CRUISE: 0.06,
        Phase.CLIMB: 0.08, Phase.DESCEND: 0.10, Phase.SPRINT: 0.18,
        Phase.BRAKING: 0.20,
    }.get(state.phase, 0.08)
    state.speed_kmh = lerp(state.speed_kmh, state.target_speed, accel_alpha)
    state.speed_kmh = max(0.0, state.speed_kmh + random.gauss(0, 0.15))

    # ── Distance parcourue ───────────────────────────────────────────────────
    state.total_km += state.speed_kmh * dt / 3600.0

    # ── Température pneu ─────────────────────────────────────────────────────
    # Les pneus chauffent avec la vitesse (frottement), refroidissent à l'arrêt
    heat_rate  = (state.speed_kmh / PROFILE["sprint"]) * 0.4  # °C par tick à pleine vitesse
    cool_rate  = 0.15 if state.speed_kmh < 3.0 else 0.05
    t_target_f = AMBIENT_TEMP_C + (state.speed_kmh / PROFILE["sprint"]) * 28.0
    t_target_r = t_target_f + 2.5  # l'arrière chauffe un peu plus (poids + motricité)
    state.t_front = lerp(state.t_front, t_target_f, heat_rate * dt * 0.03 + cool_rate * dt * 0.01)
    state.t_rear  = lerp(state.t_rear,  t_target_r, heat_rate * dt * 0.03 + cool_rate * dt * 0.01)
    state.t_front += random.gauss(0, 0.05)
    state.t_rear  += random.gauss(0, 0.05)

    # ── Pression (loi de Gay-Lussac : P/T = const) ────────────────────────────
    # Pression nominale à température ambiante → recalcul à la température réelle
    t0_k = 273.15 + AMBIENT_TEMP_C  # température de référence en Kelvin
    t_front_k = 273.15 + state.t_front
    t_rear_k  = 273.15 + state.t_rear

    p_nominal_front = PROFILE["p_front"]
    p_nominal_rear  = PROFILE["p_rear"]

    # Pression thermique + vieillissement lent (~0.005 bar/100km) + bruit capteur
    aging_loss = state.total_km * 0.00005
    state.p_front = (p_nominal_front - aging_loss) * (t_front_k / t0_k) + random.gauss(0, 0.008)
    state.p_rear  = (p_nominal_rear  - aging_loss) * (t_rear_k  / t0_k) + random.gauss(0, 0.008)

    # Crevaison lente — perd ~0.008 bar par tick
    if state.puncture_front:
        state.p_front -= 0.008
    if state.puncture_rear:
        state.p_rear -= 0.008

    # ── Déclenchement / arrêt de crevaison lente ─────────────────────────────
    state.puncture_countdown -= 1
    if state.puncture_countdown <= 0:
        if not state.puncture_front and not state.puncture_rear:
            # Démarre une crevaison sur l'une des roues
            if random.random() < 0.5:
                state.puncture_front = True
                print("[ESP32] ⚠  Crevaison lente simulée — avant")
            else:
                state.puncture_rear = True
                print("[ESP32] ⚠  Crevaison lente simulée — arrière")
            state.puncture_countdown = random.randint(30, 60)  # dure 1-2 min
        else:
            # Pneu "réparé" (reset simulation)
            state.puncture_front = False
            state.puncture_rear  = False
            print("[ESP32] ✓  Crevaison terminée — reprise pression nominale")
            state.puncture_countdown = random.randint(120, 300)

    # ── Altitude (cohérente avec la phase) ───────────────────────────────────
    altitude_delta = {
        Phase.CLIMB:    random.uniform(1.5, 4.0),
        Phase.DESCEND:  random.uniform(-4.0, -1.0),
        Phase.STOPPED:  0.0,
    }.get(state.phase, random.gauss(0, 0.5))
    state.altitude_m = max(0.0, state.altitude_m + altitude_delta)

    # ── Batterie (perd ~0.002 % par tick ≈ 100% sur ~28h de collecte) ────────
    state.battery_pct = max(0.0, state.battery_pct - 0.002 + random.gauss(0, 0.001))


def is_alert(state: SimState) -> bool:
    """Alerte si pression hors seuil, ou écart anormal avant/arrière."""
    if state.p_front < PROFILE["p_min"] or state.p_rear < PROFILE["p_min"]:
        return True
    if state.p_front > PROFILE["p_max"] or state.p_rear > PROFILE["p_max"]:
        return True
    if abs(state.p_front - state.p_rear) > 1.8:  # déséquilibre anormal
        return True
    return False


def build_payload(ride_id: str, state: SimState) -> dict:
    km_remaining = round(max(0.0, ROUTE_KM - state.total_km), 2) if ROUTE_KM > 0 else None
    payload: dict = {
        "ride_id":            ride_id,
        "pressure_front_bar": round(state.p_front, 3),
        "pressure_rear_bar":  round(state.p_rear,  3),
        "temp_front_c":       round(state.t_front,  1),
        "temp_rear_c":        round(state.t_rear,   1),
        "speed_kmh":          round(state.speed_kmh, 1),
        "altitude_m":         round(state.altitude_m, 1),
        "battery_pct":        round(state.battery_pct, 1),
        "phase":              state.phase.value,
        "alert_triggered":    is_alert(state),
    }
    if km_remaining is not None:
        payload["km_remaining"] = km_remaining
    return payload


# ── Réseau ────────────────────────────────────────────────────────────────────
def fetch_demo_ride_id() -> str:
    while True:
        try:
            with urllib.request.urlopen(DEMO_RIDE_URL, timeout=5) as resp:
                ride = json.loads(resp.read())
                print(f"[ESP32] Ride démo trouvée : {ride['id']}")
                return ride["id"]
        except (urllib.error.URLError, KeyError) as exc:
            print(f"[ESP32] Pas de ride dispo ({exc}), retry dans 3s — "
                  f"as-tu lancé `doctrine:fixtures:load` ?")
            time.sleep(3)


def send(payload: dict) -> None:
    data = json.dumps(payload).encode("utf-8")
    req  = urllib.request.Request(
        TELEMETRY_URL,
        data=data,
        headers={"Content-Type": "application/json"},
        method="POST",
    )
    try:
        with urllib.request.urlopen(req, timeout=5) as resp:
            alert_flag = "🚨" if payload["alert_triggered"] else "  "
            print(
                f"[ESP32] {alert_flag} "
                f"{payload['phase']:>12s} | "
                f"{payload['speed_kmh']:5.1f} km/h | "
                f"F {payload['pressure_front_bar']:.3f} bar {payload['temp_front_c']:.1f}°C | "
                f"R {payload['pressure_rear_bar']:.3f} bar {payload['temp_rear_c']:.1f}°C | "
                f"alt {payload['altitude_m']:.0f}m | "
                f"bat {payload['battery_pct']:.1f}%  → HTTP {resp.status}"
            )
    except urllib.error.URLError as exc:
        print(f"[ESP32] ✗ Erreur réseau : {exc.reason}")


# ── Point d'entrée ────────────────────────────────────────────────────────────
if __name__ == "__main__":
    ride_id = fetch_demo_ride_id()
    state   = SimState()
    state.target_speed = 0.0

    route_info = f"{ROUTE_KM} km" if ROUTE_KM > 0 else "sortie libre"
    print(
        f"\nSimulateur ESP32 démarré"
        f"\n  Vélo    : {BIKE_TYPE}"
        f"\n  Route   : {route_info}"
        f"\n  Pression nominale : {PROFILE['p_front']} / {PROFILE['p_rear']} bar (F/R)"
        f"\n  Temp ambiante     : {AMBIENT_TEMP_C:.1f}°C"
        f"\n  Push toutes les   : {INTERVAL_S}s → {TELEMETRY_URL}\n"
    )

    while True:
        update_physics(state)
        payload = build_payload(ride_id, state)
        send(payload)
        time.sleep(INTERVAL_S)
