# 🔧 Hexagon Automation - Debug Export Guide dla bizrun.eu

## 📥 Jak Pobrać Pliki Diagnostyczne

### Metoda 1: Safe Mode (Zalecana)
1. **Aktywuj hexagon-automation-safe.php** w WordPressie
2. **Idź do** WordPress Admin → Hexagon Auto
3. **Znajdź sekcję** "🔧 Debug Export dla bizrun.eu" 
4. **Kliknij przyciski:**
   - 📄 **Pobierz Logi (JSON)** - zapisze `hexagon-logs-bizrun-YYYY-MM-DD-HH-mm-ss.json`
   - ⚙️ **Pobierz Info Systemowe (JSON)** - zapisze `hexagon-system-info-bizrun-YYYY-MM-DD-HH-mm-ss.json`

### Metoda 2: Pełny Plugin (Jeśli działa)
1. **W WordPress Admin** → Hexagon Automation
2. **Znajdź sekcję debug** na każdej stronie plugina
3. **Użyj linków pobierania** z automatycznymi nazwami plików

## 📋 Co Zawierają Pliki

### 📄 Plik Logów (`hexagon-logs-bizrun-*.json`)
```json
{
  "export_info": {
    "site": "bizrun.eu",
    "export_date": "2025-01-13 15:30:00",
    "plugin_version": "3.0.1",
    "total_logs": 156,
    "days_exported": 7
  },
  "logs": [
    {
      "id": 123,
      "action": "Plugin Activated",
      "context": "Hexagon Automation v3.0.1 activated successfully",
      "level": "success",
      "created_at": "2025-01-13 10:15:00"
    }
  ]
}
```

### ⚙️ Plik Systemowy (`hexagon-system-info-bizrun-*.json`)
```json
{
  "export_info": {
    "site": "bizrun.eu",
    "generated_at": "2025-01-13 15:30:00",
    "plugin_version": "3.0.1",
    "mode": "safe_mode"
  },
  "wordpress": {
    "version": "6.4.2",
    "site_url": "https://bizrun.eu",
    "language": "pl_PL",
    "wp_debug": true
  },
  "server": {
    "php_version": "8.0.30",
    "mysql_version": "8.0.35",
    "memory_limit": "256M"
  },
  "plugin": {
    "version": "3.0.1", 
    "safe_mode": true,
    "logs_count": 45
  }
}
```

## 🚨 Kroki Diagnostyczne

### 1. Przed Kontaktem z Supportem
- [ ] Sprawdź czy safe mode aktywuje się bez błędów
- [ ] Przetestuj logging system (przycisk "Test Log Entry")
- [ ] Pobierz oba pliki diagnostyczne
- [ ] Sprawdź PHP error log w hosting panelu

### 2. Informacje do Załączenia
```
Temat: Hexagon Automation v3.0.1 - Problem z aktywacją
Domena: bizrun.eu
WordPress: [wersja z pliku systemowego]
PHP: [wersja z pliku systemowego]
Błąd: [dokładny komunikat błędu]

Załączniki:
- hexagon-logs-bizrun-[data].json
- hexagon-system-info-bizrun-[data].json
- PHP error log (jeśli dostępny)
```

### 3. Typowe Scenariusze

#### Safe Mode Działa ✅
```
✅ hexagon-automation-safe.php aktywuje się
✅ Tabela hex_logs tworzy się
✅ Logging działa
✅ Pliki eksportują się

➡️ Problem: Główny plugin ma konflikt modułów
🔧 Rozwiązanie: Stopniowe włączanie modułów
```

#### Safe Mode Nie Działa ❌
```
❌ Safe mode też pokazuje błąd krytyczny
❌ Nie można pobrać plików

➡️ Problem: Podstawowy konflikt (PHP/WordPress/hosting)
🔧 Rozwiązanie: Sprawdź PHP error log, memory limit
```

## 📞 Linki Debug dla bizrun.eu

Po aktywacji safe mode, automatyczne linki:
```
Logi: https://bizrun.eu/wp-admin/admin-post.php?action=hexagon_download_logs&_wpnonce=[auto]
System: https://bizrun.eu/wp-admin/admin-post.php?action=hexagon_download_system&_wpnonce=[auto]
```

## 🔍 Dodatkowe Sprawdzenia

### PHP Error Log
```bash
# Sprawdź w hosting panelu lub cPanel
/home/bizrun/public_html/wp-content/debug.log
/var/log/apache2/error.log
```

### WordPress Debug
Dodaj do `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Memory Limit
Zwiększ w `wp-config.php`:
```php
ini_set('memory_limit', '256M');
```

## ⚡ Quick Debug Commands

### Sprawdź czy tabela istnieje
```sql
SHOW TABLES LIKE 'wp_hex_logs';
```

### Sprawdź strukturę tabeli
```sql
DESCRIBE wp_hex_logs;
```

### Sprawdź recent logs
```sql
SELECT * FROM wp_hex_logs ORDER BY created_at DESC LIMIT 5;
```

---

**📧 Do załączenia w mailu:**
1. `hexagon-logs-bizrun-[data].json`
2. `hexagon-system-info-bizrun-[data].json`
3. Dokładny komunikat błędu
4. Kroki które prowadziły do błędu

**🎯 Cel:** Szybka diagnoza i naprawa problemu dla bizrun.eu