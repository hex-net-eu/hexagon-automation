# Hexagon Automation v3.0.0 - API Documentation

## 🌐 REST API Overview

The Hexagon Automation plugin provides a comprehensive REST API for all functionality. All endpoints are prefixed with `/wp-json/hexagon/v1/`.

### Base URL
```
https://yoursite.com/wp-json/hexagon/v1/
```

### Authentication
All API endpoints require authentication via API key in the header:
```
X-API-Key: your_api_key_here
```

Get your API key by authenticating through the dashboard or WordPress admin.

## 🔐 Authentication Endpoints

### POST /auth
Authenticate and get API key

**Request:**
```json
{
  "username": "admin",
  "password": "password"
}
```

**Response:**
```json
{
  "success": true,
  "api_key": "hexagon_abcd1234...",
  "user": {
    "id": 1,
    "username": "admin",
    "email": "admin@example.com",
    "display_name": "Administrator"
  }
}
```

## 🤖 AI Content Generation

### POST /ai/generate
Generate content using AI providers

**Headers:**
```
X-API-Key: your_api_key
Content-Type: application/json
```

**Request:**
```json
{
  "provider": "chatgpt",
  "content_type": "article",
  "prompt": "Write about artificial intelligence in marketing",
  "language": "pl"
}
```

**Parameters:**
- `provider` (required): `chatgpt`, `claude`, or `perplexity`
- `content_type` (required): One of 20+ content types
- `prompt` (required): Content generation prompt
- `language` (optional): `pl` or `en` (default: `pl`)

**Content Types:**
- `article` - Professional articles
- `guide` - Step-by-step guides
- `news` - News articles
- `review` - Product/service reviews
- `tutorial` - How-to tutorials
- `comparison` - Comparison articles
- `case_study` - Case studies
- `interview` - Interview format
- `opinion` - Opinion pieces
- `listicle` - List articles
- `howto` - Instruction guides
- `definition` - Term definitions
- `faq` - FAQ content
- `press_release` - Press releases
- `blog_post` - Blog posts
- `product_description` - Product descriptions
- `social_media` - Social media posts
- `email_marketing` - Email campaigns
- `landing_page` - Landing page copy
- `summary` - Content summaries

**Response:**
```json
{
  "success": true,
  "data": {
    "content": "Generated content here...",
    "usage": {
      "prompt_tokens": 150,
      "completion_tokens": 800,
      "total_tokens": 950
    },
    "provider": "ChatGPT",
    "model": "gpt-4"
  }
}
```

### POST /ai/test
Test AI provider connection

**Request:**
```json
{
  "provider": "chatgpt"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Connection to chatgpt successful",
  "provider": "chatgpt"
}
```

### GET /ai/stats
Get AI usage statistics

**Response:**
```json
{
  "success": true,
  "data": {
    "chatgpt": {
      "requests": 45,
      "tokens": 125000,
      "cost": 2.50
    },
    "claude": {
      "requests": 23,
      "tokens": 89000,
      "cost": 1.78
    }
  }
}
```

## 📱 Social Media Integration

### POST /social/post
Post to social media platforms

**Request:**
```json
{
  "platform": "facebook",
  "message": "Check out our latest blog post about AI automation!",
  "image_url": "https://example.com/image.jpg",
  "link_url": "https://example.com/blog/ai-automation"
}
```

**Parameters:**
- `platform` (required): `facebook`, `instagram`, `twitter`, or `linkedin`
- `message` (required): Post content
- `image_url` (optional): Image URL for posts with media
- `link_url` (optional): Link to include in post

**Response:**
```json
{
  "success": true,
  "data": {
    "platform": "Facebook",
    "post_id": "123456789_987654321",
    "message": "Post published successfully"
  }
}
```

### POST /social/schedule
Schedule social media posts

**Request:**
```json
{
  "platform": "twitter",
  "message": "Exciting announcement coming tomorrow!",
  "schedule_time": "2025-01-14 10:00:00",
  "image_url": "https://example.com/teaser.jpg"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Post scheduled successfully",
  "scheduled_time": "2025-01-14 10:00:00"
}
```

### POST /social/test
Test social media platform connection

**Request:**
```json
{
  "platform": "facebook"
}
```

### GET /social/stats
Get social media statistics

**Response:**
```json
{
  "success": true,
  "data": {
    "facebook": {
      "posts": 25,
      "engagement": 1250,
      "reach": 15000
    },
    "twitter": {
      "posts": 18,
      "engagement": 890,
      "reach": 8500
    }
  }
}
```

## 📧 Email System

### POST /email/test
Test email configuration

**Request:**
```json
{
  "test_email": "test@example.com"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Test email sent successfully"
}
```

### POST /email/send
Send email

**Request:**
```json
{
  "to": "recipient@example.com",
  "subject": "Test Email from Hexagon Automation",
  "message": "<h1>Hello!</h1><p>This is a test email.</p>"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Email sent successfully"
}
```

## ⚙️ Settings Management

### GET /settings
Get all plugin settings

**Response:**
```json
{
  "success": true,
  "data": {
    "ai": {
      "chatgpt_api_key": "sk-...",
      "chatgpt_model": "gpt-4",
      "chatgpt_temperature": 0.7,
      "chatgpt_max_tokens": 2000
    },
    "email": {
      "use_smtp": true,
      "smtp_host": "smtp.gmail.com",
      "smtp_port": 587,
      "smtp_encryption": "tls"
    },
    "social": {
      "auto_post": true,
      "auto_platforms": ["facebook", "twitter"],
      "post_template": "{title} {excerpt}"
    }
  }
}
```

