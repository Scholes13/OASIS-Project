# OpenCode Plugin Stack

This document records the OpenCode plugin stack chosen for this machine and this repository workflow.

## Installed Now

### `opencode-snip`

Purpose:
- reduce token waste from shell output before it enters the LLM context

Status:
- enabled in `~/.config/opencode/opencode.json`
- `snip.exe` installed locally at `C:\Users\Administrator\.local\bin\snip\snip.exe`
- OpenCode config injects that location into `PATH`

### `envsitter-guard`

Purpose:
- prevent unsafe reads and edits of sensitive `.env*` files
- provide safer inspection and mutation tools for dotenv files

Status:
- enabled in `~/.config/opencode/opencode.json`

### `opencode-notify`

Purpose:
- send native OS notifications when a task completes, errors, or needs input

Status:
- enabled in `~/.config/opencode/opencode.json`

## Deferred For Later

### `with-context-mcp`

Use later if project-specific operational notes need a more dynamic MCP-backed memory layer.

### `dynamic-context-pruning`

Use later if long sessions still accumulate too much stale tool output despite current compaction and `snip`.

### `model-announcer`

Use later if stronger model/lane observability inside prompt context becomes necessary.

### `handoff`

Use later if session-to-session continuity needs more automation than the current docs-and-plans workflow provides.

## Intentionally Skipped

- `tokenscope`
- `context analysis`

Reason:
- `9router` already serves as the main observability and routing layer, so these would be overlapping tools in the current setup.
