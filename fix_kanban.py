import re

with open('./resources/js/inertia/components/activity/KanbanBoard.tsx', 'r') as f:
    content = f.read()

# Pattern to remove the entire header div
# We search for {/* Header with Toggle */} until the next closing div before {/* Main Board Container */}
pattern = r'      {/\* Header with Toggle \*/}\n\s*<div className="mb-4 flex flex-col gap-3 px-1 sm:flex-row sm:items-center sm:justify-between">.*?</div>\n\n\s*{/\* Main Board Container \*/}'

new_content = re.sub(pattern, '      {/* Main Board Container */}', content, flags=re.DOTALL)

with open('./resources/js/inertia/components/activity/KanbanBoard.tsx', 'w') as f:
    f.write(new_content)

print("KanbanBoard updated")
