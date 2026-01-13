---
title: Roadmap
layout: page
exclude_from_sidebar: true
lastUpdated: January 2026
releases:
  - version: "MPM v1.0"
    title: "Stability and Off-Grid Readiness"
    target: "End of Q1 2026"
    description: "Deliver a stable, production-ready version of MPM tailored for off-grid use cases. We want to make MPM dependable for real-world operators and new adopters."
    milestones:
      - id: "transaction-abstraction"
        title: "Transaction Abstraction Revamp"
        description: "Complete overhaul of the transaction handling system for better flexibility and provider support."
        status: "in-progress"
        category: "Core"
        github:
          issue: 123
      - id: "rbac"
        title: "Role-Based Access Control (RBAC)"
        description: "Comprehensive role definitions and access categories with UI and mobile API integration."
        status: "completed"
        category: "Security"
        features:
          - title: "Role definitions and access categories"
          - title: "UI integration"
          - title: "Mobile API integration"
        github:
          issue: 233
      - id: "shs-integration"
        title: "Full SHS Integration"
        description: "Extended Solar Home System functionality with improved mini-grid abstraction layers."
        status: "planned"
        category: "SHS"
        features:
          - title: "Extended mobile app functionality"
          - title: "Improved mini-grid abstraction layers"
      - id: "payment-vodacom"
        title: "Vodacom Mobile Money"
        description: "Enhanced payment capabilities for Vodacom Mobile Money integration."
        status: "in-progress"
        category: "Payments"
        github:
          issue: 78
      - id: "payment-paystack"
        title: "Paystack (Nigeria)"
        description: "Payment gateway integration for Paystack to support Nigerian operations."
        status: "planned"
        category: "Payments"
        github:
          issue: 176
      - id: "sms-gateway"
        title: "SMS Gateway Support"
        description: "Alternative SMS gateway as an option to external providers for more control."
        status: "in-progress"
        category: "Communications"
        github:
          issue: 1104
      - id: "branding-update"
        title: "Updated MPM Branding"
        description: "Moving away from legacy Inensus references to unified MPM branding."
        status: "in-progress"
        category: "UX"
        features:
          - title: "Moving away from legacy Inensus references to unified MPM branding."
          - title: "Apply EnAccess branding"
        github:
          issue: 213
      - id: "prospect-integration"
        title: "Prospect Platform Integration"
        description: "Full integration with the Prospect platform for seamless data exchange."
        status: "in-progress"
        category: "Integrations"
        github:
          issue: 804
      - id: "rest-api"
        title: "REST API for Import/Export"
        description: "Comprehensive REST API endpoints for data import and export operations."
        status: "in-progress"
        category: "API"
        github:
          issue: 494
      - id: "docs-revamp"
        title: "Documentation Overhaul"
        description: "Revamped usage and onboarding guides for better developer and operator experience."
        status: "planned"
        category: "Docs"

  - version: "MPM v1.1"
    title: "Broader Use and Ecosystem Growth"
    target: "End of 2026"
    description: "Expand MPM's coverage for diverse use cases, improve developer experience, and strengthen integration options."
    milestones:
      - id: "plugin-system"
        title: "Plugin System Re-architecture"
        description: "Easier plugin development and maintenance with a revamped plugin architecture."
        status: "exploring"
        category: "Architecture"
        features:
          - title: "Simplified plugin development"
          - title: "Better plugin isolation"
          - title: "Hot-reload support"
      - id: "native-sms"
        title: "Native SMS Support"
        description: "Built-in SMS capabilities within MPM Core without external dependencies."
        status: "planned"
        category: "Communications"
      - id: "open-paygo"
        title: "Open PayGo Device Integration"
        description: "Native support for Open PayGo standard devices."
        status: "planned"
        category: "Devices"
      - id: "post-paid"
        title: "Post-Paid Metering"
        description: "Exploration and implementation of post-paid metering capabilities."
        status: "exploring"
        category: "Metering"
      - id: "payment-pesapal"
        title: "PesaPal (Uganda)"
        description: "Payment gateway integration for PesaPal to support Ugandan operations."
        status: "planned"
        category: "Payments"
      - id: "payment-safaricom"
        title: "Safaricom (Kenya)"
        description: "M-Pesa integration through Safaricom for Kenyan market support."
        status: "planned"
        category: "Payments"
      - id: "tou-pricing"
        title: "Time-of-Use (ToU) Pricing"
        description: "Dynamic pricing support based on time of use for flexible tariff structures."
        status: "planned"
        category: "Billing"
      - id: "energy-monitoring"
        title: "Energy Monitoring"
        description: "Native or third-party integrations for energy consumption monitoring."
        status: "exploring"
        category: "Monitoring"
      - id: "backend-refactor"
        title: "Backend Refactoring"
        description: "Scalability improvements and code quality enhancements."
        status: "planned"
        category: "Architecture"
      - id: "inventory-mgmt"
        title: "Inventory & Deployment Management"
        description: "Tools for tracking inventory and managing device deployments."
        status: "exploring"
        category: "Operations"
      - id: "cloud-status"
        title: "Cloud Service Status Tracker"
        description: "Real-time status and availability monitoring for cloud services."
        status: "planned"
        category: "DevOps"
      - id: "vue3-migration"
        title: "Vue 3 Migration"
        description: "Frontend upgrade from Vue 2 to Vue 3 for modern development patterns."
        status: "planned"
        category: "Frontend"
        features:
          - title: "Vue 3 Composition API"
          - title: "UI overhaul"
          - title: "Modern, intuitive design"

  - version: "MPM v2.0"
    title: "Scaling for the Future"
    target: "2027"
    description: "Evolve MPM into a platform capable of supporting large-scale operations — from off-grid operators to energy manufacturers."
    milestones:
      - id: "plugin-isolation"
        title: "Tenant-level Plugin Isolation"
        description: "Opt-in/opt-out plugin configuration per tenant instance."
        status: "exploring"
        category: "Multi-tenancy"
      - id: "operator-dashboard"
        title: "Operator Dashboard"
        description: "Advanced analytics interface designed for operators managing multiple sites."
        status: "exploring"
        category: "Analytics"
      - id: "multi-operator"
        title: "Multi-Operator Environments"
        description: "Support for large-scale, multi-operator deployments with isolated data."
        status: "exploring"
        category: "Enterprise"
---

<script setup>
import Roadmap from '@theme/components/Roadmap.vue'
</script>

<Roadmap />
