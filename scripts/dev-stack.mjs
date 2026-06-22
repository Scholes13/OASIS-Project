import { spawn } from 'node:child_process';
import { rmSync } from 'node:fs';
import { resolve } from 'node:path';

const hotFile = resolve(process.cwd(), 'public', 'hot');
const isWindows = process.platform === 'win32';

const cleanupHotFile = () => {
    try {
        rmSync(hotFile, { force: true });
    } catch {
        // Ignore stale hot cleanup failures during local startup/shutdown.
    }
};

const processes = [
    startProcess('server', 'php artisan serve'),
    startProcess('queue', 'php artisan queue:listen --tries=1'),
];

if (!isWindows) {
    processes.push(startProcess('logs', 'php artisan pail --timeout=0'));
}

processes.push(startProcess('vite', 'npm run dev'));

let shuttingDown = false;

cleanupHotFile();

for (const child of processes) {
    child.on('exit', (code) => {
        if (shuttingDown) {
            return;
        }

        if (code === 0) {
            return;
        }

        shuttingDown = true;
        stopAllChildren();
        cleanupHotFile();
        process.exit(code ?? 1);
    });
}

const gracefulShutdown = () => {
    if (shuttingDown) {
        return;
    }

    shuttingDown = true;
    stopAllChildren();
    cleanupHotFile();
    process.exit(0);
};

['SIGINT', 'SIGTERM', 'SIGHUP'].forEach((signal) => {
    process.on(signal, gracefulShutdown);
});

function startProcess(name, command) {
    return spawn(command, {
        cwd: process.cwd(),
        env: process.env,
        shell: true,
        stdio: 'inherit',
        windowsHide: true,
    }).on('spawn', () => {
        process.stdout.write(`[${name}] ${command}\n`);
    });
}

function stopAllChildren() {
    for (const child of processes) {
        if (!child.killed) {
            child.kill('SIGTERM');
        }
    }
}
