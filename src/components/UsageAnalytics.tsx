"use client";

import { useState } from "react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Progress } from "@/components/ui/progress";

interface AnalyticsData {
  totalPagesProcessed: number;
  totalWordsReplaced: number;
  totalTokensUsed: number;
  totalCost: number;
  processingTime: number;
  successRate: number;
  lastProcessed: string;
}

interface AIProviderUsage {
  name: string;
  requests: number;
  tokens: number;
  cost: number;
  averageResponseTime: number;
  successRate: number;
  dailyLimit: number;
  usedToday: number;
}

interface ProcessingHistory {
  id: string;
  date: string;
  type: string;
  pagesProcessed: number;
  tokensUsed: number;
  duration: number;
  status: 'completed' | 'failed' | 'partial';
  aiProvider: string;
}

export default function UsageAnalytics() {
  const [timeRange, setTimeRange] = useState('7days');

  const [analytics] = useState<AnalyticsData>({
    totalPagesProcessed: 127,
    totalWordsReplaced: 45280,
    totalTokensUsed: 58864,
    totalCost: 12.45,
    processingTime: 1847, // seconds
    successRate: 94.5,
    lastProcessed: '2024-01-15 14:30'
  });

  const [providerUsage] = useState<AIProviderUsage[]>([
    {
      name: 'OpenAI GPT-4',
      requests: 45,
      tokens: 23400,
      cost: 4.68,
      averageResponseTime: 2.3,
      successRate: 96.7,
      dailyLimit: 1000,
      usedToday: 340
    },
    {
      name: 'Anthropic Claude',
      requests: 38,
      tokens: 19200,
      cost: 3.84,
      averageResponseTime: 1.9,
      successRate: 97.4,
      dailyLimit: 500,
      usedToday: 125
    },
    {
      name: 'Google Gemini',
      requests: 32,
      tokens: 12600,
      cost: 2.52,
      averageResponseTime: 1.5,
      successRate: 93.8,
      dailyLimit: 800,
      usedToday: 89
    },
    {
      name: 'Groq Llama',
      requests: 28,
      tokens: 14100,
      cost: 1.41,
      averageResponseTime: 0.8,
      successRate: 91.1,
      dailyLimit: 2000,
      usedToday: 456
    }
  ]);

  const [processingHistory] = useState<ProcessingHistory[]>([
    {
      id: '1',
      date: '2024-01-15',
      type: 'Full Site Processing',
      pagesProcessed: 24,
      tokensUsed: 12400,
      duration: 340,
      status: 'completed',
      aiProvider: 'OpenAI GPT-4'
    },
    {
      id: '2', 
      date: '2024-01-14',
      type: 'Blog Posts Update',
      pagesProcessed: 8,
      tokensUsed: 4200,
      duration: 128,
      status: 'completed',
      aiProvider: 'Anthropic Claude'
    },
    {
      id: '3',
      date: '2024-01-13',
      type: 'Service Pages',
      pagesProcessed: 6,
      tokensUsed: 3100,
      duration: 95,
      status: 'partial',
      aiProvider: 'Google Gemini'
    },
    {
      id: '4',
      date: '2024-01-12',
      type: 'Product Descriptions',
      pagesProcessed: 15,
      tokensUsed: 7800,
      duration: 180,
      status: 'completed',
      aiProvider: 'Groq Llama'
    }
  ]);

  const formatDuration = (seconds: number) => {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const remainingSeconds = seconds % 60;

    if (hours > 0) {
      return `${hours}h ${minutes}m ${remainingSeconds}s`;
    } else if (minutes > 0) {
      return `${minutes}m ${remainingSeconds}s`;
    } else {
      return `${remainingSeconds}s`;
    }
  };

  const getStatusBadge = (status: ProcessingHistory['status']) => {
    const variants = {
      completed: 'bg-green-100 text-green-800',
      failed: 'bg-red-100 text-red-800',
      partial: 'bg-yellow-100 text-yellow-800'
    };

    return (
      <Badge className={variants[status]}>
        {status.charAt(0).toUpperCase() + status.slice(1)}
      </Badge>
    );
  };

  const exportData = () => {
    const csvData = processingHistory.map(item => ({
      Date: item.date,
      Type: item.type,
      Pages: item.pagesProcessed,
      Tokens: item.tokensUsed,
      Duration: item.duration,
      Status: item.status,
      Provider: item.aiProvider
    }));

    const csv = [
      Object.keys(csvData[0]).join(','),
      ...csvData.map(row => Object.values(row).join(','))
    ].join('\n');

    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'ai-content-replacer-analytics.csv';
    a.click();
    window.URL.revokeObjectURL(url);
  };

  return (
    <div className="space-y-6">
      {/* Overview Stats */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <Card>
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">Total Pages</p>
                <p className="text-3xl font-bold text-gray-900">{analytics.totalPagesProcessed}</p>
              </div>
              <div className="p-2 bg-blue-100 rounded-lg">
                <svg className="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
              </div>
            </div>
            <p className="text-xs text-green-600 mt-1">+12 this week</p>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">Words Replaced</p>
                <p className="text-3xl font-bold text-gray-900">{analytics.totalWordsReplaced.toLocaleString()}</p>
              </div>
              <div className="p-2 bg-green-100 rounded-lg">
                <svg className="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
              </div>
            </div>
            <p className="text-xs text-green-600 mt-1">+5,240 this week</p>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">Tokens Used</p>
                <p className="text-3xl font-bold text-gray-900">{analytics.totalTokensUsed.toLocaleString()}</p>
              </div>
              <div className="p-2 bg-yellow-100 rounded-lg">
                <svg className="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                </svg>
              </div>
            </div>
            <p className="text-xs text-blue-600 mt-1">6,820 this week</p>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">Total Cost</p>
                <p className="text-3xl font-bold text-gray-900">${analytics.totalCost}</p>
              </div>
              <div className="p-2 bg-purple-100 rounded-lg">
                <svg className="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                </svg>
              </div>
            </div>
            <p className="text-xs text-purple-600 mt-1">$2.80 this week</p>
          </CardContent>
        </Card>
      </div>

      {/* Performance Metrics */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <Card>
          <CardHeader>
            <CardTitle>Performance Overview</CardTitle>
            <CardDescription>Key performance indicators for content processing</CardDescription>
          </CardHeader>
          <CardContent className="space-y-6">
            <div className="space-y-3">
              <div className="flex justify-between items-center">
                <span className="text-sm font-medium">Success Rate</span>
                <span className="text-sm font-bold text-green-600">{analytics.successRate}%</span>
              </div>
              <Progress value={analytics.successRate} className="h-2" />
            </div>

            <div className="space-y-3">
              <div className="flex justify-between items-center">
                <span className="text-sm font-medium">Average Processing Time</span>
                <span className="text-sm font-bold">2.3 min/page</span>
              </div>
              <Progress value={75} className="h-2" />
            </div>

            <div className="space-y-3">
              <div className="flex justify-between items-center">
                <span className="text-sm font-medium">Cost Efficiency</span>
                <span className="text-sm font-bold text-blue-600">$0.098/page</span>
              </div>
              <Progress value={88} className="h-2" />
            </div>

            <div className="pt-4 border-t">
              <div className="text-sm text-gray-600">
                <p>Last processed: {analytics.lastProcessed}</p>
                <p>Total processing time: {formatDuration(analytics.processingTime)}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Time Range Filter */}
        <Card>
          <CardHeader>
            <div className="flex justify-between items-center">
              <div>
                <CardTitle>Analytics Period</CardTitle>
                <CardDescription>Select time range for detailed analysis</CardDescription>
              </div>
              <Select value={timeRange} onValueChange={setTimeRange}>
                <SelectTrigger className="w-32">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="1day">24 hours</SelectItem>
                  <SelectItem value="7days">7 days</SelectItem>
                  <SelectItem value="30days">30 days</SelectItem>
                  <SelectItem value="90days">90 days</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid grid-cols-2 gap-4">
              <div className="text-center p-4 bg-blue-50 rounded-lg">
                <p className="text-2xl font-bold text-blue-900">143</p>
                <p className="text-sm text-blue-700">Total Requests</p>
              </div>
              <div className="text-center p-4 bg-green-50 rounded-lg">
                <p className="text-2xl font-bold text-green-900">96.2%</p>
                <p className="text-sm text-green-700">Uptime</p>
              </div>
              <div className="text-center p-4 bg-yellow-50 rounded-lg">
                <p className="text-2xl font-bold text-yellow-900">1.8s</p>
                <p className="text-sm text-yellow-700">Avg Response</p>
              </div>
              <div className="text-center p-4 bg-purple-50 rounded-lg">
                <p className="text-2xl font-bold text-purple-900">4</p>
                <p className="text-sm text-purple-700">Active APIs</p>
              </div>
            </div>

            <Button onClick={exportData} variant="outline" className="w-full">
              Export Analytics Data
            </Button>
          </CardContent>
        </Card>
      </div>

      {/* AI Provider Usage */}
      <Card>
        <CardHeader>
          <CardTitle>AI Provider Usage</CardTitle>
          <CardDescription>Performance and usage statistics for each AI provider</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {providerUsage.map((provider) => (
              <div key={provider.name} className="p-4 border rounded-lg">
                <div className="flex justify-between items-start mb-3">
                  <div>
                    <h4 className="font-semibold">{provider.name}</h4>
                    <p className="text-sm text-gray-600">
                      {provider.requests} requests • {provider.tokens.toLocaleString()} tokens • ${provider.cost}
                    </p>
                  </div>
                  <div className="text-right">
                    <Badge variant="outline">
                      {provider.successRate}% success
                    </Badge>
                  </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-3">
                  <div className="text-sm">
                    <span className="text-gray-600">Response Time: </span>
                    <span className="font-medium">{provider.averageResponseTime}s</span>
                  </div>
                  <div className="text-sm">
                    <span className="text-gray-600">Cost/Request: </span>
                    <span className="font-medium">${(provider.cost / provider.requests).toFixed(3)}</span>
                  </div>
                  <div className="text-sm">
                    <span className="text-gray-600">Used Today: </span>
                    <span className="font-medium">{provider.usedToday}/{provider.dailyLimit}</span>
                  </div>
                </div>

                <div className="space-y-1">
                  <div className="flex justify-between text-xs">
                    <span>Daily Usage</span>
                    <span>{Math.round((provider.usedToday / provider.dailyLimit) * 100)}%</span>
                  </div>
                  <Progress value={(provider.usedToday / provider.dailyLimit) * 100} className="h-2" />
                </div>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>

      {/* Processing History */}
      <Card>
        <CardHeader>
          <CardTitle>Processing History</CardTitle>
          <CardDescription>Recent content processing activities</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="space-y-3">
            {processingHistory.map((item) => (
              <div key={item.id} className="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50">
                <div className="flex items-center gap-4">
                  <div className="text-sm">
                    <div className="font-medium">{item.type}</div>
                    <div className="text-gray-500">{item.date}</div>
                  </div>
                </div>
                
                <div className="flex items-center gap-4 text-sm">
                  <div className="text-center">
                    <div className="font-medium">{item.pagesProcessed}</div>
                    <div className="text-gray-500">pages</div>
                  </div>
                  <div className="text-center">
                    <div className="font-medium">{item.tokensUsed.toLocaleString()}</div>
                    <div className="text-gray-500">tokens</div>
                  </div>
                  <div className="text-center">
                    <div className="font-medium">{formatDuration(item.duration)}</div>
                    <div className="text-gray-500">duration</div>
                  </div>
                  <div className="text-center">
                    <div className="font-medium text-xs">{item.aiProvider}</div>
                    <div className="text-gray-500">provider</div>
                  </div>
                  {getStatusBadge(item.status)}
                </div>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}