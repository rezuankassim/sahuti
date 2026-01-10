import axios from '@/lib/axios';
import admin from '@/routes/admin';
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
     * Load and initialize Facebook SDK
     */
    const loadFacebookSDK = (appId: string): Promise<void> => {
        return new Promise((resolve, reject) => {
            // Check if SDK is already loaded and initialized
            if (window.FB) {
                resolve();
                return;
            }

            // Define callback for SDK initialization
            window.fbAsyncInit = () => {
                if (window.FB) {
                    window.FB.init({
                        appId: appId,
                        autoLogAppEvents: true,
                        xfbml: true,
                        version: 'v18.0',
                    });
                    resolve();
                } else {
                    reject(new Error('Facebook SDK failed to initialize'));
                }
            };

            // Load SDK script if not already present
            if (!document.getElementById('facebook-jssdk')) {
                const script = document.createElement('script');
                script.id = 'facebook-jssdk';
                script.src = 'https://connect.facebook.net/en_US/sdk.js';
                script.async = true;
                script.defer = true;
                script.onerror = () =>
                    reject(new Error('Failed to load Facebook SDK script'));
                document.body.appendChild(script);
            } else {
                // Script exists but SDK not initialized yet
                setTimeout(() => {
                    if (window.FB) {
                        resolve();
                    } else {
                        reject(
                            new Error(
                                'Facebook SDK script loaded but not initialized',
                            ),
                        );
                    }
                }, 1000);
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
            // Call backend using axios (CSRF token handled automatically)
            const response = await axios.post<{
                success: boolean;
                config: SignupConfig;
            }>(admin.businesses.whatsapp.initiate(businessId).url);

            const data = response.data;

            if (!data.success || !data.config) {
                throw new Error('Invalid response from server');
            }

            // Load and initialize Facebook SDK
            await loadFacebookSDK(data.config.app_id);

            // Verify SDK is ready
            if (!window.FB) {
                throw new Error('Facebook SDK failed to load');
            }

            // Launch Embedded Signup
            launchSignupModal(data.config);
        } catch (err: any) {
            const message =
                err.response?.data?.message ||
                err.message ||
                'An unknown error occurred';
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
            admin.businesses.whatsapp.disconnect(businessId).url,
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
