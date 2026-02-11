<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Populates policy pages (privacy, terms, refund, cancellation, return) with default content.
     */
    public function up(): void
    {
        $policies = [
            'privacy_policy' => $this->getPrivacyPolicyContent(),
            'terms_and_conditions' => $this->getTermsAndConditionsContent(),
            'refund_policy' => $this->getRefundPolicyContent(),
            'cancellation_policy' => $this->getCancellationPolicyContent(),
            'return_policy' => $this->getReturnPolicyContent(),
        ];

        foreach ($policies as $key => $content) {
            $existing = DB::table('business_settings')->where('key', $key)->first();
            
            if (!$existing) {
                // Key doesn't exist - insert it with default content
                DB::table('business_settings')->insert([
                    'key' => $key,
                    'value' => $content,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                echo "Inserted default content for: {$key}\n";
            } elseif (empty($existing->value) || trim($existing->value) === '' || $existing->value === '<p></p>') {
                // Key exists but value is empty or just empty HTML - update with default content
                DB::table('business_settings')
                    ->where('key', $key)
                    ->update([
                        'value' => $content,
                        'updated_at' => now(),
                    ]);
                echo "Updated default content for: {$key}\n";
            } else {
                // Key exists with non-empty content - respect admin content, don't overwrite
                echo "Skipped {$key} - already has content\n";
            }
        }

        echo "Policy pages population completed.\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We don't remove the policies as they might have been edited by admin
        // No action needed
    }

    private function getPrivacyPolicyContent(): string
    {
        return <<<'HTML'
<h2>Privacy Policy</h2>

<p><strong>Effective Date:</strong> February 11, 2026</p>

<h3>Introduction</h3>
<p>Welcome to Sunrise Family Mart. We are committed to protecting your privacy and ensuring the security of your personal information. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our grocery and food delivery service through our mobile application and website.</p>

<h3>Information We Collect</h3>
<p>We collect several types of information to provide and improve our service:</p>
<ul>
    <li><strong>Personal Information:</strong> Name, email address, phone number, delivery address</li>
    <li><strong>Order Information:</strong> Order history, product preferences, shopping patterns</li>
    <li><strong>Payment Information:</strong> Payment card details, billing address, transaction history (processed securely through our payment processors)</li>
    <li><strong>Device Information:</strong> Device type, operating system, unique device identifiers, mobile network information</li>
    <li><strong>Location Information:</strong> GPS location and delivery address to facilitate accurate deliveries</li>
    <li><strong>Usage Information:</strong> How you interact with our app, features used, time spent on pages</li>
</ul>

<h3>How We Use Your Information</h3>
<p>We use the collected information for the following purposes:</p>
<ul>
    <li><strong>Order Processing:</strong> To process and fulfill your orders, manage payments, and arrange deliveries</li>
    <li><strong>Service Delivery:</strong> To communicate with you about your orders, delivery status, and customer support</li>
    <li><strong>Customer Support:</strong> To respond to your inquiries, resolve issues, and provide assistance</li>
    <li><strong>Analytics:</strong> To analyze usage patterns and improve our services, user experience, and product offerings</li>
    <li><strong>Personalization:</strong> To provide personalized recommendations and offers based on your preferences</li>
    <li><strong>Communications:</strong> To send you updates, promotional offers, and important notifications (you can opt-out anytime)</li>
    <li><strong>Legal Compliance:</strong> To comply with legal obligations and enforce our terms of service</li>
</ul>

<h3>Information Sharing and Disclosure</h3>
<p>We respect your privacy and do not sell your personal information to third parties. We may share your information with:</p>
<ul>
    <li><strong>Payment Processors:</strong> To securely process your payments (e.g., credit card companies, payment gateways)</li>
    <li><strong>Delivery Partners:</strong> To fulfill and deliver your orders (they receive only necessary delivery information)</li>
    <li><strong>Service Providers:</strong> Third-party vendors who assist us in operating our platform (e.g., cloud hosting, analytics)</li>
    <li><strong>Legal Requirements:</strong> When required by law or to protect our rights, safety, or property</li>
</ul>
<p><strong>We NEVER sell your personal data to advertisers or other third parties.</strong></p>

<h3>Data Security</h3>
<p>We implement industry-standard security measures to protect your information:</p>
<ul>
    <li>Encryption of sensitive data during transmission (SSL/TLS)</li>
    <li>Secure storage with access controls and authentication</li>
    <li>Regular security audits and updates</li>
    <li>Payment information is handled by PCI-DSS compliant payment processors</li>
    <li>Employee access to personal data is restricted on a need-to-know basis</li>
</ul>
<p>However, no method of transmission over the internet is 100% secure. While we strive to protect your information, we cannot guarantee absolute security.</p>

<h3>Data Retention</h3>
<p>We retain your personal information for as long as necessary to:</p>
<ul>
    <li>Provide our services and maintain your account</li>
    <li>Comply with legal obligations (e.g., tax records, transaction history)</li>
    <li>Resolve disputes and enforce our agreements</li>
    <li>Improve our services through analytics</li>
</ul>
<p>You may request deletion of your account and personal data at any time, subject to legal retention requirements.</p>

<h3>Your Rights</h3>
<p>You have the following rights regarding your personal information:</p>
<ul>
    <li><strong>Access:</strong> Request a copy of the personal information we hold about you</li>
    <li><strong>Correction:</strong> Update or correct inaccurate information</li>
    <li><strong>Deletion:</strong> Request deletion of your personal data (subject to legal obligations)</li>
    <li><strong>Opt-Out:</strong> Unsubscribe from marketing communications at any time</li>
    <li><strong>Data Portability:</strong> Request your data in a machine-readable format</li>
    <li><strong>Withdraw Consent:</strong> Withdraw consent for data processing where applicable</li>
</ul>
<p>To exercise these rights, please contact us at <a href="mailto:support@sunrisefamilymart.com">support@sunrisefamilymart.com</a></p>

<h3>Cookies and Tracking Technologies</h3>
<p>We use cookies and similar tracking technologies to enhance your experience:</p>
<ul>
    <li><strong>Essential Cookies:</strong> Required for the website to function properly</li>
    <li><strong>Analytics Cookies:</strong> Help us understand how users interact with our platform</li>
    <li><strong>Session Cookies:</strong> Maintain your login session and shopping cart</li>
    <li><strong>Preference Cookies:</strong> Remember your settings and preferences</li>
</ul>
<p>You can control cookies through your browser settings, but disabling cookies may affect functionality.</p>

<h3>Children's Privacy</h3>
<p>Our services are not intended for children under 13 years of age. We do not knowingly collect personal information from children under 13. If you are a parent or guardian and believe your child has provided us with personal information, please contact us immediately, and we will delete such information.</p>
<p>This policy complies with the Children's Online Privacy Protection Act (COPPA).</p>

<h3>Third-Party Links</h3>
<p>Our platform may contain links to third-party websites or services. We are not responsible for the privacy practices of these third parties. We encourage you to review their privacy policies before providing any personal information.</p>

<h3>Changes to This Privacy Policy</h3>
<p>We may update this Privacy Policy from time to time to reflect changes in our practices or legal requirements. When we make changes:</p>
<ul>
    <li>We will update the "Effective Date" at the top of this policy</li>
    <li>Significant changes will be communicated via email or app notification</li>
    <li>Continued use of our services after changes constitutes acceptance</li>
</ul>
<p>We encourage you to review this policy periodically.</p>

<h3>Contact Us</h3>
<p>If you have any questions, concerns, or requests regarding this Privacy Policy or our data practices, please contact us:</p>
<ul>
    <li><strong>Email:</strong> <a href="mailto:support@sunrisefamilymart.com">support@sunrisefamilymart.com</a></li>
    <li><strong>Website:</strong> <a href="https://sunrisefamilymart.com">sunrisefamilymart.com</a></li>
    <li><strong>Business Name:</strong> Sunrise Family Mart</li>
</ul>
HTML;
    }

    private function getTermsAndConditionsContent(): string
    {
        return <<<'HTML'
<h2>Terms and Conditions</h2>

<p><strong>Effective Date:</strong> February 11, 2026</p>

<h3>1. Acceptance of Terms</h3>
<p>Welcome to Sunrise Family Mart. By accessing or using our mobile application, website, or services, you agree to be bound by these Terms and Conditions. If you do not agree to these terms, please do not use our services.</p>
<p>These terms constitute a legally binding agreement between you and Sunrise Family Mart. We reserve the right to modify these terms at any time, and continued use of our services constitutes acceptance of any changes.</p>

<h3>2. Account Registration and Responsibilities</h3>
<p>To use our services, you must:</p>
<ul>
    <li>Be at least 18 years of age or have parental/guardian consent</li>
    <li>Provide accurate, current, and complete information during registration</li>
    <li>Maintain the security of your account credentials</li>
    <li>Accept responsibility for all activities under your account</li>
    <li>Notify us immediately of any unauthorized use of your account</li>
</ul>
<p>You are responsible for maintaining the confidentiality of your password and account. Sunrise Family Mart will not be liable for any loss or damage arising from your failure to protect your account information.</p>

<h3>3. Orders and Payments</h3>
<p><strong>Placing Orders:</strong></p>
<ul>
    <li>All orders are subject to acceptance and product availability</li>
    <li>We reserve the right to refuse or cancel any order for any reason</li>
    <li>Prices are displayed in your local currency and include applicable taxes unless stated otherwise</li>
    <li>Product images are for illustration purposes; actual products may vary slightly</li>
</ul>

<p><strong>Pricing:</strong></p>
<ul>
    <li>Prices are subject to change without notice</li>
    <li>The price charged will be the price displayed at the time of order placement</li>
    <li>We strive to provide accurate pricing but errors may occur; we reserve the right to correct errors and cancel orders</li>
</ul>

<p><strong>Payment Methods:</strong></p>
<ul>
    <li>We accept various payment methods including credit/debit cards, digital wallets, and cash on delivery (where available)</li>
    <li>Payment must be completed before order processing</li>
    <li>All payments are processed securely through our trusted payment partners</li>
</ul>

<p><strong>Order Confirmation:</strong></p>
<ul>
    <li>You will receive an order confirmation via email or app notification</li>
    <li>Confirmation does not guarantee product availability; we will notify you if items are unavailable</li>
</ul>

<h3>4. Delivery</h3>
<p><strong>Delivery Times:</strong></p>
<ul>
    <li>Estimated delivery times are provided at checkout and are approximate</li>
    <li>We strive to meet delivery times but are not liable for delays due to circumstances beyond our control</li>
    <li>Delivery times may vary based on location, order volume, and traffic conditions</li>
</ul>

<p><strong>Delivery Areas:</strong></p>
<ul>
    <li>Delivery is available in specified service areas only</li>
    <li>Please ensure your delivery address is accurate and complete</li>
    <li>Additional charges may apply for deliveries to certain areas</li>
</ul>

<p><strong>Responsibility Upon Delivery:</strong></p>
<ul>
    <li>Upon delivery, please inspect your order for accuracy and quality</li>
    <li>Risk of loss and title for items passes to you upon delivery</li>
    <li>Report any issues immediately through our app or customer support</li>
</ul>

<h3>5. Cancellation and Modifications</h3>
<p>You may cancel or modify your order before it is processed. Once your order enters the preparation or dispatch stage, cancellation may not be possible or may be subject to fees. Please refer to our Cancellation Policy for detailed information.</p>

<h3>6. Refunds</h3>
<p>Refunds are available for eligible orders as outlined in our Refund Policy. Please review our Refund Policy for detailed information on eligibility, process, and timelines.</p>

<h3>7. Intellectual Property</h3>
<p>All content on our platform, including but not limited to:</p>
<ul>
    <li>Text, graphics, logos, images, and software</li>
    <li>Product descriptions and photographs</li>
    <li>Trademarks, service marks, and trade names</li>
</ul>
<p>are the property of Sunrise Family Mart or our licensors and are protected by copyright, trademark, and other intellectual property laws. You may not reproduce, distribute, modify, or create derivative works without our express written permission.</p>

<h3>8. Limitation of Liability</h3>
<p>To the maximum extent permitted by law:</p>
<ul>
    <li>Sunrise Family Mart is not liable for any indirect, incidental, special, or consequential damages</li>
    <li>Our total liability shall not exceed the amount paid for the specific order giving rise to the claim</li>
    <li>We are not responsible for delays or failures due to circumstances beyond our reasonable control</li>
    <li>We do not guarantee uninterrupted or error-free service</li>
</ul>

<h3>9. User Conduct</h3>
<p>You agree not to:</p>
<ul>
    <li>Use our services for any unlawful purpose or in violation of these terms</li>
    <li>Attempt to gain unauthorized access to our systems or other users' accounts</li>
    <li>Interfere with or disrupt the integrity or performance of our platform</li>
    <li>Transmit viruses, malware, or other harmful code</li>
    <li>Harass, abuse, or harm our employees, delivery personnel, or other users</li>
    <li>Provide false, inaccurate, or misleading information</li>
</ul>

<h3>10. Governing Law and Dispute Resolution</h3>
<p>These Terms and Conditions are governed by and construed in accordance with the laws of the jurisdiction in which Sunrise Family Mart operates. Any disputes arising from these terms or your use of our services shall be resolved through:</p>
<ul>
    <li>Good faith negotiations between the parties</li>
    <li>Mediation or arbitration if negotiations fail</li>
    <li>Exclusive jurisdiction of the courts in our operating jurisdiction</li>
</ul>

<h3>11. Changes to Terms</h3>
<p>We reserve the right to modify these Terms and Conditions at any time. Changes will be effective upon posting to our platform. Continued use of our services after changes are posted constitutes acceptance of the modified terms. We encourage you to review these terms periodically.</p>

<h3>12. Severability</h3>
<p>If any provision of these Terms and Conditions is found to be invalid or unenforceable, the remaining provisions shall remain in full force and effect.</p>

<h3>13. Contact Information</h3>
<p>For questions, concerns, or support regarding these Terms and Conditions, please contact us:</p>
<ul>
    <li><strong>Email:</strong> <a href="mailto:support@sunrisefamilymart.com">support@sunrisefamilymart.com</a></li>
    <li><strong>Website:</strong> <a href="https://sunrisefamilymart.com">sunrisefamilymart.com</a></li>
    <li><strong>Business Name:</strong> Sunrise Family Mart</li>
</ul>

<p>Thank you for choosing Sunrise Family Mart for your grocery and food delivery needs!</p>
HTML;
    }

    private function getRefundPolicyContent(): string
    {
        return <<<'HTML'
<h2>Refund Policy</h2>

<p><strong>Effective Date:</strong> February 11, 2026</p>

<p>At Sunrise Family Mart, we are committed to ensuring your satisfaction with every order. This Refund Policy outlines the circumstances under which refunds are available and the process for requesting them.</p>

<h3>Eligibility for Refunds</h3>
<p>You may be eligible for a refund in the following situations:</p>
<ul>
    <li><strong>Damaged Items:</strong> Products that arrive damaged, broken, or spoiled</li>
    <li><strong>Wrong Items:</strong> You received items different from what you ordered</li>
    <li><strong>Missing Items:</strong> Items listed in your order confirmation but not delivered</li>
    <li><strong>Quality Issues:</strong> Products that do not meet reasonable quality standards (e.g., expired items, poor freshness)</li>
    <li><strong>Order Cancellation:</strong> Orders cancelled by us due to unavailability or other issues</li>
    <li><strong>Non-Delivery:</strong> Order was not delivered within a reasonable timeframe or at all</li>
</ul>

<h3>How to Request a Refund</h3>
<p>To request a refund, please follow these steps:</p>
<ol>
    <li><strong>Contact Us Promptly:</strong> Report the issue within 24 hours of delivery through:
        <ul>
            <li>Our mobile app's customer support feature</li>
            <li>Email: <a href="mailto:support@sunrisefamilymart.com">support@sunrisefamilymart.com</a></li>
            <li>In-app chat or phone support</li>
        </ul>
    </li>
    <li><strong>Provide Order Details:</strong> Include your order number, details of the issue, and photos if applicable (especially for damaged or wrong items)</li>
    <li><strong>Keep Items:</strong> Do not dispose of damaged or incorrect items until we've reviewed your claim, as we may need to inspect them</li>
</ol>

<h3>Refund Processing</h3>
<p>Once your refund request is received and approved:</p>
<ul>
    <li><strong>Review Time:</strong> We will review your request within 24-48 hours</li>
    <li><strong>Approval Notification:</strong> You will be notified via email or app notification once approved</li>
    <li><strong>Refund Timeline:</strong>
        <ul>
            <li>Refunds are processed within 5-7 business days after approval</li>
            <li>For cash on delivery orders, refunds may be issued as store credit or bank transfer</li>
        </ul>
    </li>
    <li><strong>Refund Method:</strong> Refunds are issued to the original payment method used for the order
        <ul>
            <li>Credit/Debit Card: 5-10 business days (depending on your bank)</li>
            <li>Digital Wallets: 3-5 business days</li>
            <li>Cash on Delivery: Store credit or bank transfer (as per your preference)</li>
        </ul>
    </li>
</ul>

<h3>Non-Refundable Items</h3>
<p>Certain items are not eligible for refunds:</p>
<ul>
    <li><strong>Perishable Goods:</strong> Fresh produce, dairy, and other perishables that were accepted without complaint at delivery</li>
    <li><strong>Opened or Used Items:</strong> Items that have been opened, partially consumed, or used (unless defective)</li>
    <li><strong>Special Orders:</strong> Custom or specially ordered items (unless defective or incorrect)</li>
    <li><strong>Hygiene Products:</strong> Items where opening or use creates health or hygiene concerns</li>
    <li><strong>Promotional Items:</strong> Free items or promotional gifts</li>
</ul>

<h3>Partial Refunds</h3>
<p>In some cases, we may offer partial refunds:</p>
<ul>
    <li>When only some items in an order are problematic</li>
    <li>For minor quality issues where the product is still usable</li>
    <li>When packaging is damaged but the product inside is intact</li>
    <li>As compensation for delays or inconveniences (at our discretion)</li>
</ul>

<h3>Store Credit</h3>
<p>As an alternative to refunds, we may offer store credit that can be used for future orders. Store credit:</p>
<ul>
    <li>Is added directly to your account</li>
    <li>Can be used for any future purchase</li>
    <li>Does not expire</li>
    <li>May be offered as a goodwill gesture for minor inconveniences</li>
</ul>

<h3>Fraudulent Refund Requests</h3>
<p>We reserve the right to:</p>
<ul>
    <li>Investigate suspicious refund requests</li>
    <li>Request additional evidence (photos, videos) for validation</li>
    <li>Deny refunds for fraudulent or abusive claims</li>
    <li>Suspend or terminate accounts engaged in fraudulent refund activity</li>
</ul>

<h3>Contact for Refund Issues</h3>
<p>If you have questions or concerns about your refund request, please contact our customer support team:</p>
<ul>
    <li><strong>Email:</strong> <a href="mailto:support@sunrisefamilymart.com">support@sunrisefamilymart.com</a></li>
    <li><strong>Website:</strong> <a href="https://sunrisefamilymart.com">sunrisefamilymart.com</a></li>
    <li><strong>Business Name:</strong> Sunrise Family Mart</li>
</ul>

<p>We value your business and are committed to resolving any issues promptly and fairly.</p>
HTML;
    }

    private function getCancellationPolicyContent(): string
    {
        return <<<'HTML'
<h2>Cancellation Policy</h2>

<p><strong>Effective Date:</strong> February 11, 2026</p>

<p>At Sunrise Family Mart, we understand that plans change. This Cancellation Policy explains when and how you can cancel your order, and the refund implications based on the order status.</p>

<h3>Cancellation Before Order Processing</h3>
<p>If you need to cancel your order before it has been processed:</p>
<ul>
    <li><strong>Eligibility:</strong> Full refund available</li>
    <li><strong>Timeline:</strong> Orders can be cancelled within 5-10 minutes of placement, before processing begins</li>
    <li><strong>How to Cancel:</strong>
        <ul>
            <li>Use the "Cancel Order" button in the app (if order status shows "Pending" or "Confirmed")</li>
            <li>Contact customer support immediately via app chat, email, or phone</li>
        </ul>
    </li>
    <li><strong>Refund:</strong> Full refund to original payment method within 5-7 business days</li>
</ul>

<h3>Cancellation After Processing/Preparation</h3>
<p>Once your order has been processed or is being prepared:</p>
<ul>
    <li><strong>Eligibility:</strong> Cancellation may be possible but subject to partial refund or cancellation fee</li>
    <li><strong>Partial Refund:</strong> A processing fee or restocking fee may be deducted (typically 10-20% of order value)</li>
    <li><strong>Perishable Items:</strong> Orders containing perishable items that are already prepared may not be eligible for refund</li>
    <li><strong>How to Request:</strong> Contact customer support immediately; cancellation is not guaranteed and depends on order status</li>
</ul>

<h3>Cancellation After Dispatch</h3>
<p>Once your order has been dispatched for delivery:</p>
<ul>
    <li><strong>Eligibility:</strong> Cancellation is generally not available</li>
    <li><strong>Exception:</strong> If the delivery is significantly delayed or there are issues with the order, you may refuse delivery and request a refund</li>
    <li><strong>Refusal at Delivery:</strong> If you refuse the order at the time of delivery:
        <ul>
            <li>Inspect the order at delivery</li>
            <li>Refuse only if items are damaged, incorrect, or have quality issues</li>
            <li>Notify our customer support immediately</li>
            <li>Refund eligibility will be reviewed based on the reason for refusal</li>
        </ul>
    </li>
</ul>

<h3>How to Cancel Your Order</h3>
<p>To cancel your order, use one of the following methods:</p>
<ol>
    <li><strong>Through the App:</strong>
        <ul>
            <li>Go to "My Orders" section</li>
            <li>Select the order you want to cancel</li>
            <li>Click the "Cancel Order" button (available only if order is pending)</li>
            <li>Confirm cancellation and provide reason (optional)</li>
        </ul>
    </li>
    <li><strong>Contact Customer Support:</strong>
        <ul>
            <li>Email: <a href="mailto:support@sunrisefamilymart.com">support@sunrisefamilymart.com</a></li>
            <li>In-app chat support</li>
            <li>Phone support (if available)</li>
            <li>Provide your order number and reason for cancellation</li>
        </ul>
    </li>
</ol>

<h3>Refund for Cancelled Orders</h3>
<p>Refunds for cancelled orders are processed as follows:</p>
<ul>
    <li><strong>Full Cancellation (Before Processing):</strong> 100% refund to original payment method</li>
    <li><strong>Partial Cancellation (During Processing):</strong> Refund minus processing fee (if applicable)</li>
    <li><strong>Refund Timeline:</strong>
        <ul>
            <li>Processing time: 5-7 business days from cancellation approval</li>
            <li>Credit/Debit Card: 5-10 business days (depending on bank)</li>
            <li>Digital Wallets: 3-5 business days</li>
            <li>Cash on Delivery: Store credit or bank transfer</li>
        </ul>
    </li>
    <li><strong>Notification:</strong> You will receive email or app notification when refund is processed</li>
</ul>

<h3>Cancellation by Sunrise Family Mart</h3>
<p>We reserve the right to cancel orders in the following circumstances:</p>
<ul>
    <li><strong>Product Unavailability:</strong> Item(s) out of stock or no longer available</li>
    <li><strong>Pricing Errors:</strong> Significant pricing or technical errors on our platform</li>
    <li><strong>Delivery Issues:</strong> Unable to deliver to the specified address or area</li>
    <li><strong>Payment Issues:</strong> Payment authorization fails or is flagged as suspicious</li>
    <li><strong>Violation of Terms:</strong> Order violates our Terms and Conditions</li>
</ul>
<p>If we cancel your order, you will receive a full refund and notification explaining the reason.</p>

<h3>Order Modifications</h3>
<p>If you need to modify your order instead of cancelling:</p>
<ul>
    <li>Contact customer support as soon as possible</li>
    <li>Modifications are only possible before order processing begins</li>
    <li>You may be able to add or remove items, change delivery address, or update delivery time</li>
    <li>Once processing starts, modifications may not be possible</li>
</ul>

<h3>Special Circumstances</h3>
<p>In exceptional cases (natural disasters, emergencies, system failures), we may:</p>
<ul>
    <li>Offer flexible cancellation options</li>
    <li>Provide full refunds regardless of order status</li>
    <li>Issue store credit for future use</li>
</ul>

<h3>Contact Information</h3>
<p>For questions or assistance with order cancellations, please contact us:</p>
<ul>
    <li><strong>Email:</strong> <a href="mailto:support@sunrisefamilymart.com">support@sunrisefamilymart.com</a></li>
    <li><strong>Website:</strong> <a href="https://sunrisefamilymart.com">sunrisefamilymart.com</a></li>
    <li><strong>Business Name:</strong> Sunrise Family Mart</li>
</ul>

<p>We appreciate your understanding and are here to help make your experience as smooth as possible.</p>
HTML;
    }

    private function getReturnPolicyContent(): string
    {
        return <<<'HTML'
<h2>Return Policy</h2>

<p><strong>Effective Date:</strong> February 11, 2026</p>

<p>At Sunrise Family Mart, your satisfaction is our priority. This Return Policy outlines the conditions under which you can return items and the process for initiating a return.</p>

<h3>Return Eligibility</h3>
<p>You may return items in the following circumstances:</p>
<ul>
    <li><strong>Damaged Items:</strong> Products received in damaged, broken, or defective condition</li>
    <li><strong>Defective Products:</strong> Items that do not function as intended or have manufacturing defects</li>
    <li><strong>Wrong Items:</strong> You received items that differ from what you ordered</li>
    <li><strong>Quality Issues:</strong> Products that do not meet reasonable quality standards (e.g., spoiled, expired, or stale items)</li>
    <li><strong>Missing Items:</strong> If items were listed on your invoice but not delivered (contact us first for verification)</li>
</ul>

<h3>Return Timeframe</h3>
<p>Returns must be initiated within specific timeframes:</p>
<ul>
    <li><strong>24 Hours:</strong> Most grocery and perishable items must be returned within 24 hours of delivery</li>
    <li><strong>At Delivery:</strong> For damaged or wrong items, it's best to report the issue immediately upon delivery</li>
    <li><strong>Quality Complaints:</strong> Issues with freshness or quality should be reported within 24 hours of delivery</li>
</ul>
<p>Returns requested after the specified timeframe may not be accepted, except in exceptional circumstances.</p>

<h3>How to Initiate a Return</h3>
<p>To return an item, follow these steps:</p>
<ol>
    <li><strong>Contact Customer Support:</strong> Reach out within 24 hours of delivery through:
        <ul>
            <li>Our mobile app's customer support feature</li>
            <li>Email: <a href="mailto:support@sunrisefamilymart.com">support@sunrisefamilymart.com</a></li>
            <li>In-app chat or phone support</li>
        </ul>
    </li>
    <li><strong>Provide Information:</strong> Include:
        <ul>
            <li>Order number</li>
            <li>Item(s) you wish to return and reason</li>
            <li>Photos of damaged, defective, or wrong items (if applicable)</li>
        </ul>
    </li>
    <li><strong>Await Approval:</strong> Our team will review your request and respond within 24-48 hours</li>
    <li><strong>Receive Return Instructions:</strong> If approved, you'll receive instructions on how to return the item (pickup or drop-off)</li>
</ol>

<h3>Return Process</h3>
<p>Once your return is approved, we will arrange for:</p>
<ul>
    <li><strong>Pickup Service:</strong> In most cases, we will schedule a pickup from your delivery address at no additional cost</li>
    <li><strong>Drop-off (if applicable):</strong> You may be asked to drop off items at a designated location in specific situations</li>
    <li><strong>Inspection:</strong> Returned items will be inspected to verify the condition and reason for return</li>
</ul>

<h3>Refund After Return</h3>
<p>After your return is received and inspected:</p>
<ul>
    <li><strong>Approval Notification:</strong> You will be notified within 2-3 business days if your return is approved</li>
    <li><strong>Refund Processing:</strong> Approved refunds are processed within 5-7 business days</li>
    <li><strong>Refund Method:</strong> Refunds are issued to the original payment method:
        <ul>
            <li>Credit/Debit Card: 5-10 business days</li>
            <li>Digital Wallets: 3-5 business days</li>
            <li>Cash on Delivery: Store credit or bank transfer (as per your preference)</li>
        </ul>
    </li>
</ul>

<h3>Non-Returnable Items</h3>
<p>The following items cannot be returned unless defective or incorrect:</p>
<ul>
    <li><strong>Perishable Goods:</strong> Fresh produce, meat, dairy, and bakery items that were accepted without complaint at delivery</li>
    <li><strong>Opened/Used Items:</strong> Products that have been opened, partially consumed, or used (unless defective)</li>
    <li><strong>Personal Care Items:</strong> Hygiene and personal care products that have been unsealed</li>
    <li><strong>Alcohol and Tobacco:</strong> Due to legal restrictions (unless defective or incorrect)</li>
    <li><strong>Custom or Special Orders:</strong> Items specially ordered or customized for you</li>
    <li><strong>Promotional/Free Items:</strong> Complimentary items or promotional gifts</li>
</ul>

<h3>Condition of Returned Items</h3>
<p>For returns to be accepted:</p>
<ul>
    <li>Items must be in their original packaging (if applicable)</li>
    <li>Items should be unused and in the same condition as received (except for defective items)</li>
    <li>Include all accessories, manuals, and packaging materials (for non-perishable items)</li>
    <li>Perishable items should be refrigerated or stored properly pending return</li>
</ul>

<h3>Exchange Policy</h3>
<p>If you received a wrong or defective item, we offer:</p>
<ul>
    <li><strong>Replacement:</strong> We will replace the item with the correct or non-defective version at no additional cost</li>
    <li><strong>Refund Option:</strong> If you prefer, you may choose a refund instead of a replacement</li>
    <li><strong>Expedited Processing:</strong> Replacements for wrong or defective items are prioritized</li>
</ul>

<h3>Return Shipping Costs</h3>
<ul>
    <li><strong>Our Responsibility:</strong> If the return is due to our error (wrong item, damaged item, defective product), we cover all return costs</li>
    <li><strong>Customer Responsibility:</strong> If you return an item for personal reasons (changed mind, ordered wrong item), you may be responsible for return costs (though we generally do not accept such returns for groceries)</li>
</ul>

<h3>Damaged During Delivery</h3>
<p>If items are damaged during delivery:</p>
<ul>
    <li>Inspect your order immediately upon delivery</li>
    <li>Report damage to the delivery person if possible</li>
    <li>Take photos of damaged items and packaging</li>
    <li>Contact customer support within 24 hours</li>
    <li>Do not dispose of damaged items until we've reviewed your claim</li>
</ul>

<h3>Fraudulent Returns</h3>
<p>We reserve the right to:</p>
<ul>
    <li>Investigate suspicious or excessive return requests</li>
    <li>Request additional evidence (photos, videos) to validate claims</li>
    <li>Deny returns for fraudulent or abusive claims</li>
    <li>Suspend or terminate accounts engaged in fraudulent return activity</li>
</ul>

<h3>Contact Information</h3>
<p>For questions or assistance with returns, please contact us:</p>
<ul>
    <li><strong>Email:</strong> <a href="mailto:support@sunrisefamilymart.com">support@sunrisefamilymart.com</a></li>
    <li><strong>Website:</strong> <a href="https://sunrisefamilymart.com">sunrisefamilymart.com</a></li>
    <li><strong>Business Name:</strong> Sunrise Family Mart</li>
</ul>

<p>We appreciate your trust in Sunrise Family Mart and are committed to ensuring your satisfaction with every order.</p>
HTML;
    }
};
