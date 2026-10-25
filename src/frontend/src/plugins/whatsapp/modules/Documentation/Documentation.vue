<template>
  <div class="whatsapp-docs">
    <section class="docs-hero">
      <div class="docs-hero__text">
        <p class="docs-kicker">WhatsApp plugin</p>
        <h1>Integration guide and documentation</h1>
        <p class="docs-lede">
          Follow this guide from top to bottom to connect MicroPowerManager to
          the Meta WhatsApp Business platform. Every step uses clear actions so
          a company administrator can complete setup without developer help.
        </p>
      </div>
      <div class="docs-hero__card">
        <h2>Before you start</h2>
        <ul>
          <li>A Meta Business account for your company.</li>
          <li>A Meta Developer account at developers.facebook.com.</li>
          <li>A verified business and a WhatsApp Business phone number.</li>
          <li>Administrator access to the MicroPowerManager WhatsApp plugin.</li>
        </ul>
      </div>
    </section>

    <nav class="docs-contents" aria-label="Documentation sections">
      <a href="#setup">Setup and credentials</a>
      <a href="#templates">Message templates</a>
      <a href="#variables">Dynamic variables</a>
      <a href="#checklist">Final checklist</a>
    </nav>

    <section id="setup" class="docs-section">
      <div class="section-heading">
        <md-icon class="section-icon">settings</md-icon>
        <div>
          <h2>Setup and credentials guide</h2>
          <p>
            The plugin sends WhatsApp messages through the Meta WhatsApp
            Business API. You need three credentials from Meta: Phone Number
            ID, WhatsApp Business Account ID, and Access Token.
          </p>
        </div>
      </div>

      <div class="credential-pill">
        <span>Phone Number ID</span>
        <span>WhatsApp Business Account ID</span>
        <span>Access Token</span>
      </div>

      <div class="steps">
        <article class="step-card">
          <span class="step-number">1</span>
          <div>
            <h3>Open the Meta Developers dashboard</h3>
            <p>
              Go to developers.facebook.com and sign in with your company Meta
              account. Open My Apps, then create a new app or select the app
              your company uses for WhatsApp.
            </p>
          </div>
        </article>
        <article class="step-card">
          <span class="step-number">2</span>
          <div>
            <h3>Add the WhatsApp product</h3>
            <p>
              Inside the app, choose Add Product, then find WhatsApp and click
              Set Up. This activates the WhatsApp tools and shows your test or
              production phone number details.
            </p>
          </div>
        </article>
        <article class="step-card">
          <span class="step-number">3</span>
          <div>
            <h3>Copy your credentials</h3>
            <p>
              Open WhatsApp, then API Setup. Copy these values exactly:
            </p>
            <ul>
              <li>Phone Number ID, shown next to your sending number.</li>
              <li>WhatsApp Business Account ID, shown in the same area.</li>
              <li>Access Token, generated for a production System User.</li>
            </ul>
          </div>
        </article>
        <article class="step-card">
          <span class="step-number">4</span>
          <div>
            <h3>Save them in MicroPowerManager</h3>
            <p>
              Open WhatsApp, then Settings in MicroPowerManager. Paste each
              value into the matching field, enter your sender phone number, and
              turn on Enabled. Save, then send a test message from Message
              History.
            </p>
          </div>
        </article>
      </div>

      <div class="callout callout--info">
        <md-icon>info</md-icon>
        <div>
          <strong>Production tip:</strong> use a permanent access token created
          for a System User. Temporary tokens expire and can stop invoices,
          tokens, and balance messages until a new token is added.
        </div>
      </div>
    </section>

    <section id="templates" class="docs-section">
      <div class="section-heading">
        <md-icon class="section-icon">description</md-icon>
        <div>
          <h2>Message templates tutorial</h2>
          <p>
            Approved templates keep every business message clear and help Meta
            protect customers from spam. Create and manage templates in the Meta
            dashboard before using them in MicroPowerManager.
          </p>
        </div>
      </div>

      <div class="steps">
        <article class="step-card">
          <span class="step-number">1</span>
          <div>
            <h3>Find Message Templates</h3>
            <p>
              In Meta Business Suite or WhatsApp Manager, open your WhatsApp
              Business Account and choose Message templates.
            </p>
          </div>
        </article>
        <article class="step-card">
          <span class="step-number">2</span>
          <div>
            <h3>Create a new template</h3>
            <p>
              Click Create template. Choose a clear name, a language, and a
              category. Use Utility for receipts, tokens, and balance reminders,
              so the category matches the message purpose.
            </p>
          </div>
        </article>
        <article class="step-card">
          <span class="step-number">3</span>
          <div>
            <h3>Write a simple message body</h3>
            <p>
              Use short sentences and only details the customer needs.
              Example: “Hello {{customer_name}}, we received your payment of
              {{payment_amount}}.” Avoid unrelated links or marketing text.
            </p>
          </div>
        </article>
        <article class="step-card">
          <span class="step-number">4</span>
          <div>
            <h3>Submit and wait for approval</h3>
            <p>
              Submit the template. Meta reviews it and marks it Approved,
              Pending, or Rejected. Only approved templates can be used for
              customer messages.
            </p>
          </div>
        </article>
      </div>

      <div class="docs-table-wrap">
        <table class="docs-table">
          <thead>
            <tr>
              <th>Template status</th>
              <th>Meaning for the admin</th>
              <th>Next action</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Approved</td>
              <td>Meta allows the message to be sent.</td>
              <td>Use it in MicroPowerManager.</td>
            </tr>
            <tr>
              <td>Pending</td>
              <td>Meta is still reviewing the text.</td>
              <td>Wait before sending live messages.</td>
            </tr>
            <tr>
              <td>Rejected</td>
              <td>The text needs improvement.</td>
              <td>Edit and resubmit the template.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="callout callout--warning">
        <md-icon>warning</md-icon>
        <div>
          <strong>Keep names stable:</strong> when a template is changed inside
          Meta, update the same template record in MicroPowerManager so message
          history and reporting stay consistent.
        </div>
      </div>
    </section>

    <section id="variables" class="docs-section">
      <div class="section-heading">
        <md-icon class="section-icon">code</md-icon>
        <div>
          <h2>Dynamic variables explanation</h2>
          <p>
            Dynamic variables place real customer and payment data into a
            template. Type the placeholder in the Meta template body, and
            MicroPowerManager fills it with the matching value when the message
            is sent.
          </p>
        </div>
      </div>

      <div class="docs-table-wrap">
        <table class="docs-table">
          <thead>
            <tr>
              <th>Placeholder</th>
              <th>What it represents</th>
              <th>Example value</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>{{customer_name}}</td>
              <td>The customer receiving the message.</td>
              <td>Jane Doe</td>
            </tr>
            <tr>
              <td>{{payment_amount}}</td>
              <td>The amount paid in the transaction.</td>
              <td>5000.00</td>
            </tr>
            <tr>
              <td>{{currency}}</td>
              <td>The company currency used for the payment.</td>
              <td>TZS</td>
            </tr>
            <tr>
              <td>{{payment_date}}</td>
              <td>The date the payment was recorded.</td>
              <td>2026-10-15</td>
            </tr>
            <tr>
              <td>{{transaction_id}}</td>
              <td>The MicroPowerManager transaction reference.</td>
              <td>12345</td>
            </tr>
            <tr>
              <td>{{token}}</td>
              <td>The generated energy token delivered to the customer.</td>
              <td>123456789012</td>
            </tr>
            <tr>
              <td>{{balance}}</td>
              <td>The customer's outstanding or low-balance amount.</td>
              <td>2500.00</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="callout callout--success">
        <md-icon>check_circle</md-icon>
        <div>
          <strong>How mapping works:</strong> variables are replaced only when
          a matching value exists. If a value is missing, the system leaves a
          blank space instead of sending invalid template text.
        </div>
      </div>
    </section>

    <section id="checklist" class="docs-section">
      <div class="section-heading">
        <md-icon class="section-icon">task_alt</md-icon>
        <div>
          <h2>Final checklist</h2>
          <p>Complete these checks before using WhatsApp in daily operations.</p>
        </div>
      </div>
      <div class="checklist">
        <label><input type="checkbox" checked disabled /> Credentials are saved in the plugin.</label>
        <label><input type="checkbox" checked disabled /> The test message reaches your phone.</label>
        <label><input type="checkbox" checked disabled /> At least one receipt template is approved.</label>
        <label><input type="checkbox" checked disabled /> Dynamic variables preview correctly in Message Preview.</label>
      </div>
    </section>
  </div>
