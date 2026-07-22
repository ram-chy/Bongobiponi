"use client";

import { useState, useEffect } from "react";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Loader2 } from "lucide-react";
import { useUpdateRole } from "@/features/user-management/hooks/use-update-role";
import type { UserData, UserRole } from "@/types/user";

interface ChangeRoleDialogProps {
  user: UserData | null;
  roles: UserRole[];
  open: boolean;
  onOpenChange: (open: boolean) => void;
}

export function ChangeRoleDialog({
  user,
  roles,
  open,
  onOpenChange,
}: ChangeRoleDialogProps) {
  const [selectedRoleId, setSelectedRoleId] = useState<string>("");
  const updateRoleMutation = useUpdateRole();

  useEffect(() => {
    if (open && user) {
      setSelectedRoleId(String(user.role?.id ?? ""));
    }
  }, [open, user]);

  const handleSave = () => {
    if (!user || !selectedRoleId) return;
    updateRoleMutation.mutate(
      {
        userId: user.id,
        roleId: Number(selectedRoleId),
      },
      {
        onSuccess: () => {
          onOpenChange(false);
        },
      }
    );
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Change User Role</DialogTitle>
          <DialogDescription>
            Update role for {user?.first_name} {user?.last_name}
          </DialogDescription>
        </DialogHeader>
        <div className="space-y-2">
          <Label htmlFor="role">Role</Label>
          <Select
            value={selectedRoleId || null}
            onValueChange={(v) => setSelectedRoleId(v ?? "")}
            items={roles.map((role) => ({ value: String(role.id), label: role.name }))}
          >
            <SelectTrigger className="w-full">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              {roles.map((role) => (
                <SelectItem key={role.id} value={String(role.id)}>
                  {role.name}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
        <DialogFooter>
          <Button
            variant="outline"
            onClick={() => onOpenChange(false)}
            disabled={updateRoleMutation.isPending}
          >
            Cancel
          </Button>
          <Button
            onClick={handleSave}
            disabled={
              updateRoleMutation.isPending ||
              !selectedRoleId ||
              selectedRoleId === String(user?.role?.id)
            }
          >
            {updateRoleMutation.isPending && (
              <Loader2 className="animate-spin" />
            )}
            Save
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
