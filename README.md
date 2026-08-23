# CRTSHT

Recovered archive and presentation layer for **CRTSHT**, a series of 128 unique physical works created and recorded on Ethereum in 2021.

Each 25 × 25 cm print is tied to a cryptographic record. The original NFT metadata contains the print's SHA-256 fingerprint and an `ipfs://` image URI. The physical work carries its own public address and sealed private material; four recovery words form the interaction key.

## 2026 archive

The current site deliberately separates three layers:

1. **Physical** — the unique print and its sealed key material.
2. **Record** — original 2021 JSON metadata, hashes and IPFS CIDs, kept unchanged.
3. **Presentation** — the replaceable web layer at `crtsht.info`, including resilient IPFS gateway resolution and local archival image fallback.

The web layer may change. The historical metadata should not be rewritten merely to accommodate a gateway or API change.

## Security

No database passwords, API keys, private keys, seed phrases or deployment credentials belong in this repository. Use host-level environment variables or a non-public local configuration outside the web root for future server-side integrations.

The legacy database credential files were removed from the current branch in August 2026. Credentials that appeared in Git history must be considered compromised and rotated; deleting a file does not remove it from Git history.

## Next restoration layer

The archive currently works without a database or third-party API key. A future server-side blockchain adapter can add live Ethereum ownership/transaction information once the canonical contract/token mapping is verified and any API credentials are supplied securely through the host environment.
