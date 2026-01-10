import { admin } from '@/routes';
import { router } from '@inertiajs/react';
import { useState } from 'react';

interface SignupConfig {
    app_id: string;
    config_id: string;
    state: string;
    redirect_uri: string;
}

interface SignupResponse {
    code: string;
    state: string;
}

declare global {
    interface Window {
        FB: {
            init: (params: {
                appId: string;
                autoLogAppEvents?: boolean;
                xfbml?: boolean;
                version: string;
            }) => void;
            login: (
                callback: (response: {
                    authResponse?: {
                        code: string;
                    };
                    status: string;
                }) => void,
                options?: {
                    config_id: string;
                    response_type: string;
                    override_default_response_type: boolean;
                    extras: {
                        setup: Record<string, unknown>;
                        featureType: string;
                        sessionInfoVersion: string;
                    };
                },
            ) => void;
        };
        fbAsyncInit: () => void;
    }
}

export function useWhatsAppEmbeddedSignup() {
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    /**
     * Load Facebook SDK
     */
    const loadFacebookSDK = (): Promise<void> => {
        return new Promise((resolve) => {
            // Check if SDK is already loaded
            if (window.FB) {
                resolve();
                return;
            }

            // Define callback for SDK initialization
            window.fbAsyncInit = () => {
                resolve();
            };

            // Load SDK script
            if (!document.getElementById('facebook-jssdk')) {
                const script = document.createElement('script');
                script.id = 'facebook-jssdk';
                script.src = 'https://connect.facebook.net/en_US/sdk.js';
                script.async = true;
                script.defer = true;
                document.body.appendChild(script);
            }
        });
    };

    /**
     * Initiate WhatsApp Embedded Signup
     */
    const initiateSignup = async (businessId: number) => {
        setIsLoading(true);
        setError(null);

        try {
            // Call backend to get signup configuration
            const response = await fetch(
                admin.businesses.whatsapp.initiate(businessId).url(),
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN':
                            document
                                .querySelector('meta[name="csrf-token"]')
                                ?.getAttribute('content') || '',
                    },
                },
            );

            if (!response.ok) {
                const data = await response.json();
                throw new Error(data.message || 'Failed to initiate signup');
            }

            const data: { success: boolean; config: SignupConfig } =
                await response.json();

            if (!data.success || !data.config) {
                throw new Error('Invalid response from server');
            }

            // Load Facebook SDK
            await loadFacebookSDK();

            // Initialize Facebook SDK
            if (!window.FB) {
                throw new Error('Facebook SDK failed to load');
            }

            window.FB.init({
                appId: data.config.app_id,
                autoLogAppEvents: true,
                xfbml: true,
                version: 'v18.0',
            });

            // Launch Embedded Signup
            launchSignupModal(data.config);
        } catch (err) {
            const message =
                err instanceof Error
                    ? err.message
                    : 'An unknown error occurred';
            setError(message);
            setIsLoading(false);
        }
    };

    /**
     * Launch the Meta Embedded Signup modal
     */
    const launchSignupModal = (config: SignupConfig) => {
        window.FB.login(
            (response) => {
                if (response.authResponse && response.authResponse.code) {
                    // Redirect to callback URL with code and state
                    const callbackUrl = new URL(config.redirect_uri);
                    callbackUrl.searchParams.set(
                        'code',
                        response.authResponse.code,
                    );
                    callbackUrl.searchParams.set('state', config.state);

                    // Use Inertia to navigate
                    window.location.href = callbackUrl.toString();
                } else {
                    setError('Signup was cancelled or failed');
                    setIsLoading(false);
                }
            },
            {
                config_id: config.config_id,
                response_type: 'code',
                override_default_response_type: true,
                extras: {
                    setup: {},
                    featureType: 'whatsapp_embedded_signup',
                    sessionInfoVersion: '3',
                },
            },
        );
    };

    /**
     * Disconnect WhatsApp
     */
    const disconnect = (businessId: number) => {
        if (
            !confirm(
                'Are you sure you want to disconnect WhatsApp from this business?',
            )
        ) {
            return;
        }

        router.post(
            admin.businesses.whatsapp.disconnect(businessId).url(),
            {},
            {
                preserveScroll: true,
                onStart: () => setIsLoading(true),
                onFinish: () => setIsLoading(false),
            },
        );
    };

    return {
        initiateSignup,
        disconnect,
        isLoading,
        error,
    };
}
