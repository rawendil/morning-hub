<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Polityka Prywatności – {{ config('app.name') }}</title>
  <style>
    body { font-family: Arial, sans-serif; font-size: 14px; color: #595959; max-width: 860px; margin: 0 auto; padding: 2rem; }
    h1 { font-size: 26px; color: #000; }
    h2 { font-size: 19px; color: #000; margin-top: 2rem; }
    h3 { font-size: 17px; color: #000; }
    a { color: #3030F1; word-break: break-word; }
    ul { padding-left: 1.5rem; }
    li { margin-bottom: 0.3rem; }
  </style>
</head>
<body>

<h1>POLITYKA PRYWATNOŚCI</h1>
<p><strong>Ostatnia aktualizacja: 17 maja 2026</strong></p>

<p>Niniejsza Polityka Prywatności dotyczy {{ config('app.company_name') }} (<strong>„my"</strong>, <strong>„nas"</strong> lub <strong>„nasz"</strong>) i opisuje sposób, w jaki możemy uzyskiwać dostęp do Twoich danych osobowych, zbierać je, przechowywać, wykorzystywać i/lub udostępniać (<strong>„przetwarzać"</strong>), gdy korzystasz z naszych usług (<strong>„Usługi"</strong>), w tym gdy:</p>
<ul>
  <li>Odwiedzasz naszą stronę internetową pod adresem <a href="{{ config('app.url') }}">{{ config('app.url') }}</a> lub inną stronę prowadzącą do niniejszej Polityki Prywatności.</li>
  <li>Kontaktujesz się z nami w innych powiązanych sprawach.</li>
</ul>

<p><strong>Pytania lub wątpliwości?</strong> Zapoznanie się z niniejszą Polityką Prywatności pomoże Ci zrozumieć swoje prawa i możliwości w zakresie prywatności. Jeśli nie zgadzasz się z naszymi zasadami i praktykami, prosimy o niekorzystanie z Usług. Jeśli masz pytania lub wątpliwości, skontaktuj się z nami pod adresem <a href="mailto:{{ config('app.contact_email') }}">{{ config('app.contact_email') }}</a>.</p>

<h2>PODSUMOWANIE KLUCZOWYCH PUNKTÓW</h2>

<p><em>Niniejsze podsumowanie przedstawia kluczowe punkty Polityki Prywatności. Więcej szczegółów znajdziesz w poszczególnych sekcjach poniżej.</em></p>

<p><strong>Jakie dane osobowe przetwarzamy?</strong> Gdy odwiedzasz, używasz lub przeglądasz nasze Usługi, możemy przetwarzać dane osobowe w zależności od sposobu interakcji z nami oraz dokonywanych wyborów. Dowiedz się więcej w sekcji <a href="#infocollect">1</a>.</p>

<p><strong>Czy przetwarzamy wrażliwe dane osobowe?</strong> Nie przetwarzamy wrażliwych danych osobowych.</p>

<p><strong>Czy zbieramy dane od podmiotów trzecich?</strong> Możemy zbierać informacje od Google przy logowaniu OAuth. Dowiedz się więcej w sekcji <a href="#othersources">Informacje z innych źródeł</a>.</p>

<p><strong>Jak przetwarzamy Twoje informacje?</strong> Przetwarzamy Twoje dane w celu świadczenia Usług, poprawy ich jakości, komunikacji z Tobą, zapewnienia bezpieczeństwa i zapobiegania nadużyciom oraz przestrzegania przepisów prawa. Dowiedz się więcej w sekcji <a href="#infouse">2</a>.</p>

<p><strong>Z kim dzielimy się danymi?</strong> Możemy udostępniać informacje określonym podmiotom trzecim. Dowiedz się więcej w sekcji <a href="#whoshare">4</a>.</p>

<p><strong>Jak dbamy o bezpieczeństwo danych?</strong> Stosujemy odpowiednie środki organizacyjne i techniczne. Dowiedz się więcej w sekcji <a href="#infosafe">9</a>.</p>

<p><strong>Jakie przysługują Ci prawa?</strong> W zależności od miejsca zamieszkania możesz mieć określone prawa dotyczące swoich danych osobowych. Dowiedz się więcej w sekcji <a href="#privacyrights">10</a>.</p>

<p><strong>Jak skorzystać ze swoich praw?</strong> Skontaktuj się z nami pod adresem <a href="mailto:{{ config('app.contact_email') }}">{{ config('app.contact_email') }}</a>. Rozpatrzymy każde żądanie zgodnie z obowiązującymi przepisami o ochronie danych.</p>

<h2>SPIS TREŚCI</h2>
<ol>
  <li><a href="#infocollect">JAKIE INFORMACJE ZBIERAMY?</a></li>
  <li><a href="#infouse">JAK PRZETWARZAMY TWOJE INFORMACJE?</a></li>
  <li><a href="#legalbases">NA JAKICH PODSTAWACH PRAWNYCH OPIERAMY SIĘ PRZY PRZETWARZANIU DANYCH?</a></li>
  <li><a href="#whoshare">KIEDY I KOMU UDOSTĘPNIAMY TWOJE DANE OSOBOWE?</a></li>
  <li><a href="#cookies">CZY UŻYWAMY PLIKÓW COOKIE I INNYCH TECHNOLOGII ŚLEDZENIA?</a></li>
  <li><a href="#sociallogins">JAK OBSŁUGUJEMY TWOJE LOGOWANIE SPOŁECZNOŚCIOWE?</a></li>
  <li><a href="#intltransfers">CZY TWOJE DANE SĄ PRZEKAZYWANE MIĘDZYNARODOWO?</a></li>
  <li><a href="#inforetain">JAK DŁUGO PRZECHOWUJEMY TWOJE DANE?</a></li>
  <li><a href="#infosafe">JAK CHRONIMY TWOJE DANE?</a></li>
  <li><a href="#privacyrights">JAKIE PRZYSŁUGUJĄ CI PRAWA W ZAKRESIE PRYWATNOŚCI?</a></li>
  <li><a href="#DNT">USTAWIENIA „DO NOT TRACK"</a></li>
  <li><a href="#policyupdates">CZY AKTUALIZUJEMY NINIEJSZĄ POLITYKĘ?</a></li>
  <li><a href="#contact">JAK MOŻESZ SIĘ Z NAMI SKONTAKTOWAĆ?</a></li>
  <li><a href="#request">JAK MOŻESZ PRZEGLĄDAĆ, AKTUALIZOWAĆ LUB USUWAĆ SWOJE DANE?</a></li>
</ol>

<h2 id="infocollect">1. JAKIE INFORMACJE ZBIERAMY?</h2>

<h3 id="personalinfo">Dane osobowe podawane bezpośrednio przez Ciebie</h3>
<p><em>Krótko: Zbieramy dane osobowe, które nam podajesz.</em></p>
<p>Zbieramy dane osobowe, które dobrowolnie nam podajesz podczas rejestracji w Usługach, wyrażania zainteresowania uzyskaniem informacji o nas lub naszych produktach, uczestnictwa w aktywnościach w ramach Usług lub kontaktu z nami.</p>
<p><strong>Dane osobowe podawane przez Ciebie</strong> mogą obejmować:</p>
<ul>
  <li>imiona i nazwiska</li>
  <li>adresy e-mail</li>
  <li>hasła</li>
  <li>dane kontaktowe lub uwierzytelniające</li>
</ul>
<p><strong>Dane wrażliwe.</strong> Nie przetwarzamy wrażliwych danych osobowych.</p>
<p><strong>Dane z logowania społecznościowego.</strong> Możemy umożliwić Ci rejestrację przy użyciu istniejącego konta Google. W takim przypadku zbieramy określone informacje profilowe od dostawcy – opisane szczegółowo w sekcji <a href="#sociallogins">6</a>.</p>
<p>Wszystkie dane osobowe, które nam podajesz, muszą być prawdziwe, kompletne i dokładne. W przypadku zmiany danych należy nas o tym poinformować.</p>

<h3>Informacje zbierane automatycznie</h3>
<p><em>Krótko: Niektóre informacje – takie jak adres IP oraz charakterystyka przeglądarki i urządzenia – są zbierane automatycznie podczas korzystania z Usług.</em></p>
<p>Automatycznie zbieramy pewne informacje podczas odwiedzania, używania lub przeglądania Usług. Informacje te nie ujawniają Twojej tożsamości, ale mogą obejmować dane o urządzeniu i sposobie korzystania z Usług, takie jak adres IP, charakterystyka przeglądarki i urządzenia, system operacyjny, preferencje językowe, adresy URL odsyłające, nazwy urządzeń, kraj, lokalizacja oraz inne informacje techniczne. Informacje te są potrzebne głównie do zapewnienia bezpieczeństwa i działania Usług oraz do naszych wewnętrznych celów analitycznych.</p>
<p>Zbieramy informacje również za pośrednictwem plików cookie i podobnych technologii. Zbierane informacje obejmują:</p>
<ul>
  <li><em>Dane dziennika i użytkowania.</em> Informacje diagnostyczne i dotyczące wydajności, które nasze serwery automatycznie zbierają podczas dostępu do Usług: adres IP, informacje o urządzeniu, typ przeglądarki, ustawienia, daty i godziny użytkowania, przeglądane strony, wyszukiwania oraz raporty błędów.</li>
  <li><em>Dane urządzenia.</em> Dane o urządzeniu, którego używasz: adres IP, numery identyfikacyjne urządzenia i aplikacji, lokalizacja, typ przeglądarki, model sprzętu, dostawca usług internetowych, system operacyjny i informacje o konfiguracji.</li>
  <li><em>Dane lokalizacji.</em> Dane o lokalizacji Twojego urządzenia (precyzyjne lub przybliżone, na podstawie adresu IP). Możesz zrezygnować z udostępniania tych informacji wyłączając ustawienie lokalizacji na urządzeniu.</li>
</ul>

<h3>Google API</h3>
<p>Korzystanie z informacji otrzymanych z interfejsów API Google odbywa się zgodnie z <a href="https://developers.google.com/terms/api-services-user-data-policy" target="_blank" rel="noopener">Polityką danych użytkownika usług API Google</a>, w tym z wymaganiami dotyczącymi <a href="https://developers.google.com/terms/api-services-user-data-policy#limited-use" target="_blank" rel="noopener">ograniczonego użycia</a>.</p>

<h3 id="othersources">Informacje zbierane z innych źródeł</h3>
<p><em>Krótko: Możemy zbierać ograniczone dane z platform mediów społecznościowych i innych zewnętrznych źródeł.</em></p>
<p>Jeśli logujesz się za pośrednictwem Google (OAuth), otrzymujemy od Google informacje profilowe, takie jak imię i nazwisko, adres e-mail oraz zdjęcie profilowe. Zakres tych danych zależy od ustawień prywatności Twojego konta Google. Korzystanie z Twoich danych przez Google podlega ich własnej polityce prywatności.</p>

<h2 id="infouse">2. JAK PRZETWARZAMY TWOJE INFORMACJE?</h2>
<p><em>Krótko: Przetwarzamy Twoje dane w celu świadczenia, ulepszania i administrowania Usługami, komunikacji z Tobą, zapewnienia bezpieczeństwa oraz przestrzegania prawa.</em></p>
<p>Przetwarzamy Twoje dane osobowe w następujących celach:</p>
<ul>
  <li><strong>Tworzenie konta i uwierzytelnianie.</strong> Przetwarzamy Twoje dane, abyś mógł się zarejestrować, zalogować i korzystać ze swojego konta.</li>
  <li><strong>Świadczenie Usług.</strong> Przetwarzamy Twoje dane w celu dostarczenia Ci żądanych Usług.</li>
  <li><strong>Odpowiadanie na zapytania.</strong> Przetwarzamy Twoje dane w celu odpowiadania na Twoje pytania i rozwiązywania ewentualnych problemów.</li>
  <li><strong>Wysyłanie informacji administracyjnych.</strong> Możemy przetwarzać Twoje dane w celu wysyłania informacji o naszych produktach, usługach, zmianach warunków i polityk.</li>
  <li><strong>Ochrona Usług.</strong> Przetwarzamy Twoje dane w ramach działań na rzecz bezpieczeństwa Usług i zapobiegania nadużyciom.</li>
  <li><strong>Analiza trendów użytkowania.</strong> Przetwarzamy informacje o sposobie korzystania z Usług w celu ich doskonalenia.</li>
  <li><strong>Ochrona żywotnych interesów.</strong> Możemy przetwarzać Twoje dane, gdy jest to niezbędne do ochrony żywotnych interesów użytkownika lub osób trzecich.</li>
</ul>

<h2 id="legalbases">3. NA JAKICH PODSTAWACH PRAWNYCH OPIERAMY SIĘ PRZY PRZETWARZANIU DANYCH?</h2>
<p><em>Krótko: Przetwarzamy Twoje dane osobowe wyłącznie wtedy, gdy jest to konieczne i gdy mamy ku temu ważną podstawę prawną zgodną z obowiązującym prawem.</em></p>
<p>Ogólne Rozporządzenie o Ochronie Danych (RODO) oraz UK GDPR wymagają wyjaśnienia podstaw prawnych przetwarzania danych. Opieramy się na następujących podstawach:</p>
<ul>
  <li><strong>Zgoda.</strong> Możemy przetwarzać Twoje dane, jeśli wyraziłeś na to zgodę w określonym celu. Możesz wycofać zgodę w dowolnym momencie.</li>
  <li><strong>Wykonanie umowy.</strong> Możemy przetwarzać Twoje dane, gdy jest to niezbędne do wykonania naszych zobowiązań wobec Ciebie, w tym świadczenia Usług.</li>
  <li><strong>Uzasadniony interes.</strong> Możemy przetwarzać Twoje dane, gdy jest to racjonalnie konieczne do realizacji naszych uzasadnionych interesów, np. analizowania sposobu korzystania z Usług w celu ich ulepszania lub diagnozowania problemów i zapobiegania nieuczciwym działaniom.</li>
  <li><strong>Obowiązki prawne.</strong> Możemy przetwarzać Twoje dane, gdy jest to konieczne do przestrzegania naszych obowiązków prawnych.</li>
  <li><strong>Żywotne interesy.</strong> Możemy przetwarzać Twoje dane, gdy jest to konieczne do ochrony żywotnych interesów Ciebie lub osób trzecich.</li>
</ul>

<h2 id="whoshare">4. KIEDY I KOMU UDOSTĘPNIAMY TWOJE DANE OSOBOWE?</h2>
<p><em>Krótko: Możemy udostępniać informacje w określonych sytuacjach i następującym podmiotom trzecim.</em></p>
<p>Możemy udostępniać Twoje dane dostawcom usług zewnętrznych, którzy wykonują usługi na naszą rzecz i wymagają dostępu do tych danych. Mamy zawarte z nimi umowy zaprojektowane w celu ochrony Twoich danych osobowych.</p>
<p>Podmioty trzecie, którym możemy udostępniać dane osobowe:</p>
<ul>
  <li><strong>Optymalizacja funkcjonalności:</strong> ClickUp</li>
  <li><strong>Rejestracja konta i uwierzytelnianie:</strong> Google OAuth 2.0</li>
  <li><strong>Analityka webowa i mobilna:</strong> Google Analytics, Google Tag Manager</li>
  <li><strong>Monitoring wydajności serwisu:</strong> Sentry</li>
</ul>
<p>Możemy również udostępniać Twoje dane w następujących sytuacjach:</p>
<ul>
  <li><strong>Przeniesienie działalności.</strong> Możemy udostępniać lub przekazywać informacje w związku z fuzją, sprzedażą aktywów, finansowaniem lub przejęciem całości lub części naszej działalności przez inną firmę.</li>
</ul>

<h2 id="cookies">5. CZY UŻYWAMY PLIKÓW COOKIE I INNYCH TECHNOLOGII ŚLEDZENIA?</h2>
<p><em>Krótko: Możemy używać plików cookie i innych technologii śledzenia do zbierania i przechowywania informacji.</em></p>
<p>Używamy plików cookie i podobnych technologii śledzenia do zbierania informacji podczas interakcji z Usługami. Pomagają one utrzymać bezpieczeństwo Usług i Twojego konta, zapobiegać awariom, naprawiać błędy, zapisywać preferencje i wspomagać podstawowe funkcje strony.</p>
<p>Zezwalamy również podmiotom trzecim na używanie technologii śledzenia online w naszych Usługach do celów analitycznych.</p>
<p><strong>Google Analytics.</strong> Możemy udostępniać Twoje informacje Google Analytics w celu śledzenia i analizowania korzystania z Usług. Aby zrezygnować ze śledzenia przez Google Analytics, odwiedź <a href="https://tools.google.com/dlpage/gaoptout" target="_blank" rel="noopener">https://tools.google.com/dlpage/gaoptout</a>. Więcej informacji o praktykach prywatności Google znajdziesz na stronie <a href="https://policies.google.com/privacy" target="_blank" rel="noopener">Google Privacy &amp; Terms</a>.</p>

<h2 id="sociallogins">6. JAK OBSŁUGUJEMY TWOJE LOGOWANIE SPOŁECZNOŚCIOWE?</h2>
<p><em>Krótko: Jeśli zdecydujesz się zalogować za pośrednictwem konta Google, możemy mieć dostęp do określonych informacji o Tobie.</em></p>
<p>Nasze Usługi umożliwiają rejestrację i logowanie przy użyciu konta Google. W takim przypadku otrzymamy od Google określone informacje profilowe, które mogą obejmować imię i nazwisko, adres e-mail oraz zdjęcie profilowe.</p>
<p>Będziemy wykorzystywać otrzymane informacje wyłącznie w celach opisanych w niniejszej Polityce Prywatności. Nie kontrolujemy i nie ponosimy odpowiedzialności za inne sposoby wykorzystania Twoich danych przez Google. Zalecamy zapoznanie się z polityką prywatności Google.</p>

<h2 id="intltransfers">7. CZY TWOJE DANE SĄ PRZEKAZYWANE MIĘDZYNARODOWO?</h2>
<p><em>Krótko: Możemy przekazywać, przechowywać i przetwarzać Twoje informacje w krajach innych niż Twój własny.</em></p>
<p>Nasze serwery znajdują się w Polsce. Niezależnie od Twojej lokalizacji, Twoje dane mogą być przekazywane, przechowywane i przetwarzane przez nas lub podmioty trzecie, z którymi współpracujemy (Google, ClickUp, Sentry), w tym w Stanach Zjednoczonych.</p>
<p>Jeśli jesteś rezydentem Europejskiego Obszaru Gospodarczego (EOG), Zjednoczonego Królestwa lub Szwajcarii, kraje te mogą nie posiadać przepisów o ochronie danych równie kompleksowych jak w Twoim kraju. Podejmiemy jednak wszelkie niezbędne środki w celu ochrony Twoich danych zgodnie z niniejszą Polityką i obowiązującym prawem.</p>
<p><strong>Standardowe klauzule umowne Komisji Europejskiej (SCC):</strong> Wdrożyliśmy środki ochrony danych, w tym klauzule SCC dla przekazywania danych do podmiotów trzecich. Nasze klauzule SCC mogą zostać udostępnione na żądanie.</p>

<h2 id="inforetain">8. JAK DŁUGO PRZECHOWUJEMY TWOJE DANE?</h2>
<p><em>Krótko: Przechowujemy Twoje informacje tak długo, jak jest to konieczne do celów opisanych w niniejszej Polityce, chyba że prawo wymaga inaczej.</em></p>
<p>Będziemy przechowywać Twoje dane osobowe tylko tak długo, jak jest to konieczne. Co do zasady, przechowujemy dane przez okres posiadania przez Ciebie konta u nas.</p>
<p>Gdy nie będziemy mieć uzasadnionej potrzeby dalszego przetwarzania Twoich danych, usuniemy je lub zanonimizujemy, a jeśli nie będzie to możliwe (np. dane przechowywane w archiwach kopii zapasowych), bezpiecznie je przechowamy i odizolujemy od dalszego przetwarzania do czasu, gdy usunięcie będzie możliwe.</p>

<h2 id="infosafe">9. JAK CHRONIMY TWOJE DANE?</h2>
<p><em>Krótko: Staramy się chronić Twoje dane osobowe poprzez system środków bezpieczeństwa organizacyjnego i technicznego.</em></p>
<p>Wdrożyliśmy odpowiednie środki bezpieczeństwa technicznego i organizacyjnego, w tym szyfrowanie haseł, szyfrowanie tokenów API i OAuth, uwierzytelnianie dwuskładnikowe (2FA), ochronę CSRF oraz ciasteczka HTTP-only.</p>
<p>Mimo naszych starań, żaden elektroniczny przekaz przez Internet ani technologia przechowywania danych nie może być zagwarantowana jako w 100% bezpieczna. Zalecamy korzystanie z Usług wyłącznie w bezpiecznym środowisku.</p>

<h2 id="privacyrights">10. JAKIE PRZYSŁUGUJĄ CI PRAWA W ZAKRESIE PRYWATNOŚCI?</h2>
<p><em>Krótko: W EOG, Zjednoczonym Królestwie i Szwajcarii przysługują Ci prawa zapewniające szerszy dostęp do Twoich danych osobowych i kontrolę nad nimi.</em></p>
<p>Na mocy obowiązujących przepisów o ochronie danych możesz mieć następujące prawa:</p>
<ul>
  <li>prawo dostępu do swoich danych osobowych i uzyskania ich kopii</li>
  <li>prawo do sprostowania lub usunięcia danych</li>
  <li>prawo do ograniczenia przetwarzania danych</li>
  <li>prawo do przenoszenia danych</li>
  <li>prawo do niepodlegania zautomatyzowanemu podejmowaniu decyzji</li>
</ul>
<p>Aby skorzystać z tych praw, skontaktuj się z nami korzystając z danych kontaktowych podanych w sekcji <a href="#contact">13</a>.</p>
<p>Jeśli jesteś w EOG lub Zjednoczonym Królestwie i uważasz, że przetwarzamy Twoje dane niezgodnie z prawem, masz prawo złożyć skargę do organu nadzorczego. W Polsce jest nim <a href="https://uodo.gov.pl" target="_blank" rel="noopener">Urząd Ochrony Danych Osobowych (UODO)</a>.</p>
<p>Jeśli jesteś w Szwajcarii, możesz skontaktować się z <a href="https://www.edoeb.admin.ch" target="_blank" rel="noopener">Federalnym Komisarzem ds. Ochrony Danych i Informacji</a>.</p>

<h3>Wycofanie zgody</h3>
<p>Jeśli przetwarzamy Twoje dane na podstawie zgody, masz prawo ją wycofać w dowolnym momencie, kontaktując się z nami. Wycofanie zgody nie wpłynie na zgodność z prawem przetwarzania dokonanego przed jej wycofaniem.</p>

<h3>Informacje o koncie</h3>
<p>Jeśli chcesz przejrzeć lub zmienić informacje na swoim koncie albo je usunąć, możesz:</p>
<ul>
  <li>Zalogować się do ustawień konta i zaktualizować swoje dane.</li>
  <li>Zażądać usunięcia konta – usuniemy lub dezaktywujemy konto i informacje z naszych aktywnych baz danych. Możemy zachować pewne informacje w celu zapobiegania nadużyciom lub przestrzegania wymogów prawnych.</li>
</ul>
<p>W przypadku pytań dotyczących Twoich praw prywatności skontaktuj się z nami pod adresem <a href="mailto:{{ config('app.contact_email') }}">{{ config('app.contact_email') }}</a>.</p>

<h2 id="DNT">11. USTAWIENIA „DO NOT TRACK"</h2>
<p>Większość przeglądarek internetowych zawiera funkcję „Do-Not-Track" (DNT). Obecnie nie istnieje jednolity standard techniczny dla sygnałów DNT i na chwilę obecną nie reagujemy na te sygnały. Jeśli w przyszłości zostanie przyjęty standard, którego musimy przestrzegać, poinformujemy Cię o tym w zaktualizowanej wersji niniejszej Polityki Prywatności.</p>

<h2 id="policyupdates">12. CZY AKTUALIZUJEMY NINIEJSZĄ POLITYKĘ?</h2>
<p><em>Krótko: Tak, będziemy aktualizować niniejszą Politykę w miarę potrzeb, aby zachować zgodność z obowiązującymi przepisami.</em></p>
<p>Możemy okresowo aktualizować niniejszą Politykę Prywatności. Zaktualizowana wersja będzie oznaczona zaktualizowaną datą na górze dokumentu. W przypadku istotnych zmian możemy poinformować Cię przez zamieszczenie informacji o zmianach lub bezpośrednie wysłanie powiadomienia. Zachęcamy do regularnego przeglądania niniejszej Polityki.</p>

<h2 id="contact">13. JAK MOŻESZ SIĘ Z NAMI SKONTAKTOWAĆ?</h2>
<p>Jeśli masz pytania lub uwagi dotyczące niniejszej Polityki, możesz skontaktować się z nami:</p>
<address>
  <strong>{{ config('app.company_name') }}</strong><br>
  {{ config('app.company_address') }}<br>
  {{ config('app.company_city') }}, {{ config('app.company_region') }} {{ config('app.company_zip') }}<br>
  Polska<br>
  E-mail: <a href="mailto:{{ config('app.contact_email') }}">{{ config('app.contact_email') }}</a>
</address>

<h2 id="request">14. JAK MOŻESZ PRZEGLĄDAĆ, AKTUALIZOWAĆ LUB USUWAĆ SWOJE DANE?</h2>
<p>Na podstawie obowiązujących przepisów możesz mieć prawo do żądania dostępu do swoich danych osobowych, sprostowania nieprawidłowości lub usunięcia danych. Możesz również mieć prawo do wycofania zgody na przetwarzanie danych. Prawa te mogą być w pewnych okolicznościach ograniczone przez obowiązujące przepisy.</p>
<p>Aby złożyć wniosek o przegląd, aktualizację lub usunięcie swoich danych osobowych, skontaktuj się z nami pod adresem: <a href="mailto:{{ config('app.contact_email') }}">{{ config('app.contact_email') }}</a>.</p>

</body>
</html>