</template>

<script>
export default {
  name: "WhatsAppDocumentation",
}
</script>

<style lang="scss" scoped>
.whatsapp-docs {
  max-width: 1100px;
  margin: 0 auto;
  padding: 1rem;
  box-sizing: border-box;
}

.docs-hero {
  display: grid;
  gap: 1rem;
  grid-template-columns: minmax(0, 1.4fr) minmax(260px, 0.6fr);
  margin-bottom: 1.5rem;
}

.docs-hero__text h1 {
  font-size: 2rem;
  line-height: 1.15;
  margin: 0 0 0.75rem;
}

.docs-kicker {
  color: #0f766e;
  font-size: 0.85rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  margin: 0 0 0.5rem;
  text-transform: uppercase;
}

.docs-lede {
  color: #334155;
  font-size: 1rem;
  line-height: 1.6;
  margin: 0;
}

.docs-hero__card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 1rem;
}

.docs-hero__card h2 {
  font-size: 1rem;
  margin: 0 0 0.5rem;
}

.docs-hero__card ul {
  margin: 0;
  padding-left: 1.1rem;
}

.docs-contents {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
}

.docs-contents a {
  background: #eff6ff;
  border-radius: 999px;
  color: #1d4ed8;
  padding: 0.45rem 0.85rem;
  text-decoration: none;
}

