"""
Michelin Aurora - Simulateur IoT ESP32
Simule les données capteurs pneu (pression avant/arrière + vitesse)
et les pousse vers le back-end via HTTP POST.
"""

import os
import time
import random
import json
import urllib.request
import urllib.error

API_BASE = os.environ.get("API_BASE", "http://localhost:8081").rstrip("/")
TELEMETRY_URL = f"{API_BASE}/api/telemetry"
DEMO_RIDE_URL = f"{API_BASE}/api/rides/demo"
INTERVAL_S = 2


def fetch_demo_ride_id() -> str:
    while True:
        try:
            with urllib.request.urlopen(DEMO_RIDE_URL, timeout=5) as resp:
                ride = json.loads(resp.read())
                print(f"[ESP32] Ride démo trouvée : {ride['id']}")
                return ride["id"]
        except (urllib.error.URLError, KeyError) as exc:
            print(f"[ESP32] Pas de ride démo dispo ({exc}), retry dans 3s — "
                  f"as-tu lancé `doctrine:fixtures:load` côté back ?")
            time.sleep(3)


def generate_reading(ride_id: str) -> dict:
    return {
        "ride_id": ride_id,
        "pressure_front_bar": round(random.uniform(6.5, 8.5), 2),
        "pressure_rear_bar": round(random.uniform(6.0, 8.0), 2),
        "speed_kmh": round(random.uniform(0.0, 45.0), 1),
        "alert_triggered": random.random() < 0.05,
    }


def send(payload: dict) -> None:
    data = json.dumps(payload).encode("utf-8")
    req = urllib.request.Request(
        TELEMETRY_URL,
        data=data,
        headers={"Content-Type": "application/json"},
        method="POST",
    )
    try:
        with urllib.request.urlopen(req, timeout=5) as resp:
            print(f"[ESP32] {payload}  →  HTTP {resp.status}")
    except urllib.error.URLError as exc:
        print(f"[ESP32] Erreur envoi : {exc.reason}")


if __name__ == "__main__":
    ride_id = fetch_demo_ride_id()
    print(f"Simulateur ESP32 démarré — push toutes les {INTERVAL_S}s vers {TELEMETRY_URL}")
    while True:
        send(generate_reading(ride_id))
        time.sleep(INTERVAL_S)
