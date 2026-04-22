import { spawn } from 'node:child_process';
import { rmSync } from 'node:fs';
import { resolve } from 'node:path';

const hotFile = resolve(process.cwd(), 'public', 'hot');
const forwardedArgs = process.argv.slice(2);

const cleanupHotFile = () => {
    try {
        rmSync(hotFile, { force: true });
    } catch {
        // Ignore stale hot cleanup failures during local startup/shutdown.
    }
};

cleanupHotFile();

const command = ['npm run dev:server', ...forwardedArgs].join(' ').trim();

const child = process.platform === 'win32'
    ? spawn('cmd.exe', ['/d', '/s', '/c', command], {
        cwd: process.cwd(),
        stdio: 'inherit',
        env: process.env,
        windowsHide: true,
    })
    : spawn(command, {
        cwd: process.cwd(),
        stdio: 'inherit',
        env: process.env,
        shell: true,
    });

const forwardSignal = () => {
    if (!child.killed) {
        child.kill('SIGTERM');
    }
};

['SIGINT', 'SIGTERM', 'SIGHUP'].forEach((signal) => {
    process.on(signal, forwardSignal);
});

child.on('exit', (code, signal) => {
    cleanupHotFile();

    if (signal) {
        process.kill(process.pid, signal);
        return;
    }

    process.exit(code ?? 0);
});
