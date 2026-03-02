const fs = require('fs');
const filePath = './resources/js/inertia/Pages/Activity/Dashboard.tsx';
let content = fs.readFileSync(filePath, 'utf8');

if (!content.includes('TaskFormModal ')) {
    content = content.replace("import { TaskFormModal } from '@/components/activity/TaskFormModal';", "");
    content = content.replace("import type { PageProps", "import { TaskFormModal } from '@/components/activity/TaskFormModal';\nimport type { PageProps");
    fs.writeFileSync(filePath, content);
}
console.log('Dashboard imports verified');
