<template>
  <div>
    <form
      @submit.prevent="submitCredentialForm"
      data-vv-scope="Credential-Form"
      class="Credential-Form"
    >
      <md-card>
        <md-card-content>
          <p class="help">
            Find these values in Meta for Developers: WhatsApp &gt; API Setup.
            Use a permanent system-user token for production. Leave the token
            blank to keep the saved one.
          </p>

          <md-field :class="{ 'md-invalid': errors.has('Credential-Form.phoneNumberId') }">
            <label for="phoneNumberId">Phone Number ID</label>
            <md-input
              id="phoneNumberId"
              name="phoneNumberId"
              v-model="service.credential.phoneNumberId"
              v-validate="'required'"
            />
            <span class="md-error">
              {{ errors.first("Credential-Form.phoneNumberId") }}
            </span>
          </md-field>

          <md-field>
            <label for="accessToken">
              Access Token
              {{ service.credential.hasToken ? "(saved, enter to replace)" : "" }}
            </label>
            <md-input
              id="accessToken"
              name="accessToken"
              type="password"
              autocomplete="new-password"
              v-model="service.credential.accessToken"
            />
          </md-field>

          <md-field>
            <label for="appSecret">
              Meta App Secret
              {{ service.credential.hasAppSecret ? "(saved, enter to replace)" : "" }}
            </label>
            <md-input
              id="appSecret"
              name="appSecret"
              type="password"
              autocomplete="new-password"
              v-model="service.credential.appSecret"
            />
          </md-field>

          <div class="webhook">
            <p class="help">
              Webhook for incoming messages and delivery receipts. In Meta:
              WhatsApp &gt; Configuration &gt; Webhook. When testing locally,
              replace the host below with your public tunnel address.
            </p>
            <p><strong>Callback URL:</strong> <code>{{ webhookUrl }}</code></p>
            <p>
              <strong>Verify token:</strong>
              <code>{{ service.credential.webhookVerifyToken }}</code>
            </p>
          </div>

          <md-field>
            <label for="wabaId">WhatsApp Business Account ID (optional)</label>
            <md-input id="wabaId" name="wabaId" v-model="service.credential.wabaId" />
          </md-field>

          <md-field :class="{ 'md-invalid': errors.has('Credential-Form.templateLanguage') }">
            <label for="templateLanguage">Template language code (e.g. en, en_US)</label>
            <md-input
              id="templateLanguage"
              name="templateLanguage"
              v-model="service.credential.templateLanguage"
              v-validate="'required'"
            />
          </md-field>

          <md-field :class="{ 'md-invalid': errors.has('Credential-Form.receiptTemplate') }">
            <label for="receiptTemplate">Payment receipt template name</label>
            <md-input
              id="receiptTemplate"
              name="receiptTemplate"
              v-model="service.credential.receiptTemplate"
              v-validate="'required'"
            />
          </md-field>

          <md-field :class="{ 'md-invalid': errors.has('Credential-Form.reminderTemplate') }">
            <label for="reminderTemplate">Payment reminder template name</label>
            <md-input
              id="reminderTemplate"
              name="reminderTemplate"
              v-model="service.credential.reminderTemplate"
              v-validate="'required'"
            />
          </md-field>

          <md-switch v-model="service.credential.fallbackToSms">
            Fall back to SMS if WhatsApp fails or the customer has not opted in
          </md-switch>
        </md-card-content>

        <md-progress-bar md-mode="indeterminate" v-if="loading" />
        <md-card-actions>
          <md-button class="md-primary" type="button" @click="testConnection">
            Test connection
          </md-button>
          <md-button class="md-raised md-primary" type="submit">Save</md-button>
        </md-card-actions>
      </md-card>
    </form>

    <md-card class="test-card">
      <md-card-content>
        <h4>Send a test message</h4>
        <p class="help">
          In Meta test mode, the number must be on your allowed recipients
          list. Tick the box below while your own templates are still waiting
          for Meta's approval.
        </p>
        <md-field>
          <label for="testPhone">Phone number (international format)</label>
          <md-input id="testPhone" name="testPhone" v-model="testPhone" />
        </md-field>
        <md-checkbox v-model="useHelloWorld">
          Use Meta's pre-approved "hello_world" template
        </md-checkbox>
      </md-card-content>
      <md-card-actions>
        <md-button class="md-raised" type="button" @click="sendTest">
          Send test
        </md-button>
      </md-card-actions>
    </md-card>
  </div>
</template>

<script>
import { mapGetters } from "vuex"

import { WhatsappService } from "../../services/WhatsappService.js"

import { notify } from "@/mixins/notify.js"
import { baseUrlExternal } from "@/repositories/Client/AxiosClient.js"
import { EventBus } from "@/shared/eventbus.js"

export default {
  name: "Credential",
  mixins: [notify],
  data() {
    return {
      service: new WhatsappService(),
      loading: false,
      testPhone: "",
      useHelloWorld: false,
    }
  },
  computed: {
    ...mapGetters({ authUser: "auth/getAuthenticateUser" }),
    webhookUrl() {
      return `${baseUrlExternal}/api/whatsapp/webhook/${this.authUser.companyId}`
    },
  },
  mounted() {
    this.loadCredential()
  },
  methods: {
    errorText(e) {
      return (
        (e.response && e.response.data && e.response.data.message) ||
        e.message ||
        "Request failed"
      )
    },
    async loadCredential() {
      try {
        await this.service.getCredential()
      } catch (e) {
        this.alertNotify("error", this.errorText(e))
      }
    },
    async submitCredentialForm() {
      const valid = await this.$validator.validateAll("Credential-Form")
      if (!valid) {
        return
      }
      try {
        this.loading = true
        await this.service.updateCredential()
        this.alertNotify("success", "Updated successfully")
        EventBus.$emit("Whatsapp")
      } catch (e) {
        this.alertNotify("error", this.errorText(e))
      }
      this.loading = false
    },
    async testConnection() {
      try {
        this.loading = true
        const result = await this.service.testConnection()
        if (result.ok) {
          const name = result.data && result.data.verified_name
          const number = result.data && result.data.display_phone_number
          this.alertNotify("success", `Connected: ${name || ""} ${number || ""}`)
        } else {
          this.alertNotify("error", result.error || "Connection failed")
        }
      } catch (e) {
        this.alertNotify("error", this.errorText(e))
      }
      this.loading = false
    },
    async sendTest() {
      if (!this.testPhone) {
        this.alertNotify("error", "Enter a phone number first")
        return
      }
      try {
        this.loading = true
        const result = await this.service.sendTest(this.testPhone, this.useHelloWorld)
        if (result.ok) {
          this.alertNotify("success", "Test message sent")
        } else {
          this.alertNotify("error", result.error || "Sending failed")
        }
      } catch (e) {
        this.alertNotify("error", this.errorText(e))
      }
      this.loading = false
    },
  },
}
</script>

<style lang="scss" scoped>
.help {
  color: #666;
  font-size: 13px;
}
.webhook {
  margin: 1rem 0;
}
.test-card {
  margin-top: 1rem;
}
</style>
