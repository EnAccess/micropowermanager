---
# https://vitepress.dev/reference/default-theme-home-page
layout: home
# Render the custom sections below full-width instead of inside VitePress's boxed `.vp-doc` container.
markdownStyles: false

hero:
  name: "MicroPowerManager"
  text: Managing off-grid utilities made simple
  tagline: Open Source software to automate customer, payment and device management for businesses of any size.
  actions:
    - theme: brand
      text: Get Started
      link: /get-started
    - theme: alt
      text: Try the Demo Version
      link: https://demo.micropowermanager.io/#/login
    - theme: alt
      text: Explore MPM Cloud
      link: /cloud
  image:
    src: /mpmlogo_stylized.svg
    alt: MicroPowerManager

features:
  - icon: { src: /icons/feature-manage-customers.svg, wrap: true }
    title: Manage your customers and devices
    details: Every customer, device and payment connected in one purpose-built database.
    link: "#customers"
  - icon: { src: /icons/feature-run-business.svg, wrap: true }
    title: Run your business
    details: Report to your team and your funders, resolve support cases and monitor performance in real time.
    link: "#operations"
  - icon: { src: /icons/feature-field-app.svg, wrap: true }
    title: Take MPM into the field
    details: Register customers, sell and collect payments on site with the MPM field App. Available on Android and iOS.
    link: "#field-app"
  - icon: { src: /icons/feature-open-source.svg, wrap: true }
    title: Join the Open Source community
    details: Self-host, extend with plugins and shape MPM together with the community.
    link: "#open-source"

highlights:
  - eyebrow: sales & operations
    anchor: customers
    header: Manage your customers and devices
    description: More than a traditional CRM. MicroPowerManager models the interactions a business runs on, linking every customer to their devices, tariffs, payment schemes and transactions in one place.
    image: /features/customer_detail.png
    highlightItems:
      - title: Centralized CRM database
        details: Every customer's details, devices and payment history in one place, accessible at a glance.
        icon: /icons/Icon_2_4Icon_2_3.png
      - title: Sales of goods through deferred payment
        details: Sell appliances and devices on Pay-as-you-go (PAYGO), rent-to-own or loan-based schemes as well as Energy as a Service (EaaS) contracts.
        icon: /icons/Icon_1_5Icon_1_1.png
      - title: Connect any payment method
        details: Collect through mobile money integrations or cash gathered by field agents, all reconciled against the customer.
        icon: /icons/Icon_1_6Icon_1_2.png
      - title: Two-way SMS messaging
        details: Reach every customer, a selected group or an individual for reminders, support and marketing campaigns.
        icon: /icons/Icon_2_6Icon_2_1.png

  - eyebrow: Analytics & Reporting
    anchor: operations
    header: Run your business
    description: Produce the reports your team and your funders rely on, resolve customer issues and track performance in real time.
    image: /features/main_dashboard.png
    highlightItems:
      - title: Reporting for your team and your funders
        details: Produce the operational reports you need day to day, and the data required by results-based financing (RBF) and other funding schemes.
        icon: /icons/Icon_3_4Icon_3_2.png
      - title: Built-in ticketing system
        details: Manage customer support cases in one place, saving time and operating cost.
        icon: /icons/Icon_2_5Icon_2_2.png
      - title: Real-time monitoring
        details: Follow revenue, payments and equipment status live, and optimize your assets in the most cost-effective way.
        icon: /icons/Icon_1_4Icon_1_3.png

  - eyebrow: MPM Field App
    anchor: field-app
    header: Take MPM into the field
    description: The MPM Field App lets your agents register customers, sell appliances and collect payments on site. Works online or offline.
    phoneScreen: /features/field_app_home.webp
    storeLinks:
      - store: google
        link: https://play.google.com/store/apps/details?id=io.micropowermanager.fieldapp
      - store: apple
        link: https://apps.apple.com/app/mpm-field-app/id6809758728
    learnMore:
      text: Learn more about the MPM Field App
      link: /usage-guide/field-app
    highlightItems:
      - title: Works offline
        details: Record data without signal, synced once back online.
        icon: /icons/Icon_3_5Icon_3_1.png
      - title: Payments and sales
        details: Collect payments and sell appliances on site.
        icon: /icons/Icon_1_5Icon_1_1.png
      - title: Register customers
        details: Sign up customers and link their meter or SHS.
        icon: /icons/Icon_2_4Icon_2_3.png

  - eyebrow: Open Source
    anchor: open-source
    header: Join the Open Source community
    description: Be part of something bigger. Contribute ideas, features or bugfixes resulting in constant improvements. Shape the future of MicroPowerManager.
    image: /features/shs_detail.png
    highlightItems:
      - title: MPM online Open Source community
        details: Get in touch via GitHub or Discord.
        icon: /icons/Icon_3_6Icon_3_3.png
      - title: Highly extensible plugin system
        details: Through its flexible plugin system MicroPowerManager adapts exactly to your utility's needs. Contribute your plugins to the Open Source project for everyone to benefit.
        icon: /icons/Icon_3_4Icon_3_2.png
      - title: Keep your data on servers that you own
        details: Run MicroPowerManager on your own servers so that you always know where your data is, who has access to it and how it is used.
        icon: /icons/Icon_3_5Icon_3_1.png
---

<script setup lang="ts">
import Highlight from "@theme/components/Highlight.vue"
import PageCta from "@theme/components/PageCta.vue"
</script>

<Highlight />

<div class="home-closing-cta">
  <PageCta
    title="Ready to simplify your off-grid business?"
    description="Run MicroPowerManager on your own servers, or let us host it for you with MPM Cloud."
  >
    <a href="/get-started" class="cta-btn">Get Started</a>
    <a href="/cloud" class="cta-btn cta-btn-outline">Explore MPM Cloud</a>
  </PageCta>
</div>

<style scoped>
.home-closing-cta {
  max-width: 1152px;
  margin: 0 auto;
  padding: 0 24px;
}

@media (min-width: 640px) {
  .home-closing-cta {
    padding: 0 48px;
  }
}

@media (min-width: 960px) {
  .home-closing-cta {
    max-width: calc(1152px + 2 * 64px);
    padding: 0 64px;
  }
}
</style>
