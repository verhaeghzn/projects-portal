@extends('layouts.app')

@section('title', 'Privacy Statement')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-12">
    <div class="mb-6">
        <h1 class="text-2xl sm:text-3xl lg:text-4xl font-heading text-gray-900 mb-3 sm:mb-4">Privacy Statement</h1>
        <p class="text-sm text-gray-600 mb-4">
            Last updated: {{ date('j F Y') }}
        </p>
        <div class="mb-4">
            <a href="{{ route('privacy', ['lang' => 'nl']) }}" class="text-sm text-[#7fabc9] hover:underline">
                Lees deze pagina in het Nederlands
            </a>
        </div>
    </div>

    <div class="space-y-6 text-sm sm:text-base text-gray-700">
        <section class="bg-gray-50 rounded-lg p-5 sm:p-6 lg:p-8">
            <h2 class="text-xl sm:text-2xl font-heading text-gray-900 mb-3 sm:mb-4">1. Introduction</h2>
            <p class="mb-3">
                This privacy statement explains how the Mechanical Engineering Projects Portal (the &quot;Portal&quot;) processes and protects personal data.
                The Portal is managed by the Department of Mechanical Engineering of Eindhoven University of Technology (TU/e).
            </p>
            <p>
                The Portal supports students enrolled in an academic program in selecting a thesis/graduation project.
                The Portal is designed to process as little personal data as possible. We do not create user profiles and we do not use tracking
                or analytics to analyse or profile visitor behaviour.
            </p>
        </section>

        <section class="bg-gray-50 rounded-lg p-5 sm:p-6 lg:p-8">
            <h2 class="text-xl sm:text-2xl font-heading text-gray-900 mb-3 sm:mb-4">2. Controller</h2>
            <p class="mb-3">
                The <strong>TU/e Executive Board</strong> is the controller within the meaning of the GDPR for the processing activities described in this privacy statement.
                The Department of Mechanical Engineering manages the Portal on behalf of TU/e.
            </p>
            <p class="mb-2">
                Correspondence address:
            </p>
            <p class="mb-2">
                <strong>Eindhoven University of Technology (TU/e)</strong><br>
                Department of Mechanical Engineering<br>
                PO Box 513<br>
                5600 MB Eindhoven<br>
                The Netherlands
            </p>
            <p class="mb-3">
                For questions regarding this privacy statement or the processing within the Portal, you may contact:
                <strong>J. (Joris) Remmers</strong>.
            </p>
            <p>
                For questions or exercising your privacy rights: <strong>privacy@tue.nl</strong>.<br>
                For complaints: <strong>dataprotectionofficer@tue.nl</strong>.
            </p>
        </section>

        <section class="bg-gray-50 rounded-lg p-5 sm:p-6 lg:p-8">
            <h2 class="text-xl sm:text-2xl font-heading text-gray-900 mb-3 sm:mb-4">3. Authentication and Identity Provider</h2>
            <p class="mb-3">
                The Portal uses SURFconext for authentication via Security Assertion Markup Language (SAML). SURFconext enables secure single sign-on (SSO)
                for education and research institutions in the Netherlands.
            </p>
            <p class="mb-3">
                When you sign in to the Portal:
            </p>
            <ul class="list-disc list-inside mb-3 space-y-2 ml-4">
                <li>You are redirected to SURFconext for authentication.</li>
                <li>SURFconext verifies your identity via your home institution (e.g. TU/e).</li>
                <li>After successful authentication, SURFconext provides the Portal with a SAML assertion containing the minimum necessary attributes.</li>
                <li>
                    We only process the minimum required data for affiliation verification and access control, such as:
                    <ul class="list-disc list-inside ml-6 mt-2 space-y-1">
                        <li>A persistent identifier (pseudonym) to link your session.</li>
                        <li>Institutional affiliation (e.g. student/staff) to grant access.</li>
                    </ul>
                </li>
            </ul>
            <p>
                We do not store your password. Authentication is handled by SURFconext and your home institution.
            </p>
        </section>

        <section class="bg-gray-50 rounded-lg p-5 sm:p-6 lg:p-8">
            <h2 class="text-xl sm:text-2xl font-heading text-gray-900 mb-3 sm:mb-4">4. Which personal data do we process?</h2>
            <p class="mb-3">
                As a general principle, the Portal does not process personal data in the sense of creating user profiles or storing identifying information on a long-term basis.
                However, during the authentication process we temporarily process a limited set of personal data provided via SURFconext
                (such as a persistent identifier and affiliation) in order to verify whether you are allowed to access the Portal.
            </p>
            <p class="mb-3">
                This authentication data is used solely for granting access and security. It is not used for tracking, profiling, or analysing individual usage.
            </p>
            <p class="mb-3">
                Information entered via filters or search functionality (such as project type, tags, sections, or other search criteria) is not stored on a long-term basis
                as personal data and is not linked to your identity or used for tracking/profiling.
            </p>
            <p>
                Technical logs (such as web server logs and error logs) may temporarily contain IP addresses and technical metadata for security, abuse prevention,
                and troubleshooting purposes. This data is not used for tracking or profiling.
            </p>
        </section>

        <section class="bg-gray-50 rounded-lg p-5 sm:p-6 lg:p-8">
            <h2 class="text-xl sm:text-2xl font-heading text-gray-900 mb-3 sm:mb-4">5. Purposes and legal basis</h2>
            <p class="mb-3">
                We process data only for the following purposes:
            </p>
            <ul class="list-disc list-inside mb-3 space-y-2 ml-4">
                <li>
                    <strong>Affiliation verification and access control:</strong>
                    to verify that you are a TU/e member (or otherwise authorised) and to grant access to the Portal.
                    <br>
                    <span class="text-gray-700">
                        Legal basis: performance of a task carried out in the public interest (Article 6(1)(e) GDPR), namely supporting educational activities
                        for enrolled students in selecting a thesis/graduation project and securely offering an internal Portal.
                    </span>
                </li>
                <li>
                    <strong>Security and continuity:</strong>
                    to keep the Portal secure and reliable, prevent abuse, and detect and resolve technical issues.
                    <br>
                    <span class="text-gray-700">
                        Legal basis: performance of a task carried out in the public interest (Article 6(1)(e) GDPR) and, where appropriate,
                        legitimate interest (Article 6(1)(f) GDPR) in securing systems and services.
                    </span>
                </li>
            </ul>
            <p>
                The Portal does not create user profiles and does not use tracking or analytics for behavioural analysis or profiling.
            </p>
        </section>

        <section class="bg-gray-50 rounded-lg p-5 sm:p-6 lg:p-8">
            <h2 class="text-xl sm:text-2xl font-heading text-gray-900 mb-3 sm:mb-4">6. Data sharing and access</h2>
            <p class="mb-3">
                We do not share personal data for commercial purposes and we do not provide personal data to third parties for tracking, marketing, or profiling.
            </p>
            <p class="mb-3">
                For authentication, we use SURFconext. Necessary attributes are transferred from your home institution via SURFconext to the Portal
                in order to grant access.
            </p>

            <h3 class="font-heading text-gray-900 mt-4 mb-2 text-lg sm:text-xl">6.1 Access within TU/e</h3>
            <p class="mb-3">
                Within TU/e, functional and technical administrators may have access to administrative functionality and (where required) technical data
                for management, support, security, and troubleshooting purposes. Such access is limited to what is necessary to perform their tasks
                and is subject to internal authorisations and access controls.
            </p>

            <h3 class="font-heading text-gray-900 mt-4 mb-2 text-lg sm:text-xl">6.2 Service providers (processors)</h3>
            <p class="mb-3">
                We may use external service providers (e.g. hosting providers) for technical infrastructure. If such parties process personal data on behalf of TU/e,
                appropriate arrangements are made in line with TU/e policy (such as a data processing agreement) and appropriate security measures.
            </p>

            <p>
                We do not sell, rent, or commercialise personal data.
            </p>
        </section>

        <section class="bg-gray-50 rounded-lg p-5 sm:p-6 lg:p-8">
            <h2 class="text-xl sm:text-2xl font-heading text-gray-900 mb-3 sm:mb-4">7. Retention periods</h2>
            <p class="mb-3">
                Authentication attributes processed via SURFconext are used only during sign-in and the active session and are not stored on a long-term basis
                in a user database or profile.
            </p>
            <p class="mb-3">
                Technical logs (such as web server logs and error logs) may be retained temporarily for security and troubleshooting. These logs are retained for a limited time
                and deleted periodically in accordance with applicable TU/e retention policies and operational necessity.
            </p>
            <p>
                Because the Portal does not maintain user profiles, in practice there is typically no long-term stored personal data within the Portal to delete upon request.
            </p>
        </section>

        <section class="bg-gray-50 rounded-lg p-5 sm:p-6 lg:p-8">
            <h2 class="text-xl sm:text-2xl font-heading text-gray-900 mb-3 sm:mb-4">8. Your rights</h2>
            <p class="mb-3">
                Under the General Data Protection Regulation (GDPR), you may have (depending on the circumstances) the following rights:
            </p>
            <ul class="list-disc list-inside mb-3 space-y-2 ml-4">
                <li>Right of access</li>
                <li>Right to rectification</li>
                <li>Right to erasure</li>
                <li>Right to restriction of processing</li>
                <li>Right to object</li>
                <li>Right to data portability (where applicable)</li>
                <li>Rights related to automated decision-making and profiling (where applicable)</li>
            </ul>
            <p class="mb-3">
                Because the Portal does not create user profiles and does not store personal data on a long-term basis, some rights may have limited practical applicability
                within the Portal itself. If your request concerns authentication data held by your home institution or SURFconext, it may be necessary to handle your request
                (partly) via those parties.
            </p>
            <p class="mb-3">
                For questions or exercising your rights, please contact: <strong>privacy@tue.nl</strong>.
            </p>
            <p>
                You have the right to submit a complaint to the Data Protection Officer via: <strong>dataprotectionofficer@tue.nl</strong>,
                and to the Dutch Data Protection Authority (Autoriteit Persoonsgegevens) if you believe your data protection rights have been violated.
            </p>
        </section>

        <section class="bg-gray-50 rounded-lg p-5 sm:p-6 lg:p-8">
            <h2 class="text-xl sm:text-2xl font-heading text-gray-900 mb-3 sm:mb-4">9. Data security</h2>
            <p class="mb-3">
                We implement appropriate technical and organisational measures to ensure secure operation of the Portal. These measures include:
            </p>
            <ul class="list-disc list-inside mb-3 space-y-2 ml-4">
                <li>Encryption of data in transit (HTTPS/TLS)</li>
                <li>Secure authentication via SURFconext</li>
                <li>Access controls and authentication requirements for Portal access</li>
                <li>Regular security reviews and updates</li>
                <li>Secure hosting infrastructure and hardening where appropriate</li>
            </ul>
            <p>
                No method of transmission or storage is 100% secure. We cannot guarantee absolute security, but we apply appropriate safeguards
                in line with the state of the art and the level of risk.
            </p>
        </section>

        <section class="bg-gray-50 rounded-lg p-5 sm:p-6 lg:p-8">
            <h2 class="text-xl sm:text-2xl font-heading text-gray-900 mb-3 sm:mb-4">10. Cookies and similar technologies</h2>
            <p class="mb-3">
                The Portal uses only functional cookies and similar technologies that are necessary for the operation of the website, including:
            </p>
            <ul class="list-disc list-inside mb-3 space-y-2 ml-4">
                <li>Maintaining your session and authentication status during your visit</li>
                <li>Ensuring the correct and secure functioning of the Portal</li>
            </ul>
            <p class="mb-3">
                We do not use cookies for tracking, marketing, or profiling. Session cookies are intended for the active session and are not used
                to follow visitors across websites.
            </p>
            <p>
                You can manage cookies via your browser settings. Disabling certain functional cookies may affect the operation of the Portal.
            </p>
        </section>

        <section class="bg-gray-50 rounded-lg p-5 sm:p-6 lg:p-8">
            <h2 class="text-xl sm:text-2xl font-heading text-gray-900 mb-3 sm:mb-4">11. International transfers</h2>
            <p>
                The Portal is intended for use within the European Economic Area (EEA). Authentication via SURFconext takes place within the EEA.
                We do not intend to transfer personal data outside the EEA as part of the standard functionality of the Portal.
            </p>
        </section>

        <section class="bg-gray-50 rounded-lg p-5 sm:p-6 lg:p-8">
            <h2 class="text-xl sm:text-2xl font-heading text-gray-900 mb-3 sm:mb-4">12. Changes to this statement</h2>
            <p>
                We may update this privacy statement from time to time. We will publish significant changes on this page and update the
                &quot;Last updated&quot; date. We recommend reviewing this statement periodically.
            </p>
        </section>

        <section class="bg-gray-50 rounded-lg p-5 sm:p-6 lg:p-8">
            <h2 class="text-xl sm:text-2xl font-heading text-gray-900 mb-3 sm:mb-4">13. Contact details</h2>
            <p class="mb-2">
                If you have questions or concerns about this privacy statement, or if you wish to exercise your rights, you may contact:
            </p>
            <p class="mb-2">
                <strong>J. (Joris) Remmers</strong><br>
                Department of Mechanical Engineering<br>
                Eindhoven University of Technology
            </p>
            <p class="mb-2">
                For questions or exercising your privacy rights: <strong>privacy@tue.nl</strong>.<br>
                For complaints: <strong>dataprotectionofficer@tue.nl</strong>.
            </p>
            <p>
                For technical support, please contact: <strong>B. (Bart) Verhaegh</strong>
            </p>
        </section>
    </div>
</div>
@endsection
