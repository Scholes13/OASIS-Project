# OpenCode Model Routing Reference

This document records the current OpenCode routing setup used on this machine so future sessions can reason about agent behavior without re-discovering the configuration.

## Primary Session Model

- OpenCode primary model: `9router/cx/gpt-5.4`

## Important Note About `cx/gpt-5.4`

The local `9router` model registry currently exposes `cx/gpt-5.4` as a single model ID.

Observed behavior in the local registry at the time of documentation:
- there was no separate `cx/gpt-5.4-high`
- there was no separate `cx/gpt-5.4-xhigh`
- there was no separate `cx/gpt-5.4-medium`
- there was no separate `cx/gpt-5.4-low`

Practical interpretation:
- `cx/gpt-5.4` should be treated as the default or auto profile exposed by `9router`
- reasoning effort selection, if any, is handled internally by the router or provider rather than by distinct public model IDs in this setup

This differs from some Codex surfaces where users can explicitly choose `low`, `medium`, `high`, or `xhigh`.

## Specialized Routing

- implementation lanes -> `9router/Coder`
- heavy debugging and QA lanes -> `9router/Debugger`
- read-only exploration and reviewer-style validation, plus built-in `plan`/`explore` lanes -> `9router/Viewer`

## Current OpenCode Mapping

- primary session -> `9router/cx/gpt-5.4`
- `coder-backend` -> `9router/Coder`
- `coder-frontend` -> `9router/Coder`
- `debugger` -> `9router/Debugger`
- `qa` -> `9router/Debugger`
- `reviewer` -> `9router/Viewer`
- `viewer` -> `9router/Viewer`
- built-in `plan` -> `9router/Viewer`
- built-in `explore` -> `9router/Viewer`

## About Possible `gpt-5.4` Effort Variants

From the local `9router` registry, explicit effort variants are visible for some other models, for example:
- `cx/gpt-5.3-codex-high`
- `cx/gpt-5.3-codex-xhigh`
- `cx/gpt-5.3-codex-low`

That means `9router` can expose effort-specific variants when the router owner decides to publish them as separate model IDs.

For `cx/gpt-5.4`, OpenCode may still list additional variants in its local config if you want them available for future use, even when the current registry has not exposed them yet.

So the practical answer is:
- yes, `9router` can support something like `cx/gpt-5.4-high` if the router/provider defines and exposes it
- if the active registry does not expose that variant yet, OpenCode will list it but cannot successfully use it until the router publishes it

## Experimental Vision Metadata Patch

An experimental local patch was added to `opencode.json` for these model entries:
- `9router/cx/gpt-5.4`
- `9router/cx/gpt-5.4-high`
- `9router/cx/gpt-5.4-xhigh`

The patch adds:

```json
"modalities": {
  "input": ["text", "image"],
  "output": ["text"]
}
```

Purpose:
- test whether OpenCode was rejecting image input because the proxied 9router models were treated as text-only by client-side capability detection

Interpretation:
- if image input starts working after reload, the root cause was likely missing capability metadata on the client side
- if the error persists, the next likely cause is that 9router or the upstream provider is not forwarding or accepting multimodal payloads correctly

## Why This Setup Exists

- `cx/gpt-5.4` acts as the general-purpose orchestrator model
- `Coder`, `Debugger`, and `Viewer` let `9router` optimize model choice inside each lane
- this keeps routing simpler and leaves credit-aware orchestration to `9router`

## When To Change It

Only change this assumption if the local `9router` model registry starts exposing explicit `cx/gpt-5.4` effort variants such as `-high` or `-xhigh`.
