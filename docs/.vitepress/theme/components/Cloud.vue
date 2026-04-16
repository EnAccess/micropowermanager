<template>
  <div class="cloud-container">
    <PageHero
      badge="Fully Managed"
      title="MPM Cloud"
      tagline="Focus on your energy business, not on managing infrastructure. MPM Cloud is the fully hosted version of MicroPowerManager — set up in minutes, maintained by us."
    >
      <a
        href="https://cloud.micropowermanager.io"
        target="_blank"
        class="hero-btn hero-btn-primary"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          width="18"
          height="18"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        >
          <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
          <polyline points="15 3 21 3 21 9" />
          <line x1="10" x2="21" y1="14" y2="3" />
        </svg>
        Get Started
      </a>
      <a
        href="https://demo.micropowermanager.io/#/login"
        target="_blank"
        class="hero-btn hero-btn-secondary"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          width="18"
          height="18"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        >
          <polygon points="5 3 19 12 5 21 5 3" />
        </svg>
        Try the Demo
      </a>
    </PageHero>

    <!-- Benefits Section -->
    <div class="benefits-section">
      <h2 class="section-title">Why MPM Cloud?</h2>
      <p class="section-subtitle">
        All the power of MicroPowerManager without the operational overhead.
      </p>
      <div class="benefits-grid">
        <div class="benefit-card">
          <div class="benefit-icon">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="28"
              height="28"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
            </svg>
          </div>
          <h3>Managed &amp; Secure</h3>
          <p>
            We handle updates, backups, and security patches so your team can
            focus on serving customers.
          </p>
        </div>
        <div class="benefit-card">
          <div class="benefit-icon">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="28"
              height="28"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <polyline points="22 12 18 12 15 21 9 3 6 12 2 12" />
            </svg>
          </div>
          <h3>Always Available</h3>
          <p>
            Built for reliability with monitoring and redundancy. Your
            operations keep running even when connectivity is limited.
          </p>
        </div>
        <div class="benefit-card">
          <div class="benefit-icon">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="28"
              height="28"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <circle cx="12" cy="12" r="10" />
              <line x1="2" x2="22" y1="12" y2="12" />
              <path
                d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"
              />
            </svg>
          </div>
          <h3>Ready in Minutes</h3>
          <p>
            No servers to provision, no Docker to configure. Sign up and start
            managing your utility right away.
          </p>
        </div>
        <div class="benefit-card">
          <div class="benefit-icon">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="28"
              height="28"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <path
                d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"
              />
              <polyline points="3.27 6.96 12 12.01 20.73 6.96" />
              <line x1="12" x2="12" y1="22.08" y2="12" />
            </svg>
          </div>
          <h3>Same Features</h3>
          <p>
            Everything you get with self-hosted MPM — metering, payments, CRM,
            SMS, ticketing — all included.
          </p>
        </div>
      </div>
    </div>

    <!-- Comparison Section -->
    <div class="comparison-section">
      <h2 class="section-title">Cloud vs. Self-Hosted</h2>
      <p class="section-subtitle">
        Choose the deployment that fits your organization.
      </p>
      <div class="comparison-table-wrapper">
        <table class="comparison-table">
          <thead>
            <tr>
              <th></th>
              <th class="cloud-col">
                <span class="col-badge cloud-badge">MPM Cloud</span>
              </th>
              <th>
                <span class="col-badge self-badge">Self-Hosted</span>
              </th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Setup time</td>
              <td class="cloud-col">Minutes</td>
              <td>Hours to days</td>
            </tr>
            <tr>
              <td>Server management</td>
              <td class="cloud-col">Handled by us</td>
              <td>Your responsibility</td>
            </tr>
            <tr>
              <td>Automatic updates</td>
              <td class="cloud-col"><span class="check">&#10003;</span></td>
              <td>Manual</td>
            </tr>
            <tr>
              <td>Backups</td>
              <td class="cloud-col">
                <span class="check">&#10003;</span> Automatic
              </td>
              <td>Your responsibility</td>
            </tr>
            <tr>
              <td>SSL / Security</td>
              <td class="cloud-col">
                <span class="check">&#10003;</span> Included
              </td>
              <td>Your responsibility</td>
            </tr>
            <tr>
              <td>Full feature set</td>
              <td class="cloud-col"><span class="check">&#10003;</span></td>
              <td><span class="check">&#10003;</span></td>
            </tr>
            <tr>
              <td>Custom plugins</td>
              <td class="cloud-col">
                <span class="cross">&#10005;</span>
                <sup class="footnote-marker">*</sup>
              </td>
              <td><span class="check">&#10003;</span></td>
            </tr>
            <tr>
              <td>Data ownership</td>
              <td class="cloud-col">Your data, our infrastructure</td>
              <td>Your data, your infrastructure</td>
            </tr>
          </tbody>
        </table>
      </div>
      <p class="comparison-footnote">
        <span class="footnote-marker">*</span> MPM Cloud runs the official
        mainline release. Have a feature suggestion or request? Contribute to
        the
        <a href="https://github.com/EnAccess/micropowermanager" target="_blank"
          >Open Source project</a
        >
        — every improvement benefits the entire community.
      </p>
    </div>

    <!-- What to Expect Section -->
    <div class="expectations-section">
      <h2 class="section-title">What to Expect</h2>
      <p class="section-subtitle">
        Transparency matters. Here is how we run MPM Cloud and what you can
        count on.
      </p>
      <div class="expectations-grid">
        <div class="expectation-card highlighted">
          <div class="expectation-icon">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="24"
              height="24"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <line x1="12" x2="12" y1="1" y2="23" />
              <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
            </svg>
          </div>
          <h3>Pricing</h3>
          <p>
            MPM Cloud is currently free for small utilities getting started. We
            want to lower the barrier to adoption and help you grow. Larger
            organizations with more customers are welcome too — just
            <a href="mailto:mpm@enaccess.org">reach out to us</a> so we can find
            an arrangement that works.
          </p>
        </div>
        <div class="expectation-card">
          <div class="expectation-icon">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="24"
              height="24"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <circle cx="12" cy="12" r="10" />
              <polyline points="12 6 12 12 16 14" />
            </svg>
          </div>
          <h3>Availability</h3>
          <p>
            We aim for high uptime so your operations run smoothly around the
            clock. Planned maintenance is scheduled outside peak hours and
            communicated in advance.
          </p>
        </div>
        <div class="expectation-card">
          <div class="expectation-icon">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="24"
              height="24"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <path
                d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"
              />
            </svg>
          </div>
          <h3>Support</h3>
          <p>
            Reach our team through email or Discord. We triage issues by
            severity and keep you informed until they are resolved.
          </p>
        </div>
      </div>
    </div>

    <!-- How It Works Section -->
    <div class="steps-section">
      <h2 class="section-title">Get Started in 3 Steps</h2>
      <div class="steps-grid">
        <div class="step-card">
          <div class="step-number">1</div>
          <h3>Sign Up</h3>
          <p>
            Create your MPM Cloud account and configure your company profile.
          </p>
        </div>
        <div class="step-connector">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            width="24"
            height="24"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <polyline points="9 18 15 12 9 6" />
          </svg>
        </div>
        <div class="step-card">
          <div class="step-number">2</div>
          <h3>Configure</h3>
          <p>
            Set up your meters, tariffs, and payment integrations through the
            dashboard.
          </p>
        </div>
        <div class="step-connector">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            width="24"
            height="24"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <polyline points="9 18 15 12 9 6" />
          </svg>
        </div>
        <div class="step-card">
          <div class="step-number">3</div>
          <h3>Go Live</h3>
          <p>
            Start registering customers and processing transactions immediately.
          </p>
        </div>
      </div>
    </div>

    <PageCta
      title="Ready to get started?"
      description="Join utilities across Africa that trust MPM Cloud to power their operations. No credit card required to get started."
    >
      <a
        href="https://cloud.micropowermanager.io"
        target="_blank"
        class="cta-btn"
      >
        Start with MPM Cloud
      </a>
      <a href="/get-started" class="cta-btn cta-btn-outline">
        Or self-host instead
      </a>
    </PageCta>
  </div>
