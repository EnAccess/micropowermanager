<template>
  <div>
    <widget color="primary" :title="title">
      <div class="md-layout md-gutter">
        <div class="md-layout-item md-size-100 text-center">
          <p>
            Accept payments from your customers online. Paystack generates a
            shareable payment link where customers can pay for meter tokens and
            solar home system services directly.
          </p>
        </div>

        <div class="md-layout-item md-size-33 md-small-size-100">
          <md-card>
            <md-card-content class="text-center">
              <md-icon class="md-size-2x md-primary">vpn_key</md-icon>
              <div class="md-subheading">1. Get API Keys from Paystack</div>
              <p>
                Sign in to your
                <a href="https://dashboard.paystack.com" target="_blank">
                  Paystack Dashboard
                </a>
                and copy your Secret Key and Public Key from
                <strong>Settings &rarr; API Keys</strong>
              </p>
            </md-card-content>
          </md-card>
        </div>

        <div class="md-layout-item md-size-33 md-small-size-100">
          <md-card>
            <md-card-content class="text-center">
              <md-icon class="md-size-2x md-primary">settings</md-icon>
              <div class="md-subheading">2. Enter Credentials in MPM</div>
              <p>
                Go to the Paystack overview page, paste your API keys, add your
                merchant details, and save. MPM will set up the webhook
                automatically.
              </p>
            </md-card-content>
          </md-card>
        </div>

        <div class="md-layout-item md-size-33 md-small-size-100">
          <md-card>
            <md-card-content class="text-center">
              <md-icon class="md-size-2x md-primary">share</md-icon>
              <div class="md-subheading">3. Share Payment Link</div>
              <p>
                Copy the generated payment URL and share it with customers via
                SMS, WhatsApp, or printed QR codes. They can pay anytime.
              </p>
            </md-card-content>
          </md-card>
        </div>

        <div class="md-layout-item md-size-100 text-center">
          <md-button class="md-raised md-primary" @click="goToConfiguration">
            Configure Paystack
          </md-button>
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
