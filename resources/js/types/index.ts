export * from './auth';
export * from './budget';
export * from './navigation';
export * from './shopping';
export * from './todo';
export * from './meal';
export * from './note';
export * from './bookmark';
export * from './sweet-message';
export * from './birthday';
export * from './ui';

import type { Auth } from './auth';

export type AppPageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    name: string;
    auth: Auth;
    sidebarOpen: boolean;
    [key: string]: unknown;
};
