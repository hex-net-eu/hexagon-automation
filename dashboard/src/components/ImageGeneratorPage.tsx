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
import { Progress } from '@/components/ui/progress';
import { 
  Image, 
  Palette, 
  Wand2,
  Download,
  Upload,
  Settings,
  Eye,
  Copy,
  Trash2,
  RefreshCw,
  Sparkles,
  Grid3X3,
  Square,
  RectangleHorizontal,
  Monitor,
  Smartphone,
  Camera,
  Zap,
  Clock,
  CheckCircle,
  AlertTriangle
} from 'lucide-react';

export function ImageGeneratorPage() {
  const [generators] = useState({
    dalle: {
      enabled: true,
      status: 'connected',
      model: 'dall-e-3',
      usage: 75,
      monthlyLimit: 1000,
      currentUsage: 750
    },
    midjourney: {
      enabled: true,
      status: 'connected',
      model: 'midjourney-v6',
      usage: 45,
      monthlyLimit: 500,
      currentUsage: 225
    },
    stable: {
      enabled: false,
      status: 'disconnected',
      model: 'stable-diffusion-xl',
      usage: 0,
      monthlyLimit: 0,
      currentUsage: 0
    }
  });

  const [aspectRatios] = useState([
    { name: 'Square', ratio: '1:1', size: '1024x1024', icon: Square, usage: 'Instagram posts, profile pictures' },
    { name: 'Landscape', ratio: '16:9', size: '1792x1024', icon: Monitor, usage: 'YouTube thumbnails, banners' },
    { name: 'Portrait', ratio: '9:16', size: '1024x1792', icon: Smartphone, usage: 'Instagram stories, TikTok' },
    { name: 'Wide', ratio: '21:9', size: '1344x640', icon: RectangleHorizontal, usage: 'Website headers, covers' }
  ]);

  const [generatedImages] = useState([
    {
      id: 1,
      prompt: 'Professional business team working on automation technology',
      model: 'DALL-E 3',
      size: '1024x1024',
      watermark: true,
      createdAt: '2024-01-10 14:30',
      status: 'completed',
      downloadCount: 12
    },
    {
      id: 2,
      prompt: 'Modern hexagonal pattern background with blue gradient',
      model: 'Midjourney',
      size: '1792x1024',
      watermark: true,
      createdAt: '2024-01-10 12:15',
      status: 'completed',
      downloadCount: 8
    },
    {
      id: 3,
      prompt: 'AI robot writing content on laptop, futuristic office',
      model: 'DALL-E 3',
      size: '1024x1792',
      watermark: true,
      createdAt: '2024-01-10 09:45',
      status: 'processing',
      downloadCount: 0
    }
  ]);

  const [watermarkSettings, setWatermarkSettings] = useState({
    enabled: true,
    position: 'bottom-right',
    opacity: 0.7,
    text: 'HexagonAutomation.com',
    logoEnabled: true
  });

  const generateImage = () => {
    console.log('Generating image...');
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-3xl font-bold bg-gradient-primary bg-clip-text text-transparent">
            AI Image Generator
          </h2>
          <p className="text-muted-foreground mt-2">
            Create stunning visuals for your content using AI-powered image generation
          </p>
        </div>
        <Button className="bg-gradient-primary hover:opacity-90">
          <Sparkles className="mr-2 h-4 w-4" />
          Generate New Image
        </Button>
      </div>

      <Tabs defaultValue="generate" className="space-y-6">
        <TabsList className="grid grid-cols-4 w-full max-w-2xl">
          <TabsTrigger value="generate">Generate</TabsTrigger>
          <TabsTrigger value="gallery">Gallery</TabsTrigger>
          <TabsTrigger value="settings">Settings</TabsTrigger>
          <TabsTrigger value="watermark">Watermark</TabsTrigger>
        </TabsList>

        <TabsContent value="generate" className="space-y-6">
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {/* Generation Panel */}
            <div className="lg:col-span-2 space-y-6">
              <Card className="bg-gradient-card border-border/50 shadow-card">
                <CardHeader>
                  <CardTitle className="flex items-center">
                    <Wand2 className="mr-2 h-5 w-5 text-hexagon-primary" />
                    Image Generation
                  </CardTitle>
                  <CardDescription>Describe the image you want to create</CardDescription>
                </CardHeader>
                <CardContent className="space-y-6">
                  {/* Prompt Input */}
                  <div className="space-y-2">
                    <Label>Image Prompt</Label>
                    <Textarea
                      placeholder="Describe the image you want to generate in detail..."
                      rows={4}
                      className="w-full"
                    />
                  </div>

                  {/* Generation Settings */}
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div className="space-y-2">
                      <Label>AI Model</Label>
                      <Select defaultValue="dalle">
                        <SelectTrigger>
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="dalle">DALL-E 3 (High Quality)</SelectItem>
                          <SelectItem value="midjourney">Midjourney v6 (Artistic)</SelectItem>
                          <SelectItem value="stable" disabled>Stable Diffusion (Offline)</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>

                    <div className="space-y-2">
                      <Label>Style</Label>
                      <Select defaultValue="photorealistic">
                        <SelectTrigger>
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="photorealistic">Photorealistic</SelectItem>
                          <SelectItem value="artistic">Artistic</SelectItem>
                          <SelectItem value="illustration">Illustration</SelectItem>
                          <SelectItem value="cartoon">Cartoon</SelectItem>
                          <SelectItem value="minimalist">Minimalist</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                  </div>

                  {/* Aspect Ratios */}
                  <div className="space-y-3">
                    <Label>Aspect Ratio</Label>
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
                      {aspectRatios.map((ratio) => (
                        <Card key={ratio.ratio} className="p-3 cursor-pointer hover:bg-muted/50 transition-colors">
                          <div className="text-center space-y-2">
                            <ratio.icon className="h-6 w-6 mx-auto text-hexagon-primary" />
                            <div>
                              <p className="font-medium text-sm">{ratio.name}</p>
                              <p className="text-xs text-muted-foreground">{ratio.ratio}</p>
                              <p className="text-xs text-muted-foreground">{ratio.size}</p>
                            </div>
                          </div>
                        </Card>
                      ))}
                    </div>
                  </div>

                  {/* Advanced Settings */}
                  <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div className="flex items-center justify-between">
                      <Label>Apply watermark</Label>
                      <Switch defaultChecked />
                    </div>
                    <div className="flex items-center justify-between">
                      <Label>High quality</Label>
                      <Switch defaultChecked />
                    </div>
                    <div className="flex items-center justify-between">
                      <Label>Generate variations</Label>
                      <Switch />
                    </div>
                  </div>

                  <Button 
                    onClick={generateImage}
                    className="w-full bg-gradient-primary hover:opacity-90"
                    size="lg"
                  >
                    <Sparkles className="mr-2 h-5 w-5" />
                    Generate Image
                  </Button>
                </CardContent>
              </Card>
            </div>

            {/* AI Models Status */}
            <div className="space-y-6">
              <Card className="bg-gradient-card border-border/50 shadow-card">
                <CardHeader>
                  <CardTitle className="flex items-center">
                    <Zap className="mr-2 h-5 w-5 text-hexagon-secondary" />
                    AI Models Status
                  </CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  {Object.entries(generators).map(([model, config]) => (
                    <div key={model} className="space-y-3">
                      <div className="flex items-center justify-between">
                        <div className="flex items-center space-x-2">
                          <div className={`h-2 w-2 rounded-full ${
                            config.status === 'connected' ? 'bg-hexagon-success' : 'bg-hexagon-danger'
                          }`} />
                          <span className="font-medium capitalize">{model}</span>
                        </div>
                        <Badge variant={config.status === 'connected' ? 'default' : 'destructive'}>
                          {config.status}
                        </Badge>
                      </div>
                      {config.status === 'connected' && (
                        <div className="space-y-2">
                          <div className="flex justify-between text-sm">
                            <span>Usage</span>
                            <span>{config.currentUsage}/{config.monthlyLimit}</span>
                          </div>
                          <Progress value={config.usage} />
                        </div>
                      )}
                    </div>
                  ))}
                </CardContent>
              </Card>

              {/* Quick Templates */}
              <Card className="bg-gradient-card border-border/50 shadow-card">
                <CardHeader>
                  <CardTitle className="flex items-center">
                    <Palette className="mr-2 h-5 w-5 text-hexagon-accent" />
                    Quick Templates
                  </CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                  {[
                    'Professional business meeting',
                    'Modern technology workspace',
                    'Social media post template',
                    'Blog article header image',
                    'Product showcase photo',
                    'Abstract background pattern'
                  ].map((template, index) => (
                    <Button
                      key={index}
                      variant="outline"
                      size="sm"
                      className="w-full justify-start text-left"
                    >
                      {template}
                    </Button>
                  ))}
                </CardContent>
              </Card>
            </div>
          </div>
        </TabsContent>

        <TabsContent value="gallery" className="space-y-6">
          <Card className="bg-gradient-card border-border/50 shadow-card">
            <CardHeader>
              <CardTitle className="flex items-center">
                <Grid3X3 className="mr-2 h-5 w-5 text-hexagon-primary" />
                Generated Images
              </CardTitle>
              <CardDescription>Browse and manage your AI-generated images</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              {generatedImages.map((image) => (
                <div key={image.id} className="flex items-center justify-between p-4 rounded-lg bg-muted/50">
                  <div className="flex items-center space-x-4">
                    <div className="w-16 h-16 bg-gradient-hexagon rounded-lg flex items-center justify-center">
                      <Image className="h-8 w-8 text-foreground" />
                    </div>
                    <div className="flex-1">
                      <h4 className="font-medium">{image.prompt}</h4>
                      <div className="flex items-center space-x-4 text-sm text-muted-foreground mt-1">
                        <span>{image.model}</span>
                        <span>•</span>
                        <span>{image.size}</span>
                        <span>•</span>
                        <span>{image.createdAt}</span>
                        <Badge variant={image.status === 'completed' ? 'default' : 'secondary'}>
                          {image.status === 'completed' ? (
                            <CheckCircle className="mr-1 h-3 w-3" />
                          ) : (
                            <Clock className="mr-1 h-3 w-3" />
                          )}
                          {image.status}
                        </Badge>
                      </div>
                    </div>
                  </div>
                  <div className="flex items-center space-x-2">
                    <span className="text-sm text-muted-foreground">
                      {image.downloadCount} downloads
                    </span>
                    <Button variant="outline" size="sm">
                      <Eye className="h-4 w-4" />
                    </Button>
                    <Button variant="outline" size="sm">
                      <Download className="h-4 w-4" />
                    </Button>
                    <Button variant="outline" size="sm">
                      <Copy className="h-4 w-4" />
                    </Button>
                    <Button variant="outline" size="sm">
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </div>
                </div>
              ))}
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="settings" className="space-y-6">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {Object.entries(generators).map(([model, config]) => (
              <Card key={model} className="bg-gradient-card border-border/50 shadow-card">
                <CardHeader>
                  <CardTitle className="capitalize flex items-center justify-between">
                    {model}
                    <Switch checked={config.enabled} />
                  </CardTitle>
                  <CardDescription>Configure {model} settings and preferences</CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="space-y-2">
                    <Label>API Key</Label>
                    <Input type="password" value="••••••••••••" />
                  </div>
                  <div className="space-y-2">
                    <Label>Model Version</Label>
                    <Select value={config.model}>
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        {model === 'dalle' && (
                          <>
                            <SelectItem value="dall-e-3">DALL-E 3</SelectItem>
                            <SelectItem value="dall-e-2">DALL-E 2</SelectItem>
                          </>
                        )}
                        {model === 'midjourney' && (
                          <>
                            <SelectItem value="midjourney-v6">Midjourney v6</SelectItem>
                            <SelectItem value="midjourney-v5">Midjourney v5</SelectItem>
                          </>
                        )}
                        {model === 'stable' && (
                          <>
                            <SelectItem value="stable-diffusion-xl">Stable Diffusion XL</SelectItem>
                            <SelectItem value="stable-diffusion-2">Stable Diffusion 2</SelectItem>
                          </>
                        )}
                      </SelectContent>
                    </Select>
                  </div>
                  <div className="space-y-2">
                    <Label>Quality Level</Label>
                    <Select defaultValue="high">
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="standard">Standard</SelectItem>
                        <SelectItem value="high">High</SelectItem>
                        <SelectItem value="ultra">Ultra (Premium)</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  {config.status === 'connected' && (
                    <div className="bg-muted/50 rounded-lg p-3 space-y-2">
                      <div className="flex justify-between text-sm">
                        <span>Monthly Usage</span>
                        <span>{config.currentUsage}/{config.monthlyLimit}</span>
                      </div>
                      <Progress value={config.usage} />
                    </div>
                  )}
                </CardContent>
              </Card>
            ))}
          </div>
        </TabsContent>

        <TabsContent value="watermark" className="space-y-6">
          <Card className="bg-gradient-card border-border/50 shadow-card">
            <CardHeader>
              <CardTitle className="flex items-center">
                <Camera className="mr-2 h-5 w-5 text-hexagon-accent" />
                Watermark Settings
              </CardTitle>
              <CardDescription>Customize watermark appearance and positioning</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="flex items-center justify-between">
                <Label>Enable Watermark</Label>
                <Switch 
                  checked={watermarkSettings.enabled}
                  onCheckedChange={(checked) => 
                    setWatermarkSettings(prev => ({ ...prev, enabled: checked }))
                  }
                />
              </div>

              {watermarkSettings.enabled && (
                <>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div className="space-y-2">
                      <Label>Watermark Text</Label>
                      <Input
                        value={watermarkSettings.text}
                        onChange={(e) => 
                          setWatermarkSettings(prev => ({ ...prev, text: e.target.value }))
                        }
                      />
                    </div>
                    <div className="space-y-2">
                      <Label>Position</Label>
                      <Select 
                        value={watermarkSettings.position}
                        onValueChange={(value) => 
                          setWatermarkSettings(prev => ({ ...prev, position: value }))
                        }
                      >
                        <SelectTrigger>
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="top-left">Top Left</SelectItem>
                          <SelectItem value="top-right">Top Right</SelectItem>
                          <SelectItem value="bottom-left">Bottom Left</SelectItem>
                          <SelectItem value="bottom-right">Bottom Right</SelectItem>
                          <SelectItem value="center">Center</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                  </div>

                  <div className="space-y-3">
                    <Label className="flex items-center justify-between">
                      <span>Opacity</span>
                      <span className="text-sm text-muted-foreground">{Math.round(watermarkSettings.opacity * 100)}%</span>
                    </Label>
                    <input
                      type="range"
                      min="0"
                      max="1"
                      step="0.1"
                      value={watermarkSettings.opacity}
                      onChange={(e) => 
                        setWatermarkSettings(prev => ({ ...prev, opacity: parseFloat(e.target.value) }))
                      }
                      className="w-full"
                    />
                  </div>

                  <div className="flex items-center justify-between">
                    <Label>Include Logo</Label>
                    <Switch 
                      checked={watermarkSettings.logoEnabled}
                      onCheckedChange={(checked) => 
                        setWatermarkSettings(prev => ({ ...prev, logoEnabled: checked }))
                      }
                    />
                  </div>

                  {watermarkSettings.logoEnabled && (
                    <div className="space-y-2">
                      <Label>Logo Upload</Label>
                      <div className="border-2 border-dashed border-border rounded-lg p-6 text-center">
                        <Upload className="h-8 w-8 mx-auto text-muted-foreground mb-2" />
                        <p className="text-sm text-muted-foreground">
                          Drop your logo here or click to upload
                        </p>
                        <Button variant="outline" size="sm" className="mt-2">
                          Choose File
                        </Button>
                      </div>
                    </div>
                  )}

                  <div className="bg-muted/50 rounded-lg p-4">
                    <h4 className="font-medium mb-2">Preview</h4>
                    <div className="aspect-video bg-gradient-hexagon rounded-lg flex items-center justify-center relative">
                      <span className="text-muted-foreground">Image Preview</span>
                      <div 
                        className={`absolute ${
                          watermarkSettings.position === 'top-left' ? 'top-2 left-2' :
                          watermarkSettings.position === 'top-right' ? 'top-2 right-2' :
                          watermarkSettings.position === 'bottom-left' ? 'bottom-2 left-2' :
                          watermarkSettings.position === 'bottom-right' ? 'bottom-2 right-2' :
                          'top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2'
                        }`}
                        style={{ opacity: watermarkSettings.opacity }}
                      >
                        <span className="text-xs font-medium bg-black/50 text-white px-2 py-1 rounded">
                          {watermarkSettings.text}
                        </span>
                      </div>
                    </div>
                  </div>
                </>
              )}
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}