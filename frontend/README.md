# ERP Frontend

Enterprise ERP frontend built with Next.js (App Router), consuming a Laravel 13 REST API with JWT authentication.

## Tech Stack

- **Next.js 16** — App Router
- **React 19** — Server & Client Components
- **TypeScript** — strict mode
- **Tailwind CSS v4** — utility-first styling
- **shadcn/ui** — component library (base-ui primitives)
- **React Hook Form + Zod** — form validation
- **TanStack Query** — server state management
- **TanStack Table** — data tables
- **Zustand** — client state (auth, sidebar, theme)
- **Axios** — HTTP client with JWT interceptor
- **Sonner** — toast notifications
- **Lucide React** — icons
- **next-themes** — dark/light mode

## Getting Started

```bash
# Install dependencies
npm install

# Start development server
npm run dev
```

Open [http://localhost:3000](http://localhost:3000).

## Environment

Copy `.env.example` to `.env.local` and set the API URL:

```
NEXT_PUBLIC_API_URL=http://localhost:8000/api
```

## Project Structure

```
src/
  app/              # Next.js App Router pages
    (auth)/         #  Login
    (dashboard)/    #  Protected pages (dashboard, customers, etc.)
  components/
    ui/             # shadcn/ui primitives
    common/         # EntityPage, AuthGuard, GuestGuard
    layout/         # DashboardLayout, Navbar, Sidebar
    tables/         # DataTable
    page-header/    # PageHeader
    breadcrumb/     # PageBreadcrumb
    dialogs/        # ConfirmDialog
    loaders/        # LoadingBar
    command-palette/ # CTRL+K palette
  features/         # Feature-based modules
    auth/           # Login, logout
    customers/      # Full CRUD (reference implementation)
  hooks/            # Shared hooks (useEntityList, useEntityDelete)
  lib/              # Axios client, API error utils, cn()
  providers/        # Theme, Query providers
  services/         # API service layer
  store/            # Zustand stores (auth, sidebar, theme)
  types/            # TypeScript type definitions
  constants/        # Navigation config
```

## Architecture

- **Feature-based** — each business module lives in `src/features/<module>/`
- **EntityPage** — generic CRUD list page; modules supply only config
- **API calls** — centralized in `src/services/`; one axios instance
- **Auth** — JWT persisted in Zustand; axios interceptor auto-attaches token; 401 triggers logout
- **Forms** — React Hook Form + Zod; Laravel 422 errors mapped automatically to fields
- **Protected routes** — `AuthGuard` client component in dashboard layout
