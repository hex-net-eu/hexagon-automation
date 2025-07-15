# Hexagon Automation v3.0.2 - Complete WordPress Plugin

🤖 **AI-Powered Content & Social Media Automation for WordPress**

## 🚀 Quick Start

1. **Install:** Upload `hexagon-automation-v3.0.2-fixed.zip` through WordPress admin
2. **Activate:** Start with `hexagon-automation-fixed.php` for testing
3. **Test:** Go to Admin → Hexagon Fixed → Click "🧪 Test AJAX"
4. **Upgrade:** If tests pass, activate `hexagon-automation.php` (full version)
5. **Configure:** Add API keys in Settings → AI Settings

## 📦 Package Contents

### 🎯 Plugin Versions (Choose One)

| File | Purpose | When to Use |
|------|---------|-------------|
| `hexagon-automation.php` | **Main Plugin v3.0.2** | After fixed version works |
| `hexagon-automation-fixed.php` | **Test Version** | **START HERE** - Safe testing |
| `hexagon-automation-safe.php` | **Safe Mode** | If main version has issues |
| `hexagon-automation-minimal.php` | **Minimal Test** | Environment compatibility |

### 🛠️ Fixed Files (NEW in v3.0.2)

- `includes/ajax-fix.php` - Fixes HTTP 500 errors in admin-ajax.php
- `includes/admin-nonces.php` - Adds proper nonces to JavaScript
- `assets/js/nonces.js` - Safe AJAX calls with error handling
- `debug-logs.txt` - Template for debugging information

### 📚 Documentation

- `INSTALL-INSTRUCTIONS.txt` - Step-by-step installation guide
- `CHANGELOG-v3.0.2.txt` - Complete changelog for this version
- `API_DOCUMENTATION.md` - REST API endpoints documentation
- `DEVELOPER_GUIDE.md` - Development and customization guide
- `TESTING_INSTRUCTIONS.md` - Testing procedures

### 🎨 React Dashboard

- `dashboard/` - Complete React 18 + TypeScript dashboard
- Independent frontend that can run separately
- Modern UI with Tailwind CSS and shadcn/ui components

## 🔧 What's Fixed in v3.0.2

### ❌ Previous Issues → ✅ Solutions

| Problem | Solution |
|---------|----------|
| ❌ HTTP 500 errors in admin-ajax.php | ✅ Added comprehensive error handling |
| ❌ Missing nonces in JavaScript | ✅ Proper nonce generation and passing |
| ❌ Undefined functions during AJAX | ✅ Safe fallbacks and graceful degradation |
| ❌ Crashes in AI Manager | ✅ Try-catch wrappers for all API calls |
| ❌ WordPress installation failures | ✅ Correct plugin structure and headers |

## 🤖 Core Features

### AI Content Generation
- **Providers:** ChatGPT, Claude, Perplexity
- **Content Types:** 20+ templates (articles, guides, social posts, etc.)
- **Languages:** Polish, English support
- **Smart Prompts:** Context-aware system prompts

### 📧 Email Automation
- **SMTP Auto-Config:** Gmail, Outlook, custom servers
- **Retry Logic:** 3-attempt system with exponential backoff
- **Templates:** Professional email templates
- **Error Recovery:** Automatic error detection and fixing

### 📱 Social Media Integration
- **Platforms:** Facebook, Instagram, Twitter, LinkedIn
- **Features:** Auto-posting, scheduling, templates
- **Real APIs:** Actual integration with platform APIs
- **Management:** Centralized dashboard for all platforms

### 🔧 Auto-Repair System
- **Monitoring:** Proactive health checks every hour
- **Detection:** Database issues, file permissions, API failures
- **Recovery:** Automatic repair attempts
- **Alerts:** Email notifications for critical issues

### 🧪 System Testing
- **Comprehensive Tests:** All modules and integrations
- **Performance Monitoring:** Memory, execution time tracking
- **API Testing:** Connection validation for all services
- **Reports:** Detailed test results and recommendations

## 🔑 Configuration

### Required API Keys

1. **OpenAI API Key** (ChatGPT)
   - Get from: https://platform.openai.com/api-keys
   - Add in: Settings → AI Settings

2. **Anthropic API Key** (Claude)
   - Get from: https://console.anthropic.com/
   - Add in: Settings → AI Settings

3. **Perplexity API Key**
   - Get from: https://www.perplexity.ai/settings/api
   - Add in: Settings → AI Settings

### Email Configuration

1. **SMTP Settings**
   - Auto-detection for Gmail/Outlook
   - Manual configuration for custom servers
   - Settings → Email Configuration

### Social Media Setup

1. **Facebook/Instagram**
   - Create Facebook App
   - Get access tokens
   - Settings → Social Media

2. **Twitter/LinkedIn**
   - Create developer accounts
   - Generate API keys
   - Configure in plugin settings

## 🐛 Troubleshooting

### If Plugin Won't Activate

1. **Try versions in order:**
   - `hexagon-automation-minimal.php` (basic test)
   - `hexagon-automation-fixed.php` (safe version)
   - `hexagon-automation.php` (full version)

2. **Enable WordPress debugging:**
   ```php
   // Add to wp-config.php
   define( 'WP_DEBUG', true );
   define( 'WP_DEBUG_LOG', true );
   define( 'WP_DEBUG_DISPLAY', false );
   ```

3. **Check debug logs:**
   - WordPress: `/wp-content/debug.log`
   - Plugin: Admin → Debug Export

### If AJAX Errors Occur

1. **Test AJAX functionality:**
   - Go to: Admin → Hexagon Fixed
   - Click: "🧪 Test AJAX"
   - Check results

2. **Browser console errors:**
   - Open F12 Developer Tools
   - Check Console tab for JavaScript errors
   - Report any red error messages

### Getting Help

1. **Fill debug template:** `debug-logs.txt`
2. **Direct access:** `https://your-site.com/wp-content/plugins/hexagon-automation/debug-direct-access.php`
3. **Include:** WordPress version, PHP version, error messages

## ⚙️ System Requirements

- **WordPress:** 5.0 or higher
- **PHP:** 7.4 or higher
- **MySQL:** 5.7 or higher
- **Memory:** 256MB minimum
- **Extensions:** cURL, JSON, mbstring

## 🔄 Version History

- **v3.0.2** (2025-01-13) - AJAX fixes, error handling improvements
- **v3.0.1** - Database schema fixes, function signatures
- **v3.0.0** - Initial release with full functionality

## 📄 License

This plugin is proprietary software developed by Hexagon Technology.

## 🌐 Links

- **Website:** https://hex-net.eu
- **Support:** Contact through website
- **Documentation:** Check included .md files

---

**Start with `hexagon-automation-fixed.php` for safe testing, then upgrade to the full version once everything works!**