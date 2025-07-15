# Hexagon Automation v3.0.0 - Installation Guide

## 📋 Pre-Installation Requirements

### System Requirements
- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher (8.0+ recommended)
- **MySQL**: 5.6 or higher
- **Memory Limit**: 128MB minimum (256MB recommended)
- **Disk Space**: 50MB minimum
- **cURL**: Required for API connections

### API Requirements
- At least one AI provider API key:
  - OpenAI ChatGPT API key
  - Anthropic Claude API key  
  - Perplexity API key
- Social media platform tokens (optional):
  - Facebook Page Access Token
  - Instagram Business Account Token
  - Twitter API v2 Bearer Token
  - LinkedIn API Token

## 🚀 Installation Methods

### Method 1: WordPress Admin Upload

1. **Download**: Get the `hexagon-automation.zip` file
2. **Upload**: 
   - Go to WordPress Admin → Plugins → Add New
   - Click "Upload Plugin" 
   - Select the ZIP file
   - Click "Install Now"
3. **Activate**: Click "Activate Plugin"

### Method 2: FTP Upload

1. **Extract**: Unzip the plugin files
2. **Upload**: Upload the `hexagon-automation` folder to `/wp-content/plugins/`
3. **Activate**: Go to WordPress Admin → Plugins and activate "Hexagon Automation"

### Method 3: WP-CLI Installation

```bash
# Upload plugin files to your server first, then:
wp plugin activate hexagon-automation
```

## ⚙️ Initial Configuration

### Step 1: Plugin Activation
After activation, the plugin will:
- Create required database tables
- Set up default configuration
- Initialize the auto-repair system
- Schedule health checks

### Step 2: Run System Tests
1. Go to **WordPress Admin → Hexagon Automation → System Tests**
2. Click "Run All Tests"
3. Verify all core components are working
4. Address any failed tests before proceeding

### Step 3: Configure AI Providers

#### ChatGPT Setup
1. Get API key from [OpenAI Platform](https://platform.openai.com/api-keys)
2. Go to **Settings → AI Providers → ChatGPT**
3. Enter your API key
4. Select model (gpt-4 recommended)
5. Test connection

#### Claude Setup
1. Get API key from [Anthropic Console](https://console.anthropic.com/)
2. Go to **Settings → AI Providers → Claude**
3. Enter your API key
4. Select model (claude-3-sonnet recommended)
5. Test connection

#### Perplexity Setup
1. Get API key from [Perplexity Labs](https://labs.perplexity.ai/)
2. Go to **Settings → AI Providers → Perplexity**
3. Enter your API key
4. Select model
5. Test connection

### Step 4: Email Configuration

#### Automatic SMTP Configuration
The plugin will automatically detect and configure SMTP for:
- Gmail accounts
- Outlook/Hotmail accounts
- Common hosting providers

#### Manual SMTP Configuration
If auto-configuration fails:
1. Go to **Settings → Email → SMTP Settings**
2. Enable "Use SMTP"
3. Enter your SMTP details:
   - Host: `smtp.yourdomain.com`
   - Port: `587` (TLS) or `465` (SSL)
   - Username: Your email address
   - Password: Your email password
   - Encryption: TLS or SSL
4. Test email delivery

### Step 5: Social Media Integration (Optional)

#### Facebook/Instagram Setup
1. Create a Facebook App at [developers.facebook.com](https://developers.facebook.com)
2. Get Page Access Token
3. For Instagram: Connect Instagram Business Account
4. Enter tokens in **Settings → Social Media → Facebook/Instagram**

#### Twitter Setup
1. Create Twitter App at [developer.twitter.com](https://developer.twitter.com)
2. Get API v2 Bearer Token
3. Enter token in **Settings → Social Media → Twitter**

#### LinkedIn Setup
1. Create LinkedIn App at [developer.linkedin.com](https://developer.linkedin.com)
2. Get access token with required permissions
3. Enter token in **Settings → Social Media → LinkedIn**

## 🔧 Dashboard Setup

### Accessing the Dashboard
The React dashboard is available at:
```
https://yoursite.com/wp-content/plugins/hexagon-automation/dashboard/
```

### Dashboard Authentication
1. Use your WordPress admin credentials
2. The plugin will generate an API key automatically
3. Dashboard connects via REST API

### Building Dashboard (Development)
If you need to rebuild the dashboard:
```bash
cd /path/to/hexagon-automation/dashboard
npm install
npm run build
```

## 🛡️ Security Configuration

### API Security
1. **API Keys**: Store securely in WordPress options
2. **Nonce Validation**: Enabled on all forms
3. **Capability Checks**: Admin access required
4. **Rate Limiting**: Built-in API rate limiting

### File Permissions
Ensure proper file permissions:
```bash
# Plugin files
chmod 644 hexagon-automation/*.php
chmod 755 hexagon-automation/

# Make sure wp-content is writable
chmod 755 wp-content/
chmod 755 wp-content/plugins/
```

## 📊 Verification Steps

### 1. System Health Check
Run the built-in health check:
```
WordPress Admin → Hexagon Automation → System Status
```

### 2. Test All Modules
- **AI Generation**: Create test content
- **Email System**: Send test email
- **Social Media**: Post test message
- **Auto-Repair**: Verify scheduled tasks

### 3. Monitor Logs
Check system logs at:
```
WordPress Admin → Hexagon Automation → Debug Logs
```

## 🚨 Troubleshooting

### Common Installation Issues

#### Plugin Activation Fails
- Check PHP version (7.4+ required)
- Verify memory limit (128MB minimum)
- Check file permissions
- Review PHP error logs

#### Database Tables Not Created
- Verify MySQL permissions
- Check WordPress database connection
- Manually run activation hook:
```php
do_action('hexagon_activation_hook');
```

#### API Connections Fail
- Verify SSL certificates
- Check firewall settings
- Test cURL functionality:
```php
$response = wp_remote_get('https://api.openai.com');
var_dump($response);
```

#### Dashboard Not Loading
- Check file permissions on dashboard directory
- Verify web server can serve static files
- Check browser console for JavaScript errors

### Performance Issues

#### High Memory Usage
- Increase PHP memory limit in wp-config.php:
```php
ini_set('memory_limit', '256M');
```
- Enable WordPress object caching
- Check for plugin conflicts

#### Slow API Responses
- Verify network connectivity to AI providers
- Check for rate limiting
- Monitor API usage quotas

## 📞 Support Resources

### Self-Help
1. **System Tests**: Built-in diagnostic tools
2. **Debug Logs**: Detailed error information
3. **Auto-Repair**: Automatic issue resolution
4. **Documentation**: Complete guides in `/docs/`

### Getting Help
- **Email**: support@hex-net.eu
- **Documentation**: README.md and CHANGELOG.md
- **System Health**: Use built-in monitoring tools

## 🔄 Updates

### Automatic Updates
The plugin supports WordPress automatic updates. When a new version is available:
1. Backup your site
2. Update through WordPress admin
3. Run system tests after update

### Manual Updates
1. Download latest version
2. Deactivate current plugin
3. Replace plugin files
4. Reactivate plugin
5. Run system tests

### Version Management
- **3.0.x**: Bug fixes and minor improvements
- **3.x.0**: New features and enhancements  
- **x.0.0**: Major releases with breaking changes

---

**🎉 Installation Complete!**

Your Hexagon Automation plugin is now ready to use. Start by configuring your AI providers and running your first content generation test.