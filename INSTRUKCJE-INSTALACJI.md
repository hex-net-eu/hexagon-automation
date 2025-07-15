# 🚀 Hexagon Automation v3.0.9 - INSTRUKCJE INSTALACJI

## 📋 Wymagania Systemowe

### WordPress
- WordPress 5.0 lub nowszy
- PHP 7.4 lub nowszy  
- MySQL 5.6 lub nowszy
- Limit pamięci: minimum 256MB
- Włączone rozszerzenia PHP: curl, json, mbstring, openssl, zip, gd

### Konta API (Opcjonalne)
- **OpenAI API** - dla ChatGPT integration
- **Anthropic API** - dla Claude integration  
- **Perplexity API** - dla Perplexity integration
- **Facebook Developer Account** - dla Facebook/Instagram posting
- **Twitter Developer Account** - dla Twitter posting
- **LinkedIn Developer Account** - dla LinkedIn posting

## 🔧 Instalacja Pluginu

### Krok 1: Upload Plików
1. Rozpakuj plik `hexagon-automation-v3.0.9-WORDPRESS-FINAL.zip`
2. Upload folderu `hexagon-automation` do `/wp-content/plugins/`
3. Lub zainstaluj bezpośrednio przez panel WordPress (Wtyczki → Dodaj nową → Wyślij wtyczkę)

### Krok 2: Aktywacja
1. Przejdź do **Wtyczki** w panelu WordPress
2. Znajdź **Hexagon Automation v3.0.9**
3. Kliknij **Aktywuj**

### Krok 3: Konfiguracja Podstawowa
1. Po aktywacji przejdź do **Hexagon Automation** w menu bocznym
2. Kliknij **⚙️ Settings** 
3. Wpisz klucze API dla wybranych dostawców AI:
   - **OpenAI API Key** - z https://platform.openai.com/api-keys
   - **Claude API Key** - z https://console.anthropic.com/
   - **Perplexity API Key** - z https://www.perplexity.ai/settings/api
4. Kliknij **Zapisz zmiany**

## 🎯 Konfiguracja Funkcji

### RSS Feed Integration
1. Przejdź do React Dashboard (automatycznie otwiera się po aktywacji)
2. W sekcji **Recent RSS Sources** kliknij **Add New Source**
3. Wprowadź URL RSS feed
4. Wybierz czy ma automatycznie tworzyć posty
5. Kliknij **Dodaj Feed**

### Social Media Connections

#### Facebook
1. Przejdź do https://developers.facebook.com/
2. Utwórz aplikację i otrzymaj **Access Token**
3. W dashboardzie wybierz **Social Media** → **Connect Facebook**
4. Wklej **Access Token** i opcjonalnie **Page ID**

#### Instagram Business
1. Połącz konto Instagram z Facebookiem (wymagane)
2. Użyj tego samego **Access Token** co dla Facebooka
3. W dashboardzie **Connect Instagram**

#### Twitter
1. Przejdź do https://developer.twitter.com/
2. Utwórz aplikację i otrzymaj:
   - **Bearer Token**
   - **API Key** i **API Secret**
   - **Access Token** i **Access Token Secret**
3. W dashboardzie **Connect Twitter** i wprowadź wszystkie tokeny

#### LinkedIn
1. Przejdź do https://www.linkedin.com/developers/
2. Utwórz aplikację i otrzymaj **Access Token**
3. W dashboardzie **Connect LinkedIn**

## 📊 Korzystanie z Dashboardu

### Główny Dashboard
- **Statystyki w czasie rzeczywistym** - posty, obrazy, emaile
- **Status AI Providers** - połączenia z ChatGPT, Claude, Perplexity
- **Recent Activity** - ostatnie automatyzacje
- **RSS Sources** - aktywne źródła treści

### AI Settings
- Konfiguracja dostawców AI
- Testowanie połączeń
- Zarządzanie kluczami API

### Social Media
- Podgląd połączonych platform
- Testowanie połączeń
- Publikowanie na wielu platformach jednocześnie

### Image Generator
- Generowanie obrazów AI
- Automatyczne dodawanie do biblioteki mediów

### Debug Logger
- Monitorowanie błędów
- Logi systemowe
- Diagnostyka połączeń

## 🔧 Rozwiązywanie Problemów

### Plugin się nie aktywuje
- Sprawdź wersję PHP (minimum 7.4)
- Zwiększ limit pamięci do 256MB w `wp-config.php`:
```php
ini_set('memory_limit', '256M');
```

### Dashboard nie ładuje się
- Sprawdź czy wszystkie pliki zostały przesłane
- Wyczyść cache przeglądarki
- Sprawdź konsolę developerską w przeglądarce

### AI nie działa
- Sprawdź poprawność kluczy API
- Użyj **System Tests** do diagnostyki połączeń
- Sprawdź logi błędów w **Debug Logger**

### Social Media nie publikuje
- Sprawdź uprawnienia aplikacji w panelach developerskich
- Upewnij się że tokeny są aktualne
- Sprawdź limity API poszczególnych platform

## ⚡ Funkcje Enterprise v3.0.9

- ✅ **RSS Feed Integration** z auto-posting
- ✅ **Real Social Media APIs** (Facebook, Instagram, Twitter, LinkedIn)  
- ✅ **AI Content Generation** (ChatGPT, Claude, Perplexity)
- ✅ **React Dashboard** z real-time data
- ✅ **Advanced Error Handling** i logging
- ✅ **Module Management System**
- ✅ **System Diagnostics** i health monitoring
- ✅ **Settings Import/Export**
- ✅ **Multi-platform Social Posting**
- ✅ **WordPress REST API Integration**

---

**🚀 Hexagon Automation v3.0.9 - ENTERPRISE EDITION**  
*AI-powered content & social media automation for WordPress*