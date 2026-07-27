"use client";

import { useTheme } from "next-themes";
import { useAuthStore } from "@/store/auth-store";
import { useSidebarStore } from "@/store/sidebar-store";
import { useLogout } from "@/features/auth/hooks/use-logout";
import { Button } from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import {
  Sun,
  Moon,
  Menu,
  LogOut,
  User,
  Bell,
  Search,
} from "lucide-react";

export function Navbar() {
  const { theme, setTheme } = useTheme();
  const user = useAuthStore((state) => state.user);
  const setMobileOpen = useSidebarStore((state) => state.setMobileOpen);
  const logoutMutation = useLogout();

  return (
    <header className="flex h-14 items-center gap-4 border-b bg-background px-4 lg:px-6">
      <Button
        variant="ghost"
        size="icon"
        className="lg:hidden"
        onClick={() => setMobileOpen(true)}
        aria-label="Toggle menu"
      >
        <Menu className="size-5" />
      </Button>

      <div className="flex items-center gap-2 text-sm text-muted-foreground">
        <Search className="size-4" />
        <span className="hidden sm:inline">Search...</span>
        <kbd className="hidden rounded border bg-muted px-1.5 text-[10px] font-medium text-muted-foreground sm:inline-block">
          CTRL+K
        </kbd>
      </div>

      <div className="flex-1" />

      <Button variant="ghost" size="icon" className="relative" aria-label="Notifications">
        <Bell className="size-5" />
        <span className="absolute right-1.5 top-1.5 size-2 rounded-full bg-destructive" />
      </Button>

      <Button
        variant="ghost"
        size="icon"
        onClick={() => setTheme(theme === "dark" ? "light" : "dark")}
        aria-label="Toggle theme"
      >
        {theme === "dark" ? (
          <Sun className="size-5" />
        ) : (
          <Moon className="size-5" />
        )}
      </Button>

      <DropdownMenu>
        <DropdownMenuTrigger className="flex size-8 items-center justify-center rounded-full bg-primary text-primary-foreground text-sm font-medium hover:bg-primary/90 cursor-pointer">
          {user?.first_name?.[0]}
          {user?.last_name?.[0]}
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" className="w-56">
          <DropdownMenuLabel>
            <div className="flex flex-col">
              <span>
                {user?.first_name} {user?.last_name}
              </span>
              <span className="text-xs font-normal text-muted-foreground">
                {user?.email}
              </span>
              {user?.role && (
                <span className="mt-1 text-xs font-medium text-primary">
                  {user.role.name}
                </span>
              )}
            </div>
          </DropdownMenuLabel>
          <DropdownMenuSeparator />
          <DropdownMenuItem>
            <User className="mr-2 size-4" />
            Profile
          </DropdownMenuItem>
          <DropdownMenuSeparator />
          <DropdownMenuItem
            onClick={() => logoutMutation.mutate()}
            disabled={logoutMutation.isPending}
          >
            <LogOut className="mr-2 size-4" />
            Logout
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </header>
  );
}
