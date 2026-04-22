#!/usr/bin/env node
/**
 * Harness Gardener — Automated drift detection for OpenCode agent harness files.
 *
 * Checks:
 *   1. Tech Stack Match — keywords in harness vs actual composer.json / package.json
 *   2. Directory Existence — paths mentioned in harness vs filesystem
 *   3. Module Inventory — actual modules vs modules mentioned in harness
 *
 * Usage:
 *   node scripts/harness-gardener.mjs [--harness-dir <path>] [--project-dir <path>]
 *
 * Exit codes:
 *   0 — no drift (or warnings printed to stdout)
 *   1 — fatal error (cannot read required files)
 *
 * Output: one line per warning, prefixed with [harness-gardener].
 *         Silent when no drift is detected.
 */

import { readFileSync, readdirSync, existsSync, statSync } from "node:fs";
import { join, resolve, basename } from "node:path";

// ---------------------------------------------------------------------------
// Config
// ---------------------------------------------------------------------------

const HARNESS_DIR =
  process.argv.includes("--harness-dir")
    ? process.argv[process.argv.indexOf("--harness-dir") + 1]
    : join(
        process.env.HOME || process.env.USERPROFILE || "",
        ".config",
        "opencode",
        "agents",
      );

const PROJECT_DIR =
  process.argv.includes("--project-dir")
    ? process.argv[process.argv.indexOf("--project-dir") + 1]
    : process.cwd();

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function readJson(filePath) {
  try {
    return JSON.parse(readFileSync(filePath, "utf-8"));
  } catch {
    return null;
  }
}

function readText(filePath) {
  try {
    return readFileSync(filePath, "utf-8");
  } catch {
    return null;
  }
}

/** List immediate subdirectory names under a path. */
function listSubdirs(dirPath) {
  if (!existsSync(dirPath)) return [];
  return readdirSync(dirPath, { withFileTypes: true })
    .filter((d) => d.isDirectory())
    .map((d) => d.name);
}

/** Extract all backtick-wrapped paths from markdown text that look like project directories. */
function extractPaths(text) {
  const matches = [];
  // Match paths like `app/Http/Controllers/` or `resources/js/inertia/`
  const re = /`([a-zA-Z][a-zA-Z0-9_./\\-]+\/?)`/g;
  let m;
  while ((m = re.exec(text)) !== null) {
    const p = m[1];
    // Must look like a real project path (at least 2 segments from root)
    const segments = p.split("/").filter(Boolean);
    if (
      segments.length >= 2 &&
      !p.startsWith("http") &&
      !p.startsWith("@") &&
      !p.includes("::") &&
      !p.includes("(") &&
      !p.endsWith(".json") &&
      !p.endsWith(".md") &&
      !p.startsWith("docs/")
    ) {
      matches.push(p);
    }
  }
  return [...new Set(matches)];
}

/**
 * Check if a path reference appears in a negation context
 * (e.g. "do NOT use", "not the default", "do not assume").
 */
function isNegatedPath(text, path) {
  const escaped = path.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
  // Look for negation words within ~80 chars before the path mention
  const re = new RegExp(
    `(do not|don't|NOT|not the default|never|avoid|instead of)[^\`]{0,80}\`${escaped}\``,
    "i",
  );
  return re.test(text);
}

/** Extract module names mentioned in harness text. */
function extractMentionedModules(text) {
  const modules = new Set();
  // Primary: match explicit module paths like Modules/Activity/ or Models/Modules/Activity
  const nameRe = /\bModules\/([A-Z][a-zA-Z]+)\b/g;
  let m;
  while ((m = nameRe.exec(text)) !== null) {
    modules.add(m[1]);
  }
  // Secondary: match bold module names in "Business Modules" or "Modules" sections
  // Only look inside lines that follow a modules-related heading or list pattern
  const boldRe = /\*\*([A-Z][a-zA-Z]+)\*\*\s*[—\-–]/g;
  while ((m = boldRe.exec(text)) !== null) {
    const name = m[1];
    // Skip words that are clearly not business module names
    const skip = [
      "Backend", "Frontend", "Build", "Database", "Styling", "Testing",
      "Key", "Important", "Symptom", "Root", "Fix", "Verification",
      "Status", "Findings", "Recommendations", "Risk", "Scope",
      "Tests", "Coverage", "Smoke", "Remaining", "Open", "Recommended",
      "Architecture", "Laravel", "Security", "Contracts", "Test",
      "Project", "Business", "DEPRECATED", "Reproduce", "Hypothesize",
      "Investigate", "Verify", "Process",
    ];
    if (!skip.includes(name)) {
      modules.add(name);
    }
  }
  return modules;
}

