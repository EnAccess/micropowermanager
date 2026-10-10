<template>
  <div class="whatsapp-page">
    <div class="page-heading">
      <div>
        <h1>WhatsApp</h1>
        <p>Manage customer notifications through WhatsApp Business.</p>
      </div>
      <md-button class="md-raised md-primary" to="/whatsapp/settings">
        Configure integration
      </md-button>
    </div>

    <md-card v-if="loading">
      <md-progress-bar md-mode="indeterminate" />
    </md-card>
    <md-card v-else-if="error" class="state-card">
      <md-icon>error_outline</md-icon>
      <p>{{ error }}</p>
      <md-button class="md-raised" @click="load">Try again</md-button>
    </md-card>
    <template v-else>
      <div class="stat-grid">
        <md-card v-for="stat in stats" :key="stat.label" class="stat-card">
          <span class="stat-label">{{ stat.label }}</span>
          <strong>{{ stat.value }}</strong>
          <small>{{ stat.detail }}</small>
        </md-card>
      </div>
      <div class="content-grid">
        <md-card>
          <md-card-header>
            <div class="md-title">Connection status</div>
          </md-card-header>
          <md-card-content>
            <div class="connection-row">
              <span
                class="connection-dot"
                :class="{ connected: connection.connected }"
              />
              <div>
                <strong>
                  {{
                    connection.connected
                      ? "Connected"
                      : connection.configured
                        ? "Disabled"
                        : "Not configured"
                  }}
                </strong>
                <p v-if="connection.connected">
                  {{ connection.sender || "Sender number not provided" }}
                </p>
                <p v-else-if="connection.missingFields.length">
                  Missing: {{ connection.missingFields.join(", ") }}
                </p>
              </div>
            </div>
            <p v-if="!connection.configured" class="hint">
              Complete the required fields in Settings, then save the
              configuration to connect this integration.
            </p>
          </md-card-content>
        </md-card>
        <md-card>
          <md-card-header>
            <div class="md-title">Notification preferences</div>
          </md-card-header>
          <md-card-content>
            <div
              v-for="preference in preferences"
              :key="preference.label"
              class="preference-row"
            >
              <span>{{ preference.label }}</span>
              <status-badge
                :status="preference.enabled ? 'Enabled' : 'Disabled'"
              />
            </div>
          </md-card-content>
        </md-card>
      </div>
      <md-card>
        <md-card-header>
          <div class="md-title">Recent message activity</div>
        </md-card-header>
        <md-table v-if="messages.length">
          <md-table-row slot="md-table-row" slot-scope="{ item }">
            <md-table-cell md-label="Date">{{ item.date }}</md-table-cell>
            <md-table-cell md-label="Customer">
              {{ item.customer }}
            </md-table-cell>
            <md-table-cell md-label="Type">{{ item.type }}</md-table-cell>
            <md-table-cell md-label="Status">
              <status-badge :status="item.status" />
            </md-table-cell>
          </md-table-row>
        </md-table>
        <div v-else class="empty-state">No message activity yet.</div>
      </md-card>
    </template>
  </div>
</template>

<script>
import { whatsappService } from "../../services/WhatsAppService.js"
import StatusBadge from "../Components/StatusBadge.vue"

export default {
  name: "WhatsAppOverview",
  components: { StatusBadge },
  data() {
    return {
      connection: {},
      error: "",
      loading: true,
      messages: [],
      settings: {},
    }
  },
  computed: {
    stats() {
      return [
        {
          label: "Integration",
          value: this.settings.enabled ? "Enabled" : "Disabled",
          detail: "Plugin status",
        },
        { label: "Templates", value: "3", detail: "2 active, 1 draft" },
        { label: "Delivered today", value: "24", detail: "Customer messages" },
        { label: "Recent failures", value: "1", detail: "Needs attention" },
      ]
    },
    preferences() {
      const notifications = this.settings.notifications || {}
      return [
        { label: "Payment receipts", enabled: notifications.paymentReceipts },
        {
          label: "Low-balance warnings",
          enabled: notifications.lowBalanceWarnings,
        },
        { label: "Token deliveries", enabled: notifications.tokenDeliveries },
      ]
    },
  },
  mounted() {
    this.load()
  },
  methods: {
    async load() {
      this.loading = true
      this.error = ""
      try {
        this.settings = await whatsappService.getSettings()
        this.connection = await whatsappService.getConnectionStatus()
        this.messages = (await whatsappService.getMessages()).slice(0, 4)
      } catch (error) {
        this.error = error.message || "Unable to load WhatsApp overview."
      } finally {
        this.loading = false
      }
    },
  },
}
</script>

<style lang="scss" scoped>
.whatsapp-page {
  padding: 1rem;
}
.page-heading,
.connection-row,
.preference-row {
  align-items: center;
  display: flex;
  justify-content: space-between;
}
.page-heading {
  margin-bottom: 1.5rem;
}
.page-heading h1 {
  margin: 0;
}
.page-heading p,
.connection-row p {
  color: #607d8b;
  margin: 0.35rem 0 0;
}
.stat-grid,
.content-grid {
  display: grid;
  gap: 1rem;
  margin-bottom: 1rem;
}
.stat-grid {
  grid-template-columns: repeat(4, 1fr);
}
.content-grid {
  grid-template-columns: repeat(2, 1fr);
}
.stat-card {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
  padding: 1.25rem;
}
.stat-label,
.hint,
.empty-state {
  color: #607d8b;
}
.stat-card strong {
  font-size: 1.7rem;
}
.stat-card small {
  color: #78909c;
}
.connection-dot {
  background: #b0bec5;
  border-radius: 50%;
  height: 12px;
  margin-right: 12px;
  width: 12px;
}
.connection-dot.connected {
  background: #43a047;
}
.connection-row {
  justify-content: flex-start;
}
.preference-row {
  border-bottom: 1px solid #eceff1;
  padding: 0.65rem 0;
}
.preference-row:last-child {
  border-bottom: 0;
}
.state-card,
.empty-state {
  padding: 2rem;
  text-align: center;
}
@media (max-width: 800px) {
  .stat-grid,
  .content-grid {
    grid-template-columns: 1fr 1fr;
  }
}
@media (max-width: 520px) {
  .stat-grid,
  .content-grid {
    grid-template-columns: 1fr;
  }
  .page-heading {
    align-items: flex-start;
    flex-direction: column;
    gap: 1rem;
  }
}
</style>
