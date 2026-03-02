const fs = require("fs");
const path = "./resources/js/inertia/components/activity/TaskFormModal.tsx";
let content = fs.readFileSync(path, "utf8");

// Fallback exact replace to fix Rollup parser bug
content = content.replace("function TaskFormModal", "export function TaskFormModal");

// Remove duplicate export defaults just in case
content = content.replace("export default function TaskFormModal", "export function TaskFormModal");

fs.writeFileSync(path, content);
console.log("Fixed manually");

