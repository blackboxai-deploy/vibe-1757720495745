"use client";

import { useState } from "react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Progress } from "@/components/ui/progress";
import { Switch } from "@/components/ui/switch";
import { Label } from "@/components/ui/label";

interface ContentItem {
  id: string;
  type: 'page' | 'post' | 'widget';
  title: string;
  wordCount: number;
  pageBuilder: string;
  status: 'pending' | 'processing' | 'completed' | 'error';
  originalContent: string;
  newContent?: string;
}

interface ProcessingOptions {
  includePages: boolean;
  includePosts: boolean;
  includeWidgets: boolean;
  preserveImages: boolean;
  optimizeSEO: boolean;
  backupOriginal: boolean;
}

export default function ContentProcessor() {
  const [isProcessing, setIsProcessing] = useState(false);
  const [progress, setProgress] = useState(0);
  const [currentStep, setCurrentStep] = useState('');
  const [currentAIProvider, setCurrentAIProvider] = useState('OpenAI');
  
  const [options, setOptions] = useState<ProcessingOptions>({
    includePages: true,
    includePosts: true,
    includeWidgets: false,
    preserveImages: true,
    optimizeSEO: true,
    backupOriginal: true
  });

  const [detectedContent] = useState<ContentItem[]>([
    {
      id: '1',
      type: 'page',
      title: 'Home Page',
      wordCount: 450,
      pageBuilder: 'Elementor',
      status: 'pending',
      originalContent: 'Welcome to our amazing website...'
    },
    {
      id: '2',
      type: 'page',
      title: 'About Us',
      wordCount: 320,
      pageBuilder: 'Gutenberg',
      status: 'pending',
      originalContent: 'We are a company that provides...'
    },
    {
      id: '3',
      type: 'page',
      title: 'Services',
      wordCount: 580,
      pageBuilder: 'Elementor',
      status: 'pending',
      originalContent: 'Our services include...'
    },
    {
      id: '4',
      type: 'page',
      title: 'Contact',
      wordCount: 180,
      pageBuilder: 'Gutenberg',
      status: 'pending',
      originalContent: 'Get in touch with us...'
    },
    {
      id: '5',
      type: 'post',
      title: 'Welcome Post',
      wordCount: 250,
      pageBuilder: 'Gutenberg',
      status: 'pending',
      originalContent: 'This is a sample blog post...'
    }
  ]);

  const handleStartProcessing = async () => {
    setIsProcessing(true);
    setProgress(0);
    
    const steps = [
      'Analyzing existing content...',
      'Loading business profile...',
      'Connecting to AI providers...',
      'Processing with OpenAI...',
      'Generating personalized content...',
      'Preserving page layouts...',
      'Optimizing for SEO...',
      'Creating backups...',
      'Finalizing changes...'
    ];

    for (let i = 0; i < steps.length; i++) {
      setCurrentStep(steps[i]);
      
      // Simulate API switching at step 4
      if (i === 4) {
        setCurrentAIProvider('Anthropic Claude');
        await new Promise(resolve => setTimeout(resolve, 1000));
        setCurrentStep('Switching to Anthropic Claude...');
        await new Promise(resolve => setTimeout(resolve, 1000));
        setCurrentStep('Continuing content generation...');
      }
      
      // Simulate progress
      await new Promise(resolve => setTimeout(resolve, 2000));
      setProgress(((i + 1) / steps.length) * 100);
    }

    setCurrentStep('Processing completed successfully!');
    setTimeout(() => {
      setIsProcessing(false);
      setCurrentStep('');
      setProgress(0);
      alert('Content processing completed! All pages have been updated with your business information.');
    }, 2000);
  };

  const updateOption = (key: keyof ProcessingOptions, value: boolean) => {
    setOptions(prev => ({ ...prev, [key]: value }));
  };

  const getContentTypeIcon = (type: ContentItem['type']) => {
    switch (type) {
      case 'page':
        return (
          <svg className="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
        );
      case 'post':
        return (
          <svg className="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
          </svg>
        );
      case 'widget':
        return (
          <svg className="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
          </svg>
        );
    }
  };

  const getStatusBadge = (status: ContentItem['status']) => {
    const variants = {
      pending: 'secondary',
      processing: 'default',
      completed: 'default',
      error: 'destructive'
    } as const;

    const colors = {
      pending: 'bg-gray-100 text-gray-800',
      processing: 'bg-blue-100 text-blue-800',
      completed: 'bg-green-100 text-green-800',
      error: 'bg-red-100 text-red-800'
    };

    return (
      <Badge variant={variants[status]} className={colors[status]}>
        {status.charAt(0).toUpperCase() + status.slice(1)}
      </Badge>
    );
  };

  return (
    <div className="space-y-6">
      {/* Processing Options */}
      <Card>
        <CardHeader>
          <CardTitle>Content Processing Options</CardTitle>
          <CardDescription>
            Configure what content to process and how to handle it
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div className="space-y-4">
              <h4 className="font-semibold">Content Types</h4>
              <div className="space-y-3">
                <div className="flex items-center justify-between">
                  <Label htmlFor="includePages">Pages</Label>
                  <Switch
                    id="includePages"
                    checked={options.includePages}
                    onCheckedChange={(checked) => updateOption('includePages', checked)}
                  />
                </div>
                <div className="flex items-center justify-between">
                  <Label htmlFor="includePosts">Posts</Label>
                  <Switch
                    id="includePosts"
                    checked={options.includePosts}
                    onCheckedChange={(checked) => updateOption('includePosts', checked)}
                  />
                </div>
                <div className="flex items-center justify-between">
                  <Label htmlFor="includeWidgets">Widgets</Label>
                  <Switch
                    id="includeWidgets"
                    checked={options.includeWidgets}
                    onCheckedChange={(checked) => updateOption('includeWidgets', checked)}
                  />
                </div>
              </div>
            </div>

            <div className="space-y-4">
              <h4 className="font-semibold">Processing Options</h4>
              <div className="space-y-3">
                <div className="flex items-center justify-between">
                  <Label htmlFor="preserveImages">Preserve Images</Label>
                  <Switch
                    id="preserveImages"
                    checked={options.preserveImages}
                    onCheckedChange={(checked) => updateOption('preserveImages', checked)}
                  />
                </div>
                <div className="flex items-center justify-between">
                  <Label htmlFor="optimizeSEO">SEO Optimization</Label>
                  <Switch
                    id="optimizeSEO"
                    checked={options.optimizeSEO}
                    onCheckedChange={(checked) => updateOption('optimizeSEO', checked)}
                  />
                </div>
                <div className="flex items-center justify-between">
                  <Label htmlFor="backupOriginal">Backup Original</Label>
                  <Switch
                    id="backupOriginal"
                    checked={options.backupOriginal}
                    onCheckedChange={(checked) => updateOption('backupOriginal', checked)}
                  />
                </div>
              </div>
            </div>

            <div className="space-y-4">
              <h4 className="font-semibold">Summary</h4>
              <div className="text-sm space-y-2">
                <div className="flex justify-between">
                  <span>Pages to process:</span>
                  <span className="font-medium">{detectedContent.filter(c => c.type === 'page').length}</span>
                </div>
                <div className="flex justify-between">
                  <span>Posts to process:</span>
                  <span className="font-medium">{detectedContent.filter(c => c.type === 'post').length}</span>
                </div>
                <div className="flex justify-between">
                  <span>Total words:</span>
                  <span className="font-medium">{detectedContent.reduce((sum, item) => sum + item.wordCount, 0)}</span>
                </div>
                <div className="flex justify-between">
                  <span>Estimated tokens:</span>
                  <span className="font-medium">~{Math.round(detectedContent.reduce((sum, item) => sum + item.wordCount, 0) * 1.3)}</span>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Detected Content */}
      <Card>
        <CardHeader>
          <CardTitle>Detected Content</CardTitle>
          <CardDescription>
            Content found in your WordPress site that will be processed
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {detectedContent.map((item) => (
              <div key={item.id} className="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50">
                <div className="flex items-center gap-3">
                  {getContentTypeIcon(item.type)}
                  <div>
                    <h4 className="font-medium">{item.title}</h4>
                    <p className="text-sm text-gray-500">
                      {item.wordCount} words • Built with {item.pageBuilder}
                    </p>
                  </div>
                </div>
                <div className="flex items-center gap-3">
                  {getStatusBadge(item.status)}
                  <Badge variant="outline" className="text-xs">
                    {item.type}
                  </Badge>
                </div>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>

      {/* Processing Control */}
      <Card>
        <CardHeader>
          <CardTitle>Start Content Processing</CardTitle>
          <CardDescription>
            One-click AI-powered content replacement with your business information
          </CardDescription>
        </CardHeader>
        <CardContent>
          {!isProcessing ? (
            <div className="text-center py-8">
              <div className="mb-6">
                <svg className="w-16 h-16 text-blue-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                <h3 className="text-xl font-semibold mb-2">Ready to Transform Your Content</h3>
                <p className="text-gray-600 max-w-md mx-auto">
                  Click the button below to replace all template content with your personalized business information using AI
                </p>
              </div>
              
              <Button 
                size="lg" 
                onClick={handleStartProcessing}
                className="px-12 py-3 text-lg"
              >
                🚀 Start AI Content Replacement
              </Button>
              
              <div className="mt-4 text-sm text-gray-500">
                <p>✅ Business profile configured</p>
                <p>✅ AI providers ready</p>
                <p>✅ {detectedContent.length} items detected</p>
              </div>
            </div>
          ) : (
            <div className="space-y-6 py-8">
              <div className="text-center">
                <div className="inline-flex items-center gap-2 text-lg font-semibold text-blue-600">
                  <svg className="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                  </svg>
                  Processing Content...
                </div>
                <p className="text-sm text-gray-600 mt-1">Using {currentAIProvider}</p>
              </div>

              <div className="max-w-md mx-auto">
                <Progress value={progress} className="h-3" />
                <div className="flex justify-between text-xs text-gray-500 mt-2">
                  <span>{Math.round(progress)}% complete</span>
                  <span>Step {Math.ceil((progress / 100) * 9)}/9</span>
                </div>
              </div>

              <div className="text-center">
                <p className="text-sm font-medium">{currentStep}</p>
              </div>

              <div className="max-w-sm mx-auto space-y-2 text-xs text-gray-500">
                <div className="flex items-center gap-2">
                  <div className="w-2 h-2 bg-green-500 rounded-full"></div>
                  <span>Design layouts preserved</span>
                </div>
                <div className="flex items-center gap-2">
                  <div className="w-2 h-2 bg-green-500 rounded-full"></div>
                  <span>SEO optimization active</span>
                </div>
                <div className="flex items-center gap-2">
                  <div className="w-2 h-2 bg-green-500 rounded-full"></div>
                  <span>Original content backed up</span>
                </div>
                <div className="flex items-center gap-2">
                  <div className="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>
                  <span>Smart AI rotation active</span>
                </div>
              </div>
            </div>
          )}
        </CardContent>
      </Card>

      {/* Warning Card */}
      <Card className="border-yellow-200 bg-yellow-50">
        <CardContent className="pt-6">
          <div className="flex items-start gap-3">
            <svg className="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
            <div>
              <h4 className="font-semibold text-yellow-800">Important Notes</h4>
              <ul className="text-sm text-yellow-700 mt-2 space-y-1">
                <li>• Original content will be backed up before processing</li>
                <li>• Page layouts and designs will be preserved</li>
                <li>• Processing may take several minutes depending on content volume</li>
                <li>• You can restore original content anytime from the backup</li>
              </ul>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}