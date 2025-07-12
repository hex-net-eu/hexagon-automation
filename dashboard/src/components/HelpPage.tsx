import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { HelpCircle, ExternalLink, Download, Mail, Globe } from 'lucide-react';

export function HelpPage() {
  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-3xl font-bold bg-gradient-primary bg-clip-text text-transparent">
          Help & Documentation
        </h2>
        <p className="text-muted-foreground mt-2">
          Get help with Hexagon Automation v2.0.0
        </p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <Card className="bg-gradient-card border-border/50 shadow-card">
          <CardHeader>
            <CardTitle className="flex items-center">
              <HelpCircle className="mr-2 h-5 w-5 text-hexagon-primary" />
              Quick Start Guide
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <p className="text-sm text-muted-foreground">
              Get up and running with Hexagon Automation in minutes
            </p>
            <Button className="w-full" variant="outline">
              <ExternalLink className="mr-2 h-4 w-4" />
              View Guide
            </Button>
          </CardContent>
        </Card>

        <Card className="bg-gradient-card border-border/50 shadow-card">
          <CardHeader>
            <CardTitle>Support</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-2">
              <p className="text-sm">Email: it@hex-net.eu</p>
              <p className="text-sm">Website: https://hex-net.eu</p>
            </div>
            <Button className="w-full bg-gradient-primary hover:opacity-90">
              <Mail className="mr-2 h-4 w-4" />
              Contact Support
            </Button>
          </CardContent>
        </Card>

        <Card className="bg-gradient-card border-border/50 shadow-card">
          <CardHeader>
            <CardTitle>Version Info</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-2">
              <Badge variant="default">v2.0.0</Badge>
              <p className="text-sm text-muted-foreground">
                Latest version with multi-AI support
              </p>
            </div>
            <Button className="w-full" variant="outline">
              <Download className="mr-2 h-4 w-4" />
              Check Updates
            </Button>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}