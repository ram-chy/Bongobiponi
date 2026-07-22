"use client";

import { MoreHorizontal, Eye, Pencil, Trash2, Download } from "lucide-react";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { usePermissions } from "@/hooks/use-permissions";

interface RowActionsProps {
  viewUrl?: string;
  editUrl?: string;
  onView?: () => void;
  onEdit?: () => void;
  onDelete?: () => void;
  onDownloadPDF?: () => void;
  downloadLabel?: string;
  downloadPending?: boolean;
}

export function RowActions({
  viewUrl,
  editUrl,
  onView,
  onEdit,
  onDelete,
  onDownloadPDF,
  downloadLabel = "Download PDF",
  downloadPending = false,
}: RowActionsProps) {
  const { can } = usePermissions();
  const canEdit = can("update");
  const canDelete = can("delete");

  const hasActions = [viewUrl || onView, (editUrl || onEdit) && canEdit, onDownloadPDF, onDelete && canDelete].some(Boolean);
  if (!hasActions) return null;

  return (
    <DropdownMenu>
      <DropdownMenuTrigger className="flex size-8 items-center justify-center rounded-md hover:bg-muted">
        <MoreHorizontal className="size-4" />
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end">
        {(viewUrl || onView) && (
          <DropdownMenuItem onClick={onView}>
            <Eye className="size-4" />
            View
          </DropdownMenuItem>
        )}
        {(editUrl || onEdit) && canEdit && (
          <DropdownMenuItem onClick={onEdit}>
            <Pencil className="size-4" />
            Edit
          </DropdownMenuItem>
        )}
        {onDownloadPDF && (
          <DropdownMenuItem onClick={onDownloadPDF} disabled={downloadPending}>
            <Download className="size-4" />
            {downloadLabel}
          </DropdownMenuItem>
        )}
        {onDelete && canDelete && (
          <DropdownMenuItem variant="destructive" onClick={onDelete}>
            <Trash2 className="size-4" />
            Delete
          </DropdownMenuItem>
        )}
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
