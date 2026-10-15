<template>
  <div class="whatsapp-page">
    <div class="page-heading">
      <div>
        <h1>Message history</h1>
        <p>Review delivery status and investigate failed messages.</p>
      </div>
    </div>
    <md-card class="test-card">
      <md-card-header>
        <div class="md-title">Send a test message</div>
        <div class="md-subhead">
          Sends a test WhatsApp message using a saved template.
        </div>
      </md-card-header>
      <md-card-content>
        <div class="md-layout md-gutter">
          <div class="md-layout-item md-size-30 md-small-size-100">
            <md-field :class="{ 'md-invalid': phoneError }">
              <label>Phone number</label>
              <md-input v-model="phone" placeholder="+258 84 000 0000" />
              <span v-if="phoneError" class="md-error">{{ phoneError }}</span>
            </md-field>
          </div>
          <div class="md-layout-item md-size-50 md-small-size-100">
            <md-field :class="{ 'md-invalid': templateError }">
              <label>Template</label>
              <md-select v-model="selectedTemplateId" placeholder="Select a template">
                <md-option
                  v-for="template in templates"
                  :key="template.id"
                  :value="template.id"
                >
                  {{ template.name }}
                </md-option>
              </md-select>
              <span v-if="templateError" class="md-error">{{ templateError }}</span>
            </md-field>
          </div>
          <div class="md-layout-item md-size-20 md-small-size-100 test-action">
            <md-button
              class="md-raised md-primary"
              :disabled="sending || !selectedTemplateId"
              @click="sendTest"
            >
              Send test message
            </md-button>
          </div>
        </div>
        <p v-if="feedback" class="success-message">{{ feedback }}</p>
        <p v-if="error" class="error-message">{{ error }}</p>
      </md-card-content>
    </md-card>
    <md-card>
      <md-card-header>
        <div class="toolbar">
          <div class="md-title">Messages</div>
          <md-field class="filter">
            <label>Filter status</label>
            <md-select v-model="statusFilter">
              <md-option value="">All statuses</md-option>
              <md-option
                v-for="status in statuses"
                :key="status"
                :value="status"
              >
                {{ status }}
              </md-option>
            </md-select>
          </md-field>
        </div>
      </md-card-header>
      <md-table v-if="filteredMessages.length">
        <md-table-row>
          <md-table-head>Date</md-table-head>
          <md-table-head>Customer</md-table-head>
          <md-table-head>Notification type</md-table-head>
          <md-table-head>Status</md-table-head>
          <md-table-head>Details</md-table-head>
          <md-table-head>External message ID</md-table-head>
          <md-table-head>Reference</md-table-head>
        </md-table-row>
        <md-table-row v-for="item in filteredMessages" :key="item.id">
          <md-table-cell>{{ item.date }}</md-table-cell>
          <md-table-cell>
            {{ item.customer }}
            <br v-if="item.phone" />
            <small>{{ item.phone }}</small>
          </md-table-cell>
          <md-table-cell>
            {{ item.type }}
          </md-table-cell>
          <md-table-cell>
            <status-badge :status="item.status" />
          </md-table-cell>
          <md-table-cell>
            <small v-if="item.errorMessage" class="error-detail">
              {{ item.errorMessage }}
            </small>
            <span v-else>-</span>
          </md-table-cell>
          <md-table-cell>
            {{ item.externalId }}
          </md-table-cell>
          <md-table-cell>
            {{ item.reference }}
          </md-table-cell>
        </md-table-row>
      </md-table>
      <div v-else class="empty-state">
        <md-icon>inbox</md-icon>
        <p>
          {{
            statusFilter
              ? "No messages match this status."
              : "No messages have been sent yet."
          }}
        </p>
      </div>
    </md-card>
  </div>
</template>

<script>
import { whatsappService } from "../../services/WhatsAppService.js"
import StatusBadge from "../Components/StatusBadge.vue"

export default {
  name: "WhatsAppMessages",
  components: { StatusBadge },
  data() {
    return {
      error: "",
      feedback: "",
      messages: [],
      phone: "",
      phoneError: "",
      sending: false,
      statusFilter: "",
      statuses: ["Queued", "Sending", "Sent", "Delivered", "Read", "Failed"],
      templates: [],
      selectedTemplateId: null,
      templateError: "",
    }
  },
  computed: {
    filteredMessages() {
      return this.messages.filter(
        (message) =>
          !this.statusFilter ||
          (message.status &&
            message.status.toLowerCase() === this.statusFilter.toLowerCase()),
      )
    },
    selectedTemplate() {
      return (
        this.templates.find(
          (template) => template.id === this.selectedTemplateId,
        ) || null
      )
    },
  },
  async mounted() {
    try {
      this.messages = await whatsappService.getMessages()
    } catch (error) {
      this.error = error.message || "Unable to load message history."
    }
    try {
      this.templates = await whatsappService.getTemplates()
      if (this.templates.length) {
        this.selectedTemplateId = this.templates[0].id
      }
    } catch (error) {
      this.error = error.message || "Unable to load message templates."
    }
  },
  methods: {
    async sendTest() {
      this.phoneError = ""
      this.templateError = ""
      this.feedback = ""
      this.error = ""
      if (!this.phone.trim()) {
        this.phoneError = "Phone number is required"
        return
      }
      if (!this.selectedTemplateId) {
        this.templateError = "Select a template"
        return
      }
      this.sending = true
      try {
        await whatsappService.sendTestMessage(this.phone, this.selectedTemplateId)
        this.feedback = "Test message sent successfully."
        this.phone = ""
      } catch (error) {
        this.error = error.message || "Unable to send the test message."
      } finally {
        this.sending = false
        try {
          this.messages = await whatsappService.getMessages()
        } catch (_) {}
      }
    },
  },
}
</script>

<style lang="scss" scoped>
.whatsapp-page {
  padding: 1rem;
}
.page-heading {
  margin-bottom: 1.5rem;
}
.page-heading h1 {
  margin: 0;
}
.page-heading p,
.md-subhead {
  color: #607d8b;
  margin: 0.35rem 0 0;
}
.test-card {
  margin-bottom: 1rem;
}
.test-action {
  align-items: center;
  display: flex;
}
.toolbar {
  align-items: center;
  display: flex;
  justify-content: space-between;
}
.filter {
  margin: 0;
  min-width: 180px;
}
.empty-state {
  color: #607d8b;
  padding: 2.5rem;
  text-align: center;
}
.success-message {
  color: #2e7d32;
}
.error-message {
  color: #c62828;
}
.error-detail {
  color: #c62828;
}
@media (max-width: 700px) {
  .toolbar {
    align-items: flex-start;
    flex-direction: column;
    gap: 0.5rem;
  }
  .filter {
    width: 100%;
  }
}
</style>
