"use client";

import Link from "next/link";
import Image from "next/image";
import { usePathname } from "next/navigation";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import { useSidebarStore } from "@/store/sidebar-store";
import { useAuthStore } from "@/store/auth-store";
import { navigation } from "@/constants/navigation";
import type { NavItem as NavItemType } from "@/constants/navigation";
import {
  ChevronDown,
  PanelLeftClose,
  PanelLeft,
} from "lucide-react";
import { useState, useMemo, useEffect, useRef } from "react";

function filterNavByRole(
  items: NavItemType[],
  roleSlug: string | null | undefined
): NavItemType[] {
  return items
    .filter((item) => {
      if (item.roles && roleSlug && !item.roles.includes(roleSlug)) {
        return false;
      }
      return true;
    })
    .map((item) => {
      if (item.children) {
        return {
          ...item,
          children: filterNavByRole(item.children, roleSlug),
        };
      }
      return item;
    })
    .filter((item) => {
      if (item.children && item.children.length === 0) {
        return false;
      }
      return true;
    });
}

function NavItem({
  item,
  collapsed,
  onNav,
}: {
  item: NavItemType;
  collapsed: boolean;
  onNav: () => void;
}) {
  const pathname = usePathname();
  const isChildActive = useMemo(
    () => item.children?.some((child) => pathname.startsWith(child.href ?? "/")) ?? false,
    [item.children, pathname]
  );
  const [manualExpanded, setManualExpanded] = useState(false);
  const [userCollapsed, setUserCollapsed] = useState(false);
  const prevIsChildActiveRef = useRef(isChildActive);
  const expanded = isChildActive ? !userCollapsed : manualExpanded;

  useEffect(() => {
    if (isChildActive && !prevIsChildActiveRef.current) {
      setUserCollapsed(false);
    }
    prevIsChildActiveRef.current = isChildActive;
  }, [isChildActive]);

  const isActive = item.href
    ? pathname === item.href
    : false;

  if (item.children) {
    return (
      <div>
        {!collapsed && (
          <button
            onClick={() => {
              if (isChildActive) {
                setUserCollapsed(!userCollapsed);
              } else {
                setManualExpanded(!manualExpanded);
              }
            }}
            className={cn(
              "flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-colors hover:bg-muted",
              expanded && "text-foreground"
            )}
          >
            {item.icon && <item.icon className="size-4 shrink-0" />}
            <span className="flex-1 text-left">{item.title}</span>
            <ChevronDown
              className={cn(
                "size-3.5 transition-transform",
                expanded && "rotate-180"
              )}
            />
          </button>
        )}
        {expanded && (
          <div className={cn("space-y-1", !collapsed && "ml-4 mt-1")}>
            {item.children.map((child) => (
              <NavItem
                key={child.href}
                item={child}
                collapsed={false}
                onNav={onNav}
              />
            ))}
          </div>
        )}
      </div>
    );
  }

  return (
    <Link
      href={item.href ?? "#"}
      onClick={onNav}
      className={cn(
        "flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-colors hover:bg-muted",
        isActive
          ? "bg-primary/10 text-primary hover:bg-primary/15"
          : "text-muted-foreground hover:text-foreground"
      )}
    >
      {item.icon && <item.icon className="size-4 shrink-0" />}
      {!collapsed && <span>{item.title}</span>}
    </Link>
  );
}

export function Sidebar() {
  const { isCollapsed, toggle, isMobileOpen, setMobileOpen } =
    useSidebarStore();
  const user = useAuthStore((state) => state.user);
  const roleSlug = user?.role?.slug;

  const filteredNavigation = useMemo(
    () => filterNavByRole(navigation, roleSlug),
    [roleSlug]
  );

  return (
    <>
      {isMobileOpen && (
        <div
          className="fixed inset-0 z-40 bg-black/50 lg:hidden"
          onClick={() => setMobileOpen(false)}
        />
      )}

      <aside
        className={cn(
          "fixed left-0 top-0 z-50 flex h-screen flex-col border-r bg-background transition-all duration-300 lg:static lg:z-auto",
          isCollapsed ? "w-16" : "w-64",
          isMobileOpen ? "translate-x-0" : "-translate-x-full lg:translate-x-0"
        )}
      >
        <div className="flex h-14 items-center gap-2 border-b px-4">
          <Image src="/logo.png" alt="Bongobiponi" width={32} height={32} className="shrink-0" />
          {!isCollapsed && (
            <span className="text-lg font-bold tracking-tight">Bongobiponi</span>
          )}
          <div className="flex-1" />
          <Button
            variant="ghost"
            size="icon"
            onClick={toggle}
            className="hidden lg:inline-flex"
          >
            {isCollapsed ? (
              <PanelLeft className="size-4" />
            ) : (
              <PanelLeftClose className="size-4" />
            )}
          </Button>
        </div>

        <nav className="flex-1 space-y-1 overflow-y-auto p-2">
          {filteredNavigation.map((item) => (
            <NavItem
              key={item.title}
              item={item}
              collapsed={isCollapsed}
              onNav={() => setMobileOpen(false)}
            />
          ))}
        </nav>

        <div className="border-t p-2">
          {!isCollapsed && (
            <p className="px-3 text-xs text-muted-foreground">
              BongoBiponi v1.0
            </p>
          )}
        </div>
      </aside>
    </>
  );
}