.docs-section {
  border-top: 1px solid #e2e8f0;
  margin-top: 2rem;
  padding-top: 2rem;
}

.section-heading {
  align-items: flex-start;
  display: flex;
  gap: 0.75rem;
  margin-bottom: 1.25rem;
}

.section-icon {
  color: #0f766e;
  flex: 0 0 auto;
}

.section-heading h2 {
  font-size: 1.5rem;
  margin: 0 0 0.35rem;
}

.section-heading p {
  color: #475569;
  line-height: 1.6;
  margin: 0;
}

.credential-pill {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.credential-pill span {
  background: #ecfeff;
  border: 1px solid #a5f3fc;
  border-radius: 999px;
  color: #0f766e;
  font-weight: 600;
  padding: 0.4rem 0.75rem;
}

.steps {
  display: grid;
  gap: 1rem;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  margin-bottom: 1.25rem;
}

.step-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  display: flex;
  gap: 0.85rem;
  padding: 1rem;
}

.step-number {
  align-items: center;
  background: #0f766e;
  border-radius: 999px;
  color: #ffffff;
  display: inline-flex;
  flex: 0 0 auto;
  font-weight: 700;
  height: 1.9rem;
  justify-content: center;
  width: 1.9rem;
}

.step-card h3 {
  font-size: 1rem;
  margin: 0 0 0.35rem;
}

.step-card p,
.step-card ul {
  color: #475569;
  line-height: 1.6;
  margin: 0;
}

.callout {
  border-radius: 8px;
  display: flex;
  gap: 0.75rem;
  padding: 1rem;
}

.callout--info {
  background: #eff6ff;
  border: 1px solid #bfdbfe;
}

.callout--warning {
  background: #fffbeb;
  border: 1px solid #fde68a;
}

.callout--success {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
}

.docs-table-wrap {
  overflow-x: auto;
}

.docs-table {
  border-collapse: collapse;
  margin: 1rem 0;
  width: 100%;
}

.docs-table th,
.docs-table td {
  border: 1px solid #e2e8f0;
  padding: 0.75rem;
  text-align: left;
  vertical-align: top;
}

.docs-table th {
  background: #f8fafc;
}

.docs-table code {
  background: #f1f5f9;
  border-radius: 4px;
  padding: 0.15rem 0.3rem;
}

.checklist {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  display: grid;
  gap: 0.6rem;
  padding: 1rem;
}

.checklist label {
  color: #334155;
  display: flex;
  gap: 0.5rem;
}

@media (max-width: 760px) {
  .docs-hero {
    grid-template-columns: 1fr;
  }

  .docs-hero__text h1 {
    font-size: 1.6rem;
  }
}
</style>
