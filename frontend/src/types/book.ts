export interface BookAuthor {
  id: number;
  name: string;
}

export interface BookPublisher {
  id: number;
  name: string;
}

export interface BookCategory {
  id: number;
  name: string;
}

export interface Book {
  id: number;
  isbn: string | null;
  barcode: string | null;
  title: string;
  subtitle: string | null;
  publisher_id: number | null;
  category_id: number | null;
  edition: string | null;
  language: string | null;
  purchase_price: number;
  selling_price: number;
  minimum_stock: number;
  description: string | null;
  cover_image: string | null;
  publisher?: BookPublisher | null;
  category?: BookCategory | null;
  authors?: BookAuthor[];
  created_by: {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
  } | null;
  updated_by: {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
  } | null;
  created_at: string;
  updated_at: string;
}
