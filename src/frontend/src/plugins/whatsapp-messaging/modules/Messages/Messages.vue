<template>
  <div>
    <div class="md-layout md-gutter message-filters">
      <md-field class="md-layout-item md-size-20 md-small-size-50">
        <label>Status</label>
        <md-select v-model="filters.status">
          <md-option
            v-for="(icon, status) in statusIcons"
            :key="status"
            :value="status"
          >
            {{ status }}
          </md-option>
        </md-select>
      </md-field>
      <md-field class="md-layout-item md-size-20 md-small-size-50">
        <label>Phone</label>
        <md-input v-model="filters.phone" />
      </md-field>
      <md-datepicker
        v-for="field in ['from', 'to']"
        :key="field"
        v-model="filters[field]"
        class="md-layout-item md-size-20 md-small-size-50"
        md-immediately
      >
        <label>{{ field === "from" ? "From" : "To" }}</label>
      </md-datepicker>
      <div class="md-layout-item md-size-20">
        <md-button class="md-raised" @click="filters = emptyFilters()">
          Clear
        </md-button>
      </div>
    </div>

    <widget
      title="Messages Logs"
      color="primary"
      :paginator="paginator"
      :subscriber="subscriber"
      :show_per_page="true"
      :max_per_page="100"
    >
      <md-table v-model="messages" md-card>
        <md-table-row
          slot="md-table-row"
          slot-scope="{ item }"
          @click="toggleDetails(item)"
        >
          <md-table-cell md-label="Customer">
            {{ item.customer_name }}
          </md-table-cell>
          <md-table-cell md-label="Receiver">{{ item.receiver }}</md-table-cell>
          <md-table-cell md-label="Message" :title="item.body">
            {{ truncate(item.body) }}
            <div v-if="expandedId === item.id" class="md-caption">
              <strong>WhatsApp error {{ item.error_code }}:</strong>
              {{ item.error_message || "—" }}
              <div>
                <strong>{{ channelLabel(item) }}</strong>
                <sms-delivery-status
                  :status="item.sms_status"
                  :error-message="item.sms_error_message"
                />
              </div>
            </div>
          </md-table-cell>
          <md-table-cell md-label="Template">
            {{ item.template_name }}
          </md-table-cell>
          <md-table-cell md-label="Type">{{ item.message_type }}</md-table-cell>
          <md-table-cell md-label="Status" :title="item.whatsapp_status">
            <md-icon :class="`status--${item.whatsapp_status}`">
              {{ statusIcons[item.whatsapp_status] }}
            </md-icon>
          </md-table-cell>
          <md-table-cell md-label="Channel">
            <span :class="`channel channel--${channelClass(item)}`">
              {{ channelLabel(item) }}
            </span>
          </md-table-cell>
          <md-table-cell md-label="Time">
            {{ formatDateTime(item.created_at) }}
            <div class="md-caption">{{ lastEvent(item) }}</div>
          </md-table-cell>
        </md-table-row>
      </md-table>
    </widget>
  </div>
</template>

<script>
import moment from "moment"

import { Paginator } from "@/Helpers/Paginator.js"
import { notify } from "@/mixins/notify.js"
import { SmsGatewayService } from "@/services/SmsGatewayService.js"
import { EventBus } from "@/shared/eventbus.js"
import SmsDeliveryStatus from "@/shared/SmsDeliveryStatus.vue"
import Widget from "@/shared/Widget.vue"

const debounce = require("debounce")

const SMS_STATUS_SENT = 1
const SMS_STATUS_DELIVERED = 2

