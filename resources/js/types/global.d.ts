import type { Auth } from "@/types/auth";

declare module "react" {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}
