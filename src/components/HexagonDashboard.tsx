import { useState } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Progress } from '@/components/ui/progress';
import { 
  Bot, 
  Share2, 
  Image, 
  Settings, 
  HelpCircle, 
  Shield, 
  Activity,
  TrendingUp,
  Users,
  FileText,
  Mail,
  Globe,
  Zap,
  Clock,
  CheckCircle,
  AlertTriangle,
  Eye,
  Rss,
  ExternalLink,
  ArrowRight,
  Plus
} from 'lucide-react';
import hexagonLogo from '@/assets/hexagon-logo.png';

interface DashboardProps {
  currentPage: string;
  onPageChange: (page: string) => void;
}

export function HexagonDashboard({ currentPage, onPageChange }: DashboardProps) {
  const [stats] = useState({
    postsGenerated: 1247,
    socialPosts: 892,
    imagesCreated: 345,
    emailsProcessed: 2156,
    activeConnections: 8,
    automationsSaved: '156h',
  });

  const [aiProviders] = useState([
    { name: 'ChatGPT', status: 'connected', usage: 85 },
    { name: 'Claude', status: 'connected', usage: 45 },
    { name: 'Perplexity', status: 'disconnected', usage: 0 },
  ]);

  const [recentActivity] = useState([
    { type: 'post', title: 'AI-Generated Article Published', time: '2 min ago', status: 'success' },
    { type: 'social', title: 'Posted to Facebook & Instagram', time: '15 min ago', status: 'success' },
    { type: 'image', title: 'Generated 3 social media images', time: '1h ago', status: 'success' },
    { type: 'email', title: 'Processed 12 new emails', time: '2h ago', status: 'success' },
    { type: 'error', title: 'LinkedIn posting failed - check credentials', time: '3h ago', status: 'error' },
    { type: 'rss', title: 'Updated RSS feeds from 8 sources', time: '4h ago', status: 'success' },
  ]);

  const [recentRSSSources] = useState([
    { title: 'TechCrunch AI News', url: 'techcrunch.com/ai', lastUpdate: '5 min ago', status: 'active', articles: 12 },
    { title: 'Wired Technology', url: 'wired.com/tech', lastUpdate: '1h ago', status: 'active', articles: 8 },
    { title: 'MIT Tech Review', url: 'technologyreview.com', lastUpdate: '2h ago', status: 'active', articles: 5 },
    { title: 'VentureBeat AI', url: 'venturebeat.com/ai', lastUpdate: '4h ago', status: 'inactive', articles: 0 },
    { title: 'AI News Daily', url: 'ainews.com', lastUpdate: '6h ago', status: 'active', articles: 15 },
  ]);

  return (
    <div className="min-h-screen bg-background text-foreground">
      {/* Header */}
      <div className="border-b bg-gradient-card">
        <div className="flex h-16 items-center px-6">
          <div className="flex items-center space-x-4">
            <img src={hexagonLogo} alt="Hexagon Automation" className="h-8 w-8" />
            <div>
              <h1 className="text-xl font-bold bg-gradient-primary bg-clip-text text-transparent">
                Hexagon Automation
              </h1>
              <p className="text-sm text-muted-foreground">v2.0.0</p>
            </div>
          </div>
          <div className="ml-auto flex items-center space-x-4">
            <Badge variant="outline" className="bg-hexagon-success/10 text-hexagon-success border-hexagon-success/20">
              <Shield className="mr-1 h-3 w-3" />
              Safe Mode: ON
            </Badge>
            <Button
              variant="outline"
              size="sm"
              onClick={() => onPageChange('help')}
              className="border-muted-foreground/20"
            >
              <HelpCircle className="mr-2 h-4 w-4" />
              Help
            </Button>
          </div>
        </div>
      </div>

      <div className="flex">
        {/* Sidebar */}
        <div className="w-64 border-r bg-gradient-card min-h-screen">
          <nav className="p-4 space-y-2">
            {[
              { id: 'dashboard', icon: Activity, label: 'Dashboard' },
              { id: 'ai-settings', icon: Bot, label: 'AI Settings' },
              { id: 'social-media', icon: Share2, label: 'Social Media' },
              { id: 'image-generator', icon: Image, label: 'Image Generator' },
              { id: 'debug-logger', icon: Eye, label: 'Debug Logger' },
              { id: 'settings', icon: Settings, label: 'Settings' },
            ].map((item) => (
              <Button
                key={item.id}
                variant={currentPage === item.id ? 'default' : 'ghost'}
                className={`w-full justify-start ${
                  currentPage === item.id 
                    ? 'bg-primary text-primary-foreground shadow-hexagon' 
                    : 'hover:bg-muted'
                }`}
                onClick={() => onPageChange(item.id)}
              >
                <item.icon className="mr-3 h-4 w-4" />
                {item.label}
              </Button>
            ))}
          </nav>
        </div>

        {/* Main Content */}
        <div className="flex-1 p-6">
          {currentPage === 'dashboard' && (
            <div className="space-y-6">
              {/* Overview Stats */}
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <Card className="bg-gradient-card border-border/50 shadow-card">
                  <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium">Posts Generated</CardTitle>
                    <FileText className="h-4 w-4 text-hexagon-primary" />
                  </CardHeader>
                  <CardContent>
                    <div className="text-2xl font-bold text-hexagon-primary">{stats.postsGenerated}</div>
                    <p className="text-xs text-muted-foreground">
                      <TrendingUp className="inline mr-1 h-3 w-3" />
                      +12% from last month
                    </p>
                  </CardContent>
                </Card>

                <Card className="bg-gradient-card border-border/50 shadow-card">
                  <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium">Social Posts</CardTitle>
                    <Share2 className="h-4 w-4 text-hexagon-secondary" />
                  </CardHeader>
                  <CardContent>
                    <div className="text-2xl font-bold text-hexagon-secondary">{stats.socialPosts}</div>
                    <p className="text-xs text-muted-foreground">
                      <Users className="inline mr-1 h-3 w-3" />
                      Across 4 platforms
                    </p>
                  </CardContent>
                </Card>

                <Card className="bg-gradient-card border-border/50 shadow-card">
                  <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium">Images Created</CardTitle>
                    <Image className="h-4 w-4 text-hexagon-accent" />
                  </CardHeader>
                  <CardContent>
                    <div className="text-2xl font-bold text-hexagon-accent">{stats.imagesCreated}</div>
                    <p className="text-xs text-muted-foreground">
                      <Zap className="inline mr-1 h-3 w-3" />
                      AI-powered generation
                    </p>
                  </CardContent>
                </Card>

                <Card className="bg-gradient-card border-border/50 shadow-card">
                  <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium">Emails Processed</CardTitle>
                    <Mail className="h-4 w-4 text-hexagon-warning" />
                  </CardHeader>
                  <CardContent>
                    <div className="text-2xl font-bold text-hexagon-warning">{stats.emailsProcessed}</div>
                    <p className="text-xs text-muted-foreground">
                      <Globe className="inline mr-1 h-3 w-3" />
                      Auto content extraction
                    </p>
                  </CardContent>
                </Card>

                <Card className="bg-gradient-card border-border/50 shadow-card">
                  <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium">Active Connections</CardTitle>
                    <Activity className="h-4 w-4 text-hexagon-success" />
                  </CardHeader>
                  <CardContent>
                    <div className="text-2xl font-bold text-hexagon-success">{stats.activeConnections}</div>
                    <p className="text-xs text-muted-foreground">
                      <CheckCircle className="inline mr-1 h-3 w-3" />
                      All systems operational
                    </p>
                  </CardContent>
                </Card>

                <Card className="bg-gradient-card border-border/50 shadow-card">
                  <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium">Time Saved</CardTitle>
                    <Clock className="h-4 w-4 text-primary" />
                  </CardHeader>
                  <CardContent>
                    <div className="text-2xl font-bold text-primary">{stats.automationsSaved}</div>
                    <p className="text-xs text-muted-foreground">
                      <TrendingUp className="inline mr-1 h-3 w-3" />
                      This month alone
                    </p>
                  </CardContent>
                </Card>
              </div>

              {/* AI Provider Status */}
              <Card className="bg-gradient-card border-border/50 shadow-card">
                <CardHeader>
                  <CardTitle className="flex items-center">
                    <Bot className="mr-2 h-5 w-5 text-hexagon-primary" />
                    AI Provider Status
                  </CardTitle>
                  <CardDescription>Monitor your connected AI services</CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                  {aiProviders.map((provider) => (
                    <div key={provider.name} className="flex items-center justify-between">
                      <div className="flex items-center space-x-3">
                        <div className={`h-3 w-3 rounded-full ${
                          provider.status === 'connected' ? 'bg-hexagon-success' : 'bg-hexagon-danger'
                        }`} />
                        <span className="font-medium">{provider.name}</span>
                        <Badge variant={provider.status === 'connected' ? 'default' : 'destructive'}>
                          {provider.status}
                        </Badge>
                      </div>
                      <div className="flex items-center space-x-3">
                        <span className="text-sm text-muted-foreground">{provider.usage}% usage</span>
                        <Progress value={provider.usage} className="w-20" />
                      </div>
                    </div>
                  ))}
                </CardContent>
              </Card>

              {/* Recent Activity and RSS Sources */}
              <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {/* Recent Activity */}
                <Card className="bg-gradient-card border-border/50 shadow-card">
                  <CardHeader>
                    <CardTitle className="flex items-center">
                      <Activity className="mr-2 h-5 w-5 text-hexagon-secondary" />
                      Recent Activity
                    </CardTitle>
                    <CardDescription>Latest automation activities</CardDescription>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    {recentActivity.map((activity, index) => (
                      <div key={index} className="flex items-center space-x-3 p-3 rounded-lg bg-muted/50">
                        {activity.status === 'success' ? (
                          <CheckCircle className="h-4 w-4 text-hexagon-success" />
                        ) : (
                          <AlertTriangle className="h-4 w-4 text-hexagon-danger" />
                        )}
                        <div className="flex-1">
                          <p className="text-sm font-medium">{activity.title}</p>
                          <p className="text-xs text-muted-foreground">{activity.time}</p>
                        </div>
                      </div>
                    ))}
                    <div className="pt-2 border-t border-border/50">
                      <Button variant="ghost" size="sm" className="w-full justify-between text-muted-foreground hover:text-foreground">
                        View All Activity
                        <ArrowRight className="h-4 w-4" />
                      </Button>
                    </div>
                  </CardContent>
                </Card>

                {/* Recent RSS Sources */}
                <Card className="bg-gradient-card border-border/50 shadow-card">
                  <CardHeader>
                    <CardTitle className="flex items-center">
                      <Rss className="mr-2 h-5 w-5 text-hexagon-accent" />
                      Recent RSS Sources
                    </CardTitle>
                    <CardDescription>Active content sources monitoring</CardDescription>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    {recentRSSSources.map((source, index) => (
                      <div key={index} className="flex items-center space-x-3 p-3 rounded-lg bg-muted/50 hover:bg-muted/70 transition-colors">
                        <div className={`h-3 w-3 rounded-full ${
                          source.status === 'active' ? 'bg-hexagon-success' : 'bg-hexagon-warning'
                        }`} />
                        <div className="flex-1">
                          <div className="flex items-center justify-between">
                            <p className="text-sm font-medium">{source.title}</p>
                            <ExternalLink className="h-3 w-3 text-muted-foreground" />
                          </div>
                          <p className="text-xs text-muted-foreground">{source.url}</p>
                          <div className="flex items-center justify-between mt-1">
                            <p className="text-xs text-muted-foreground">{source.lastUpdate}</p>
                            <Badge variant="outline" className="text-xs">
                              {source.articles} articles
                            </Badge>
                          </div>
                        </div>
                      </div>
                    ))}
                    <div className="pt-2 border-t border-border/50 space-y-2">
                      <Button variant="ghost" size="sm" className="w-full justify-between text-muted-foreground hover:text-foreground">
                        View All Sources
                        <ArrowRight className="h-4 w-4" />
                      </Button>
                      <Button variant="outline" size="sm" className="w-full justify-center border-hexagon-accent/20 text-hexagon-accent hover:bg-hexagon-accent/10">
                        <Plus className="h-4 w-4 mr-2" />
                        Add New Source
                      </Button>
                    </div>
                  </CardContent>
                </Card>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}