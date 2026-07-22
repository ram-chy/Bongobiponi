import {
  LayoutDashboard,
  Users,
  FileText,
  ShoppingCart,
  ClipboardList,
  Truck,
  Receipt,
  CreditCard,
  Wallet,
  Tag,
  BarChart3,
  Settings,
  Shield,
  BookOpen,
  PenTool,
  FolderTree,
  Building2,
  Warehouse,
  PackagePlus,
  SlidersHorizontal,
  AlertTriangle,
  ScrollText,
  type LucideIcon,
} from "lucide-react";

export interface NavItem {
  title: string;
  href?: string;
  icon?: LucideIcon;
  children?: NavItem[];
  roles?: string[];
}

export const navigation: NavItem[] = [
  {
    title: "Dashboard",
    href: "/dashboard",
    icon: LayoutDashboard,
  },
  {
    title: "Registration",
    children: [
      {
        title: "User Management",
        href: "/users",
        icon: Shield,
        roles: ["admin"],
      },
      {
        title: "Customers",
        href: "/customers",
        icon: Users,
      },
    ],
  },
  {
    title: "Master Data",
    children: [
      {
        title: "Suppliers",
        href: "/suppliers",
        icon: Truck,
      },
      {
        title: "Publishers",
        href: "/publishers",
        icon: Building2,
      },
      {
        title: "Authors",
        href: "/authors",
        icon: PenTool,
      },
      {
        title: "Categories",
        href: "/categories",
        icon: FolderTree,
      },
      {
        title: "Books",
        href: "/books",
        icon: BookOpen,
      },
    ],
  },
  {
    title: "Sales",
    children: [
      {
        title: "Receive Orders",
        href: "/receive-orders",
        icon: ClipboardList,
      },
      {
        title: "Purchases",
        href: "/purchases",
        icon: ShoppingCart,
      },
      {
        title: "Quotations",
        href: "/quotations",
        icon: FileText,
      },
      {
        title: "Order Booking",
        href: "/orders",
        icon: ShoppingCart,
      },
      {
        title: "Sales Orders",
        href: "/sales-orders",
        icon: ClipboardList,
      },
      {
        title: "Delivery Challans",
        href: "/delivery-challans",
        icon: Truck,
      },
      {
        title: "Invoices",
        href: "/invoices",
        icon: Receipt,
      },
      {
        title: "Payments",
        href: "/payments",
        icon: CreditCard,
      },
    ],
  },
  {
    title: "Inventory",
    children: [
      {
        title: "Dashboard",
        href: "/inventory",
        icon: Warehouse,
      },
      {
        title: "Current Stock",
        href: "/inventory/stock",
        icon: PackagePlus,
      },
      {
        title: "Inventory Ledger",
        href: "/inventory/ledger",
        icon: ScrollText,
      },
      {
        title: "Opening Stock",
        href: "/inventory/opening",
        icon: PackagePlus,
      },
      {
        title: "Stock Adjustment",
        href: "/inventory/adjustment",
        icon: SlidersHorizontal,
      },
      {
        title: "Damage Entry",
        href: "/inventory/damage",
        icon: AlertTriangle,
      },
    ],
  },
  {
    title: "Accounts",
    children: [
      {
        title: "Expenses",
        href: "/expenses",
        icon: Wallet,
      },
      {
        title: "Expense Categories",
        href: "/expense-categories",
        icon: Tag,
        roles: ["admin"],
      },
    ],
  },
  {
    title: "Reports",
    href: "/reports",
    icon: BarChart3,
  },
  {
    title: "Settings",
    href: "/settings",
    icon: Settings,
  },
];
