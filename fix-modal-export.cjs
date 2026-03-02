const fs = require("fs");
const path = "./resources/js/inertia/components/activity/TaskFormModal.tsx";
let content = fs.readFileSync(path, "utf8");

// Fix the missing export issue
if (!content.includes("export function TaskFormModal")) {
    content = content.replace("function TaskFormModal", "export function TaskFormModal");
}
// Clean up any remaining export default from old file
if (content.includes("export default function TaskForm")) {
    content = content.replace("export default function TaskForm", "export function TaskFormModal");
}

fs.writeFileSync(path, content);
console.log("Fixed export syntax");

