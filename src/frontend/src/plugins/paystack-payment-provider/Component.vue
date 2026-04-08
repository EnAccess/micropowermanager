<template>
  <div>
    <widget color="primary" :title="title">
      <div class="md-layout md-gutter">
        <div class="md-layout-item md-size-100 wizard-intro">
          <p class="intro-text">
            Accept payments from your customers online. Paystack generates a
            shareable payment link where customers can pay for meter tokens and
            solar home system services directly.
          </p>
        </div>
        <div class="md-layout-item md-size-100" style="text-align: center">
          <div class="configuration-steps">
            <div class="step step-keys">
              <div class="step-number">1</div>
              <md-icon class="step-icon">vpn_key</md-icon>
              <h4>Get API Keys from Paystack</h4>
              <p>
                Sign in to your
                <a href="https://dashboard.paystack.com" target="_blank">
                  Paystack Dashboard
                </a>
                and copy your Secret Key and Public Key from
                <strong>Settings &rarr; API Keys</strong>
              </p>
            </div>

            <div class="step step-credentials">
              <div class="step-number">2</div>
              <md-icon class="step-icon">settings</md-icon>
              <h4>Enter Credentials in MPM</h4>
              <p>
                Go to the Paystack overview page, paste your API keys, add your
                merchant details, and save. MPM will set up the webhook
                automatically.
              </p>
            </div>

            <div class="step step-share">
              <div class="step-number">3</div>
              <md-icon class="step-icon">share</md-icon>
              <h4>Share Payment Link</h4>
              <p>
                Copy the generated payment URL and share it with customers via
                SMS, WhatsApp, or printed QR codes. They can pay anytime.
              </p>
            </div>
          </div>

          <div class="buttons-area">
            <md-button
              role="button"
              class="md-raised md-primary"
              @click="goToConfiguration"
            >
              Configure Paystack
            </md-button>
          </div>
        </div>
      </div>
    </widget>
  </div>
</template>

<script>
import Widget from "@/shared/Widget.vue"

export default {
  name: "PaystackConfiguration",
  components: { Widget },
  data() {
    return {
      title: "Paystack Payment Provider",
    }
  },
  methods: {
    goToConfiguration() {
      this.$router.push("/paystack/overview")
    },
  },
}
</script>

<style scoped lang="scss">
.wizard-intro {
  text-align: center;
  padding: 1.5rem 2rem 0;
}

.intro-text {
  font-size: 15px;
  font-weight: 500;
  color: #555;
  line-height: 1.6;
  max-width: 600px;
  margin: 0 auto;
}

.configuration-steps {
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
  margin: 1.5rem 0;
  gap: 1.5rem;
}

.step {
  flex: 1;
  min-width: 220px;
  max-width: 280px;
  padding: 1.5rem;
  border-radius: 8px;
  position: relative;
  text-align: center;
}

.step-keys {
  background: #e3f2fd;
  border: 1px solid #bbdefb;
}

.step-credentials {
  background: #e8f5e9;
  border: 1px solid #c8e6c9;
}

.step-share {
  background: #fff3e0;
  border: 1px solid #ffe0b2;
}

.step-number {
  position: absolute;
  top: -12px;
  left: 50%;
  transform: translateX(-50%);
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: #448aff;
  color: #fff;
  font-size: 13px;
  font-weight: 600;
  line-height: 24px;
  text-align: center;
}

.step-icon {
  font-size: 2.5rem !important;
  color: #1976d2;
  margin-bottom: 0.5rem;
}

.step h4 {
  margin: 0.75rem 0 0.5rem;
  color: #333;
  font-size: 14px;
  font-weight: 600;
}

.step p {
  color: #666;
  line-height: 1.5;
  margin: 0;
  font-size: 13px;
}

.step a {
  color: #1976d2;
  text-decoration: none;
}

.step a:hover {
  text-decoration: underline;
}

.buttons-area {
  margin: 1.5rem auto 1rem;
  display: flex;
  justify-content: center;
}

@media (max-width: 768px) {
  .configuration-steps {
    flex-direction: column;
    align-items: center;
  }

  .step {
    max-width: 90%;
  }
}
</style>
