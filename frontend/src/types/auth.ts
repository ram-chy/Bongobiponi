export interface User {
  id: number;
  first_name: string;
  last_name: string;
  email: string;
  mobile_no: string;
  email_verified_at: string | null;
  created_at: string;
  updated_at: string;
  role: {
    id: number;
    name: string;
    slug: string;
  } | null;
}

export interface AuthResponse {
  data: {
    access_token: string;
    token_type: string;
    expires_in: number;
    user: User;
  };
}

export interface MeResponse {
  data: User;
}

export interface LoginPayload {
  email: string;
  password: string;
}

export interface RegisterPayload {
  first_name: string;
  last_name: string;
  email: string;
  mobile_no: string;
  password: string;
  password_confirmation: string;
}

export interface ForgotPasswordPayload {
  email: string;
}

export interface VerifyOtpPayload {
  email: string;
  otp: string;
}

export interface ResetPasswordPayload {
  email: string;
  password: string;
  password_confirmation: string;
}
