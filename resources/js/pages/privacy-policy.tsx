import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { home } from '@/routes';
import { Head, Link } from '@inertiajs/react';

export default function PrivacyPolicy() {
    return (
        <>
            <Head title="Privacy Policy" />
            <div className="min-h-screen bg-background">
                <div className="container mx-auto max-w-4xl px-4 py-8">
                    {/* Header */}
                    <div className="mb-8">
                        <Link href={home().url}>
                            <Button variant="ghost" className="mb-4">
                                ← Back to Home
                            </Button>
                        </Link>
                        <h1 className="mb-2 text-4xl font-bold">
                            Privacy Policy
                        </h1>
                        <p className="text-muted-foreground">
                            Last updated: January 10, 2026
                        </p>
                    </div>

                    {/* Introduction */}
                    <Card className="mb-6">
                        <CardHeader>
                            <CardTitle>Introduction</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <p>
                                Welcome to Sahuti ("we," "our," or "us"). We are
                                committed to protecting your privacy and
                                ensuring the security of your personal
                                information. This Privacy Policy explains how we
                                collect, use, disclose, and safeguard your
                                information when you use our WhatsApp-based
                                customer support platform.
                            </p>
                            <p>
                                By using our services, you agree to the
                                collection and use of information in accordance
                                with this policy.
                            </p>
                        </CardContent>
                    </Card>

                    {/* Information We Collect */}
                    <Card className="mb-6">
                        <CardHeader>
                            <CardTitle>1. Information We Collect</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <h3 className="mb-2 font-semibold">
                                    1.1 Information You Provide
                                </h3>
                                <ul className="list-inside list-disc space-y-1 text-sm text-muted-foreground">
                                    <li>
                                        <strong>Business Information:</strong>{' '}
                                        Business name, phone number, services
                                        offered, coverage areas, operating
                                        hours, and booking methods
                                    </li>
                                    <li>
                                        <strong>WhatsApp Messages:</strong>{' '}
                                        Messages sent and received through
                                        WhatsApp for customer support purposes
                                    </li>
                                    <li>
                                        <strong>Account Information:</strong>{' '}
                                        Email address, password, and
                                        authentication details
                                    </li>
                                </ul>
                            </div>

                            <Separator />

                            <div>
                                <h3 className="mb-2 font-semibold">
                                    1.2 Automatically Collected Information
                                </h3>
                                <ul className="list-inside list-disc space-y-1 text-sm text-muted-foreground">
                                    <li>
                                        <strong>Usage Data:</strong> Information
                                        about how you interact with our platform
                                    </li>
                                    <li>
                                        <strong>Device Information:</strong> IP
                                        address, browser type, operating system
                                    </li>
                                    <li>
                                        <strong>Cookies:</strong> We use cookies
                                        for authentication and session
                                        management
                                    </li>
                                </ul>
                            </div>

                            <Separator />

                            <div>
                                <h3 className="mb-2 font-semibold">
                                    1.3 WhatsApp-Specific Data
                                </h3>
                                <ul className="list-inside list-disc space-y-1 text-sm text-muted-foreground">
                                    <li>
                                        <strong>Phone Numbers:</strong> Customer
                                        phone numbers for communication
                                    </li>
                                    <li>
                                        <strong>Message Content:</strong> Text
                                        messages exchanged for customer support
                                    </li>
                                    <li>
                                        <strong>
                                            WhatsApp Business Account Data:
                                        </strong>{' '}
                                        WABA ID, Phone Number ID, and access
                                        tokens (encrypted)
                                    </li>
                                </ul>
                            </div>
                        </CardContent>
                    </Card>

                    {/* How We Use Your Information */}
                    <Card className="mb-6">
                        <CardHeader>
                            <CardTitle>
                                2. How We Use Your Information
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="mb-4">
                                We use the collected information for:
                            </p>
                            <ul className="list-inside list-disc space-y-2 text-sm text-muted-foreground">
                                <li>
                                    <strong>Service Delivery:</strong> Providing
                                    automated customer support responses via
                                    WhatsApp
                                </li>
                                <li>
                                    <strong>Communication:</strong> Enabling
                                    businesses to communicate with their
                                    customers
                                </li>
                                <li>
                                    <strong>AI Processing:</strong> Using AI/LLM
                                    to generate intelligent responses based on
                                    business profile data only
                                </li>
                                <li>
                                    <strong>Platform Improvement:</strong>{' '}
                                    Analyzing usage patterns to improve our
                                    services
                                </li>
                                <li>
                                    <strong>Security:</strong> Protecting
                                    against unauthorized access and ensuring
                                    platform security
                                </li>
                                <li>
                                    <strong>Compliance:</strong> Meeting legal
                                    obligations and enforcing our terms
                                </li>
                            </ul>
                        </CardContent>
                    </Card>

                    {/* Data Storage and Security */}
                    <Card className="mb-6">
                        <CardHeader>
                            <CardTitle>3. Data Storage and Security</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <h3 className="mb-2 font-semibold">
                                    3.1 Data Storage
                                </h3>
                                <p className="text-sm text-muted-foreground">
                                    Your data is stored securely in our
                                    database. WhatsApp access tokens are
                                    encrypted at rest using Laravel's encryption
                                    features.
                                </p>
                            </div>

                            <Separator />

                            <div>
                                <h3 className="mb-2 font-semibold">
                                    3.2 Security Measures
                                </h3>
                                <ul className="list-inside list-disc space-y-1 text-sm text-muted-foreground">
                                    <li>
                                        Encrypted storage of sensitive
                                        credentials
                                    </li>
                                    <li>
                                        HTTPS encryption for all data
                                        transmission
                                    </li>
                                    <li>
                                        CSRF protection for all form submissions
                                    </li>
                                    <li>
                                        Rate limiting to prevent abuse (1
                                        auto-reply per 90 seconds)
                                    </li>
                                    <li>
                                        Comprehensive logging for security
                                        auditing
                                    </li>
                                </ul>
                            </div>

                            <Separator />

                            <div>
                                <h3 className="mb-2 font-semibold">
                                    3.3 Data Retention
                                </h3>
                                <p className="text-sm text-muted-foreground">
                                    We retain your information for as long as
                                    your account is active or as needed to
                                    provide services. Message logs are retained
                                    for operational purposes and may be purged
                                    periodically.
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Third-Party Services */}
                    <Card className="mb-6">
                        <CardHeader>
                            <CardTitle>4. Third-Party Services</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <h3 className="mb-2 font-semibold">
                                    4.1 Meta/WhatsApp
                                </h3>
                                <p className="mb-2 text-sm text-muted-foreground">
                                    We use WhatsApp Business API provided by
                                    Meta. Your WhatsApp messages are subject to
                                    WhatsApp's Privacy Policy.
                                </p>
                                <a
                                    href="https://www.whatsapp.com/legal/privacy-policy"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="text-sm text-primary underline"
                                >
                                    View WhatsApp Privacy Policy →
                                </a>
                            </div>

                            <Separator />

                            <div>
                                <h3 className="mb-2 font-semibold">
                                    4.2 OpenAI (LLM)
                                </h3>
                                <p className="mb-2 text-sm text-muted-foreground">
                                    When LLM features are enabled, we use
                                    OpenAI's API to generate responses. We only
                                    send your business profile data and customer
                                    queries—never any personal identifiable
                                    information beyond what's necessary for the
                                    service.
                                </p>
                                <a
                                    href="https://openai.com/policies/privacy-policy"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="text-sm text-primary underline"
                                >
                                    View OpenAI Privacy Policy →
                                </a>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Your Rights */}
                    <Card className="mb-6">
                        <CardHeader>
                            <CardTitle>5. Your Rights</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="mb-4">You have the right to:</p>
                            <ul className="list-inside list-disc space-y-2 text-sm text-muted-foreground">
                                <li>
                                    <strong>Access:</strong> Request a copy of
                                    your personal data
                                </li>
                                <li>
                                    <strong>Correction:</strong> Update or
                                    correct inaccurate information
                                </li>
                                <li>
                                    <strong>Deletion:</strong> Request deletion
                                    of your account and associated data
                                </li>
                                <li>
                                    <strong>Opt-Out:</strong> Disable LLM
                                    features or disconnect WhatsApp integration
                                </li>
                                <li>
                                    <strong>Data Portability:</strong> Request
                                    your data in a machine-readable format
                                </li>
                            </ul>
                        </CardContent>
                    </Card>

                    {/* Cookies */}
                    <Card className="mb-6">
                        <CardHeader>
                            <CardTitle>6. Cookies and Tracking</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="mb-4 text-sm text-muted-foreground">
                                We use essential cookies for:
                            </p>
                            <ul className="list-inside list-disc space-y-1 text-sm text-muted-foreground">
                                <li>
                                    Authentication and session management
                                    (XSRF-TOKEN, session cookie)
                                </li>
                                <li>
                                    User preferences (appearance, sidebar state)
                                </li>
                                <li>Security and CSRF protection</li>
                            </ul>
                            <p className="mt-4 text-sm text-muted-foreground">
                                We do not use advertising or tracking cookies.
                            </p>
                        </CardContent>
                    </Card>

                    {/* Children's Privacy */}
                    <Card className="mb-6">
                        <CardHeader>
                            <CardTitle>7. Children's Privacy</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-sm text-muted-foreground">
                                Our services are not intended for individuals
                                under the age of 18. We do not knowingly collect
                                personal information from children. If you
                                believe we have collected information from a
                                child, please contact us immediately.
                            </p>
                        </CardContent>
                    </Card>

                    {/* Changes to Privacy Policy */}
                    <Card className="mb-6">
                        <CardHeader>
                            <CardTitle>
                                8. Changes to This Privacy Policy
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-sm text-muted-foreground">
                                We may update this Privacy Policy from time to
                                time. We will notify you of any changes by
                                posting the new Privacy Policy on this page and
                                updating the "Last updated" date. You are
                                advised to review this Privacy Policy
                                periodically for any changes.
                            </p>
                        </CardContent>
                    </Card>

                    {/* Contact */}
                    <Card className="mb-6">
                        <CardHeader>
                            <CardTitle>9. Contact Us</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="mb-4 text-sm text-muted-foreground">
                                If you have any questions about this Privacy
                                Policy or our data practices, please contact us:
                            </p>
                            <ul className="space-y-2 text-sm text-muted-foreground">
                                <li>
                                    <strong>Email:</strong> privacy@sahuti.com
                                </li>
                                <li>
                                    <strong>Website:</strong> https://sahuti.com
                                </li>
                            </ul>
                        </CardContent>
                    </Card>

                    {/* Footer */}
                    <div className="mt-8 text-center text-sm text-muted-foreground">
                        <p>
                            © {new Date().getFullYear()} Sahuti. All rights
                            reserved.
                        </p>
                    </div>
                </div>
            </div>
        </>
    );
}
