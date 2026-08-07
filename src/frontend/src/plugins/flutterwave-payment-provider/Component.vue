<template>
  <div>
    <widget color="primary" :title="title">
      <div class="md-layout md-gutter">
        <div class="md-layout-item md-size-100 text-center">
          <p>
            Accept payments from your customers online. Flutterwave generates a
            shareable payment link where customers can pay for meter tokens and
            solar home system services directly.
          </p>
        </div>

        <div class="md-layout-item md-size-33 md-small-size-100">
          <md-card>
            <md-card-content class="text-center">
              <md-icon class="md-size-2x md-primary">vpn_key</md-icon>
              <div class="md-subheading">1. Get API Keys from Flutterwave</div>
              <p>
                Sign in to your
                <a href="https://dashboard.flutterwave.com" target="_blank">
                  Flutterwave Dashboard
                </a>
                and copy your Public Key, Secret Key, and Encryption Key from
                <strong>Settings &rarr; API</strong>
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
                Go to the Flutterwave overview page, paste your API keys, add
                your merchant details, and save. MPM will set up the webhook
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
            Configure Flutterwave
          </md-button>
        </div>
      </div>
    </widget>
  </div>
</template>

<script>
import Widget from "@/shared/Widget.vue"

export default {
  name: "FlutterwaveConfiguration",
  components: { Widget },
  data() {
    return {
      title: "Flutterwave Payment Provider",
    }
  },
  methods: {
    goToConfiguration() {
      this.$router.push("/flutterwave/overview")
    },
  },
}
</script>