export default {
  name: "Messages",
  components: { SmsDeliveryStatus, Widget },
  mixins: [notify],
  data() {
    return {
      smsGatewayService: new SmsGatewayService(),
      areSmsGatewaysLoaded: false,
      paginator: new Paginator("/api/whatsapp-messaging/messages"),
      subscriber: "whatsapp-messages",
      messages: [],
      expandedId: null,
      filters: this.emptyFilters(),
      statusIcons: {
        accepted: "schedule",
        sent: "check",
        delivered: "done_all",
        read: "done_all",
        failed: "error",
      },
    }
  },
  computed: {
    // The available list only holds active gateway plugins, so a configured but disabled gateway counts as none.
    configuredSmsGateway() {
      const { smsGatewayId } = this.$store.getters["settings/getMainSettings"]
      return this.smsGatewayService.availableGateways.find(
        (gateway) => gateway.id === Number(smsGatewayId),
      )
    },
  },
  watch: {
    filters: {
      handler: debounce(function () {
        this.applyFilters()
      }, 400),
      deep: true,
    },
  },
  async mounted() {
    EventBus.$on("pageLoaded", this.reloadList)
    // The gateway list needs the settings permission, which the messages list does not.
    if (!this.$store.getters["auth/getPermissions"]?.includes("settings")) {
      return
    }
    try {
      await this.smsGatewayService.getAvailableGateways()
      this.areSmsGatewaysLoaded = true
    } catch (e) {
      this.alertNotify("error", e.message)
    }
  },
  beforeDestroy() {
    EventBus.$off("pageLoaded", this.reloadList)
  },
  methods: {
    emptyFilters() {
      return { status: "", phone: "", from: null, to: null }
    },
    reloadList(subscriber, data) {
      if (subscriber !== this.subscriber) return
      this.messages = data
      EventBus.$emit("widgetContentLoaded", this.subscriber, data.length)
    },
    applyFilters() {
      const { status, phone, from, to } = this.filters
      const term = Object.entries({
        status,
        phone: phone.trim(),
        from: from && moment(from).format("YYYY-MM-DD"),
        to: to && moment(to).format("YYYY-MM-DD"),
      }).filter(([, value]) => value)
      EventBus.$emit("loadPage", this.paginator, Object.fromEntries(term))
    },
    toggleDetails(item) {
      if (item.whatsapp_status !== "failed") return
      this.expandedId = this.expandedId === item.id ? null : item.id
    },
    // A failed WhatsApp message keeps a stored or failed SMS status until a gateway sends it, so only sent or delivered proves the fallback ran.
    isSentBySms(item) {
      return [SMS_STATUS_SENT, SMS_STATUS_DELIVERED].includes(item.sms_status)
    },
    channelClass(item) {
      if (item.whatsapp_status !== "failed") return "whatsapp"
      return this.isSentBySms(item) ? "sms" : "unsent"
    },
    channelLabel(item) {
      if (item.whatsapp_status !== "failed") return "WhatsApp"
      const gatewayLabel = this.configuredSmsGateway?.label
      if (this.isSentBySms(item)) return `Sent by ${gatewayLabel ?? "SMS"}`
      if (gatewayLabel) return `Not sent by ${gatewayLabel}`
      if (this.areSmsGatewaysLoaded)
        return "Not sent: no SMS gateway configured"
      return "Not sent"
    },
    truncate(text) {
      return text?.length > 40 ? `${text.slice(0, 40)}…` : text
    },
    formatDateTime(date) {
      return date ? moment(date).format("YYYY-MM-DD HH:mm") : ""
    },
    lastEvent(item) {
      const event = ["failed", "read", "delivered", "sent"].find(
        (name) => item[`${name}_at`],
      )
      return event ? `${event} ${this.formatDateTime(item[`${event}_at`])}` : ""
    },
  },
}
</script>

<style lang="scss" scoped>
// Doubled with .md-icon to outrank Vue Material's theme colour without !important.
.md-icon.status--read {
  color: #34b7f1;
}

.md-icon.status--failed {
  color: #f44336;
}

.channel {
  padding: 2px 8px;
  border-radius: 12px;
  color: #fff;
  font-size: 12px;
  background-color: #25d366;
}

.channel--sms {
  background-color: #ff9800;
}

.channel--unsent {
  background-color: #f44336;
}
.message-filters {
  padding-left: 24px;
  padding-right: 16px;
  box-sizing: border-box;
  max-width: 100%;
}
</style>
