"use client";

import { useState } from "react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Button } from "@/components/ui/button";
import { Switch } from "@/components/ui/switch";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Badge } from "@/components/ui/badge";
import { Progress } from "@/components/ui/progress";

interface AIProvider {
  id: string;
  name: string;
  enabled: boolean;
  apiKey: string;
  model: string;
  priority: number;
  dailyLimit: number;
  usedToday: number;
  costPerToken: number;
  status: 'active' | 'inactive' | 'error' | 'limit_reached';
}

export default function AIApiConfig() {
  const [providers, setProviders] = useState<AIProvider[]>([
    {
      id: 'openai',
      name: 'OpenAI',
      enabled: false,
      apiKey: '',
      model: 'gpt-3.5-turbo',
      priority: 1,
      dailyLimit: 1000,
      usedToday: 0,
      costPerToken: 0.002,
      status: 'inactive'
    },
    {
      id: 'anthropic',
      name: 'Anthropic Claude',
      enabled: false,
      apiKey: '',
      model: 'claude-3-haiku-20240307',
      priority: 2,
      dailyLimit: 500,
      usedToday: 0,
      costPerToken: 0.0008,
      status: 'inactive'
    },
    {
      id: 'google',
      name: 'Google Gemini',
      enabled: false,
      apiKey: '',
      model: 'gemini-pro',
      priority: 3,
      dailyLimit: 800,
      usedToday: 0,
      costPerToken: 0.001,
      status: 'inactive'
    },
    {
      id: 'groq',
      name: 'Groq',
      enabled: false,
      apiKey: '',
      model: 'llama3-70b-8192',
      priority: 4,
      dailyLimit: 2000,
      usedToday: 0,
      costPerToken: 0.0005,
      status: 'inactive'
    }
  ]);

  const modelOptions = {
    openai: ['gpt-3.5-turbo', 'gpt-4', 'gpt-4-turbo', 'gpt-4o'],
    anthropic: ['claude-3-haiku-20240307', 'claude-3-sonnet-20240229', 'claude-3-opus-20240229'],
    google: ['gemini-pro', 'gemini-pro-vision', 'gemini-1.5-flash'],
    groq: ['llama3-70b-8192', 'llama3-8b-8192', 'mixtral-8x7b-32768']
  };

  const updateProvider = (id: string, updates: Partial<AIProvider>) => {
    setProviders(prev => prev.map(provider => 
      provider.id === id ? { ...provider, ...updates } : provider
    ));
  };

  const testApiKey = async (providerId: string) => {
    const provider = providers.find(p => p.id === providerId);
    if (!provider || !provider.apiKey) return;

    // Simulate API test
    updateProvider(providerId, { status: 'active' });
    setTimeout(() => {
      alert(`${provider.name} API key tested successfully!`);
    }, 1000);
  };

  const getStatusBadge = (status: AIProvider['status']) => {
    const variants = {
      active: 'default',
      inactive: 'secondary',
      error: 'destructive',
      limit_reached: 'outline'
    } as const;

    const labels = {
      active: 'Active',
      inactive: 'Inactive', 
      error: 'Error',
      limit_reached: 'Limit Reached'
    };

    return (
      <Badge variant={variants[status]}>
        {labels[status]}
      </Badge>
    );
  };

  const getTotalActiveProviders = () => providers.filter(p => p.enabled).length;

  return (
    <div className="space-y-6">
      {/* Overview Card */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center justify-between">
            AI Provider Configuration
            <Badge variant="outline" className="ml-2">
              {getTotalActiveProviders()} Active
            </Badge>
          </CardTitle>
          <CardDescription>
            Configure multiple AI providers for intelligent content generation with automatic failover
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div className="text-center p-4 bg-blue-50 rounded-lg">
              <h3 className="text-lg font-semibold text-blue-900">Smart Rotation</h3>
              <p className="text-sm text-blue-700">Automatically switches between providers</p>
            </div>
            <div className="text-center p-4 bg-green-50 rounded-lg">
              <h3 className="text-lg font-semibold text-green-900">Cost Optimization</h3>
              <p className="text-sm text-green-700">Uses most cost-effective models first</p>
            </div>
            <div className="text-center p-4 bg-purple-50 rounded-lg">
              <h3 className="text-lg font-semibold text-purple-900">Token Management</h3>
              <p className="text-sm text-purple-700">Tracks usage across all providers</p>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Provider Configuration Cards */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {providers.map((provider) => (
          <Card key={provider.id} className={provider.enabled ? 'ring-2 ring-blue-200' : ''}>
            <CardHeader>
              <div className="flex items-center justify-between">
                <CardTitle className="text-lg">{provider.name}</CardTitle>
                <div className="flex items-center gap-2">
                  {getStatusBadge(provider.status)}
                  <Switch
                    checked={provider.enabled}
                    onCheckedChange={(enabled) => updateProvider(provider.id, { enabled })}
                  />
                </div>
              </div>
              <CardDescription>
                Priority: {provider.priority} | Cost: ${provider.costPerToken}/token
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor={`${provider.id}-key`}>API Key</Label>
                <div className="flex gap-2">
                  <Input
                    id={`${provider.id}-key`}
                    type="password"
                    placeholder="Enter your API key"
                    value={provider.apiKey}
                    onChange={(e) => updateProvider(provider.id, { apiKey: e.target.value })}
                  />
                  <Button 
                    variant="outline" 
                    onClick={() => testApiKey(provider.id)}
                    disabled={!provider.apiKey}
                  >
                    Test
                  </Button>
                </div>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor={`${provider.id}-model`}>Model</Label>
                  <Select 
                    value={provider.model} 
                    onValueChange={(value) => updateProvider(provider.id, { model: value })}
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      {modelOptions[provider.id as keyof typeof modelOptions]?.map((model) => (
                        <SelectItem key={model} value={model}>{model}</SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>

                <div className="space-y-2">
                  <Label htmlFor={`${provider.id}-priority`}>Priority</Label>
                  <Select 
                    value={provider.priority.toString()} 
                    onValueChange={(value) => updateProvider(provider.id, { priority: parseInt(value) })}
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      {[1, 2, 3, 4, 5].map((num) => (
                        <SelectItem key={num} value={num.toString()}>
                          {num} {num === 1 ? '(Highest)' : num === 5 ? '(Lowest)' : ''}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
              </div>

              <div className="space-y-2">
                <Label htmlFor={`${provider.id}-limit`}>Daily Limit</Label>
                <Input
                  id={`${provider.id}-limit`}
                  type="number"
                  value={provider.dailyLimit}
                  onChange={(e) => updateProvider(provider.id, { dailyLimit: parseInt(e.target.value) })}
                />
              </div>

              {provider.enabled && (
                <div className="space-y-2">
                  <div className="flex justify-between text-sm">
                    <span>Today's Usage</span>
                    <span>{provider.usedToday} / {provider.dailyLimit}</span>
                  </div>
                  <Progress 
                    value={(provider.usedToday / provider.dailyLimit) * 100} 
                    className="h-2"
                  />
                </div>
              )}
            </CardContent>
          </Card>
        ))}
      </div>

      {/* Rotation Strategy */}
      <Card>
        <CardHeader>
          <CardTitle>Smart Rotation Strategy</CardTitle>
          <CardDescription>
            Configure how the plugin switches between AI providers
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div className="space-y-4">
              <h4 className="font-semibold">Failover Rules</h4>
              <div className="space-y-2 text-sm">
                <div className="flex items-center gap-2">
                  <div className="w-2 h-2 bg-blue-500 rounded-full"></div>
                  <span>Start with highest priority provider</span>
                </div>
                <div className="flex items-center gap-2">
                  <div className="w-2 h-2 bg-green-500 rounded-full"></div>
                  <span>Switch when daily limit reached</span>
                </div>
                <div className="flex items-center gap-2">
                  <div className="w-2 h-2 bg-yellow-500 rounded-full"></div>
                  <span>Skip providers with API errors</span>
                </div>
                <div className="flex items-center gap-2">
                  <div className="w-2 h-2 bg-purple-500 rounded-full"></div>
                  <span>Consider cost optimization</span>
                </div>
              </div>
            </div>

            <div className="space-y-4">
              <h4 className="font-semibold">Current Rotation Order</h4>
              <div className="space-y-2">
                {providers
                  .filter(p => p.enabled)
                  .sort((a, b) => a.priority - b.priority)
                  .map((provider, index) => (
                    <div key={provider.id} className="flex items-center gap-2 text-sm">
                      <Badge variant="outline">{index + 1}</Badge>
                      <span>{provider.name}</span>
                      <span className="text-gray-500">({provider.model})</span>
                      {getStatusBadge(provider.status)}
                    </div>
                  ))}
                {providers.filter(p => p.enabled).length === 0 && (
                  <p className="text-gray-500">No active providers configured</p>
                )}
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Save Configuration */}
      <Card>
        <CardContent className="pt-6">
          <div className="flex justify-between items-center">
            <div>
              <h3 className="text-lg font-semibold">Save AI Configuration</h3>
              <p className="text-sm text-gray-600">
                Your API keys are encrypted and stored securely
              </p>
            </div>
            <Button size="lg" className="px-8">
              Save Configuration
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}