### POST /settings
Update plugin settings

**Request:**
```json
{
  "settings": {
    "ai": {
      "chatgpt_temperature": 0.8,
      "chatgpt_max_tokens": 1500
    },
    "email": {
      "daily_digest": true
    }
  }
}
```

**Response:**
```json
{
  "success": true,
  "message": "Settings updated successfully"
}
```

## 📊 System Monitoring

### GET /status
Get comprehensive system status

**Response:**
```json
{
  "success": true,
  "data": {
    "wordpress_version": "6.4.2",
    "php_version": "8.0.30",
    "plugin_version": "3.0.0",
    "memory_limit": "256M",
    "database_version": "8.0.35",
    "modules": {
      "ai_manager": true,
      "email_integration": true,
      "social_integration": true,
      "auto_repair": true
    },
    "api_status": {
      "chatgpt": true,
      "claude": false,
      "perplexity": true,
      "facebook": true,
      "twitter": false
    }
  }
}
```

### GET /dashboard
Get dashboard data

**Response:**
```json
{
  "success": true,
  "data": {
    "ai_stats": {
      "chatgpt": {"requests": 45, "tokens": 125000}
    },
    "social_stats": {
      "facebook": {"posts": 25, "engagement": 1250}
    },
    "recent_logs": [
      {
        "id": 123,
        "action": "AI Content Generated",
        "context": "Provider: chatgpt, Type: article",
        "level": "success",
        "created_at": "2025-01-13 10:30:00"
      }
    ],
    "error_count_24h": 2,
    "system_health": {
      "uptime": 86400,
      "memory_usage": {
        "used": 134217728,
        "peak": 150994944,
        "limit": 268435456
      },
      "database_size": 45.2
    }
  }
}
```

## 📝 Logging System

### GET /logs
Get system logs

**Parameters:**
- `level` (optional): Filter by log level (`error`, `warning`, `info`, `success`)
- `limit` (optional): Number of logs to return (default: 50)
- `offset` (optional): Pagination offset (default: 0)

**Example:**
```
GET /logs?level=error&limit=20&offset=0
```

**Response:**
```json
{
  "success": true,
  "data": {
    "logs": [
      {
        "id": 125,
        "action": "AI Generation Error",
        "context": "ChatGPT API rate limit exceeded",
        "level": "error",
        "created_at": "2025-01-13 11:15:00"
      }
    ],
    "total": 156,
    "limit": 20,
    "offset": 0
  }
}
```

### DELETE /logs/clear
Clear all system logs

**Response:**
```json
{
  "success": true,
  "message": "Logs cleared successfully"
}
```

## 🔧 Error Handling

### Error Response Format
All API errors follow this format:

```json
{
  "success": false,
  "code": "ai_error",
  "message": "ChatGPT API Error: Insufficient quota",
  "data": {
    "status": 500
  }
}
```

### Common Error Codes
- `rest_forbidden` - Authentication required (401)
- `insufficient_permissions` - User lacks permissions (403)
- `ai_error` - AI provider error (500)
- `social_error` - Social media API error (500)
- `email_error` - Email sending error (500)
- `settings_error` - Settings update error (500)

### Rate Limiting
API endpoints are rate limited:
- **AI endpoints**: 60 requests per hour per user
- **Social endpoints**: 30 requests per hour per user
- **Other endpoints**: 300 requests per hour per user

Rate limit headers are included in responses:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1642089600
```

## 📘 Code Examples

### JavaScript/Ajax Example
```javascript
// Generate AI content
async function generateContent() {
  const response = await fetch('/wp-json/hexagon/v1/ai/generate', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-API-Key': 'your_api_key_here'
    },
    body: JSON.stringify({
      provider: 'chatgpt',
      content_type: 'article',
      prompt: 'Write about WordPress automation',
      language: 'pl'
    })
  });
  
  const data = await response.json();
  if (data.success) {
    console.log('Generated content:', data.data.content);
  }
}
```

### PHP Example
```php
// Post to social media
$response = wp_remote_post('https://yoursite.com/wp-json/hexagon/v1/social/post', [
  'headers' => [
    'X-API-Key' => 'your_api_key_here',
    'Content-Type' => 'application/json'
  ],
  'body' => json_encode([
    'platform' => 'facebook',
    'message' => 'Check out our new feature!',
    'link_url' => 'https://example.com/new-feature'
  ])
]);

$data = json_decode(wp_remote_retrieve_body($response), true);
if ($data['success']) {
  echo 'Post ID: ' . $data['data']['post_id'];
}
```

### cURL Example
```bash
# Test system status
curl -X GET "https://yoursite.com/wp-json/hexagon/v1/status" \
  -H "X-API-Key: your_api_key_here" \
  -H "Content-Type: application/json"
```

## 🔗 Webhooks (Future Feature)

Future versions will support webhooks for real-time notifications:
- Content generation completed
- Social media post published
- System health alerts
- Auto-repair actions taken

---

**📚 Need More Help?**

- Check the plugin's built-in system tests
- Review the debug logs for detailed error information  
- Contact support at support@hex-net.eu