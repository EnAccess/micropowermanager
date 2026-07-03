<template>
  <div>
    <widget color="primary" :title="title">
      <div class="md-layout md-gutter">
        <div class="md-layout-item md-size-100 text-center">
          <p>
            Accept payments from your customers online. PesaPal generates a
            shareable payment link where customers can pay for meter tokens and
            solar home system services directly.
          </p>
        </div>

        <div class="md-layout-item md-size-33 md-small-size-100">
          <md-card>
            <md-card-content class="text-center">
              <md-icon class="md-size-2x md-primary">vpn_key</md-icon>
              <div class="md-subheading">1. Get Keys from PesaPal</div>
              <p>
                Sign in to your
                <a href="https://www.pesapal.com" target="_blank">
                  PesaPal Merchant Dashboard
                </a>
                and copy your Consumer Key and Consumer Secret from
                <strong>Account &rarr; API Keys</strong>
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
                Open the PesaPal overview page, paste your keys, pick your
                currency, and save. MPM will register the IPN callback with
                PesaPal automatically.
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
            Configure PesaPal
          </md-button>
        </div>
      </div>
    </widget>
  </div>
</template>

<script>
import Widget from "@/shared/Widget.vue"

export default {
  name: "PesapalConfiguration",
  components: { Widget },
  data() {
    return {
      title: "Pesapal Payment Provider",
    }
  },
  methods: {
    goToConfiguration() {
      this.$router.push("/pesapal/overview")
    },
  },
}
</script>
