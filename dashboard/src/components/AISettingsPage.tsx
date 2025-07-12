import { useState } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Switch } from '@/components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Slider } from '@/components/ui/slider';
import { Progress } from '@/components/ui/progress';
import { 
  Bot, 
  Key, 
  Settings, 
  Zap,
  CheckCircle,
  AlertTriangle,
  RefreshCw,
  Eye,
  EyeOff,
  Globe,
  Clock,
  BarChart,
  FileText
} from 'lucide-react';

export function AISettingsPage() {
  const [showApiKeys, setShowApiKeys] = useState<Record<string, boolean>>({});
  const [aiConfig, setAiConfig] = useState({
    chatgpt: {
      enabled: true,
      apiKey: '••••••••••••sk-1234',
      model: 'gpt-4-turbo',
      temperature: 0.7,
      maxTokens: 2000,
      systemPrompt: 'You are a professional content writer creating engaging articles for a business website.',
      status: 'connected',
      usage: 85,
      monthlyLimit: 100000,
      currentUsage: 85000
    },
    claude: {
      enabled: true,
      apiKey: '••••••••••••cl-5678',
      model: 'claude-3-sonnet',
      temperature: 0.6,
      maxTokens: 1500,
      systemPrompt: 'Create professional, well-structured content with clear headings and engaging introductions.',
      status: 'connected',
      usage: 45,
      monthlyLimit: 50000,
      currentUsage: 22500
    },
    perplexity: {
      enabled: false,
      apiKey: '',
      model: 'llama-3.1-sonar-small-128k-online',
      temperature: 0.2,
      maxTokens: 1000,
      systemPrompt: 'Provide current, fact-based content with real-time information and sources.',
      status: 'disconnected',
      usage: 0,
      monthlyLimit: 10000,
      currentUsage: 0
    }
  });

  const [contentTypes] = useState([
    'Artykuł (Article)', 'Awans (Announcement)', 'Poradnik (Guide)', 'Definicja (Definition)',
    'How To', 'Raport (Report)', 'Pobierz (Download)', 'Ciekawostka (Curiosity)',
    'Wywiad (Interview)', 'Wydarzenie (Event)', 'Nowość (News)', 'Hot News',
    'Mega News', 'Patronat (Patronage)', 'Zaproszenie (Invitation)', 'Przetarg (Tender)',
    'Informacja prasowa (Press Release)', 'Ranking', 'Komentarz (Comment)', 'Video'
  ]);

  const toggleApiKeyVisibility = (provider: string) => {
    setShowApiKeys(prev => ({
      ...prev,
      [provider]: !prev[provider]
    }));
  };

  const testConnection = async (provider: string) => {
    // Simulate API test
    console.log(`Testing ${provider} connection...`);
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-3xl font-bold bg-gradient-primary bg-clip-text text-transparent">
            AI Settings
          </h2>
          <p className="text-muted-foreground mt-2">
            Configure your AI providers and content generation settings
          </p>
        </div>
        <Button className="bg-gradient-primary hover:opacity-90">
          <RefreshCw className="mr-2 h-4 w-4" />
          Refresh All Connections
        </Button>
      </div>

      <Tabs defaultValue="providers" className="space-y-6">
        <TabsList className="grid grid-cols-3 w-full max-w-md">
          <TabsTrigger value="providers">AI Providers</TabsTrigger>
          <TabsTrigger value="content">Content Settings</TabsTrigger>
          <TabsTrigger value="advanced">Advanced</TabsTrigger>
        </TabsList>

        <TabsContent value="providers" className="space-y-6">
          {Object.entries(aiConfig).map(([provider, config]) => (
            <Card key={provider} className="bg-gradient-card border-border/50 shadow-card">
              <CardHeader>
                <div className="flex items-center justify-between">
                  <div className="flex items-center space-x-3">
                    <Bot className="h-6 w-6 text-hexagon-primary" />
                    <div>
                      <CardTitle className="capitalize">{provider}</CardTitle>
                      <CardDescription>
                        {provider === 'chatgpt' && 'OpenAI ChatGPT for content generation'}
                        {provider === 'claude' && 'Anthropic Claude for advanced reasoning'}
                        {provider === 'perplexity' && 'Real-time web search integration'}
                      </CardDescription>
                    </div>
                  </div>
                  <div className="flex items-center space-x-3">
                    <Badge variant={config.status === 'connected' ? 'default' : 'destructive'}>
                      {config.status === 'connected' ? (
                        <CheckCircle className="mr-1 h-3 w-3" />
                      ) : (
                        <AlertTriangle className="mr-1 h-3 w-3" />
                      )}
                      {config.status}
                    </Badge>
                    <Switch 
                      checked={config.enabled}
                      onCheckedChange={(checked) => 
                        setAiConfig(prev => ({
                          ...prev,
                          [provider]: { ...prev[provider], enabled: checked }
                        }))
                      }
                    />
                  </div>
                </div>
              </CardHeader>
              <CardContent className="space-y-6">
                {/* API Key Configuration */}
                <div className="space-y-2">
                  <Label htmlFor={`${provider}-api-key`} className="flex items-center">
                    <Key className="mr-2 h-4 w-4" />
                    API Key
                  </Label>
                  <div className="flex space-x-2">
                    <div className="relative flex-1">
                      <Input
                        id={`${provider}-api-key`}
                        type={showApiKeys[provider] ? 'text' : 'password'}
                        value={config.apiKey}
                        onChange={(e) => 
                          setAiConfig(prev => ({
                            ...prev,
                            [provider]: { ...prev[provider], apiKey: e.target.value }
                          }))
                        }
                        placeholder="Enter your API key"
                      />
                      <Button
                        variant="ghost"
                        size="sm"
                        className="absolute right-2 top-1/2 -translate-y-1/2 h-6 w-6 p-0"
                        onClick={() => toggleApiKeyVisibility(provider)}
                      >
                        {showApiKeys[provider] ? (
                          <EyeOff className="h-3 w-3" />
                        ) : (
                          <Eye className="h-3 w-3" />
                        )}
                      </Button>
                    </div>
                    <Button 
                      variant="outline"
                      onClick={() => testConnection(provider)}
                    >
                      Test
                    </Button>
                  </div>
                </div>

                {/* Model Selection */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label>Model</Label>
                    <Select value={config.model}>
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        {provider === 'chatgpt' && (
                          <>
                            <SelectItem value="gpt-4-turbo">GPT-4 Turbo</SelectItem>
                            <SelectItem value="gpt-4">GPT-4</SelectItem>
                            <SelectItem value="gpt-3.5-turbo">GPT-3.5 Turbo</SelectItem>
                          </>
                        )}
                        {provider === 'claude' && (
                          <>
                            <SelectItem value="claude-3-opus">Claude 3 Opus</SelectItem>
                            <SelectItem value="claude-3-sonnet">Claude 3 Sonnet</SelectItem>
                            <SelectItem value="claude-3-haiku">Claude 3 Haiku</SelectItem>
                          </>
                        )}
                        {provider === 'perplexity' && (
                          <>
                            <SelectItem value="llama-3.1-sonar-small-128k-online">Llama 3.1 Sonar Small</SelectItem>
                            <SelectItem value="llama-3.1-sonar-large-128k-online">Llama 3.1 Sonar Large</SelectItem>
                          </>
                        )}
                      </SelectContent>
                    </Select>
                  </div>

                  <div className="space-y-2">
                    <Label>Max Tokens</Label>
                    <Input
                      type="number"
                      value={config.maxTokens}
                      onChange={(e) => 
                        setAiConfig(prev => ({
                          ...prev,
                          [provider]: { ...prev[provider], maxTokens: parseInt(e.target.value) }
                        }))
                      }
                    />
                  </div>
                </div>

                {/* Temperature Setting */}
                <div className="space-y-3">
                  <Label className="flex items-center justify-between">
                    <span>Temperature (Creativity)</span>
                    <span className="text-sm text-muted-foreground">{config.temperature}</span>
                  </Label>
                  <Slider
                    value={[config.temperature]}
                    onValueChange={([value]) => 
                      setAiConfig(prev => ({
                        ...prev,
                        [provider]: { ...prev[provider], temperature: value }
                      }))
                    }
                    max={1}
                    min={0}
                    step={0.1}
                    className="w-full"
                  />
                </div>

                {/* System Prompt */}
                <div className="space-y-2">
                  <Label>System Prompt</Label>
                  <Textarea
                    value={config.systemPrompt}
                    onChange={(e) => 
                      setAiConfig(prev => ({
                        ...prev,
                        [provider]: { ...prev[provider], systemPrompt: e.target.value }
                      }))
                    }
                    rows={3}
                    placeholder="Define how the AI should behave and respond"
                  />
                </div>

                {/* Usage Statistics */}
                {config.status === 'connected' && (
                  <div className="bg-muted/50 rounded-lg p-4 space-y-3">
                    <div className="flex items-center justify-between">
                      <span className="text-sm font-medium">Monthly Usage</span>
                      <span className="text-sm text-muted-foreground">
                        {config.currentUsage.toLocaleString()} / {config.monthlyLimit.toLocaleString()} tokens
                      </span>
                    </div>
                    <Progress value={config.usage} className="w-full" />
                    <div className="grid grid-cols-3 gap-4 text-center">
                      <div>
                        <p className="text-xs text-muted-foreground">Remaining</p>
                        <p className="text-sm font-medium">
                          {(config.monthlyLimit - config.currentUsage).toLocaleString()}
                        </p>
                      </div>
                      <div>
                        <p className="text-xs text-muted-foreground">Used Today</p>
                        <p className="text-sm font-medium">2,450</p>
                      </div>
                      <div>
                        <p className="text-xs text-muted-foreground">Avg/Day</p>
                        <p className="text-sm font-medium">3,200</p>
                      </div>
                    </div>
                  </div>
                )}
              </CardContent>
            </Card>
          ))}
        </TabsContent>

        <TabsContent value="content" className="space-y-6">
          <Card className="bg-gradient-card border-border/50 shadow-card">
            <CardHeader>
              <CardTitle className="flex items-center">
                <FileText className="mr-2 h-5 w-5 text-hexagon-secondary" />
                Content Types & Lengths
              </CardTitle>
              <CardDescription>Configure available content types and word count settings</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              {/* Content Length Settings */}
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                {[
                  { name: 'Short', range: '400-500 words', color: 'text-hexagon-accent' },
                  { name: 'Standard', range: '800-1000 words', color: 'text-hexagon-primary' },
                  { name: 'Extended', range: '1500-2000 words', color: 'text-hexagon-secondary' },
                  { name: 'Comprehensive', range: '3000-5000 words', color: 'text-hexagon-warning' }
                ].map((length) => (
                  <Card key={length.name} className="p-4 bg-muted/30">
                    <div className="text-center">
                      <h4 className={`font-semibold ${length.color}`}>{length.name}</h4>
                      <p className="text-sm text-muted-foreground mt-1">{length.range}</p>
                    </div>
                  </Card>
                ))}
              </div>

              {/* Content Types Grid */}
              <div>
                <Label className="text-base font-medium mb-4 block">Available Content Types (20)</Label>
                <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                  {contentTypes.map((type) => (
                    <div key={type} className="flex items-center space-x-2 p-2 rounded-lg bg-muted/30">
                      <Switch defaultChecked />
                      <span className="text-sm">{type}</span>
                    </div>
                  ))}
                </div>
              </div>

              {/* Language Settings */}
              <div className="space-y-4">
                <Label className="text-base font-medium">Language & Localization</Label>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label>Primary Language</Label>
                    <Select defaultValue="pl">
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="pl">Polish (Polski)</SelectItem>
                        <SelectItem value="en">English</SelectItem>
                        <SelectItem value="es">Spanish (Español)</SelectItem>
                        <SelectItem value="fr">French (Français)</SelectItem>
                        <SelectItem value="de">German (Deutsch)</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div className="space-y-2">
                    <Label>Translation Service</Label>
                    <Select defaultValue="deepl">
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="deepl">DeepL (Recommended)</SelectItem>
                        <SelectItem value="google">Google Translate</SelectItem>
                        <SelectItem value="disabled">Disabled</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="advanced" className="space-y-6">
          <Card className="bg-gradient-card border-border/50 shadow-card">
            <CardHeader>
              <CardTitle className="flex items-center">
                <Settings className="mr-2 h-5 w-5 text-hexagon-accent" />
                Advanced Configuration
              </CardTitle>
              <CardDescription>Fine-tune AI behavior and performance settings</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="space-y-4">
                  <h4 className="font-medium">Generation Settings</h4>
                  <div className="space-y-3">
                    <div className="flex items-center justify-between">
                      <Label>Auto-retry on failure</Label>
                      <Switch defaultChecked />
                    </div>
                    <div className="space-y-2">
                      <Label>Max retry attempts</Label>
                      <Input type="number" defaultValue="3" min="1" max="5" />
                    </div>
                    <div className="space-y-2">
                      <Label>Request timeout (seconds)</Label>
                      <Input type="number" defaultValue="30" min="10" max="120" />
                    </div>
                  </div>
                </div>

                <div className="space-y-4">
                  <h4 className="font-medium">Rate Limiting</h4>
                  <div className="space-y-3">
                    <div className="space-y-2">
                      <Label>Requests per minute</Label>
                      <Input type="number" defaultValue="60" min="1" max="100" />
                    </div>
                    <div className="space-y-2">
                      <Label>Concurrent requests</Label>
                      <Input type="number" defaultValue="5" min="1" max="10" />
                    </div>
                    <div className="flex items-center justify-between">
                      <Label>Respect rate limits</Label>
                      <Switch defaultChecked />
                    </div>
                  </div>
                </div>
              </div>

              <div className="space-y-4">
                <h4 className="font-medium">Content Quality Control</h4>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div className="flex items-center justify-between">
                    <Label>Enable content filtering</Label>
                    <Switch defaultChecked />
                  </div>
                  <div className="flex items-center justify-between">
                    <Label>Duplicate content detection</Label>
                    <Switch defaultChecked />
                  </div>
                  <div className="flex items-center justify-between">
                    <Label>SEO optimization</Label>
                    <Switch defaultChecked />
                  </div>
                  <div className="flex items-center justify-between">
                    <Label>Fact-checking (Perplexity)</Label>
                    <Switch />
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}