import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { FitAddon } from '@xterm/addon-fit';
import { Terminal } from '@xterm/xterm';
import '@xterm/xterm/css/xterm.css';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Server } from '@/types/server';
import { LoaderCircleIcon, RefreshCwIcon, SquareIcon, Trash2Icon } from 'lucide-react';

export default function WebTerminal({ server, defaultUser = 'root' }: { server: Server; defaultUser?: string }) {
  const page = usePage<{ csrf_token: string }>();
  const [user, setUser] = useState(server.ssh_users.includes(defaultUser) ? defaultUser : server.ssh_user);
  const [dir, setDir] = useState(user === 'root' ? '/root' : `/home/${user}`);
  const [running, setRunning] = useState(false);
  const [commandHistory, setCommandHistory] = useState<string[]>([]);
  const [historyIndex, setHistoryIndex] = useState(-1);

  const terminalElementRef = useRef<HTMLDivElement>(null);
  const abortControllerRef = useRef<AbortController | null>(null);
  const terminalRef = useRef<Terminal | null>(null);
  const fitAddonRef = useRef<FitAddon | null>(null);
  const currentCommandRef = useRef('');

  const shellPrefix = useMemo(() => `${user}@${server.name}:${dir}$`, [user, server.name, dir]);

  const writePrompt = useCallback(() => {
    terminalRef.current?.write(`\r\n\u001b[1;32m${shellPrefix}\u001b[0m `);
  }, [shellPrefix]);

  const focusTerminal = () => {
    terminalRef.current?.focus();
  };

  const getWorkingDir = useCallback(async (selectedUser: string) => {
    const response = await fetch(route('console.working-dir', { server: server.id, user: selectedUser }));

    if (!response.ok) {
      const fallback = selectedUser === 'root' ? '/root' : `/home/${selectedUser}`;
      setDir(fallback);

      return fallback;
    }

    const data = await response.json();
    setDir(data.dir);

    return data.dir as string;
  }, [server.id]);

  useEffect(() => {
    getWorkingDir(user).finally(() => focusTerminal());
  }, [getWorkingDir, user]);

  useEffect(() => {
    if (!terminalElementRef.current || terminalRef.current) {
      return;
    }

    const terminal = new Terminal({
      cursorBlink: true,
      fontFamily: 'Consolas, Menlo, Monaco, "Courier New", monospace',
      fontSize: 14,
      lineHeight: 1.3,
      scrollback: 5000,
      theme: {
        background: '#020817',
        foreground: '#e2e8f0',
        cursor: '#f8fafc',
        black: '#0f172a',
        brightBlack: '#334155',
      },
    });

    const fitAddon = new FitAddon();
    terminal.loadAddon(fitAddon);
    terminal.open(terminalElementRef.current);
    fitAddon.fit();
    terminalRef.current = terminal;
    fitAddonRef.current = fitAddon;

    terminal.writeln(`Connected to ${server.name} as ${user}.`);
    terminal.write(`\u001b[1;32m${shellPrefix}\u001b[0m `);

    const handleResize = () => fitAddon.fit();
    window.addEventListener('resize', handleResize);

    const disposable = terminal.onData((data) => {
      if (running) {
        return;
      }

      switch (data) {
        case '\r': {
          const command = currentCommandRef.current.trim();
          terminal.write('\r\n');

          if (command === '') {
            writePrompt();
            currentCommandRef.current = '';
            return;
          }

          setCommandHistory((previous) => previous.filter((item) => item !== command).concat(command));
          setHistoryIndex(-1);
          void run(command);
          currentCommandRef.current = '';
          return;
        }
        case '\u007F': {
          if (currentCommandRef.current.length > 0) {
            currentCommandRef.current = currentCommandRef.current.slice(0, -1);
            terminal.write('\b \b');
          }
          return;
        }
        case '\u001b[A': {
          setHistoryIndex((previous) => {
            if (commandHistory.length === 0) {
              return previous;
            }

            const nextIndex = previous === -1 ? commandHistory.length - 1 : Math.max(0, previous - 1);
            const nextCommand = commandHistory[nextIndex] ?? '';

            while (currentCommandRef.current.length > 0) {
              terminal.write('\b \b');
              currentCommandRef.current = currentCommandRef.current.slice(0, -1);
            }

            currentCommandRef.current = nextCommand;
            terminal.write(nextCommand);

            return nextIndex;
          });
          return;
        }
        case '\u001b[B': {
          setHistoryIndex((previous) => {
            if (previous < 0) {
              return previous;
            }

            const nextIndex = previous + 1;
            const nextCommand = nextIndex >= commandHistory.length ? '' : (commandHistory[nextIndex] ?? '');

            while (currentCommandRef.current.length > 0) {
              terminal.write('\b \b');
              currentCommandRef.current = currentCommandRef.current.slice(0, -1);
            }

            currentCommandRef.current = nextCommand;
            terminal.write(nextCommand);

            return nextIndex >= commandHistory.length ? -1 : nextIndex;
          });
          return;
        }
        case '\u0003': {
          if (running) {
            stop();
            return;
          }

          terminal.write('^C');
          currentCommandRef.current = '';
          writePrompt();
          return;
        }
        default: {
          if (data >= ' ' && data !== '\u007f') {
            currentCommandRef.current += data;
            terminal.write(data);
          }
        }
      }
    });

    focusTerminal();

    return () => {
      disposable.dispose();
      window.removeEventListener('resize', handleResize);
      terminal.dispose();
      terminalRef.current = null;
      fitAddonRef.current = null;
    };
  }, [commandHistory, running, server.name, shellPrefix, user, writePrompt]);

  useEffect(() => {
    if (!terminalRef.current) {
      return;
    }

    terminalRef.current.focus();
  }, [user, dir]);

  const addToHistory = (value: string) => {
    setCommandHistory((previous) => previous.filter((item) => item !== value).concat(value));
    setHistoryIndex(-1);
  };

  const run = async (currentCommand: string) => {
    if (!currentCommand.trim() || running) {
      return;
    }

    setRunning(true);
    addToHistory(currentCommand);
    abortControllerRef.current = new AbortController();

    try {
      const response = await fetch(route('console.run', { server: server.id }), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': page.props.csrf_token,
        },
        body: JSON.stringify({ user, command: currentCommand }),
        signal: abortControllerRef.current.signal,
      });

      if (!response.body) {
        return;
      }

      const reader = response.body.getReader();
      const decoder = new TextDecoder('utf-8');

      while (true) {
        const { value, done } = await reader.read();
        if (done) {
          break;
        }

        terminalRef.current?.write(decoder.decode(value, { stream: true }).replace(/\n/g, '\r\n'));
      }

      await getWorkingDir(user);
    } catch (error) {
      if (!(error instanceof Error && error.name === 'AbortError')) {
        terminalRef.current?.write('\r\nError executing command\r\n');
      }
    } finally {
      setRunning(false);
      abortControllerRef.current = null;
      writePrompt();
      focusTerminal();
    }
  };

  const stop = () => {
    abortControllerRef.current?.abort();
  };

  const newSession = async () => {
    await fetch(route('console.new-session', { server: server.id, user }));
    terminalRef.current?.clear();
    terminalRef.current?.writeln(`Connected to ${server.name} as ${user}.`);
    await getWorkingDir(user);
    currentCommandRef.current = '';
    writePrompt();
    focusTerminal();
  };

  return (
    <div className="overflow-hidden rounded-xl border bg-black shadow-sm">
      <div className="bg-muted/50 flex items-center justify-between border-b px-4 py-3">
        <div>
          <div className="text-sm font-semibold">Web SSH Terminal</div>
          <div className="text-muted-foreground text-xs">Dedicated SSH shell, defaulting to root.</div>
        </div>

        <div className="flex items-center gap-2">
          <Select value={user} onValueChange={setUser} disabled={running}>
            <SelectTrigger className="w-32">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              {server.ssh_users.map((sshUser) => (
                <SelectItem key={sshUser} value={sshUser}>
                  {sshUser}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>

          <Button variant="outline" size="icon" onClick={() => terminalRef.current?.clear()} disabled={running}>
            <Trash2Icon className="h-4 w-4" />
          </Button>
          <Button variant="outline" size="icon" onClick={newSession} disabled={running}>
            <RefreshCwIcon className="h-4 w-4" />
          </Button>
          {running && (
            <Button variant="destructive" size="icon" onClick={stop}>
              <SquareIcon className="h-4 w-4" />
            </Button>
          )}
        </div>
      </div>

      <div className="relative border-t">
        <div ref={terminalElementRef} className="h-[70vh] w-full p-4" />
        {running && (
          <div className="pointer-events-none absolute top-3 right-3 flex items-center gap-2 rounded-md bg-slate-900/80 px-3 py-1 text-xs text-white">
            <LoaderCircleIcon className="h-4 w-4 animate-spin" />
            Running...
          </div>
        )}
      </div>
    </div>
  );
}