</template>

<script setup lang="ts">
import PageHero from "./PageHero.vue"
import PageCta from "./PageCta.vue"
</script>

<style scoped lang="scss">
$tablet: 719px;
$mobile: 419px;

.cloud-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1.5rem;
}

.section-title {
  font-size: 2rem;
  font-weight: 700;
  color: var(--vp-c-text-1);
  text-align: center;
  margin-bottom: 0.5rem;
}

.section-subtitle {
  font-size: 1.1rem;
  color: var(--vp-c-text-2);
  text-align: center;
  max-width: 600px;
  margin: 0 auto 2.5rem;
  line-height: 1.6;
}

/* Benefits Section */
.benefits-section {
  padding: 4rem 0;
}

.benefits-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.5rem;
}

.benefit-card {
  padding: 2rem;
  background: var(--vp-c-bg-soft);
  border-radius: 1rem;
  transition: all 0.2s ease;

  &:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
  }

  .benefit-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 3.5rem;
    height: 3.5rem;
    background: rgba(27, 117, 186, 0.1);
    border-radius: 0.75rem;
    margin-bottom: 1.25rem;
    color: var(--vp-c-brand-1);
  }

  h3 {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--vp-c-text-1);
    margin: 0 0 0.5rem;
  }

  p {
    font-size: 0.9rem;
    color: var(--vp-c-text-2);
    margin: 0;
    line-height: 1.6;
  }
}

