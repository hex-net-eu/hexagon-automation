import { useState } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Switch } from '@/components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { 
  Eye, 
  Download,
  Trash2,
  RefreshCw,
  Search,
  Filter,
  AlertTriangle,
  CheckCircle,
  Info,
  XCircle,
  Clock,
  Settings,
  Mail,
  FileText,
  Bug,
  Zap,
  Activity,
  BarChart3,
  Calendar,
  Archive
} from 'lucide-react';

export function DebugLoggerPage() {
  const [logs] = useState([
    {
      id: 1,
      timestamp: '2024-01-10 15:30:25',
      level: 'error',
      module: 'Social Media',
      message: 'Failed to post to LinkedIn: Invalid access token',
      details: 'LinkedIn API returned 401 Unauthorized. Token may have expired.',
      stackTrace: 'at SocialMediaManager.postToLinkedIn() line 156',
      count: 3
    },
    {
      id: 2,
      timestamp: '2024-01-10 15:28:12',
      level: 'warning',
      module: 'AI Content',
      message: 'ChatGPT API rate limit approaching',
      details: 'Current usage: 85% of monthly limit. Consider upgrading plan.',
      stackTrace: null,
      count: 1
    },
    {
      id: 3,
      timestamp: '2024-01-10 15:25:45',
      level: 'info',
      module: 'Email Processing',
      message: 'Successfully processed 12 new emails',
      details: '8 articles extracted, 4 contacts added to CRM',
      stackTrace: null,
      count: 1
    },
    {
      id: 4,
      timestamp: '2024-01-10 15:20:33',
      level: 'success',
      module: 'Image Generator',
      message: 'Generated 3 images for social media carousel',
      details: 'DALL-E 3 model used, 1024x1024 resolution with watermark applied',
      stackTrace: null,
      count: 1
    },
    {
      id: 5,
      timestamp: '2024-01-10 15:15:18',
      level: 'error',
      module: 'WordPress Integration',
      message: 'Failed to publish article: Database connection timeout',
      details: 'MySQL server connection timeout after 30 seconds',
      stackTrace: 'at WordPress.publishArticle() line 89',
      count: 2
    }
  ]);

  const [systemHealth] = useState({
    overall: 'good',
    uptime: '99.2%',
    lastRestart: '2024-01-08 12:00:00',
    totalErrors: 15,
    totalWarnings: 23,
    totalRequests: 12456,
    responseTime: 245
  });

  const [modules] = useState([
    { name: 'AI Content Generator', status: 'online', lastError: null, requests: 1247 },
    { name: 'Social Media Manager', status: 'warning', lastError: '2 min ago', requests: 892 },
    { name: 'Image Generator', status: 'online', lastError: null, requests: 345 },
    { name: 'Email Processor', status: 'online', lastError: null, requests: 2156 },
    { name: 'WordPress Integration', status: 'error', lastError: '15 min ago', requests: 734 },
    { name: 'Debug Logger', status: 'online', lastError: null, requests: 5432 }
  ]);

  const [filters, setFilters] = useState({
    level: 'all',
    module: 'all',
    timeRange: '24h',
    searchQuery: ''
  });

  const levelIcons = {
    error: XCircle,
    warning: AlertTriangle,
    info: Info,
    success: CheckCircle
  };

  const levelColors = {
    error: 'text-hexagon-danger',
    warning: 'text-hexagon-warning',
    info: 'text-hexagon-primary',
    success: 'text-hexagon-success'
  };

  const exportLogs = () => {
    console.log('Exporting logs to CSV...');
  };

  const clearLogs = () => {
    console.log('Clearing all logs...');
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-3xl font-bold bg-gradient-primary bg-clip-text text-transparent">
            Debug Logger
          </h2>
          <p className="text-muted-foreground mt-2">
            Monitor system health, track errors, and debug automation issues
          </p>
        </div>
        <div className="flex items-center space-x-2">
          <Button variant="outline" onClick={exportLogs}>
            <Download className="mr-2 h-4 w-4" />
            Export CSV
          </Button>
          <Button variant="outline" onClick={clearLogs}>
            <Trash2 className="mr-2 h-4 w-4" />
            Clear Logs
          </Button>
          <Button className="bg-gradient-primary hover:opacity-90">
            <RefreshCw className="mr-2 h-4 w-4" />
            Refresh
          </Button>
        </div>
      </div>

      <Tabs defaultValue="logs" className="space-y-6">
        <TabsList className="grid grid-cols-4 w-full max-w-2xl">
          <TabsTrigger value="logs">System Logs</TabsTrigger>
          <TabsTrigger value="health">Health Monitor</TabsTrigger>
          <TabsTrigger value="modules">Module Status</TabsTrigger>
          <TabsTrigger value="settings">Settings</TabsTrigger>
        </TabsList>

        <TabsContent value="logs" className="space-y-6">
          {/* Filters */}
          <Card className="bg-gradient-card border-border/50 shadow-card">
            <CardHeader>
              <CardTitle className="flex items-center">
                <Filter className="mr-2 h-5 w-5 text-hexagon-primary" />
                Log Filters
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div className="space-y-2">
                  <Label>Search</Label>
                  <div className="relative">
                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input
                      placeholder="Search logs..."
                      value={filters.searchQuery}
                      onChange={(e) => setFilters(prev => ({ ...prev, searchQuery: e.target.value }))}
                      className="pl-10"
                    />
                  </div>
                </div>
                <div className="space-y-2">
                  <Label>Level</Label>
                  <Select value={filters.level} onValueChange={(value) => setFilters(prev => ({ ...prev, level: value }))}>
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="all">All Levels</SelectItem>
                      <SelectItem value="error">Errors Only</SelectItem>
                      <SelectItem value="warning">Warnings Only</SelectItem>
                      <SelectItem value="info">Info Only</SelectItem>
                      <SelectItem value="success">Success Only</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label>Module</Label>
                  <Select value={filters.module} onValueChange={(value) => setFilters(prev => ({ ...prev, module: value }))}>
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="all">All Modules</SelectItem>
                      <SelectItem value="social">Social Media</SelectItem>
                      <SelectItem value="ai">AI Content</SelectItem>
                      <SelectItem value="email">Email Processing</SelectItem>
                      <SelectItem value="image">Image Generator</SelectItem>
                      <SelectItem value="wordpress">WordPress</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label>Time Range</Label>
                  <Select value={filters.timeRange} onValueChange={(value) => setFilters(prev => ({ ...prev, timeRange: value }))}>
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="1h">Last Hour</SelectItem>
                      <SelectItem value="24h">Last 24 Hours</SelectItem>
                      <SelectItem value="7d">Last 7 Days</SelectItem>
                      <SelectItem value="30d">Last 30 Days</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Log Entries */}
          <Card className="bg-gradient-card border-border/50 shadow-card">
            <CardHeader>
              <CardTitle className="flex items-center justify-between">
                <span className="flex items-center">
                  <Bug className="mr-2 h-5 w-5 text-hexagon-secondary" />
                  System Logs
                </span>
                <Badge variant="outline">{logs.length} entries</Badge>
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              {logs.map((log) => {
                const LevelIcon = levelIcons[log.level as keyof typeof levelIcons];
                const levelColor = levelColors[log.level as keyof typeof levelColors];
                
                return (
                  <div key={log.id} className="border rounded-lg p-4 bg-muted/30">
                    <div className="flex items-start justify-between">
                      <div className="flex items-start space-x-3 flex-1">
                        <div className={`mt-1 ${levelColor}`}>
                          <LevelIcon className="h-4 w-4" />
                        </div>
                        <div className="flex-1 space-y-2">
                          <div className="flex items-center space-x-3">
                            <Badge variant={log.level === 'error' ? 'destructive' : 
                                          log.level === 'warning' ? 'secondary' :
                                          log.level === 'success' ? 'default' : 'outline'}>
                              {log.level.toUpperCase()}
                            </Badge>
                            <span className="font-medium">{log.module}</span>
                            <span className="text-sm text-muted-foreground">{log.timestamp}</span>
                            {log.count > 1 && (
                              <Badge variant="outline" className="text-xs">
                                {log.count}x
                              </Badge>
                            )}
                          </div>
                          <p className="font-medium">{log.message}</p>
                          <p className="text-sm text-muted-foreground">{log.details}</p>
                          {log.stackTrace && (
                            <details className="text-xs text-muted-foreground">
                              <summary className="cursor-pointer hover:text-foreground">
                                Stack Trace
                              </summary>
                              <pre className="mt-2 bg-muted p-2 rounded overflow-x-auto">
                                {log.stackTrace}
                              </pre>
                            </details>
                          )}
                        </div>
                      </div>
                      <div className="flex items-center space-x-2">
                        <Button variant="outline" size="sm">
                          <Eye className="h-4 w-4" />
                        </Button>
                        <Button variant="outline" size="sm">
                          <Archive className="h-4 w-4" />
                        </Button>
                      </div>
                    </div>
                  </div>
                );
              })}
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="health" className="space-y-6">
          {/* System Overview */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <Card className="bg-gradient-card border-border/50 shadow-card">
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">System Health</CardTitle>
                <Activity className="h-4 w-4 text-hexagon-success" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold text-hexagon-success">
                  {systemHealth.overall.toUpperCase()}
                </div>
                <p className="text-xs text-muted-foreground">
                  <CheckCircle className="inline mr-1 h-3 w-3" />
                  All systems operational
                </p>
              </CardContent>
            </Card>

            <Card className="bg-gradient-card border-border/50 shadow-card">
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Uptime</CardTitle>
                <Clock className="h-4 w-4 text-hexagon-primary" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold text-hexagon-primary">{systemHealth.uptime}</div>
                <p className="text-xs text-muted-foreground">
                  Last restart: {systemHealth.lastRestart}
                </p>
              </CardContent>
            </Card>

            <Card className="bg-gradient-card border-border/50 shadow-card">
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Total Errors</CardTitle>
                <XCircle className="h-4 w-4 text-hexagon-danger" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold text-hexagon-danger">{systemHealth.totalErrors}</div>
                <p className="text-xs text-muted-foreground">
                  <AlertTriangle className="inline mr-1 h-3 w-3" />
                  {systemHealth.totalWarnings} warnings
                </p>
              </CardContent>
            </Card>

            <Card className="bg-gradient-card border-border/50 shadow-card">
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Response Time</CardTitle>
                <Zap className="h-4 w-4 text-hexagon-accent" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold text-hexagon-accent">{systemHealth.responseTime}ms</div>
                <p className="text-xs text-muted-foreground">
                  <BarChart3 className="inline mr-1 h-3 w-3" />
                  {systemHealth.totalRequests.toLocaleString()} requests
                </p>
              </CardContent>
            </Card>
          </div>

          {/* Performance Chart */}
          <Card className="bg-gradient-card border-border/50 shadow-card">
            <CardHeader>
              <CardTitle className="flex items-center">
                <BarChart3 className="mr-2 h-5 w-5 text-hexagon-primary" />
                Performance Metrics
              </CardTitle>
              <CardDescription>System performance over the last 24 hours</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="h-64 bg-muted/30 rounded-lg flex items-center justify-center">
                <span className="text-muted-foreground">Performance chart would be displayed here</span>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="modules" className="space-y-6">
          <Card className="bg-gradient-card border-border/50 shadow-card">
            <CardHeader>
              <CardTitle className="flex items-center">
                <Settings className="mr-2 h-5 w-5 text-hexagon-accent" />
                Module Status
              </CardTitle>
              <CardDescription>Monitor the health and status of all automation modules</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              {modules.map((module, index) => (
                <div key={index} className="flex items-center justify-between p-4 rounded-lg bg-muted/50">
                  <div className="flex items-center space-x-4">
                    <div className={`h-3 w-3 rounded-full ${
                      module.status === 'online' ? 'bg-hexagon-success' :
                      module.status === 'warning' ? 'bg-hexagon-warning' :
                      'bg-hexagon-danger'
                    }`} />
                    <div>
                      <h4 className="font-medium">{module.name}</h4>
                      <div className="flex items-center space-x-4 text-sm text-muted-foreground">
                        <span>{module.requests.toLocaleString()} requests</span>
                        {module.lastError && (
                          <>
                            <span>•</span>
                            <span>Last error: {module.lastError}</span>
                          </>
                        )}
                      </div>
                    </div>
                  </div>
                  <div className="flex items-center space-x-3">
                    <Badge variant={
                      module.status === 'online' ? 'default' :
                      module.status === 'warning' ? 'secondary' :
                      'destructive'
                    }>
                      {module.status}
                    </Badge>
                    <Button variant="outline" size="sm">
                      <Eye className="h-4 w-4" />
                    </Button>
                    <Button variant="outline" size="sm">
                      <Settings className="h-4 w-4" />
                    </Button>
                  </div>
                </div>
              ))}
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="settings" className="space-y-6">
          <Card className="bg-gradient-card border-border/50 shadow-card">
            <CardHeader>
              <CardTitle className="flex items-center">
                <Settings className="mr-2 h-5 w-5 text-hexagon-primary" />
                Logger Configuration
              </CardTitle>
              <CardDescription>Configure debug logging and monitoring settings</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="space-y-4">
                  <h4 className="font-medium">Logging Levels</h4>
                  <div className="space-y-3">
                    {['error', 'warning', 'info', 'debug'].map((level) => (
                      <div key={level} className="flex items-center justify-between">
                        <Label className="capitalize">{level} logging</Label>
                        <Switch defaultChecked={level !== 'debug'} />
                      </div>
                    ))}
                  </div>
                </div>

                <div className="space-y-4">
                  <h4 className="font-medium">Storage Settings</h4>
                  <div className="space-y-3">
                    <div className="space-y-2">
                      <Label>Max log file size (MB)</Label>
                      <Input type="number" defaultValue="100" min="10" max="1000" />
                    </div>
                    <div className="space-y-2">
                      <Label>Log retention (days)</Label>
                      <Input type="number" defaultValue="30" min="1" max="365" />
                    </div>
                    <div className="flex items-center justify-between">
                      <Label>Auto-cleanup old logs</Label>
                      <Switch defaultChecked />
                    </div>
                  </div>
                </div>
              </div>

              <div className="space-y-4">
                <h4 className="font-medium">Email Notifications</h4>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div className="flex items-center justify-between">
                    <Label>Email on critical errors</Label>
                    <Switch defaultChecked />
                  </div>
                  <div className="flex items-center justify-between">
                    <Label>Daily summary reports</Label>
                    <Switch />
                  </div>
                  <div className="space-y-2">
                    <Label>Notification email</Label>
                    <Input type="email" placeholder="admin@yourdomain.com" />
                  </div>
                  <div className="space-y-2">
                    <Label>Error threshold</Label>
                    <Select defaultValue="5">
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="1">1 error</SelectItem>
                        <SelectItem value="5">5 errors</SelectItem>
                        <SelectItem value="10">10 errors</SelectItem>
                        <SelectItem value="50">50 errors</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                </div>
              </div>

              <div className="space-y-4">
                <h4 className="font-medium">Advanced Options</h4>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div className="flex items-center justify-between">
                    <Label>Enable stack traces</Label>
                    <Switch defaultChecked />
                  </div>
                  <div className="flex items-center justify-between">
                    <Label>Log API requests</Label>
                    <Switch />
                  </div>
                  <div className="flex items-center justify-between">
                    <Label>Performance monitoring</Label>
                    <Switch defaultChecked />
                  </div>
                  <div className="flex items-center justify-between">
                    <Label>Real-time alerts</Label>
                    <Switch defaultChecked />
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