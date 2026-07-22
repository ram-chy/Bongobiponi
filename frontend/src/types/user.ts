export interface UserRole {
  id: number;
  name: string;
  slug: string;
}

export interface UserData {
  id: number;
  first_name: string;
  last_name: string;
  email: string;
  mobile_no: string;
  role: UserRole | null;
  created_at: string;
  updated_at: string;
}

export interface UpdateRolePayload {
  role_id: number;
}
