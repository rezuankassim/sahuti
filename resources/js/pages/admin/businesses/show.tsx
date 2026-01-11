import BusinessController from '@/actions/App/Http/Controllers/Admin/BusinessController';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import admin from '@/routes/admin';
import { type BreadcrumbItem } from '@/types';
import { Transition } from '@headlessui/react';
import { Form, Head, router, usePage } from '@inertiajs/react';
import { AlertCircle, CheckCircle2, Info } from 'lucide-react';

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
    onboarding_phone: string | null;
    meta_app_id: string | null;
    webhook_verify_token: string | null;
}

interface Props {
    business: Business;
}

export default function BusinessShow({ business }: Props) {
    const { props } = usePage<{
        flash: { success?: string; error?: string };
    }>();

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

    const handleResetOnboarding = () => {
        if (
            confirm(
                'Are you sure you want to reset the onboarding lock? This will allow a new phone number to complete the onboarding process.'
            )
        ) {
            router.post(
                `/admin/businesses/${business.id}/whatsapp/reset-onboarding`,
                {}
            );
        }
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
                {props.flash?.error && (
                    <Alert className="border-red-500 bg-red-50 text-red-900 dark:bg-red-950 dark:text-red-100">
                        <AlertTitle>{props.flash.error}</AlertTitle>
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
                                        Configure WhatsApp Business API
                                        credentials
                                    </CardDescription>
                                </div>
                                {getStatusBadge()}
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {business.is_connected && (
                                <Alert>
                                    <CheckCircle2 className="h-4 w-4" />
                                    <AlertTitle>Connected</AlertTitle>
                                    <AlertDescription>
                                        WhatsApp Business API is connected
                                        {business.display_phone_number &&
                                            ` (${business.display_phone_number})`}
                                        {business.connected_at &&
                                            `. Connected since ${new Date(business.connected_at).toLocaleDateString()}`}
                                    </AlertDescription>
                                </Alert>
                            )}

                            <Alert>
                                <Info className="h-4 w-4" />
                                <AlertTitle>Setup instructions</AlertTitle>
                                <AlertDescription className="space-y-2 text-xs">
                                    <p>
                                        1. Create a Meta app at
                                        developers.facebook.com
                                    </p>
                                    <p>
                                        2. Add WhatsApp product and get
                                        credentials
                                    </p>
                                    <p>
                                        3. Set webhook URL:
                                        <code className="ml-1 rounded bg-muted px-1 py-0.5">
                                            {window.location.origin}
                                            /webhook/whatsapp
                                        </code>
                                    </p>
                                    <p>
                                        4. Message business number with
                                        <strong> ONBOARDING</strong> to start
                                    </p>
                                </AlertDescription>
                            </Alert>

                            <Form
                                {...BusinessController.updateWhatsApp.form(
                                    business.id
                                )}
                                options={{
                                    preserveScroll: true,
                                }}
                                className="space-y-4"
                            >
                                {({ processing, recentlySuccessful, errors }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="meta_app_id">
                                                Meta App ID *
                                            </Label>
                                            <Input
                                                id="meta_app_id"
                                                name="meta_app_id"
                                                defaultValue={
                                                    business.meta_app_id || ''
                                                }
                                                required
                                                placeholder="123456789012345"
                                            />
                                            <InputError
                                                message={errors.meta_app_id}
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="meta_app_secret">
                                                Meta App Secret *
                                            </Label>
                                            <Input
                                                id="meta_app_secret"
                                                name="meta_app_secret"
                                                type="password"
                                                defaultValue=""
                                                required
                                                placeholder="Enter app secret"
                                            />
                                            <InputError
                                                message={errors.meta_app_secret}
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="phone_number_id">
                                                Phone Number ID *
                                            </Label>
                                            <Input
                                                id="phone_number_id"
                                                name="phone_number_id"
                                                defaultValue={
                                                    business.phone_number_id ||
                                                    ''
                                                }
                                                required
                                                placeholder="123456789012345"
                                            />
                                            <InputError
                                                message={errors.phone_number_id}
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="wa_access_token">
                                                Access Token *
                                            </Label>
                                            <Input
                                                id="wa_access_token"
                                                name="wa_access_token"
                                                type="password"
                                                defaultValue=""
                                                required
                                                placeholder="Enter access token"
                                            />
                                            <InputError
                                                message={errors.wa_access_token}
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="webhook_verify_token">
                                                Webhook Verify Token *
                                            </Label>
                                            <Input
                                                id="webhook_verify_token"
                                                name="webhook_verify_token"
                                                defaultValue={
                                                    business.webhook_verify_token ||
                                                    ''
                                                }
                                                required
                                                placeholder="my_secure_token"
                                            />
                                            <InputError
                                                message={
                                                    errors.webhook_verify_token
                                                }
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="waba_id">
                                                WABA ID (Optional)
                                            </Label>
                                            <Input
                                                id="waba_id"
                                                name="waba_id"
                                                defaultValue={
                                                    business.waba_id || ''
                                                }
                                                placeholder="123456789012345"
                                            />
                                            <InputError
                                                message={errors.waba_id}
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="display_phone_number">
                                                Display Phone (Optional)
                                            </Label>
                                            <Input
                                                id="display_phone_number"
                                                name="display_phone_number"
                                                defaultValue={
                                                    business.display_phone_number ||
                                                    ''
                                                }
                                                placeholder="+1234567890"
                                            />
                                            <InputError
                                                message={
                                                    errors.display_phone_number
                                                }
                                            />
                                        </div>

                                        <div className="flex items-center gap-4">
                                            <Button disabled={processing}>
                                                Save configuration
                                            </Button>

                                            <Transition
                                                show={recentlySuccessful}
                                                enter="transition ease-in-out"
                                                enterFrom="opacity-0"
                                                leave="transition ease-in-out"
                                                leaveTo="opacity-0"
                                            >
                                                <p className="text-sm text-neutral-600">
                                                    Saved
                                                </p>
                                            </Transition>
                                        </div>
                                    </>
                                )}
                            </Form>

                            {business.onboarding_phone && (
                                <div className="space-y-4 border-t pt-4">
                                    <HeadingSmall
                                        title="Onboarding lock"
                                        description="Onboarding is locked to a specific phone number"
                                    />

                                    <Alert variant="destructive">
                                        <AlertCircle className="h-4 w-4" />
                                        <AlertTitle>
                                            Locked to {business.onboarding_phone}
                                        </AlertTitle>
                                        <AlertDescription>
                                            Only this number can complete
                                            onboarding. Reset to allow a
                                            different number.
                                        </AlertDescription>
                                    </Alert>

                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        onClick={handleResetOnboarding}
                                    >
                                        Reset onboarding lock
                                    </Button>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
