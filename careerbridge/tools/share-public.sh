#!/usr/bin/env bash
# CareerBridge - share a local XAMPP/LAMP site on the internet (macOS / Linux).
# Usage:  ./tools/share-public.sh [ngrok|cloudflare] [port]
# Then open the printed https URL and add /careerbridge/ to the end.
set -e
TOOL="${1:-cloudflare}"
PORT="${2:-80}"
case "$TOOL" in
  ngrok)
    command -v ngrok >/dev/null || { echo "ngrok not found. Install: https://ngrok.com/download"; exit 1; }
    echo "Public URL will be https://<subdomain>.ngrok-free.app/careerbridge/  (Ctrl+C to stop)"
    exec ngrok http "$PORT" ;;
  cloudflare|cloudflared)
    command -v cloudflared >/dev/null || { echo "cloudflared not found. macOS: brew install cloudflared"; exit 1; }
    echo "Look for https://<words>.trycloudflare.com and add /careerbridge/  (Ctrl+C to stop)"
    exec cloudflared tunnel --url "http://localhost:$PORT" ;;
  *) echo "Usage: $0 [ngrok|cloudflare] [port]"; exit 1 ;;
esac