/* Comparison Section */
.comparison-section {
  padding: 4rem 0;
}

.comparison-table-wrapper {
  overflow-x: auto;
}

.comparison-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  border-radius: 1rem;
  overflow: hidden;
  border: 1px solid var(--vp-c-divider);

  th,
  td {
    padding: 1rem 1.5rem;
    text-align: left;
    font-size: 0.95rem;

    @media (max-width: $mobile) {
      padding: 0.75rem 1rem;
      font-size: 0.85rem;
    }
  }

  thead tr {
    background: var(--vp-c-bg-soft);
  }

  th {
    font-weight: 600;
    color: var(--vp-c-text-1);
  }

  tbody tr {
    border-top: 1px solid var(--vp-c-divider);
    transition: background 0.15s ease;

    &:hover {
      background: var(--vp-c-bg-soft);
    }
  }

  td {
    color: var(--vp-c-text-2);
  }

  .cloud-col {
    color: var(--vp-c-text-1);
    font-weight: 500;
    background: rgba(27, 117, 186, 0.04);
  }
}

.col-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 1rem;
  font-size: 0.8rem;
  font-weight: 600;
}

.cloud-badge {
  background: rgba(27, 117, 186, 0.15);
  color: var(--vp-c-brand-1);
}

.self-badge {
  background: var(--vp-c-bg-alt);
  color: var(--vp-c-text-2);
}

.check {
  color: #16a34a;
  font-weight: 700;
  font-size: 1.1rem;
}

.cross {
  color: #9ca3af;
  font-weight: 700;
  font-size: 1.1rem;
}

.footnote-marker {
  color: var(--vp-c-brand-1);
  font-weight: 600;
}

.comparison-footnote {
  font-size: 0.85rem;
  color: var(--vp-c-text-2);
  line-height: 1.6;
  margin-top: 1rem;
  text-align: center;
  max-width: 640px;
  margin-left: auto;
  margin-right: auto;

  a {
    color: var(--vp-c-brand-1);
    text-decoration: none;
    font-weight: 500;

    &:hover {
      text-decoration: underline;
    }
  }
}

/* Expectations Section */
.expectations-section {
  padding: 4rem 0;
}

.expectations-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;

  @media (max-width: $tablet) {
    grid-template-columns: 1fr;
  }
}

.expectation-card {
  display: flex;
  gap: 1.25rem;
  padding: 1.75rem;
  background: var(--vp-c-bg);
  border: 1px solid var(--vp-c-divider);
  border-radius: 1rem;
  transition: all 0.2s ease;

  &:hover {
    border-color: var(--vp-c-brand-1);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
  }

  &.highlighted {
    grid-column: 1 / -1;
    border-color: var(--vp-c-brand-1);
    background: rgba(27, 117, 186, 0.03);
  }

  .expectation-icon {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 3rem;
    height: 3rem;
    background: rgba(27, 117, 186, 0.1);
    border-radius: 0.75rem;
    color: var(--vp-c-brand-1);
  }

  h3 {
    font-size: 1rem;
    font-weight: 700;
    color: var(--vp-c-text-1);
    margin: 0 0 0.375rem;
  }

  p {
    font-size: 0.9rem;
    color: var(--vp-c-text-2);
    margin: 0;
    line-height: 1.6;

    a {
      color: var(--vp-c-brand-1);
      text-decoration: none;
      font-weight: 500;

      &:hover {
        text-decoration: underline;
      }
    }
  }
}

/* Steps Section */
.steps-section {
  padding: 4rem 0;
}

.steps-grid {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  flex-wrap: wrap;

  @media (max-width: $tablet) {
    flex-direction: column;
  }
}

.step-card {
  flex: 1;
  max-width: 280px;
  padding: 2rem;
  background: var(--vp-c-bg-soft);
  border-radius: 1rem;
  text-align: center;

  @media (max-width: $tablet) {
    max-width: 100%;
    width: 100%;
  }

  .step-number {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 3rem;
    height: 3rem;
    background: var(--vp-c-brand-1);
    color: white;
    border-radius: 50%;
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 1rem;
  }

  h3 {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--vp-c-text-1);
    margin: 0 0 0.5rem;
  }

  p {
    font-size: 0.9rem;
    color: var(--vp-c-text-2);
    margin: 0;
    line-height: 1.5;
  }
}

.step-connector {
  color: var(--vp-c-text-3);

  @media (max-width: $tablet) {
    transform: rotate(90deg);
  }
}
</style>
