<template>
  <div class="whatsapp-page">
    <div class="page-heading">
      <div>
        <h1>WhatsApp settings</h1>
        <p>Configure the Business API connection and customer notifications.</p>
      </div>
      <md-button class="md-raised md-primary" :disabled="saving" @click="save">
        Save settings
      </md-button>
    </div>
    <md-card>
      <md-card-header>
        <div class="md-title">Integration</div>
        <div class="md-subhead">
          Stored credentials are masked; leave a secret blank to keep the
          existing value.
        </div>
      </md-card-header>
      <md-card-content>
        <md-switch v-model="form.enabled">
          Enable WhatsApp notifications
        </md-switch>
        <p class="required-fields-hint">
          API access token, Phone Number ID, and Business Account ID are
          required for the connection status to become connected.
        </p>
        <div class="md-layout md-gutter">
          <div
            v-for="field in integrationFields"
            :key="field.key"
            class="md-layout-item md-size-50 md-medium-size-50 md-small-size-100"
          >
            <md-field :class="{ 'md-invalid': hasFieldError(field) }">
              <label :for="field.key">{{ field.label }}</label>
              <md-input
                :id="field.key"
                v-model="form[field.key]"
                :type="field.secret ? 'password' : 'text'"
                :placeholder="field.secret && form.hasApiAccessToken && field.key === 'apiAccessToken' ? '••••••••••••••••' : ''"
              />
              <span class="md-helper-text">
                <template v-if="field.key === 'apiAccessToken' && form.hasApiAccessToken">
                  ✓ Token saved in database (leave blank to keep current token)
                </template>
                <template v-else-if="field.key === 'webhookVerificationToken' && form.hasWebhookVerificationToken">
                  ✓ Token saved in database (leave blank to keep current token)
                </template>
                <template v-else>
                  {{ field.help }}
                </template>
              </span>
              <span v-if="hasFieldError(field)" class="md-error">
                This field is required.
              </span>
            </md-field>
          </div>
        </div>
      </md-card-content>
    </md-card>
    <md-card class="notification-card">
      <md-card-header>
        <div class="md-title">Notification preferences</div>
      </md-card-header>
      <md-card-content>
        <div
          v-for="item in notificationFields"
          :key="item.key"
          class="notification-row"
        >
          <div>
            <strong>{{ item.label }}</strong>
            <p>{{ item.description }}</p>
          </div>
          <md-switch
            v-model="form.notifications[item.key]"
            class="md-primary"
          />
        </div>
      </md-card-content>
    </md-card>
    <p v-if="error" class="error-message">
      <md-icon>error_outline</md-icon>
      {{ error }}
    </p>
    <p v-if="saved" class="success-message">
      <md-icon>check_circle</md-icon>
      Settings saved.
    </p>
  </div>
</template>

<script>
import { whatsappService } from "../../services/WhatsAppService.js"

export default {
  name: "WhatsAppSettings",
  data() {
    return {
      error: "",
      form: {
        enabled: false,
        apiAccessToken: "",
        phoneNumberId: "",
        businessAccountId: "",
        apiVersion: "",
        senderPhoneNumber: "",
        webhookVerificationToken: "",
        hasApiAccessToken: false,
        notifications: {},
      },
      integrationFields: [
        {
          key: "apiAccessToken",
          label: "API access token",
          help: "UI-only credential field.",
          secret: true,
        },
        {
          key: "phoneNumberId",
          label: "Phone Number ID",
          help: "The WhatsApp sender number ID.",
        },
        {
          key: "businessAccountId",
          label: "WhatsApp Business Account ID",
          help: "The business account connected to this sender.",
        },
        {
          key: "apiVersion",
          label: "API version",
          help: "For example, v20.0.",
        },
        {
          key: "senderPhoneNumber",
          label: "Sender / phone number",
          help: "Shown to operators as the configured sender.",
        },
        {
          key: "webhookVerificationToken",
          label: "Webhook verification token",
          help: "UI-only token field.",
          secret: true,
        },
      ],
      saved: false,
      validationAttempted: false,
      saving: false,
    }
  },
  computed: {
    notificationFields() {
      return [
        {
          key: "paymentReceipts",
          label: "Payment receipts",
          description: "Send a confirmation after a customer payment.",
        },
        {
          key: "lowBalanceWarnings",
          label: "Low-balance warnings",
          description: "Alert customers when their balance needs attention.",
        },
        {
          key: "tokenDeliveries",
          label: "Token deliveries",
          description: "Deliver purchased energy tokens to customers.",
        },
      ]
    },
  },
  async mounted() {
    try {
      this.form = { ...this.form, ...(await whatsappService.getSettings()) }
    } catch (error) {
      this.error = error.message || "Unable to load WhatsApp settings."
    }
  },
  methods: {
    async save() {
      this.validationAttempted = true
      this.saving = true
      this.saved = false
      this.error = ""
      if (this.integrationFields.some((field) => this.hasFieldError(field))) {
        this.saving = false
        return
      }
      try {
        const updated = await whatsappService.updateSettings(this.form)
        this.form = {
          ...this.form,
          ...updated,
        }
        this.saved = true
        this.validationAttempted = false
      } catch (error) {
        this.error = error.message || "Unable to save WhatsApp settings."
      } finally {
        this.saving = false
      }
    },
    hasFieldError(field) {
      const requiredFields = [
        "apiAccessToken",
        "phoneNumberId",
        "businessAccountId",
      ]
      if (!this.validationAttempted || !requiredFields.includes(field.key)) {
        return false
      }
      if (field.key === "apiAccessToken" && this.form.hasApiAccessToken) {
        return false
      }
      return !String(this.form[field.key] || "").trim()
    },
  },
}
</script>

<style lang="scss" scoped>
.whatsapp-page {
  padding: 1rem;
}
.page-heading {
  align-items: center;
  display: flex;
  justify-content: space-between;
  margin-bottom: 1.5rem;
}
.page-heading h1 {
  margin: 0;
}
.page-heading p,
.md-subhead,
.notification-row p {
  color: #607d8b;
  margin: 0.35rem 0 0;
}
.notification-card {
  margin-top: 1rem;
}
.required-fields-hint {
  color: #607d8b;
  font-size: 0.85rem;
  margin: 0 0 1rem;
}
.notification-row {
  align-items: center;
  border-bottom: 1px solid #eceff1;
  display: flex;
  justify-content: space-between;
  padding: 0.8rem 0;
}
.notification-row:last-child {
  border-bottom: 0;
}
.success-message {
  align-items: center;
  color: #2e7d32;
  display: flex;
  gap: 0.4rem;
  margin: 1rem 0;
}
.error-message {
  align-items: center;
  color: #c62828;
  display: flex;
  gap: 0.4rem;
  margin: 1rem 0;
}
@media (max-width: 600px) {
  .page-heading {
    align-items: flex-start;
    flex-direction: column;
    gap: 1rem;
  }
}
</style>
