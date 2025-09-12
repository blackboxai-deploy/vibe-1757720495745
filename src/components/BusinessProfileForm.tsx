"use client";

import { useState } from "react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Button } from "@/components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Badge } from "@/components/ui/badge";

interface BusinessProfile {
  businessName: string;
  businessType: string;
  description: string;
  targetAudience: string;
  services: string;
  location: string;
  phone: string;
  email: string;
  website: string;
  tone: string;
  keywords: string[];
  usp: string;
}

export default function BusinessProfileForm() {
  const [profile, setProfile] = useState<BusinessProfile>({
    businessName: "",
    businessType: "",
    description: "",
    targetAudience: "",
    services: "",
    location: "",
    phone: "",
    email: "",
    website: "",
    tone: "",
    keywords: [],
    usp: ""
  });

  const [keywordInput, setKeywordInput] = useState("");

  const businessTypes = [
    "Restaurant", "E-commerce", "Healthcare", "Real Estate", "Technology", 
    "Education", "Legal Services", "Fitness", "Beauty", "Consulting", 
    "Manufacturing", "Travel", "Finance", "Non-Profit", "Other"
  ];

  const toneOptions = [
    "Professional", "Friendly", "Casual", "Authoritative", "Warm", 
    "Modern", "Traditional", "Creative", "Technical", "Conversational"
  ];

  const handleInputChange = (field: keyof BusinessProfile, value: string) => {
    setProfile(prev => ({ ...prev, [field]: value }));
  };

  const addKeyword = () => {
    if (keywordInput.trim() && !profile.keywords.includes(keywordInput.trim())) {
      setProfile(prev => ({
        ...prev,
        keywords: [...prev.keywords, keywordInput.trim()]
      }));
      setKeywordInput("");
    }
  };

  const removeKeyword = (keyword: string) => {
    setProfile(prev => ({
      ...prev,
      keywords: prev.keywords.filter(k => k !== keyword)
    }));
  };

  const handleSaveProfile = () => {
    // Save profile logic here
    console.log("Saving profile:", profile);
    alert("Business profile saved successfully!");
  };

  return (
    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
      {/* Basic Information */}
      <Card>
        <CardHeader>
          <CardTitle>Basic Information</CardTitle>
          <CardDescription>
            Enter your business details to personalize content generation
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="businessName">Business Name *</Label>
              <Input
                id="businessName"
                placeholder="e.g., Digital Marketing Pro"
                value={profile.businessName}
                onChange={(e) => handleInputChange("businessName", e.target.value)}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="businessType">Business Type *</Label>
              <Select 
                value={profile.businessType} 
                onValueChange={(value) => handleInputChange("businessType", value)}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select business type" />
                </SelectTrigger>
                <SelectContent>
                  {businessTypes.map((type) => (
                    <SelectItem key={type} value={type}>{type}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>

          <div className="space-y-2">
            <Label htmlFor="description">Business Description *</Label>
            <Textarea
              id="description"
              placeholder="Brief description of your business, what you do, and what makes you unique..."
              rows={3}
              value={profile.description}
              onChange={(e) => handleInputChange("description", e.target.value)}
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="targetAudience">Target Audience</Label>
            <Input
              id="targetAudience"
              placeholder="e.g., Small business owners, entrepreneurs, marketing managers"
              value={profile.targetAudience}
              onChange={(e) => handleInputChange("targetAudience", e.target.value)}
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="services">Services/Products</Label>
            <Textarea
              id="services"
              placeholder="List your main services or products..."
              rows={2}
              value={profile.services}
              onChange={(e) => handleInputChange("services", e.target.value)}
            />
          </div>
        </CardContent>
      </Card>

      {/* Contact & Brand Information */}
      <Card>
        <CardHeader>
          <CardTitle>Contact & Brand Details</CardTitle>
          <CardDescription>
            Contact information and brand personality settings
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="location">Location</Label>
              <Input
                id="location"
                placeholder="e.g., New York, USA"
                value={profile.location}
                onChange={(e) => handleInputChange("location", e.target.value)}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="phone">Phone</Label>
              <Input
                id="phone"
                placeholder="e.g., +1 (555) 123-4567"
                value={profile.phone}
                onChange={(e) => handleInputChange("phone", e.target.value)}
              />
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="email">Email</Label>
              <Input
                id="email"
                type="email"
                placeholder="e.g., info@yourbusiness.com"
                value={profile.email}
                onChange={(e) => handleInputChange("email", e.target.value)}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="website">Website</Label>
              <Input
                id="website"
                placeholder="e.g., https://yourbusiness.com"
                value={profile.website}
                onChange={(e) => handleInputChange("website", e.target.value)}
              />
            </div>
          </div>

          <div className="space-y-2">
            <Label htmlFor="tone">Brand Tone</Label>
            <Select 
              value={profile.tone} 
              onValueChange={(value) => handleInputChange("tone", value)}
            >
              <SelectTrigger>
                <SelectValue placeholder="Select brand tone" />
              </SelectTrigger>
              <SelectContent>
                {toneOptions.map((tone) => (
                  <SelectItem key={tone} value={tone}>{tone}</SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          <div className="space-y-2">
            <Label htmlFor="keywords">Keywords</Label>
            <div className="flex gap-2">
              <Input
                placeholder="Add keyword and press Enter"
                value={keywordInput}
                onChange={(e) => setKeywordInput(e.target.value)}
                onKeyPress={(e) => {
                  if (e.key === 'Enter') {
                    e.preventDefault();
                    addKeyword();
                  }
                }}
              />
              <Button type="button" onClick={addKeyword} variant="outline">
                Add
              </Button>
            </div>
            <div className="flex flex-wrap gap-2 mt-2">
              {profile.keywords.map((keyword) => (
                <Badge key={keyword} variant="secondary" className="cursor-pointer">
                  {keyword}
                  <button
                    type="button"
                    className="ml-2 hover:text-red-500"
                    onClick={() => removeKeyword(keyword)}
                  >
                    ×
                  </button>
                </Badge>
              ))}
            </div>
          </div>

          <div className="space-y-2">
            <Label htmlFor="usp">Unique Selling Proposition</Label>
            <Textarea
              id="usp"
              placeholder="What makes your business unique? Why should customers choose you?"
              rows={2}
              value={profile.usp}
              onChange={(e) => handleInputChange("usp", e.target.value)}
            />
          </div>
        </CardContent>
      </Card>

      {/* Save Button */}
      <div className="lg:col-span-2">
        <Card>
          <CardContent className="pt-6">
            <div className="flex justify-between items-center">
              <div>
                <h3 className="text-lg font-semibold">Save Business Profile</h3>
                <p className="text-sm text-gray-600">
                  This information will be used to generate personalized content for your website
                </p>
              </div>
              <Button onClick={handleSaveProfile} size="lg" className="px-8">
                Save Profile
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}