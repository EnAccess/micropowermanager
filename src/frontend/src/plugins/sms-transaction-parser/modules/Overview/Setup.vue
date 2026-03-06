<template>
  <div>
    <md-card>
      <md-card-header>
        <div class="md-title">SMS Transaction Parser</div>
        <div class="md-subhead">
          Automatically parse incoming mobile money SMS messages (M-Pesa,
          e-Mola) and create energy transactions.
        </div>
      </md-card-header>
      <md-card-content>
        <div class="info-box">
          <h3>Default parsing rules will be installed for:</h3>
          <ul>
            <li><strong>Vodacom M-Pesa</strong></li>
            <li><strong>Movitel e-Mola</strong></li>
          </ul>
          <p class="hint">
            You can customize these rules later from the plugin settings page.
          </p>
        </div>

        <div v-if="installed" class="success-message">
          <md-icon>check_circle</md-icon>
          <span>{{ rulesCount }} parsing rule(s) installed successfully.</span>
        </div>
      </md-card-content>
      <md-progress-bar md-mode="indeterminate" v-if="loading" />
      <md-card-actions>
        <md-button
          class="md-raised md-primary"
          :disabled="loading || installed"
          @click="install"
        >
          Install Default Rules
        </md-button>
      </md-card-actions>
    </md-card>
  </div>
</template>

<script>
import { EventBus } from "@/shared/eventbus"
import { notify } from "@/mixins/notify"
import Client from "@/repositories/Client/AxiosClient"

export default {
  name: "SmsTransactionParserSetup",
  mixins: [notify],
  data() {
    return {
      loading: false,
      installed: false,
      rulesCount: 0,
    }
  },
  methods: {
    async install() {
      try {
        this.loading = true
        const { data } = await Client.post(
          "/api/sms-transaction-parser/install",
        )
        this.rulesCount = data.data.length
        this.installed = true
        this.alertNotify("success", "Default parsing rules installed")
        EventBus.$emit("SmsTransactionParser")
      } catch (e) {
        this.alertNotify("error", "Failed to install default rules")
      }
      this.loading = false
    },
  },
}
</script>

<style scoped>
.md-card {
  height: 100% !important;
}

.md-subhead {
  margin-top: 0.5rem;
  font-size: 0.9rem;
}

.info-box {
  padding: 1.5rem;
  background-color: #f8f9fa;
  border-radius: 4px;
  border-left: 4px solid #4caf50;
}

.info-box h3 {
  margin-top: 0;
  color: #333;
  font-size: 1.1rem;
  margin-bottom: 1rem;
}

.info-box ul {
  padding-left: 1.5rem;
  margin-bottom: 1rem;
}

.info-box li {
  margin-bottom: 0.5rem;
  line-height: 1.5;
}

.info-box .hint {
  margin: 0;
  color: #666;
  font-size: 0.85rem;
}

.success-message {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #4caf50;
  margin-top: 16px;
  font-size: 14px;
}
</style>
