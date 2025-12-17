import { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
}

export type AppPageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
};

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;

export interface ProductItem {
    id: number;
    name: string;
    thickness?: string | null;
    ply?: string | null;
    glue_type?: string | null;
    qty: number;
    notes?: string | null;
    created_at?: string | null;
}

export interface ProductsPageProps {
    products: {
        data: ProductItem[];
        links?: { 
            next?: string | null; 
            prev?: string | null 
        };
    };
    meta?: { 
        per_page?: number; 
        q?: string 
    };
}

export interface ProductFormData {
    name: string;
    thickness: string;
    ply: string;
    glue_type: string;
    qty: number;
    notes: string;
}

export interface ProductFormErrors {
    name?: string;
    thickness?: string;
    ply?: string;
    glue_type?: string;
    qty?: string;
}