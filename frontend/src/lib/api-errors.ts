import { AxiosError } from "axios";
import type { UseFormSetError, FieldValues, Path } from "react-hook-form";
import { toast } from "sonner";

interface ApiErrorData {
  message?: string;
  errors?: Record<string, string[]>;
}

export function getApiMessage(error: unknown): string {
  if (error instanceof AxiosError) {
    const data = error.response?.data as ApiErrorData | undefined;

    if (!error.response) {
      return "Network error. Please check your connection.";
    }

    const { status } = error.response;

    if (data?.message) {
      return data.message;
    }

    switch (status) {
      case 403:
        return "You do not have permission to perform this action.";
      case 404:
        return "The requested resource was not found.";
      case 422:
        return "Please check the form for errors.";
      case 500:
        return "Server error. Please try again later.";
      default:
        return "An unexpected error occurred.";
    }
  }

  if (error instanceof Error) {
    return error.message;
  }

  return "An unexpected error occurred.";
}

export function mapValidationErrors<T extends FieldValues>(
  error: unknown,
  setError: UseFormSetError<T>
): void {
  if (error instanceof AxiosError && error.response?.status === 422) {
    const data = error.response.data as ApiErrorData | undefined;
    const serverErrors = data?.errors;

    if (serverErrors) {
      for (const [field, messages] of Object.entries(serverErrors)) {
        setError(field as Path<T>, {
          message: (messages as string[])[0],
        });
      }
      return;
    }
  }

  toast.error(getApiMessage(error));
}
