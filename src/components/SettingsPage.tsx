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
import { 
  Settings, 
  Shield,
  Database,
  Mail,
  Globe,
  Clock,
  Save,
  RefreshCw,
  Download,
  Upload,
  Key,
  Lock,
  Eye,
  EyeOff,
  AlertTriangle,
  CheckCircle,
  Zap,
  Users,
  FileText,
  Palette
} from 'lucide-react';

export function SettingsPage() {
  const [showApiKeys, setShowApiKeys] = useState<Record<string, boolean>>({});
  const [settings, setSettings] = useState({
    general: {
      siteName: 'Your Business Website',
      adminEmail: 'admin@yourbusiness.com',
      timezone: 'Europe/Warsaw',
      language: 'pl_PL',
      debugMode: false,
      safeMode: true
    },
    automation: {
      autoPublish: true,
      contentReview: false,
      socialAutoPost: true,
      imageGeneration: true,
      emailProcessing: true,
      scheduledPosts: true
    },
    security: {
      enableTwoFactor: false,
      sessionTimeout: 24,
      ipWhitelist: '',
      encryptionEnabled: true,
      backupEnabled: true,
      auditLogging: true
    },
    notifications: {
      emailNotifications: true,
      errorAlerts: true,
      successReports: false,
      weeklyDigest: true,
      slackIntegration: false,
      webhookUrl: ''
    }
  });

  const toggleApiKeyVisibility = (key: string) => {
    setShowApiKeys(prev => ({
      ...prev,
      [key]: !prev[key]
    }));
  };

  const saveSettings = () => {
    console.log('Saving settings...', settings);
  };

  const exportSettings = () => {
    console.log('Exporting settings...');
  };

  const importSettings = () => {
    console.log('Importing settings...');
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-3xl font-bold bg-gradient-primary bg-clip-text text-transparent">
            Settings
          </h2>
          <p className="text-muted-foreground mt-2">
            Configure Hexagon Automation settings and preferences
          </p>
        </div>
        <div className="flex items-center space-x-2">
          <Button variant="outline" onClick={exportSettings}>
            <Download className="mr-2 h-4 w-4" />
            Export
          </Button>
          <Button variant="outline" onClick={importSettings}>
            <Upload className="mr-2 h-4 w-4" />
            Import
          </Button>
          <Button onClick={saveSettings} className="bg-gradient-primary hover:opacity-90">
            <Save className="mr-2 h-4 w-4" />
            Save Changes
          </Button>
        </div>
      </div>

      <Tabs defaultValue="general" className="space-y-6">
        <TabsList className="grid grid-cols-5 w-full max-w-3xl">
          <TabsTrigger value="general">General</TabsTrigger>
          <TabsTrigger value="automation">Automation</TabsTrigger>
          <TabsTrigger value="security">Security</TabsTrigger>
          <TabsTrigger value="notifications">Notifications</TabsTrigger>
          <TabsTrigger value="advanced">Advanced</TabsTrigger>
        </TabsList>

        <TabsContent value="general" className="space-y-6">
          <Card className="bg-gradient-card border-border/50 shadow-card">
            <CardHeader>
              <CardTitle className="flex items-center">
                <Settings className="mr-2 h-5 w-5 text-hexagon-primary" />
                General Settings
              </CardTitle>
              <CardDescription>Basic configuration and site information</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Site Name</Label>
                  <Input
                    value={settings.general.siteName}
                    onChange={(e) => 
                      setSettings(prev => ({
                        ...prev,
                        general: { ...prev.general, siteName: e.target.value }
                      }))
                    }
                  />
                </div>
                <div className="space-y-2">
                  <Label>Admin Email</Label>
                  <Input
                    type="email"
                    value={settings.general.adminEmail}
                    onChange={(e) => 
                      setSettings(prev => ({
                        ...prev,
                        general: { ...prev.general, adminEmail: e.target.value }
                      }))
                    }
                  />
                </div>
                <div className="space-y-2">
                  <Label>Timezone</Label>
                  <Select 
                    value={settings.general.timezone}
                    onValueChange={(value) => 
                      setSettings(prev => ({
                        ...prev,
                        general: { ...prev.general, timezone: value }
                      }))
                    }
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="Europe/Warsaw">Europe/Warsaw (GMT+1)</SelectItem>
                      <SelectItem value="Europe/London">Europe/London (GMT+0)</SelectItem>
                      <SelectItem value="America/New_York">America/New_York (GMT-5)</SelectItem>
                      <SelectItem value="America/Los_Angeles">America/Los_Angeles (GMT-8)</SelectItem>
                      <SelectItem value="Asia/Tokyo">Asia/Tokyo (GMT+9)</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label>Language</Label>
                  <Select 
                    value={settings.general.language}
                    onValueChange={(value) => 
                      setSettings(prev => ({
                        ...prev,
                        general: { ...prev.general, language: value }
                      }))
                    }
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="pl_PL">Polish (Polski)</SelectItem>
                      <SelectItem value="en_US">English (US)</SelectItem>
                      <SelectItem value="en_GB">English (UK)</SelectItem>
                      <SelectItem value="es_ES">Spanish (Español)</SelectItem>
                      <SelectItem value="fr_FR">French (Français)</SelectItem>
                      <SelectItem value="de_DE">German (Deutsch)</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>

              <div className="space-y-4">
                <h4 className="font-medium">System Options</h4>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div className="flex items-center justify-between">
                    <div>
                      <Label>Debug Mode</Label>
                      <p className="text-sm text-muted-foreground">Enable detailed logging</p>
                    </div>
                    <Switch 
                      checked={settings.general.debugMode}
                      onCheckedChange={(checked) => 
                        setSettings(prev => ({
                          ...prev,
                          general: { ...prev.general, debugMode: checked }
                        }))
                      }
                    />
                  </div>
                  <div className="flex items-center justify-between">
                    <div>
                      <Label>Safe Mode</Label>
                      <p className="text-sm text-muted-foreground">Automatic error recovery</p>
                    </div>
                    <Switch 
                      checked={settings.general.safeMode}
                      onCheckedChange={(checked) => 
                        setSettings(prev => ({
                          ...prev,
                          general: { ...prev.general, safeMode: checked }
                        }))
                      }
                    />
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Plugin Status */}
          <Card className="bg-gradient-card border-border/50 shadow-card">
            <CardHeader>
              <CardTitle className="flex items-center">
                <Zap className="mr-2 h-5 w-5 text-hexagon-secondary" />
                Plugin Information
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div className="text-center p-4 bg-muted/30 rounded-lg">
                  <h4 className="font-medium">Version</h4>
                  <p className="text-2xl font-bold text-hexagon-primary">2.0.0</p>
                  <p className="text-sm text-muted-foreground">Latest</p>
                </div>
                <div className="text-center p-4 bg-muted/30 rounded-lg">
                  <h4 className="font-medium">License</h4>
                  <p className="text-2xl font-bold text-hexagon-secondary">Pro</p>
                  <p className="text-sm text-muted-foreground">Valid until 2025-01-10</p>
                </div>
                <div className="text-center p-4 bg-muted/30 rounded-lg">
                  <h4 className="font-medium">Status</h4>
                  <p className="text-2xl font-bold text-hexagon-success">Active</p>
                  <p className="text-sm text-muted-foreground">All features enabled</p>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="automation" className="space-y-6">
          <Card className="bg-gradient-card border-border/50 shadow-card">
            <CardHeader>
              <CardTitle className="flex items-center">
                <Zap className="mr-2 h-5 w-5 text-hexagon-accent" />
                Automation Settings
              </CardTitle>
              <CardDescription>Configure automated processes and workflows</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="space-y-4">
                  <h4 className="font-medium">Content Automation</h4>
                  <div className="space-y-3">
                    <div className="flex items-center justify-between">
                      <div>
                        <Label>Auto-publish articles</Label>
                        <p className="text-sm text-muted-foreground">Automatically publish generated content</p>
                      </div>
                      <Switch 
                        checked={settings.automation.autoPublish}
                        onCheckedChange={(checked) => 
                          setSettings(prev => ({
                            ...prev,
                            automation: { ...prev.automation, autoPublish: checked }
                          }))
                        }
                      />
                    </div>
                    <div className="flex items-center justify-between">
                      <div>
                        <Label>Content review required</Label>
                        <p className="text-sm text-muted-foreground">Require manual approval before publishing</p>
                      </div>
                      <Switch 
                        checked={settings.automation.contentReview}
                        onCheckedChange={(checked) => 
                          setSettings(prev => ({
                            ...prev,
                            automation: { ...prev.automation, contentReview: checked }
                          }))
                        }
                      />
                    </div>
                    <div className="flex items-center justify-between">
                      <div>
                        <Label>Image generation</Label>
                        <p className="text-sm text-muted-foreground">Auto-generate featured images</p>
                      </div>
                      <Switch 
                        checked={settings.automation.imageGeneration}
                        onCheckedChange={(checked) => 
                          setSettings(prev => ({
                            ...prev,
                            automation: { ...prev.automation, imageGeneration: checked }
                          }))
                        }
                      />
                    </div>
                  </div>
                </div>

                <div className="space-y-4">
                  <h4 className="font-medium">Social Media & Email</h4>
                  <div className="space-y-3">
                    <div className="flex items-center justify-between">
                      <div>
                        <Label>Auto-post to social media</Label>
                        <p className="text-sm text-muted-foreground">Share new content automatically</p>
                      </div>
                      <Switch 
                        checked={settings.automation.socialAutoPost}
                        onCheckedChange={(checked) => 
                          setSettings(prev => ({
                            ...prev,
                            automation: { ...prev.automation, socialAutoPost: checked }
                          }))
                        }
                      />
                    </div>
                    <div className="flex items-center justify-between">
                      <div>
                        <Label>Email processing</Label>
                        <p className="text-sm text-muted-foreground">Monitor and process incoming emails</p>
                      </div>
                      <Switch 
                        checked={settings.automation.emailProcessing}
                        onCheckedChange={(checked) => 
                          setSettings(prev => ({
                            ...prev,
                            automation: { ...prev.automation, emailProcessing: checked }
                          }))
                        }
                      />
                    </div>
                    <div className="flex items-center justify-between">
                      <div>
                        <Label>Scheduled posts</Label>
                        <p className="text-sm text-muted-foreground">Enable post scheduling</p>
                      </div>
                      <Switch 
                        checked={settings.automation.scheduledPosts}
                        onCheckedChange={(checked) => 
                          setSettings(prev => ({
                            ...prev,
                            automation: { ...prev.automation, scheduledPosts: checked }
                          }))
                        }
                      />
                    </div>
                  </div>
                </div>
              </div>

              <div className="space-y-4">
                <h4 className="font-medium">Posting Schedule</h4>
                <div className="grid grid-cols-7 gap-2 text-center text-sm">
                  {['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].map((day) => (
                    <div key={day} className="space-y-2">
                      <Label className="font-medium">{day}</Label>
                      <div className="space-y-1">
                        <Input type="time" defaultValue="09:00" className="text-xs" />
                        <Input type="time" defaultValue="15:00" className="text-xs" />
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="security" className="space-y-6">
          <Card className="bg-gradient-card border-border/50 shadow-card">
            <CardHeader>
              <CardTitle className="flex items-center">
                <Shield className="mr-2 h-5 w-5 text-hexagon-success" />
                Security Settings
              </CardTitle>
              <CardDescription>Configure security and access control settings</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="space-y-4">
                  <h4 className="font-medium">Authentication</h4>
                  <div className="space-y-3">
                    <div className="flex items-center justify-between">
                      <div>
                        <Label>Two-Factor Authentication</Label>
                        <p className="text-sm text-muted-foreground">Add extra security layer</p>
                      </div>
                      <Switch 
                        checked={settings.security.enableTwoFactor}
                        onCheckedChange={(checked) => 
                          setSettings(prev => ({
                            ...prev,
                            security: { ...prev.security, enableTwoFactor: checked }
                          }))
                        }
                      />
                    </div>
                    <div className="space-y-2">
                      <Label>Session Timeout (hours)</Label>
                      <Input
                        type="number"
                        value={settings.security.sessionTimeout}
                        onChange={(e) => 
                          setSettings(prev => ({
                            ...prev,
                            security: { ...prev.security, sessionTimeout: parseInt(e.target.value) }
                          }))
                        }
                        min="1"
                        max="168"
                      />
                    </div>
                  </div>
                </div>

                <div className="space-y-4">
                  <h4 className="font-medium">Access Control</h4>
                  <div className="space-y-3">
                    <div className="space-y-2">
                      <Label>IP Whitelist</Label>
                      <Textarea
                        placeholder="Enter IP addresses, one per line"
                        value={settings.security.ipWhitelist}
                        onChange={(e) => 
                          setSettings(prev => ({
                            ...prev,
                            security: { ...prev.security, ipWhitelist: e.target.value }
                          }))
                        }
                        rows={3}
                      />
                    </div>
                    <div className="flex items-center justify-between">
                      <div>
                        <Label>Audit Logging</Label>
                        <p className="text-sm text-muted-foreground">Log all admin actions</p>
                      </div>
                      <Switch 
                        checked={settings.security.auditLogging}
                        onCheckedChange={(checked) => 
                          setSettings(prev => ({
                            ...prev,
                            security: { ...prev.security, auditLogging: checked }
                          }))
                        }
                      />
                    </div>
                  </div>
                </div>
              </div>

              <div className="space-y-4">
                <h4 className="font-medium">Data Protection</h4>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div className="flex items-center justify-between">
                    <div>
                      <Label>Encryption Enabled</Label>
                      <p className="text-sm text-muted-foreground">Encrypt sensitive data</p>
                    </div>
                    <Switch 
                      checked={settings.security.encryptionEnabled}
                      onCheckedChange={(checked) => 
                        setSettings(prev => ({
                          ...prev,
                          security: { ...prev.security, encryptionEnabled: checked }
                        }))
                      }
                    />
                  </div>
                  <div className="flex items-center justify-between">
                    <div>
                      <Label>Automatic Backups</Label>
                      <p className="text-sm text-muted-foreground">Daily data backups</p>
                    </div>
                    <Switch 
                      checked={settings.security.backupEnabled}
                      onCheckedChange={(checked) => 
                        setSettings(prev => ({
                          ...prev,
                          security: { ...prev.security, backupEnabled: checked }
                        }))
                      }
                    />
                  </div>
                </div>
              </div>

              {/* Security Status */}
              <div className="bg-muted/50 rounded-lg p-4 space-y-3">
                <h4 className="font-medium">Security Status</h4>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div className="flex items-center space-x-2">
                    <CheckCircle className="h-4 w-4 text-hexagon-success" />
                    <span className="text-sm">SSL Certificate Valid</span>
                  </div>
                  <div className="flex items-center space-x-2">
                    <CheckCircle className="h-4 w-4 text-hexagon-success" />
                    <span className="text-sm">Data Encryption Active</span>
                  </div>
                  <div className="flex items-center space-x-2">
                    <AlertTriangle className="h-4 w-4 text-hexagon-warning" />
                    <span className="text-sm">2FA Disabled</span>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="notifications" className="space-y-6">
          <Card className="bg-gradient-card border-border/50 shadow-card">
            <CardHeader>
              <CardTitle className="flex items-center">
                <Mail className="mr-2 h-5 w-5 text-hexagon-warning" />
                Notification Settings
              </CardTitle>
              <CardDescription>Configure email alerts and notification preferences</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="space-y-4">
                  <h4 className="font-medium">Email Notifications</h4>
                  <div className="space-y-3">
                    <div className="flex items-center justify-between">
                      <div>
                        <Label>Enable email notifications</Label>
                        <p className="text-sm text-muted-foreground">Receive system notifications</p>
                      </div>
                      <Switch 
                        checked={settings.notifications.emailNotifications}
                        onCheckedChange={(checked) => 
                          setSettings(prev => ({
                            ...prev,
                            notifications: { ...prev.notifications, emailNotifications: checked }
                          }))
                        }
                      />
                    </div>
                    <div className="flex items-center justify-between">
                      <div>
                        <Label>Error alerts</Label>
                        <p className="text-sm text-muted-foreground">Get notified of system errors</p>
                      </div>
                      <Switch 
                        checked={settings.notifications.errorAlerts}
                        onCheckedChange={(checked) => 
                          setSettings(prev => ({
                            ...prev,
                            notifications: { ...prev.notifications, errorAlerts: checked }
                          }))
                        }
                      />
                    </div>
                    <div className="flex items-center justify-between">
                      <div>
                        <Label>Success reports</Label>
                        <p className="text-sm text-muted-foreground">Reports on successful operations</p>
                      </div>
                      <Switch 
                        checked={settings.notifications.successReports}
                        onCheckedChange={(checked) => 
                          setSettings(prev => ({
                            ...prev,
                            notifications: { ...prev.notifications, successReports: checked }
                          }))
                        }
                      />
                    </div>
                    <div className="flex items-center justify-between">
                      <div>
                        <Label>Weekly digest</Label>
                        <p className="text-sm text-muted-foreground">Weekly summary of activities</p>
                      </div>
                      <Switch 
                        checked={settings.notifications.weeklyDigest}
                        onCheckedChange={(checked) => 
                          setSettings(prev => ({
                            ...prev,
                            notifications: { ...prev.notifications, weeklyDigest: checked }
                          }))
                        }
                      />
                    </div>
                  </div>
                </div>

                <div className="space-y-4">
                  <h4 className="font-medium">External Integrations</h4>
                  <div className="space-y-3">
                    <div className="flex items-center justify-between">
                      <div>
                        <Label>Slack integration</Label>
                        <p className="text-sm text-muted-foreground">Send notifications to Slack</p>
                      </div>
                      <Switch 
                        checked={settings.notifications.slackIntegration}
                        onCheckedChange={(checked) => 
                          setSettings(prev => ({
                            ...prev,
                            notifications: { ...prev.notifications, slackIntegration: checked }
                          }))
                        }
                      />
                    </div>
                    <div className="space-y-2">
                      <Label>Webhook URL</Label>
                      <Input
                        type="url"
                        placeholder="https://hooks.slack.com/services/..."
                        value={settings.notifications.webhookUrl}
                        onChange={(e) => 
                          setSettings(prev => ({
                            ...prev,
                            notifications: { ...prev.notifications, webhookUrl: e.target.value }
                          }))
                        }
                      />
                    </div>
                  </div>
                </div>
              </div>

              <div className="space-y-4">
                <h4 className="font-medium">Notification Schedule</h4>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div className="space-y-2">
                    <Label>Error notification frequency</Label>
                    <Select defaultValue="immediate">
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="immediate">Immediate</SelectItem>
                        <SelectItem value="hourly">Hourly digest</SelectItem>
                        <SelectItem value="daily">Daily digest</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div className="space-y-2">
                    <Label>Report delivery time</Label>
                    <Input type="time" defaultValue="09:00" />
                  </div>
                  <div className="space-y-2">
                    <Label>Quiet hours</Label>
                    <div className="flex space-x-2">
                      <Input type="time" defaultValue="22:00" placeholder="From" />
                      <Input type="time" defaultValue="07:00" placeholder="To" />
                    </div>
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
                <Database className="mr-2 h-5 w-5 text-hexagon-accent" />
                Advanced Settings
              </CardTitle>
              <CardDescription>Advanced configuration options for power users</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="space-y-4">
                  <h4 className="font-medium">Performance</h4>
                  <div className="space-y-3">
                    <div className="space-y-2">
                      <Label>Cache duration (minutes)</Label>
                      <Input type="number" defaultValue="60" min="5" max="1440" />
                    </div>
                    <div className="space-y-2">
                      <Label>Max concurrent requests</Label>
                      <Input type="number" defaultValue="10" min="1" max="50" />
                    </div>
                    <div className="space-y-2">
                      <Label>Request timeout (seconds)</Label>
                      <Input type="number" defaultValue="30" min="10" max="300" />
                    </div>
                  </div>
                </div>

                <div className="space-y-4">
                  <h4 className="font-medium">Database</h4>
                  <div className="space-y-3">
                    <div className="space-y-2">
                      <Label>Cleanup old logs after (days)</Label>
                      <Input type="number" defaultValue="30" min="1" max="365" />
                    </div>
                    <div className="space-y-2">
                      <Label>Max log entries</Label>
                      <Input type="number" defaultValue="10000" min="1000" max="100000" />
                    </div>
                    <div className="flex items-center justify-between">
                      <Label>Enable database optimization</Label>
                      <Switch defaultChecked />
                    </div>
                  </div>
                </div>
              </div>

              <div className="space-y-4">
                <h4 className="font-medium">Custom Code</h4>
                <div className="space-y-2">
                  <Label>Custom CSS</Label>
                  <Textarea
                    placeholder="Add custom CSS styles for the admin interface..."
                    rows={4}
                  />
                </div>
                <div className="space-y-2">
                  <Label>Custom JavaScript</Label>
                  <Textarea
                    placeholder="Add custom JavaScript code..."
                    rows={4}
                  />
                </div>
              </div>

              <div className="space-y-4">
                <h4 className="font-medium">Development</h4>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div className="flex items-center justify-between">
                    <Label>Developer mode</Label>
                    <Switch />
                  </div>
                  <div className="flex items-center justify-between">
                    <Label>API access logging</Label>
                    <Switch />
                  </div>
                  <div className="flex items-center justify-between">
                    <Label>Show debug information</Label>
                    <Switch />
                  </div>
                  <div className="flex items-center justify-between">
                    <Label>Enable webhooks</Label>
                    <Switch defaultChecked />
                  </div>
                </div>
              </div>

              {/* System Information */}
              <div className="bg-muted/50 rounded-lg p-4 space-y-3">
                <h4 className="font-medium">System Information</h4>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                  <div>
                    <span className="text-muted-foreground">PHP Version:</span>
                    <span className="ml-2 font-medium">8.1.2</span>
                  </div>
                  <div>
                    <span className="text-muted-foreground">WordPress Version:</span>
                    <span className="ml-2 font-medium">6.4.2</span>
                  </div>
                  <div>
                    <span className="text-muted-foreground">MySQL Version:</span>
                    <span className="ml-2 font-medium">8.0.35</span>
                  </div>
                  <div>
                    <span className="text-muted-foreground">Max Execution Time:</span>
                    <span className="ml-2 font-medium">300 seconds</span>
                  </div>
                  <div>
                    <span className="text-muted-foreground">Memory Limit:</span>
                    <span className="ml-2 font-medium">512M</span>
                  </div>
                  <div>
                    <span className="text-muted-foreground">Upload Max Size:</span>
                    <span className="ml-2 font-medium">64M</span>
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