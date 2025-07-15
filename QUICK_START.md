# Hexagon Automation v3.0.1 - Quick Start Guide

## 🚀 5-Minute Setup

### 1. Install Plugin (2 minutes)
```bash
# Upload to WordPress
1. Upload hexagon-automation folder to /wp-content/plugins/
2. Activate plugin in WordPress Admin → Plugins
3. Plugin creates database tables automatically
```

### 2. Configure AI Provider (1 minute)
```php
# Get ChatGPT API Key (recommended)
1. Go to https://platform.openai.com/api-keys
2. Create new API key
3. WordPress Admin → Hexagon Automation → AI Settings
4. Paste API key, select gpt-4 model
5. Click "Test Connection"
```

### 3. Test System (1 minute)
```php
# Run System Tests
1. Go to WordPress Admin → Hexagon Automation → System Tests
2. Click "Run All Tests"
3. Verify 80%+ success rate
4. Fix any critical failures
```

### 4. Generate First Content (1 minute)
```php
# Via Dashboard
1. Go to: yoursite.com/wp-content/plugins/hexagon-automation/dashboard/
2. Navigate to AI Settings → Generate Content
3. Select: Provider=ChatGPT, Type=Article, Language=Polish
4. Enter prompt: "Napisz artykuł o automatyzacji WordPress"
5. Click Generate
```

## ⚡ Essential Features Demo

### AI Content Generation
```javascript
// REST API Call
fetch('/wp-json/hexagon/v1/ai/generate', {
  method: 'POST',
  headers: {
    'X-API-Key': 'your_key',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    provider: 'chatgpt',
    content_type: 'article',
    prompt: 'AI w marketingu',
    language: 'pl'
  })
})
```

### Social Media Auto-Post
```php
# Enable Auto-Posting
1. Settings → Social Media → Auto-Post: ON
2. Select platforms: Facebook, Twitter
3. Template: "{title} - {excerpt} {link}"
4. Publish WordPress post → automatically shares to social media
```

### Email System Setup
```php
# Auto-SMTP Configuration
1. Plugin auto-detects Gmail/Outlook accounts
2. For custom SMTP: Settings → Email → Manual SMTP
3. Test: Settings → Email → Send Test Email
4. Enable daily digest for system health reports
```

## 🔧 Advanced Configuration (Optional)

### Custom Content Types
```php
add_filter('hexagon_ai_content_types', function($types) {
    $types['email_campaign'] = [
        'name' => 'Email Campaign',
        'prompt' => 'Create compelling email marketing content...',
        'max_tokens' => 800
    ];
    return $types;
});
```

### Social Media Templates
```php
# Customize per platform
Facebook: "{title} 📖 {excerpt} #wordpress #ai"
Twitter: "🚀 {title} {link} #automation"
LinkedIn: "Professional insight: {title} {excerpt}"
```

### Auto-Repair Configuration
```php
# System automatically:
- Monitors health every hour
- Fixes SMTP issues
- Repairs database corruption
- Optimizes performance
- Sends alerts for critical errors
```

## 📊 Dashboard Overview

### Main Dashboard
- **System Health**: Real-time status (95%+ = healthy)
- **AI Usage**: Tokens consumed, costs, rate limits
- **Social Stats**: Posts published, engagement rates
- **Recent Activity**: Live log of all actions

### Key Metrics to Monitor
- **Error Rate**: Should be <5%
- **API Response Time**: <2 seconds average
- **Memory Usage**: <80% of limit
- **Database Size**: Monitor growth

## 🛠️ Troubleshooting Quick Fixes

### API Connection Issues
```bash
# Check:
1. API keys are correct and active
2. Server can make HTTPS requests
3. No firewall blocking api.openai.com
4. WordPress cURL is enabled
```

### Email Not Sending
```bash
# Auto-repair will try to fix, or manually:
1. Check SMTP settings
2. Test with different port (587, 465, 25)
3. Verify hosting provider allows SMTP
4. Check for rate limiting
```

### Social Media Failures
```bash
# Common fixes:
1. Refresh expired tokens
2. Check API rate limits
3. Verify page/account permissions
4. Test with simple text post first
```

### Plugin Conflicts
```bash
# Diagnostic steps:
1. Deactivate other plugins temporarily
2. Switch to default WordPress theme
3. Check PHP error logs
4. Run system tests to isolate issues
```

## 📈 Optimization Tips

### Performance
```php
# Enable caching
1. Install Redis/Memcached
2. Enable WordPress object cache
3. Use CDN for dashboard assets
4. Monitor database query performance
```

### Security
```php
# Best practices:
1. Use strong API keys
2. Enable 2FA on AI provider accounts
3. Regularly update plugin
4. Monitor access logs
5. Backup before major changes
```

### Cost Management
```php
# AI Usage optimization:
1. Set monthly token limits
2. Use cheaper models for simple tasks
3. Cache frequently requested content
4. Monitor usage in dashboard
```

## 🔗 Quick Links

- **Dashboard**: `/wp-content/plugins/hexagon-automation/dashboard/`
- **System Tests**: `WordPress Admin → Hexagon Automation → Tests`
- **API Documentation**: `API_DOCUMENTATION.md`
- **Full Installation Guide**: `INSTALLATION.md`
- **Developer Guide**: `DEVELOPER_GUIDE.md`

## 🆘 Need Help?

### Self-Diagnosis
1. **Run System Tests** - Identifies most issues
2. **Check Debug Logs** - Shows detailed error information
3. **Verify API Keys** - Test each provider connection
4. **Monitor Health Status** - Real-time system monitoring

### Support Resources
- **Documentation**: Complete guides included
- **Auto-Repair**: System fixes issues automatically
- **Email Alerts**: Notified of critical problems
- **Support**: support@hex-net.eu (Premium users)

---

**🎉 You're Ready!**

Hexagon Automation v3.0.0 is now configured and ready to automate your WordPress content and social media. The system will monitor itself and fix issues automatically, but you can always check the dashboard for detailed insights.

**Next Steps:**
1. Generate your first AI article
2. Set up social media auto-posting  
3. Configure email notifications
4. Explore advanced features in the documentation