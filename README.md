# 🚀 Hexagon Automation v3.1.1 - Enterprise Edition

[![WordPress Plugin](https://img.shields.io/badge/WordPress-Plugin-blue.svg)](https://wordpress.org)
[![Version](https://img.shields.io/badge/Version-3.1.1-green.svg)](https://github.com/hexagon-technology/hexagon-automation/releases)
[![License](https://img.shields.io/badge/License-GPLv2-red.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-7.4+-purple.svg)](https://php.net)
[![React](https://img.shields.io/badge/React-18+-blue.svg)](https://reactjs.org)

**AI-powered content & social media automation for WordPress**

Transform your WordPress site into an AI-powered content machine with advanced automation capabilities.

## 🎯 **New in Version 3.1.1 - Enterprise Edition**

- **Complete WordPress Integration** - Native hooks, shortcodes, and admin interface
- **Enhanced Authentication System** - Secure API key management and sessions
- **Multi-Provider AI Support** - ChatGPT, Claude, Perplexity with quality scoring
- **Advanced Image Generation** - DALL-E 3, Stable Diffusion with WordPress media integration
- **Comprehensive Social Media** - Facebook, Twitter, Instagram, LinkedIn automation
- **Real-Time Analytics** - Performance monitoring and detailed reporting
- **Enterprise Security** - Input validation, CSRF protection, data encryption
- **WordPress.org Compliance** - Full standards compliance with proper documentation

## 🚀 Features

### ✅ Fully Functional AI Integration
- **ChatGPT, Claude & Perplexity APIs** - Real working implementations
- **20+ Content Types** - Articles, guides, reviews, social posts, emails
- **Multi-language Support** - Polish and English content generation
- **Usage Tracking** - Monitor API consumption and costs

### ✅ Advanced Email System
- **Smart SMTP Auto-Configuration** - Gmail, Outlook, custom SMTP
- **Automatic Retry Logic** - 3 attempts with exponential backoff  
- **Daily Digest Reports** - System health and usage statistics
- **Error Alert System** - Instant notifications for critical issues

### ✅ Complete Social Media Automation  
- **4 Platforms Supported** - Facebook, Instagram, Twitter/X, LinkedIn
- **Auto-posting** - Publish WordPress posts automatically
- **Scheduling System** - Plan posts for optimal engagement
- **Template Engine** - Customizable post formats per platform

### ✅ Intelligent Auto-Repair System
- **Proactive Health Monitoring** - Hourly system checks
- **Automatic Issue Resolution** - Fixes common problems without intervention
- **Emergency Recovery** - Safe mode activation during critical errors
- **Self-Healing Database** - Automatic optimization and corruption repair

### ✅ Powerful REST API
- **Complete API Coverage** - All features accessible via REST
- **Dashboard Integration** - Modern React dashboard included
- **Authentication System** - Secure API key authentication
- **Real-time Monitoring** - Live system status and metrics

### ✅ Comprehensive Testing Suite
- **Automated Testing** - 30+ system tests across all modules
- **Health Scoring** - Real-time system health percentage
- **Module Validation** - Individual component testing
- **Performance Monitoring** - Database and API response times

## 📋 Requirements

- **WordPress**: 5.0+
- **PHP**: 7.4+
- **MySQL**: 5.6+
- **Memory**: 128MB+ recommended
- **API Keys**: At least one AI provider (ChatGPT/Claude/Perplexity)

## 🔧 Installation

1. Upload plugin files to `/wp-content/plugins/hexagon-automation/`
2. Activate the plugin through WordPress admin
3. Configure AI providers in Settings → Hexagon Automation
4. Set up email and social media integrations as needed
5. Run system tests to verify all components

## ⚙️ Configuration

### AI Providers Setup
```php
// Example: ChatGPT configuration
hexagon_ai_chatgpt_api_key: 'sk-...'
hexagon_ai_chatgpt_model: 'gpt-4'
hexagon_ai_chatgpt_temperature: 0.7
hexagon_ai_chatgpt_max_tokens: 2000
```

### SMTP Email Configuration
```php
// Auto-configured for Gmail/Outlook
// Or manual SMTP settings:
hexagon_email_smtp_host: 'smtp.yourdomain.com'
hexagon_email_smtp_port: 587
hexagon_email_smtp_encryption: 'tls'
```

### Social Media Integration
```php
// Platform tokens and credentials
hexagon_social_facebook_token: 'your_token'
hexagon_social_auto_post: true
hexagon_social_auto_platforms: ['facebook', 'twitter']
```

## 🔗 REST API Endpoints

### AI Content Generation
```
POST /wp-json/hexagon/v1/ai/generate
{
  "provider": "chatgpt",
  "content_type": "article", 
  "prompt": "Write about AI automation",
  "language": "pl"
}
```

### Social Media Posting
```
POST /wp-json/hexagon/v1/social/post
{
  "platform": "facebook",
  "message": "Check out our new post!",
  "link_url": "https://example.com"
}
```

### System Health Check
```
GET /wp-json/hexagon/v1/status
```

## 🛠️ Auto-Repair Features

### Automatic Fixes
- **SMTP Connection Issues** - Auto-detects and configures proper settings
- **API Rate Limits** - Implements intelligent backoff strategies  
- **Database Corruption** - Repairs and optimizes tables automatically
- **Memory Issues** - Clears caches and optimizes memory usage
- **Disk Space** - Cleans temporary files and old logs

### Health Monitoring
- **Hourly System Checks** - Comprehensive health analysis
- **Error Threshold Detection** - Triggers repairs before failures
- **Performance Monitoring** - Database query optimization
- **Security Validation** - Ensures proper access controls

## 📊 Dashboard Features

Modern React dashboard available at: `your-site.com/wp-content/plugins/hexagon-automation/dashboard/`

### Key Features:
- **Real-time System Status** - Live health monitoring
- **AI Usage Analytics** - Token consumption and costs
- **Social Media Metrics** - Engagement and reach statistics  
- **Error Logs Viewer** - Filterable system logs
- **Configuration Management** - Easy settings interface
- **Test Suite Runner** - One-click system validation

## 🔒 Security Features

- **ABSPATH Protection** - Prevents direct file access
- **Nonce Validation** - CSRF protection on all forms
- **Data Sanitization** - All inputs properly cleaned
- **Capability Checks** - Role-based access control
- **API Key Encryption** - Secure credential storage

## 📈 Performance Optimizations

- **Database Indexing** - Optimized queries for large datasets
- **Caching Layer** - Reduces API calls and improves speed
- **Lazy Loading** - Modules load only when needed
- **Memory Management** - Efficient resource utilization
- **Background Processing** - Heavy tasks run asynchronously

## 🐛 Troubleshooting

### Common Issues:

**API Connection Errors**
- Verify API keys are correct and active
- Check firewall settings allow HTTPS connections
- Run system tests to diagnose specific issues

**Email Delivery Problems** 
- Auto-repair will attempt SMTP configuration
- Check hosting provider email restrictions
- Verify SMTP credentials if using custom server

**Social Media Posting Failures**
- Ensure platform tokens haven't expired
- Check API rate limits and usage quotas
- Verify page/account permissions

### Debug Mode
Enable WordPress debug mode for detailed error logs:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## 📞 Support

- **Documentation**: Full docs in `docs/` directory
- **GitHub Issues**: Report bugs and feature requests
- **Email Support**: support@hex-net.eu (Premium users)
- **System Tests**: Use built-in test suite for diagnostics

## 📄 License

This plugin is licensed under GPL v2 or later.

## 🔄 Version History

### v3.0.0 (Current)
- Complete rewrite with functional AI/email/social integrations
- Advanced auto-repair system with proactive monitoring
- Comprehensive REST API with React dashboard
- Intelligent error handling and recovery
- Full test suite with 30+ automated checks

### v2.5.0 (Previous)
- Basic plugin structure and UI framework
- Skeleton implementations of core features
- Initial dashboard interface design

---

**Built with ❤️ by [Hexagon Technology](https://hex-net.eu)**
