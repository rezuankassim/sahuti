import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import admin from '@/routes/admin';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface Business {
    id: number;
    name: string;
    phone_number: string;
    display_phone_number: string | null;
    wa_status: 'pending_connect' | 'connected' | 'disabled';
    is_onboarded: boolean;
    connected_at: string | null;
    created_at: string;
    is_connected: boolean;
}

interface Props {
    businesses: Business[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin',
        href: admin.businesses.index().url,
    },
    {
        title: 'Businesses',
        href: admin.businesses.index().url,
    },
];

function getStatusBadge(status: Business['wa_status']) {
    switch (status) {
        case 'connected':
            return (
                <Badge
                    variant="outline"
                    className="border-green-500 text-green-500"
                >
                    Connected
                </Badge>
            );
        case 'disabled':
            return (
                <Badge
                    variant="outline"
                    className="border-red-500 text-red-500"
                >
                    Disabled
                </Badge>
            );
        case 'pending_connect':
        default:
            return (
                <Badge
                    variant="outline"
                    className="border-yellow-500 text-yellow-500"
                >
                    Pending
                </Badge>
            );
    }
}

export default function BusinessesIndex({ businesses }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Businesses" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Businesses</CardTitle>
                        <CardDescription>
                            Manage business profiles and WhatsApp connections
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {businesses.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-12 text-center">
                                <p className="text-muted-foreground">
                                    No businesses found. Businesses are created
                                    when customers complete onboarding via
                                    WhatsApp.
                                </p>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full">
                                    <thead>
                                        <tr className="border-b">
                                            <th className="pr-4 pb-3 text-left font-medium">
                                                Business Name
                                            </th>
                                            <th className="pr-4 pb-3 text-left font-medium">
                                                Phone
                                            </th>
                                            <th className="pr-4 pb-3 text-left font-medium">
                                                WhatsApp Status
                                            </th>
                                            <th className="pr-4 pb-3 text-left font-medium">
                                                Connected
                                            </th>
                                            <th className="pb-3 text-left font-medium">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {businesses.map((business) => (
                                            <tr
                                                key={business.id}
                                                className="border-b last:border-b-0"
                                            >
                                                <td className="py-3 pr-4">
                                                    <div>
                                                        <div className="font-medium">
                                                            {business.name}
                                                        </div>
                                                        {!business.is_onboarded && (
                                                            <span className="text-xs text-muted-foreground">
                                                                Onboarding
                                                                incomplete
                                                            </span>
                                                        )}
                                                    </div>
                                                </td>
                                                <td className="py-3 pr-4">
                                                    <div className="text-sm">
                                                        {business.display_phone_number ||
                                                            business.phone_number}
                                                    </div>
                                                </td>
                                                <td className="py-3 pr-4">
                                                    {getStatusBadge(
                                                        business.wa_status,
                                                    )}
                                                </td>
                                                <td className="py-3 pr-4 text-sm text-muted-foreground">
                                                    {business.connected_at ? (
                                                        <span>
                                                            {
                                                                business.connected_at
                                                            }
                                                        </span>
                                                    ) : (
                                                        <span>—</span>
                                                    )}
                                                </td>
                                                <td className="py-3">
                                                    <Link
                                                        href={
                                                            admin.businesses.show(
                                                                business.id,
                                                            ).url
                                                        }
                                                    >
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                        >
                                                            View Details
                                                        </Button>
                                                    </Link>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
