import { usePage } from '@inertiajs/react';

export default function AppLogo() {
    const { name } = usePage().props;

    return (
        <div className="flex items-center gap-2">
            <img
                src="/logo-long.svg"
                alt={String(name || 'Clinigoo')}
                className="h-8 w-auto object-contain"
            />
        </div>
    );
}
