import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { Editor } from '@monaco-editor/react';
import { FolderPlusIcon, FilePlusIcon, LoaderCircleIcon, ChevronRightIcon } from 'lucide-react';
import ServerLayout from '@/layouts/server/layout';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/data-table';
import { PaginatedData } from '@/types';
import { Server } from '@/types/server';
import { ServerFile } from '@/types/file';
import { getColumns } from '@/pages/files/components/columns';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Form } from '@/components/ui/form';
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Sheet, SheetClose, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { Skeleton } from '@/components/ui/skeleton';
import { useAppearance } from '@/hooks/use-appearance';
import Refresh from '@/components/refresh';
import { FormEvent } from 'react';

type Page = {
  server: Server;
  currentPath: string;
  serverUser: string;
  files: PaginatedData<ServerFile>;
};

function CreateDirectoryDialog({ server, currentPath, serverUser }: { server: Server; currentPath: string; serverUser: string }) {
  const [open, setOpen] = useState(false);
  const form = useForm({
    path: currentPath,
    server_user: serverUser,
    name: '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(route('server-files.directories.store', { server: server.id }), {
      preserveScroll: true,
      onSuccess: () => {
        setOpen(false);
        form.reset('name');
      },
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button variant="outline">
          <FolderPlusIcon />
          New folder
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Create folder</DialogTitle>
          <DialogDescription>Create a new directory in the current path.</DialogDescription>
        </DialogHeader>
        <Form className="space-y-4" onSubmit={submit}>
          <div className="space-y-2">
            <Label htmlFor="directory-name">Folder name</Label>
            <Input id="directory-name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
            <InputError message={form.errors.name} />
          </div>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button type="submit" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Create
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function CreateFileDialog({ server, currentPath, serverUser }: { server: Server; currentPath: string; serverUser: string }) {
  const [open, setOpen] = useState(false);
  const form = useForm({
    path: currentPath,
    server_user: serverUser,
    name: '',
    content: '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(route('server-files.store', { server: server.id }), {
      preserveScroll: true,
      onSuccess: () => {
        setOpen(false);
        form.reset('name', 'content');
      },
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button>
          <FilePlusIcon />
          New file
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-2xl">
        <DialogHeader>
          <DialogTitle>Create file</DialogTitle>
          <DialogDescription>Create a new file in the current path.</DialogDescription>
        </DialogHeader>
        <Form className="space-y-4" onSubmit={submit}>
          <div className="space-y-2">
            <Label htmlFor="file-name">File name</Label>
            <Input id="file-name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
            <InputError message={form.errors.name} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="file-content">Initial content</Label>
            <textarea
              id="file-content"
              className="border-input min-h-48 w-full rounded-md border bg-transparent px-3 py-2 text-sm"
              value={form.data.content}
              onChange={(e) => form.setData('content', e.target.value)}
            />
            <InputError message={form.errors.content} />
          </div>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button type="submit" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Create
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function FileEditor({ file, server, onClose, serverUser }: { file: ServerFile | null; server: Server; onClose: () => void; serverUser: string }) {
  const { getActualAppearance } = useAppearance();
  const form = useForm({
    path: file?.file_path || '',
    server_user: serverUser,
    content: '',
  });

  const query = useQuery({
    queryKey: ['server-files.content', server.id, file?.file_path],
    queryFn: async () => {
      const response = await axios.get(route('server-files.content', { server: server.id, path: file?.file_path }));
      form.setData('content', response.data.content || '');
      form.setData('path', file?.file_path || '');
      form.setData('server_user', file?.server_user || serverUser);
      return response.data;
    },
    enabled: !!file,
    retry: false,
    refetchOnWindowFocus: false,
  });

  const save = (e: FormEvent) => {
    e.preventDefault();
    form.patch(route('server-files.update', { server: server.id }), {
      preserveScroll: true,
      onSuccess: () => onClose(),
    });
  };

  return (
    <Sheet open={!!file} onOpenChange={(open) => !open && onClose()}>
      <SheetContent className="sm:max-w-5xl">
        <SheetHeader>
          <SheetTitle>{file?.name}</SheetTitle>
          <SheetDescription>{file?.file_path}</SheetDescription>
        </SheetHeader>
        <Form id="file-editor-form" className="h-full" onSubmit={save}>
          {query.isSuccess ? (
            <Editor
              value={form.data.content}
              defaultLanguage="plaintext"
              theme={getActualAppearance() === 'dark' ? 'vs-dark' : 'vs'}
              className="h-full"
              onChange={(value) => form.setData('content', value ?? '')}
              options={{ fontSize: 15 }}
            />
          ) : (
            <Skeleton className="h-full w-full rounded-none" />
          )}
        </Form>
        <SheetFooter>
          <div className="flex items-center gap-2">
            <Button form="file-editor-form" type="submit" disabled={form.processing || query.isLoading}>
              {(form.processing || query.isLoading) && <LoaderCircleIcon className="animate-spin" />}
              Save
            </Button>
            <SheetClose asChild>
              <Button variant="outline">Cancel</Button>
            </SheetClose>
          </div>
        </SheetFooter>
      </SheetContent>
    </Sheet>
  );
}

export default function Files() {
  const page = usePage<Page>();
  const [selectedFile, setSelectedFile] = useState<ServerFile | null>(null);
  const columns = useMemo(() => getColumns({ onOpenEditor: setSelectedFile }), []);

  const homePath = (user: string) => (user === 'root' ? '/root' : `/home/${user}`);

  const pathSegments = useMemo(() => page.props.currentPath.split('/').filter(Boolean), [page.props.currentPath]);

  const breadcrumbs = pathSegments.map((segment, index) => {
    const path = `/${pathSegments.slice(0, index + 1).join('/')}`;
    return {
      label: segment,
      href: route('server-files', { server: page.props.server.id, path, server_user: page.props.serverUser }),
    };
  });

  return (
    <ServerLayout>
      <Head title={`Files - ${page.props.server.name}`} />

      <Container className="max-w-7xl">
        <HeaderContainer>
          <div className="space-y-1">
            <h2 className="text-xl font-semibold tracking-tight">File Manager</h2>
            <div className="text-muted-foreground flex flex-wrap items-center gap-1 text-sm">
              <span>User:</span>
              <span className="font-medium">{page.props.serverUser}</span>
              <span>•</span>
              <span>Path:</span>
              <Link
                href={route('server-files', {
                  server: page.props.server.id,
                  path: page.props.currentPath,
                  server_user: page.props.serverUser,
                })}
                className="font-medium hover:underline"
              >
                {page.props.currentPath}
              </Link>
            </div>
            <div className="flex flex-wrap items-center gap-1 text-sm">
              <Link
                href={route('server-files', {
                  server: page.props.server.id,
                  path: '/',
                  server_user: page.props.serverUser,
                })}
                className="hover:underline"
              >
                /
              </Link>
              {breadcrumbs.map((item) => (
                <span key={item.href} className="flex items-center gap-1">
                  <ChevronRightIcon className="h-3 w-3" />
                  <Link href={item.href} className="hover:underline">
                    {item.label}
                  </Link>
                </span>
              ))}
            </div>
          </div>

          <div className="flex flex-wrap items-center gap-2">
            <Select
              value={page.props.serverUser}
              onValueChange={(value) => {
                router.get(route('server-files', {
                  server: page.props.server.id,
                  server_user: value,
                  path: homePath(value),
                }));
              }}
            >
              <SelectTrigger className="w-[180px]">
                <SelectValue placeholder="Select user" />
              </SelectTrigger>
              <SelectContent>
                <SelectGroup>
                  {page.props.server.ssh_users.map((user) => (
                    <SelectItem key={user} value={user}>
                      {user}
                    </SelectItem>
                  ))}
                </SelectGroup>
              </SelectContent>
            </Select>
            <Refresh />
            <CreateDirectoryDialog server={page.props.server} currentPath={page.props.currentPath} serverUser={page.props.serverUser} />
            <CreateFileDialog server={page.props.server} currentPath={page.props.currentPath} serverUser={page.props.serverUser} />
          </div>
        </HeaderContainer>

        <DataTable
          columns={columns}
          paginatedData={page.props.files}
          searchable
          data={page.props.files.data}
        />
      </Container>

      <FileEditor file={selectedFile} server={page.props.server} serverUser={page.props.serverUser} onClose={() => setSelectedFile(null)} />
    </ServerLayout>
  );
}
