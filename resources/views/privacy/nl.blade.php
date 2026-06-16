@extends('layouts.app')

@section('title', 'Privacyverklaring')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-12">
    <div class="mb-6">
        <h1 class="text-2xl sm:text-3xl lg:text-4xl font-heading text-gray-900 mb-3 sm:mb-4">Privacyverklaring</h1>
        <p class="text-sm text-gray-600 mb-4">
            Laatst bijgewerkt: {{ date('j F Y') }}
        </p>
        <div class="mb-4">
            <a href="{{ route('privacy', ['lang' => 'en']) }}" class="text-sm link-primary">
                Read this page in English
            </a>
        </div>
    </div>

    <div class="space-y-6 text-sm sm:text-base text-gray-700">
        <section class="bg-gray-50 rounded-lg p-5 sm:p-6 lg:p-8">
            <h2 class="text-xl sm:text-2xl font-heading text-gray-900 mb-3 sm:mb-4">1. Inleiding</h2>
            <p class="mb-3">
                Deze privacyverklaring legt uit hoe het CEM Projects Portal (het &quot;Portaal&quot;) persoonsgegevens verwerkt en beschermt.
                Het Portaal wordt beheerd door de Computational and Experimental Mechanics (CEM) Divisie binnen de faculteit Mechanical Engineering
                van de Technische Universiteit Eindhoven (TU/e).
            </p>
            <p>
                Het Portaal ondersteunt studenten die zijn ingeschreven bij een opleiding bij het kiezen van een afstudeer-/thesisproject.
                Het Portaal is ontworpen om zo min mogelijk persoonsgegevens te verwerken. Wij maken geen gebruikersprofielen aan en gebruiken geen tracking
                of analytics om bezoekersgedrag te analyseren of te profileren.
            </p>
        </section>

        <section class="bg-gray-50 rounded-lg p-5 sm:p-6 lg:p-8">
            <h2 class="text-xl sm:text-2xl font-heading text-gray-900 mb-3 sm:mb-4">2. Verantwoordelijke voor de verwerking</h2>
            <p class="mb-3">
                De <strong>TU/e Executive Board (College van Bestuur)</strong> is verwerkingsverantwoordelijke in de zin van de AVG voor de verwerkingen die
                in deze privacyverklaring worden beschreven. De CEM Divisie beheert het Portaal namens TU/e.
            </p>
            <p class="mb-2">
                Correspondentieadres:
            </p>
            <p class="mb-2">
                <strong>Technische Universiteit Eindhoven (TU/e)</strong><br>
                Computational and Experimental Mechanics (CEM) Divisie<br>
                Faculteit Werktuigbouwkunde<br>
                Postbus 513<br>
                5600 MB Eindhoven<br>
                Nederland
            </p>
            <p class="mb-3">
                Voor vragen over deze privacyverklaring of de verwerking binnen het Portaal kunt u contact opnemen met:
                <strong>J. (Joris) Remmers</strong>.
            </p>
            <p>
                Voor vragen of het uitoefenen van uw privacyrechten: <strong>privacy@tue.nl</strong>.<br>
                Voor klachten: <strong>dataprotectionofficer@tue.nl</strong>.
            </p>
        </section>

        <section class="bg-gray-50 rounded-lg p-5 sm:p-6 lg:p-8">
            <h2 class="text-xl sm:text-2xl font-heading text-gray-900 mb-3 sm:mb-4">3. Authenticatie en Identity Provider</h2>
            <p class="mb-3">
                Het Portaal gebruikt SURFconext voor authenticatie via Security Assertion Markup Language (SAML). SURFconext maakt veilige single sign-on (SSO)
                mogelijk voor onderwijs- en onderzoeksinstellingen in Nederland.
            </p>
            <p class="mb-3">
                Wanneer u inlogt op het Portaal:
            </p>
            <ul class="list-disc list-inside mb-3 space-y-2 ml-4">
                <li>Wordt u doorgestuurd naar SURFconext voor authenticatie.</li>
                <li>SURFconext verifieert uw identiteit via uw thuisinstelling (bijv. TU/e).</li>
                <li>Na succesvolle authenticatie stuurt SURFconext het Portaal een SAML-assertie met de minimaal noodzakelijke attributen.</li>
                <li>
                    Wij verwerken uitsluitend de minimaal noodzakelijke gegevens voor affiliatieverificatie en toegangsbeheer, zoals:
                    <ul class="list-disc list-inside ml-6 mt-2 space-y-1">
                        <li>Een persistente identificatiecode (pseudoniem) om de sessie te koppelen.</li>
                        <li>Institutionele affiliatie (bijv. student/medewerker) om toegang te verlenen.</li>
                    </ul>
                </li>
            </ul>
            <p>
                Wij slaan uw wachtwoord niet op. De authenticatie wordt afgehandeld door SURFconext en uw thuisinstelling.
            </p>
        </section>

        <section class="bg-gray-50 rounded-lg p-5 sm:p-6 lg:p-8">
            <h2 class="text-xl sm:text-2xl font-heading text-gray-900 mb-3 sm:mb-4">4. Welke persoonsgegevens verwerken wij?</h2>
            <p class="mb-3">
                Het Portaal verwerkt in beginsel geen persoonsgegevens in de zin van het aanmaken van gebruikersprofielen of het duurzaam opslaan van
                identificerende gegevens. Tijdens het authenticatieproces verwerken wij echter tijdelijk een beperkte set persoonsgegevens die via SURFconext
                wordt verstrekt (zoals een persistente identificatiecode en affiliatie) om te controleren of u toegang mag krijgen.
            </p>
            <p class="mb-3">
                Deze authenticatiegegevens worden uitsluitend gebruikt voor het verlenen van toegang en beveiliging. Zij worden niet gebruikt voor tracking,
                profilering of analyse van individueel gebruik.
            </p>
            <p class="mb-3">
                Informatie die u invoert via filters of zoekfunctionaliteit (zoals projecttype, tags, secties of andere zoekcriteria) wordt niet duurzaam opgeslagen
                als persoonsgegeven en wordt niet gekoppeld aan uw identiteit of gebruikt voor tracking/profilering.
            </p>
            <p>
                Technische logbestanden (zoals webserver- en foutlogs) kunnen tijdelijk IP-adressen en technische metadata bevatten ten behoeve van beveiliging,
                misbruikpreventie en foutopsporing. Deze gegevens worden niet gebruikt voor tracking of profilering.
            </p>
        </section>

        <section class="bg-gray-50 rounded-lg p-5 sm:p-6 lg:p-8">
            <h2 class="text-xl sm:text-2xl font-heading text-gray-900 mb-3 sm:mb-4">5. Doeleinden en rechtsgrond</h2>
            <p class="mb-3">
                Wij verwerken gegevens uitsluitend voor de volgende doeleinden:
            </p>
            <ul class="list-disc list-inside mb-3 space-y-2 ml-4">
                <li>
                    <strong>Affiliatieverificatie en toegangsbeheer:</strong>
                    om te verifiëren of u lid bent van de TU/e (of een geautoriseerde instelling) en om toegang te verlenen tot het Portaal.
                    <br>
                    <span class="text-gray-700">
                        Rechtsgrond: uitvoering van een taak van algemeen belang (artikel 6 lid 1 sub e AVG), namelijk het ondersteunen van onderwijsactiviteiten
                        voor ingeschreven studenten bij het kiezen van een afstudeer-/thesisproject, en het veilig aanbieden van een intern Portaal.
                    </span>
                </li>
                <li>
                    <strong>Beveiliging en bedrijfscontinuïteit:</strong>
                    om het Portaal veilig en betrouwbaar te laten functioneren, misbruik te voorkomen en technische fouten op te sporen.
                    <br>
                    <span class="text-gray-700">
                        Rechtsgrond: uitvoering van een taak van algemeen belang (artikel 6 lid 1 sub e AVG) en, waar passend, het gerechtvaardigd belang
                        (artikel 6 lid 1 sub f AVG) bij het beveiligen van systemen en diensten.
                    </span>
                </li>
            </ul>
            <p>
                Het Portaal maakt geen gebruikersprofielen aan en gebruikt geen tracking of analytics voor gedragsanalyse of profilering.
            </p>
        </section>

        <section class="bg-gray-50 rounded-lg p-5 sm:p-6 lg:p-8">
            <h2 class="text-xl sm:text-2xl font-heading text-gray-900 mb-3 sm:mb-4">6. Delen van gegevens en toegang</h2>
            <p class="mb-3">
                Wij delen geen persoonsgegevens voor commerciële doeleinden en verstrekken geen persoonsgegevens aan derden voor tracking, marketing of profilering.
            </p>
            <p class="mb-3">
                Voor authenticatie maken wij gebruik van SURFconext. Hierbij worden noodzakelijke attributen vanuit uw thuisinstelling via SURFconext aan het Portaal
                doorgegeven om toegang te verlenen.
            </p>

            <h3 class="font-heading text-gray-900 mt-4 mb-2 text-lg sm:text-xl">6.1 Toegang binnen TU/e</h3>
            <p class="mb-3">
                Binnen TU/e kunnen functionele en technische beheerders toegang hebben tot beheerfunctionaliteiten en (waar noodzakelijk) technische gegevens
                voor beheer, ondersteuning, beveiliging en foutopsporing. Deze toegang is beperkt tot wat noodzakelijk is voor de taakuitvoering en vindt plaats
                onder interne autorisaties en toegangscontroles.
            </p>

            <h3 class="font-heading text-gray-900 mt-4 mb-2 text-lg sm:text-xl">6.2 Dienstverleners (verwerkers)</h3>
            <p class="mb-3">
                Wij kunnen gebruik maken van dienstverleners (bijv. hostingproviders) voor technische infrastructuur. Indien deze partijen persoonsgegevens verwerken
                in opdracht van TU/e, worden passende afspraken gemaakt conform TU/e-beleid (zoals een verwerkersovereenkomst) en passende beveiligingsmaatregelen.
            </p>

            <p>
                Wij verkopen, verhuren of commercialiseren geen persoonsgegevens.
            </p>
        </section>

        <section class="bg-gray-50 rounded-lg p-5 sm:p-6 lg:p-8">
            <h2 class="text-xl sm:text-2xl font-heading text-gray-900 mb-3 sm:mb-4">7. Bewaartermijnen</h2>
            <p class="mb-3">
                Authenticatie-attributen die via SURFconext worden verwerkt, worden alleen gebruikt tijdens het inloggen en de actieve sessie en worden niet
                duurzaam opgeslagen in een gebruikersdatabase of profiel.
            </p>
            <p class="mb-3">
                Technische logbestanden (zoals webserver- en foutlogs) kunnen tijdelijk worden bewaard voor beveiliging en foutopsporing. Deze logs worden beperkt
                bewaard en periodiek verwijderd conform de geldende TU/e-beleidstermijnen en operationele noodzaak.
            </p>
            <p>
                Omdat het Portaal geen gebruikersprofielen bijhoudt, zijn er in de praktijk doorgaans geen duurzaam opgeslagen persoonsgegevens om op verzoek te verwijderen
                uit het Portaal.
            </p>
        </section>

        <section class="bg-gray-50 rounded-lg p-5 sm:p-6 lg:p-8">
            <h2 class="text-xl sm:text-2xl font-heading text-gray-900 mb-3 sm:mb-4">8. Uw rechten</h2>
            <p class="mb-3">
                Onder de Algemene Verordening Gegevensbescherming (AVG) heeft u, afhankelijk van de omstandigheden, onder meer de volgende rechten:
            </p>
            <ul class="list-disc list-inside mb-3 space-y-2 ml-4">
                <li>Recht op inzage</li>
                <li>Recht op rectificatie</li>
                <li>Recht op gegevenswissing</li>
                <li>Recht op beperking van de verwerking</li>
                <li>Recht van bezwaar</li>
                <li>Recht op overdraagbaarheid van gegevens (waar van toepassing)</li>
                <li>Recht op informatie over geautomatiseerde besluitvorming en profilering (voor zover van toepassing)</li>
            </ul>
            <p class="mb-3">
                Omdat het Portaal geen gebruikersprofielen aanmaakt en geen persoonsgegevens duurzaam opslaat, zullen sommige rechten in de praktijk beperkt toepasbaar
                zijn binnen het Portaal zelf. Indien uw vraag betrekking heeft op authenticatiegegevens bij uw thuisinstelling of SURFconext, kan het nodig zijn om uw verzoek
                (deels) via die partijen te laten verlopen.
            </p>
            <p class="mb-3">
                Voor vragen of het uitoefenen van uw privacyrechten kunt u contact opnemen met: <strong>privacy@tue.nl</strong>.
            </p>
            <p>
                U heeft het recht om een klacht in te dienen bij de Functionaris Gegevensbescherming via: <strong>dataprotectionofficer@tue.nl</strong>,
                en bij de Autoriteit Persoonsgegevens als u van mening bent dat uw gegevensbeschermingsrechten zijn geschonden.
            </p>
        </section>

        <section class="bg-gray-50 rounded-lg p-5 sm:p-6 lg:p-8">
            <h2 class="text-xl sm:text-2xl font-heading text-gray-900 mb-3 sm:mb-4">9. Gegevensbeveiliging</h2>
            <p class="mb-3">
                Wij nemen passende technische en organisatorische maatregelen om het Portaal veilig te laten functioneren. Deze maatregelen omvatten onder meer:
            </p>
            <ul class="list-disc list-inside mb-3 space-y-2 ml-4">
                <li>Versleuteling van gegevens tijdens verzending (HTTPS/TLS)</li>
                <li>Veilige authenticatie via SURFconext</li>
                <li>Toegangscontroles en authenticatievereisten voor Portaaltoegang</li>
                <li>Regelmatige beveiligingsbeoordelingen en updates</li>
                <li>Veilige hostinginfrastructuur en hardening waar passend</li>
            </ul>
            <p>
                Geen enkele methode van verzending of opslag is 100% veilig. Wij kunnen absolute veiligheid niet garanderen, maar treffen passende maatregelen
                conform de stand van de techniek en het risico.
            </p>
        </section>

        <section class="bg-gray-50 rounded-lg p-5 sm:p-6 lg:p-8">
            <h2 class="text-xl sm:text-2xl font-heading text-gray-900 mb-3 sm:mb-4">10. Cookies en vergelijkbare technieken</h2>
            <p class="mb-3">
                Het Portaal gebruikt uitsluitend functionele cookies en vergelijkbare technieken die noodzakelijk zijn voor de werking van de website, waaronder:
            </p>
            <ul class="list-disc list-inside mb-3 space-y-2 ml-4">
                <li>Het behouden van uw sessie en authenticatiestatus tijdens uw bezoek</li>
                <li>Het waarborgen van de correcte en veilige werking van het Portaal</li>
            </ul>
            <p class="mb-3">
                Wij gebruiken geen cookies voor tracking, marketing of profilering. De sessiecookies zijn bedoeld voor de actieve sessie en niet om bezoekers
                over websites heen te volgen.
            </p>
            <p>
                U kunt cookies beheren via uw browserinstellingen. Het uitschakelen van bepaalde functionele cookies kan de werking van het Portaal beïnvloeden.
            </p>
        </section>

        <section class="bg-gray-50 rounded-lg p-5 sm:p-6 lg:p-8">
            <h2 class="text-xl sm:text-2xl font-heading text-gray-900 mb-3 sm:mb-4">11. Internationale doorgifte</h2>
            <p>
                Het Portaal is bedoeld voor gebruik binnen de Europese Economische Ruimte (EER). De authenticatie via SURFconext vindt plaats binnen de EER.
                Wij beogen geen doorgifte van persoonsgegevens buiten de EER als onderdeel van de standaardfunctionaliteit van het Portaal.
            </p>
        </section>

        <section class="bg-gray-50 rounded-lg p-5 sm:p-6 lg:p-8">
            <h2 class="text-xl sm:text-2xl font-heading text-gray-900 mb-3 sm:mb-4">12. Wijzigingen in deze verklaring</h2>
            <p>
                Wij kunnen deze privacyverklaring van tijd tot tijd bijwerken. Wij zullen belangrijke wijzigingen publiceren op deze pagina en de datum
                &quot;Laatst bijgewerkt&quot; actualiseren. Wij raden u aan deze verklaring periodiek te raadplegen.
            </p>
        </section>

        <section class="bg-gray-50 rounded-lg p-5 sm:p-6 lg:p-8">
            <h2 class="text-xl sm:text-2xl font-heading text-gray-900 mb-3 sm:mb-4">13. Contactgegevens</h2>
            <p class="mb-2">
                Als u vragen of zorgen heeft over deze privacyverklaring, of als u uw rechten wilt uitoefenen, kunt u contact opnemen met:
            </p>
            <p class="mb-2">
                <strong>J. (Joris) Remmers</strong><br>
                Computational and Experimental Mechanics (CEM) Divisie<br>
                Technische Universiteit Eindhoven
            </p>
            <p class="mb-2">
                Voor vragen of het uitoefenen van uw privacyrechten: <strong>privacy@tue.nl</strong>.<br>
                Voor klachten: <strong>dataprotectionofficer@tue.nl</strong>.
            </p>
            <p>
                Voor technische ondersteuning kunt u contact opnemen met: <strong>B. (Bart) Verhaegh</strong>
            </p>
        </section>
    </div>
</div>
@endsection
