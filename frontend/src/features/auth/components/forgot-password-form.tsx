"use client";

import { useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { Button, buttonVariants } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Card,
  CardHeader,
  CardTitle,
  CardDescription,
  CardContent,
  CardFooter,
} from "@/components/ui/card";
import { Loader2, ArrowLeft, CheckCircle2, Eye, EyeOff } from "lucide-react";
import { toast } from "sonner";
import { cn } from "@/lib/utils";
import { useForgotPassword } from "@/features/auth/hooks/use-forgot-password";
import { useVerifyOtp } from "@/features/auth/hooks/use-verify-otp";
import { useResetPassword } from "@/features/auth/hooks/use-reset-password";

const emailSchema = z.object({
  email: z.string().email("Invalid email address"),
});

const otpSchema = z.object({
  otp: z.string().regex(/^\d{6}$/, "OTP must be 6 digits"),
});

const passwordSchema = z
  .object({
    password: z.string().min(8, "Password must be at least 8 characters"),
    password_confirmation: z.string().min(1, "Please confirm your password"),
  })
  .refine((data) => data.password === data.password_confirmation, {
    message: "Passwords do not match",
    path: ["password_confirmation"],
  });

type EmailFormData = z.infer<typeof emailSchema>;
type OtpFormData = z.infer<typeof otpSchema>;
type PasswordFormData = z.infer<typeof passwordSchema>;

