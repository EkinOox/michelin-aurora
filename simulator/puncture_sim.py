"""
Michelin Aurora — Simulateur de crevaison
Scénario : sortie normale → crevaison lente → pression critique → arrêt

Usage:
    python3 simulator/puncture_sim.py
    WHEEL=rear BIKE_TYPE=gravel python3 simulator/puncture_sim.py
"""

import json
import os
import random
import time
import urllib.error
import urllib.request

API_BASE      = os.environ.get("API_BASE", "http://localhost:8081").rstrip("/")
TELEMETRY_URL = f"{API_BASE}/api/telemetry"
DEMO_RIDE_URL = f"{API_BASE}/api/rides/demo"
INTERVAL_S    = 1

BIKE_TYPE = os.environ.get("BIKE_TYPE", "road").lower()
WHEEL     = os.environ.get("WHEEL", "rear").lower()  # front | rear

PROFILES = {
    "road":   {"p_front": 3.0, "p_rear": 2.8, "p_min": 1.5, "speed": 28.0},
    "gravel": {"p_front": 3.0, "p_rear": 2.8, "p_min": 1.5, "speed": 20.0},
    "mtb":    {"p_front": 3.0, "p_rear": 2.8, "p_min": 1.5, "speed": 15.0},
}
P = PROFILES.get(BIKE_TYPE, PROFILES["road"])

# Scénario en étapes : (label, durée_ticks, description)
SCENARIO = [
    ("normal",    15, "Sortie normale, tout va bien"),
    ("impact",     1, "Choc sur obstacle — crevaison amorcée"),
    ("slow_leak", 40, "Fuite lente, pression qui descend progressivement"),
    ("critical",  10, "Pression critique — alerte déclenchée"),
    ("flat",       5, "Pneu à plat — arrêt immédiat"),
]


def noise(amp: float = 0.008) -> float:
    return random.gauss(0, amp)


def lerp(a: float, b: float, t: float) -> float:
    return a + (b - a) * t


def fetch_ride_id() -> str:
    while True:
        try:
            with urllib.request.urlopen(DEMO_RIDE_URL, timeout=5) as r:
                ride = json.loads(r.read())
                print(f"[SIM] Ride trouvée : {ride['id']}")
                return ride["id"]
        except (urllib.error.URLError, KeyError) as e:
            print(f"[SIM] En attente du back ({e}), retry dans 3s…")
            time.sleep(3)


def send(payload: dict) -> None:
    data = json.dumps(payload).encode()
    req  = urllib.request.Request(
        TELEMETRY_URL, data=data,
        headers={"Content-Type": "application/json"}, method="POST",
    )
    try:
        with urllib.request.urlopen(req, timeout=5) as r:
            flag = "🚨" if payload["alert_triggered"] else "  "
            print(
                f"  {flag} [{payload['phase']:>10s}] "
                f"F {payload['pressure_front_bar']:.3f} bar | "
                f"R {payload['pressure_rear_bar']:.3f} bar | "
                f"{payload['speed_kmh']:5.1f} km/h  → {r.status}"
            )
    except urllib.error.URLError as e:
        print(f"[SIM] ✗ {e.reason}")


def run(ride_id: str) -> None:
    p_front = P["p_front"] + noise(0.05)
    p_rear  = P["p_rear"]  + noise(0.05)
    speed   = 0.0
    tick    = 0

    # Pression de départ de la roue crevaison (pour interpoler la chute)
    puncture_wheel   = WHEEL
    p_start_puncture = p_front if puncture_wheel == "front" else p_rear
    p_flat           = 0.15  # pression à plat

    print(f"\n{'─'*60}")
    print(f"  Scénario crevaison — {BIKE_TYPE.upper()} | roue : {puncture_wheel}")
    print(f"  Pression nominale  F {P['p_front']} / R {P['p_rear']} bar")
    print(f"  Seuil alerte bas   {P['p_min']} bar")
    print(f"{'─'*60}\n")

    for stage_name, duration, description in SCENARIO:
        print(f"\n▶  {stage_name.upper()} — {description}")

        for i in range(duration):
            t = i / max(duration - 1, 1)  # progression 0→1 dans l'étape

            if stage_name == "normal":
                speed = lerp(0.0, P["speed"], min(t * 3, 1.0)) + noise(0.5)
                speed = max(0.0, speed)
                p_front += noise()
                p_rear  += noise()

            elif stage_name == "impact":
                # Choc brutal : micro-pic de pression puis chute instantanée
                speed = P["speed"] * 0.85
                if puncture_wheel == "front":
                    p_front = p_start_puncture * 0.97
                else:
                    p_rear = p_start_puncture * 0.97
                print(f"  💥 IMPACT — crevaison amorcée sur {puncture_wheel}")

            elif stage_name == "slow_leak":
                # Chute progressive, le cycliste continue de rouler mais ralentit
                leak_progress = t
                p_target = lerp(p_start_puncture * 0.97, P["p_min"] * 1.05, leak_progress)
                if puncture_wheel == "front":
                    p_front = p_target + noise(0.012)
                else:
                    p_rear  = p_target + noise(0.012)
                # Le vélo devient instable : vitesse qui baisse
                speed = P["speed"] * (1.0 - leak_progress * 0.4) + noise(0.8)
                speed = max(0.0, speed)

            elif stage_name == "critical":
                # Pression sous le seuil, le cycliste freine
                p_target = lerp(P["p_min"] * 1.05, P["p_min"] * 0.80, t)
                if puncture_wheel == "front":
                    p_front = p_target + noise(0.015)
                else:
                    p_rear  = p_target + noise(0.015)
                speed = P["speed"] * 0.3 * (1 - t) + noise(0.5)
                speed = max(0.0, speed)

            elif stage_name == "flat":
                # À plat — arrêt complet
                if puncture_wheel == "front":
                    p_front = lerp(p_front, p_flat, t * 0.8) + noise(0.005)
                else:
                    p_rear  = lerp(p_rear,  p_flat, t * 0.8) + noise(0.005)
                speed = max(0.0, speed * 0.4 + noise(0.1))

            alert = (
                p_front < P["p_min"] or p_rear < P["p_min"]
                or abs(p_front - p_rear) > 1.8
            )

            send({
                "ride_id":            ride_id,
                "pressure_front_bar": round(max(0.0, p_front), 3),
                "pressure_rear_bar":  round(max(0.0, p_rear),  3),
                "speed_kmh":          round(max(0.0, speed), 1),
                "alert_triggered":    alert,
                "phase":              stage_name,
                "temp_front_c":       round(20.0 + speed * 0.3 + noise(0.1), 1),
                "temp_rear_c":        round(22.0 + speed * 0.3 + noise(0.1), 1),
                "altitude_m":         round(350.0 + noise(0.5), 1),
                "battery_pct":        round(98.0 - tick * 0.01, 1),
            })

            tick += 1
            time.sleep(INTERVAL_S)

    print(f"\n{'─'*60}")
    print(f"  Scénario terminé — pneu {puncture_wheel} à plat.")
    print(f"  {tick} lectures envoyées.")
    print(f"{'─'*60}\n")


if __name__ == "__main__":
    ride_id = fetch_ride_id()
    run(ride_id)
