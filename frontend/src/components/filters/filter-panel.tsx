"use client";

import { X } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
interface FilterField {
  id: string;
  label: string;
  type: "select" | "date" | "text";
  value: string;
  onChange: (value: string) => void;
  options?: { label: string; value: string }[];
}

interface FilterPanelProps {
  fields: FilterField[];
  onClear: () => void;
  hasActiveFilters: boolean;
}

export function FilterPanel({ fields, onClear, hasActiveFilters }: FilterPanelProps) {
  return (
    <div className="mb-4 flex flex-wrap items-end gap-3">
      {fields.map((field) => (
        <div key={field.id} className="min-w-[180px]">
          <label className="mb-1 block text-xs font-medium text-muted-foreground">
            {field.label}
          </label>
          {field.type === "select" ? (
            <Select value={field.value || null} onValueChange={(v) => field.onChange(v ?? "")} items={field.options?.map((opt) => ({ value: opt.value, label: opt.label }))}>
              <SelectTrigger>
                <SelectValue placeholder={`Select ${field.label.toLowerCase()}`} />
              </SelectTrigger>
              <SelectContent>
                {field.options?.map((opt) => (
                  <SelectItem key={opt.value} value={opt.value}>
                    {opt.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          ) : (
            <Input
              type={field.type === "date" ? "date" : "text"}
              value={field.value}
              onChange={(e) => field.onChange(e.target.value)}
              placeholder={field.type === "date" ? undefined : `Filter ${field.label.toLowerCase()}...`}
            />
          )}
        </div>
      ))}
      {hasActiveFilters && (
        <Button variant="ghost" size="sm" onClick={onClear} className="gap-1">
          <X className="size-3.5" />
          Clear
        </Button>
      )}
    </div>
  );
}
