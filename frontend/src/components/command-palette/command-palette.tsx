"use client";

import { useEffect, useState, useCallback } from "react";
import { useRouter } from "next/navigation";
import {
  Dialog,
  DialogContent,
} from "@/components/ui/dialog";
import { navigation } from "@/constants/navigation";
import { Search } from "lucide-react";

interface FlatItem {
  title: string;
  href: string;
}

function flattenNav(items: typeof navigation): FlatItem[] {
  const result: FlatItem[] = [];
  for (const item of items) {
    if (item.href) {
      result.push({ title: item.title, href: item.href });
    }
    if (item.children) {
      result.push(...flattenNav(item.children));
    }
  }
  return result;
}

const allItems = flattenNav(navigation);

export function CommandPalette() {
  const router = useRouter();
  const [open, setOpen] = useState(false);
  const [query, setQuery] = useState("");

  const filtered = query
    ? allItems.filter((item) =>
        item.title.toLowerCase().includes(query.toLowerCase())
      )
    : allItems;

  const handleSelect = useCallback(
    (href: string) => {
      setOpen(false);
      setQuery("");
      router.push(href);
    },
    [router]
  );

  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if ((e.ctrlKey || e.metaKey) && e.key === "k") {
        e.preventDefault();
        setOpen((prev) => !prev);
      }
    };
    window.addEventListener("keydown", handleKeyDown);
    return () => window.removeEventListener("keydown", handleKeyDown);
  }, []);

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogContent className="sm:max-w-lg gap-0 p-0 overflow-hidden">
        <div className="flex items-center gap-2 border-b px-4 py-3">
          <Search className="size-4 text-muted-foreground shrink-0" />
          <input
            autoFocus
            placeholder="Search pages..."
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            className="flex-1 bg-transparent text-sm outline-none placeholder:text-muted-foreground"
          />
          <kbd className="rounded border bg-muted px-1.5 py-0.5 text-[10px] font-medium text-muted-foreground">
            ESC
          </kbd>
        </div>
        <div className="max-h-80 overflow-y-auto p-2">
          {filtered.length === 0 && (
            <p className="py-6 text-center text-sm text-muted-foreground">
              No results found.
            </p>
          )}
          {filtered.map((item) => (
            <button
              key={item.href}
              onClick={() => handleSelect(item.href)}
              className="flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm hover:bg-muted transition-colors text-left"
            >
              {item.title}
            </button>
          ))}
        </div>
      </DialogContent>
    </Dialog>
  );
}