export function ForgotPasswordForm() {
  const router = useRouter();
  const [step, setStep] = useState<"email" | "otp" | "password" | "done">("email");
  const [email, setEmail] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);

  const forgotPasswordMutation = useForgotPassword();
  const verifyOtpMutation = useVerifyOtp();
  const resetPasswordMutation = useResetPassword();

  const emailForm = useForm<EmailFormData>({
    resolver: zodResolver(emailSchema),
  });

  const otpForm = useForm<OtpFormData>({
    resolver: zodResolver(otpSchema),
  });

  const passwordForm = useForm<PasswordFormData>({
    resolver: zodResolver(passwordSchema),
  });

  const onEmailSubmit = async (data: EmailFormData) => {
    try {
      await forgotPasswordMutation.mutateAsync(data);
      setEmail(data.email);
      setStep("otp");
      toast.success("OTP sent to your email");
    } catch {
      toast.error("Failed to send OTP. Please check your email.");
    }
  };

  const onOtpSubmit = async (data: OtpFormData) => {
    try {
      const result = await verifyOtpMutation.mutateAsync({
        email,
        otp: data.otp,
      });
      if (result.token) {
        sessionStorage.setItem("reset_token", result.token);
        setStep("password");
      } else {
        toast.error("Invalid or expired OTP");
      }
    } catch {
      toast.error("Invalid or expired OTP");
    }
  };

  const onPasswordSubmit = async (data: PasswordFormData) => {
    const token = sessionStorage.getItem("reset_token");
    if (!token) {
      toast.error("Session expired. Please start over.");
      setStep("email");
      return;
    }

    try {
      await resetPasswordMutation.mutateAsync({
        email,
        token,
        password: data.password,
        password_confirmation: data.password_confirmation,
      });
      sessionStorage.removeItem("reset_token");
      setStep("done");
      toast.success("Password reset successfully");
    } catch {
      toast.error("Failed to reset password. The link may have expired.");
    }
  };

  if (step === "done") {
    return (
      <Card className="w-full max-w-sm">
        <CardHeader>
          <div className="flex justify-center mb-2">
            <CheckCircle2 className="size-12 text-emerald-500" />
          </div>
          <CardTitle className="text-xl text-center">Password Reset</CardTitle>
          <CardDescription className="text-center">
            Your password has been reset successfully.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <Button
            className="w-full"
            onClick={() => router.push("/login")}
          >
            Sign in with new password
          </Button>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card className="w-full max-w-sm">
      <CardHeader>
        <CardTitle className="text-xl">
          {step === "email" && "Forgot Password"}
          {step === "otp" && "Enter OTP"}
          {step === "password" && "New Password"}
        </CardTitle>
        <CardDescription>
          {step === "email" && "Enter your email to receive a reset code."}
          {step === "otp" && `Enter the 6-digit code sent to ${email}.`}
          {step === "password" && "Choose a new password for your account."}
        </CardDescription>
      </CardHeader>
      <CardContent>
        {step === "email" && (
          <form onSubmit={emailForm.handleSubmit(onEmailSubmit)} className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="email">Email</Label>
              <Input
                id="email"
                type="email"
                placeholder="your@email.com"
                {...emailForm.register("email")}
              />
              {emailForm.formState.errors.email && (
                <p className="text-sm text-destructive">{emailForm.formState.errors.email.message}</p>
              )}
            </div>
            <Button
              type="submit"
              className="w-full"
              disabled={forgotPasswordMutation.isPending}
            >
              {forgotPasswordMutation.isPending && <Loader2 className="animate-spin" />}
              Send Reset Code
            </Button>
          </form>
        )}

        {step === "otp" && (
          <form onSubmit={otpForm.handleSubmit(onOtpSubmit)} className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="otp">OTP Code</Label>
              <Input
                id="otp"
                type="text"
                inputMode="numeric"
                placeholder="000000"
                maxLength={6}
                className="text-center text-2xl tracking-[8px]"
                {...otpForm.register("otp")}
              />
              {otpForm.formState.errors.otp && (
                <p className="text-sm text-destructive">{otpForm.formState.errors.otp.message}</p>
              )}
            </div>
            <Button
              type="submit"
              className="w-full"
              disabled={verifyOtpMutation.isPending}
            >
              {verifyOtpMutation.isPending && <Loader2 className="animate-spin" />}
              Verify Code
            </Button>
            <Button
              type="button"
              variant="link"
              className="w-full text-sm"
              disabled={forgotPasswordMutation.isPending}
              onClick={async () => {
                try {
                  await forgotPasswordMutation.mutateAsync({ email });
                  toast.success("New OTP sent");
                } catch {
                  toast.error("Failed to resend OTP");
                }
              }}
            >
              {forgotPasswordMutation.isPending ? "Sending..." : "Resend code"}
            </Button>
          </form>
        )}

        {step === "password" && (
          <form onSubmit={passwordForm.handleSubmit(onPasswordSubmit)} className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="password">New Password</Label>
              <div className="relative">
                <Input
                  id="password"
                  type={showPassword ? "text" : "password"}
                  placeholder="Min. 8 characters"
                  className="pr-9"
                  {...passwordForm.register("password")}
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  className="absolute right-2.5 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                  tabIndex={-1}
                >
                  {showPassword ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
                </button>
              </div>
              {passwordForm.formState.errors.password && (
                <p className="text-sm text-destructive">{passwordForm.formState.errors.password.message}</p>
              )}
            </div>
            <div className="space-y-2">
              <Label htmlFor="password_confirmation">Confirm Password</Label>
              <div className="relative">
                <Input
                  id="password_confirmation"
                  type={showConfirmPassword ? "text" : "password"}
                  placeholder="Re-enter new password"
                  className="pr-9"
                  {...passwordForm.register("password_confirmation")}
                />
                <button
                  type="button"
                  onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                  className="absolute right-2.5 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                  tabIndex={-1}
                >
                  {showConfirmPassword ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
                </button>
              </div>
              {passwordForm.formState.errors.password_confirmation && (
                <p className="text-sm text-destructive">{passwordForm.formState.errors.password_confirmation.message}</p>
              )}
            </div>
            <Button
              type="submit"
              className="w-full"
              disabled={resetPasswordMutation.isPending}
            >
              {resetPasswordMutation.isPending && <Loader2 className="animate-spin" />}
              Reset Password
            </Button>
          </form>
        )}
      </CardContent>
      <CardFooter className="justify-center">
        <Link
          href="/login"
          className={cn(
            buttonVariants({ variant: "link" }),
            "h-auto p-0 text-sm"
          )}
        >
          <ArrowLeft className="mr-1 size-3.5" />
          Back to Sign In
        </Link>
      </CardFooter>
    </Card>
  );
}
