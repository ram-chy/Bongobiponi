"use client";

import { Sidebar } from "@/components/layout/sidebar";
import { Navbar } from "@/components/layout/navbar";
import { LoadingBar } from "@/components/loaders/loading-bar";
import { CommandPalette } from "@/components/command-palette/command-palette";
import { Toaster } from "sonner";

export function DashboardLayout({ children }: { children: React.ReactNode }) {
  return (
    <div className="flex min-h-screen">
      <LoadingBar />
      <CommandPalette />
      <Sidebar />
      <div className="flex flex-1 flex-col">
        <Navbar />
        <main className="flex-1 p-4 lg:p-6">{children}</main>
      </div>
      <Toaster richColors position="top-right" closeButton />
    </div>
  );
}