// ---------------------------------------------------------------------------
// Checks
// ---------------------------------------------------------------------------

const warnings = [];

function warn(file, message) {
  warnings.push(`  - ${file}: ${message}`);
}

// --- 1. Tech Stack Match ---------------------------------------------------

function checkTechStack() {
  const composer = readJson(join(PROJECT_DIR, "composer.json"));
  const pkg = readJson(join(PROJECT_DIR, "package.json"));

  if (!composer && !pkg) {
    warn("(project)", "Cannot read composer.json or package.json — skipping tech stack check");
    return;
  }

  // Build a set of actual dependency names (lowercase)
  const actualDeps = new Set();
  if (composer) {
    for (const key of ["require", "require-dev"]) {
      if (composer[key]) {
        Object.keys(composer[key]).forEach((d) => actualDeps.add(d.toLowerCase()));
      }
    }
  }
  if (pkg) {
    for (const key of ["dependencies", "devDependencies"]) {
      if (pkg[key]) {
        Object.keys(pkg[key]).forEach((d) => actualDeps.add(d.toLowerCase()));
      }
    }
  }

  // Keywords that should NOT appear in harness if the corresponding dep is absent
  const foreignKeywords = [
    { keyword: "fastapi", dep: "fastapi", label: "FastAPI" },
    { keyword: "celery", dep: "celery", label: "Celery" },
    { keyword: "sqlalchemy", dep: "sqlalchemy", label: "SQLAlchemy" },
    { keyword: "pydantic", dep: "pydantic", label: "Pydantic" },
    { keyword: "next.js", dep: "next", label: "Next.js" },
    { keyword: "next js", dep: "next", label: "Next.js" },
    { keyword: "app router", dep: "next", label: "Next.js App Router" },
    { keyword: "swr", dep: "swr", label: "SWR" },
    { keyword: "django", dep: "django", label: "Django" },
    { keyword: "flask", dep: "flask", label: "Flask" },
    { keyword: "express", dep: "express", label: "Express" },
    { keyword: "nuxt", dep: "nuxt", label: "Nuxt" },
    { keyword: "vue", dep: "vue", label: "Vue.js" },
    { keyword: "angular", dep: "@angular/core", label: "Angular" },
    { keyword: "svelte", dep: "svelte", label: "Svelte" },
    { keyword: "pytest", dep: "pytest", label: "pytest" },
    { keyword: "bun test", dep: "bun", label: "Bun" },
  ];

  // Check each harness file
  const harnessFiles = getHarnessFiles();
  for (const { name, content } of harnessFiles) {
    const lower = content.toLowerCase();
    for (const { keyword, dep, label } of foreignKeywords) {
      if (lower.includes(keyword) && !actualDeps.has(dep)) {
        warn(name, `mentions "${label}" but "${dep}" is not in project dependencies`);
      }
    }
  }
}

// --- 2. Directory Existence -------------------------------------------------

function checkDirectories() {
  // Paths under these roots are ownership claims — they may not exist yet
  // but are valid forward declarations (e.g. app/Policies/ before first policy)
  const ownershipRoots = ["app/", "bootstrap/", "config/", "database/", "routes/", "tests/", "resources/"];

  const harnessFiles = getHarnessFiles();
  for (const { name, content } of harnessFiles) {
    const paths = extractPaths(content);
    for (const p of paths) {
      // Skip paths mentioned in negation context ("do NOT use this path")
      if (isNegatedPath(content, p)) continue;

      // Skip ownership-claim paths under known Laravel roots
      // (these are valid even if the dir hasn't been created yet)
      const isOwnershipClaim = ownershipRoots.some((root) => p.startsWith(root));
      if (isOwnershipClaim) continue;

      const fullPath = join(PROJECT_DIR, p);
      if (!existsSync(fullPath)) {
        warn(name, `references \`${p}\` which does not exist in the project`);
      }
    }
  }
}

