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
  Share2, 
  Facebook, 
  Instagram, 
  Twitter, 
  Linkedin,
  CheckCircle,
  AlertTriangle,
  Clock,
  Calendar,
  Users,
  TrendingUp,
  Settings,
  Plus,
  Eye,
  EyeOff,
  RefreshCw,
  Send,
  Image,
  Hash,
  Globe
} from 'lucide-react';

export function SocialMediaPage() {
  const [showTokens, setShowTokens] = useState<Record<string, boolean>>({});
  const [platforms, setPlatforms] = useState({
    facebook: {
      enabled: true,
      status: 'connected',
      accessToken: '••••••••••••EAA123',
      pageId: 'your-business-page',
      posts: 156,
      reach: 12500,
      engagement: 8.5,
      followers: 2340,
      lastPost: '2 hours ago'
    },
    instagram: {
      enabled: true,
      status: 'connected',
      accessToken: '••••••••••••IGQ456',
      businessId: 'instagram-business',
      posts: 89,
      reach: 8900,
      engagement: 12.3,
      followers: 1890,
      lastPost: '4 hours ago'
    },
    twitter: {
      enabled: true,
      status: 'connected',
      accessToken: '••••••••••••TWT789',
      username: '@yourbusiness',
      posts: 234,
      reach: 15600,
      engagement: 6.7,
      followers: 3456,
      lastPost: '1 hour ago'
    },
    linkedin: {
      enabled: false,
      status: 'disconnected',
      accessToken: '',
      companyId: '',
      posts: 0,
      reach: 0,
      engagement: 0,
      followers: 0,
      lastPost: 'Never'
    }
  });

  const [scheduledPosts] = useState([
    {
      id: 1,
      title: 'New Product Launch Announcement',
      platforms: ['facebook', 'instagram', 'twitter'],
      scheduledFor: '2024-01-15 10:00',
      status: 'scheduled',
      type: 'post'
    },
    {
      id: 2,
      title: 'Weekly Industry Report',
      platforms: ['linkedin', 'twitter'],
      scheduledFor: '2024-01-16 09:00',
      status: 'scheduled',
      type: 'article'
    },
    {
      id: 3,
      title: 'Customer Success Story',
      platforms: ['facebook', 'linkedin'],
      scheduledFor: '2024-01-17 14:30',
      status: 'draft',
      type: 'story'
    }
  ]);

  const toggleTokenVisibility = (platform: string) => {
    setShowTokens(prev => ({
      ...prev,
      [platform]: !prev[platform]
    }));
  };

  const connectPlatform = (platform: string) => {
    console.log(`Connecting to ${platform}...`);
  };

  const testConnection = (platform: string) => {
    console.log(`Testing ${platform} connection...`);
  };

  const platformIcons = {
    facebook: Facebook,
    instagram: Instagram,
    twitter: Twitter,
    linkedin: Linkedin
  };

  const platformColors = {
    facebook: 'text-blue-500',
    instagram: 'text-pink-500',
    twitter: 'text-sky-500',
    linkedin: 'text-blue-600'
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-3xl font-bold bg-gradient-primary bg-clip-text text-transparent">
            Social Media Management
          </h2>
          <p className="text-muted-foreground mt-2">
            Manage your social media accounts and automate posting across platforms
          </p>
        </div>
        <Button className="bg-gradient-primary hover:opacity-90">
          <Plus className="mr-2 h-4 w-4" />
          Schedule New Post
        </Button>
      </div>

      <Tabs defaultValue="overview" className="space-y-6">
        <TabsList className="grid grid-cols-4 w-full max-w-2xl">
          <TabsTrigger value="overview">Overview</TabsTrigger>
          <TabsTrigger value="platforms">Platforms</TabsTrigger>
          <TabsTrigger value="scheduler">Scheduler</TabsTrigger>
          <TabsTrigger value="analytics">Analytics</TabsTrigger>
        </TabsList>

        <TabsContent value="overview" className="space-y-6">
          {/* Platform Status Cards */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {Object.entries(platforms).map(([platform, config]) => {
              const Icon = platformIcons[platform as keyof typeof platformIcons];
              const colorClass = platformColors[platform as keyof typeof platformColors];
              
              return (
                <Card key={platform} className="bg-gradient-card border-border/50 shadow-card">
                  <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium capitalize">{platform}</CardTitle>
                    <Icon className={`h-4 w-4 ${colorClass}`} />
                  </CardHeader>
                  <CardContent>
                    <div className="space-y-2">
                      <div className="flex items-center justify-between">
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
                            setPlatforms(prev => ({
                              ...prev,
                              [platform]: { ...prev[platform], enabled: checked }
                            }))
                          }
                        />
                      </div>
                      {config.status === 'connected' && (
                        <>
                          <div className="text-2xl font-bold text-hexagon-primary">{config.posts}</div>
                          <p className="text-xs text-muted-foreground">
                            <Users className="inline mr-1 h-3 w-3" />
                            {config.followers} followers
                          </p>
                          <p className="text-xs text-muted-foreground">
                            Last post: {config.lastPost}
                          </p>
                        </>
                      )}
                    </div>
                  </CardContent>
                </Card>
              );
            })}
          </div>

          {/* Quick Stats */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <Card className="bg-gradient-card border-border/50 shadow-card">
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Total Reach</CardTitle>
                <TrendingUp className="h-4 w-4 text-hexagon-primary" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold text-hexagon-primary">37,000</div>
                <p className="text-xs text-muted-foreground">
                  <TrendingUp className="inline mr-1 h-3 w-3" />
                  +15% from last week
                </p>
              </CardContent>
            </Card>

            <Card className="bg-gradient-card border-border/50 shadow-card">
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Engagement Rate</CardTitle>
                <Users className="h-4 w-4 text-hexagon-secondary" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold text-hexagon-secondary">9.2%</div>
                <p className="text-xs text-muted-foreground">
                  <TrendingUp className="inline mr-1 h-3 w-3" />
                  Above industry average
                </p>
              </CardContent>
            </Card>

            <Card className="bg-gradient-card border-border/50 shadow-card">
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Scheduled Posts</CardTitle>
                <Clock className="h-4 w-4 text-hexagon-accent" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold text-hexagon-accent">{scheduledPosts.length}</div>
                <p className="text-xs text-muted-foreground">
                  <Calendar className="inline mr-1 h-3 w-3" />
                  Next: Today 3:00 PM
                </p>
              </CardContent>
            </Card>
          </div>

          {/* Recent Activity */}
          <Card className="bg-gradient-card border-border/50 shadow-card">
            <CardHeader>
              <CardTitle className="flex items-center">
                <Share2 className="mr-2 h-5 w-5 text-hexagon-primary" />
                Recent Social Media Activity
              </CardTitle>
              <CardDescription>Latest posts and engagement across all platforms</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              {[
                { platform: 'Facebook', action: 'Posted: "New Product Launch"', time: '2 hours ago', engagement: '45 likes, 8 comments' },
                { platform: 'Instagram', action: 'Shared carousel with 4 images', time: '4 hours ago', engagement: '89 likes, 12 comments' },
                { platform: 'Twitter', action: 'Thread about industry trends', time: '1 hour ago', engagement: '23 retweets, 67 likes' },
                { platform: 'LinkedIn', action: 'Article: "Digital Transformation"', time: '1 day ago', engagement: '156 views, 15 likes' }
              ].map((activity, index) => (
                <div key={index} className="flex items-center space-x-3 p-3 rounded-lg bg-muted/50">
                  <div className="h-2 w-2 rounded-full bg-hexagon-success" />
                  <div className="flex-1">
                    <div className="flex items-center space-x-2">
                      <span className="font-medium text-sm">{activity.platform}</span>
                      <span className="text-sm text-muted-foreground">•</span>
                      <span className="text-sm">{activity.action}</span>
                    </div>
                    <div className="flex items-center space-x-4 text-xs text-muted-foreground mt-1">
                      <span>{activity.time}</span>
                      <span>{activity.engagement}</span>
                    </div>
                  </div>
                </div>
              ))}
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="platforms" className="space-y-6">
          {Object.entries(platforms).map(([platform, config]) => {
            const Icon = platformIcons[platform as keyof typeof platformIcons];
            const colorClass = platformColors[platform as keyof typeof platformColors];
            
            return (
              <Card key={platform} className="bg-gradient-card border-border/50 shadow-card">
                <CardHeader>
                  <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-3">
                      <Icon className={`h-6 w-6 ${colorClass}`} />
                      <div>
                        <CardTitle className="capitalize">{platform}</CardTitle>
                        <CardDescription>
                          {platform === 'facebook' && 'Connect your Facebook Page for automated posting'}
                          {platform === 'instagram' && 'Instagram Business account integration'}
                          {platform === 'twitter' && 'Twitter/X account for tweets and threads'}
                          {platform === 'linkedin' && 'LinkedIn Company Page management'}
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
                          setPlatforms(prev => ({
                            ...prev,
                            [platform]: { ...prev[platform], enabled: checked }
                          }))
                        }
                      />
                    </div>
                  </div>
                </CardHeader>
                <CardContent className="space-y-6">
                  {config.status === 'disconnected' ? (
                    <div className="text-center py-8">
                      <p className="text-muted-foreground mb-4">
                        Connect your {platform} account to start automating posts
                      </p>
                      <Button 
                        onClick={() => connectPlatform(platform)}
                        className="bg-gradient-primary hover:opacity-90"
                      >
                        <Icon className="mr-2 h-4 w-4" />
                        Connect {platform}
                      </Button>
                    </div>
                  ) : (
                    <>
                      {/* Connection Details */}
                      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="space-y-2">
                          <Label>Access Token</Label>
                          <div className="flex space-x-2">
                            <div className="relative flex-1">
                              <Input
                                type={showTokens[platform] ? 'text' : 'password'}
                                value={config.accessToken}
                                readOnly
                              />
                              <Button
                                variant="ghost"
                                size="sm"
                                className="absolute right-2 top-1/2 -translate-y-1/2 h-6 w-6 p-0"
                                onClick={() => toggleTokenVisibility(platform)}
                              >
                                {showTokens[platform] ? (
                                  <EyeOff className="h-3 w-3" />
                                ) : (
                                  <Eye className="h-3 w-3" />
                                )}
                              </Button>
                            </div>
                            <Button 
                              variant="outline"
                              onClick={() => testConnection(platform)}
                            >
                              Test
                            </Button>
                          </div>
                        </div>

                        <div className="space-y-2">
                          <Label>Account ID</Label>
                          <Input
                            value={platform === 'facebook' ? (config as any).pageId : 
                                   platform === 'instagram' ? (config as any).businessId :
                                   platform === 'twitter' ? (config as any).username :
                                   (config as any).companyId || ''}
                            readOnly
                          />
                        </div>
                      </div>

                      {/* Platform Settings */}
                      <div className="space-y-4">
                        <h4 className="font-medium">Posting Settings</h4>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                          <div className="flex items-center justify-between">
                            <Label>Auto-post new articles</Label>
                            <Switch defaultChecked />
                          </div>
                          <div className="flex items-center justify-between">
                            <Label>Include featured image</Label>
                            <Switch defaultChecked />
                          </div>
                          <div className="flex items-center justify-between">
                            <Label>Add hashtags automatically</Label>
                            <Switch defaultChecked />
                          </div>
                          <div className="flex items-center justify-between">
                            <Label>Schedule for optimal times</Label>
                            <Switch defaultChecked />
                          </div>
                        </div>
                      </div>

                      {/* Post Template */}
                      <div className="space-y-2">
                        <Label>Post Template</Label>
                        <Textarea
                          placeholder={`Default template for ${platform} posts...`}
                          defaultValue={`🎉 New article published: {{title}}

{{excerpt}}

Read more: {{link}}

#automation #content #${platform}`}
                          rows={4}
                        />
                      </div>

                      {/* Performance Metrics */}
                      <div className="bg-muted/50 rounded-lg p-4 space-y-3">
                        <h4 className="font-medium">Performance Metrics</h4>
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                          <div>
                            <p className="text-2xl font-bold text-hexagon-primary">{config.posts}</p>
                            <p className="text-xs text-muted-foreground">Posts</p>
                          </div>
                          <div>
                            <p className="text-2xl font-bold text-hexagon-secondary">{config.reach.toLocaleString()}</p>
                            <p className="text-xs text-muted-foreground">Reach</p>
                          </div>
                          <div>
                            <p className="text-2xl font-bold text-hexagon-accent">{config.engagement}%</p>
                            <p className="text-xs text-muted-foreground">Engagement</p>
                          </div>
                          <div>
                            <p className="text-2xl font-bold text-hexagon-warning">{config.followers}</p>
                            <p className="text-xs text-muted-foreground">Followers</p>
                          </div>
                        </div>
                      </div>
                    </>
                  )}
                </CardContent>
              </Card>
            );
          })}
        </TabsContent>

        <TabsContent value="scheduler" className="space-y-6">
          <Card className="bg-gradient-card border-border/50 shadow-card">
            <CardHeader>
              <CardTitle className="flex items-center">
                <Calendar className="mr-2 h-5 w-5 text-hexagon-accent" />
                Scheduled Posts
              </CardTitle>
              <CardDescription>Manage and monitor your scheduled social media posts</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              {scheduledPosts.map((post) => (
                <div key={post.id} className="flex items-center justify-between p-4 rounded-lg bg-muted/50">
                  <div className="flex-1">
                    <h4 className="font-medium">{post.title}</h4>
                    <div className="flex items-center space-x-4 text-sm text-muted-foreground mt-2">
                      <span className="flex items-center">
                        <Clock className="mr-1 h-3 w-3" />
                        {post.scheduledFor}
                      </span>
                      <div className="flex items-center space-x-1">
                        {post.platforms.map(platform => {
                          const Icon = platformIcons[platform as keyof typeof platformIcons];
                          return <Icon key={platform} className="h-3 w-3" />;
                        })}
                      </div>
                      <Badge variant={post.status === 'scheduled' ? 'default' : 'secondary'}>
                        {post.status}
                      </Badge>
                    </div>
                  </div>
                  <div className="flex items-center space-x-2">
                    <Button variant="outline" size="sm">
                      <Eye className="h-4 w-4" />
                    </Button>
                    <Button variant="outline" size="sm">
                      <Settings className="h-4 w-4" />
                    </Button>
                    <Button size="sm" className="bg-gradient-primary hover:opacity-90">
                      <Send className="h-4 w-4" />
                    </Button>
                  </div>
                </div>
              ))}
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="analytics" className="space-y-6">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {Object.entries(platforms).filter(([, config]) => config.status === 'connected').map(([platform, config]) => {
              const Icon = platformIcons[platform as keyof typeof platformIcons];
              const colorClass = platformColors[platform as keyof typeof platformColors];
              
              return (
                <Card key={platform} className="bg-gradient-card border-border/50 shadow-card">
                  <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium capitalize">{platform}</CardTitle>
                    <Icon className={`h-4 w-4 ${colorClass}`} />
                  </CardHeader>
                  <CardContent className="space-y-4">
                    <div className="space-y-2">
                      <div className="flex justify-between">
                        <span className="text-xs">Reach</span>
                        <span className="text-xs font-medium">{config.reach.toLocaleString()}</span>
                      </div>
                      <Progress value={(config.reach / 20000) * 100} />
                    </div>
                    <div className="space-y-2">
                      <div className="flex justify-between">
                        <span className="text-xs">Engagement</span>
                        <span className="text-xs font-medium">{config.engagement}%</span>
                      </div>
                      <Progress value={config.engagement * 10} />
                    </div>
                    <div className="text-center">
                      <p className="text-xl font-bold">{config.followers}</p>
                      <p className="text-xs text-muted-foreground">Followers</p>
                    </div>
                  </CardContent>
                </Card>
              );
            })}
          </div>
        </TabsContent>
      </Tabs>
    </div>
  );
}