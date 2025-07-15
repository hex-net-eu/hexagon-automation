# 🚨 Hexagon Automation - Testing Instructions

## Problem z Głównym Pluginem

Wystąpił błąd krytyczny podczas aktywacji głównego plugina `hexagon-automation.php`. 

## 🛠️ Rozwiązanie - Safe Mode Testing

Utworzyłem wersję testową: **`hexagon-automation-safe.php`**

### Kroki Testowania:

#### 1. Deaktywuj Główny Plugin
```bash
# W WordPress Admin
1. Idź do Plugins
2. Deaktywuj "Hexagon Automation" (jeśli aktywny)
3. Usuń plugin (tymczasowo)
```

#### 2. Testuj Safe Mode
```bash
# Aktywuj wersję testową
1. Aktywuj plugin "Hexagon Automation (Safe Mode)"
2. Plugin powinien aktywować się bez błędów
3. Sprawdź czy pojawił się komunikat o sukcesie
```

#### 3. Weryfikuj Podstawowe Funkcje
```bash
# W WordPress Admin
1. Idź do "Hexagon Auto" w menu
2. Sprawdź informacje systemowe
3. Kliknij "Test Log Entry"
4. Sprawdź czy logi się zapisują
```

## 🔍 Diagnostyka Problemów

### Sprawdź Logi PHP
```bash
# Logi błędów WordPress/PHP
wp-content/debug.log
/var/log/apache2/error.log
/var/log/nginx/error.log
```

### Typowe Problemy:

#### 1. Błąd MySQL/Database
```
Error: Table doesn't exist
```
**Rozwiązanie:** Safe mode automatycznie tworzy tabele

#### 2. Błąd PHP Memory
```
Fatal error: Allowed memory size exhausted
```
**Rozwiązanie:** Zwiększ memory limit w wp-config.php:
```php
ini_set('memory_limit', '256M');
```

#### 3. Błąd Class Not Found
```
Fatal error: Class 'Hexagon_*' not found
```
**Rozwiązanie:** Problem z autoloading - safe mode ładuje tylko niezbędne klasy

#### 4. Błąd Permissions
```
Permission denied
```
**Rozwiązanie:** Sprawdź uprawnienia plików (644) i folderów (755)

## 🧪 Test Scenarios

### Test 1: Basic Activation
- [ ] Plugin aktywuje się bez błędów
- [ ] Tworzy tabelę `wp_hex_logs`
- [ ] Pokazuje menu "Hexagon Auto"
- [ ] Wyświetla stronę admin

### Test 2: Logging System
- [ ] Funkcja `hexagon_log()` działa
- [ ] Logi zapisują się do bazy
- [ ] AJAX test log działa
- [ ] Logi wyświetlają się w tabeli

### Test 3: Database
- [ ] Tabela ma właściwą strukturę
- [ ] Indeksy są utworzone
- [ ] Zapisy i odczyty działają

## 🔧 Naprawianie Głównego Plugina

Po potwierdzeniu, że safe mode działa:

### Problem 1: Loader Conflicts
```php
// Możliwy problem w class-hexagon-loader.php
// Ładuje wszystkie moduły na raz
```

### Problem 2: Missing Dependencies
```php
// Niektóre klasy mogą wymagać innych klas
// które nie są jeszcze załadowane
```

### Problem 3: Database Schema Mismatch
```php
// Stara struktura vs nowa struktura tabeli logs
```

## 📊 Safe Mode vs Full Plugin

| Funkcja | Safe Mode | Full Plugin |
|---------|-----------|-------------|
| Aktywacja | ✅ Działa | ❌ Błąd |
| Logging | ✅ Basic | ✅ Advanced |
| AI Integration | ❌ Brak | ✅ Full |
| Email System | ❌ Brak | ✅ Full |
| Social Media | ❌ Brak | ✅ Full |
| Auto-Repair | ❌ Brak | ✅ Full |
| REST API | ❌ Brak | ✅ Full |
| Dashboard | ❌ Brak | ✅ React |

## 🚀 Next Steps

1. **Test Safe Mode** - Potwierdź że podstawy działają
2. **Znajdź Root Cause** - Zidentyfikuj dokładny błąd
3. **Fix Incrementally** - Dodawaj moduły po kolei
4. **Test Each Module** - Sprawdzaj każdy osobno

## 📞 Debug Commands

### Check PHP Errors
```bash
tail -f /var/log/apache2/error.log
```

### Check WordPress Debug
```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Check Database
```sql
SHOW TABLES LIKE '%hex_logs%';
DESCRIBE wp_hex_logs;
SELECT * FROM wp_hex_logs ORDER BY created_at DESC LIMIT 5;
```

---

**🎯 Cel:** Najpierw potwierdzić, że safe mode działa, potem stopniowo naprawić główny plugin.