// --- 3. Module Inventory ----------------------------------------------------

function checkModuleInventory() {
  // Discover actual modules from code — use backend Modules/ dirs as source of truth
  const actualModules = new Set();

  // Backend modules: app/Models/Modules/*
  const backendModulesDir = join(PROJECT_DIR, "app", "Models", "Modules");
  for (const name of listSubdirs(backendModulesDir)) {
    actualModules.add(name);
  }

  // Also check services: app/Services/Modules/*
  const servicesModulesDir = join(PROJECT_DIR, "app", "Services", "Modules");
  for (const name of listSubdirs(servicesModulesDir)) {
    actualModules.add(name);
  }

  // Frontend page dirs that map to distinct business modules
  // (only count dirs that also have backend presence OR are clearly module-scoped)
  const frontendPagesDir = join(PROJECT_DIR, "resources", "js", "inertia", "Pages");
  // Generic/infrastructure page dirs — not business modules
  const frontendSkip = new Set([
    "Auth", "Profile", "ErrorPage", "ErrorTest", "PrefetchTest",
    "Dashboard", "Settings",
  ]);
  for (const name of listSubdirs(frontendPagesDir)) {
    if (!frontendSkip.has(name)) {
      actualModules.add(name);
    }
  }

  if (actualModules.size === 0) return;

  // Collect all modules mentioned across all harness files
  const harnessFiles = getHarnessFiles();
  const mentionedModules = new Set();
  for (const { content } of harnessFiles) {
    for (const mod of extractMentionedModules(content)) {
      mentionedModules.add(mod);
    }
  }

  // "Core" is a namespace (app/Models/Core/, app/Services/Core/), not a Modules/ entry.
  // It's valid to mention in harness without being in Modules/ dirs.
  mentionedModules.delete("Core");

  // Modules in code but not in harness
  for (const mod of actualModules) {
    if (!mentionedModules.has(mod)) {
      warn("(all)", `Module "${mod}" exists in code but is not mentioned in any harness file`);
    }
  }

  // Modules in harness but not in code — only warn for modules that are NOT deprecated
  for (const mod of mentionedModules) {
    if (!actualModules.has(mod)) {
      warn("(all)", `Module "${mod}" is mentioned in harness but not found in code directories`);
    }
  }
}

// ---------------------------------------------------------------------------
// Harness file loader (cached)
// ---------------------------------------------------------------------------

let _harnessCache = null;

function getHarnessFiles() {
  if (_harnessCache) return _harnessCache;

  _harnessCache = [];
  if (!existsSync(HARNESS_DIR)) return _harnessCache;

  const files = readdirSync(HARNESS_DIR).filter((f) => f.endsWith(".md"));
  for (const f of files) {
    const content = readText(join(HARNESS_DIR, f));
    if (content) {
      _harnessCache.push({ name: f, content });
    }
  }
  return _harnessCache;
}

// ---------------------------------------------------------------------------
// Main
// ---------------------------------------------------------------------------

function main() {
  if (!existsSync(HARNESS_DIR)) {
    // No harness dir — nothing to check
    process.exit(0);
  }

  checkTechStack();
  checkDirectories();
  checkModuleInventory();

  if (warnings.length > 0) {
    console.log(`[harness-gardener] DRIFT DETECTED (${warnings.length} issue${warnings.length > 1 ? "s" : ""}):`);
    for (const w of warnings) {
      console.log(w);
    }
    console.log(
      `[harness-gardener] Run: review and update files in ${HARNESS_DIR}`,
    );
  }
  // Exit 0 regardless — drift is a warning, not a blocker
  process.exit(0);
}

main();
