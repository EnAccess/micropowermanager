<template>
  <div>
    <widget color="primary" title="WhatsApp Business API Settings">
      <div class="md-layout md-gutter">
        <div class="md-layout-item md-size-100">
          <form @submit.prevent="saveCredentials">
            <div class="md-layout md-gutter">
              <div class="md-layout-item md-size-50">
                <md-field :class="{ 'md-invalid': errors.phone_number_id }">
                  <label>Phone Number ID</label>
                  <md-input
                    v-model="credentials.phone_number_id"
                    required
                  ></md-input>
                  <span class="md-error">{{ errors.phone_number_id }}</span>
                </md-field>
              </div>
              <div class="md-layout-item md-size-50">
                <md-field :class="{ 'md-invalid': errors.waba_id }">
                  <label>WhatsApp Business Account (WABA) ID</label>
                  <md-input v-model="credentials.waba_id" required></md-input>
                  <span class="md-error">{{ errors.waba_id }}</span>
                </md-field>
              </div>
            </div>

            <div class="md-layout md-gutter">
              <div class="md-layout-item md-size-100">
                <md-field :class="{ 'md-invalid': errors.access_token }">
                  <label>Access Token (System User Token)</label>
                  <md-input
                    v-model="credentials.access_token"
                    type="password"
                    required
                  ></md-input>
                  <span class="md-error">{{ errors.access_token }}</span>
                </md-field>
              </div>
            </div>

            <div class="md-layout md-gutter">
              <div class="md-layout-item md-size-100">
                <md-field :class="{ 'md-invalid': errors.webhook_secret }">
                  <label>Webhook Verify Token</label>
                  <md-input
                    v-model="credentials.webhook_secret"
                    required
                  ></md-input>
                  <span class="md-error">{{ errors.webhook_secret }}</span>
                </md-field>
              </div>
            </div>

            <div class="md-layout md-gutter">
              <div class="md-layout-item md-size-50">
                <md-field
                  :class="{
                    'md-invalid': errors.transaction_confirmation_template,
                  }"
                >
                  <label>Transaction Confirmation Template Name</label>
                  <md-input
                    v-model="credentials.transaction_confirmation_template"
                  ></md-input>
                  <span class="md-error">
                    {{ errors.transaction_confirmation_template }}
                  </span>
                </md-field>
              </div>
              <div class="md-layout-item md-size-50">
                <md-field :class="{ 'md-invalid': errors.reminder_template }">
                  <label>Reminder Template Name</label>
                  <md-input v-model="credentials.reminder_template"></md-input>
                  <span class="md-error">{{ errors.reminder_template }}</span>
                </md-field>
              </div>
            </div>

            <md-button
              class="md-raised md-primary"
              type="submit"
              :disabled="loading"
            >
              Save Credentials
            </md-button>
            <md-button
              class="md-raised"
              type="button"
              @click="configurePlugin"
              :disabled="loading"
            >
              Mark plugin as configured
            </md-button>
          </form>
        </div>
      </div>
    </widget>
  </div>
</template>

<script>
import { WhatsappService } from "../../services/WhatsappService.js"

import { notify } from "@/mixins/notify.js"
import { EventBus } from "@/shared/eventbus.js"
import Widget from "@/shared/Widget.vue"

export default {
  name: "WhatsappBusinessApiOverview",
  components: { Widget },
  mixins: [notify],
  data() {
    return {
      whatsappService: new WhatsappService(),
      credentials: {
        id: null,
        access_token: "",
        phone_number_id: "",
        waba_id: "",
        webhook_secret: "",
        transaction_confirmation_template: "",
        reminder_template: "",
      },
      errors: {},
      loading: false,
    }
  },
  mounted() {
    this.fetchCredentials()
  },
  methods: {
    async fetchCredentials() {
      this.loading = true
      try {
        const response = await this.whatsappService.getCredentials()
        if (response.data && response.data.data) {
          this.credentials = response.data.data
        }
      } catch (error) {
        this.alertNotify("error", "Failed to load credentials")
      } finally {
        this.loading = false
      }
    },
    async saveCredentials() {
      this.loading = true
      this.errors = {}
      try {
        const response = await this.whatsappService.updateCredentials(
          this.credentials,
        )
        if (response.data && response.data.data) {
          this.credentials = response.data.data
          this.alertNotify("success", "Credentials saved successfully.")
        }
      } catch (error) {
        if (error.response && error.response.status === 422) {
          this.errors = error.response.data.errors
        } else {
          this.alertNotify("error", "Failed to save credentials")
        }
      } finally {
        this.loading = false
      }
    },
    async configurePlugin() {
      EventBus.$emit("WhatsappBusinessApi")
    },
  },
}
</script>

<style lang="scss" scoped></style>
