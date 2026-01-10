import { Alert, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { useWhatsAppEmbeddedSignup } from '@/hooks/use-whatsapp-embedded-signup';
import AppLayout from '@/layouts/app-layout';
import admin from '@/routes/admin';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/react';

interface Service {
    name: string;
    price: string;
}

interface OperatingHours {
    [key: string]: { open: string; close: string } | { closed: true };
}

interface Business {
    id: number;
    name: string;
    phone_number: string;
    services: Service[];
    areas: string[];
    operating_hours: OperatingHours;
    booking_method: string;
    is_onboarded: boolean;
    llm_enabled: boolean;
    waba_id: string | null;
    phone_number_id: string | null;
    display_phone_number: string | null;
    wa_status: 'pending_connect' | 'connected' | 'disabled';
    connected_at: string | null;
    created_at: string;
    is_connected: boolean;
    can_send_messages: boolean;
}

interface Props {
    business: Business;
}

export default function BusinessShow({ business }: Props) {
    const { props } = usePage<{
        flash: { success?: string; error?: string };
    }>();
    const { initiateSignup, disconnect, isLoading, error } =
        useWhatsAppEmbeddedSignup();

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Admin',
            href: admin.businesses.index().url,
        },
        {
            title: 'Businesses',
            href: admin.businesses.index().url,
        },
        {
            title: business.name,
            href: admin.businesses.show(business.id).url,
        },
    ];

    const getStatusBadge = () => {
        switch (business.wa_status) {
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
                        Pending Connection
                    </Badge>
                );
        }
    };

    const formatOperatingHours = () => {
        return Object.entries(business.operating_hours).map(([day, hours]) => (
            <div key={day} className="flex justify-between text-sm">
                <span className="capitalize">{day}:</span>
                <span className="text-muted-foreground">
                    {'closed' in hours
                        ? 'Closed'
                        : `${hours.open} - ${hours.close}`}
                </span>
            </div>
        ));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={business.name} />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                {/* Flash Messages */}
                {props.flash?.success && (
                    <Alert className="border-green-500 bg-green-50 text-green-900 dark:bg-green-950 dark:text-green-100">
                        <AlertTitle>{props.flash.success}</AlertTitle>
                    </Alert>
                )}
                {(props.flash?.error || error) && (
                    <Alert className="border-red-500 bg-red-50 text-red-900 dark:bg-red-950 dark:text-red-100">
                        <AlertTitle>{props.flash?.error || error}</AlertTitle>
                    </Alert>
                )}

                <div className="grid gap-4 md:grid-cols-2">
                    {/* Business Profile */}
                    <Card>
                        <CardHeader>
                            <CardTitle>{business.name}</CardTitle>
                            <CardDescription>
                                Business Information
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <h4 className="mb-2 text-sm font-medium">
                                    Phone Number
                                </h4>
                                <p className="text-sm text-muted-foreground">
                                    {business.phone_number}
                                </p>
                            </div>

                            <Separator />

                            <div>
                                <h4 className="mb-2 text-sm font-medium">
                                    Services
                                </h4>
                                <div className="space-y-1">
                                    {business.services.map((service, index) => (
                                        <div
                                            key={index}
                                            className="flex justify-between text-sm"
                                        >
                                            <span>{service.name}</span>
                                            <span className="text-muted-foreground">
                                                {service.price}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <Separator />

                            <div>
                                <h4 className="mb-2 text-sm font-medium">
                                    Coverage Areas
                                </h4>
                                <p className="text-sm text-muted-foreground">
                                    {business.areas.join(', ')}
                                </p>
                            </div>

                            <Separator />

                            <div>
                                <h4 className="mb-2 text-sm font-medium">
                                    Operating Hours
                                </h4>
                                <div className="space-y-1">
                                    {formatOperatingHours()}
                                </div>
                            </div>

                            <Separator />

                            <div>
                                <h4 className="mb-2 text-sm font-medium">
                                    Booking Method
                                </h4>
                                <p className="text-sm text-muted-foreground">
                                    {business.booking_method}
                                </p>
                            </div>

                            <Separator />

                            <div className="flex items-center justify-between">
                                <span className="text-sm font-medium">
                                    LLM Enabled
                                </span>
                                <Badge
                                    variant={
                                        business.llm_enabled
                                            ? 'default'
                                            : 'outline'
                                    }
                                >
                                    {business.llm_enabled ? 'Yes' : 'No'}
                                </Badge>
                            </div>
                        </CardContent>
                    </Card>

                    {/* WhatsApp Connection */}
                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <div>
                                    <CardTitle>WhatsApp Connection</CardTitle>
                                    <CardDescription>
                                        Connect this business to WhatsApp
                                        Business API
                                    </CardDescription>
                                </div>
                                {getStatusBadge()}
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {business.is_connected ? (
                                <>
                                    <div>
                                        <h4 className="mb-2 text-sm font-medium">
                                            WABA ID
                                        </h4>
                                        <p className="font-mono text-sm text-muted-foreground">
                                            {business.waba_id}
                                        </p>
                                    </div>

                                    <div>
                                        <h4 className="mb-2 text-sm font-medium">
                                            Phone Number ID
                                        </h4>
                                        <p className="font-mono text-sm text-muted-foreground">
                                            {business.phone_number_id}
                                        </p>
                                    </div>

                                    {business.display_phone_number && (
                                        <div>
                                            <h4 className="mb-2 text-sm font-medium">
                                                Display Phone Number
                                            </h4>
                                            <p className="text-sm text-muted-foreground">
                                                {business.display_phone_number}
                                            </p>
                                        </div>
                                    )}

                                    <div>
                                        <h4 className="mb-2 text-sm font-medium">
                                            Connected At
                                        </h4>
                                        <p className="text-sm text-muted-foreground">
                                            {business.connected_at}
                                        </p>
                                    </div>

                                    <Separator />

                                    <div className="flex items-center justify-between rounded-lg border p-3">
                                        <div>
                                            <p className="text-sm font-medium">
                                                Can Send Messages
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {business.can_send_messages
                                                    ? 'This business can send and receive messages'
                                                    : 'Complete onboarding to enable messaging'}
                                            </p>
                                        </div>
                                        <Badge
                                            variant={
                                                business.can_send_messages
                                                    ? 'default'
                                                    : 'outline'
                                            }
                                        >
                                            {business.can_send_messages
                                                ? 'Active'
                                                : 'Inactive'}
                                        </Badge>
                                    </div>

                                    <Button
                                        variant="destructive"
                                        className="w-full"
                                        onClick={() => disconnect(business.id)}
                                        disabled={isLoading}
                                    >
                                        {isLoading
                                            ? 'Processing...'
                                            : 'Disconnect WhatsApp'}
                                    </Button>
                                </>
                            ) : (
                                <>
                                    <div className="rounded-lg border border-dashed p-6 text-center">
                                        <p className="mb-4 text-sm text-muted-foreground">
                                            This business is not connected to
                                            WhatsApp Business API. Connect now
                                            to enable automated messaging.
                                        </p>
                                        <Button
                                            onClick={() =>
                                                initiateSignup(business.id)
                                            }
                                            disabled={isLoading}
                                            className="w-full"
                                        >
                                            {isLoading
                                                ? 'Connecting...'
                                                : 'Connect WhatsApp'}
                                        </Button>
                                    </div>

                                    <div className="rounded-lg bg-muted p-4">
                                        <h4 className="mb-2 text-sm font-medium">
                                            What happens next?
                                        </h4>
                                        <ol className="list-inside list-decimal space-y-1 text-sm text-muted-foreground">
                                            <li>
                                                Click "Connect WhatsApp" button
                                            </li>
                                            <li>
                                                Sign in with your Facebook
                                                account (if needed)
                                            </li>
                                            <li>
                                                Select your WhatsApp Business
                                                Account
                                            </li>
                                            <li>
                                                Choose which phone number to use
                                            </li>
                                            <li>Grant necessary permissions</li>
                                            <li>
                                                Your business will be connected!
                                            </li>
                                        </ol>
                                    </div>
                                </>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
