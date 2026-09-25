#!/usr/bin/env python3
"""Generate Egyptian Arabic TTS via edge-tts. Usage: say.py TEXT OUTPUT.mp3"""

from __future__ import annotations

import asyncio
import os
import sys


async def main() -> int:
    if len(sys.argv) < 3:
        print("usage: say.py TEXT OUTPUT", file=sys.stderr)
        return 2

    text = sys.argv[1].strip()
    output = sys.argv[2]

    if not text:
        print("empty text", file=sys.stderr)
        return 2

    try:
        import edge_tts
    except ImportError:
        print("edge_tts is not installed", file=sys.stderr)
        return 1

    voice = os.environ.get("EDGE_TTS_VOICE", "ar-EG-ShakirNeural")
    communicate = edge_tts.Communicate(text, voice)
    await communicate.save(output)

    return 0 if os.path.isfile(output) and os.path.getsize(output) > 0 else 1


if __name__ == "__main__":
    raise SystemExit(asyncio.run(main()))
