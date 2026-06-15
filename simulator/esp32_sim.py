"""
Michelin Aurora - Simulateur IoT ESP32
Simule les données capteurs pneu (pression avant/arrière + vitesse)
et les pousse vers le back-end via HTTP POST.
"""

import time
import random
import json
import urllib.request
import urllib.error

API_URL = "http://localhost:8080/api/telemetry"
INTERVAL_S = 2


def generate_reading() -> dict:
    return {
        "pressure_front_bar": round(random.uniform(6.5, 8.5), 2),
        "pressure_rear_bar": round(random.uniform(6.0, 8.0), 2),
        "speed_kmh": round(random.uniform(0.0, 45.0), 1),
        "alert_triggered": random.random() < 0.05,
    }


def send(payload: dict) -> None:
    data = json.dumps(payload).encode("utf-8")
    req = urllib.request.Request(
        API_URL,
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
    print(f"Simulateur ESP32 démarré — push toutes les {INTERVAL_S}s vers {API_URL}")
    while True:
        send(generate_reading())
        time.sleep(INTERVAL_S)
