import WhatsAppController from '@/actions/App/Http/Controllers/Settings/WhatsAppController';
import { type BreadcrumbItem } from '@/types';
import { Transition } from '@headlessui/react';
import { Form, Head, router } from '@inertiajs/react';

import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { edit } from '@/routes/whatsapp';
import { AlertCircle, CheckCircle2, Info } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'WhatsApp settings',
        href: edit().url,
    },
];

interface Business {
    id: number;
    name: string;
    phone_number: string;
    display_phone_number: string | null;
    wa_status: 'pending_connect' | 'connected' | 'disabled';
    is_onboarded: boolean;
    onboarding_phone: string | null;
    meta_app_id: string | null;
    webhook_verify_token: string | null;
    phone_number_id: string | null;
    waba_id: string | null;
    connected_at: string | null;
}

export default function WhatsApp({
    business,
    status,
}: {
    business: Business;
    status?: string;
}) {
    const handleResetOnboarding = () => {
        if (
            confirm(
                'Are you sure you want to reset the onboarding lock? This will allow a new phone number to complete the onboarding process.'
            )
        ) {
            router.post('/settings/whatsapp/reset-onboarding', {});
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="WhatsApp settings" />

            <h1 className="sr-only">WhatsApp Settings</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title="WhatsApp configuration"
                        description="Configure your Meta app credentials for WhatsApp Business API"
                    />

                    {business.wa_status === 'connected' && (
                        <Alert>
                            <CheckCircle2 className="h-4 w-4" />
                            <AlertTitle>Connected</AlertTitle>
                            <AlertDescription>
                                Your WhatsApp Business API is connected
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
                        <AlertDescription className="space-y-2">
                            <p>
                                1. Create a Meta app at{' '}
                                <a
                                    href="https://developers.facebook.com"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="underline"
                                >
                                    developers.facebook.com
                                </a>
                            </p>
                            <p>
                                2. Add WhatsApp product to your app and get your
                                credentials
                            </p>
                            <p>
                                3. Set up webhook URL:{' '}
                                <code className="rounded bg-muted px-1 py-0.5">
                                    {window.location.origin}/whatsapp/webhook
                                </code>
                            </p>
                            <p>
                                4. After saving, message your business number
                                with <strong>ONBOARDING</strong> to start
                            </p>
                        </AlertDescription>
                    </Alert>

                    <Form
                        {...WhatsAppController.update.form()}
                        options={{
                            preserveScroll: true,
                        }}
                        className="space-y-6"
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
                                    <InputError message={errors.meta_app_id} />
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
                                        placeholder="Enter your Meta app secret"
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
                                            business.phone_number_id || ''
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
                                        WhatsApp Access Token *
                                    </Label>
                                    <Input
                                        id="wa_access_token"
                                        name="wa_access_token"
                                        type="password"
                                        defaultValue=""
                                        required
                                        placeholder="Enter your WhatsApp access token"
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
                                            business.webhook_verify_token || ''
                                        }
                                        required
                                        placeholder="my_secure_token_123"
                                    />
                                    <p className="text-sm text-muted-foreground">
                                        Create a secure random string for
                                        webhook verification
                                    </p>
                                    <InputError
                                        message={errors.webhook_verify_token}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="waba_id">
                                        WhatsApp Business Account ID (Optional)
                                    </Label>
                                    <Input
                                        id="waba_id"
                                        name="waba_id"
                                        defaultValue={business.waba_id || ''}
                                        placeholder="123456789012345"
                                    />
                                    <InputError message={errors.waba_id} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="display_phone_number">
                                        Display Phone Number (Optional)
                                    </Label>
                                    <Input
                                        id="display_phone_number"
                                        name="display_phone_number"
                                        defaultValue={
                                            business.display_phone_number || ''
                                        }
                                        placeholder="+1234567890"
                                    />
                                    <InputError
                                        message={errors.display_phone_number}
                                    />
                                </div>

                                <div className="flex items-center gap-4">
                                    <Button
                                        disabled={processing}
                                        data-test="update-whatsapp-button"
                                    >
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
                        <div className="space-y-4 border-t pt-6">
                            <HeadingSmall
                                title="Onboarding lock"
                                description="The onboarding process is locked to a specific phone number"
                            />

                            <Alert variant="destructive">
                                <AlertCircle className="h-4 w-4" />
                                <AlertTitle>
                                    Locked to {business.onboarding_phone}
                                </AlertTitle>
                                <AlertDescription>
                                    Only this phone number can complete the
                                    onboarding process. Reset to allow a
                                    different number.
                                </AlertDescription>
                            </Alert>

                            <Button
                                variant="destructive"
                                onClick={handleResetOnboarding}
                            >
                                Reset onboarding lock
                            </Button>
                        </div>
                    )}
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
