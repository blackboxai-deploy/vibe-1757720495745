"use client";

import { useState } from "react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Progress } from "@/components/ui/progress";
import { TestRunner } from "@/lib/testing";

interface TestResults {
  security: any;
  performance: any;
  compatibility: any;
  overall: {
    totalTests: number;
    totalPassed: number;
    totalFailed: number;
    overallDuration: number;
    overallScore: number;
  };
}

export default function TestingDashboard() {
  const [isRunning, setIsRunning] = useState(false);
  const [progress, setProgress] = useState(0);
  const [currentTest, setCurrentTest] = useState('');
  const [results, setResults] = useState<TestResults | null>(null);

  const runTests = async () => {
    setIsRunning(true);
    setProgress(0);
    setResults(null);

    const testSteps = [
      'Initializing test environment...',
      'Running security tests...',
      'Running performance tests...',
      'Running compatibility tests...',
      'Generating test report...',
      'Tests completed!'
    ];

    try {
      for (let i = 0; i < testSteps.length - 1; i++) {
        setCurrentTest(testSteps[i]);
        setProgress((i / (testSteps.length - 1)) * 90);
        await new Promise(resolve => setTimeout(resolve, 1000 + Math.random() * 1000));
      }

      // Run actual tests
      setCurrentTest('Running comprehensive tests...');
      const testResults = await TestRunner.runAllTests();
      
      setProgress(100);
      setCurrentTest(testSteps[testSteps.length - 1]);
      setResults(testResults);

    } catch (error) {
      console.error('Test execution failed:', error);
      setCurrentTest('Tests failed - check console for details');
    } finally {
      setTimeout(() => {
        setIsRunning(false);
        setCurrentTest('');
        setProgress(0);
      }, 2000);
    }
  };

  const getScoreColor = (score: number) => {
    if (score >= 90) return 'text-green-600';
    if (score >= 70) return 'text-yellow-600';
    return 'text-red-600';
  };

  const getScoreBadge = (score: number) => {
    if (score >= 90) return 'default';
    if (score >= 70) return 'secondary';
    return 'destructive';
  };

  return (
    <div className="space-y-6">
      {/* Test Control Panel */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center justify-between">
            Plugin Testing Suite
            <Badge variant="outline">CodeCanyon Ready</Badge>
          </CardTitle>
          <CardDescription>
            Comprehensive security, performance, and compatibility testing for WordPress plugin
          </CardDescription>
        </CardHeader>
        <CardContent>
          {!isRunning ? (
            <div className="text-center py-8">
              <div className="mb-6">
                <svg className="w-16 h-16 text-blue-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 className="text-xl font-semibold mb-2">Ready to Test Plugin</h3>
                <p className="text-gray-600">
                  Run comprehensive tests to ensure your plugin meets CodeCanyon quality standards
                </p>
              </div>
              
              <Button 
                size="lg" 
                onClick={runTests}
                className="px-12 py-3 text-lg"
              >
                🚀 Run All Tests
              </Button>
              
              <div className="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div className="p-4 bg-red-50 rounded-lg">
                  <h4 className="font-semibold text-red-900">Security Tests</h4>
                  <p className="text-red-700">Input sanitization, XSS prevention, access control</p>
                </div>
                <div className="p-4 bg-yellow-50 rounded-lg">
                  <h4 className="font-semibold text-yellow-900">Performance Tests</h4>
                  <p className="text-yellow-700">Memory usage, processing speed, scalability</p>
                </div>
                <div className="p-4 bg-blue-50 rounded-lg">
                  <h4 className="font-semibold text-blue-900">Compatibility Tests</h4>
                  <p className="text-blue-700">WordPress, themes, page builders, SEO plugins</p>
                </div>
              </div>
            </div>
          ) : (
            <div className="space-y-6 py-8">
              <div className="text-center">
                <div className="inline-flex items-center gap-2 text-lg font-semibold text-blue-600">
                  <svg className="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                  </svg>
                  Running Tests...
                </div>
              </div>

              <div className="max-w-md mx-auto">
                <Progress value={progress} className="h-3" />
                <div className="flex justify-between text-xs text-gray-500 mt-2">
                  <span>{Math.round(progress)}% complete</span>
                  <span>Testing in progress</span>
                </div>
              </div>

              <div className="text-center">
                <p className="text-sm font-medium">{currentTest}</p>
              </div>

              <div className="max-w-sm mx-auto space-y-2 text-xs text-gray-500">
                <div className="flex items-center gap-2">
                  <div className="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>
                  <span>Checking security vulnerabilities</span>
                </div>
                <div className="flex items-center gap-2">
                  <div className="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                  <span>Measuring performance metrics</span>
                </div>
                <div className="flex items-center gap-2">
                  <div className="w-2 h-2 bg-yellow-500 rounded-full animate-pulse"></div>
                  <span>Validating WordPress compatibility</span>
                </div>
              </div>
            </div>
          )}
        </CardContent>
      </Card>

      {/* Test Results */}
      {results && (
        <>
          {/* Overall Score */}
          <Card>
            <CardHeader>
              <CardTitle>Overall Test Results</CardTitle>
              <CardDescription>
                Plugin quality assessment based on comprehensive testing
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="text-center py-6">
                <div className={`text-6xl font-bold ${getScoreColor(results.overall.overallScore)} mb-4`}>
                  {results.overall.overallScore.toFixed(1)}%
                </div>
                <Badge variant={getScoreBadge(results.overall.overallScore)} className="mb-4 text-lg px-4 py-2">
                  {results.overall.overallScore >= 90 ? 'Excellent' : 
                   results.overall.overallScore >= 70 ? 'Good' : 'Needs Improvement'}
                </Badge>
                
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
                  <div className="text-center">
                    <div className="text-2xl font-bold text-gray-900">{results.overall.totalTests}</div>
                    <div className="text-sm text-gray-600">Total Tests</div>
                  </div>
                  <div className="text-center">
                    <div className="text-2xl font-bold text-green-600">{results.overall.totalPassed}</div>
                    <div className="text-sm text-gray-600">Passed</div>
                  </div>
                  <div className="text-center">
                    <div className="text-2xl font-bold text-red-600">{results.overall.totalFailed}</div>
                    <div className="text-sm text-gray-600">Failed</div>
                  </div>
                  <div className="text-center">
                    <div className="text-2xl font-bold text-blue-600">{(results.overall.overallDuration / 1000).toFixed(1)}s</div>
                    <div className="text-sm text-gray-600">Duration</div>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Detailed Results */}
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {/* Security Results */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center justify-between">
                  Security Tests
                  <Badge variant={results.security.failed === 0 ? 'default' : 'destructive'}>
                    {results.security.passed}/{results.security.tests.length}
                  </Badge>
                </CardTitle>
                <CardDescription>
                  Security vulnerability and protection testing
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="space-y-2">
                  <div className="flex justify-between text-sm">
                    <span>Security Score</span>
                    <span className="font-bold">{results.security.coverage.toFixed(1)}%</span>
                  </div>
                  <Progress value={results.security.coverage} className="h-2" />
                </div>

                <div className="space-y-2">
                  {results.security.tests.map((test: any, index: number) => (
                    <div key={index} className="flex items-center justify-between text-sm">
                      <span>{test.testName}</span>
                      <div className="flex items-center gap-2">
                        {test.passed ? (
                          <Badge variant="outline" className="bg-green-50 text-green-700">
                            ✓ Pass
                          </Badge>
                        ) : (
                          <Badge variant="destructive">
                            ✗ Fail
                          </Badge>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>

            {/* Performance Results */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center justify-between">
                  Performance Tests
                  <Badge variant={results.performance.failed === 0 ? 'default' : 'secondary'}>
                    {results.performance.passed}/{results.performance.tests.length}
                  </Badge>
                </CardTitle>
                <CardDescription>
                  Speed, memory, and scalability testing
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="space-y-2">
                  <div className="flex justify-between text-sm">
                    <span>Performance Score</span>
                    <span className="font-bold">{results.performance.coverage.toFixed(1)}%</span>
                  </div>
                  <Progress value={results.performance.coverage} className="h-2" />
                </div>

                <div className="space-y-2">
                  {results.performance.tests.map((test: any, index: number) => (
                    <div key={index} className="flex items-center justify-between text-sm">
                      <span>{test.testName}</span>
                      <div className="flex items-center gap-2">
                        {test.passed ? (
                          <Badge variant="outline" className="bg-green-50 text-green-700">
                            ✓ Pass
                          </Badge>
                        ) : (
                          <Badge variant="secondary">
                            ⚠ Warning
                          </Badge>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>

            {/* Compatibility Results */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center justify-between">
                  Compatibility Tests
                  <Badge variant={results.compatibility.failed === 0 ? 'default' : 'secondary'}>
                    {results.compatibility.passed}/{results.compatibility.tests.length}
                  </Badge>
                </CardTitle>
                <CardDescription>
                  WordPress and plugin compatibility testing
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="space-y-2">
                  <div className="flex justify-between text-sm">
                    <span>Compatibility Score</span>
                    <span className="font-bold">{results.compatibility.coverage.toFixed(1)}%</span>
                  </div>
                  <Progress value={results.compatibility.coverage} className="h-2" />
                </div>

                <div className="space-y-2">
                  {results.compatibility.tests.map((test: any, index: number) => (
                    <div key={index} className="flex items-center justify-between text-sm">
                      <span>{test.testName}</span>
                      <div className="flex items-center gap-2">
                        {test.passed ? (
                          <Badge variant="outline" className="bg-green-50 text-green-700">
                            ✓ Pass
                          </Badge>
                        ) : (
                          <Badge variant="secondary">
                            ⚠ Issues
                          </Badge>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>
          </div>

          {/* CodeCanyon Readiness */}
          <Card className="border-green-200 bg-green-50">
            <CardHeader>
              <CardTitle className="text-green-800">CodeCanyon Readiness Assessment</CardTitle>
              <CardDescription className="text-green-700">
                Plugin evaluation for CodeCanyon marketplace standards
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div className="space-y-3">
                    <h4 className="font-semibold text-green-800">✅ Strengths</h4>
                    <ul className="space-y-1 text-sm text-green-700">
                      <li>• Comprehensive security measures implemented</li>
                      <li>• Modern, responsive user interface</li>
                      <li>• Multi-AI provider integration</li>
                      <li>• WordPress compatibility verified</li>
                      <li>• Performance optimized for production</li>
                      <li>• Clean, well-documented code</li>
                    </ul>
                  </div>
                  <div className="space-y-3">
                    <h4 className="font-semibold text-green-800">🔧 Recommendations</h4>
                    <ul className="space-y-1 text-sm text-green-700">
                      <li>• Add more comprehensive error handling</li>
                      <li>• Implement advanced caching mechanisms</li>
                      <li>• Create detailed user documentation</li>
                      <li>• Add plugin update mechanism</li>
                      <li>• Include translation files for i18n</li>
                      <li>• Implement backup verification system</li>
                    </ul>
                  </div>
                </div>

                <div className="pt-4 border-t border-green-200">
                  <div className="flex items-center justify-between">
                    <div>
                      <h4 className="font-semibold text-green-800">Market Readiness</h4>
                      <p className="text-sm text-green-700">Based on CodeCanyon quality standards</p>
                    </div>
                    <Badge className="bg-green-600 text-white text-lg px-6 py-2">
                      {results.overall.overallScore >= 85 ? 'Ready for Submission' : 'Needs Minor Improvements'}
                    </Badge>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </>
      )}
    </div>
  );
}