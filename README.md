# CRTSHT

Recovered archive and presentation layer for **CRTSHT**, a series of 128 unique 20 × 20 cm physical works created, printed and recorded on Ethereum in 2021.

The original NFT metadata contains each print's SHA-256 fingerprint and an `ipfs://` image URI. Each physical work carries its own public address, a mooncake record and sealed private material; four visible recovery words act as an interaction key while the remaining words stay sealed.

## Public structure

- `/` — the complete visual archive, kept intentionally quiet.
- `/lore` — the mythology, creatures, original exhibition plan, mooncakes, hashes and draw.
- `/oracle` — collection-wide four-word lookup and fortune.
- `/crtsht/1` … `/crtsht/128` — forensic object / Ethereum / network records.

## Layers

1. **Physical** — the unique print, mooncake and sealed key material.
2. **Record** — original 2021 JSON metadata, hashes, wallets and IPFS CIDs.
3. **Presentation** — the replaceable web layer at `crtsht.info`.

The historical JSON remains unchanged. The presentation layer can evolve around it.

## Security

No database passwords, API keys, private keys, seed phrases or deployment credentials belong in this repository. Server credentials live in `private/config.php`, which is ignored by Git and blocked from HTTP access.

Credentials that previously appeared in Git history must be considered compromised even after their files are removed and should remain rotated.
